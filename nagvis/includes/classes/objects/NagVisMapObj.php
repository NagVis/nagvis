<?php
/*****************************************************************************
 *
 * NagVisMapObj.php - Class of a Map object in NagVis with all necessary 
 *                  informations which belong to the object handling in NagVis
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
class NagVisMapObj extends NagVisStatefulObject {
	var $MAINCFG;
	var $MAPCFG;
	var $MAP;
	var $BACKEND;
	var $LANG;
	
	var $objects;
	var $linkedMaps;
	
	var $object_id;
	var $map_name;
	var $alias;
	
	var $state;
	var $output;
	var $problem_has_been_acknowledged;
	var $in_downtime;
	
	var $summary_state;
	var $summary_output;
	var $summary_problem_has_been_acknowledged;
	var $summary_in_downtime;
	
	// When this map object summarizes the state of a map this is true
	var $is_summary_object;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Object		Object of class NagVisMapCfg
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisMapObj(&$MAINCFG, &$BACKEND, &$LANG, $MAPCFG) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		
		$this->map_name = $this->MAPCFG->getName();
		$this->alias = $this->MAPCFG->getAlias();
		$this->type = 'map';
		$this->iconset = 'std_medium';
		$this->objects = Array();
		$this->linkedMaps = Array();
		
		// Crapy way to get an object ID for a map - got a better idea?
		$this->object_id = rand(0,1000);
		
		$this->state = '';
		$this->summary_state = '';
		$this->has_been_acknowledged = 0;
		
		$this->is_summary_object = FALSE;
		
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
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
		return parent::parse();
	}
	
	/**
	 * PUBLIC getMapObjects()
	 *
	 * Returns the array of objects on the map
	 *
	 * @return	Array	Array with map objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMapObjects() {
		return $this->objects;
	}
	
	/**
	 * PUBLIC getNumObjects()
	 *
	 * Returns the number of objects on the map
	 *
	 * @return	Integer	Number of objects on the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getNumObjects() {
		return count($this->objects);
	}
	
	/**
	 * PUBLIC hasObjects()
	 *
	 * The fastest way I can expect to check if the map has objects
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function hasObjects() {
		return isset($this->objects[0]);
	}
	
	/**
	 * PUBLIC fetchMembers()
	 *
	 * Gets all member objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMembers() {
		// Get all member objects
		$this->fetchMapObjects();
		
		// Get all services of member host
		foreach($this->getMapObjects() AS $OBJ) {
			// When the current map object is a summary object skip the map
			// child for preventing a loop
			if($OBJ->getType() == 'map' && $this->MAPCFG->getName() == $OBJ->MAPCFG->getName() && $this->is_summary_object) {
				continue;
			}
			
			// When the child map is a summary object declare it as a summary
			// object
			if($OBJ->getType() == 'map' && $this->MAPCFG->getName() == $OBJ->MAPCFG->getName()) {
				$OBJ->is_summary_object = TRUE;
			}
			
			// Check for indirect loop when the current child is a map object
			if($OBJ->getType() == 'map' && !$OBJ->is_summary_object && !$this->checkLoop($OBJ)) {
				continue;
			}
			
			$OBJ->fetchMembers();
		}
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
		// Get state of all member objects
		foreach($this->getMapObjects() AS $OBJ) {
			// Don't get state from textboxes and shapes
			if($OBJ->getType() == 'textbox' || $OBJ->getType() == 'shape') {
				continue;
			}
			
			$OBJ->fetchState();
			
			$OBJ->fetchIcon();
		}
		
		// Also get summary state
		$this->fetchSummaryState();
		
		// At least summary output
		$this->fetchSummaryOutput();
		
		$this->state = $this->summary_state;
	}
	
	/**
	 * PUBLIC objectTreeToMapObjects()
	 *
	 * Links the object in the object tree to to the map objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function objectTreeToMapObjects(&$OBJ, &$arrHostnames=Array()) {
		$this->objects[] = $OBJ;
		
		foreach($OBJ->getChilds() AS $OBJ1) {
			/*
			 * Check if the host is already on the map (If it's not done, the 
			 * objects with more than one parent be printed several times on the 
			 * map, especially the links to child objects will be too many.
			 */
			if(is_object($OBJ1) && !in_array($OBJ1->getName(), $arrHostnames)){
				// Add the name of this host to the array with hostnames which are
				// already on the map
				$arrHostnames[] = $OBJ1->getName();
				
				$this->objectTreeToMapObjects($OBJ1, $arrHostnames);
			}
		}
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
		if($this->hasObjects()) {
			$arrStates = Array('UNREACHABLE' => 0, 'CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
			
			foreach($this->getMapObjects() AS $OBJ) {
				// Don't reconize summarize map objects
				if($OBJ->getType() == 'map' && $OBJ->is_summary_object) {
					continue;
				}
				
				if(method_exists($OBJ,'getSummaryState')) {
					$arrStates[$OBJ->getSummaryState()]++;
				}
			}
			
			$this->mergeSummaryOutput($arrStates, $this->LANG->getText('objects'));
		} else {
			$this->summary_output = $this->LANG->getText('mapIsEmpty','MAP~'.$this->getName());
		}
	}
	
	/**
	 * Gets all objects of the map
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMapObjects() {
		foreach($this->MAPCFG->validConfig AS $type => &$arr) {
			if($type != 'global' && $type != 'template' && is_array($objs = $this->MAPCFG->getDefinitions($type))){
				foreach($objs AS $index => &$objConf) {
					$OBJ = '';
					
					// workaround
					$objConf['id'] = $index;
					
					// merge with "global" settings
					foreach($this->MAPCFG->validConfig[$type] AS $key => &$values) {
						if((!isset($objConf[$key]) || $objConf[$key] == '') && isset($values['default'])) {
							$objConf[$key] = $values['default'];
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
								$OBJ->summary_output = $this->LANG->getText('mapCfgNotExists', 'MAP~'.$objConf['map_name']);
							}
						break;
						case 'shape':
							$OBJ = new NagVisShape($this->MAINCFG, $this->LANG, $objConf['icon']);
						break;
						case 'textbox':
							$OBJ = new NagVisTextbox($this->MAINCFG, $this->LANG);
						break;
						default:
							$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot' => 'nagvis'));
							$FRONTEND->messageToUser('ERROR', 'unknownObject', 'TYPE~'.$type.',MAPNAME~'.$this->getName());
						break;
					}
					
					// Apply default configuration to object
					$OBJ->setConfiguration($objConf);
					
					// Write member to object array
					$this->objects[] = $OBJ;
				}
			}
		}
		
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
		// No direct loop, now check the harder one: indirect loop
		// Also check for permissions to view the state of the map
		
		// Check for valid permissions
		if($OBJ->checkPermissions($OBJ->MAPCFG->getValue('global',0, 'allowed_user'), FALSE)) {
			// Loop all objects on the child map to find out if there is a link back to this map (loop)
			foreach($OBJ->MAPCFG->getDefinitions('map') AS $arrChildMap) {
				if($this->MAPCFG->getName() == $arrChildMap['map_name']) {
					$FRONTEND = new GlobalPage($this->MAINCFG, Array('languageRoot' => 'nagvis'));
					$FRONTEND->messageToUser('WARNING', 'loopInMapRecursion');
					
					$OBJ->summary_state = 'UNKNOWN';
					$OBJ->summary_output = $this->LANG->getText('loopInMapRecursion');
					
					return FALSE;
				} else {
					return TRUE;
				}
			}
			
			// This is just a fallback if the above loop is not looped when there
			// are no child maps on this map
			return TRUE;
		} else {
			$OBJ->summary_state = 'UNKNOWN';
			$OBJ->summary_output = $this->LANG->getText('noReadPermissions');
			
			return FALSE;
		}
	}
	
	/**
	 * Fetches the icon for the object depending on the summary state
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchIcon() {
		// Set the paths of this icons
		$this->iconPath = $this->MAINCFG->getValue('paths', 'icon');
		$this->iconHtmlPath = $this->MAINCFG->getValue('paths', 'htmlicon');
		
		if($this->getSummaryState() != '') {
			$stateLow = strtolower($this->getSummaryState());
			
			switch($stateLow) {
				case 'unknown':
				case 'unreachable':
				case 'down':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_ack.png';
					} elseif($this->getSummaryInDowntime() == 1) {
						$icon = $this->iconset.'_downtime.png';
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.png';
					}
				break;
				case 'critical':
				case 'warning':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_sack.png';
					} elseif($this->getSummaryInDowntime() == 1) {
						$icon = $this->iconset.'_sdowntime.png';
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.png';
					}
				break;
				case 'up':
				case 'ok':
					$icon = $this->iconset.'_up.png';
				break;
				case 'pending':
					$icon = $this->iconset.'_'.$stateLow.'.png';
				break;
				default:
					$icon = $this->iconset.'_error.png';
				break;
			}
			
			//Checks whether the needed file exists
			if(@file_exists($this->MAINCFG->getValue('paths', 'icon').$icon)) {
				$this->icon = $icon;
			} else {
				$this->icon = $this->iconset.'_error.png';
			}
		} else {
			$this->icon = $this->iconset.'_error.png';
		}
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		if(isset($this->url) && $this->url != '') {
			$link = parent::createLink();
		} else {
			$link = '<a href="'.$this->MAINCFG->getValue('paths', 'htmlbase').'/index.php?map='.$this->map_name.'" target="'.$this->url_target.'">';
		};
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
		if($this->hasObjects()) {
			// Get summary state member objects
			foreach($this->getMapObjects() AS $OBJ) {
				// Don't reconize summarize map objects
				if($OBJ->getType() == 'map' && $OBJ->is_summary_object) {
					continue;
				}
				
				if(method_exists($OBJ,'getSummaryState')) {
					$this->wrapChildState($OBJ);
				}
			}
		} else {
			$this->summary_state = 'UNKNOWN';
		}
	}
	
	/**
	 * Checks for valid Permissions
	 *
	 * @param 	String 	$allowed	
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkPermissions($allowed,$printErr) {
		if(isset($allowed) && !in_array('EVERYONE', $allowed) && !in_array($this->MAINCFG->getRuntimeValue('user'), $allowed)) {
			if($printErr) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot' => 'nagvis'));
				$FRONTEND->messageToUser('ERROR', 'permissionDenied', 'USER~'.$this->MAINCFG->getRuntimeValue('user'));
			}
			return FALSE;
		} else {
		 	return TRUE;
		}
		return TRUE;
	}
	
	/**
	 * Checks if the map is in maintenance mode
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMaintenance($printErr) {
		if($this->MAPCFG->getValue('global', 0, 'in_maintenance')) {
			if($printErr) {
				$FRONTEND = new GlobalPage($this->MAINCFG, Array('languageRoot' => 'nagvis'));
				$FRONTEND->messageToUser('INFO-STOP', 'mapInMaintenance', 'MAP~'.$this->getName());
			}
			return FALSE;
		} else {
			return TRUE;
		}
		return TRUE;
	}
}
?>
