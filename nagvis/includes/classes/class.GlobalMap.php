<?php
/** 
 * Class for printing the map
 * Should be used by ALL pages of NagVis and NagVisWui
 */
class GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	
	var $objects;
	var $linkedMaps = Array();
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalMap(&$MAINCFG,&$MAPCFG,&$BACKEND='') {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::GlobalMap($MAINCFG,$MAPCFG,$BACKEND)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::GlobalMap()');
	}
	
	/**
	 * Check if GD-Libs installed, when GD-Libs are enabled.
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkGd($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::checkGd('.$printErr.')');
		if($this->MAINCFG->getValue('global', 'usegdlibs') == '1') {
        	if(!extension_loaded('gd')) {
        		if($printErr) {
	                $FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','gdLibNotFound');
	            }
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkGd(): FALSE');
	            return FALSE;
            } else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkGd(): TRUE');
            	return TRUE;
        	}
        } else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkGd(): TRUE');
            return TRUE;
        }
	}
	
	/**
	 * Gets the background of the map
	 *
	 * @param	String	$type	Type of Background (gd/img)
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getBackground($type='gd') {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getBackground('.$type.')');
		$style = '';
		if($this->MAPCFG->getName() != '') {
			if($this->MAINCFG->getValue('global', 'usegdlibs') == '1' && $type == 'gd' && $this->checkGd(0)) {
				$src = './draw.php?map='.$this->MAPCFG->getName();
			} else {
				$src = $this->MAINCFG->getValue('paths', 'htmlmap').$this->MAPCFG->getImage();
			}
		} else {
			$src = './images/internal/wuilogo.png';
			$style = 'width:800px; height:600px;';
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getBackground(): Array(...)');
		return '<img id="background" src="'.$src.'" style="z-index:0;'.$style.'" alt="">';
	}
	
	/**
	 * Gets all objects of the map
	 *
	 * @param	Boolean	$getState	With state?
	 * @return	Array	Array of Objects of this map
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getMapObjects($getState=1,$mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getMapObjects('.$getState.','.$mergeWithGlobals.')');
		$objects = Array();
		
		$objects = array_merge($objects,$this->getObjectsOfType('map',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('host',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('service',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('hostgroup',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('servicegroup',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('textbox',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('shape',0,$mergeWithGlobals));
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getMapObjects(): Array(...)');
		return $objects;
	}
	
	/**
	 * Gets all objects of the defined type from a map and return an array with states
	 *
	 * @param	String	$type		Type of objects
	 * @param	Boolean	$getState	With state?
	 * @return	Array	Array of Objects of this type on the map
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getObjectsOfType($type,$getState=1,$mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getObjectsOfType('.$type.','.$getState.','.$mergeWithGlobals.')');
		// object array
		$objects = Array();
		
		// Default object state
		if($type == 'host' || $type == 'hostgroup') {
			$objState = Array('state'=>'UP','stateOutput'=>'Default State');
		} else {
			$objState = Array('state'=>'OK','stateOutput'=>'Default State');
		}
		
		if(is_array($objs = $this->MAPCFG->getDefinitions($type))){
			foreach($objs AS $index => $obj) {
				if (DEBUG&&DEBUGLEVEL&2) debug('Start object of type: '.$type);
				// workaround
				$obj['id'] = $index;
				
				if($mergeWithGlobals) {
					// merge with "global" settings
					foreach($this->MAPCFG->validConfig[$type] AS $key => $values) {
						if((!isset($obj[$key]) || $obj[$key] == '') && isset($values['default'])) {
							$obj[$key] = $values['default'];
						}
					}
				}
				
				// add default state to the object
				$obj = array_merge($obj,$objState);
				
				if($getState) {
					$obj = array_merge($obj,$this->getState($obj));
				}
				
				if($obj['type'] != 'textbox' && $obj['type'] != 'shape') {
					$obj['icon'] = $this->getIcon($obj);
				}
				
				// add object to array of objects
				$objects[] = $obj;
				if (DEBUG&&DEBUGLEVEL&2) debug('End object of type: '.$type);
			}
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getObjectsOfType(): Array(...)');
			return $objects;
		}
	}
	
	/**
	 * Gets the summary state of all objects on the map
	 *
	 * @param	Array	$arr	Array with states
	 * @return	String	Summary state of the map
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getMapState(&$arr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getMapState(Array(...))');
		$ret = Array();
		foreach($arr AS $obj) {
			$ret[] = $obj['state'];
		}
		
		$sRet = $this->wrapState($ret);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getMapState(): '.$sRet);
		return $sRet;
	}
	
	/**
	 * Gets the state of an object
	 *
	 * @param	Array	$obj	Array with object properties
	 * @return	Array	Array with state of the object
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getState(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getState(&$obj)');
		$state = Array('State'=>'','Output'=>'');
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		switch($obj['type']) {
			case 'map':
				// save mapName in linkedMaps array
				$this->linkedMaps[] = $this->MAPCFG->getName();
				
				$SUBMAPCFG = new NagVisMapCfg($this->MAINCFG,$obj[$name]);
				$SUBMAPCFG->readMapConfig();
				$SUBMAP = new GlobalMap($this->MAINCFG,$SUBMAPCFG,$this->BACKEND);
				$SUBMAP->linkedMaps = $this->linkedMaps;
				
				if($this->checkPermissions($SUBMAPCFG->getValue('global',0, 'allowed_user'),FALSE)) {
					// prevent loops in recursion
					if(in_array($SUBMAPCFG->getName(),$this->linkedMaps)) {
		                $FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
			            $FRONTEND->messageToUser('WARNING','loopInMapRecursion');
						
						$state = Array('State' => 'UNKNOWN','Output' => $FRONTEND->LANG->getMessageText('loopInMapRecursion'));
					} else {
						$state = $SUBMAP->getMapState($SUBMAP->getMapObjects(1));
						$state = Array('State' => $state,'Output'=>'State of child map is '.$state);
					}
				} else {
					$state = Array('State' => 'UNKNOWN','Output'=>'Error: You\'re not permited to view the state of this map.');
				}
			break;
			case 'textbox':
				// Check if set a hostname
				if(isset($obj['host_name'])) {
					if($this->BACKEND->checkBackendInitialized($obj['backend_id'],TRUE)) {
						$state = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$obj['host_name'],$obj['recognize_services'],'',$obj['only_hard_states']);
					}
				}
			break;
			default:
				if(isset($obj['line_type']) && $obj['line_type'] == '20') {
					// line with 2 states...
					list($objNameFrom,$objNameTo) = explode(',', $obj[$name]);
					list($serviceDescriptionFrom,$serviceDescriptionTo) = explode(',', $obj['service_description']);
					
					if($this->BACKEND->checkBackendInitialized($obj['backend_id'],TRUE)) {
						$state1 = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$objNameFrom,$obj['recognize_services'],$serviceDescriptionFrom,$obj['only_hard_states']);
						$state2 = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$objNameTo,$obj['recognize_services'],$serviceDescriptionTo,$obj['only_hard_states']);
					}
					$state = Array('State' => $this->wrapState(Array($state1['State'],$state2['State'])),'Output' => 'State1: '.$state1['Output'].'<br />State2:'.$state2['Output']);
				} else {
					if(!isset($obj['service_description'])) {
						$obj['service_description'] = '';
					}
					if(!isset($obj['recognize_services'])) {
						$obj['recognize_services'] = '';	
					}
					
					if($this->BACKEND->checkBackendInitialized($obj['backend_id'],TRUE)) {
						$state = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$obj[$name],$obj['recognize_services'],$obj['service_description'],$obj['only_hard_states']);
					}
				}
			break;	
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getState(): Array()');
		return Array('state' => $state['State'],'stateOutput' => $state['Output']);
	}
	
	/**
	 * Searches the icon for an object
	 *
	 * @param	Array	$obj	Array with object properties
	 * @return	String	Name of the icon
	 * @author Michael Luebben <michael_luebben@web.de>
	 * @author Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getIcon(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getIcon(&$obj)');
		$stateLow = strtolower($obj['state']);
		
		switch($obj['type']) {
			case 'map':
				switch($stateLow) {
					case 'ok':
					case 'warning':
					case 'critical':
					case 'unknown':
					case 'ack':		
						$icon = $obj['iconset'].'_'.$stateLow.'.png';
					break;
					default:
						$icon = $obj['iconset'].'_error.png';
					break;
				}
			break;
			case 'host':
			case 'hostgroup':
				switch($stateLow) {
					case 'down':
					case 'unknown':
					case 'critical':
					case 'unreachable':
					case 'warning':
					case 'ack':
					case 'up':
						$icon = $obj['iconset'].'_'.$stateLow.'.png';
					break;
					default:
						$icon = $obj['iconset'].'_error.png';
					break;
				}
			break;
			case 'service':
			case 'servicegroup':
				switch($stateLow) {
					case 'critical':
					case 'warning':
					case 'sack':
					case 'unknown':
					case 'ok':
						$icon = $obj['iconset'].'_'.$stateLow.'.png';
					break;
					default:	
						$icon = $obj['iconset'].'_error.png';
					break;
				}
			break;
			default:
					$icon = $obj['iconset'].'_error.png';
			break;
		}
		
		//replaced: if(file_exists($this->MAINCFG->getValue('paths', 'icon').$icon)) {
		if(@fclose(@fopen($this->MAINCFG->getValue('paths', 'icon').$icon,'r'))) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getIcon(): '.$icon);
			return $icon;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getIcon(): '.$obj['iconset'].'_error.png');
			return $obj['iconset'].'_error.png';
		}
	}
	
	/**
	 * Create a position for a icon on the map
	 *
	 * @param	Array	Array with object properties
	 * @return	Array	Array with object properties
	 * @author	Michael Luebben <michael_luebben@web.de>
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function fixIconPosition(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::fixIconPosition(&$obj)');
		if(!isset($obj['path']) | $obj['path'] == '') {
			$imgPath = $obj['icon'];
		} else {
			$imgPath = $obj['path'].$obj['icon'];
		}
		
		if(file_exists($imgPath)) {
			$size = getimagesize($imgPath);
		} else {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		    $FRONTEND->messageToUser('WARNING','iconNotExists','IMGPATH~'.$imgPath);
		    
			$obj['path'] = $this->MAINCFG->getValue('paths', 'icon');
			$obj['htmlPath'] = $this->MAINCFG->getValue('paths', 'htmlicon');
			$obj['icon'] = '20x20.gif';
			$size = getimagesize($obj['path'].$obj['icon']);
		}
			
		$obj['x'] = $obj['x'] - ($size[0] / 2);
		$obj['y'] = $obj['y'] - ($size[1] / 2);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::fixIconPosition(): Array(...)');
		return $obj;
	}
	
	/**
	 * Wraps all states in an Array to a summary state
	 *
	 * @param	Array	Array with objects states
	 * @return	String	Object state (DOWN|CRITICAL|WARNING|UNKNOWN|ERROR)
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function wrapState(&$objStates) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::wrapState(Array(...))');
		if(in_array('DOWN', $objStates) || in_array('CRITICAL', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): CRITICAL');
			return 'CRITICAL';
		} elseif(in_array('WARNING', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): WARNING');
			return 'WARNING';
		} elseif(in_array('UNKNOWN', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): UNKNOWN');
			return 'UNKNOWN';
		} elseif(in_array('ERROR', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): ERROR');
			return 'ERROR';
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): OK');
			return 'OK';
		}
	}
	
	/**
	 * Gets the paths to the icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	Array with object informations
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getIconPaths(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getIconPaths(&$obj)');
		if($obj['type'] == 'shape') {
			if(preg_match('/^\[(.*)\]$/',$obj['icon'],$match) > 0) {
				$obj['path'] = '';
				$obj['htmlPath'] = '';
			} else {
				$obj['path'] = $this->MAINCFG->getValue('paths', 'shape');
				$obj['htmlPath'] = $this->MAINCFG->getValue('paths', 'htmlshape');
			}
		} else {
			$obj['path'] = $this->MAINCFG->getValue('paths', 'icon');
			$obj['htmlPath'] = $this->MAINCFG->getValue('paths', 'htmlicon');
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getIconPaths(): Array(...)');
		return $obj;
	}
	
	/**
	 * Checks for valid Permissions
	 *
	 * @param 	String 	$allowed	
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkPermissions(&$allowed,$printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::checkPermissions(Array(...),'.$printErr.')');
		if(isset($allowed) && !in_array('EVERYONE', $allowed) && !in_array($this->MAINCFG->getRuntimeValue('user'),$allowed)) {
        	if($printErr) {
        		$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		        $FRONTEND->messageToUser('ERROR','permissionDenied','USER~'.$this->MAINCFG->getRuntimeValue('user'));
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkPermissions(): FALSE');
			return FALSE;
        } else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkPermissions(): TRUE');
        	return TRUE;
    	}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkPermissions(): TRUE');
		return TRUE;
	}
}