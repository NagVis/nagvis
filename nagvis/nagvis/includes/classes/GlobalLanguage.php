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
		
		$this->sCurrentLanguage = $this->MAINCFG->getValue('global', 'language');
		
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
		$this->checkLanguageAvailable();
		
		// Set the language to use
		putenv('LANG='.$this->sCurrentLanguage);
		putenv('LC_ALL='.$this->sCurrentLanguage.'.'.$this->sCurrentEncoding);
		setlocale(LC_ALL, $this->sCurrentLanguage.'.'.$this->sCurrentEncoding);

		bindtextdomain($this->textDomain, $this->MAINCFG->getValue('paths', 'language'));
		textdomain($this->textDomain);
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
	private function checkLanguageAvailable($printErr=1) {
		$CORE = new GlobalCore($this->MAINCFG, $this);
		if(in_array($this->sCurrentLanguage, $CORE->getAvailableLanguages())) {
			return TRUE;
		} else {
			if($printErr) {
				new GlobalFrontendMessage('ERROR', $this->getText('languageNotFound','LANG~'.$this->sCurrentLanguage), $this->MAINCFG->getValue('paths','htmlbase'));
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
				new GlobalFrontendMessage('ERROR', $this->getText('phpModuleNotLoaded','MODULE~gettext'), $this->MAINCFG->getValue('paths','htmlbase'));
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
