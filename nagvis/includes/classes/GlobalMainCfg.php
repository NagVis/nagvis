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
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalMainCfg($configFile) {
		$this->config = Array();
		$this->runtimeConfig = Array();
		
		$this->validConfig = Array(
			'global' => Array(
				'language' => Array('must' => 1,
					'editable' => 1,
					'default' => 'english',
					'match' => MATCH_STRING_NO_SPACE),
				'dateformat' => Array('must' => 1,
					'editable' => 1,
					'default' => 'Y-m-d H:i:s',
					'match' => MATCH_STRING),
				'displayheader' => Array('must' => 1,
						'editable' => 1,
						'default' => '1',
						'match' => MATCH_BOOLEAN),
				'refreshtime' => Array('must' => 1,
						'editable' => 1,
						'default' => '60',
						'match' => MATCH_INTEGER)),
			'defaults' => Array(
				'backend' => Array('must' => 0,
					'editable' => 0,
					'default' => 'ndomy_1',
					'match' => MATCH_STRING_NO_SPACE),
				'usegdlibs' => Array('must' => 1,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'icons' => Array('must' => 1,
					'editable' => 1,
					'default' => 'std_medium',
					'match' => MATCH_STRING_NO_SPACE),
				'backgroundcolor' => Array('must' => 0,
					'editable' => 1,
					'default' => '#fff',
					'match' => MATCH_COLOR),
				'recognizeservices' => Array('must' => 0,
					'editable' => 1,
					'default' => 1,
					'match' => MATCH_BOOLEAN),
				'onlyhardstates' => Array('must' => 0,
					'editable' => 1,
					'default' => 0,
					'match' => MATCH_BOOLEAN),
				'headertemplate' => Array('must' => 0,
					'editable' => 1,
					'default' => 'default',
					'match' => MATCH_STRING_NO_SPACE),
				'hovermenu' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'hovertemplate' => Array('must' => 0,
					'editable' => 1,
					'default' => 'default',
					'match' => MATCH_STRING_NO_SPACE),
				'hoverdelay' => Array('must' => 0,
					'editable' => 1,
					'default' => '0',
					'match' => MATCH_INTEGER),
				'showinlists' => Array('must' => 0,
					'editable' => 1,
					'default' => 1,
					'match' => MATCH_BOOLEAN),
				'urltarget' => Array('must' => 0,
					'editable' => 1,
					'default' => '_self',
					'match' => MATCH_STRING_NO_SPACE),
				'hoverchildsshow' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'hoverchildsorder' => Array('must' => 0,
					'editable' => 1,
					'default' => 's',
					'match' => MATCH_STRING_NO_SPACE),
				'hoverchildslimit' => Array('must' => 0,
					'editable' => 1,
					'default' => '10',
					'match' => MATCH_INTEGER)),
			'wui' => Array(
					'autoupdatefreq' => Array('must' => 0,
						'editable' => 1,
						'default' => '25',
						'match' => MATCH_INTEGER),
					'maplocktime' => Array('must' => 0,
						'editable' => 1,
						'default' => '350',
						'match' => MATCH_INTEGER),
					'allowedforconfig' => Array(
						'must' => 0,
						'editable' => 1,
						'default' => Array('EVERYONE'),
						'match' => MATCH_STRING)),
			'paths' => Array(
				'base' => Array('must' => 1,
					'editable' => 1,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'cfg' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'icon' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'images' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'shape' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'language' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'map' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'var' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'mapcfg' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'hovertemplate' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'headertemplate' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlbase' => Array('must' => 1,
					'editable' => 1,
					'default' => '/nagios/nagvis',
					'match' => MATCH_STRING_PATH),
				'htmlcgi' => Array('must' => 1,
					'editable' => 1,
					'default' => '/nagios/cgi-bin',
					'match' => MATCH_STRING_PATH),
				'htmlimages' => Array('must' => 0,
					'editable' => 0,
					'default' => '/nagios/nagvis/nagvis/images/',
					'match' => MATCH_STRING_PATH),
				'htmlhovertemplates' => Array('must' => 0,
					'editable' => 0,
					'default' => '/nagios/nagvis/nagvis/etc/templates/hover/',
					'match' => MATCH_STRING_PATH),
				'htmlheadertemplates' => Array('must' => 0,
					'editable' => 0,
					'default' => '/nagios/nagvis/nagvis/etc/templates/header/',
					'match' => MATCH_STRING_PATH),			
				'htmlhovertemplateimages' => Array('must' => 0,
					'editable' => 0,
					'default' => '/nagios/nagvis/nagvis/images/templates/hover/',
					'match' => MATCH_STRING_PATH),
				'htmlheadertemplateimages' => Array('must' => 0,
					'editable' => 0,
					'default' => '/nagios/nagvis/nagvis/images/templates/header/',
					'match' => MATCH_STRING_PATH),
				'htmlicon' => Array('must' => 0,
					'editable' => 0,
					'default' => '/nagios/nagvis/nagvis/images/iconsets/',
					'match' => MATCH_STRING_PATH),
				'htmlshape' => Array('must' => 0,
					'editable' => 0,
					'default' => '/nagios/nagvis/nagvis/images/shape/',
					'match' => MATCH_STRING_PATH),
				'htmlmap' => Array('must' => 0,
					'editable' => 0,
					'default' => '/nagios/nagvis/nagvis/images/maps/',
					'match' => MATCH_STRING_PATH),
				'htmlvar' => Array('must' => 0,
					'editable' => 0,
					'default' => '/nagios/var/',
					'match' => MATCH_STRING_PATH)),
			'backend' => Array(
				'backendtype' => Array('must' => 1,
					'editable' => 0,
					'default' => 'ndomy',
					'match' => MATCH_STRING_NO_SPACE),
				'backendid' => Array('must' => 1,
					'editable' => 0,
					'default' => 'ndomy_1',
					'match' => MATCH_STRING_NO_SPACE),
				'htmlcgi' => Array('must' => 0,
					'editable' => 1,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'options' => Array(
					'ndomy' => Array('dbhost' => Array('must' => 1,
							'editable' => 1,
							'default' => 'localhost',
							'match' => MATCH_STRING_NO_SPACE),
						'dbport' => Array('must' => 0,
							'editable' => 1,
							'default' => '3306',
							'match' => MATCH_INTEGER),
						'dbname' => Array('must' => 1,
							'editable' => 1,
							'default' => 'nagios',
							'match' => MATCH_STRING_NO_SPACE),
						'dbuser' => Array('must' => 1,
							'editable' => 1,
							'default' => 'root',
							'match' => MATCH_STRING_NO_SPACE),
						'dbpass' => Array('must' => 0,
							'editable' => 1,
							'default' => 'root',
							'match' => MATCH_STRING_EMPTY),
						'dbprefix' => Array('must' => 0,
							'editable' => 1,
							'default' => 'nagios_',
							'match' => MATCH_STRING_NO_SPACE),
						'dbinstancename' => Array('must' => 0,
							'editable' => 1,
							'default' => 'default',
							'match' => MATCH_STRING_NO_SPACE),
						'maxtimewithoutupdate' => Array('must' => 0,
							'editable' => 1,
							'default' => '180',
							'match' => MATCH_INTEGER)),
					'html' => Array(
						'backendid' => Array('must' => 1,
							'editable' => 0,
							'default' => 'html_1',
							'match' => MATCH_STRING_NO_SPACE),
						'cgiuser' => Array('must' => 1,
							'editable' => 1,
							'default' => 'nagiosadmin',
							'match' => MATCH_STRING_NO_SPACE),
						'cgi' => Array('must' => 1,
							'editable' => 1,
							'default' => '/usr/local/nagios/sbin/',
							'match' => MATCH_STRING_PATH)))),
			'rotation' => Array(
				'rotationid' => Array('must' => 1,
					'editable' => 1,
					'default' => 'demo',
					'match' =>MATCH_STRING_NO_SPACE),
				'maps' => Array('must' => 1,
					'editable' => 1,
					'default' => 'demo,demo2',
					'match' => MATCH_STRING_NO_SPACE),
				'interval' => Array('must' => 0,
					'editable' => 1,
					'default' => '',
					'match' => MATCH_INTEGER)),
			'automap' => Array(
				'showinlists' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'defaultroot' => Array('must' => 0,
					'editable' => 1,
					'default' => 'localhost',
					'match' => MATCH_STRING_NO_SPACE_EMPTY),
				'graphvizpath' => Array('must' => 0,
					'editable' => 0,
					'default' => '/usr/local/bin/',
					'match' => MATCH_STRING_PATH),
				'defaultparams' => Array('must' => 0,
					'editable' => 0,
					'default' => '&maxLayers=2',
					'match' => MATCH_STRING_URL)),
			'internal' => Array(
				'version' => Array('must' => 1,
					'editable' => 0,
					'default' => CONST_VERSION,
					'locked' => 1,
					'match' => MATCH_STRING_NO_SPACE),
				'title' => Array('must' => 1,
					'editable' => 0,
					'default' => 'NagVis ' . CONST_VERSION,
					'locked' => 1,
					'match' => MATCH_STRING)));
		
		// Try to get the base path via $_SERVER['SCRIPT_FILENAME']
		$this->validConfig['paths']['base']['default'] = $this->getBasePath();
		$this->setPathsByBase($this->getValue('paths','base'),$this->getValue('paths','htmlbase'));
			
		// Read Main Config file
		$this->configFile = $configFile;
		$this->readConfig(1);
		
		// want to reduce the paths in the NagVis config, but don't want to hardcode the paths relative from the bases
		$this->setPathsByBase($this->getValue('paths','base'),$this->getValue('paths','htmlbase'));
		
		// set default value
		$this->validConfig['rotation']['interval']['default'] = $this->getValue('global','refreshtime');
		$this->validConfig['backend']['htmlcgi']['default'] = $this->getValue('paths','htmlcgi');
		
	}
	
	/**
	 * Gets the base path 
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setPathsByBase($base,$htmlBase) {
		$this->validConfig['paths']['cfg']['default'] = $base.'etc/';
		$this->validConfig['paths']['icon']['default'] = $base.'nagvis/images/iconsets/';
		$this->validConfig['paths']['images']['default'] = $base.'nagvis/images/';
		$this->validConfig['paths']['shape']['default'] = $base.'nagvis/images/shapes/';
		$this->validConfig['paths']['language']['default'] = $base.'nagvis/includes/languages/';
		$this->validConfig['paths']['class']['default'] = $base.'nagvis/includes/classes/';
		$this->validConfig['paths']['map']['default'] = $base.'nagvis/images/maps/';
		$this->validConfig['paths']['var']['default'] = $base.'var/';
		$this->validConfig['paths']['hovertemplate']['default'] = $base.'nagvis/templates/hover/';
		$this->validConfig['paths']['headertemplate']['default'] = $base.'nagvis/templates/header/';
		$this->validConfig['paths']['mapcfg']['default'] = $base.'etc/maps/';
		$this->validConfig['paths']['htmlimages']['default'] = $htmlBase.'/nagvis/images/';
		$this->validConfig['paths']['htmlhovertemplates']['default'] = $htmlBase.'/nagvis/templates/hover/';
		$this->validConfig['paths']['htmlheadertemplates']['default'] = $htmlBase.'/nagvis/templates/header/';
		$this->validConfig['paths']['htmlhovertemplateimages']['default'] = $this->validConfig['paths']['htmlimages']['default'].'templates/hover/';
		$this->validConfig['paths']['htmlheadertemplateimages']['default'] = $this->validConfig['paths']['htmlimages']['default'].'templates/header/';
		$this->validConfig['paths']['htmlicon']['default'] = $htmlBase.'/nagvis/images/iconsets/';
		$this->validConfig['paths']['htmlshape']['default'] = $htmlBase.'/nagvis/images/shapes/';
		$this->validConfig['paths']['htmlmap']['default'] = $htmlBase.'/nagvis/images/maps/';
		$this->validConfig['paths']['htmlvar']['default'] = $htmlBase.'/var/';
	}
	
	/**
	 * Gets the base path 
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBasePath() {
		$return = preg_replace('/wui|nagvis$/i', '', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
		return $return ;
	}
	
	/**
	 * Reads the config file specified in $this->configFile
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readConfig($printErr=1) {
		$numComments = 0;
		$sec = '';
		
		// Check for config file and read permissions
		if($this->checkNagVisConfigExists($printErr) && $this->checkNagVisConfigReadable($printErr)) {
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
						$sec = strtolower(trim(substr($line, 1, strlen($line)-2)));
						
						// write to array
						if(preg_match('/^backend_/i', $sec)) {
							$this->config[$sec] = Array();
							$this->config[$sec]['backendid'] = str_replace('backend_','',$sec);
						} elseif(preg_match('/^rotation_/i', $sec)) {
							$this->config[$sec] = Array();
							$this->config[$sec]['rotationid'] = str_replace('rotation_','',$sec);
						} else {
							$this->config[$sec] = Array();
						}
					} else {
						// parameter...
						
						// seperate string in an array
						$arr = explode('=',$line);
						// read key from array and delete it
						$key = strtolower(trim($arr[0]));
						unset($arr[0]);
						// build string from rest of array
						$val = trim(implode('=', $arr));
						
						// remove " at beginign and at the end of the string
						if ((substr($val,0,1) == '"') && (substr($val,-1,1)=='"')) {
							$val = substr($val,1,strlen($val)-2);
						}
						
						// Special options (Arrays)
						if($sec == 'wui' && $key == 'allowedforconfig') {
							$val = explode(',', str_replace(' ','',$val));
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
			
			if($this->checkMainConfigIsValid(1)) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks if the main config file is valid
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMainConfigIsValid($printErr) {
		
		// check given objects and attributes
		foreach($this->config AS $type => $vars) {
			if(!ereg('^comment_',$type)) {
				if(isset($this->validConfig[$type]) || ereg('^(backend|rotation)_', $type)) {
					// loop validConfig for checking: => missing "must" atributes
					if(ereg('^backend_', $type)) {
						$arrValidConfig = array_merge($this->validConfig['backend'],$this->validConfig['backend']['options'][$this->getValue($type,'backendtype')]);
					} elseif(ereg('^rotation_', $type)) {
						$arrValidConfig = $this->validConfig['rotation'];
					} else {
						$arrValidConfig = $this->validConfig[$type];
					}
					foreach($arrValidConfig AS $key => $val) {
						if((isset($val['must']) && $val['must'] == '1')) {
							// value is "must"
							if($this->getValue($type,$key) == '') {
								// a "must" value is missing or empty
								$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
								$FRONTEND->messageToUser('ERROR','mainMustValueNotSet','ATTRIBUTE~'.$key.',TYPE~'.$type);
							}
						}
					}
					
					// loop given elements for checking: => all given atributes valid
					foreach($vars AS $key => $val) {
						if(!ereg('^comment_',$key)) {
							if(ereg('^backend_', $type)) {
								$arrValidConfig = array_merge($this->validConfig['backend'],$this->validConfig['backend']['options'][$this->getValue($type,'backendtype')]);
							} elseif(ereg('^rotation_', $type)) {
								$arrValidConfig = $this->validConfig['rotation'];
							} else {
								$arrValidConfig = $this->validConfig[$type];
							}
							
							if(!isset($arrValidConfig[$key])) {
								// unknown attribute
								if($printErr) {
									$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
									$FRONTEND->messageToUser('ERROR','unknownValue','ATTRIBUTE~'.$key.',TYPE~'.$type);
								}
								return FALSE;
							} else {
								if(isset($val) && is_array($val)) {
									$val = implode(',',$val);
								}
								// valid attribute, now check for value format
								if(!preg_match($arrValidConfig[$key]['match'],$val)) {
									# DEBUG: echo $val;
									// wrong format
									if($printErr) {
										$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
										$FRONTEND->messageToUser('ERROR','wrongValueFormat','TYPE~'.$type.',ATTRIBUTE~'.$key);
									}
									return FALSE;
								}
							}
						}
					}	
				} else {
					// unknown type
					if($printErr) {
						$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
						$FRONTEND->messageToUser('ERROR','unknownSection','TYPE~'.$type);
					}
					return FALSE;
				}
			}
		}
		return TRUE;
	}
	
	/**
	 * Writes the config file completly from array $this->configFile
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function writeConfig() {
		// Check for config file and read permissions
		if($this->checkNagVisConfigReadable(1) && $this->checkNagVisConfigWriteable(1)) {
			foreach($this->config as $key => $item) {
				if(is_array($item)) {
					$content .= '['.$key.']'."\n";
					foreach ($item as $key2 => $item2) {
						if(substr($key2,0,8) == 'comment_') {
							$content .= $item2."\n";
						} else {
							if(is_numeric($item2) || is_bool($item2))
								$content .= $key2."=".$item2."\n";
							else
							$content .= $key2.'="'.$item2.'"'."\n";
						}
					}
				} elseif(substr($key,0,8) == 'comment_') {
					$content .= $item."\n";
				} else {
					if(is_numeric($item) || is_bool($item))
						$content .= $key.'='.$item."\n";
					else
						$content .= $key.'="'.$item.'"'."\n";
				}
			}
			
			if(!$handle = fopen($this->configFile, 'w+')) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','mainCfgNotWriteable');
				return FALSE;
			}
			
			if(!fwrite($handle, $content)) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','19');
				return FALSE;
			}
			
			fclose($handle);
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for existing config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkNagVisConfigExists($printErr) {
		if($this->configFile != '') {
			if(file_exists($this->configFile)) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
					$FRONTEND->messageToUser('ERROR','mainCfgNotExists','MAINCFG~'.$this->configFile);
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkNagVisConfigReadable($printErr) {
		if($this->configFile != '') {
			if(is_readable($this->configFile)) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
					$FRONTEND->messageToUser('ERROR','mainCfgNotReadable','MAINCFG~'.$this->configFile);
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
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkNagVisConfigWriteable($printErr) {
		if($this->checkNagVisConfigExists($printErr) && is_writeable($this->configFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','mainCfgNotWriteable','MAINCFG~'.$this->configFile);
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable MapCfgFolder
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMapCfgFolderReadable($printErr) {
		if(file_exists($this->getValue('paths', 'mapcfg')) && is_readable($this->getValue('paths', 'mapcfg'))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','mapCfgDirNotReadable','MAPPATH~'.$this->getValue('paths', 'mapcfg'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable MapCfgFolder
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMapCfgFolderWriteable($printErr) {
		if(file_exists(substr($this->getValue('paths', 'mapcfg'),0,-1)) && is_writable(substr($this->getValue('paths', 'mapcfg'),0,-1))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','mapCfgDirNotWriteable','MAPPATH~'.$this->getValue('paths', 'mapcfg'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Finds the Section of a var
	 *
	 * @param	String	$var	Config variable
	 * @return	String	Section of the var
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function findSecOfVar($var) {
		foreach($this->validConfig AS $key => $item) {
			if(is_array($item)) {
				foreach ($item AS $key2 => $item2) {
					if(substr($key2,0,8) != 'comment_') {
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
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setValue($sec, $var, $val) {
		if(isset($this->config[$sec][$var]) && $val == '') {
			// Value is empty and there is an entry in the config array
			unset($this->config[$sec][$var]);
		} elseif(!isset($this->config[$sec][$var]) && $val == '') {
			// Value is empty and there is nothing in config array yet
		} else {
			// Value is set
			$this->config[$sec][$var] = $val;
		}
		return TRUE;
	}
	
	/**
	 * Gets a config setting
	 *
	 * @param	String	$sec	Section
	 * @param	String	$var	Variable
	 * @param   Bool	$ignoreDefault Don't read default value
	 * @return	String	$val	Value
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getValue($sec, $var, $ignoreDefault=FALSE) {
		// if nothing is set in the config file, use the default value
		if(isset($this->config[$sec]) && is_array($this->config[$sec]) && isset($this->config[$sec][$var])) {
			return $this->config[$sec][$var];
		} elseif(!$ignoreDefault) {
			// Enfasten this method by first check for famous sections and only if 
			// they don't match try to match the backend_ and rotation_ sections
			if($sec == 'global' || $sec == 'default' || $sec == 'paths') {
				return $this->validConfig[$sec][$var]['default'];
			} elseif(strpos($sec, 'backend_') === 0) {
				if(isset($this->config[$sec]['backendtype']) && $this->config[$sec]['backendtype'] != '') {
					return $this->validConfig['backend']['options'][$this->config[$sec]['backendtype']][$var]['default'];
				} else {
					if(isset($this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default']) && $this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default'] != '') {
						return $this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default'];
					} else {
						if(isset($this->validConfig['backend'][$var]['default']) && $this->validConfig['backend'][$var]['default'] != '') {
							return $this->validConfig['backend'][$var]['default'];
						} else {
							return $this->validConfig['backend']['backendtype']['default'];
						}
					}
				}
			} elseif(strpos($sec, 'rotation_') === 0) {
				if(isset($this->config[$sec]) && is_array($this->config[$sec])) {
					return $this->validConfig['rotation'][$var]['default'];
				} else {
					return FALSE;
				}
			} else {
				return $this->validConfig[$sec][$var]['default'];
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Sets a runtime config value
	 *
	 * @param	String	$var	Variable
	 * @param	String	$val	Value
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
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
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getRuntimeValue($var) {
		if(isset($this->runtimeConfig[$var])) {
			return $this->runtimeConfig[$var];
		} else {
			return '';
		}
	}
}
?>