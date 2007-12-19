<?php
/**
 * Class of a Servicegroups in Nagios with all necessary informations
 */
class NagiosServicegroup extends NagVisStatefulObject {
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	var $backend_id;
	
	var $servicegroup_name;
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
	 * @param		String		Name of the servicegroup
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagiosServicegroup(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $servicegroupName) {
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->backend_id = $backend_id;
		$this->servicegroup_name = $servicegroupName;
		
		$this->members = Array();
		$this->state = '';
		
		//FIXME: $this->getInformationsFromBackend();
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Fetches the state of the servicegroup and all members. It also fetches the
	 * summary output
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchState() {
		// Get all Members and states
		$this->fetchMemberServiceObjects();
		
		// Also get summary state
		$this->fetchSummaryState();
		
		// At least summary output
		$this->fetchSummaryOutput();
		$this->state = $this->summary_state;
	}
	
	# End public methods
	# #########################################################################
	
	function fetchMemberServiceObjects() {
		// Get all services and states
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$arrServices = $this->BACKEND->BACKENDS[$this->backend_id]->getServicesByServicegroupName($this->servicegroup_name);
			if(count($arrServices) > 0) {
				foreach($arrServices AS $service) {
					$OBJ = new NagVisService($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $service['host_name'], $service['service_description']);
					$OBJ->fetchState();
					
					$this->members[] = $OBJ;
				}
			} else {
				$this->summary_state = 'ERROR';
				$this->summary_output = $this->LANG->getMessageText('serviceGroupNotFoundInDB','SERVICEGROUP~'.$this->servicegroup_name);
			}
		}
	}
	
	function getNumMembers() {
		return count($this->members);
	}
	
	function fetchSummaryState() {
		if(count($this->members) > 0) {
			// Get summary state member objects
			foreach($this->members AS $MEMBER) {
				$this->wrapChildState($MEMBER);
			}
		}
	}
	
	function fetchSummaryOutput() {
		if(count($this->members) > 0) {
			$arrStates = Array('CRITICAL'=>0,'DOWN'=>0,'WARNING'=>0,'UNKNOWN'=>0,'UP'=>0,'OK'=>0,'ERROR'=>0,'ACK'=>0,'PENDING'=>0);
			$output = '';
			
			// FIXME: Get summary state of this and child objects
			foreach($this->members AS $MEMBER) {
				$arrStates[$MEMBER->getSummaryState()]++;
			}
			
			// FIXME: LANGUAGE
			$this->summary_output = 'There are '.($arrStates['DOWN']+$arrStates['CRITICAL']).' DOWN/CRTICAL, '.$arrStates['WARNING'].' WARNING, '.$arrStates['UNKNOWN'].' UNKNOWN and '.($arrStates['UP']+$arrStates['OK']).' UP/OK services';
		}
	}
	
	# END Public Methods
	# #####################################################
	
	function fetchInformationsFromBackend() {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$arrValues = $this->BACKEND->BACKENDS[$this->backend_id]->getServicegroupBasicInformations($this->servicegroup_name);
			
			$this->alias = $arrValues['alias'];
			$this->display_name = $arrValues['display_name'];
		}
	}
}
?>
