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
	 * PUBLIC queueState()
	 *
	 * Queues the state fetching to the backend.
	 *
	 * @param   Boolean  Optional flag to disable fetching of member status
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function queueState($_unused_flag = true, $bFetchMemberState = true) {
		$queries = Array('servicegroupMemberState' => true);
		
		if($this->hover_menu == 1
		   && $this->hover_childs_show == 1
		   && $bFetchMemberState)
			$queries['servicegroupMemberDetails'] = true;
		
		$this->BACKEND->queue($queries, $this);
	}
	
	/**
	 * PUBLIC applyState()
	 *
	 * Applies the fetched state
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function applyState() {
		if($this->backend_msg) {
			$this->summary_state = 'ERROR';
			$this->summary_output = $this->backend_msg;
			return;
		}

		if($this->hasMembers()) {
			foreach($this->members AS $MOBJ) {
				$MOBJ->applyState();
			}
		}
		
		// Use state summaries when some are available to
		// calculate summary state and output
		if($this->aStateCounts !== null) {
			// Calculate summary state and output
			$this->fetchSummaryStateFromCounts();
			$this->fetchSummaryOutputFromCounts();
		} else {
			$this->fetchSummaryState();
			$this->fetchSummaryOutput();
		}
		
		$this->state = $this->summary_state;
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
	
	/**
	 * PUBLIC fetchMemberObjectCounts()
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
	public function fetchMemberObjectCounts() {
		// Fist get the host states for all the hostgroup members
		try {
			$aServices = $this->BACKEND->BACKENDS[$this->backend_id]->getServiceState(Array('service_groups' => Array($this->getName() => Array('operator' => '>='))));
		} catch(BackendException $e) {
			$this->summary_state = 'UNKNOWN';
			$this->state = 'UNKNOWN';
			$this->summary_output = GlobalCore::getInstance()->getLang()->getText('Connection Problem (Backend: [BACKENDID]): [MSG]', 
			                                                                      Array('BACKENDID' => $this->backend_id, 'MSG' => $e->getMessage()));
			$this->output = $this->summary_output;
			
			return false;
		}
		
		// Regular member adding loop
		foreach($aServices AS $host => $serviceList) {
			foreach($serviceList AS $aService) {
				$OBJ = new NagVisService($this->CORE, $this->BACKEND, $this->backend_id, $host, $aService['service_description']);
				
				// Append contents of the array to the object properties
				$OBJ->setObjectInformation($aService);
				
				// Also get summary state
				$aService['summary_state'] = $aService['state'];
				$aService['summary_output'] = $aService['output'];
				
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
	 * PUBLIC fetchMemberObjects()
	 *
	 * Gets all members of the given servicegroup and saves them to the members
	 * array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchMemberObjects() {
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
	
	# End public methods
	# #########################################################################
	
	
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
			$this->summary_output = $this->CORE->getLang()->getText('The servicegroup "[GROUP]" has no members or does not exist (Backend: [BACKEND]).',
			                                                                            Array('GROUP' => $this->getName(),
			                                                                                  'BACKEND' => $this->backend_id));
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
