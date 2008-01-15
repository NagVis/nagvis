<?php
/**
* This Class handles the NagVis language files
*/
class GlobalLanguage {
	var $MAINCFG;
	var $languageFile;
	var $lang;
	var $languageRoot;
	var $cachedLang;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG
	 * @param	String			$type		Type of language-file
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalLanguage(&$MAINCFG,$languageRoot) {
		$this->MAINCFG = &$MAINCFG;
		$this->languageRoot = $languageRoot;
		$this->cachedLang = Array();
		
		$this->languageFile = $this->MAINCFG->getValue('paths', 'language').$this->MAINCFG->getValue('global', 'language').'.xml';
		$this->getLanguage();
	}
	
	/**
	 * Runs all the functions needed to read the language files
	 *
	 * @return	Boolean	Successful?
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getLanguage() {
		
		/**
		 * If the language cache vars are set and the cache is newer than the
		 * language file modification time load the cache. If not, read the
		 * language file and parse the XML
		 *
		 * The NagVis version also has to be recognized in the language cache. If 
		 * not this could cause problems when using different NagVis versions.
		 */
		if(isset($_SESSION['nagvis_lang_cache']) 
			&& isset($_SESSION['nagvis_version']) 
			&& isset($_SESSION['nagvis_lang_cache_time']) 
			&& is_array($_SESSION['nagvis_lang_cache']) 
			&& $_SESSION['nagvis_version'] == CONST_VERSION 
			&& $_SESSION['nagvis_lang_cache_time'] > $this->getLanguageFileModificationTime()) {
			$this->lang = $_SESSION['nagvis_lang_cache'];
			return TRUE;
		} else {
			if($strLang = $this->readLanguageFile()) {
				$this->lang = $this->parseXML($strLang);
				$this->lang = $this->lang['language'];
				
				$_SESSION['nagvis_version'] = CONST_VERSION;
				$_SESSION['nagvis_lang_cache_time'] = time();
				$_SESSION['nagvis_lang_cache'] = $this->lang;
				
				return TRUE;
			} else {
				return FALSE;
			}
		}
	}
	
	/**
	 * Gets the last modification time of the language file
	 *
	 * @return	Integer Unix timestamp with last modification time
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getLanguageFileModificationTime() {
		if($this->checkLanguageFileReadable(1)) {
			$time = filemtime($this->languageFile);
			return $time;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Reads the language file
	 *
	 * @return	String	String with the language XML file
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readLanguageFile() {
		if($this->checkLanguageFileReadable(1)) {
			$data = $this->replaceSpecial(file_get_contents($this->languageFile));
			return $data;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Parses the given XML-String in an array
	 *
	 * @param	String	String with the language XML file
	 * @return	Array	Array with the language definitions
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseXML($data) {
		$vals = Array();
		$index = Array();
		$ret = Array();
		$i = 0;
		
		$data = trim($data);
		
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parse_into_struct($parser, $data, $vals, $index);
		xml_parser_free($parser);
		
		$tagname = $vals[$i]['tag'];
		$ret[$tagname] = $this->parseXMLObj($vals, $i);
		
		return $ret;
	}
	
	/**
	 * Parses the given XML-String in an array (don't access directly, only needed by parseXML()
	 *
	 * @param	Array	Array with the language definitions
	 * @param	Integer	ID of the current object in Array
	 * @return	Array	Array with the language definitions
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseXMLObj($vals, &$i) {
		$child = Array();
		
		// Don't count in the loop
		$numVals = count($vals);
		
		while($i++ < $numVals) {
			if(isset($vals[$i])) {
				switch($vals[$i]['type']) {
					case 'open':
						$child[$vals[$i]['tag']] = $this->parseXMLObj($vals, $i);
					break;
					case 'complete':
						$child[$vals[$i]['tag']] = $vals[$i]['value'];
					break;
					case 'close':
						return $child;
					break;
					default:
						// for "cdata" or anything else ... do nothing
					break;
				}
			}
		}
		
		return $child;
	}
	
	/**
	 * Replaces some special chars like �,�,�,...
	 * 
	 * @param	String	$str	Dirty String
	 * @return	String	$str	Cleaned String
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceSpecial($str) {
		// DEPRECATED: This is buggy in PHP 4.3, using own methods
		//$str = html_entity_decode($str,ENT_NOQUOTES,'UTF-8');
		$str = $this->htmlEntityDecodeUtf8($str);
		return $str;
	}
	
	/**
	 * Returns the utf string corresponding to the unicode value (from php.net)
	 *
	 * @param	String	raw string
	 * @return	String	UTF8 encoded string
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function htmlEntityDecodeUtf8($string) {
		static $trans_tbl;
		
		// replace numeric entities
		$string = preg_replace('~&#x(?:[0-9a-f]+);~ei', 'code2utf(hexdec("\\1"))', $string);
		$string = preg_replace('~&#(?:[0-9]+);~e', 'code2utf(\\1)', $string);
		
		// replace literal entities
		if (!isset($trans_tbl)) {
			$trans_tbl = array();
			
			foreach (get_html_translation_table(HTML_ENTITIES) as $val=>$key) {
				$trans_tbl[$key] = utf8_encode($val);
			}
		}
		
		return strtr($string, $trans_tbl);
	}
	
	/**
	 * Returns the utf string corresponding to the unicode value (from php.net)
	 *
	 * @param	Integer	Unicode value
	 * @return	String 	UTF string
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function code2utf($num) {
		if ($num < 128) return chr($num);
		if ($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
		if ($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		if ($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		return '';
	}
	
	/**
	 * Check if the Language-File is readable
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkLanguageFileReadable($printErr) {
		if(!is_readable($this->languageFile)) {
			if($printErr == 1) {
				// This has to be a manual error message - using an error message from language File would cause in a loop
				print '<script>alert(\'Impossible to read from the language file ('.$this->languageFile.')\');</script>';
			}
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	function getMessageText($id,$replace='',$mergeWithGlobal=TRUE) {
		if($replace == '' && isset($this->cachedLang[$id]) && isset($this->cachedLang[$id]['text']) && $this->cachedLang[$id]['text'] != '') {
			$ret = $this->cachedLang[$id]['text'];
		} else {
			$ret = $this->getText($this->languageRoot.':messages:'.$id.':text',$replace,$mergeWithGlobal);
			if($replace == '') {
				if(!isset($this->cachedLang[$id])) {
					$this->cachedLang[$id] = Array();
				}
				$this->cachedLang[$id]['text'] = $ret;
			}
			
		}
		return $ret;
	}
	
	function getMessageTitle($id,$replace='',$mergeWithGlobal=TRUE) {
		if($replace == '' && isset($this->cachedLang[$id]) && isset($this->cachedLang[$id]['title']) && $this->cachedLang[$id]['title'] != '') {
			$ret = $this->cachedLang[$id]['title'];
		} else {
			$ret = $this->getText($this->languageRoot.':messages:'.$id.':title',$replace,$mergeWithGlobal);
			if($replace == '') {
				if(!isset($this->cachedLang[$id])) {
					$this->cachedLang[$id] = Array();
				}
				$this->cachedLang[$id]['label'] = $ret;
			}
		}
		return $ret;
	}
	
	function getLabel($id,$replace='',$mergeWithGlobal=TRUE) {
		if($replace == '' && isset($this->cachedLang[$id]) && isset($this->cachedLang[$id]['label']) && $this->cachedLang[$id]['label'] != '') {
			$ret = $this->cachedLang[$id]['label'];
		} else {
			$ret = $this->getText($this->languageRoot.':labels:'.$id.':text',$replace,$mergeWithGlobal);
			if($replace == '') {
				if(!isset($this->cachedLang[$id])) {
					$this->cachedLang[$id] = Array();
				}
				$this->cachedLang[$id]['label'] = $ret;
			}
	 	}
		return $ret;
	}
	
	/**
	 * Gets the text of an id
	 *
	 * @param	String	$languagePath		Path to the Language String in the XML File
	 * @param	String	$replace			Strings to Replace
	 * @param	Boolean $mergeWithGlobal	Merge with Global Type
	 * @return	String	String with Language String
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getText($languagePath,$replace='',$mergeWithGlobal=TRUE) {
		$arrLang = Array();
		$strLang = '';
		$arrLanguagePath = explode(':',$languagePath);
		# DEBUG: print_r($arrLanguagePath);
		# [0] => backend
		# [1] => ndomy
		# [2] => messages
		# [3] => errorSelectingDb
		# [4] => title
		
		if($mergeWithGlobal && isset($this->lang['global']['global'][$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]]) 
			  && $this->lang['global']['global'][$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]] != '') {
			$strLang = $this->lang['global']['global'][$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]];
		} else {
			if($mergeWithGlobal && isset($this->lang[$arrLanguagePath[0]]['global'][$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]]) 
				  && $this->lang[$arrLanguagePath[0]]['global'][$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]] != '') {
				$strLang = $this->lang[$arrLanguagePath[0]]['global'][$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]];
			} else {
				if(isset($this->lang[$arrLanguagePath[0]][$arrLanguagePath[1]][$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]]) 
					  && $this->lang[$arrLanguagePath[0]][$arrLanguagePath[1]][$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]] != '') {
					$strLang = $this->lang[$arrLanguagePath[0]][$arrLanguagePath[1]][$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]];
				}
			}
		}
		
		// filter type, messages/labels
		if($strLang != '') {
			// Replace [i],[b] and their ending tags with HTML code
			$strLang = preg_replace('/\[(\/|)(i|b|br)\]/i',"<$1$2>",$strLang);
			
			if($replace != '') {
				$arrReplace = explode(',', $replace);
				$size = count($arrReplace);
				for($i=0;$i<$size;$i++) {
					if(isset($arrReplace[$i])) {
						// If = are in the text, they'l be cut: $var = explode('=', str_replace('~','=',$arrReplace[$i]));
						$var = explode('~', $arrReplace[$i]);
						$strLang = str_replace('['.$var[0].']', $var[1], $strLang);
					}
				}
				
				// Return string with replaced text
				return $strLang;
			} else {
				// Return without replacement
				return $strLang;
			}
		} else {
			// Return Translation not Found error
			return 'TranslationNotFound: '.$languagePath;
		}
	}
}
?>