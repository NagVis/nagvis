<?php
/*****************************************************************************
 *
 * NagiosHostgroup.php - Class of a Hostgroup in Nagios with all necessary 
 *                  information
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
	 * PUBLIC fetchMembers()
	 *
	 * Gets all member objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchMembers() {
		// The service objects are all fetched in fetchState() method
		// Seems this is not needed anymore and only a dummy at this place
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
		// New backend feature which reduces backend queries and breaks up the performance
		// problems due to the old recursive mechanism. If it's not available fall back to
		// old mechanism.
		if($this->BACKEND->checkBackendFeature($this->backend_id, 'getHostgroupStateCounts', false)) {
			// Get state counts
			$this->aStateCounts = $this->BACKEND->BACKENDS[$this->backend_id]->getHostgroupStateCounts($this->hostgroup_name, $this->only_hard_states);
			
			// Calculate summary state
			$this->fetchSummaryStateFromCounts();
			
			// Generate summary output
			$this->fetchSummaryOutputFromCounts();
			
			// This should only be called when the hover menu is needed to be shown
			if($this->hover_menu == 1) {
				if($bFetchChilds) {
					// Get member summary state+substate, output for the objects to be shown in hover menu
					$this->fetchHostObjects();
				}
			}
				
			$this->state = $this->summary_state;
		} else {
			// Get all member hosts
			$this->fetchMemberHostObjects();
			
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
		// Get all hosts and states
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			// Get additional information like the alias (maybe bad place here)
			$this->setConfiguration($this->BACKEND->BACKENDS[$this->backend_id]->getHostgroupInformations($this->hostgroup_name));
			
			$arrHosts = $this->BACKEND->BACKENDS[$this->backend_id]->getHostsByHostgroupName($this->hostgroup_name);
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
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE fetchHostObjects()
	 *
	 * Gets all hosts of the hostgroup and saves them to the members array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchHostObjects() {
		foreach($this->BACKEND->BACKENDS[$this->backend_id]->getHostgroupState($this->hostgroup_name, $this->only_hard_states) AS $arrHost) {
			$OBJ = new NagVisHost($this->CORE, $this->BACKEND, $this->backend_id, $arrHost['name']);
			
			// Append contents of the array to the object properties
			$OBJ->setObjectInformation($arrHost);
			
			// The services have to know how they should handle hard/soft 
			// states. This is a little dirty but the simplest way to do this
			// until the hard/soft state handling has moved from backend to the
			// object classes.
			$OBJ->setConfiguration($this->getObjectConfiguration());
			
			// Also get summary state
			// FIXME: Needed?
			//$OBJ->fetchSummaryState();
			$OBJ->summary_state = $OBJ->state;
			$OBJ->summary_output = $OBJ->output;
			
			// At least summary output
			// FIXME: Needed?
			//$OBJ->fetchSummaryOutput();
			$OBJ->fetchState(DONT_GET_OBJECT_STATE, DONT_GET_SINGLE_MEMBER_STATES);
			
			$this->members[] = $OBJ;
		}
	}
	
	/**
	 * PRIVATE fetchSummaryStateFromCounts()
	 *
	 * Fetches the summary state from the member state counts
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchSummaryStateFromCounts() {
		// Load the configured state weights
		$stateWeight = $this->CORE->getMainCfg()->getStateWeight();
		
		// Fetch the current state to start with
		if($this->summary_state == '') {
			$currWeight = 0;
		} else {
			if(isset($stateWeight[$this->summary_state])) {
				$sCurrSubState = 'normal';
				
				if($this->getSummaryAcknowledgement() == 1 && isset($stateWeight[$sSummaryState]['ack'])) {
					$sCurrSubState = 'ack';
				} elseif($this->getSummaryInDowntime() == 1 && isset($stateWeight[$sSummaryState]['downtime'])) {
					$sCurrSubState = 'downtime';
				}
				
				$currWeight = $stateWeight[$this->summary_state][$sCurrSubState];
			} else {
				//FIXME: Error handling: Invalid state
			}
		}
		
		// Loop all major states
		$iSumCount = 0;
		foreach($this->aStateCounts AS $sState => $aSubstates) {
			// Loop all substates (normal,ack,downtime,...)
			foreach($aSubstates AS $sSubState => $iCount) {
				// Found some objects with this state+substate
				if($iCount > 0) {
					// Count all child objects
					$iSumCount += $iCount;
					
					// Get weight
					if(isset($stateWeight[$sState]) && isset($stateWeight[$sState][$sSubState])) {
						$weight = $stateWeight[$sState][$sSubState];
						
						// The new state is worse than the current state
						if($currWeight < $weight) {
							$this->summary_state = $sState;
					
							if($sSubState == 'ack') {
								$this->summary_problem_has_been_acknowledged = 1;
							} else {
								$this->summary_problem_has_been_acknowledged = 0;
							}
							
							if($sSubState == 'downtime') {
								$this->summary_in_downtime = 1;
							} else {
								$this->summary_in_downtime = 0;
							}
						}
					} else {
						//FIXME: Error handling: Invalid state+substate
					}
				}
			}
		}
		
		// Fallback for hostgroups without members
		if($iSumCount == 0) {
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
		
		// FIXME: Recode mergeSummaryOutput method
		$this->mergeSummaryOutput($arrHostStates, $this->CORE->getLang()->getText('hosts'));
		$this->summary_output .= "<br />";
		$this->mergeSummaryOutput($arrServiceStates, $this->CORE->getLang()->getText('services'));
		
		// Fallback for hostgroups without members
		if($iSumCount == 0) {
			$this->summary_output = $this->CORE->getLang()->getText('hostGroupNotFoundInDB','HOSTGROUP~'.$this->hostgroup_name);
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
