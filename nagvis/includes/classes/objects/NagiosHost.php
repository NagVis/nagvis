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
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 	ID of queried backend
	 * @param		String		Name of the host
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagiosHost(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostName) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHost::NagiosHost(MAINCFG,BACKEND,LANG,'.$backend_id.','.$hostName.')');
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
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHost::NagiosHost()');
	}
	
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Gets the state of the host and all its services from selected backend. It
	 * forms the summary output
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchState() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHost::fetchState()');
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
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHost::fetchState()');
	}
	
	/**
	 * PUBLIC fetchChilds()
	 *
	 * Gets all child objects of this host from the backend. The child objects are
	 * saved to the childObjects array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchChilds($maxLayers=-1, &$objConf=Array(), $ignoreHosts=Array()) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHost::fetchChilds()');
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$this->fetchDirectChildObjects($objConf, $ignoreHosts);
			
			/**
			 * If maxLayers is not set there is no layer limitation
			 */
			if($maxLayers < 0 || $maxLayers > 0) {
				foreach($this->childObjects AS $OBJ) {
					$OBJ->fetchChilds($maxLayers-1, $objConf, $ignoreHosts);
				}
			}
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHost::fetchChilds()');
	}
	
	/**
	 * PRIVATE getChilds()
	 *
	 * Returns all child objects in childObjects array 
	 *
	 * @return	Array		Array of host objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getChilds() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHost::getChilds()');
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHost::getChilds()');
		return $this->childObjects;
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE fetchServiceObjects()
	 *
	 * Gets all services of the given host and saves them to the services array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchServiceObjects() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHost::fetchServiceObjects()');
		// Get all services and states
		foreach($this->BACKEND->BACKENDS[$this->backend_id]->getServicesByHostName($this->host_name) As $serviceDescription) {			
			$OBJ = new NagVisService($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $this->host_name, $serviceDescription);
			$OBJ->fetchState();
			$this->services[] = $OBJ;
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHost::fetchServiceObjects()');
	}
	
	/**
	 * PRIVATE fetchDirectChildObjects()
	 *
	 * Gets all child objects of the given host and saves them to the childObjects
	 * array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchDirectChildObjects(&$objConf, &$ignoreHosts=Array()) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHost::fetchDirectChildObjects(&Array(), &Array())');
		foreach($this->BACKEND->BACKENDS[$this->backend_id]->getDirectChildNamesByHostName($this->host_name) AS $childName) {
			if(DEBUG&&DEBUGLEVEL&2) debug('Start Loop Host');
			// If the host is in ignoreHosts, don't recognize it
			if(count($ignoreHosts) == 0 || !in_array($childName, $ignoreHosts)) {
				$OBJ = new NagVisHost($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $childName);
				$OBJ->fetchState();
				$OBJ->fetchIcon();
				$OBJ->setConfiguration($objConf);
				$this->childObjects[] = $OBJ;
			}
			if(DEBUG&&DEBUGLEVEL&2) debug('Stop Loop Host');
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHost::fetchDirectChildObjects()');
	}
	
	/**
	 * PRIVATE fetchSummaryState()
	 *
	 * Fetches the summary state from all services
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryState() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHost::fetchSummaryState()');
		$arrStates = Array();
		
		// Get Host state
		$this->summary_state = $this->state;
		$this->summary_problem_has_been_acknowledged = $this->problem_has_been_acknowledged;
		
		// Get states of services and merge with host state
		foreach($this->services AS $SERVICE) {
			$this->wrapChildState($SERVICE);
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHost::fetchSummaryState()');
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output from host and all services
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryOutput() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagiosHost::fetchSummaryOutput()');
		$arrStates = Array('CRITICAL'=>0,'DOWN'=>0,'WARNING'=>0,'UNKNOWN'=>0,'UP'=>0,'OK'=>0,'ERROR'=>0,'ACK'=>0,'PENDING'=>0);
		$output = '';
		
		foreach($this->services AS $SERVICE) {
			$arrStates[$SERVICE->getSummaryState()]++;
		}
		
		// FIXME: LANGUAGE
		$this->summary_output = 'Host is '.$this->state.'. There are ';
		if(count($this->services) > 0) {
			foreach($arrStates AS $state => $num) {
				if($num > 0) {
					$this->summary_output .= $num.' '.$state.', ';
				}
			}
		} else {
			$this->summary_output .= '0';
		}
		$this->summary_output .= ' services.';
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagiosHost::fetchSummaryState()');
	}
	
	/* UNNEEDED atm
	function fetchInformationsFromBackend() {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$arrValues = $this->BACKEND->BACKENDS[$this->backend_id]->getHostBasicInformations($this->host_name);
			
			$this->alias = $arrValues['alias'];
			$this->display_name = $arrValues['display_name'];
			$this->address = $arrValues['address'];
		}
	}*/
}
?>
