<?php
/**
 * Class of a Host in Nagios with all necessary informations
 */
class NagiosHost extends NagVisStatefulObject {
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	var $backend_id;
	
	var $host_name;
	var $alias;
	var $display_name;
	var $address;
	
	var $state;
	var $output;
	var $problem_has_been_acknowledged;
	
	var $summary_state;
	var $summary_output;
	var $summary_problem_has_been_acknowledged;
	
	var $childObjects;
	var $services;
	
	function NagiosHost(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostName) {
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->backend_id = $backend_id;
		$this->host_name = $hostName;
		
		$this->childObjects = Array();
		$this->services = Array();
		$this->state = '';
		$this->has_been_acknowledged = 0;
		
		//FIXME: $this->getInformationsFromBackend();
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
	}
	
	function fetchState() {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$arrValues = $this->BACKEND->BACKENDS[$this->backend_id]->checkStates($this->type,$this->host_name,$this->recognize_services, '',$this->only_hard_states);
			
			// Append contents of the array to the object properties
			// Bad: this method is not meant for this, but it works
			$this->setConfiguration($arrValues);
			
			// Get also services and states
			$this->fetchServiceObjects();
			
			// Also get summary state
			$this->fetchSummaryState();
			
			// At least summary output
			$this->fetchSummaryOutput();
		}
	}
	
	function fetchChilds($maxLayers=-1) {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$this->fetchDirectChildObjects();
			
			/**
			 * If maxLayers is not set there is no layer limitation
			 */
			if($maxLayers < 0 || $maxLayers > 0) {
				foreach($this->childObjects AS $OBJ) {
					$OBJ->fetchChilds($maxLayers-1);
				}
			}
		}
	}
	
	# End public methods
	# #########################################################################
	
	function fetchServiceObjects() {
		// Get all services and states
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			foreach($this->BACKEND->BACKENDS[$this->backend_id]->getServicesByHostName($this->host_name) As $serviceDescription) {			
				$OBJ = new NagVisService($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $this->host_name, $serviceDescription);
				$OBJ->fetchState();
				$this->services[] = $OBJ;
			}
		}
	}
	
	function fetchDirectChildObjects() {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			foreach($this->BACKEND->BACKENDS[$this->backend_id]->getDirectChildNamesByHostName($this->host_name) AS $childName) {
				$OBJ = new NagVisHost($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $childName);
				$OBJ->fetchState();
				$OBJ->fetchIcon();
				$this->childObjects[] = $OBJ;
			}
		}
	}
	
	function getNumChilds() {
		return count($this->childObjects);
	}
	
	function getChilds() {
		return $this->childObjects;
	}
	
	function fetchSummaryState() {
		$arrStates = Array();
		
		// Get Host state
		$this->summary_state = $this->state;
		$this->summary_problem_has_been_acknowledged = $this->problem_has_been_acknowledged;
		
		// Get states of services and merge with host state
		foreach($this->services AS $SERVICE) {
			$this->wrapChildState($SERVICE);
		}
	}
	
	function fetchSummaryOutput() {
		$arrStates = Array('CRITICAL'=>0,'DOWN'=>0,'WARNING'=>0,'UNKNOWN'=>0,'UP'=>0,'OK'=>0,'ERROR'=>0,'ACK'=>0,'PENDING'=>0);
		$output = '';
		
		foreach($this->services AS $SERVICE) {
			$arrStates[$SERVICE->getSummaryState()]++;
		}
		
		// FIXME: LANGUAGE
		$this->summary_output = 'Host is '.$this->state.'. There are ';
		foreach($arrStates AS $state => $num) {
			if($num > 0) {
				$this->summary_output .= $num.' '.$state.', ';
			}
		}
		$this->summary_output .= ' services.';
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
