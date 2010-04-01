<?php
/*****************************************************************************
 *
 * NagiosHostgroup.php - Class of a Hostgroup in Nagios with all necessary 
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
class NagiosHostgroup extends NagVisStatefulObject {
	protected $hostgroup_name;
	protected $alias;
	protected $display_name;
	protected $address;
	
	protected $in_downtime;
	
	protected $members;
	
	protected $aStateCounts = Array();
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 		ID of queried backend
	 * @param		String		Name of the hostgroup
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $BACKEND, $backend_id, $hostgroupName) {
		$this->backend_id = $backend_id;
		$this->hostgroup_name = $hostgroupName;
		
		$this->members = Array();
		
		parent::__construct($CORE, $BACKEND);
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Fetches the state of the hostgroup and all members. It also fetches the
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
		if($this->BACKEND->checkBackendFeature($this->backend_id, 'getHostgroupStateCounts', false)) {
			try {
				$this->BACKEND->checkBackendInitialized($this->backend_id, TRUE);
				$this->aStateCounts = $this->BACKEND->BACKENDS[$this->backend_id]->getHostgroupStateCounts($this->hostgroup_name, $this->only_hard_states);
			} catch(BackendException $e) {
				$this->aStateCounts = Array();
				$this->setBackendConnectionProblem($e);
				return false;
			}
			
			// Calculate summary state
			$this->fetchSummaryStateFromCounts();
			
			// Generate summary output
			$this->fetchSummaryOutputFromCounts();
			
			// This should only be called when the hover menu is enabled and the childs
			// should be to be shown
			if($this->hover_menu == 1 && $this->hover_childs_show == 1 && $bFetchChilds) {
				// Get member summary state+substate, output for the objects to be shown in hover menu
				$this->fetchHostObjects();
			}
				
			$this->state = $this->summary_state;
		} else {
			try {
				// Get all member hosts
				$this->fetchMemberHostObjects();
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
	 * Returns the number of member objects
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
	 * Simple check if the hostgroup has at least one member
	 *
	 * @return Boolean	Yes, No
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function hasMembers() {
		return isset($this->members[0]);
	}
	
	/**
	 * PUBLIC fetchMemberHostObjects)
	 *
	 * Gets all members of the given hostgroup and saves them to the members array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchMemberHostObjects() {
		try {
			// Get additional information like the alias (maybe bad place here)
			$this->BACKEND->checkBackendInitialized($this->backend_id, TRUE);
			$this->setConfiguration($this->BACKEND->BACKENDS[$this->backend_id]->getHostgroupInformations($this->hostgroup_name));
			
			$arrHosts = $this->BACKEND->BACKENDS[$this->backend_id]->getHostsByHostgroupName($this->hostgroup_name);
		} catch(BackendException $e) {
			throw $e;
		}
		
		if(count($arrHosts) > 0) {
			foreach($arrHosts AS $hostName) {
				$OBJ = new NagVisHost($this->CORE, $this->BACKEND, $this->backend_id, $hostName);
				
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
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE fetchHostObjects()
	 *
	 * Gets all hosts of the hostgroup and saves them to the members array
	 * This method can only be used with backends which support the new
	 * efficent methods like the mklivestatus backend
	 *
	 * This is trimmed to reduce the number of queries to the backend:
	 * 1.) fetch states for all hosts
	 * 2.) fetch state counts for all hosts
	 *
	 * This is a big benefit compared to the old recursive algorithm
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchHostObjects() {
		// When using sort by state analyze the state counts to limit
		// the hosts to ask the backend for. Loop the state counts until the
		// object counter is above the hover_childs_limit
		$filters = null;
		if($this->hover_childs_sort === 's')
			$filters = Array('s' => $this->getChildFetchingStateFilters());
		
		// First get the host states for all the hostgroup members
		try {
			$aHosts = $this->BACKEND->BACKENDS[$this->backend_id]->getHostgroupState($this->hostgroup_name, $this->only_hard_states, $filters);
		} catch(BackendException $e) {
			$this->summary_state = 'UNKNOWN';
			$this->summary_output = GlobalCore::getInstance()->getLang()->getText('Connection Problem (Backend: [BACKENDID]): [MSG]', 
			                                                                Array('BACKENDID' => $this->backend_id, 'MSG' => $e->getMessage()));
			return false;
		}
		
		// When the first object has an error it seems that there was a problem
		// fetching the requested information
		if($aHosts[0]['state'] == 'ERROR') {
			// Only set the summary state
			$this->summary_state = $aHosts[0]['state'];
			$this->summary_output = $aHosts[0]['output'];
		} else {
			// Regular handling
			
			// Now fetch the service state counts for all hostgroup members
			try {
				$aServiceStateCounts = $this->BACKEND->BACKENDS[$this->backend_id]->getHostgroupHostStateCounts($this->hostgroup_name, $this->only_hard_states);
			} catch(BackendException $e) {
				$aServiceStateCounts = Array();
			}
			
			foreach($aHosts AS $aHost) {
				$OBJ = new NagVisHost($this->CORE, $this->BACKEND, $this->backend_id, $aHost['name']);
				
				// Append contents of the array to the object properties
				$OBJ->setObjectInformation($aHost);
				
				// The services have to know how they should handle hard/soft 
				// states. This is a little dirty but the simplest way to do this
				// until the hard/soft state handling has moved from backend to the
				// object classes.
				$OBJ->setConfiguration($this->getObjectConfiguration());
				
				// Put state counts to the object
				if(isset($aServiceStateCounts[$aHost['name']])) {
					$OBJ->setStateCounts($aServiceStateCounts[$aHost['name']]);
				}
				
				// Fetch summary state and output
				$OBJ->fetchSummariesFromCounts();
				
				$this->members[] = $OBJ;
			}
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
		$arrHostStates = Array();
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
					
					if($sState === 'UP' || $sState === 'DOWN' || $sState === 'UNREACHABLE') {
						if(!isset($arrHostStates[$sState])) {
							$arrHostStates[$sState] = $iCount;
						} else {
							$arrHostStates[$sState] += $iCount;
						}
					} else {
						if(!isset($arrServiceStates[$sState])) {
							$arrServiceStates[$sState] = $iCount;
						} else {
							$arrServiceStates[$sState] += $iCount;
						}
					}
				}
			}
		}
		
		// Fallback for hostgroups without members
		if($iSumCount == 0) {
			$this->summary_output = $this->CORE->getLang()->getText('hostGroupNotFoundInDB','HOSTGROUP~'.$this->hostgroup_name);
		} else {
			// FIXME: Recode mergeSummaryOutput method
			$this->mergeSummaryOutput($arrHostStates, $this->CORE->getLang()->getText('hosts'));
			$this->summary_output .= "<br />";
			$this->mergeSummaryOutput($arrServiceStates, $this->CORE->getLang()->getText('services'));
		}
	}
	
	/**
	 * PRIVATE fetchSummaryState()
	 *
	 * Fetches the summary state from all members recursive
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchSummaryState() {
		if($this->hasMembers()) {
			// Get summary state member objects
			foreach($this->members AS &$MEMBER) {
				$this->wrapChildState($MEMBER);
			}
		} else {
			$this->summary_state = 'ERROR';
		}
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output from all members
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchSummaryOutput() {
		if($this->hasMembers()) {
			$arrStates = Array('UNREACHABLE' => 0, 'CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
			
			// Get summary state of this and child objects
			foreach($this->members AS &$MEMBER) {
				$arrStates[$MEMBER->getSummaryState()]++;
			}
			
			$this->mergeSummaryOutput($arrStates, $this->CORE->getLang()->getText('hosts'));
		} else {
			$this->summary_output = $this->CORE->getLang()->getText('hostGroupNotFoundInDB','HOSTGROUP~'.$this->hostgroup_name);
		}
	}
}
?>
