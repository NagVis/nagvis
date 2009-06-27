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
		// Get all member hosts
		$this->fetchMemberHostObjects();
		
		// Get all services of member host
		foreach($this->members AS &$OBJ) {
			$OBJ->fetchMembers();
		}
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Fetches the state of the hostgroup and all members. It also fetches the
	 * summary output
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchState() {
		
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
				foreach($arrHosts AS &$hostName) {
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
	 * PRIVATE fetchSummaryState()
	 *
	 * Fetches the summary state from all members
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
			
			$this->mergeSummaryOutput($arrStates, $this->CORE->LANG->getText('hosts'));
		} else {
			$this->summary_output = $this->CORE->LANG->getText('hostGroupNotFoundInDB','HOSTGROUP~'.$this->hostgroup_name);
		}
	}
}
?>
