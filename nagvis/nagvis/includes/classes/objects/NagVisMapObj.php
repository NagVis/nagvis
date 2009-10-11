<?php
/*****************************************************************************
 *
 * NagVisMapObj.php - Class of a Map object in NagVis with all necessary 
 *                  information which belong to the object handling in NagVis
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
	protected $MAPCFG;
	private $MAP;
	
	protected $members;
	protected $linkedMaps;
	
	protected $map_name;
	protected $alias;
	
	protected $in_downtime;
	
	// When this map object summarizes the state of a map this is true
	protected $is_summary_object;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Object		Object of class NagVisMapCfg
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $BACKEND, $MAPCFG) {
		$this->MAPCFG = $MAPCFG;
		
		$this->map_name = $this->MAPCFG->getName();
		$this->alias = $this->MAPCFG->getAlias();
		$this->type = 'map';
		$this->iconset = 'std_medium';
		$this->members = Array();
		$this->linkedMaps = Array();
		$this->is_summary_object = false;
		
		$this->backend_id = $this->MAPCFG->getValue('global', 0, 'backend_id');
		
		parent::__construct($CORE, $BACKEND);
	}
	
	/**
	 * PUBLIC getMembers()
	 *
	 * Returns the array of objects on the map
	 *
	 * @return	Array	Array with map objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getMembers() {
		return $this->members;
	}

	/**
	 * PUBLIC getStateRelevantMembers()
	 *
	 * Returns an array of state relevant members
	 * textboxes, shapes and "summary objects" are
	 * excluded here
	 *
	 * @return  Array Array with map objects
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getStateRelevantMembers() {
		$a = Array();
		
		// Loop all members
		foreach($this->members AS $OBJ) {

			// Skip unrelevant object types
			if($OBJ->getType() == 'textbox' || $OBJ->getType() == 'shape') {
				continue;
      }

			/**
			 * When the current map object is a summary object skip the map
			 * child for preventing a loop
			 */
			if($OBJ->getType() == 'map' && $this->MAPCFG->getName() == $OBJ->MAPCFG->getName() && $this->is_summary_object == true) {
				continue;
			}

			// Add relevant objects to array
			$a[] = $OBJ;
    }

    return $a;
	}
	
	/**
	 * PUBLIC getNumMembers()
	 *
	 * Returns the number of objects on the map
	 *
	 * @return	Integer	Number of objects on the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getNumMembers() {
		return count($this->members);
	}
	
	/**
	 * PUBLIC getNumiStatefulMembers()
	 *
	 * Returns the number of stateful objects on the map
	 *
	 * @return  Integer    Number of stateful objects on the map
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getNumStatefulMembers() {
		$i = 0;
		// Loop all objects except the stateless ones and count them
		foreach($this->members AS $OBJ) {
			if($OBJ->getType() != 'textbox' && $OBJ->getType() != 'shape') {
				$i++;
			}
		}

		return $i;
	}
	
	/**
	 * PUBLIC hasObjects()
	 *
	 * The fastest way I can expect to check if the map has objects
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function hasObjects() {
		return isset($this->members[0]);
	}

	/**
	 * PUBLIC hasStatefulObjects()
	 *
	 * Check if the map has a stateful object on it
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function hasStatefulObjects() {
		// Loop all objects on the map
		foreach($this->members AS $OBJ) {
			if($OBJ->getType() != 'textbox' && $OBJ->getType() != 'shape') {
				// Exit on first result
				return true;
			}
		}

		// No stateful object found
		return false;
	}
	
	/**
	 * PUBLIC fetchMembers()
	 *
	 * Gets all member objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchMembers() {
		// Get all member objects
		$this->fetchMapObjects();
		
		// Get all services of member host
		foreach($this->getMembers() AS $OBJ) {
			// Objects of type map need some extra handling for loop prevention
			if($OBJ->getType() == 'map') {
				/**
				 * When the current map object is a summary object skip the map
				 * child for preventing a loop
				 */
				if($this->MAPCFG->getName() == $OBJ->MAPCFG->getName() && $this->is_summary_object == true) {
					continue;
				}
				
				/**
				 * This occurs when someone creates a map icon which links to itself
				 *
				 * The object will be marked as summary object and is ignored on next level.
				 * See the code above.
				 */
				if($this->MAPCFG->getName() == $OBJ->MAPCFG->getName()) {
					$OBJ->is_summary_object = true;
				}
				
				// Check for indirect loop when the current child is a map object
				if(!$OBJ->is_summary_object && !$this->checkLoop($OBJ)) {
					continue;
				}
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
	public function fetchState() {
		// Get state of all member objects
		foreach($this->getStateRelevantMembers() AS $OBJ) {
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
	 * Links the object in the object tree to the map objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function objectTreeToMapObjects(&$OBJ, &$arrHostnames=Array()) {
		$this->members[] = $OBJ;
		
		foreach($OBJ->getChilds() AS $OBJ1) {
			/*
			 * Check if the host is already on the map (If it's not done, the 
			 * objects with more than one parent will be printed several times on 
			 * the map, especially the links to child objects will be too many.
			 */
			if(is_object($OBJ1) && !in_array($OBJ1->getName(), $arrHostnames)){
				// Add the name of this host to the array with hostnames which are
				// already on the map
				$arrHostnames[] = $OBJ1->getName();
				
				$this->objectTreeToMapObjects($OBJ1, $arrHostnames);
			}
		}
	}
	
	/**
	 * Checks if the map is in maintenance mode
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkMaintenance($printErr) {
		if($this->MAPCFG->getValue('global', 0, 'in_maintenance')) {
			if($printErr) {
				new GlobalFrontendMessage('INFO-STOP', $this->CORE->LANG->getText('mapInMaintenance', 'MAP~'.$this->getName()));
			}
			return FALSE;
		} else {
			return TRUE;
		}
		return TRUE;
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
	private function fetchSummaryOutput() {
		if($this->hasObjects() && $this->hasStatefulObjects()) {
			$arrStates = Array('UNREACHABLE' => 0, 'CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
			
			foreach($this->getStateRelevantMembers() AS $OBJ) {
				if(method_exists($OBJ,'getSummaryState')) {
					$sState = $OBJ->getSummaryState();
					if(isset($arrStates[$sState])) {
						$arrStates[$sState]++;
					}
				}
			}
			
			$this->mergeSummaryOutput($arrStates, $this->CORE->LANG->getText('objects'));
		} else {
			$this->summary_output = $this->CORE->LANG->getText('mapIsEmpty','MAP~'.$this->getName());
		}
	}
	
	/**
	 * Gets all objects of the map
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchMapObjects() {
		foreach($this->MAPCFG->getValidObjectTypes() AS $type) {
			if($type != 'global' && $type != 'template' && is_array($objs = $this->MAPCFG->getDefinitions($type))){
				foreach($objs AS $index => $objConf) {
					$OBJ = '';
					
					// workaround
					//$objConf['id'] = $objConf['object_id'];
					$objConf['id'] = $index;
					
					// merge with "global" settings
					foreach($this->MAPCFG->getValidTypeKeys($type) AS $key) {
						$objConf[$key] = $this->MAPCFG->getValue($type, $index, $key);
					}
					
					switch($type) {
						case 'host':
							$OBJ = new NagVisHost($this->CORE, $this->BACKEND, $objConf['backend_id'], $objConf['host_name']);
						break;
						case 'service':
							$OBJ = new NagVisService($this->CORE, $this->BACKEND, $objConf['backend_id'], $objConf['host_name'], $objConf['service_description']);
						break;
						case 'hostgroup':
							$OBJ = new NagVisHostgroup($this->CORE, $this->BACKEND, $objConf['backend_id'], $objConf['hostgroup_name']);
						break;
						case 'servicegroup':
							$OBJ = new NagVisServicegroup($this->CORE, $this->BACKEND, $objConf['backend_id'], $objConf['servicegroup_name']);
						break;
						case 'map':
							$SUBMAPCFG = new NagVisMapCfg($this->CORE, $objConf['map_name']);
							if($SUBMAPCFG->checkMapConfigExists(0)) {
								$SUBMAPCFG->readMapConfig();
							}
							$OBJ = new NagVisMapObj($this->CORE, $this->BACKEND, $SUBMAPCFG);
							
							if(!$SUBMAPCFG->checkMapConfigExists(0)) {
								$OBJ->summary_state = 'ERROR';
								$OBJ->summary_output = $this->CORE->LANG->getText('mapCfgNotExists', 'MAP~'.$objConf['map_name']);
							}
						break;
						case 'shape':
							$OBJ = new NagVisShape($this->CORE, $objConf['icon']);
						break;
						case 'textbox':
							$OBJ = new NagVisTextbox($this->CORE);
						break;
						default:
							new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('unknownObject', 'TYPE~'.$type.',MAPNAME~'.$this->getName()));
						break;
					}
					
					// Apply default configuration to object
					$OBJ->setConfiguration($objConf);
					
					// Write member to object array
					$this->members[] = $OBJ;
				}
			}
		}
	}
	
	/**
	 * PRIVATE checkLoop()
	 *
	 * Checks if there is a loop on the linked maps and submaps.
	 * Also check for permissions to view the state of the map
	 *
	 * @param		Object		Map object to check for a loop
	 * @return	Boolean		True: No Loop, False: Loop
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkLoop($OBJ) {
		// Check for valid permissions
		if($OBJ->MAPCFG->checkPermissions($OBJ->MAPCFG->getValue('global',0, 'allowed_user'), FALSE)) {
			// Loop all objects on the child map to find out if there is a link back 
			// to this map (loop)
			//
			// Can't use OBJ->getMembers() cause they are not created yet
			foreach($OBJ->MAPCFG->getDefinitions('map') AS $arrChildMap) {
				if($this->MAPCFG->getName() == $arrChildMap['map_name']) {
					// Commented out:
					// The fact of map loops should not be reported anymore. Let's simply
					// skip the looping objects in summary view.
					//
					//new GlobalFrontendMessage('WARNING', $this->CORE->LANG->getText('loopInMapRecursion'));
					//$OBJ->summary_state = 'UNKNOWN';
					//$OBJ->summary_output = $this->CORE->LANG->getText('loopInMapRecursion');
					
					return FALSE;
				}
			}
			
			// This is just a fallback if the above loop is not looped when there
			// are no child maps on this map
			return TRUE;
		} else {
			$OBJ->summary_state = 'UNKNOWN';
			$OBJ->summary_output = $this->CORE->LANG->getText('noReadPermissions');
			
			return FALSE;
		}
	}
	
	/**
	 * PUBLIC fetchSummaryState()
	 *
	 * Fetches the summary state of the map object and all members/childs
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchSummaryState() {
		if($this->hasObjects() && $this->hasStatefulObjects()) {
			// Get summary state member objects
			foreach($this->getStateRelevantMembers() AS $OBJ) {
				if(method_exists($OBJ,'getSummaryState')) {
					$this->wrapChildState($OBJ);
				}
			}
		} else {
			$this->summary_state = 'UNKNOWN';
		}
	}
}
?>
