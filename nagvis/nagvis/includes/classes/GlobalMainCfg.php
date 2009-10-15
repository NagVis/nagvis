<?php
/*****************************************************************************
 *
 * GlobalMainCfg.php - Class for handling the main configuration of NagVis
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
class GlobalMainCfg {
	private $CACHE;
	
	protected $config;
	protected $runtimeConfig;
	
	protected $configFile;
	
	protected $validConfig;
	
	/**
	 * Class Constructor
	 *
	 * @param	String	$configFile			String with path to config file
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($configFile) {
		$this->config = Array();
		$this->runtimeConfig = Array();
		
		$this->validConfig = Array(
			'global' => Array(
				'dateformat' => Array('must' => 1,
					'editable' => 1,
					'default' => 'Y-m-d H:i:s',
					'match' => MATCH_STRING),
				'displayheader' => Array('must' => 1,
						'editable' => 1,
						'deprecated' => 1,
						'default' => '1',
						'match' => MATCH_BOOLEAN),
				'language' => Array('must' => 1,
					'editable' => 1,
					'default' => 'en_US',
					'match' => MATCH_STRING_NO_SPACE),
				'refreshtime' => Array('must' => 1,
						'editable' => 1,
						'default' => '60',
						'match' => MATCH_INTEGER)),
			'defaults' => Array(
				'backend' => Array('must' => 0,
					'editable' => 0,
					'default' => 'ndomy_1',
					'match' => MATCH_STRING_NO_SPACE),
				'backgroundcolor' => Array('must' => 0,
					'editable' => 1,
					'default' => '#fff',
					'match' => MATCH_COLOR),
				'contextmenu' => Array('must' => 0,
					'editable' => 1,
					'default' => 1,
					'match' => MATCH_BOOLEAN),
				'contexttemplate' => Array('must' => 0,
					'editable' => 1,
					'default' => 'default',
					'match' => MATCH_STRING_NO_SPACE),
				'eventbackground' => Array('must' => 0,
					'editable' => 1,
					'default' => '0',
					'match' => MATCH_BOOLEAN),
				'eventhighlight' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'eventhighlightinterval' => Array('must' => 0,
					'editable' => 1,
					'default' => '500',
					'match' => MATCH_INTEGER),
				'eventhighlightduration' => Array('must' => 0,
					'editable' => 1,
					'default' => '10000',
					'match' => MATCH_INTEGER),
				'eventlog' => Array('must' => 0,
					'editable' => 1,
					'default' => '0',
					'match' => MATCH_BOOLEAN),
				'eventloglevel' => Array('must' => 0,
					'editable' => 1,
					'default' => 'info',
					'match' => MATCH_STRING_NO_SPACE),
				'eventlogheight' => Array('must' => 0,
					'editable' => 1,
					'default' => '100',
					'match' => MATCH_INTEGER),
				'eventloghidden' => Array('must' => 0,
					'editable' => 1,
					'default' => 1,
					'match' => MATCH_BOOLEAN),
				'eventscroll' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'eventsound' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'headermenu' => Array('must' => 1,
					'editable' => 1,
					'default' => '1',
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
				'hovertimeout' => Array('must' => 0,
					'editable' => 1,
					'default' => '5',
					'deprecated' => '1',
					'match' => MATCH_INTEGER),
				'hoverdelay' => Array('must' => 0,
					'editable' => 1,
					'default' => '0',
					'match' => MATCH_INTEGER),
				'hoverchildsshow' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'hoverchildslimit' => Array('must' => 0,
					'editable' => 1,
					'default' => '10',
					'match' => MATCH_INTEGER),
				'hoverchildsorder' => Array('must' => 0,
					'editable' => 1,
					'default' => 'asc',
					'match' => MATCH_ORDER),
				'hoverchildssort' => Array('must' => 0,
					'editable' => 1,
					'default' => 's',
					'match' => MATCH_STRING_NO_SPACE),
				'icons' => Array('must' => 1,
					'editable' => 1,
					'default' => 'std_medium',
					'match' => MATCH_STRING_NO_SPACE),
				'onlyhardstates' => Array('must' => 0,
					'editable' => 1,
					'default' => 0,
					'match' => MATCH_BOOLEAN),
				'recognizeservices' => Array('must' => 0,
					'editable' => 1,
					'default' => 1,
					'match' => MATCH_BOOLEAN),
				'showinlists' => Array('must' => 0,
					'editable' => 1,
					'default' => 1,
					'match' => MATCH_BOOLEAN),
				'urltarget' => Array('must' => 0,
					'editable' => 1,
					'default' => '_self',
					'match' => MATCH_STRING_NO_SPACE),
				'hosturl' => Array('must' => 0,
					'default' => '[htmlcgi]/status.cgi?host=[host_name]',
					'match' => MATCH_STRING_URL_EMPTY),
				'hostgroupurl' => Array('must' => 0,
					'default' => '[htmlcgi]/status.cgi?hostgroup=[hostgroup_name]',
					'match' => MATCH_STRING_URL_EMPTY),
				'serviceurl' => Array('must' => 0,
					'default' => '[htmlcgi]/extinfo.cgi?type=2&host=[host_name]&service=[service_description]',
					'match' => MATCH_STRING_URL_EMPTY),
				'servicegroupurl' => Array('must' => 0,
					'default' => '[htmlcgi]/status.cgi?servicegroup=[servicegroup_name]&style=detail',
					'match' => MATCH_STRING_URL_EMPTY),
				'usegdlibs' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
          'deprecated' => 1,
					'match' => MATCH_BOOLEAN)),
			'wui' => Array(
				'allowedforconfig' => Array(
					'must' => 0,
					'editable' => 1,
					'default' => Array('EVERYONE'),
					'match' => MATCH_STRING),
				'autoupdatefreq' => Array('must' => 0,
					'editable' => 1,
					'default' => '25',
					'match' => MATCH_INTEGER),
				'maplocktime' => Array('must' => 0,
					'editable' => 1,
					'default' => '350',
					'match' => MATCH_INTEGER)),
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
				'gadget' => Array('must' => 0,
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
				'hovercontext' => Array('must' => 0,
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
					'match' => MATCH_STRING_URL),
				'htmlgadgets' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlimages' => Array('must' => 0,
					'editable' => 0,
					'default' => '/',
					'match' => MATCH_STRING_PATH),
				'htmlhovertemplates' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlheadertemplates' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),			
				'htmlcontexttemplates' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlhovertemplateimages' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlheadertemplateimages' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlcontexttemplateimages' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlicon' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlshape' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlsound' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlmap' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
					'match' => MATCH_STRING_PATH),
				'htmlvar' => Array('must' => 0,
					'editable' => 0,
					'default' => '',
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
					'match' => MATCH_STRING_URL),
				'options' => Array()),
			'rotation' => Array(
				'rotationid' => Array('must' => 1,
					'editable' => 1,
					'default' => 'demo',
					'match' =>MATCH_STRING_NO_SPACE),
				'interval' => Array('must' => 0,
					'editable' => 1,
					'default' => '',
					'match' => MATCH_INTEGER),
				'maps' => Array('must' => 1,
					'editable' => 1,
					'default' => 'demo,demo2',
					'match' => MATCH_STRING)),
			'automap' => Array(
				'defaultparams' => Array('must' => 0,
					'editable' => 1,
					'default' => '&maxLayers=2',
					'match' => MATCH_STRING_URL),
				'defaultroot' => Array('must' => 0,
					'editable' => 1,
					'default' => 'localhost',
					'match' => MATCH_STRING_NO_SPACE_EMPTY),
				'graphvizpath' => Array('must' => 0,
					'editable' => 1,
					'default' => '/usr/local/bin/',
					'match' => MATCH_STRING_PATH),
				'showinlists' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN)
				),
			'index' => Array(
				'cellsperrow' => Array('must' => 0,
					'editable' => 1,
					'default' => '4',
					'match' => MATCH_INTEGER),
				'headermenu' => Array('must' => 1,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'headertemplate' => Array('must' => 0,
					'editable' => 1,
					'default' => 'default',
					'match' => MATCH_STRING_NO_SPACE),
				'showrotations' => Array('must' => 0,
					'editable' => 1,
					'default' => '1',
					'match' => MATCH_BOOLEAN),
				'backgroundcolor' => Array('must' => 0,
					'editable' => 1,
					'default' => '#fff',
					'match' => MATCH_COLOR)),
			'worker' => Array(
				'interval' => Array('must' => 0,
					'editable' => 1,
					'default' => '10',
					'match' => MATCH_INTEGER),
				'updateobjectstates' => Array('must' => 0,
					'editable' => 1,
					'default' => '30',
					'match' => MATCH_INTEGER),
				'requestmaxparams' => Array('must' => 0,
					'editable' => 1,
					'default' => 0,
					'match' => MATCH_INTEGER),
				'requestmaxlength' => Array('must' => 0,
					'editable' => 1,
					'default' => 1900,
					'match' => MATCH_INTEGER)),
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
		
		// Define the main configuration file
		$this->configFile = $configFile;
		
		// Do preflight checks
		// Only proceed when the configuration file exists and is readable
		if(!$this->checkNagVisConfigExists(TRUE) || !$this->checkNagVisConfigReadable(TRUE)) {
			return FALSE;
		}
		
		// Create instance of GlobalFileCache object for caching the config
		$CORE = new GlobalCore($this);
		$this->CACHE = new GlobalFileCache($CORE, $this->configFile, $this->getValue('paths','var').'nagvis.ini.php-'.CONST_VERSION.'-cache');
		
		// Get the valid configuration definitions from the available backends
		$this->getBackendValidConf();
		
		if($this->CACHE->isCached(FALSE) !== -1) {
			$this->config = $this->CACHE->getCache();
		} else {
			
			// Read Main Config file, when succeeded cache it
			if($this->readConfig(TRUE)) {
				
				// Cache the resulting config
				$this->CACHE->writeCache($this->config, TRUE);
			}
		}
		
		// want to reduce the paths in the NagVis config, but don't want to hardcode the paths relative from the bases
		$this->setPathsByBase($this->getValue('paths','base'),$this->getValue('paths','htmlbase'));
		
		// set default value
		$this->validConfig['rotation']['interval']['default'] = $this->getValue('global','refreshtime');
		$this->validConfig['backend']['htmlcgi']['default'] = $this->getValue('paths','htmlcgi');
	}
	
	/**
	 * Gets the valid configuration definitions from the available backends. The
	 * definitions were moved to the backends so it is easier to create new
	 * backends without any need to modify the main configuration
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getBackendValidConf() {
		// Get the configuration options from the backends
		$CORE = new GlobalCore($this);
		$aBackends = $CORE->getAvailableBackends();
		
		foreach($aBackends AS $backend) {
			$class = 'GlobalBackend'.$backend;
			
			// FIXME: Does not work in PHP 5.2 (http://bugs.php.net/bug.php?id=31318)
			//$this->validConfig['backend']['options'][$backend] = $class->getValidConfig();
			// I'd prefer to use the above but for the moment I use the fix below
			
			if(method_exists($class, 'getValidConfig')) {
				$this->validConfig['backend']['options'][$backend] = call_user_func(Array('GlobalBackend'.$backend, 'getValidConfig'));
				//$this->validConfig['backend']['options'][$backend] = call_user_func('GlobalBackend'.$backend.'::getValidConfig');
			}
		}
	}
	
	/**
	 * Gets the base path 
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function setPathsByBase($base,$htmlBase) {
		$this->validConfig['paths']['cfg']['default'] = $base.'etc/';
		$this->validConfig['paths']['icon']['default'] = $base.'nagvis/images/iconsets/';
		$this->validConfig['paths']['images']['default'] = $base.'nagvis/images/';
		$this->validConfig['paths']['shape']['default'] = $base.'nagvis/images/shapes/';
		$this->validConfig['paths']['language']['default'] = $base.'nagvis/includes/locale';
		$this->validConfig['paths']['class']['default'] = $base.'nagvis/includes/classes/';
		$this->validConfig['paths']['map']['default'] = $base.'nagvis/images/maps/';
		$this->validConfig['paths']['var']['default'] = $base.'var/';
		$this->validConfig['paths']['hovertemplate']['default'] = $base.'nagvis/templates/hover/';
		$this->validConfig['paths']['headertemplate']['default'] = $base.'nagvis/templates/header/';
		$this->validConfig['paths']['contexttemplate']['default'] = $base.'nagvis/templates/context/';
		$this->validConfig['paths']['mapcfg']['default'] = $base.'etc/maps/';
		$this->validConfig['paths']['gadget']['default'] = $base.'nagvis/gadgets/';
		$this->validConfig['paths']['htmlimages']['default'] = $htmlBase.'/nagvis/images/';
		$this->validConfig['paths']['htmlhovertemplates']['default'] = $htmlBase.'/nagvis/templates/hover/';
		$this->validConfig['paths']['htmlheadertemplates']['default'] = $htmlBase.'/nagvis/templates/header/';
		$this->validConfig['paths']['htmlcontexttemplates']['default'] = $htmlBase.'/nagvis/templates/context/';
		$this->validConfig['paths']['htmlhovertemplateimages']['default'] = $this->validConfig['paths']['htmlimages']['default'].'templates/hover/';
		$this->validConfig['paths']['htmlheadertemplateimages']['default'] = $this->validConfig['paths']['htmlimages']['default'].'templates/header/';
		$this->validConfig['paths']['htmlcontexttemplateimages']['default'] = $this->validConfig['paths']['htmlimages']['default'].'templates/context/';
		$this->validConfig['paths']['htmlicon']['default'] = $htmlBase.'/nagvis/images/iconsets/';
		$this->validConfig['paths']['htmlshape']['default'] = $htmlBase.'/nagvis/images/shapes/';
		$this->validConfig['paths']['htmlmap']['default'] = $htmlBase.'/nagvis/images/maps/';
		$this->validConfig['paths']['htmlvar']['default'] = $htmlBase.'/var/';
		$this->validConfig['paths']['htmlsounds']['default'] = $htmlBase.'/nagvis/sounds/';
		$this->validConfig['paths']['htmlgadgets']['default'] = $htmlBase.'/nagvis/gadgets/';
	}
	
	/**
	 * Gets the base path 
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getBasePath() {
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
					
					// separate string in an array
					$arr = explode('=',$line);
					// read key from array and delete it
					$key = strtolower(trim($arr[0]));
					unset($arr[0]);
					// build string from rest of array
					$val = trim(implode('=', $arr));
					
					// remove " at beginning and at the end of the string
					if ((substr($val,0,1) == '"') && (substr($val,-1,1)=='"')) {
						$val = substr($val,1,strlen($val)-2);
					}
					
					// Special options (Arrays)
					if($sec == 'wui' && $key == 'allowedforconfig') {
						// Explode comma separated list to array
						$val = explode(',', $val);
					} elseif(preg_match('/^rotation_/i', $sec) && $key == 'maps') {
						// Explode comma separated list to array
						$val = explode(',', $val);
						
						// Check if an element has a label defined
						foreach($val AS $id => $element) {
							if(preg_match("/^([^\[.]+:)?(\[(.+)\]|(.+))$/", $element, $arrRet)) {
								$label = '';
								$map = '';
								
								// When no label is set, set map or url as label
								if($arrRet[1] != '') {
									$label = substr($arrRet[1],0,-1);
								} else {
									if($arrRet[3] != '') {
										$label = $arrRet[3];
									} else {
										$label = $arrRet[4];
									}
								}
								
								if(isset($arrRet[4]) && $arrRet[4] != '') {
									// Remove leading/trailing spaces
									$map = $arrRet[4];
								}

								// Remove surrounding spaces
								$label = trim($label);
								$map = trim($map);
								
								// Save the extracted information to an array
								$val[$id] = Array('label' => $label, 'map' => $map, 'url' => $arrRet[3], 'target' => '');
							}
						}
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
	}
	
	/**
	 * Checks if the main config file is valid
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkMainConfigIsValid($printErr) {
		// check given objects and attributes
		foreach($this->config AS $type => &$vars) {
			if(!preg_match('/^comment_/',$type)) {
				if(isset($this->validConfig[$type]) || preg_match('/^(backend|rotation)_/', $type)) {
					// loop validConfig for checking: => missing "must" atributes
					if(preg_match('/^backend_/', $type)) {
						if(isset($this->validConfig['backend']['options'][$this->getValue($type,'backendtype')]) 
							 && is_array($this->validConfig['backend']['options'][$this->getValue($type,'backendtype')])) {
							$arrValidConfig = array_merge($this->validConfig['backend'], $this->validConfig['backend']['options'][$this->getValue($type,'backendtype')]);
						} else {
							$arrValidConfig = $this->validConfig['backend'];
						}
					} elseif(preg_match('/^rotation_/', $type)) {
						$arrValidConfig = $this->validConfig['rotation'];
					} else {
						$arrValidConfig = $this->validConfig[$type];
					}
					foreach($arrValidConfig AS $key => &$val) {
						if((isset($val['must']) && $val['must'] == '1')) {
							// value is "must"
							if($this->getValue($type,$key) == '') {
								// a "must" value is missing or empty
								$CORE = new GlobalCore($this);
								new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mainMustValueNotSet', 'ATTRIBUTE~'.$key.',TYPE~'.$type), $CORE->MAINCFG->getValue('paths','htmlbase'));
								return FALSE;
							}
						}
					}
					
					// loop given elements for checking: => all given attributes valid
					foreach($vars AS $key => $val) {
						if(!ereg('^comment_',$key)) {
							if(ereg('^backend_', $type)) {
								if(isset($this->validConfig['backend']['options'][$this->getValue($type,'backendtype')]) 
									 && is_array($this->validConfig['backend']['options'][$this->getValue($type,'backendtype')])) {
									$arrValidConfig = array_merge($this->validConfig['backend'], $this->validConfig['backend']['options'][$this->getValue($type,'backendtype')]);
								} else {
									$arrValidConfig = $this->validConfig['backend'];
								}
							} elseif(ereg('^rotation_', $type)) {
								$arrValidConfig = $this->validConfig['rotation'];
							} else {
								$arrValidConfig = $this->validConfig[$type];
							}
							
							if(!isset($arrValidConfig[$key])) {
								// unknown attribute
								if($printErr) {
									$CORE = new GlobalCore($this);
									new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('unknownValue', 'ATTRIBUTE~'.$key.',TYPE~'.$type), $CORE->MAINCFG->getValue('paths','htmlbase'));
								}
								return FALSE;
							} elseif(isset($arrValidConfig[$key]['deprecated']) && $arrValidConfig[$key]['deprecated'] == 1) {
								// deprecated option
								if($printErr) {
									$CORE = new GlobalCore($this);
									new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('deprecatedOption', 'ATTRIBUTE~'.$key.',TYPE~'.$type), $CORE->MAINCFG->getValue('paths','htmlbase'));
								}
								return FALSE;
							} else {
								// Workaround to get the configured string back
								if(ereg('^rotation_', $type) && $key == 'maps') {
									foreach($val AS $intId => $arrStep) {
										if(isset($arrStep['label']) && $arrStep['label'] != '') {
											$label = $arrStep['label'].':';
										}
										
										$val[$intId] = $label.$arrStep['url'].$arrStep['map'];
									}
								}
								
								if(isset($val) && is_array($val)) {
									$val = implode(',',$val);
								}
								
								// valid attribute, now check for value format
								if(!preg_match($arrValidConfig[$key]['match'],$val)) {
									// wrong format
									if($printErr) {
										$CORE = new GlobalCore($this);
										new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('wrongValueFormat', 'TYPE~'.$type.',ATTRIBUTE~'.$key), $CORE->MAINCFG->getValue('paths','htmlbase'));
									}
									return FALSE;
								}
								
								// Check if the configured backend is defined in main configuration file
								if($type == 'defaults' && $key == 'backend' && !isset($this->config['backend_'.$val])) {
									if($printErr) {
										$CORE = new GlobalCore($this);
										new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('backendNotDefined', Array('BACKENDID' => $val)), $CORE->MAINCFG->getValue('paths','htmlbase'));
									}
									return FALSE;
								}
							}
						}
					}	
				} else {
					// unknown type
					if($printErr) {
						$CORE = new GlobalCore($this);
						new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('unknownSection', 'TYPE~'.$type), $CORE->MAINCFG->getValue('paths','htmlbase'));
					}
					return FALSE;
				}
			}
		}
		return TRUE;
	}
	
	/**
	 * Checks for existing config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkNagVisConfigExists($printErr) {
		if($this->configFile != '') {
			if(file_exists($this->configFile)) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$CORE = new GlobalCore($this);
					new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mainCfgNotExists','MAINCFG~'.$this->configFile), $CORE->MAINCFG->getValue('paths','htmlbase'));
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
	private function checkNagVisConfigReadable($printErr) {
		if($this->configFile != '') {
			if(is_readable($this->configFile)) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$CORE = new GlobalCore($this);
					new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mainCfgNotReadable', 'MAINCFG~'.$this->configFile), $CORE->MAINCFG->getValue('paths','htmlbase'));
				}
				return FALSE;
			}
		} else {
			return FALSE;
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
	public function getValue($sec, $var, $ignoreDefault=FALSE) {
		// if nothing is set in the config file, use the default value
		// (Removed "&& is_array($this->config[$sec]) due to performance issues)
		if(isset($this->config[$sec]) && isset($this->config[$sec][$var])) {
			return $this->config[$sec][$var];
		} elseif(!$ignoreDefault) {
			// Speed up this method by first checking for major sections and only if 
			// they don't match try to match the backend_ and rotation_ sections
			if($sec == 'global' || $sec == 'defaults' || $sec == 'paths') {
				return $this->validConfig[$sec][$var]['default'];
			} elseif(strpos($sec, 'backend_') === 0) {
				// If backendtype specified, return the default value of this backend
				if(isset($this->config[$sec]['backendtype']) && $this->config[$sec]['backendtype'] != '') {
					if(isset($this->validConfig['backend']['options'][$this->config[$sec]['backendtype']][$var]['default']) && $this->validConfig['backend']['options'][$this->config[$sec]['backendtype']][$var]['default'] != '') {
						return $this->validConfig['backend']['options'][$this->config[$sec]['backendtype']][$var]['default'];
					} else {
						return $this->validConfig['backend'][$var]['default'];
					}
				} else {
					// When existing, return the default value of the default backend
					if(isset($this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default']) && $this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default'] != '') {
						return $this->validConfig['backend']['options'][$this->validConfig['backend']['backendtype']['default']][$var]['default'];
					} else {
						// When existing, return the default value of backend-global options
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
	 * A getter to provide all section names of main configuration
	 *
	 * @return  Array  List of all sections as values
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSections() {
		$aRet = Array();
		foreach($this->config AS $key => $var) {
			$aRet[] = $key;
		}
		return $aRet;
	}
	
	/**
	 * Sets a runtime config value
	 *
	 * @param	String	$var	Variable
	 * @param	String	$val	Value
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setRuntimeValue($var, $val) {
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
	public function getRuntimeValue($var) {
		if(isset($this->runtimeConfig[$var])) {
			return $this->runtimeConfig[$var];
		} else {
			return '';
		}
	}
	
	/**
	 * Parses general settings
	 *
	 * @return	String 	JSON Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseGeneralProperties() {
		$arr = Array();
		
		$arr['date_format'] = $this->getValue('global', 'dateformat');
		$arr['path_htmlbase'] = $this->getValue('paths','htmlbase');
		$arr['path_htmlcgi'] = $this->getValue('paths','htmlcgi');
		$arr['path_htmlsounds'] = $this->getValue('paths','htmlsounds');
		$arr['path_htmlimages'] = $this->getValue('paths','htmlimages');
		$arr['internal_title'] = $this->getValue('internal', 'title');
		
		return json_encode($arr);
	}
	
	/**
	 * Parses the settings for the javascript worker
	 *
	 * @return	String 	JSON Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseWorkerProperties() {
		$arr = Array();
		
		$arr['worker_interval'] = $this->getValue('worker', 'interval');
		$arr['worker_update_object_states'] = $this->getValue('worker', 'updateobjectstates');
		$arr['worker_request_max_params'] = $this->getValue('worker', 'requestmaxparams');
		$arr['worker_request_max_length'] = $this->getValue('worker', 'requestmaxlength');
		
		return json_encode($arr);
	}
}
?>
