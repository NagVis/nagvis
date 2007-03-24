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
	 * @author Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalMainCfg($configFile) {
		if (DEBUG) debug('Start method GlobalMainCfg::GlobalMainCfg('.$configFile.')');
		$this->config = Array();
		$this->runtimeConfig = Array();
		
		$this->validConfig = Array(
			'global' => Array('language' => Array('must' => 1,
												 'editable' => 1,
												'default' => 'english'),
							'rotatemaps' => Array('must' => 1,
												 'editable' => 1,
												'default' => '0'),
							'maps' => Array('must' => 1,
												 'editable' => 1,
												'default' => 'demo,demo2'),
							'displayheader' => Array('must' => 1,
												 'editable' => 1,
												'default' => '1'),
							'headercount' => Array('must' => 1,
												 'editable' => 1,
												'default' => '4'),
							'usegdlibs' => Array('must' => 1,
												 'editable' => 1,
												'default' => '1'),
							'refreshtime' => Array('must' => 1,
												 'editable' => 1,
												'default' => '60')),
			'defaults' => Array('backend' => Array('must' => 0,
												 'editable' => 0,
												'default' => 'ndomy_1'),
							'icons' => Array('must' => 1,
												 'editable' => 1,
												'default' => 'std_medium'),
							'backgroundcolor' => Array('must' => 0,
												 'editable' => 1,
												'default' => '#fff'),
							'recognizeservices' => Array('must' => 0,
												 'editable' => 1,
												'default' => 1),
							'onlyhardstates' => Array('must' => 0,
												 'editable' => 1,
												'default' => 0)
							),
			'wui' => Array('autoupdatefreq' => Array('must' => 1,
												 'editable' => 1,
												'default' => '25')),
			'paths' => Array('base' => Array('must' => 1,
												 'editable' => 1,
												'default' => '/usr/local/nagios/share/nagvis/'),
							'cfg' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/usr/local/nagios/share/nagvis/nagvis/etc/'),
							'icon' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/usr/local/nagios/share/nagvis/nagvis/images/iconsets/'),
							'shape' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/usr/local/nagios/share/nagvis/nagvis/images/shapes/'),
							'language' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/usr/local/nagios/share/nagvis/nagvis/includes/languages/'),
							'map' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/usr/local/nagios/share/nagvis/nagvis/images/maps/'),
							'mapcfg' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/usr/local/nagios/share/nagvis/nagvis/etc/maps/'),
							'htmlbase' => Array('must' => 1,
												 'editable' => 1,
												'default' => '/nagios/nagvis'),
							'htmlcgi' => Array('must' => 1,
												 'editable' => 1,
												'default' => '/nagios/cgi-bin'),
							'htmlimages' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/nagios/nagvis/nagvis/images/'),
							'htmlicon' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/nagios/nagvis/nagvis/images/iconsets/'),
							'htmlshape' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/nagios/nagvis/nagvis/images/shape/'),
							'htmlmap' => Array('must' => 0,
												 'editable' => 0,
												'default' => '/nagios/nagvis/nagvis/images/maps/'),
							'htmldoku' => Array('must' => 1,
												 'editable' => 0,
												'default' => 'http://luebben-home.de/nagvis-doku/nav.html?nagvis/')),
			'backend' => Array(
							'backendtype' => Array('must' => 1,
												'default' => 'ndomy'),
							'options' => Array('ndomy' => Array('dbhost' => Array('must' => 1,
																					 'editable' => 1,
																					'default' => 'localhost'),
																'dbport' => Array('must' => 0,
																					 'editable' => 1,
																					'default' => '3306'),
																'dbname' => Array('must' => 1,
																					 'editable' => 1,
																					'default' => 'db_nagios'),
																'dbuser' => Array('must' => 1,
																					 'editable' => 1,
																					'default' => 'root'),
																'dbpass' => Array('must' => 1,
																					 'editable' => 1,
																					'default' => 'root'),
																'dbprefix' => Array('must' => 0,
																					 'editable' => 1,
																					'default' => 'nagios_'),
																'dbinstancename' => Array('must' => 0,
																					 'editable' => 1,
																					'default' => 'default'),
																'maxtimewithoutupdate' => Array('must' => 0,
																					 'editable' => 1,
																					'default' => '180')),
												'html' => Array('cgiuser' => Array('must' => 1,
																					 'editable' => 1,
																					'default' => 'nagiosadmin'),
																'cgi' => Array('must' => 1,
																					 'editable' => 1,
																					'default' => '/usr/local/nagios/sbin/')))),
			'includes' => Array('header' => Array('must' => 1,
												 'editable' => 1,
												'default' => 'header.nagvis.inc')),
			'internal' => Array('version' => Array('must' => 1,
												 'editable' => 0,
												'default' => '1.0b3',
												'locked' => 1),
							'title' => Array('must' => 1,
												 'editable' => 0,
												'default' => 'NagVis 1.0b3',
												'locked' => 1)));
		
		// Default - minimal - config initialisation
		// if an error with the main-cfg-file occours and we can't get the settings 
		// set defaults here
		// DEPRECATED: $this->config['global']['language'] = $this->validConfig['global']['language']['default'];
		
		// Read Main Config file
		$this->configFile = $configFile;
		$this->readConfig(1);
		
		// want to reduce the paths in the NagVis config, but don't want to hardcode the paths relative from the bases
		$base = $this->getValue('paths','base');
		$htmlBase = $this->getValue('paths','htmlbase');
		$this->validConfig['paths']['cfg']['default'] = $base."nagvis/etc/";
		$this->validConfig['paths']['icon']['default'] = $base."nagvis/images/iconsets/";
		$this->validConfig['paths']['shape']['default'] = $base."nagvis/images/shapes/";
		$this->validConfig['paths']['language']['default'] = $base."nagvis/includes/languages/";
		$this->validConfig['paths']['class']['default'] = $base."nagvis/includes/classes/";
		$this->validConfig['paths']['map']['default'] = $base."nagvis/images/maps/";
		$this->validConfig['paths']['mapcfg']['default'] = $base."nagvis/etc/maps/";
		$this->validConfig['paths']['htmlimages']['default'] = $htmlBase."/nagvis/images/";
		$this->validConfig['paths']['htmlicon']['default'] = $htmlBase."/nagvis/images/iconsets/";
		$this->validConfig['paths']['htmlshape']['default'] = $htmlBase."/nagvis/images/shapes/";
		$this->validConfig['paths']['htmlmap']['default'] = $htmlBase."/nagvis/images/maps/";
		if (DEBUG) debug('End method GlobalMainCfg::GlobalMainCfg()');
	}
	
    /**
	 * Reads the config file specified in $this->configFile
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function readConfig($printErr=1) {
		if (DEBUG) debug('Start method GlobalMainCfg::readConfig('.$printErr.')');
		$numComments = 0;
		$sec = '';
		
		// Check for config file and read permissions
		if($this->checkNagVisConfigExists($printErr) && $this->checkNagVisConfigReadable($printErr)) {
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
						
						if(isset($sec) && $sec != '') {
							$this->config[$sec][$key] = $val;
						} else {
							$this->config[$key] = $val;
						}
					} elseif ((@substr($line, 0, 1) == "[") && (@substr($line, -1, 1)) == "]") {
						// section
						$sec = @strtolower(@trim(@substr($line, 1, @strlen($line)-2)));
						
						// In Array schreiben
						if(preg_match("/^backend_/i", $sec)) {
							$this->config[$sec] = Array();
							$this->config[$sec]['backendid'] = str_replace('backend_','',$sec);
						} else {
							$this->config[$sec] = Array();
						}
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
						if(isset($sec)) {
							$this->config[$sec][$key] = $val;
						} else {
							$this->config[$key] = $val;
						}
					}
				} else {
					$sec = '';
					$this->config["comment_".($numComments++)] = '';
				}
			}
			
			if (DEBUG) debug('End method GlobalMainCfg::readConfig(): TRUE');
			return TRUE;
		} else {
			if (DEBUG) debug('End method GlobalMainCfg::readConfig(): FALSE');
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
		if (DEBUG) debug('Start method GlobalMainCfg::writeConfig()');
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
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
		        $FRONTEND->messageToUser('ERROR','mainCfgNotWriteable');
				if (DEBUG) debug('End method GlobalMainCfg::writeConfig(): FALSE');
				return FALSE;
			}
			
			if(!fwrite($handle, $content)) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
		        $FRONTEND->messageToUser('ERROR','19');
				if (DEBUG) debug('End method GlobalMainCfg::writeConfig(): FALSE');
				return FALSE;
			}
			
			fclose($handle);
			if (DEBUG) debug('End method GlobalMainCfg::writeConfig(): TRUE');
			return TRUE;
		} else {
			if (DEBUG) debug('End method GlobalMainCfg::writeConfig(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Checks for existing config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkNagVisConfigExists($printErr) {
		if (DEBUG) debug('Start method GlobalMainCfg::checkNagVisConfigExists('.$printErr.')');
		if($this->configFile != '') {
			if(file_exists($this->configFile)) {
				if (DEBUG) debug('End method GlobalMainCfg::checkNagVisConfigExists(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','mainCfgNotExists','MAINCFG~'.$this->configFile);
				}
				if (DEBUG) debug('End method GlobalMainCfg::checkNagVisConfigExists(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG) debug('End method GlobalMainCfg::checkNagVisConfigExists(): FALSE');
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
		if (DEBUG) debug('Start method GlobalMainCfg::checkNagVisConfigReadable('.$printErr.')');
		if($this->configFile != '') {
			if(is_readable($this->configFile)) {
				if (DEBUG) debug('End method GlobalMainCfg::checkNagVisConfigReadable(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','mainCfgNotReadable','MAINCFG~'.$this->configFile);
				}
				if (DEBUG) debug('End method GlobalMainCfg::checkNagVisConfigReadable(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG) debug('End method GlobalMainCfg::checkNagVisConfigReadable(): FALSE');
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
		if (DEBUG) debug('Start method GlobalMainCfg::checkNagVisConfigWriteable('.$printErr.')');
		if($this->checkNagVisConfigExists($printErr) && is_writeable($this->configFile)) {
			if (DEBUG) debug('End method GlobalMainCfg::checkNagVisConfigWriteable(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
	            $FRONTEND->messageToUser('ERROR','mainCfgNotWriteable','MAINCFG~'.$this->configFile);
			}
			if (DEBUG) debug('End method GlobalMainCfg::checkNagVisConfigWriteable(): FALSE');
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
		if (DEBUG) debug('Start method GlobalMainCfg::checkMapCfgFolderReadable('.$printErr.')');
		if(file_exists($this->getValue('paths', 'mapcfg')) && @is_readable($this->getValue('paths', 'mapcfg'))) {
			if (DEBUG) debug('End method GlobalMainCfg::checkMapCfgFolderReadable(): FALSE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
	            $FRONTEND->messageToUser('ERROR','mapCfgDirNotReadable','MAPPATH~'.$this->getValue('paths', 'mapcfg'));
			}
			if (DEBUG) debug('End method GlobalMainCfg::checkMapCfgFolderReadable(): FALSE');
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
		if (DEBUG) debug('Start method GlobalMainCfg::checkMapCfgFolderWriteable('.$printErr.')');
		if(file_exists(substr($this->getValue('paths', 'mapcfg'),0,-1)) && @is_writable(substr($this->getValue('paths', 'mapcfg'),0,-1))) {
			if (DEBUG) debug('End method GlobalMainCfg::checkMapCfgFolderWriteable(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
	            $FRONTEND->messageToUser('ERROR','mapCfgDirNotWriteable','MAPPATH~'.$this->getValue('paths', 'mapcfg'));
			}
			if (DEBUG) debug('End method GlobalMainCfg::checkMapCfgFolderWriteable(): FALSE');
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
		if (DEBUG) debug('Start method GlobalMainCfg::findSecOfVar('.$var.')');
		foreach($this->validConfig AS $key => $item) {
			if(is_array($item)) {
				foreach ($item AS $key2 => $item2) {
					if(@substr($key2,0,8) != "comment_") {
						if($key2 == $var) {
							if (DEBUG) debug('End method GlobalMainCfg::findSecOfVar(): '.$key);
							return $key;
						}
					}
				}       
			}
		}
		if (DEBUG) debug('End method GlobalMainCfg::findSecOfVar(): FALSE');
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
		if (DEBUG) debug('Start method GlobalMainCfg::setValue('.$sec.','.$var.','.$val.')');
		if(isset($this->config[$sec][$var]) && $val == '') {
			// Value is empty and there is an entry in the config array
			unset($this->config[$sec][$var]);
		} elseif(!isset($this->config[$sec][$var]) && $val == '') {
			// Value is empty and there is nothing in config array yet
		} else {
			// Value is set
			$this->config[$sec][$var] = $val;
		}
		if (DEBUG) debug('End method GlobalMainCfg::setValue(): TRUE');
		return TRUE;
	}
	
    /**
	 * Gets a config setting
	 *
	 * @param	String	$sec	Section
	 * @param	String	$var	Variable
	 * @param   Bool	$ignoreDefault Don't read default value
	 * @return	String	$val	Value
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getValue($sec, $var, $ignoreDefault=FALSE) {
		if (DEBUG) debug('Start method GlobalMainCfg::getValue('.$sec.','.$var.','.$ignoreDefault.')');
		// if nothing is set in the config file, use the default value
		if(isset($this->config[$sec]) && is_array($this->config[$sec]) && array_key_exists($var,$this->config[$sec])) {
			if (DEBUG) debug('End method GlobalMainCfg::getValue(): '.$this->config[$sec][$var]);
			return $this->config[$sec][$var];
		} elseif(!$ignoreDefault) {
			if(preg_match("/^backend_/i", $sec)) {
				if($this->config[$sec]['backendtype'] != '') {
					if (DEBUG) debug('End method GlobalMainCfg::getValue(): '.$this->validConfig['backend']['options'][$this->config[$sec]['backendtype']][$var]['default']);
					return $this->validConfig['backend']['options'][$this->config[$sec]['backendtype']][$var]['default'];
				} else {
					// FIXME: Errorhandling
					if (DEBUG) debug('End method GlobalMainCfg::getValue(): ""');
					return '';
				}
			} else {
				if (DEBUG) debug('End method GlobalMainCfg::getValue(): '.$this->validConfig[$sec][$var]['default']);
				return $this->validConfig[$sec][$var]['default'];
			}
		} else {
			if (DEBUG) debug('End method GlobalMainCfg::getValue(): FALSE');
			return FALSE;
		}
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
		if (DEBUG) debug('Start method GlobalMainCfg::setRuntimeValue('.$var.','.$val.')');
		$this->runtimeConfig[$var] = $val;
		if (DEBUG) debug('End method GlobalMainCfg::setRuntimeValue(): TRUE');
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
		if (DEBUG) debug('Start method GlobalMainCfg::getRuntimeValue('.$var.')');
		if(isset($this->runtimeConfig[$var])) {
			if (DEBUG) debug('End method GlobalMainCfg::getRuntimeValue(): '.$this->runtimeConfig[$var]);
			return $this->runtimeConfig[$var];
		} else {
			if (DEBUG) debug('End method GlobalMainCfg::getRuntimeValue(): ""');
			return '';
		}
	}
}
?>
