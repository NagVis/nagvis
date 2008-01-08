<?php
/**
 * Class of a map objects in Nagios with all necessary informations
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisMapObj extends NagVisStatefulObject {
	var $MAINCFG;
	var $MAPCFG;
	var $MAP;
	var $BACKEND;
	var $LANG;
	
	var $objects;
	var $linkedMaps;
	
	var $map_name;
	var $alias;
	
	var $state;
	var $output;
	var $problem_has_been_acknowledged;
	
	var $summary_state;
	var $summary_output;
	var $summary_problem_has_been_acknowledged;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Object		Object of class NagVisMapCfg
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisMapObj(&$MAINCFG, &$BACKEND, &$LANG, &$MAPCFG) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::NagVisMapObj(MAINCFG,BACKEND,LANG,MAPCFG)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		
		$this->map_name = $this->MAPCFG->getName();
		$this->type = 'map';
		$this->iconset = 'std_medium';
		$this->objects = Array();
		$this->linkedMaps = Array();
		
		$this->state = '';
		$this->summary_state = '';
		$this->has_been_acknowledged = 0;
		
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::NagVisMapObj()');
	}
	
	/**
	 * PUBLIC parse()
	 *
	 * Parses the object
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parse() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::parse()');
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::parse()');
		return parent::parse();
	}
	
	function getMapObjects() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::getMapObjects()');
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::getMapObjects()');
		return $this->objects;
	}
	
	/**
	 * PUBLIC fetchMembers()
	 *
	 * Gets all member objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMembers() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::fetchMembers()');
		// Get all member objects
		$this->fetchMapObjects();
		
		// Get all services of member host
		foreach($this->getMapObjects() AS $OBJ) {
			$OBJ->fetchMembers();
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::fetchMembers()');
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Fetches the state of the map and all map objects. It also fetches the
	 * summary output
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchState() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::fetchState()');
		
		// Get state of all member objects
		foreach($this->getMapObjects() AS $OBJ) {
			// Before getting state of maps we have to check if there is a loop in the maps
			if(get_class($OBJ) != 'NagVisMapObj' || (get_class($OBJ) == 'NagVisMapObj' && $this->checkLoop($OBJ))) {
				// Don't get state from textboxes and shapes
				if($OBJ->type != 'textbox' && $OBJ->type != 'shape') {
					$OBJ->fetchState();
				}
			}
			
			$OBJ->fetchIcon();
		}
		
		// Also get summary state
		$this->fetchSummaryState();
		
		// At least summary output
		$this->fetchSummaryOutput();
		$this->state = $this->summary_state;
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::fetchState()');
	}
	
	/**
	 * PUBLIC objectTreeToMapObjects()
	 *
	 * Links the object in the object tree to to the map objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function objectTreeToMapObjects(&$OBJ) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::objectTreeToMapObjects()');
		$this->objects[] = &$OBJ;
		$this->objects = array_merge($this->getMapObjects(), $OBJ->getChilds());
		
		foreach($OBJ->getChilds() AS $OBJ1) {
			$this->objectTreeToMapObjects($OBJ1);
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::objectTreeToMapObjects()');
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output of the map
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryOutput() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::fetchSummaryOutput()');
		if(count($this->getMapObjects()) > 0) {
			$arrStates = Array('CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
			
			foreach($this->getMapObjects() AS $OBJ) {
				if(method_exists($OBJ,'getSummaryState')) {
					$arrStates[$OBJ->getSummaryState()]++;
				}
			}
			
			parent::fetchSummaryOutput($arrStates, $this->LANG->getLabel('objects'));
		} else {
			$this->summary_output .= $this->LANG->getMessageText('mapIsEmpty','MAP~'.$this->getName());
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::fetchSummaryOutput()');
	}
	
	/**
	 * Gets all objects of the map
	 *
	 * @param	Boolean	$getState	With state?
	 * @return	Array	Array of Objects of this map
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMapObjects($getState=1,$mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::fetchMapObjects('.$getState.','.$mergeWithGlobals.')');
		
		/**
		 * Check if we can use cached objects. The cache is only be used when the 
		 * following things are OK: NagVis version, Nagios start time < cache time
		 */
		if(isset($_SESSION['nagvis_version']) 
		 && isset($_SESSION['nagvis_object_cache_time_'.$this->getName()]) 
		 && isset($_SESSION['nagvis_object_cache'.$this->getName()])
		 && $_SESSION['nagvis_version'] == CONST_VERSION
		 /* The following check is only temporary until I got a good idea for
		  * reloading the configuration to cached objects */
		 && $this->MAPCFG->getFileModificationTime() < $_SESSION['nagvis_object_cache_time_'.$this->getName()]
		 && $this->BACKEND->checkBackendInitialized($this->MAPCFG->getValue('global', 0, 'backend_id'), TRUE)
		 && $this->BACKEND->BACKENDS[$this->MAPCFG->getValue('global', 0, 'backend_id')]->getNagiosStartTime() < $_SESSION['nagvis_object_cache_time_'.$this->getName()]) {
			// Cache seems to be OK, use it!
			
			// Unserialize the string which stores all objects (and child objects) of
			// this map in it
			$this->objects = unserialize($_SESSION['nagvis_object_cache'.$this->getName()]);
			
			// Only do this if there are objects on that map
			if(count($this->objects) > 0) {
				// The mysql resource $CONN in the BACKEND object is not valid after 
				// serialisation, now add the current resource to the BACKEND of the cache
				$this->objects[0]->BACKEND = $this->BACKEND;
				
				// If the configuration file is newer than the cache reload the configuration for the objects
				if($this->MAPCFG->getFileModificationTime() > $_SESSION['nagvis_object_cache_time_'.$this->getName()]) {
					//FIXME: Reload the configuration (When this is fixed remove the one check in the above if statement
				}
			}
		} else {
			foreach($this->MAPCFG->validConfig AS $type => $arr) {
				if($type != 'global' && is_array($objs = $this->MAPCFG->getDefinitions($type))){
					foreach($objs AS $index => $objConf) {
						if (DEBUG&&DEBUGLEVEL&2) debug('Start object of type: '.$type);
						// workaround
						$objConf['id'] = $index;
						
						if($mergeWithGlobals) {
							// merge with "global" settings
							foreach($this->MAPCFG->validConfig[$type] AS $key => $values) {
								if((!isset($objConf[$key]) || $objConf[$key] == '') && isset($values['default'])) {
									$objConf[$key] = $values['default'];
								}
							}
						}
						
						switch($type) {
							case 'host':
								$OBJ = new NagVisHost($this->MAINCFG, $this->BACKEND, $this->LANG, $objConf['backend_id'], $objConf['host_name']);
							break;
							case 'service':
								$OBJ = new NagVisService($this->MAINCFG, $this->BACKEND, $this->LANG, $objConf['backend_id'], $objConf['host_name'], $objConf['service_description']);
							break;
							case 'hostgroup':
								$OBJ = new NagVisHostgroup($this->MAINCFG, $this->BACKEND, $this->LANG, $objConf['backend_id'], $objConf['hostgroup_name']);
							break;
							case 'servicegroup':
								$OBJ = new NagVisServicegroup($this->MAINCFG, $this->BACKEND, $this->LANG, $objConf['backend_id'], $objConf['servicegroup_name']);
							break;
							case 'map':
								$SUBMAPCFG = new NagVisMapCfg($this->MAINCFG, $objConf['map_name']);
								if($SUBMAPCFG->checkMapConfigExists(0)) {
									$SUBMAPCFG->readMapConfig();
								}
								$OBJ = new NagVisMapObj($this->MAINCFG, $this->BACKEND, $this->LANG, $SUBMAPCFG);
								
								if(!$SUBMAPCFG->checkMapConfigExists(0)) {
									$OBJ->summary_state = 'ERROR';
									$OBJ->summary_output = $this->LANG->getMessageText('mapCfgNotExists', 'MAP~'.$objConf['map_name']);
								}
							break;
							case 'shape':
								$OBJ = new NagVisShape($this->MAINCFG, $this->LANG, $objConf['icon']);
							break;
							case 'textbox':
								$OBJ = new NagVisTextbox($this->MAINCFG, $this->LANG);
							break;
							default:
								$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
								$FRONTEND->messageToUser('ERROR', 'unknownObject', 'TYPE~'.$type.';MAPNAME~'.$this->getName());
							break;
						}
						
						// Apply default configuration to object
						$OBJ->setConfiguration($objConf);
						
						// Write member to object array
						$this->objects[] = $OBJ;
						
						if (DEBUG&&DEBUGLEVEL&2) debug('End object of type: '.$type);
					}
				}
			}
			
			// Write the objects to the object cache
			$_SESSION['nagvis_version'] = CONST_VERSION;
			$_SESSION['nagvis_object_cache_time_'.$this->getName()] = time();
			// Serialize all objects of this map (including childs) to a string and
			// save this to a session variable. This is the object cache
			$_SESSION['nagvis_object_cache'.$this->getName()] = serialize($this->objects);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMapObj::fetchMapObjects()');
	}
	
	/**
	 * PRIVATE checkLoop()
	 *
	 * Checks if there is a loop on the linked maps and submaps
	 *
	 * @param		Object		Map object to check for a loop
	 * @return	Boolean		True: No Loop, False: Loop
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkLoop(&$OBJ) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::checkLoop()');
		// prevent direct loops (map including itselfes as map icon)
		if($this->MAPCFG->getName() == $OBJ->MAPCFG->getName()) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
			$FRONTEND->messageToUser('WARNING','loopInMapRecursion');
			
			$OBJ->summary_state = 'UNKNOWN';
			$OBJ->summary_output = $this->LANG->getMessageText('loopInMapRecursion');
			
			return FALSE;
		} else {
			// No direct loop, now check the harder one: indirect loop
			// Also check for permissions to view the state of the map
			
			// Check for valid permissions
			if($OBJ->checkPermissions($OBJ->MAPCFG->getValue('global',0, 'allowed_user'), FALSE)) {
				
				// Loop all objects on the child map to find out if there is a link back to this map (loop)
				foreach($OBJ->MAPCFG->getDefinitions('map') AS $arrChildMap) {
					if($this->MAPCFG->getName() == $arrChildMap['map_name']) {
						$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
						$FRONTEND->messageToUser('WARNING','loopInMapRecursion');
						
						$LANG = new GlobalLanguage($this->MAINCFG,'global:global');
						$OBJ->summary_state = 'UNKNOWN';
						$OBJ->summary_output = $this->LANG->getMessageText('loopInMapRecursion');
						
						if (DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::checkLoop(): FALSE');
						return FALSE;
					} else {
						if (DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::checkLoop(): TRUE');
						return TRUE;
					}
				}
				
				// This is just a fallback if the above loop is not looped when there
				// are no child maps on this map
				if (DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::checkLoop(): TRUE');
				return TRUE;
			} else {
				$OBJ->summary_state = 'UNKNOWN';
				$OBJ->summary_output = $this->LANG->getMessageText('noReadPermissions');
				
				if (DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::checkLoop(): FALSE');
				return FALSE;
			}
		}
	}
	
	/**
	 * Fetches the icon for the object depending on the summary state
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchIcon() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::fetchIcon()');
		if($this->getSummaryState() != '') {
			$stateLow = strtolower($this->getSummaryState());
			
			switch($stateLow) {
				case 'unreachable':
				case 'down':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_ack.png';
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.png';
					}
				break;
				case 'critical':
				case 'warning':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_sack.png';
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.png';
					}
				break;
				case 'up':
				case 'ok':
					$icon = $this->iconset.'_up.png';
				break;
				case 'unknown':
				case 'pending':
					$icon = $this->iconset.'_'.$stateLow.'.png';
				break;
				default:
					$icon = $this->iconset.'_error.png';
				break;
			}
			
			//Checks whether the needed file exists
			if(@fclose(@fopen($this->MAINCFG->getValue('paths', 'icon').$icon,'r'))) {
				$this->icon = $icon;
			} else {
				$this->icon = $this->iconset.'_error.png';
			}
		} else {
			$this->icon = $this->iconset.'_error.png';
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::fetchIcon()');
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::createLink()');
		
		if(isset($this->url) && $this->url != '') {
			$link = parent::createLink();
		} else {
			$link = '<a href="'.$this->MAINCFG->getValue('paths', 'htmlbase').'/index.php?map='.$this->map_name.'" target="'.$this->url_target.'">';
		};
		if (DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::createLink():'.$link);
		return $link;
	}
	
	/**
	 * PUBLIC fetchSummaryState()
	 *
	 * Fetches the summary state of the map object and all members/childs
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryState() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::fetchSummaryState()');
		// Get summary state member objects
		foreach($this->objects AS $OBJ) {
			if(method_exists($OBJ,'getSummaryState')) {
				$this->wrapChildState($OBJ);
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisMapObj::fetchSummaryState()');
	}
	
	/**
	 * Checks for valid Permissions
	 *
	 * @param 	String 	$allowed	
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkPermissions(&$allowed,$printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMapObj::checkPermissions(Array(...),'.$printErr.')');
		if(isset($allowed) && !in_array('EVERYONE', $allowed) && !in_array($this->MAINCFG->getRuntimeValue('user'), $allowed)) {
				if($printErr) {
						$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
						$FRONTEND->messageToUser('ERROR', 'permissionDenied', 'USER~'.$this->MAINCFG->getRuntimeValue('user'));
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMapObj::checkPermissions(): FALSE');
				return FALSE;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMapObj::checkPermissions(): TRUE');
		 	return TRUE;
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMapObj::checkPermissions(): TRUE');
		return TRUE;
	}
}
?>
