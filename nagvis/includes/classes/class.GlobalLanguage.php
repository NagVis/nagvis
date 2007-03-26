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
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalLanguage(&$MAINCFG,$languageRoot) {
		if (DEBUG) debug('Start method GlobalLanguage::GlobalLanguage($MAINCFG,'.$languageRoot.')');
		$this->MAINCFG = &$MAINCFG;
		$this->languageRoot = $languageRoot;
		$this->cachedLang = Array();
		
		$this->languageFile = $this->MAINCFG->getValue('paths', 'language').$this->MAINCFG->getValue('global', 'language').'.xml';
		$this->getLanguage();
		if (DEBUG) debug('End method GlobalLanguage::GlobalLanguage()');
	}
	
	/**
	 * Runs all the functions needed to read the language files
	 *
	 * @return	Boolean	Successful?
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getLanguage() {
		if (DEBUG) debug('Start method GlobalLanguage::getLanguage()');
		if($strLang = $this->readLanguageFile()) {
			$this->lang = $this->parseXML($strLang);
			$this->lang = $this->lang['language'];
			
			if (DEBUG) debug('End method GlobalLanguage::getLanguage(): TRUE');
			return TRUE;
		} else {
			if (DEBUG) debug('End method GlobalLanguage::getLanguage(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Reads the language file
	 *
	 * @return	String	String with the language XML file
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function readLanguageFile() {
		if (DEBUG) debug('Start method GlobalLanguage::readLanguageFile()');
		if($this->checkLanguageFileReadable(1)) {
			$data = $this->replaceSpecial(file_get_contents($this->languageFile));
			if (DEBUG) debug('End method GlobalLanguage::readLanguageFile(): String');
			return $data;
		} else {
			if (DEBUG) debug('End method GlobalLanguage::readLanguageFile(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Parses the given XML-String in an array
	 *
	 * @param	String	String with the language XML file
	 * @return	Array	Array with the language definitions
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseXML($data) {
		if (DEBUG) debug('Start method GlobalLanguage::parseXML(String)');
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
	    
		if (DEBUG) debug('End method GlobalLanguage::parseXML(String)');
	    return $ret;
	}
	
	/**
	 * Parses the given XML-String in an array (don't access directly, only needed by parseXML()
	 *
	 * @param	Array	Array with the language definitions
	 * @param	Integer	ID of the current object in Array
	 * @return	Array	Array with the language definitions
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseXMLObj($vals, &$i) {
		if (DEBUG) debug('Start method GlobalLanguage::parseXMLObj(Array(...),'.$i.')');
	    $child = Array();
		
	    while($i++ < count($vals)) {
	    	if(isset($vals[$i])) {
		        switch($vals[$i]['type']) {
		           case 'open':
		                $child[$vals[$i]['tag']] = $this->parseXMLObj($vals, $i);
		            break;
		            case 'complete':
		                $child[$vals[$i]['tag']] = $vals[$i]['value'];
		            break;
		            case 'close':
						if (DEBUG) debug('End method GlobalLanguage::parseXMLObj(): String');
		                return $child;
		            break;
		            default:
		            	// for "cdata" or anything else ... do nothing
		            break;
		        }
			}
	    }
	    
		if (DEBUG) debug('End method GlobalLanguage::parseXMLObj(): Array()');
		return $child;
	}
	
	/**
	 * Replaces some special chars like ä,ö,ü,...
	 * 
	 * @param	String	$str	Dirty String
	 * @return	String	$str	Cleaned String
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function replaceSpecial($str) {
		if (DEBUG) debug('Start method GlobalLanguage::replaceSpecial(String)');
		// FIXME: dirty workaround
		$str = str_replace('&#252;','ü',$str);
		$str = str_replace('&#246;','ö',$str);
		$str = str_replace('&#228;','ä',$str);
		$str = str_replace('&uuml;','ü',$str);
		$str = str_replace('&ouml;','ö',$str);
		$str = str_replace('&auml;','ä',$str);
		$str = str_replace('&#220;','Ü',$str);
		$str = str_replace('&#214;','Ö',$str);
		$str = str_replace('&#196;','Ä',$str);
		$str = str_replace('&Uuml;','Ü',$str);
		$str = str_replace('&Ouml;','Ö',$str);
		$str = str_replace('&Auml;','Ä',$str);
		$str = str_replace('Ã¤','ä',$str);
		$str = str_replace('Ã¶','ö',$str);
		$str = str_replace('Ã¼','ü',$str);
		if (DEBUG) debug('End method GlobalLanguage::replaceSpecial(): String');
		return $str;
	}
	
	/**
	 * Check if the Language-File is readable
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
    function checkLanguageFileReadable($printErr) {
		if (DEBUG) debug('Start method GlobalLanguage::checkLanguageFileReadable('.$printErr.')');
        if(!is_readable($this->languageFile)) {
        	if($printErr == 1) {
				// This has to be a manual error message - using an error message from language File would cause in a loop
				print "<script>alert('Impossible to read from the language file (".$this->languageFile.")');</script>";
			}
			if (DEBUG) debug('End method GlobalLanguage::checkLanguageFileReadable(): FALSE');
	        return FALSE;
        } else {
			if (DEBUG) debug('End method GlobalLanguage::checkLanguageFileReadable(): TRUE');
        	return TRUE;
        }
    }
    
    
    function getMessageText($id,$replace='',$mergeWithGlobal=TRUE) {
		if (DEBUG) debug('Start method GlobalLanguage::getMessageText('.$id.','.$replace.','.$mergeWithGlobal.')');
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
		if (DEBUG) debug('End method GlobalLanguage::getMessageText(): '.$ret);
    	return $ret;
    }
    
    function getMessageTitle($id,$replace='',$mergeWithGlobal=TRUE) {
		if (DEBUG) debug('Start method GlobalLanguage::getMessageTitle('.$id.','.$replace.','.$mergeWithGlobal.')');
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
		if (DEBUG) debug('End method GlobalLanguage::getMessageTitle(): '.$ret);
		return $ret;
    }
    
    function getLabel($id,$replace='',$mergeWithGlobal=TRUE) {
		if (DEBUG) debug('Start method GlobalLanguage::getLabel('.$id.','.$replace.','.$mergeWithGlobal.')');
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
		if (DEBUG) debug('End method GlobalLanguage::getLabel(): '.$ret);
		return $ret;
    }
	
	/**
	 * Gets the text of an id
	 *
	 * @param	String	$languagePath		Path to the Language String in the XML File
	 * @param	String	$replace			Strings to Replace
	 * @param	Boolean $mergeWithGlobal	Merge with Global Type
	 * @return	String	String with Language String
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getText($languagePath,$replace='',$mergeWithGlobal=TRUE) {
		if (DEBUG) debug('Start method GlobalLanguage::getText('.$languagePath.','.$replace.','.$mergeWithGlobal.')');
		$arrLang = Array();
		$strLang = '';
		$arrLanguagePath = explode(':',$languagePath);
		# DEBUG: print_r($arrLanguagePath);
	    # [0] => backend
	    # [1] => ndomy
	    # [2] => messages
	    # [3] => errorSelectingDb
	    # [4] => title
		
		/* DEPRECATED
		// merge first level with global if $mergeWithGlobal is TRUE
		// search not only the array of $this->lang['xy'], also search $this->lang['global']
		if($arrLanguagePath[0] != 'global') {
			if($mergeWithGlobal) {
				$arrLang = $this->mergeArrayRecursive($this->lang[$arrLanguagePath[0]],$this->lang['global']);
			} else {
				$arrLang = $this->lang[$arrLanguagePath[0]];
			}
		} else {
			$arrLang = $this->lang['global'];
		}
		
		// same procedure with second level
		if($arrLanguagePath[1] != 'global') {
			if($mergeWithGlobal) {
				if(!isset($arrLang[$arrLanguagePath[1]])) {
					$arrLang[$arrLanguagePath[1]] = Array();
				}
				$arrLang = $this->mergeArrayRecursive($arrLang[$arrLanguagePath[1]],$arrLang['global']);
			} else {
				$arrLang = $arrLang[$arrLanguagePath[1]];
			}
		} else {
			$arrLang = $arrLang['global'];
		}*/
		
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
			$strLang = preg_replace("/\[(\/|)(i|b)\]/i","<$1$2>",$strLang);
			
			if($replace != '') {
				$arrReplace = explode(',', $replace);
				for($i=0;$i<count($arrReplace);$i++) {
					if(isset($arrReplace[$i])) {
						// If = are in the text, they'l be cut: $var = explode('=', str_replace('~','=',$arrReplace[$i]));
						$var = explode('~', $arrReplace[$i]);
						$strLang = str_replace("[".$var[0]."]", $var[1], $strLang);
					}
				}
				
				if (DEBUG) debug('End method GlobalLanguage::getText(): '.$strLang);
				// Return string with replaced text
				return $strLang;
			} else {
				if (DEBUG) debug('End method GlobalLanguage::getText(): '.$strLang);
				// Return without replacement
				return $strLang;
			}
		} else {
			if (DEBUG) debug('End method GlobalLanguage::getText(): TranslationNotFound: '.$languagePath);
			// Return Translation not Found error
			return 'TranslationNotFound: '.$languagePath;
		}
	}
}
?>