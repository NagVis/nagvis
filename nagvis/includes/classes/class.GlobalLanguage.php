<?php
/**
* This Class handles the NagVis language files
*/
class GlobalLanguage {
	var $MAINCFG;
	var $languageFile;
	var $lang;
	var $languageRoot;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG
	 * @param	String			$type		Type of language-file
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalLanguage(&$MAINCFG,$languageRoot) {
		$this->MAINCFG = &$MAINCFG;
		$this->languageRoot = $languageRoot;
		
		$this->languageFile = $this->MAINCFG->getValue('paths', 'cfg').'languages/'.$this->MAINCFG->getValue('global', 'language').'.xml';
		$this->getLanguage();
	}
	
	/**
	 * Runs all the functions needed to read the language files
	 *
	 * @return	Boolean	Successful?
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getLanguage() {
		if($strLang = $this->readLanguageFile()) {
			$this->lang = $this->parseXML($strLang);
			$this->lang = $this->lang['language'];
			
			return TRUE;
		} else {
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
		if($this->checkLanguageFileReadable(1)) {
			$fp = fopen($this->languageFile, "r");
			$data = fread($fp, filesize($this->languageFile));
			fclose($fp);
			
			return $this->replaceSpecial($data);
		} else {
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
	    $vals = Array();
	    $index = Array();
	    $ret = Array();
	    $i = 0;
	
	    $data = trim($data);
	    
	    $parser = xml_parser_create();
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
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseXMLObj($vals, &$i) {
	    $child = Array();
		
	    while($i++ < count($vals)) {
	        switch ($vals[$i]['type']) {
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
        if(!is_readable($this->languageFile)) {
        	if($printErr == 1) {
				// This has to be a manual error message - using an error message from language File would cause in a loop
				print "<script>alert('Impossible to read from the language file (".$this->languageFile.")');</script>";
			}
	        return FALSE;
        } else {
        	return TRUE;
        }
    }
    
    
    function getMessageText($id,$replace='',$mergeWithGlobal=TRUE) {
    	return $this->getText($this->languageRoot.':messages:'.$id.':text',$replace,$mergeWithGlobal);
    }
    
    function getMessageTitle($id,$replace='',$mergeWithGlobal=TRUE) {
    	return $this->getText($this->languageRoot.':messages:'.$id.':title',$replace,$mergeWithGlobal);
    }
    
    function getLabel($id,$replace='',$mergeWithGlobal=TRUE) {
    	return $this->getText($this->languageRoot.':labels:'.$id.':text',$replace,$mergeWithGlobal);
    }
    
    function mergeArrayRecursive($array1, $array2) {
		if (is_array($array2) && count($array2)) {
			foreach ($array2 as $k => $v) {
				if (is_array($v) && count($v)) {
					$array1[$k] = $this->mergeArrayRecursive($array1[$k], $v);
				} else {
					$array1[$k] = $v;
				}
			}
		} else {
			$array1 = $array2;
		}
		
		return $array1;
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
		$arrLanguagePath = explode(':',$languagePath);
		# DEBUG: print_r($arrLanguagePath);
	    # [0] => backend
	    # [1] => ndomy
	    # [2] => messages
	    # [3] => errorSelectingDb
	    # [4] => title
		
		
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
				$arrLang = $this->mergeArrayRecursive($arrLang[$arrLanguagePath[1]],$arrLang['global']);
			} else {
				$arrLang = $arrLang[$arrLanguagePath[1]];
			}
		} else {
			$arrLang = $arrLang['global'];
		}
		
		// filter type, messages/labels
		if($arrLang[$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]] != '') {
			$strLang = $arrLang[$arrLanguagePath[2]][$arrLanguagePath[3]][$arrLanguagePath[4]];
			
			// replace html-codes, FIXME quick 'n dirty - could be done with regex
			$strLang = str_replace('[i]','<i>',$strLang);
			$strLang = str_replace('[/i]','</i>',$strLang);
			$strLang = str_replace('[I]','<i>',$strLang);
			$strLang = str_replace('[/I]','</i>',$strLang);
			$strLang = str_replace('[b]','<b>',$strLang);
			$strLang = str_replace('[/b]','</b>',$strLang);
			$strLang = str_replace('[B]','<b>',$strLang);
			$strLang = str_replace('[/B]','</b>',$strLang);
			
			if($replace != '') {
				$arrReplace = explode(',', $replace);
				for($i=0;$i<count($arrReplace);$i++) {
					// If = are in the text, they'l be cut: $var = explode('=', str_replace('~','=',$arrReplace[$i]));
					$var = explode('~', $arrReplace[$i]);
					$strLang = str_replace("[".$var[0]."]", $var[1], $strLang);
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