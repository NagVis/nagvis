<?php
/*****************************************************************************
 *
 * NagiosServicegroup.php - Class of a Servicegroup in Nagios with all necessary 
 *                  information
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
class NagiosServicegroup extends NagVisStatefulObject {
	protected $servicegroup_name;
	protected $alias;
	protected $display_name;
	protected $address;
	
	protected $in_downtime;
	
	protected $members;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 		ID of queried backend
	 * @param		String		Name of the servicegroup
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $BACKEND, $backend_id, $servicegroupName) {
		$this->backend_id = $backend_id;
		$this->servicegroup_name = $servicegroupName;
		
		$this->members = Array();
		
		parent::__construct($CORE, $BACKEND);
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Fetches the state of the servicegroup and all members. It also fetches the
	 * summary output
	 *
	 * @param   Boolean  Optional flag to disable fetching of member status
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchState($bFetchChilds = true) {
		/* New backend feature which reduces backend queries and breaks up the performance
		 * problems due to the old recursive mechanism. If it's not available fall back to
		 * old mechanism.
		 *
		 * the revolution starts >>> here >>> ;D
		 */
		if($this->BACKEND->checkBackendFeature($this->backend_id, 'getServicegroupStateCounts', false)) {
			// Get state counts
			try {
				$this->BACKEND->checkBackendInitialized($this->backend_id, TRUE);
				$this->aStateCounts = $this->BACKEND->BACKENDS[$this->backend_id]->getServicegroupStateCounts($this->servicegroup_name, $this->only_hard_states);
			} catch(BackendException $e) {
				$this->aStateCounts = Array();
				$this->setBackendConnectionProblem($e);
				return false;
			}
			
			// Calculate summary state
			$this->fetchSummaryStateFromCounts();
			
			// Generate summary output
			$this->fetchSummaryOutputFromCounts();
			
			// This should only be called when the hover menu is needed to be shown
			if($this->hover_menu == 1 && $this->hover_childs_show == 1 && $bFetchChilds) {
				// Get member summary state+substate, output for the objects to be shown in hover menu
				$this->fetchServiceObjects();
			}
				
			$this->state = $this->summary_state;
		} else {
			try {
				// Get all member services
				$this->fetchMemberServiceObjects();
			} catch(BackendException $e) {
				$this->setBackendConnectionProblem($e);
				return false;
			}
		
			// Get states of all members
			foreach($this->members AS &$OBJ) {
				$OBJ->fetchState();
			}
			
			// Also get summary state
			$this->fetchSummaryState();
			
			// At least summary output
			$this->fetchSummaryOutput();
			$this->state = $this->summary_state;
		}
	}
	
	/**
	 * PUBLIC getNumMembers()
	 *
	 * Counts the number of members
	 *
	 * @return	Integer		Number of members
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getNumMembers() {
		return count($this->members);
	}
	
	/**
	 * PUBLIC getMembers()
	 *
	 * Returns the member objects
	 *
	 * @return	Array		Member objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getMembers() {
		return $this->members;
	}
	
	/**
	 * PUBLIC hasMembers()
	 *
	 * Simple check if the servicegroup has at least one member
	 *
	 * @return Boolean	Yes, No
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
	 * Gets all services of the servicegroup and saves them to the members array
	 * This method can only be used with backends which support the new
	 * efficent methods like the mklivestatus backend
	 *
	 * This is trimmed to reduce the number of queries to the backend:
	 * 1.) fetch states for all services
	 * 2.) fetch state counts for all services
	 *
	 * This is a big benefit compared to the old recursive algorithm
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchServiceObjects() {
		// When using sort by state analyze the state counts to limit
		// the hosts to ask the backend for. Loop the state counts until the
		// object counter is above the hover_childs_limit
		$filters = null;
		if($this->hover_childs_sort === 's')
			$filters = Array('s' => $this->getChildFetchingStateFilters());
		
		// Fist get the host states for all the hostgroup members
		try {
			$aServices = $this->BACKEND->BACKENDS[$this->backend_id]->getServicegroupState($this->servicegroup_name, $this->only_hard_states, $filters);
		} catch(BackendException $e) {
			$this->summary_state = 'UNKNOWN';
			$this->state = 'UNKNOWN';
			$this->summary_output = GlobalCore::getInstance()->getLang()->getText('Connection Problem (Backend: [BACKENDID]): [MSG]', 
			                                                                      Array('BACKENDID' => $this->backend_id, 'MSG' => $e->getMessage()));
			$this->output = $this->summary_output;
			
			return false;
		}
		
		// When the first object has an error it seems that there was a problem
		// fetching the requested information
		if($aServices[0]['state'] == 'ERROR') {
			// Only set the summary state
			$this->summary_state = $aServices[0]['state'];
			$this->summary_output = $aServices[0]['output'];
		} else {
			// Regular member adding loop
			foreach($aServices AS $aService) {
				$OBJ = new NagVisService($this->CORE, $this->BACKEND, $this->backend_id, $aService['host'], $aService['description']);
				
				// Also get summary state
				$aService['summary_state'] = $aService['state'];
				$aService['summary_output'] = $aService['output'];
				
				// Append contents of the array to the object properties
				$OBJ->setObjectInformation($aService);
				
				// The services have to know how they should handle hard/soft 
				// states. This is a little dirty but the simplest way to do this
				// until the hard/soft state handling has moved from backend to the
				// object classes.
				$OBJ->setConfiguration($this->getObjectConfiguration());
				
				// Add child object to the members array
				$this->members[] = $OBJ;
			}
		}
	}
	
	/**
	 * PRIVATE fetchMemberServiceObjects()
	 *
	 * Gets all members of the given servicegroup and saves them to the members
	 * array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchMemberServiceObjects() {
		// Get all services and states
		try {
			$this->BACKEND->checkBackendInitialized($this->backend_id, TRUE);
			// Get additional information like the alias (maybe bad place here)
			$this->setConfiguration($this->BACKEND->BACKENDS[$this->backend_id]->getServicegroupInformations($this->servicegroup_name));
			
			$arrServices = $this->BACKEND->BACKENDS[$this->backend_id]->getServicesByServicegroupName($this->servicegroup_name);
		} catch(BackendException $e) {
			throw $e;
		}

		foreach($arrServices AS &$service) {
			$OBJ = new NagVisService($this->CORE, $this->BACKEND, $this->backend_id, $service['host_name'], $service['service_description']);
			
			// The services have to know how they should handle hard/soft 
			// states. This is a little dirty but the simplest way to do this
			// until the hard/soft state handling has moved from backend to the
			// object classes.
			$OBJ->setConfiguration($this->getObjectConfiguration());
			
			// Add child object to the members array
			$this->members[] = $OBJ;
		}
	}
	
	/**
	 * PRIVATE fetchSummaryState()
	 *
	 * Fetches the summary state of all members
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchSummaryState() {
		if($this->getNumMembers() > 0) {
			// Get summary state member objects
			foreach($this->members AS &$MEMBER) {
				$this->wrapChildState($MEMBER);
			}
		} else {
			$this->summary_state = 'ERROR';
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
		$arrServiceStates = Array();
		
		// Loop all major states
		$iSumCount = 0;
		foreach($this->aStateCounts AS $sState => $aSubstates) {
			// Loop all substates (normal,ack,downtime,...)
			foreach($aSubstates AS $sSubState => $iCount) {
				// Found some objects with this state+substate
				if($iCount > 0) {
					// Count all child objects
					$iSumCount += $iCount;
					
					if(!isset($arrServiceStates[$sState])) {
						$arrServiceStates[$sState] = $iCount;
					} else {
						$arrServiceStates[$sState] += $iCount;
					}
				}
			}
		}
		
		// FIXME: Recode mergeSummaryOutput method
		$this->mergeSummaryOutput($arrServiceStates, $this->CORE->getLang()->getText('services'));
		
		// Fallback for hostgroups without members
		if($iSumCount == 0) {
			$this->summary_output = $this->CORE->getLang()->getText('serviceGroupNotFoundInDB','SERVICEGROUP~'.$this->getName());
		}
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output from all members
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchSummaryOutput() {
		if($this->getNumMembers() > 0) {
			$arrStates = Array('UNREACHABLE' => 0, 'CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
			
			// Get summary state of this and child objects
			foreach($this->members AS &$MEMBER) {
				$arrStates[$MEMBER->getSummaryState()]++;
			}
			
			$this->mergeSummaryOutput($arrStates, $this->CORE->getLang()->getText('services'));
		} else {
			$this->summary_output = $this->CORE->getLang()->getText('serviceGroupNotFoundInDB','SERVICEGROUP~'.$this->servicegroup_name);
		}
	}
}
?>
