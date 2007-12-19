<?php
/**
 * Class of a Host in Nagios with all necessary informations
 */
class NagiosService extends NagVisStatefulObject {
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	var $backend_id;
	
	var $host_name;
	var $service_description;
	var $alias;
	var $display_name;
	var $address;
	
	var $state;
	var $output;
	var $problem_has_been_acknowledged;
	
	var $summary_state;
	var $summary_output;
	var $summary_problem_has_been_acknowledged;
	
	var $recognize_services;
	var $only_hard_states;
	
	var $childObjects;
	var $services;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 	ID of queried backend
	 * @param		String		Name of the host
	 * @param		String		Service description
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagiosService(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostName, $serviceDescription) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosService::NagiosService(MAINCFG,BACKEND,LANG,'.$backend_id.','.$hostName.','.$serviceDescription.')');
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->backendId = $backend_id;
		$this->host_name = $hostName;
		$this->service_description = $serviceDescription;
		
		$this->childObjects = Array();
		$this->state = '';
		$this->has_been_acknowledged = 0;
		
		//FIXME: $this->getInformationsFromBackend();
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosService::NagiosService()');
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Fetches the state of the service. Also fetch the summary output
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchState() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosService::fetchState()');
		if($this->BACKEND->checkBackendInitialized($this->backendId, TRUE)) {
			$arrValues = $this->BACKEND->BACKENDS[$this->backendId]->checkStates($this->type,$this->host_name,$this->recognize_services, $this->service_description,$this->only_hard_states);
			
			// Append contents of the array to the object properties
			// Bad: this method is not meant for this, but it works
			$this->setConfiguration($arrValues);
			
			// Also get summary state
			$this->fetchSummaryState();
			
			// At least summary output
			$this->fetchSummaryOutput();
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosService::fetchState()');
	}
	
	# End public methods
	# #########################################################################
	
	function fetchSummaryState() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosService::fetchSummaryState()');
		$this->summary_state = $this->state;
		$this->summary_problem_has_been_acknowledged = $this->problem_has_been_acknowledged;
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosService::fetchSummaryState()');
	}
	
	function fetchSummaryOutput() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosService::fetchSummaryOutput()');
		$this->summary_output = $this->output;
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosService::fetchSummaryOutput()');
	}
	
	/* UNNEEDED atm
	function getInformationsFromBackend() {
		if($this->BACKEND->checkBackendInitialized($this->backendId, TRUE)) {
			$arrValues = $this->BACKEND->BACKENDS[$this->backendId]->getHostBasicInformations($this->host_name);
			
			$this->alias = $arrValues['alias'];
			$this->display_name = $arrValues['display_name'];
			$this->address = $arrValues['address'];
		}
	}*/
}
?>
