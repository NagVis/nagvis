<?
##########################################################################
##     	                           NagVis                               ##
##               *** Klasse zum verarbeiten der Config ***              ##
##                               Lizenz GPL                             ##
##########################################################################

/**
* This Class handles the NagVis configuration file
*/
class nagvisconfig {
	var $config;
	var $configFile;
	
	/**
	* Constructor
	*
	* @param config $CONFIG
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function nagvisconfig($configFile) {
		$this->config = Array();
		$this->configFile = $configFile;
		
		$this->readConfig();
	}
	
	/**
	* Reads the config file specified in $configFile
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function readConfig() {
		$this->config = Array();
		$numComments = 0;
		$sec = '';
		
		// Check for config file and read permissions
		if(@file_exists($this->configFile) && @is_readable($this->configFile)) {
			// read thx config file line by line in array $file
			$file = @file($this->configFile);
			
			// loop trough array
			for ($i = 0; $i < @count($file); $i++) {
				// cut spaces from beginning and end
				$line = @trim($file[$i]);
				
				// don't read empty lines
				if(isset($line) && $line != '') {
					// get first char of actual line
					$firstChar = @substr($line,0,1);
				
					// check what's in this line
					if($firstChar == ';') {
						// comment...
						$key = "comment_".($numComments++);
						$val = @trim($line);
						
						if(isset($sec) && $sec != '')
							$this->config[$sec][$key] = $val;
						else
							$this->config[$key] = $val;
					} elseif ((@substr($line, 0, 1) == "[") && (@substr($line, -1, 1)) == "]") {
						// section
						$sec = @strtolower(@trim(@substr($line, 1, @strlen($line)-2)));
						
						// In Array schreiben
						$this->config[$sec] = Array();
					} else {
						// parameter...
						
						// seperate string in an array
						$arr = @explode("=",$line);
						// read key from array and delete it
						$key = @strtolower(@trim($arr[0]));
						unset($arr[0]);
						// build string from rest of array
						$val = @trim(@implode("=", $arr));
						
						// remove " at beginign and at the end of the string
						if ((@substr($val,0,1) == "\"") && (@substr($val,-1,1)=="\"")) {
							$val = @substr($val,1,@strlen($val)-2);
						}
						
						// write in config array
						if(isset($sec))
							$this->config[$sec][$key] = $val;
						else
							$this->config[$key] = $val;
							
					}
				} else {
					$sec = '';
					$this->config["comment_".($numComments++)] = '';
				}
			}
		} else {
			//FIXME: Errorhandling
		}
	}
	
	/**
	* Writes the config file completly from array $configFile
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function writeConfig() {
		// Check for config file and read permissions
		if(file_exists($this->configFile) && is_writeable($this->configFile)) {
			foreach($this->config as $key => $item) {
				if(is_array($item)) {
					$content .= "[".$key."]\n";
					foreach ($item as $key2 => $item2) {
						if(@substr($key2,0,8) == "comment_") {
							$content .= $item2."\n";
						} else {
							if(is_numeric($item2) || is_bool($item2))
								$content .= $key2."=".$item2."\n";
							else
							$content .= $key2."=\"".$item2."\"\n";
						}
					}       
				} elseif(@substr($key,0,8) == "comment_") {
					$content .= $item."\n";
				} else {
					if(is_numeric($item) || is_bool($item))
						$content .= $key."=".$item."\n";
					else
						$content .= $key."=\"".$item."\"\n";
				}
			}
			
			if(!$handle = fopen($this->configFile, 'w+')) {
				//FIXME: Errorhandling
				return FALSE;
			}
			
			if(!fwrite($handle, $content)) {
				//FIXME: Errorhandling
				return FALSE;
			}
			
			fclose($handle);
			return TRUE;
		} else {
			//FIXME: Errorhandling
			return FALSE;
		}
	}
	
	/**
	* Finds the Sections of a Var
	*
	* @param string $var
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function findSecOfVar($var) {
		foreach($this->config AS $key => $item) {
			if(is_array($item)) {
				foreach ($item AS $key2 => $item2) {
					if(@substr($key2,0,8) != "comment_") {
						if($key2 == $var) {
							return $key;
						}
					}
				}       
			}
		}
		return FALSE;
	}
	
	/**
	* Sets a config setting
	*
	* @param string $sec
	* @param string $var
	* @param string $val
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function setValue($sec, $var, $val) {
       $this->config[$sec][$var] = $val;
	}
	
	/**
	* Gets a config setting
	*
	* @param string $sec
	* @param string $var
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getValue($sec, $var) {
		return $this->config[$sec][$var];
	}
}
?>
