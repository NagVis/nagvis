<?php
/**
 * This Class handles the NagVis configuration file
 */
class GlobalMainCfg {
	var $config;
	var $runtimeConfig;
	var $configFile;
	var $validConfig;
	
	/**
	 * Class Constructor
	 *
	 * @param	String	$configFile			String with path to config file
	 * @param	String	$localConfigFile	String with path to local config file
	 * @author Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalMainCfg($configFile,$localConfigFile) {
		$this->config = Array();
		$this->runtimeConfig = Array();
		
		$this->validConfig = Array(
			'global' => Array('backend' => Array('must' => 1),
							'language' => Array('must' => 1),
							'defaulticons' => Array('must' => 1),
							'rotatemaps' => Array('must' => 1),
							'maps' => Array('must' => 1),
							'displayheader' => Array('must' => 1),
							'headercount' => Array('must' => 1),
							'usegdlibs' => Array('must' => 1),
							'refreshtime' => Array('must' => 1)),
			'wui' => Array('autoupdatefreq' => Array('must' => 1)),
			'paths' => Array('base' => Array('must' => 1),
							'cfg' => Array('must' => 1),
							'icon' => Array('must' => 1),
							'map' => Array('must' => 1),
							'mapcfg' => Array('must' => 1),
							'htmlbase' => Array('must' => 1),
							'htmlcgi' => Array('must' => 1),
							'htmlimages' => Array('must' => 1),
							'htmlicon' => Array('must' => 1),
							'htmlmap' => Array('must' => 1),
							'htmldoku' => Array('must' => 1)),
			'backend_ndomy' => Array('dbhost' => Array('must' => 1),
							'dbport' => Array('must' => 1),
							'dbname' => Array('must' => 1),
							'dbuser' => Array('must' => 1),
							'dbpass' => Array('must' => 1),
							'dbprefix' => Array('must' => 1),
							'dbinstanceid' => Array('must' => 1),
							'maxtimewithoutupdate' => Array('must' => 1)),
			'backend_html' => Array('cgiuser' => Array('must' => 1),
							'cgi' => Array('must' => 1)),
			'includes' => Array('header' => Array('must' => 1)),
			'internal' => Array('version' => Array('must' => 1, 'locked' => 1),
							'title' => Array('must' => 1, 'locked' => 1)));
		
		// Default - minimal - config initialisation
		// if an error with the main-cfg-file occours and we can't get the settings 
		// we have to set defaults here
		$this->config['global']['language'] = 'english';
		
		// Read Main Config file
		$this->configFile = $configFile;
		$this->readConfig(1);
		
		// Read local config if exists
		$this->configFile = $localConfigFile;
		$this->readConfig(1);
	}
	
    /**
	 * Reads the config file specified in $this->configFile
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function readConfig($printErr=1) {
		$numComments = 0;
		$sec = '';
		
		// Check for config file and read permissions
		if($this->checkNagVisConfigReadable($printErr)) {
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
			return FALSE;
		}
	}
	
    /**
	 * Writes the config file completly from array $this->configFile
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function writeConfig() {
		// Check for config file and read permissions
		if($this->checkNagVisConfigReadable(1) && $this->checkNagVisConfigWriteable(1)) {
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
				$FRONTEND = new GlobalPage($this);
		        $FRONTEND->messageToUser('ERROR','25', '');
				return FALSE;
			}
			
			if(!fwrite($handle, $content)) {
				$FRONTEND = new GlobalPage($this);
		        $FRONTEND->messageToUser('ERROR','19', '');
				return FALSE;
			}
			
			fclose($handle);
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
    /**
	 * Checks for readable config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkNagVisConfigReadable($printErr) {
		if($this->configFile != '') {
			if(file_exists($this->configFile) && is_readable($this->configFile)) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this);
		            $FRONTEND->messageToUser('ERROR','18','MAINCFG~'.$this->configFile);
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
    /**
	 * Checks for writeable config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkNagVisConfigWriteable($printErr) {
		if(file_exists($this->configFile) && is_writeable($this->configFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this);
	            $FRONTEND->messageToUser('ERROR','19','MAINCFG~'.$this->configFile);
			}
			return FALSE;
		}
	}
	
    /**
	 * Checks for readable MapCfgFolder
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkMapCfgFolderReadable($printErr) {
		if(file_exists($this->getValue('paths', 'mapcfg')) && @is_readable($this->getValue('paths', 'mapcfg'))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this);
	            $FRONTEND->messageToUser('ERROR','26','MAPPATH~'.$this->getValue('paths', 'mapcfg'));
			}
			return FALSE;
		}
	}
	
    /**
	 * Checks for writeable MapCfgFolder
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkMapCfgFolderWriteable($printErr) {
		if(file_exists(substr($this->getValue('paths', 'mapcfg'),0,-1)) && @is_writable(substr($this->getValue('paths', 'mapcfg'),0,-1))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this);
	            $FRONTEND->messageToUser('ERROR','27','MAPPATH~'.$this->getValue('paths', 'mapcfg'));
			}
			return FALSE;
		}
	}
	
    /**
	 * Finds the Section of a var
	 *
	 * @param	String	$var	Config variable
	 * @return	String	Section of the var
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
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
	 * @param	String	$sec	Section
	 * @param	String	$var	Variable
	 * @param	String	$val	Value
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function setValue($sec, $var, $val) {
       $this->config[$sec][$var] = $val;
       
       return TRUE;
	}
	
    /**
	 * Gets a config setting
	 *
	 * @param	String	$sec	Section
	 * @param	String	$var	Variable
	 * @return	String	$val	Value
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getValue($sec, $var) {
		return $this->config[$sec][$var];
	}
	
    /**
	 * Sets a runtime config value
	 *
	 * @param	String	$var	Variable
	 * @param	String	$val	Value
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function setRuntimeValue($var, $val) {
       $this->runtimeConfig[$var] = $val;
       
       return TRUE;
	}
	
    /**
	 * Gets a runtime config value
	 *
	 * @param	String	$var	Variable
	 * @return	String	$val	Value
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getRuntimeValue($var) {
		return $this->runtimeConfig[$var];
	}
}
?>
