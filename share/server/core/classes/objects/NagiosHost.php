<?php
/*****************************************************************************
 *
 * NagiosHost.php - Class of a Host in Nagios with all necessary information
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
class NagiosHost extends NagVisStatefulObject {
	protected $host_name;
	protected $alias;
	protected $display_name;
	protected $address;
	protected $statusmap_image;
	protected $notes;
	
	protected $perfdata;
	protected $last_check;
	protected $next_check;
	protected $state_type;
	protected $current_check_attempt;
	protected $max_check_attempts;
	protected $last_state_change;
	protected $last_hard_state_change;
	
	protected $in_downtime;
	protected $downtime_start;
	protected $downtime_end;
	protected $downtime_author;
	protected $downtime_data;
	
	protected $fetchedChildObjects;
	protected $fetchedParentObjects;
	protected $childObjects;
	protected $parentObjects;
	protected $members;
	
	protected $aStateCounts = Array();
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 		ID of queried backend
	 * @param		String		Name of the host
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $BACKEND, $backend_id, $hostName) {
		$this->backend_id = $backend_id;
		
		$this->fetchedChildObjects = 0;
		$this->fetchedParentObjects = 0;
		$this->childObjects = Array();
		$this->parentObjects = Array();
		$this->members = Array();
		
		parent::__construct($CORE, $BACKEND);
		
		$this->setConfiguration(Array('host_name' => $hostName));
	}
	
	/**
	 * PUBLIC setStateCounts()
	 *
	 * Puts the service state counts to the host object
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setStateCounts($aStateCounts) {
		$this->aStateCounts = $aStateCounts;
	}
	
	/**
	 * PUBLIC fetchSummariesFromCounts()
	 *
	 * Fetches the summary state and output from the already set state counts
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchSummariesFromCounts() {
		// Generate summary output
		$this->fetchSummaryOutputFromCounts();
		
		// Add host state to counts
		// This should be done after output generation and before
		// summary state fetching. It could confuse the output fetching but
		// is needed for the summary state
		$this->addHostStateToStateCounts();
				
		// Calculate summary state
		$this->fetchSummaryStateFromCounts();
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Gets the state of the host and all its services from selected backend. It
	 * forms the summary output
	 *
	 * @param   Boolean  Optional flag to disable fetching of member status
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchState($bFetchObjectState = true, $bFetchChilds = true) {
		$error = false;
		
		// Get host state and general host information
		// This can be ignored when called from e.g. hostgroups where the state
		// has been fetched for all members before
		if($bFetchObjectState === true) {
			try {
				$this->BACKEND->checkBackendInitialized($this->backend_id, true);
				$aHost = $this->BACKEND->BACKENDS[$this->backend_id]->getHostState($this->host_name, $this->only_hard_states);
				$this->setObjectInformation($aHost);
			} catch(BackendException $e) {
				$this->setBackendConnectionProblem($e);
				$error = true;
			}
		}
		
		// New backend feature which reduces backend queries and breaks up the performance
		// problems due to the old recursive mechanism. If it's not available fall back to
		// old mechanism.
		if($this->BACKEND->checkBackendFeature($this->backend_id, 'getHostStateCounts', false)) {
			$useStateCounts = true;
		} else {
			$useStateCounts = false;
		}

		if($this->getRecognizeServices() && $useStateCounts) {
			try {
				$this->BACKEND->checkBackendInitialized($this->backend_id, true);
				$this->aStateCounts = $this->BACKEND->BACKENDS[$this->backend_id]->getHostStateCounts($this->host_name, $this->only_hard_states);
			} catch(BackendException $e) {
				$this->aStateCounts = Array();
			}
		
			// Calculate summary state and output
			$this->fetchSummariesFromCounts();
		
			// Get all service states
			// These information are only interesting when the hover_menu is shown
			/* FIXME: Get member summary state+substate, output for the objects to
					be shown in hover menu. This could be improved by limiting the 
					number of members the state will be fetched for.
					For example: when the members will be sorted by name and limited to
					10 it is only neccessary to fetch 10 members.
					When member should be sorted by state the state counts could be
					used to exclude objects with states which will not be displayed.
			*/
			if($this->hover_menu == 1 && $this->hover_childs_show == 1 && $bFetchChilds && !$error && $this->getState() != 'ERROR' && !$this->hasMembers()) {
				$this->fetchServiceObjects();
			}
		} elseif($this->getRecognizeServices()) {
			// Only merge host state with service state when recognize_services is set 
			if(!$error && $this->getState() != 'ERROR' && !$this->hasMembers()) {
				$this->fetchServiceObjects();
			}
		}

		if(!$this->getRecognizeServices() || !$useStateCounts || $error) {
			$this->fetchSummaryState();
			$this->fetchSummaryOutput();
		}
	}
	
	/**
	 * PUBLIC fetchParents()
	 *
	 * Gets all parent objects of this host from the backend. The parent objects are
	 * saved to the parentObjects array.
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchParents($maxLayers = -1, &$objConf = Array(), &$ignoreHosts = Array(), &$arrHostnames, &$arrMapObjects) {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			if(!$this->fetchedParentObjects) {
				$this->fetchDirectParentObjects($objConf, $ignoreHosts, $arrHostnames, $arrMapObjects);
			}
			
			/**
			 * If maxLayers is not set there is no layer limitation
			 */
			if($maxLayers < 0 || $maxLayers > 0) {
				foreach($this->parentObjects AS &$OBJ) {
					$OBJ->fetchParents($maxLayers-1, $objConf, $ignoreHosts, $arrHostnames, $arrMapObjects);
				}
			}
		}
	}
	/**
	 * PUBLIC filterParents()
	 *
	 * Filters the parents depending on the allowed hosts list. All objects which
	 * are not in the list and are no child of a host in this list will be
	 * removed from the map.
	 *
	 * @param	Array	List of allowed hosts
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function filterParents(&$arrAllowedHosts) {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$remain = 0;
			
			$numChilds = $this->getNumParents();
			for($i = 0; $i < $numChilds; $i++) {
				$OBJ = &$this->parentObjects[$i];
				$selfRemain = 0;
				
				if(is_object($OBJ)) {
					/**
					 * The current parent is member of the filter group, it declares 
					 * itselfs as remaining object
					 */
					if(in_array($OBJ->getName(), $arrAllowedHosts)) {
						$selfRemain = 1;
					} else {
						$selfRemain = 0;
					}
					
					/**
					 * If there are parent objects loop them all to get their remaining
					 * state. If there is no parent object the only remaining state is
					 * the state of the current parent object.
					 */
					if($OBJ->hasParents()) {
						$parentsRemain = $OBJ->filterParents($arrAllowedHosts);
						
						if(!$selfRemain && $parentsRemain) {
							$selfRemain = 1;
						}
					}
					
					// If the host should not remain on the map remove it from the 
					// object tree
					if(!$selfRemain) {
						// Remove the object from the tree
						unset($this->parentObjects[$i]);
					}
				}
				
				$remain |= $selfRemain;
			}
			return $remain;
		}
	}
	
	/**
	 * PUBLIC fetchChilds()
	 *
	 * Gets all child objects of this host from the backend. The child objects are
	 * saved to the childObjects array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchChilds($maxLayers=-1, &$objConf=Array(), &$ignoreHosts=Array(), &$arrHostnames, &$arrMapObjects) {
		if(!$this->fetchedChildObjects) {
			$this->fetchDirectChildObjects($objConf, $ignoreHosts, $arrHostnames, $arrMapObjects);
		}
		
		/**
			* If maxLayers is not set there is no layer limitation
			*/
		if($maxLayers < 0 || $maxLayers > 0) {
			foreach($this->childObjects AS &$OBJ) {
				$OBJ->fetchChilds($maxLayers-1, $objConf, $ignoreHosts, $arrHostnames, $arrMapObjects);
			}
		}
	}
	
	/**
	 * PUBLIC filterChilds()
	 *
	 * Filters the children depending on the allowed hosts list. All objects which
	 * are not in the list and are no parent of a host in this list will be
	 * removed from the map.
	 *
	 * @param	Array	List of allowed hosts
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function filterChilds(&$arrAllowedHosts) {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$remain = 0;
			
			$numChilds = $this->getNumChilds();
			for($i = 0; $i < $numChilds; $i++) {
				$OBJ = &$this->childObjects[$i];
				$selfRemain = 0;
				
				if(is_object($OBJ)) {
					/**
					 * The current child is member of the filter group, it declares 
					 * itselfs as remaining object
					 */
					if(in_array($OBJ->getName(), $arrAllowedHosts)) {
						$selfRemain = 1;
					} else {
						$selfRemain = 0;
					}
					
					/**
					 * If there are child objects loop them all to get their remaining
					 * state. If there is no child object the only remaining state is
					 * the state of the current child object.
					 */
					if($OBJ->hasChilds()) {
						$childsRemain = $OBJ->filterChilds($arrAllowedHosts);
						
						if(!$selfRemain && $childsRemain) {
							$selfRemain = 1;
						}
					}
					
					// If the host should not remain on the map remove it from the 
					// object tree
					if(!$selfRemain) {
						// Remove the object from the tree
						unset($this->childObjects[$i]);
					}
				}
				
				$remain |= $selfRemain;
			}
			return $remain;
		}
	}
	
	/**
	 * PUBLIC getChildsAndParents()
	 *
	 * Returns all childs and parent objects
	 *
	 * @return	Array		Array of host objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getChildsAndParents() {
		return array_merge($this->parentObjects, $this->childObjects);
	}
	
	/**
	 * PUBLIC getNumParents()
	 *
	 * Returns the count of parent objects
	 *
	 * @return	Integer		Number of child objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getNumParents() {
		return count($this->parentObjects);
	}
	
	/**
	 * PUBLIC getParents()
	 *
	 * Returns all parent objects in parentObjects array 
	 *
	 * @return	Array		Array of host objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getParents() {
		return $this->parentObjects;
	}
	
	/**
	 * PUBLIC hasParents()
	 *
	 * Simple check if the host has at least one parent
	 *
	 * @return Boolean	Yes: Has parents, No: No parent
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function hasParents() {
		return isset($this->parentObjects[0]);
	}
	
	/**
	 * PUBLIC getNumChilds()
	 *
	 * Returns the count of child objects
	 *
	 * @return	Integer		Number of child objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getNumChilds() {
		return count($this->childObjects);
	}
	
	/**
	 * PUBLIC getChilds()
	 *
	 * Returns all child objects in childObjects array 
	 *
	 * @return	Array		Array of host objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getChilds() {
		return $this->childObjects;
	}
	
	/**
	 * PUBLIC hasChilds()
	 *
	 * Simple check if the host has at least one child
	 *
	 * @return Boolean	Yes: Has children, No: No Child
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function hasChilds() {
		return isset($this->childObjects[0]);
	}
	
	/**
	 * PUBLIC getNumMembers()
	 *
	 * Returns the number of services
	 *
	 * @return	Integer		Number of services
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getNumMembers() {
		return count($this->members);
	}
	
	/**
	 * PUBLIC getMembers()
	 *
	 * Returns the number of services
	 *
	 * @return	Array		Array of Services
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getMembers() {
		return $this->members;
	}
	
	/**
	 * PUBLIC hasMembers()
	 *
	 * Simple check if the host has at least one service
	 *
	 * @return Boolean	Yes: Has services, No: No Service
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function hasMembers() {
		return isset($this->members[0]);
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE fetchServiceObjects()
	 *
	 * Gets all services of the given host and saves them to the services array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchServiceObjects() {
		try {
			$this->BACKEND->checkBackendInitialized($this->backend_id, true);
			$aServices = $this->BACKEND->BACKENDS[$this->backend_id]->getServiceState($this->host_name, '', $this->only_hard_states);
		} catch(BackendException $e) {
			$aServices = Array();
		}
		
		foreach($aServices AS $arrService) {
			$OBJ = new NagVisService($this->CORE, $this->BACKEND, $this->backend_id, $this->host_name, $arrService['service_description']);
			
			// Append contents of the array to the object properties
			$OBJ->setObjectInformation($arrService);
			
			// The service of this host has to know how it should handle 
			//hard/soft states. This is a little dirty but the simplest way to do this
			//until the hard/soft state handling has moved from backend to the object
			// classes.
			$OBJ->setConfiguration($this->getObjectConfiguration());
			
			// Also get summary state
			$OBJ->fetchSummaryState();
			
			// At least summary output
			$OBJ->fetchSummaryOutput();
			
			$this->members[] = $OBJ;
		}
	}
	
	/**
	 * PRIVATE fetchDirectParentObjects()
	 *
	 * Gets all parent objects of the given host and saves them to the parentObjects
	 * array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchDirectParentObjects(&$objConf, &$ignoreHosts=Array(), &$arrHostnames, &$arrMapObjects) {
		try {
			$this->BACKEND->checkBackendInitialized($this->backend_id, true);
			$aParents = $this->BACKEND->BACKENDS[$this->backend_id]->getDirectParentNamesByHostName($this->getName());
		} catch(BackendException $e) {
			$aParents = Array();
		}
		foreach($aParents AS $parentName) {
			// If the host is in ignoreHosts, don't recognize it
			if(count($ignoreHosts) == 0 || !in_array($childName, $ignoreHosts)) {
				/*
				 * Check if the host is already on the map (If it's not done, the 
				 * objects with more than one parent will be printed several times on the 
				 * map, especially the links to child objects will be too many.
				 */
				if(!in_array($parentName, $arrHostnames)){
					$OBJ = new NagVisHost($this->CORE, $this->BACKEND, $this->backend_id, $parentName);
					$OBJ->setConfiguration($objConf);
					$OBJ->fetchIcon();
					
					// The number of objects on the map controlls the object id to take
					// 0 is reserved for the root host
					$OBJ->setObjectId(count($arrHostnames)+1);
					
					// Append the object to the parentObjects array
					$this->parentObjects[] = $OBJ;
					
					// Append the object to the arrMapObjects array
					$arrMapObjects[] = $this->parentObjects[count($this->parentObjects)-1];
					
					// Add the name of this host to the array with hostnames which are
					// already on the map
					$arrHostnames[] = $OBJ->getName();
				} else {
					// Add reference of already existing host object to the
					// child objects array
					foreach($arrMapObjects AS $OBJ) {
						if($OBJ->getName() == $parentName) {
							$this->childObjects[] = $OBJ;
						}
					}
				}
			}
		}

		// All parents were fetched, save the state for this object
		$this->fetchedParentObjects = 1;
	}
	
	/**
	 * PRIVATE fetchDirectChildObjects()
	 *
	 * Gets all child objects of the given host and saves them to the childObjects
	 * array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchDirectChildObjects(&$objConf, &$ignoreHosts=Array(), &$arrHostnames, &$arrMapObjects) {
		try {
			$this->BACKEND->checkBackendInitialized($this->backend_id, true);
			$aChilds = $this->BACKEND->BACKENDS[$this->backend_id]->getDirectChildNamesByHostName($this->getName());
		} catch(BackendException $e) {
			$aChilds = Array();
		}
		foreach($aChilds AS $childName) {
			// If the host is in ignoreHosts, don't recognize it
			if(count($ignoreHosts) == 0 || !in_array($childName, $ignoreHosts)) {
				/*
				 * Check if the host is already on the map (If it's not done, the 
				 * objects with more than one parent will be printed several times on the 
				 * map, especially the links to child objects will be too many.
				 */
				if(!in_array($childName, $arrHostnames)){
					$OBJ = new NagVisHost($this->CORE, $this->BACKEND, $this->backend_id, $childName);
					$OBJ->setConfiguration($objConf);
					$OBJ->fetchIcon();
					
					// The number of objects on the map controlls the object id to take
					// 0 is reserved for the root host
					$OBJ->setObjectId(count($arrHostnames)+1);
					
					// Append the object to the childObjects array
					$this->childObjects[] = $OBJ;
					
					// Append the object to the arrMapObjects array
					$arrMapObjects[] = $this->childObjects[count($this->childObjects)-1];
					
					// Add the name of this host to the array with hostnames which are
					// already on the map
					$arrHostnames[] = $OBJ->getName();
				} else {
					// Add reference of already existing host object to the
					// child objects array
					foreach($arrMapObjects AS $OBJ) {
						if($OBJ->getName() == $childName) {
							$this->childObjects[] = $OBJ;
						}
					}
				}
			}
		}

		// All children were fetched, save the state for this object
		$this->fetchedChildObjects = 1;
	}
	
	/**
	 * PRIVATE fetchSummaryState()
	 *
	 * Fetches the summary state from all services
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchSummaryState() {
		// Get Host state
		$this->summary_state = $this->state;
		$this->summary_problem_has_been_acknowledged = $this->problem_has_been_acknowledged;
		$this->summary_in_downtime = $this->in_downtime;
		
		// Only merge host state with service state when recognize_services is set 
		// to 1
		if($this->getRecognizeServices()) {
			// Get states of services and merge with host state
			foreach($this->getMembers() AS $SERVICE) {
				$this->wrapChildState($SERVICE);
			}
		}
	}
		
	/**
	 * PUBLIC addHostStateToStateCounts()
	 *
	 * Adds the current host state to the member state counts
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function addHostStateToStateCounts() {
		$sState = $this->getState();

		$stateWeight = $this->CORE->getMainCfg()->getStateWeight();
		
		$sType = 'normal';
		if($this->getAcknowledgement() == 1 && isset($stateWeight[$sState]['ack'])) {
			$sType = 'ack';
		} elseif($this->getInDowntime() == 1 && isset($stateWeight[$sState]['downtime'])) {
			$sType = 'downtime';
		}
		
		if(!isset($this->aStateCounts[$sState])) {
			$this->aStateCounts[$sState] = Array($sType => 1);
		} else {
			$this->aStateCounts[$sState][$sType] += 1;
		}
	}

	/**
	 * PRIVATE fetchSummaryOutputFromCounts()
	 *
	 * Fetches the summary output from the object state counts
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchSummaryOutputFromCounts() {
		// Write host state
		$this->summary_output = $this->CORE->getLang()->getText('hostStateIs').' '.$this->state.'. ';
		
		// Only merge host state with service state when recognize_services is set 
		// to 1
		if($this->getRecognizeServices()) {
			$iNumServices = 0;
			$arrServiceStates = Array();
			
			// Loop all major states
			foreach($this->aStateCounts AS $sState => $aSubstates) {
				// Ignore host state here
				if($sState != 'UP' && $sState != 'DOWN' && $sState != 'UNREACHABLE') {
					// Loop all substates (normal,ack,downtime,...)
					foreach($aSubstates AS $sSubState => $iCount) {
						// Found some objects with this state+substate
						if($iCount > 0) {
							if(!isset($arrServiceStates[$sState])) {
								$arrServiceStates[$sState] = $iCount;
								$iNumServices += $iCount;
							} else {
								$arrServiceStates[$sState] += $iCount;
								$iNumServices += $iCount;
							}
						}
					}
				}
			}
			
			if($iNumServices > 0) {
				$this->mergeSummaryOutput($arrServiceStates, $this->CORE->getLang()->getText('services'));
			} else {
				$this->summary_output .= $this->CORE->getLang()->getText('hostHasNoServices','HOST~'.$this->getName());
			}
		}
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output from host and all services
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchSummaryOutput() {
		// Write host state
		$this->summary_output = $this->CORE->getLang()->getText('hostStateIs').' '.$this->state.'. ';
		
		// Only merge host state with service state when recognize_services is set 
		// to 1
		if($this->getRecognizeServices()) {
			// If there are services write the summary state for them
			if($this->hasMembers()) {
				$arrStates = Array('CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
				
				foreach($this->members AS &$SERVICE) {
					$arrStates[$SERVICE->getSummaryState()]++;
				}
				
				$this->mergeSummaryOutput($arrStates, $this->CORE->getLang()->getText('services'));
			} else {
				$this->summary_output .= $this->CORE->getLang()->getText('hostHasNoServices','HOST~'.$this->getName());
			}
		}
	}
}
?>
