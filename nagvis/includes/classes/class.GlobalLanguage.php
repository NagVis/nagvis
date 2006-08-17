<?php
/**
* This Class handles the NagVis language files
*/
class GlobalLanguage {
	var $MAINCFG;
	var $languageFile;
	var $lang;
	
	/*var $indexes = Array();
	var $values = Array();
	var $nb = 0;*/
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG
	 * @param	String			$type		Type of language-file
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalLanguage(&$MAINCFG,$type='') {
		$this->MAINCFG = &$MAINCFG;
		
		if(isset($type) && $type != '') {
			if($type == 'errors') {
				$type = '';
			} else {
				$type .= '_';
			}
		} elseif($this->MAINCFG->getRuntimeValue('wui')) {
			$type = 'wui_';
		}
		
		$this->languageFile = $this->MAINCFG->getValue('paths', 'cfg').'languages/german.xml';
		//$this->languageFile = $this->MAINCFG->getValue('paths', 'cfg').'languages/'.$this->MAINCFG->getValue('global', 'language').'.xml';
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
			
			print_r($this->lang);
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
		$str = str_replace('&uuml','ü',$str);
		$str = str_replace('&ouml','ö',$str);
		$str = str_replace('&auml','ä',$str);
		$str = str_replace('&#220;','Ü',$str);
		$str = str_replace('&#214;','Ö',$str);
		$str = str_replace('&#196;','Ä',$str);
		$str = str_replace('&Uuml','ü',$str);
		$str = str_replace('&Ouml','ö',$str);
		$str = str_replace('&Auml','ä',$str);
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
	
	/**
	 * Gets the text of an id
	 *
	 * @param	Integer	$myid	Id in Language file
	 * @return	String	Text of the id
	 * @author	FIXME
	 * @fixme	FIXME: cleanup
     */
	function getText($myid) {
		if(count($this->indexes)==1) {
			print "The language coudln't be loaded";
			return "";
		} else {
			$key = array_search($myid,$this->indexes); 
			if ($key!==null&&$key!==false) {
				return $this->values[$key];
			} else {
				print "<script>alert('Impossible to find the index ".$myid." in the language file".$this->$filepath."');</script>";
				return FALSE;
			}
		}
	}
	
	/**
	 * Gets the text of an id and replaces something
	 *
	 * @param	Integer	$myid		Id in Language file
	 * @param	String	$myvalues	String with the replacements
	 * @return	String	Text of the id
	 * @author	FIXME
	 * @fixme	FIXME: cleanup
     */
	function getTextReplace($myid,$myvalues) {
		if (count($this->indexes)==1) {
			print "The language couldn't be loaded";
			return 0;
		} else {
			$key = array_search($myid,$this->indexes);
			if ($key !== null && key !== false) {
				$message = $this->values[$key];
				$vars = explode(',', $myvalues);
				for($i=0;$i<count($vars);$i++) {
					$var = explode('=', $vars[$i]);
					$message = str_replace("[".$var[0]."]", $var[1], $message);
				}
				return $message;
			} else {
				print "<script>alert('Impossible to find the index ".$myid." in the language file".$this->$filepath."');</script>";
				return "";
			}
		}
	}
	
	/**
	 * Gets the text silent
	 *
	 * @param	Integer	$myid		Id in Language file
	 * @return	String	Text of the id
	 * @author	FIXME
	 * @fixme	FIXME: cleanup
     */
	function getTextSilent($myid) {
		if(count($this->indexes) == 1) {
			return "";
		} else {
			// Cause of the new config-format this has to be case insensitive
			$key = $this->arrayLSearch($myid,$this->indexes);
			if($key !== null && $key !== false && !is_array($key)) {
				return str_replace("'","\'",$this->values[$key]);
			} else {
				return "";
			}
		}
	}
	
	/**
	 * Searches an array case insensitive
	 *
	 * @param	String	$str	String to search for
	 * @param	Array	$array	Array to be searched in
	 * @return	String/Array	Key(s) of the String
	 * @author	FIXME
     */
	function arrayLSearch($str,$array) {
		$found = Array();
		
		foreach($array as $k => $v) {
			if(@strtolower($v) == @strtolower($str)) {
				$found[] = $k;
			}
		}
		
		$f = @count($found);
		
		if($f == 0) {
			return FALSE;
		} elseif($f == 1) {
			return $found[0];
		} else {
			return $found;
		}
	}
}
?>