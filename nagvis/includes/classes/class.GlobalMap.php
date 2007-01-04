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
	function GlobalMap(&$MAINCFG,&$MAPCFG,&$BACKEND) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
	}
	
	/**
	 * Check if GD-Libs installed, when GD-Libs are enabled.
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkGd($printErr) {
		if($this->MAINCFG->getValue('global', 'usegdlibs') == "1") {
        	if(!extension_loaded('gd')) {
        		if($printErr) {
	                $FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','gdLibNotFound');
	            }
	            return FALSE;
            } else {
            	return TRUE;
        	}
        } else {
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
		$style = '';
		if($this->MAPCFG->getName() != '') {
			if($this->MAINCFG->getValue('global', 'usegdlibs') == "1" && $type == 'gd' && $this->checkGd(0)) {
				$src = "./draw.php?map=".$this->MAPCFG->getName();
			} else {
				$src = $this->MAINCFG->getValue('paths', 'htmlmap').$this->MAPCFG->getImage();
			}
		} else {
			$src = "./images/internal/wuilogo.jpg";
			$style = "width:600px; height:600px;";
		}
		
		return Array('<img id="background" src="'.$src.'" style="'.$style.'" alt="">');
	}
	
	/**
	 * Gets all objects of the map
	 *
	 * @param	Boolean	$getState	With state?
	 * @return	Array	Array of Objects of this map
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getMapObjects($getState=1,$mergeWithGlobals=1) {
		$objects = Array();
		
		$objects = array_merge($objects,$this->getObjectsOfType('map',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('host',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('service',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('hostgroup',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('servicegroup',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('textbox',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('shape',0,$mergeWithGlobals));
		
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
		// object array
		$objects = Array();
		
		// Default object state
		if($type == 'host' || $type == 'hostgroup') {
			$objState = Array('state'=>'UP','stateOutput'=>'Default State');
		} else {
			$objState = Array('state'=>'OK','stateOutput'=>'Default State');
		}
		
		if(is_array($this->MAPCFG->getDefinitions($type))){
			foreach($this->MAPCFG->getDefinitions($type) AS $index => $obj) {
				// workaround
				$obj['id'] = $index;
				
				if($mergeWithGlobals) {
					// merge with "global" settings
					foreach($this->MAPCFG->validConfig[$type] AS $key => $values) {
						$obj[$key] = $this->MAPCFG->getValue($type, $index, $key);
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
			}
			
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
	function getMapState($arr) {
		$ret = Array();
		foreach($arr AS $obj) {
			$ret[] = $obj['state'];
		}
		
		return $this->wrapState($ret);
	}
	
	/**
	 * Gets the state of an object
	 *
	 * @param	Array	$obj	Array with object properties
	 * @return	Array	Array with state of the object
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getState($obj) {
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
				
				$SUBMAPCFG = new GlobalMapCfg($this->MAINCFG,$obj[$name]);
				$SUBMAPCFG->readMapConfig();
				$SUBMAP = new GlobalMap($this->MAINCFG,$SUBMAPCFG,$this->BACKEND);
				$SUBMAP->linkedMaps = $this->linkedMaps;
				
				// prevent loops in recursion
				if(in_array($SUBMAPCFG->getName(),$this->linkedMaps)) {
	                $FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('WARNING','loopInMapRecursion');
					
					$state = Array('State' => 'UNKNOWN','Output' => 'Error: Loop in Recursion');
				} else {
					$state = $SUBMAP->getMapState($SUBMAP->getMapObjects(1));
					$state = Array('State' => $state,'Output'=>'State of child map is '.$state);
				}
			break;
			case 'textbox':
				// Check if set a hostname
				if(isset($obj['host_name'])) {
					$state = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$obj['host_name'],$obj['recognize_services'],'',$obj['only_hard_states']);
				}
			break;
			default:
				if(isset($obj['line_type']) && $obj['line_type'] == "20") {
					// line with 2 states...
					list($objNameFrom,$objNameTo) = explode(",", $obj[$name]);
					list($serviceDescriptionFrom,$serviceDescriptionTo) = explode(",", $obj['service_description']);
					
					$state1 = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$objNameFrom,$obj['recognize_services'],$serviceDescriptionFrom,$obj['only_hard_states']);
					$state2 = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$objNameTo,$obj['recognize_services'],$serviceDescriptionTo,$obj['only_hard_states']);
					
					$state = Array('State' => $this->wrapState(Array($state1['State'],$state2['State'])),'Output' => 'State1: '.$state1['Output'].'<br />State2:'.$state2['Output']);
				} else {
					if(!isset($obj['service_description'])) {
						$obj['service_description'] = '';
					}
					if(!isset($obj['recognize_services'])) {
						$obj['recognize_services'] = '';	
					}
					$state = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$obj[$name],$obj['recognize_services'],$obj['service_description'],$obj['only_hard_states']);
				}
			break;	
		}
		
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
	function getIcon($obj) {
        $valid_format = array(
                0=>"gif",
                1=>"png",
                2=>"bmp",
                3=>"jpg",
                4=>"jpeg"
        );
		$stateLow = strtolower($obj['state']);
		
		switch($obj['type']) {
			case 'map':
				switch($stateLow) {
					case 'ok':
					case 'warning':
					case 'critical':
					case 'unknown':
					case 'ack':		
						$icon = $obj['iconset'].'_'.$stateLow;
					break;
					default:
						$icon = $obj['iconset']."_error";
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
						$icon = $obj['iconset'].'_'.$stateLow;
					break;
					default:
						$icon = $obj['iconset']."_error";
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
						$icon = $obj['iconset'].'_'.$stateLow;
					break;
					default:	
						$icon = $obj['iconset']."_error";
					break;
				}
			break;
			default:
					//echo "getIcon: Unknown Object Type (".$obj['type'].")!";
					$icon = $obj['iconset']."_error";
			break;
		}

		for($i=0;$i<count($valid_format);$i++) {
			if(file_exists($this->MAINCFG->getValue('paths', 'icon').$icon.".".$valid_format[$i])) {
            	$icon .= ".".$valid_format[$i];
			}
		}
		
		if(file_exists($this->MAINCFG->getValue('paths', 'icon').$icon)) {	
			return $icon;
		} else {
			return $obj['iconset']."_error.png";
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
	function fixIconPosition($obj) {
		$size = getimagesize($obj['path'].$obj['icon']);
		$obj['x'] = $obj['x'] - ($size[0]/2);
		$obj['y'] = $obj['y'] - ($size[1]/2);
		
		return $obj;
	}
	
	/**
	 * Wraps all states in an Array to a summary state
	 *
	 * @param	Array	Array with objects states
	 * @return	String	Object state (DOWN|CRITICAL|WARNING|UNKNOWN|ERROR)
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function wrapState($objStates) {
		if(in_array("DOWN", $objStates) || in_array("CRITICAL", $objStates)) {
			return "CRITICAL";
		} elseif(in_array("WARNING", $objStates)) {
			return "WARNING";
		} elseif(in_array("UNKNOWN", $objStates)) {
			return "UNKNOWN";
		} elseif(in_array("ERROR", $objStates)) {
			return "ERROR";
		} else {
			return "OK";
		}
	}
}