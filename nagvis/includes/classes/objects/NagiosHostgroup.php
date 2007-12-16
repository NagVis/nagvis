<?php
/**
 * Class of a Host in Nagios with all necessary informations
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
	
	function NagiosHostgroup(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostgroupName) {
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->backend_id = $backend_id;
		$this->hostgroup_name = $hostgroupName;
		
		$this->members = Array();
		$this->state = '';
		
		//FIXME: $this->getInformationsFromBackend();
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
	}
	
	function fetchState() {
		// Get all Members and states
		$this->fetchMemberHostObjects();
		
		// Also get summary state
		$this->fetchSummaryState();
		
		// At least summary output
		$this->fetchSummaryOutput();
		$this->state = $this->summary_state;
	}
	
	# End public methods
	# #########################################################################
	
	function fetchMemberHostObjects() {
		// Get all hosts and states
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			foreach($this->BACKEND->BACKENDS[$this->backend_id]->getHostsByHostgroupName($this->hostgroup_name) AS $hostName) {			
				$OBJ = new NagVisHost($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $hostName);
				$OBJ->fetchState();
				
				$this->members[] = $OBJ;
			}
		}
	}
	
	function getNumMembers() {
		return count($this->members);
	}
	
	function fetchSummaryState() {
		// Get summary state member objects
		foreach($this->members AS $MEMBER) {
			$this->wrapChildState($MEMBER);
		}
	}
	
	function fetchSummaryOutput() {
		$arrStates = Array('CRITICAL'=>0,'DOWN'=>0,'WARNING'=>0,'UNKNOWN'=>0,'UP'=>0,'OK'=>0,'ERROR'=>0,'ACK'=>0,'PENDING'=>0);
		$output = '';
		
		// FIXME: Get summary state of this and child objects
		foreach($this->members AS $MEMBER) {
			$arrStates[$MEMBER->getSummaryState()]++;
		}
		
		// FIXME: LANGUAGE
		$this->summary_output = 'There are '.($arrStates['DOWN']+$arrStates['CRITICAL']).' DOWN/CRTICAL, '.$arrStates['WARNING'].' WARNING, '.$arrStates['UNKNOWN'].' UNKNOWN and '.($arrStates['UP']+$arrStates['OK']).' UP/OK hosts';
	}
	
	function fetchInformationsFromBackend() {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$arrValues = $this->BACKEND->BACKENDS[$this->backend_id]->getHostBasicInformations($this->host_name);
			
			$this->alias = $arrValues['alias'];
			$this->display_name = $arrValues['display_name'];
			$this->address = $arrValues['address'];
		}
	}
}
?>
