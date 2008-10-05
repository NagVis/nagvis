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
	var $MAINCFG;
	var $textDomain;
	var $sCurrentLanguage;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG
	 * @param	String			$type		Type of language-file
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalLanguage(&$MAINCFG, $textDomain = 'nagvis') {
		$this->MAINCFG = &$MAINCFG;
		$this->textDomain = $textDomain;
		
		$this->sCurrentLanguage = $this->MAINCFG->getValue('global', 'language');
		
		// Fix old language entries (english => en_US, german => de_DE)
		switch($this->sCurrentLanguage) {
			case 'german':
				$this->sCurrentLanguage = 'de_DE';
			break;
			case 'english':
				$this->sCurrentLanguage = 'en_US';
			break;
		}
		
		// Append encoding (UTF8)
		$this->sCurrentLanguage .= '.UTF-8';
		
		// Check for gettext extension
		$this->checkGettextSupport();
		
		// Set the language to use
		putenv('LC_ALL='.$this->sCurrentLanguage);
		setlocale(LC_ALL, $this->sCurrentLanguage);

		bindtextdomain($this->textDomain, $this->MAINCFG->getValue('paths', 'language'));
		textdomain($this->textDomain);
	}
	
	/**
	 * Checks if gettext is supported in this PHP version
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkGettextSupport() {
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
	function getText($id, $replace='') {
		$ret = $this->getTextOfId($id);
		
		if($replace != '') {
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
	function getTextOfId($s) {
		$sLocalized = gettext($s);
		
		// filter type, messages/labels
		if($sLocalized != '') {
			// Replace [i],[b] and their ending tags with HTML code
			// FIXME
			$sLocalized = preg_replace('/\[(\/|)(i|b|br)\]/i',"<$1$2>", $sLocalized);
			
			return $sLocalized;
		}
	}
	
	
	/**
	 * Gets the text of an id
	 *
	 * @param	String	String Plain language string
	 * @return	String	String Replaced language string
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getReplacedString($sLang, $sReplace) {
		$aReplace = explode(',', $sReplace);
		$size = count($aReplace);
		for($i = 0; $i < $size; $i++) {
			if(isset($aReplace[$i])) {
				// If = are in the text, they'l be cut: $var = explode('=', str_replace('~','=',$arrReplace[$i]));
				$var = explode('~', $aReplace[$i]);
				$sLang = str_replace('['.$var[0].']', $var[1], $sLang);
			}
		}
		
		// Return string with replaced text
		return $sLang;
	}
}
?>
