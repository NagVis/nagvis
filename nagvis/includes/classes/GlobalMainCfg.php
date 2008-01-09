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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::GlobalMainCfg('.$configFile.')');
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
					'match' => MATCH_STRING_NO_SPACE)),
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
				'default_root' => Array('must' => 0,
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
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::GlobalMainCfg()');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::getBasePath()');
		$return = preg_replace('/wui|nagvis$/i', '', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getBasePath(): '.$return);
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::readConfig('.$printErr.')');
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
						$key = 'comment_'.($numComments++);
						$val = @trim($line);
						
						if(isset($sec) && $sec != '') {
							$this->config[$sec][$key] = $val;
						} else {
							$this->config[$key] = $val;
						}
					} elseif ((@substr($line, 0, 1) == '[') && (@substr($line, -1, 1)) == ']') {
						// section
						$sec = @strtolower(@trim(@substr($line, 1, @strlen($line)-2)));
						
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
						$arr = @explode('=',$line);
						// read key from array and delete it
						$key = @strtolower(@trim($arr[0]));
						unset($arr[0]);
						// build string from rest of array
						$val = @trim(@implode('=', $arr));
						
						// remove " at beginign and at the end of the string
						if ((@substr($val,0,1) == '"') && (@substr($val,-1,1)=='"')) {
							$val = @substr($val,1,@strlen($val)-2);
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
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::readConfig(): TRUE');
				return TRUE;
			} else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::readConfig(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::readConfig(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::checkMainConfigIsValid('.$printErr.')');
		
		// check given objects and attributes
		foreach($this->config AS $type => $vars) {
			if(!ereg('^comment_',$type)) {
				if(array_key_exists($type,$this->validConfig) || ereg('^(backend|rotation)_', $type)) {
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
							
							if(!array_key_exists($key,$arrValidConfig)) {
								// unknown attribute
								if($printErr) {
									$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
									$FRONTEND->messageToUser('ERROR','unknownValue','ATTRIBUTE~'.$key.',TYPE~'.$type);
								}
								if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigIsValid(): FALSE');
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
									if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigIsValid(): FALSE');
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
					if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkMainConfigIsValid(): FALSE');
					return FALSE;
				}
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkMainConfigIsValid(): TRUE');
		return TRUE;
	}
	
	/**
	 * Writes the config file completly from array $this->configFile
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function writeConfig() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::writeConfig()');
		// Check for config file and read permissions
		if($this->checkNagVisConfigReadable(1) && $this->checkNagVisConfigWriteable(1)) {
			foreach($this->config as $key => $item) {
				if(is_array($item)) {
					$content .= '['.$key.']'."\n";
					foreach ($item as $key2 => $item2) {
						if(@substr($key2,0,8) == 'comment_') {
							$content .= $item2."\n";
						} else {
							if(is_numeric($item2) || is_bool($item2))
								$content .= $key2."=".$item2."\n";
							else
							$content .= $key2.'="'.$item2.'"'."\n";
						}
					}
				} elseif(@substr($key,0,8) == 'comment_') {
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
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::writeConfig(): FALSE');
				return FALSE;
			}
			
			if(!fwrite($handle, $content)) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','19');
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::writeConfig(): FALSE');
				return FALSE;
			}
			
			fclose($handle);
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::writeConfig(): TRUE');
			return TRUE;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::writeConfig(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::checkNagVisConfigExists('.$printErr.')');
		if($this->configFile != '') {
			if(file_exists($this->configFile)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkNagVisConfigExists(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
					$FRONTEND->messageToUser('ERROR','mainCfgNotExists','MAINCFG~'.$this->configFile);
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkNagVisConfigExists(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkNagVisConfigExists(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::checkNagVisConfigReadable('.$printErr.')');
		if($this->configFile != '') {
			if(is_readable($this->configFile)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkNagVisConfigReadable(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
					$FRONTEND->messageToUser('ERROR','mainCfgNotReadable','MAINCFG~'.$this->configFile);
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkNagVisConfigReadable(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkNagVisConfigReadable(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::checkNagVisConfigWriteable('.$printErr.')');
		if($this->checkNagVisConfigExists($printErr) && is_writeable($this->configFile)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkNagVisConfigWriteable(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','mainCfgNotWriteable','MAINCFG~'.$this->configFile);
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkNagVisConfigWriteable(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::checkMapCfgFolderReadable('.$printErr.')');
		if(file_exists($this->getValue('paths', 'mapcfg')) && @is_readable($this->getValue('paths', 'mapcfg'))) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkMapCfgFolderReadable(): FALSE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','mapCfgDirNotReadable','MAPPATH~'.$this->getValue('paths', 'mapcfg'));
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkMapCfgFolderReadable(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::checkMapCfgFolderWriteable('.$printErr.')');
		if(file_exists(substr($this->getValue('paths', 'mapcfg'),0,-1)) && @is_writable(substr($this->getValue('paths', 'mapcfg'),0,-1))) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkMapCfgFolderWriteable(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','mapCfgDirNotWriteable','MAPPATH~'.$this->getValue('paths', 'mapcfg'));
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::checkMapCfgFolderWriteable(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::findSecOfVar('.$var.')');
		foreach($this->validConfig AS $key => $item) {
			if(is_array($item)) {
				foreach ($item AS $key2 => $item2) {
					if(@substr($key2,0,8) != 'comment_') {
						if($key2 == $var) {
							if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::findSecOfVar(): '.$key);
							return $key;
						}
					}
				}
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::findSecOfVar(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::setValue('.$sec.','.$var.','.$val.')');
		if(isset($this->config[$sec][$var]) && $val == '') {
			// Value is empty and there is an entry in the config array
			unset($this->config[$sec][$var]);
		} elseif(!isset($this->config[$sec][$var]) && $val == '') {
			// Value is empty and there is nothing in config array yet
		} else {
			// Value is set
			$this->config[$sec][$var] = $val;
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::setValue(): TRUE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::getValue('.$sec.','.$var.','.$ignoreDefault.')');
		// if nothing is set in the config file, use the default value
		if(isset($this->config[$sec]) && is_array($this->config[$sec]) && array_key_exists($var,$this->config[$sec])) {
			if (DEBUG&&DEBUGLEVEL&2) debug('  Value is set in the config file');
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getValue(): '.$this->config[$sec][$var]);
			return $this->config[$sec][$var];
		} elseif(!$ignoreDefault) {
			if (DEBUG&&DEBUGLEVEL&2) debug('  Not set in config file, try to get default value');
			if(preg_match('/^backend_/i', $sec)) {
				if (DEBUG&&DEBUGLEVEL&2) debug('  Section: Backend');
				
				if(isset($this->config[$sec]['backendtype']) && $this->config[$sec]['backendtype'] != '') {
					if (DEBUG&&DEBUGLEVEL&2) debug('  Backendtype is set in the backend, get the default value from backend default options');
					
					if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getValue(): '.$this->validConfig['backend']['options'][$this->config[$sec]['backendtype']][$var]['default']);
					return $this->validConfig['backend']['options'][$this->config[$sec]['backendtype']][$var]['default'];
				} else {
					if (DEBUG&&DEBUGLEVEL&2) debug('  Backendtype is not set in the backend, get the options of the default backend');
					
					if(isset($this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default']) && $this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default'] != '') {
						if (DEBUG&&DEBUGLEVEL&2) debug('  Got default value from the default backend');
						
						if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getValue(): '.$this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default']);
						return $this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default'];
					} else {
						if (DEBUG&&DEBUGLEVEL&2) debug('  The default value is empty or not set in the default backend');
						if(isset($this->validConfig['backend'][$var]['default']) && $this->validConfig['backend'][$var]['default'] != '') {
							if (DEBUG&&DEBUGLEVEL&2) debug('  Found the value in "global" backend section');
							
							if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getValue(): '.$this->validConfig['backend'][$var]['default']);
							return $this->validConfig['backend'][$var]['default'];
						} else {
							if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getValue(): '.$this->validConfig['backend']['backendtype']['default']);
							return $this->validConfig['backend']['backendtype']['default'];
						}
					}
				}
			} elseif(preg_match('/^rotation_/i', $sec)) {
				if (DEBUG&&DEBUGLEVEL&2) debug('  Section: Rotation');
				
				if(isset($this->config[$sec]) && is_array($this->config[$sec])) {
					if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getValue(): '.$this->validConfig['rotation'][$var]['default']);
					return $this->validConfig['rotation'][$var]['default'];
				} else {
					if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getValue(): FALSE');
					return FALSE;
				}
			} else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getValue(): '.$this->validConfig[$sec][$var]['default']);
				return $this->validConfig[$sec][$var]['default'];
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&2) debug('  Not set in config file, not allowed to get default value');
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getValue(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::setRuntimeValue('.$var.','.$val.')');
		$this->runtimeConfig[$var] = $val;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::setRuntimeValue(): TRUE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMainCfg::getRuntimeValue('.$var.')');
		if(isset($this->runtimeConfig[$var])) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getRuntimeValue(): '.$this->runtimeConfig[$var]);
			return $this->runtimeConfig[$var];
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMainCfg::getRuntimeValue(): ""');
			return '';
		}
	}
}
?>