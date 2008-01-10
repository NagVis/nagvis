<?php
/**
 * Class of a Host in Nagios with all necessary informations
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagiosHostgroup extends NagVisStatefulObject {
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	var $backend_id;
	
	var $hostgroup_name;
	var $alias;
	var $display_name;
	var $address;
	
	var $state;
	var $output;
	var $problem_has_been_acknowledged;
	
	var $summary_state;
	var $summary_output;
	var $summary_problem_has_been_acknowledged;
	
	var $members;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 	ID of queried backend
	 * @param		String		Name of the hostgroup
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagiosHostgroup(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostgroupName) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHostgroup::NagiosHostgroup(MAINCFG,BACKEND,LANG,'.$backend_id.','.$hostgroupName.')');
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->backend_id = $backend_id;
		$this->hostgroup_name = $hostgroupName;
		
		$this->members = Array();
		$this->state = '';
		
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHostgroup::NagiosHostgroup()');
	}
	
	/**
	 * PUBLIC fetchMembers()
	 *
	 * Gets all member objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMembers() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHostgroup::fetchMembers()');
		// Get all member hosts
		$this->fetchMemberHostObjects();
		
		// Get all services of member host
		foreach($this->members AS $OBJ) {
			$OBJ->fetchMembers();
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHostgroup::fetchMembers()');
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Fetches the state of the hostgroup and all members. It also fetches the
	 * summary output
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchState() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHostgroup::fetchState()');
		
		// Get states of all members
		foreach($this->members AS $OBJ) {
			$OBJ->fetchState();
		}
		
		// Also get summary state
		$this->fetchSummaryState();
		
		// At least summary output
		$this->fetchSummaryOutput();
		$this->state = $this->summary_state;
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHostgroup::fetchState()');
	}
	
	/**
	 * PUBLIC getNumMembers()
	 *
	 * Returns the number of member objects
	 *
	 * @return	Integer		Number of members
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getNumMembers() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHostgroup::getNumMembers()');
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHostgroup::getNumMembers()');
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
	function getMembers() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHostgroup::getMembers()');
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHostgroup::getMembers()');
		return $this->members;
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE fetchMemberHostObjects)
	 *
	 * Gets all members of the given hostgroup and saves them to the members array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMemberHostObjects() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHostgroup::fetchMemberHostObjects()');
		// Get all hosts and states
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$arrHosts = $this->BACKEND->BACKENDS[$this->backend_id]->getHostsByHostgroupName($this->hostgroup_name);
			if(count($arrHosts) > 0) {
				foreach($arrHosts AS $hostName) {
					$OBJ = new NagVisHost($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $hostName);
					
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
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHostgroup::fetchMemberHostObjects()');
	}
	
	/**
	 * PRIVATE fetchSummaryState()
	 *
	 * Fetches the summary state from all members
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryState() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHostgroup::fetchSummaryState()');
		if($this->getNumMembers() > 0) {
			// Get summary state member objects
			foreach($this->members AS $MEMBER) {
				$this->wrapChildState($MEMBER);
			}
		} else {
			$this->summary_state = 'ERROR';
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHostgroup::fetchSummaryState()');
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output from all members
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryOutput() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHostgroup::fetchSummaryOutput()');
		if($this->getNumMembers() > 0) {
			$arrStates = Array('CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
			
			// Get summary state of this and child objects
			foreach($this->members AS $MEMBER) {
				$arrStates[$MEMBER->getSummaryState()]++;
			}
			
			parent::fetchSummaryOutput($arrStates, $this->LANG->getLabel('hosts'));
		} else {
			$this->summary_output = $this->LANG->getMessageText('hostGroupNotFoundInDB','HOSTGROUP~'.$this->hostgroup_name);
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHostgroup::fetchSummaryOutput()');
	}
}
?>
