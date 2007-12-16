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
	
	function NagiosService(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostName, $serviceDescription) {
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
	}
	
	function fetchState() {
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
	}
	
	# End public methods
	# #########################################################################
	
	function fetchSummaryState() {
		$this->summary_state = $this->state;
		$this->summary_problem_has_been_acknowledged = $this->problem_has_been_acknowledged;
	}
	
	function fetchSummaryOutput() {
		$this->summary_output = $this->output;
	}
	
	function getInformationsFromBackend() {
		if($this->BACKEND->checkBackendInitialized($this->backendId, TRUE)) {
			$arrValues = $this->BACKEND->BACKENDS[$this->backendId]->getHostBasicInformations($this->host_name);
			
			$this->alias = $arrValues['alias'];
			$this->display_name = $arrValues['display_name'];
			$this->address = $arrValues['address'];
		}
	}
}
?>
