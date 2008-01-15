<?php
/**
 * Class of a Host in Nagios with all necessary informations
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
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
	var $last_check;
	var $next_check;
	var $state_type;
	var $current_check_attempt;
	var $max_check_attempts;
	var $last_state_change;
	var $last_hard_state_change;
	
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
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->backend_id = $backend_id;
		$this->host_name = $hostName;
		
		$this->childObjects = Array();
		$this->services = Array();
		$this->state = '';
		$this->has_been_acknowledged = 0;
		
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
	}
	
	/**
	 * PUBLIC fetchMembers()
	 *
	 * Gets all member objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMembers() {
		// Get all service objects
		$this->fetchServiceObjects();
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
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$arrValues = $this->BACKEND->BACKENDS[$this->backend_id]->getHostState($this->host_name, $this->only_hard_states);
			
			// Append contents of the array to the object properties
			$this->setObjectInformation($arrValues);
			
			// Get all service states
			if($this->getNumServices() == 0) {
				$this->fetchServiceObjects();
			}
			
			// DEP: Old method, getting every single service state/informations
			/*foreach($this->services AS $OBJ) {
				$OBJ->fetchState();
			}*/
			
			// Also get summary state
			$this->fetchSummaryState();
			
			// At least summary output
			$this->fetchSummaryOutput();
		}
	}
	
	/**
	 * PUBLIC fetchChilds()
	 *
	 * Gets all child objects of this host from the backend. The child objects are
	 * saved to the childObjects array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchChilds($maxLayers=-1, &$objConf=Array(), &$ignoreHosts=Array(), &$arrHostnames) {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$this->fetchDirectChildObjects($objConf, $ignoreHosts, $arrHostnames);
			
			/**
			 * If maxLayers is not set there is no layer limitation
			 */
			if($maxLayers < 0 || $maxLayers > 0) {
				foreach($this->childObjects AS $OBJ) {
					/*
					 * Check if the host is already on the map (If it's not done, the 
					 * objects with more than one parent be printed several times on the 
					 * map, especially the links to child objects will be too many.
					 */
					if(!in_array($OBJ->getName(), $arrHostnames)){
						$OBJ->fetchChilds($maxLayers-1, $objConf, $ignoreHosts, $arrHostnames);
						
						// Add the name of this host to the array with hostnames which are
						// already on the map
						$arrHostnames[] = $OBJ->getName();
					}
				}
			}
		}
	}
	
	/**
	 * PUBLIC getChilds()
	 *
	 * Returns all child objects in childObjects array 
	 *
	 * @return	Array		Array of host objects
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getChilds() {
		return $this->childObjects;
	}
	
	/**
	 * PUBLIC getNumServices()
	 *
	 * Returns the number of services
	 *
	 * @return	Integer		Number of services
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getNumServices() {
		return count($this->services);
	}
	
	/**
	 * PUBLIC getServices()
	 *
	 * Returns the number of services
	 *
	 * @return	Array		Array of Services
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getServices() {
		return $this->services;
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
		foreach($this->BACKEND->BACKENDS[$this->backend_id]->getServiceState($this->host_name, '', $this->only_hard_states) AS $arrService) {
			$OBJ = new NagVisService($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $this->host_name, $arrService['service_description']);
			
			// Append contents of the array to the object properties
			$OBJ->setObjectInformation($arrService);
			
			// The service of this host has to know how he should handle 
			//hard/soft states. This is a little dirty but the simplest way to do this
			//until the hard/soft state handling has moved from backend to the object
			// classes.
			$OBJ->setConfiguration($this->getObjectConfiguration());
			
			// Also get summary state
			$OBJ->fetchSummaryState();
			
			// At least summary output
			$OBJ->fetchSummaryOutput();
			
			$this->services[] = $OBJ;
		}
		
		// Old method, getting every single method
		/*foreach($this->BACKEND->BACKENDS[$this->backend_id]->getServicesByHostName($this->host_name) As $serviceDescription) {			
			$OBJ = new NagVisService($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $this->getName(), $serviceDescription);
			// FIXME: The service of this host has to know how he should handle 
			//hard/soft states. This is a little dirty but the simplest way to do this
			//until the hard/soft state handling has moved from backend to the object
			// classes.
			$objConf = Array('only_hard_states' => $this->getOnlyHardStates());
			$OBJ->setConfiguration($objConf);
			
			// Add service object to the service array
			$this->services[] = $OBJ;
		}*/
	}
	
	/**
	 * PRIVATE fetchDirectChildObjects()
	 *
	 * Gets all child objects of the given host and saves them to the childObjects
	 * array
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchDirectChildObjects(&$objConf, &$ignoreHosts=Array(), &$arrHostnames) {
		foreach($this->BACKEND->BACKENDS[$this->backend_id]->getDirectChildNamesByHostName($this->getName()) AS $childName) {
			// If the host is in ignoreHosts, don't recognize it
			if(count($ignoreHosts) == 0 || !in_array($childName, $ignoreHosts)) {
				$OBJ = new NagVisHost($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $childName);
				$OBJ->fetchMembers();
				$OBJ->fetchState();
				$OBJ->fetchIcon();
				$OBJ->setConfiguration($objConf);
				
				// Append the host object to the childObjects array
				$this->childObjects[] = $OBJ;
			}
		}
	}
	
	/**
	 * PRIVATE fetchSummaryState()
	 *
	 * Fetches the summary state from all services
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryState() {
		$arrStates = Array();
		
		// Get Host state
		$this->summary_state = $this->state;
		$this->summary_problem_has_been_acknowledged = $this->problem_has_been_acknowledged;
		
		// Only merge host state with service state when recognize_services is set 
		// to 1
		if($this->getRecognizeServices()) {
			// Get states of services and merge with host state
			foreach($this->services AS $SERVICE) {
				$this->wrapChildState($SERVICE);
			}
		}
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output from host and all services
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryOutput() {
		
		// Write host state
		$this->summary_output = $this->LANG->getLabel('hostStateIs').' '.$this->state.'. ';
		
		// Only merge host state with service state when recognize_services is set 
		// to 1
		if($this->getRecognizeServices()) {
			// If there are services write the summary state for them
			if($this->getNumServices() > 0) {
				$arrStates = Array('CRITICAL' => 0,'DOWN' => 0,'WARNING' => 0,'UNKNOWN' => 0,'UP' => 0,'OK' => 0,'ERROR' => 0,'ACK' => 0,'PENDING' => 0);
				
				foreach($this->services AS $SERVICE) {
					$arrStates[$SERVICE->getSummaryState()]++;
				}
				
				parent::fetchSummaryOutput($arrStates, $this->LANG->getLabel('services'));
			} else {
				$this->summary_output .= $this->LANG->getMessageText('hostHasNoServices','HOST~'.$this->getName());
			}
		}
	}
}
?>
