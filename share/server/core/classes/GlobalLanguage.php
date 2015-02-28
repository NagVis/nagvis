<?php
/*****************************************************************************
 *
 * GlobalLanguage.php - Class for handling languages in NagVis
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/

/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalLanguage {
    private $USERCFG = null;
    private $textDomain;
    private $sCurrentLanguage;
    private $sCurrentEncoding;
    private $cache = Array();

    /**
     * Class Constructor
     *
     * @param	String			$type		Type of language-file
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct($textDomain = 'nagvis') {
        $this->textDomain = $textDomain;

        // Append encoding (UTF8)
        $this->sCurrentEncoding = 'UTF-8';

        // Check for gettext extension
        require_once('gettext.inc');

        $this->setLanguage();

        T_bindtextdomain($this->textDomain, cfg('paths', 'language'));
        T_bind_textdomain_codeset($this->textDomain, $this->sCurrentEncoding);

        T_textdomain($this->textDomain);

        // Check if native gettext or php-gettext is used
        if(DEBUG&&DEBUGLEVEL&2) {
            if(locale_emulation()) {
                debug('GlobalLanguage: Using php-gettext for translations');
            } else {
                debug('GlobalLanguage: Using native gettext for translations');
            }
        }
    }

    /**
     * Sets the language to be used for future localized strings
     * while processing the current page.
     */
    public function setLanguage($handleUserCfg = false) {
        if($handleUserCfg)
            $this->USERCFG = new CoreUserCfg();

        $this->sCurrentLanguage = $this->gatherCurrentLanguage();

        // Check if choosen language is available
        $this->checkLanguageAvailable($this->sCurrentLanguage, true, true);

        // Set the language to us
        putenv('LC_ALL='.$this->sCurrentLanguage.'.'.$this->sCurrentEncoding);
        putenv('LANG='.$this->sCurrentLanguage.'.'.$this->sCurrentEncoding);
        T_setlocale(LC_MESSAGES, $this->sCurrentLanguage.'.'.$this->sCurrentEncoding);
    }

    /**
     * Reads the language to use in NagVis
     *
     * @return  String
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function gatherCurrentLanguage() {
        $sReturn = '';
        $aMethods = cfg('global', 'language_detection');

        foreach($aMethods AS $sMethod) {
            if($sReturn == '') {
                switch($sMethod) {
                    case 'session':
                        // Read the user choice from user options
                        if($this->USERCFG !== null)
                            $sReturn = $this->USERCFG->getValue('language', '');
                    break;
                    case 'browser':
                        // Read the prefered language from the users browser
                        $sReturn = $this->getBrowserLanguage();
                    break;
                    case 'ip':
                        //@todo: It is also possible to get the country via IP and
                        //       indirectly the language from that country.
                    break;
                    case 'user':
                        // Read the language from url or user config
                        $sReturn = $this->getUserLanguage();

                        // Save language to user config when user set one
                        if($sReturn != ''
                           && $this->USERCFG !== null
                           && $sReturn != $this->USERCFG->getValue('language', ''))
                            $this->USERCFG->doSet(Array('language' => $sReturn));
                    break;
                    case 'config':
                        // Read default language from configuration
                        $sReturn = cfg('global', 'language');
                    break;

                    default:
                        throw new NagVisException(
                            $this->getText('Invalid mode [MODE] in language_detection option.',
                                          Array('MODE' => $sMethod)));
                    break;
                }
            }
        }

        return $sReturn;
    }

    /**
     * Checks if the user requested a language by the url
     *
     * @return  String
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function getUserLanguage() {
        $sLang = '';

        $UHANDLER = new CoreUriHandler();

        // Load the specific params to the UriHandler
        $UHANDLER->parseModSpecificUri(Array('lang' => MATCH_LANGUAGE_EMPTY));

        if($UHANDLER->isSetAndNotEmpty('lang')
           // Check if language is available
           && $this->checkLanguageAvailable($UHANDLER->get('lang'), false)) {

          // Get given language
            $sLang = $UHANDLER->get('lang');
        }

        return $sLang;
    }

    /**
     * Trys to detect the language of the browser by analyzing the
     * HTTP_ACCEPT_LANGUAGE var. Returns a language string when found one language
     * which is available
     *
     * @return  String
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function getBrowserLanguage() {
        $return = Array();
        $langs = Array();

        if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // break up string into pieces (languages and q factors)
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

            if(count($lang_parse[1])) {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);

                // set default to 1 for any without q factor
                foreach ($langs as $lang => $val) {
                    if ($val === '') $langs[$lang] = 1;
                }

                // sort list based on value
                arsort($langs, SORT_NUMERIC);
            }
        }

        // Check if the languages are available and then return the most important language which is available
        $sLang = '';
        foreach($langs AS $key => $val) {
            // Format the language keys
            if(strpos($key, '-') !== false) {
                $a = explode('-', $key);

                $key = $a[0] . '_' . strtoupper($a[1]);
            }

            if($this->checkLanguageAvailable($key, false)) {
                $sLang = $key;
                break;
            }
        }

        return $sLang;
    }

    /**
     * Returns the string representing the current language
     *
     * @return  String
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getCurrentLanguage() {
        return $this->sCurrentLanguage;
    }

    /**
     * Checks if the choosen language is available
     *
     * @param   String     Language definition string
     * @param   Boolean    Print error message or not
     * @param   Boolean    Check language_available config or not
     * @return  Boolean
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkLanguageAvailable($sLang, $printErr = 1, $ignoreConf = false) {
        global $CORE;
        // Checks two things:
        // a) The language availabilty in the filesyste,
        // b) Listed language in global/language_available config option
        if(in_array($sLang, $CORE->getAvailableLanguages())
           && ($ignoreConf == true
               || ($ignoreConf == false
                   && in_array($sLang, cfg('global', 'language_available'))))) {
            return TRUE;
        } else {
            if($printErr) {
                throw new NagVisException($this->getText('languageNotFound', Array('LANG' => $sLang)));
            }
            return FALSE;
        }
    }

    /**
     * Calls the real getText method and replaces some macros after fetching the
     * text
     *
     * @param	String	String to be localized
     * @param	String	Replace options
     * @return	String	Localized String
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getText($id, $replace = null) {
        // Use cache if available
        // FIXME: At the moment the cache can only be used without macros
        if($replace === null && isset($this->cache[$id]))
            return $this->cache[$id];

        $ret = $this->getTextOfId($id);

        if($replace !== null) {
            $ret = self::getReplacedString($ret, $replace);
        }

        // When the translated string is equal to the requested id and some macros
        // should be replaced it is possible that there is a problem with the
        // gettext/translation mechanism. Then append the imploded
        if($id === $ret && $replace !== null) {
            if(!is_array($replace)) {
                $ret .= 'Opts: '.$replace;
            } else {
                // Implode does not return the keys. So simply use json_encode here
                // to show the keys to the user
                $ret .= 'Opts: '.json_encode($replace);
            }
        }

        // Store in cache for this page processing
        if($replace === null && !isset($this->cache[$id]))
            $this->cache[$id] = $ret;

        return $ret;
    }

    /**
     * Gets the text of an id
     *
     * @param	String	String to be localized
     * @return	String	Localized String
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function getTextOfId($s) {
        return T_gettext($s);
    }


    /**
     * Gets the text of an id
     *
     * @param   String        String Plain language string
     * @param   String/Array  String or Array with macros to replace
     * @return  String        String Replaced language string
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    static public function getReplacedString($sLang, $replace) {
        if(!is_array($replace)) {
            $aReplace = explode(',', $replace);
            for($i = 0, $size = count($aReplace); $i < $size; $i++) {
                if(isset($aReplace[$i])) {
                    $var = explode('~', $aReplace[$i]);
                    $sLang = str_replace('['.$var[0].']', $var[1], $sLang);
                }
            }
        } else {
            foreach($replace AS $key => $val) {
                $sLang = str_replace('['.$key.']', $val, $sLang);
            }
        }

        // Return string with replaced text
        return $sLang;
    }
}
?>
