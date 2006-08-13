<?php
/**
* This Class handles the NagVis language files
*/
class GlobalLanguage {
	var $MAINCFG;
	var $languageFile;
	
	var $indexes = Array();
	var $values = Array();
	var $nb = 0;
	
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
		
		$this->languageFile = $this->MAINCFG->getValue('paths', 'cfg').'languages/' . $type . $this->MAINCFG->getValue('global', 'language').'.txt';
	}
	
	/**
	 * Reads the language file
	 *
	 * @return	Boolean	Is Successful?
	 * @author	FIXME
	 */
	function readLanguageFile() {
		if($this->checkLanguageFileReadable(1)) {
			# we fill the indexes and values arrays, by reading all the file lines
			array_push($this->indexes,"");
			array_push($this->values,"");
			
			$fic = fopen($this->languageFile,"r");
			while (!feof($fic)) {
				$myline = fgets($fic, 4096);
				$myline = substr($myline,0,strlen($myline)-1);
				if(strlen($myline)>0) {
					$temp = explode("~",$myline,2);
					array_push($this->indexes,$temp[0]);
					array_push($this->values,$this->replaceSpecial($temp[1]));
					$this->nb = $this->nb+1;	
				}
			}
			fclose ($fic);
			
			return TRUE;
		} else {
			return FALSE;
		}
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