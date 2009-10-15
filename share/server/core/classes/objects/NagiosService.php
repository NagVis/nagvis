<?php
/*****************************************************************************
 *
 * NagiosService.php - Class of a Service in Nagios with all necessary 
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
class NagiosService extends NagVisStatefulObject {
	protected $host_name;
	protected $service_description;
	protected $alias;
	protected $display_name;
	protected $address;
	protected $notes;
	
	protected $perfdata;
	protected $last_check;
	protected $next_check;
	protected $state_type;
	protected $current_check_attempt;
	protected $max_check_attempts;
	protected $last_state_change;
	protected $last_hard_state_change;
	
	protected $in_downtime;
	protected $downtime_start;
	protected $downtime_end;
	protected $downtime_author;
	protected $downtime_data;
	
	protected $childObjects;
	protected $services;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 		ID of queried backend
	 * @param		String		Name of the host
	 * @param		String		Service description
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function __construct($CORE, $BACKEND, $backend_id, $hostName, $serviceDescription) {
		$this->backend_id = $backend_id;
		$this->host_name = $hostName;
		$this->service_description = $serviceDescription;
		
		$this->childObjects = Array();
		
		$this->state = '';
		$this->problem_has_been_acknowledged = 0;
		$this->in_downtime = 0;
		
		$this->summary_state = '';
		$this->summary_problem_has_been_acknowledged = 0;
		$this->summary_in_downtime = 0;
		
		parent::__construct($CORE, $BACKEND);
	}
	
	/**
	 * PUBLIC fetchMembers()
	 *
	 * Just a dummy here
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMembers() {
	}
	
	/**
	 * PUBLIC getNumMembers()
	 *
	 * Just a dummy here
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getNumMembers() {
		 return 0;
	}
	
	/**
	 * PUBLIC fetchState()
	 *
	 * Fetches the state of the service. Also fetch the summary output
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchState() {
		if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
			$arrValues = $this->BACKEND->BACKENDS[$this->backend_id]->getServiceState($this->host_name, $this->service_description, $this->only_hard_states);
			
			// Append contents of the array to the object properties
			$this->setObjectInformation($arrValues);
			
			// Also get summary state
			$this->fetchSummaryState();
			
			// At least summary output
			$this->fetchSummaryOutput();
		}
	}
	
	/**
	 * PUBLIC getServiceDescription()
	 *
	 * Returns the service description
	 *
	 * @return	String		Service description
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getServiceDescription() {
		return $this->service_description;
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE fetchSummaryState()
	 *
	 * Is just a dummy here, this sets the state of the service as summary state
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryState() {
		$this->summary_state = $this->state;
		$this->summary_problem_has_been_acknowledged = $this->problem_has_been_acknowledged;
		$this->summary_in_downtime = $this->in_downtime;
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Is just a dummy here, this sets the output of the service as summary output
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryOutput() {
		$this->summary_output = $this->output;
	}
}
?>
