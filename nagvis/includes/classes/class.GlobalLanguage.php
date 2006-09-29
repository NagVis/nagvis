<?php
/**
* This Class handles the NagVis language files
*/
class GlobalLanguage {
	var $MAINCFG;
	var $languageFile;
	var $lang;
	var $type;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG
	 * @param	String			$type		Type of language-file
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalLanguage(&$MAINCFG,$type) {
		$this->MAINCFG = &$MAINCFG;
		$this->type = $type;
		
		$this->languageFile = $this->MAINCFG->getValue('paths', 'cfg').'languages/'.$this->MAINCFG->getValue('global', 'language').'.txt';
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
    
    function getMessageText($id,$space='global',$replace='',$mergeWithGlobal=TRUE) {
    	return $this->getText($id,'text','messages',$space,$replace,$mergeWithGlobal);
    }
    
    function getMessageTitle($id,$space='global',$replace='',$mergeWithGlobal=TRUE) {
    	return $this->getText($id,'title','messages',$space,$replace,$mergeWithGlobal);
    }
    
    function getLabel($id,$space='global',$replace='',$mergeWithGlobal=TRUE) {
    	return $this->getText($id,'text','labels',$space,$replace,$mergeWithGlobal);
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
	 * @param	String	$id					Name of the Language String
	 * @param	String	$key				Key to the Language String (text|label|...)
	 * @param	String	$type				Type of Language (label|message)
	 * @param	String	$space				Default global
	 * @param	String	$replace			Strings to Replace
	 * @param	Boolean $mergeWithGlobal	Merge with Global Type
	 * @return	String	String with Language String
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getText($id,$key,$type,$space='global',$replace='',$mergeWithGlobal=TRUE) {
		// search not only the array of $lang[$this->type], also search $lang['global']
		if($this->type != 'global') {
			if($mergeWithGlobal) {
				$arrLang = $this->mergeArrayRecursive($this->lang[$this->type],$this->lang['global']);
			} else {
				$arrLang = $this->lang[$this->type];
			}
		} else {
			$arrLang = $this->lang['global'];
		}
		
		// same procedure...
		if($space != 'global') {
			if($mergeWithGlobal) {
				$arrLang = $this->mergeArrayRecursive($arrLang[$space],$arrLang['global']);
			} else {
				$arrLang = $arrLang[$space];
			}
		} else {
			$arrLang = $arrLang['global'];
		}
		
		if($arrLang[$type][$id][$key] != '') {
			$strLang = $arrLang[$type][$id][$key];
			
			if($replace != '') {
				$arrReplace = explode(',', $replace);
				for($i=0;$i<count($arrReplace);$i++) {
					$var = explode('=', $arrReplace[$i]);
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
			return 'TranslationNotFound: '.$this->type.':'.$space.':'.$type.':'.$id.':'.$key;
		}
	}
}
?>