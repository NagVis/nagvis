<?php
/*****************************************************************************
 *
 * GlobalLanguage.php - Class for handling languages in NagVis
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
	private $SHANDLER = null;
	private $CORE = null;
	private $MAINCFG;
	private $textDomain;
	private $sCurrentLanguage;
	private $sCurrentEncoding;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG
	 * @param	String			$type		Type of language-file
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($MAINCFG, $textDomain = 'nagvis') {
		$this->MAINCFG = $MAINCFG;
		$this->textDomain = $textDomain;
		
		$this->SHANDLER = new CoreSessionHandler($this->MAINCFG->getValue('global', 'sesscookiedomain'), 
		                                         $this->MAINCFG->getValue('global', 'sesscookiepath'),
		                                         $this->MAINCFG->getValue('global', 'sesscookieduration'));
		
		$this->sCurrentLanguage = $this->gatherCurrentLanguage();
		
		// Fix old language entries (english => en_US, german => de_DE)
		// FIXME: Remove this in 1.5, mark this as deprecated somewhere
		switch($this->sCurrentLanguage) {
			case 'german':
				$this->sCurrentLanguage = 'de_DE';
			break;
			case 'english':
				$this->sCurrentLanguage = 'en_US';
			break;
		}
		
		// Append encoding (UTF8)
		$this->sCurrentEncoding = 'UTF-8';
		
		// Check for gettext extension
		$this->checkGettextSupport();
		
		// Check if choosen language is available
		$this->checkLanguageAvailable($this->sCurrentLanguage);
		
		// Set the language to use
		putenv('LANG='.$this->sCurrentLanguage);
		putenv('LC_ALL='.$this->sCurrentLanguage.'.'.$this->sCurrentEncoding);
		setlocale(LC_ALL, $this->sCurrentLanguage.'.'.$this->sCurrentEncoding);

		bindtextdomain($this->textDomain, $this->MAINCFG->getValue('paths', 'language'));
		textdomain($this->textDomain);
	}
	
	/**
	 * Reads the language to use in NagVis
	 *
	 * @return  String
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function gatherCurrentLanguage() {
		$sReturn = '';
		$aMethods = $this->MAINCFG->getValue('global', 'language_detection');
		
		foreach($aMethods AS $sMethod) {
			if($sReturn == '') {
				switch($sMethod) {
					case 'session':
						// Read the user choice from session
						$sReturn = $this->SHANDLER->get('userLanguage');
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
						// Read the language from url
						$sReturn = $this->getUserLanguage();
						
						// Save language to session when user set one
						if($sReturn != '') {
				 			$this->SHANDLER->set('userLanguage', $sReturn);
				 		}
					break;
					case 'config':
						// Read default language from configuration
						$sReturn = $this->MAINCFG->getValue('global', 'language');
					break;
						
					default:
						//FIXME: Error handling
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
		
		$CORE = new GlobalCore($this->MAINCFG, $this);
		$UHANDLER = new CoreUriHandler($CORE);
		
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
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkLanguageAvailable($sLang, $printErr=1) {
		$CORE = new GlobalCore($this->MAINCFG, $this);
		
		// Checks two things:
		// a) The language availabilty in the filesyste,
		// b) Listed language in global/language_available config option
		if(in_array($sLang, $CORE->getAvailableLanguages()) && in_array($sLang, $this->MAINCFG->getValue('global', 'language_available'))) {
			return TRUE;
		} else {
			if($printErr) {
				new GlobalMessage('ERROR', $this->getText('languageNotFound', Array('LANG' => $sLang)), $this->MAINCFG->getValue('paths','htmlbase'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks if gettext is supported in this PHP version
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkGettextSupport() {
		if (!extension_loaded('gettext')) {
			dl('gettext.so');
			if (!extension_loaded('gettext')) {
				new GlobalMessage('ERROR', $this->getText('phpModuleNotLoaded','MODULE~gettext'), $this->MAINCFG->getValue('paths','htmlbase'));
				return FALSE;
			} else {
				return TRUE;
			}
		} else {
			return TRUE;	
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
	public function getText($id, $replace = NULL) {
		$ret = $this->getTextOfId($id);
		
		if($replace !== NULL) {
			$ret = $this->getReplacedString($ret, $replace);
		}
		
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
		return gettext($s);
	}
	
	
	/**
	 * Gets the text of an id
	 *
	 * @param   String        String Plain language string
	 * @param   String/Array  String or Array with macros to replace 
	 * @return  String        String Replaced language string
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getReplacedString($sLang, $replace) {
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
