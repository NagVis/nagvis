<?php
/*****************************************************************************
 *
 * GlobaliUserCfg.php - Class for handling the user configuration of NagVis
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
class GlobalUserCfg {
	private $CORE;
	private $CACHE;
	
	protected $config;
	protected $configFile;
	
	protected $validConfig;
	
	/**
	 * Class Constructor
	 *
	 * @param	String	$configFile			String with path to config file
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(GlobalCore $CORE, $configFile) {
		$this->config = Array();
		
		$this->validConfig = Array(
			'user' => Array(
				'username' => Array('must' => 1,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING),
				'userId' => Array('must' => 1,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'password' => Array('must' => 1,
					'editable' => 1,
					'default' => '',
					'match' => MATCH_STRING),
				'password' => Array('must' => 1,
					'editable' => 1,
					'default' => '',
					'match' => MATCH_STRING),
				'roles' => Array('must' => 1,
					'editable' => 1,
					'default' => '',
					'match' => MATCH_STRING)
				));
		
		// Apply params
		$this->CORE = $CORE;
		$this->configFile = $configFile;
		
		// Do preflight checks
		// Only proceed when the configuration file exists and is readable
		if(!$this->checkConfigExists(TRUE) || !$this->checkConfigReadable(TRUE)) {
			return FALSE;
		}
		
		// Create instance of GlobalFileCache object for caching the config
		$this->CACHE = new GlobalFileCache($this->CORE, $this->configFile, $this->CORE->MAINCFG->getValue('paths','var').'users.ini.php-'.CONST_VERSION.'-cache');
		
		if($this->CACHE->isCached(FALSE) !== -1) {
			$this->config = $this->CACHE->getCache();
		} else {
			
			// Read Main Config file, when succeeded cache it
			if($this->readConfig(TRUE)) {
				// Cache the resulting config
				$this->CACHE->writeCache($this->config, TRUE);
			}
		}
	}
	
	/**
	 * Reads the config file specified in $this->configFile
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function readConfig($printErr=1) {
		$numComments = 0;
		$sec = '';
		
		// read thx config file line by line in array $file
		$file = file($this->configFile);
		
		// Count the lines before the loop (only counts once)
		$countLines = count($file);
		
		// loop trough array
		for ($i = 0; $i < $countLines; $i++) {
			// cut spaces from beginning and end
			$line = trim($file[$i]);
			
			// don't read empty lines
			if(isset($line) && $line != '') {
				// get first char of actual line
				$firstChar = substr($line,0,1);
				
				// check what's in this line
				if($firstChar == ';') {
					// comment...
					$key = 'comment_'.($numComments++);
					$val = trim($line);
					
					if(isset($sec) && $sec != '') {
						$this->config[$sec][$key] = $val;
					} else {
						$this->config[$key] = $val;
					}
				} elseif ((substr($line, 0, 1) == '[') && (substr($line, -1, 1)) == ']') {
					// section
					$sec = trim(substr($line, 1, strlen($line)-2));
					
					// write to array
					$this->config[$sec] = Array();
					$this->config[$sec]['username'] = $sec;
				} else {
					// parameter...
					
					// separate string in an array
					$arr = explode('=',$line);
					// read key from array and delete it
					$key = trim($arr[0]);
					unset($arr[0]);
					// build string from rest of array
					$val = trim(implode('=', $arr));
					
					// remove " at beginning and at the end of the string
					if ((substr($val,0,1) == '"') && (substr($val,-1,1)=='"')) {
						$val = substr($val,1,strlen($val)-2);
					}
					
					// Special options (Arrays)
					if($key == 'roles') {
						// Explode comma separated list to array
						$val = explode(',', $val);
					}
					
					// write in config array
					if(isset($sec)) {
						$this->config[$sec][$key] = $val;
					} else {
						$this->config[$key] = $val;
					}
				}
			} else {
				$sec = '';
				$this->config['comment_'.($numComments++)] = '';
			}
		}
		
		if($this->checkConfigIsValid(1)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Checks if the main config file is valid
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkConfigIsValid($printErr) {
		// check given objects and attributes
		foreach($this->config AS $type => &$vars) {
			if(!ereg('^comment_',$type)) {
				// loop validConfig for checking: => missing "must" atributes
				$arrValidConfig = $this->validConfig['user'];
				foreach($arrValidConfig AS $key => &$val) {
					if((isset($val['must']) && $val['must'] == '1')) {
						// value is "must"
						if($this->getValue($type,$key) == '') {
							// a "must" value is missing or empty
							new GlobalMessage('ERROR', $this->CORE->LANG->getText('mainMustValueNotSet', 'ATTRIBUTE~'.$key.',TYPE~'.$type));
							return false;
						}
					}
				}
					
				// loop given elements for checking: => all given attributes valid
				foreach($vars AS $key => $val) {
					if(!ereg('^comment_',$key)) {
						$arrValidConfig = $this->validConfig['user'];
						if(!isset($arrValidConfig[$key])) {
							// unknown attribute
							if($printErr) {
								$CORE = new GlobalCore($this);
								new GlobalMessage('ERROR', $this->CORE->LANG->getText('unknownValue', 'ATTRIBUTE~'.$key.',TYPE~'.$type));
							}
							return false;
						} elseif(isset($arrValidConfig[$key]['deprecated']) && $arrValidConfig[$key]['deprecated'] == 1) {
							// deprecated option
							if($printErr) {
								$CORE = new GlobalCore($this);
								new GlobalMessage('ERROR', $this->CORE->LANG->getText('deprecatedOption', 'ATTRIBUTE~'.$key.',TYPE~'.$type));
							}
							return false;
						} else {
							if(isset($val) && is_array($val)) {
								$val = implode(',',$val);
							}
							
							// valid attribute, now check for value format
							if(!preg_match($arrValidConfig[$key]['match'],$val)) {
								// wrong format
								if($printErr) {
									$CORE = new GlobalCore($this);
									new GlobalMessage('ERROR', $this->CORE->LANG->getText('wrongValueFormat', 'TYPE~'.$type.',ATTRIBUTE~'.$key));
								}
								return false;
							}
						}
					}
				}	
			}
		}
		return true;
	}
	
	/**
	 * Checks for existing config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkConfigExists($printErr) {
		if($this->configFile != '') {
			if(file_exists($this->configFile)) {
				return true;
			} else {
				if($printErr == 1) {
					$CORE = new GlobalCore($this);
					new GlobalMessage('ERROR', $this->CORE->LANG->getText('mainCfgNotExists','MAINCFG~'.$this->configFile));
				}
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Checks for readable config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkConfigReadable($printErr) {
		if($this->configFile != '') {
			if(is_readable($this->configFile)) {
				return true;
			} else {
				if($printErr == 1) {
					$CORE = new GlobalCore($this);
					new GlobalMessage('ERROR', $this->CORE->LANG->getText('mainCfgNotReadable', 'MAINCFG~'.$this->configFile));
				}
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Returns the last modification time of the configuration file
	 *
	 * @return	Integer	Unix Timestamp
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getConfigFileAge() {
		return filemtime($this->configFile);
	}
	
	/**
	 * Public Adaptor for the isCached method of CACHE object
	 *
	 * @return  Boolean  Result
	 * @return  Integer  Unix timestamp of cache creation time or -1 when not cached
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function isCached() {
		return $this->CACHE->isCached();
	}
	
	/**
	 * Sets a config setting
	 *
	 * @param	String	$sec	Section
	 * @param	String	$var	Variable
	 * @param	String	$val	Value
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setValue($sec, $var, $val) {
		if(isset($this->config[$sec][$var]) && $val == '') {
			// Value is empty and there is an entry in the config array
			unset($this->config[$sec][$var]);
		} elseif(!isset($this->config[$sec][$var]) && $val == '') {
			// Value is empty and there is nothing in config array yet
		} else {
			// Value is set
			$this->config[$sec][$var] = $val;
		}
		
		return true;
	}

	/**
	 * A getter to provide all avilable usernames
	 *
	 * @return  Array  List of all users
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getUsers() {
		$aRet = Array();
		foreach($this->config AS $key => $var) {
			if(!ereg('^comment_', $key)) {
				$aRet[$key] = '';
			}
		}
		return $aRet;
	}
	
	/**
	 * Gets a config setting
	 *
	 * @param	String	$sec	Section
	 * @param	String	$var	Variable
	 * @return	String	$val	Value
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getValue($sec, $var) {
		// if nothing is set in the config file, use the default value
		// (Removed "&& is_array($this->config[$sec]) due to performance issues)
		if(isset($this->config[$sec]) && isset($this->config[$sec][$var])) {
			return $this->config[$sec][$var];
		} else {
			return null;
		}
	}
}
?>
