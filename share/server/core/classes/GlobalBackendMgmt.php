<?php
/*****************************************************************************
 *
 * GlobalBackendMgmt.php - class for handling all backends
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
class GlobalBackendMgmt {
	protected $CORE;
	public $BACKENDS = Array();
	private $aInitialized = Array();
	private $aQueue = Array();
	
	/**
	 * Constructor
	 *
	 * Initializes all backends
	 *
	 * @param   config  $MAINCFG
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->loadBackends();
		
		return 0;
	}

	/**
	 * PUBLIC queue()
	 *
	 * Add a backend query to the queue
	 *
	 * @param   String  Query ID to add to the queue
	 * @param   Object  Map object to fetch the informations for
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function queue($query, $OBJ) {
		if(!is_array($query)) {
			$query = Array($query => true);
		}
		
		$backendId = $OBJ->getBackendId();
		if(!isset($this->aQueue[$backendId]))
			$this->aQueue[$backendId] = Array();
		
		foreach($query AS $query => $_unused) {
			if(!isset($this->aQueue[$backendId][$query]))
				$this->aQueue[$backendId][$query] = Array();
			
			// Gather the object name
			if($query == 'serviceState')
				$name = $OBJ->getName().'~~'.$OBJ->getServiceDescription();
			else
				$name = $OBJ->getName();
			
			// Options is a mask which tells the backend how to handle this object
			$options = $this->parseOptions($OBJ);
			
			// Only query the backend once per object
			// If the object is several times queued add it to the object list
			if(!isset($this->aQueue[$backendId][$query][$name])) {
				$this->aQueue[$backendId][$query][$name] = Array('OBJS' => Array($OBJ), 'options' => $options);
			} else
				$this->aQueue[$backendId][$query][$name]['OBJS'][] = $OBJ;
		}
	}

	private function parseOptions($OBJ) {
		$options = 0;
		if($OBJ->getOnlyHardStates())
			$options |= 1;
		/*FIXME: Implement as optional filter: "Filter: in_notification_period = 1\n" .*/

		return $options;
	}

	/**
	 * PUBLIC clearQueue()
	 *
	 * Resets the backend queue
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function clearQueue() {
		$this->aQueue = Array();
	}

	/**
	 * PUBLIC execute()
	 *
	 * Executes all backend queries and assigns the gathered information
	 * to the objects
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function execute() {
		// Loop all backends
		foreach($this->aQueue AS $backendId => $types) {
			// First check if the backend is initialized and maybe initiale it
			$this->checkBackendInitialized($backendId, true);

			// Loop all different query types
			foreach($types AS $type => $aObjs) {
				switch($type) {
					case 'serviceState':
					case 'hostState':
					case 'hostMemberState':
					case 'hostgroupMemberState':
					case 'servicegroupMemberState':
						$this->fetchStateCounts($backendId, $type, $aObjs);
					break;
					case 'hostMemberDetails':
						$this->fetchHostMemberDetails($backendId, $aObjs);
					break;
					case 'hostgroupMemberDetails':
						$this->fetchHostgroupMemberDetails($backendId, $aObjs);
					break;
					case 'servicegroupMemberDetails':
						$this->fetchServicegroupMemberDetails($backendId, $aObjs);
					break;
				}
			}
		}

		// Clear the queue after processing
		$this->clearQueue();
	}
	
	/**
	 * PRIVATE fetchServicegroupMemberDetails()
	 *
	 * Loops all queued servicegroups and executes the queries for each group.
	 * Gets all services of the servicegroup and saves them to the members array
	 *
	 * This is trimmed to reduce the number of queries to the backend:
	 * 1.) fetch states for all services
	 * 2.) fetch state counts for all services
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchServicegroupMemberDetails($backendId, $aObjs) {
		foreach($aObjs AS $name => $opts)
			foreach($opts['OBJS'] AS $OBJ) {
				// Fist get the host states for all the hostgroup members
				try {
					$filters = Array(Array('key' => 'service_groups', 'op' => '>=', 'val' => 'name'));
					$aServices = $this->BACKENDS[$backendId]->getServiceState(
																								Array($OBJ->getName() => 
																									Array('OBJS' => Array($OBJ),
																												'options' => $this->parseOptions($OBJ)
																								)), $filters);
				} catch(BackendException $e) {
					$OBJ->setBackendProblem($this->CORE->getLang()->getText('Connection Problem (Backend: [BACKENDID]): [MSG]',
				  																						Array('BACKENDID' => $backendId, 'MSG' => $e->getMessage())));
				}
		
				// Regular member adding loop
				foreach($aServices AS $host => $serviceList) {
					$members = Array();
					foreach($serviceList AS $aService) {
						$SOBJ = new NagVisService($this->CORE, $this, $backendId, $host, $aService['service_description']);
						
						// Append contents of the array to the object properties
						$SOBJ->setObjectInformation($aService);
						
						// Also get summary state
						$aService['summary_state'] = $aService['state'];
						$aService['summary_output'] = $aService['output'];
						
						// The services have to know how they should handle hard/soft 
						// states. This is a little dirty but the simplest way to do this
						// until the hard/soft state handling has moved from backend to the
						// object classes.
						$SOBJ->setConfiguration($OBJ->getObjectConfiguration());
						
						// Add child object to the members array
						$members[] = $SOBJ;
					}
					$OBJ->setMembers($members);
				}
			}
	}
	
	/**
	 * PRIVATE fetchHostgroupMemberDetails()
	 *
	 * Loops all queued objects.
	 * Gets all hosts of the hostgroup and saves them to the members array
	 *
	 * This is trimmed to reduce the number of queries to the backend:
	 * 1.) fetch states for all hosts
	 * 2.) fetch state counts for all hosts
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchHostgroupMemberDetails($backendId, $aObjs) {
		// And then apply them to the objects
		foreach($aObjs AS $name => $opts)
			foreach($opts['OBJS'] AS $OBJ) {
				// First get the host states for all the hostgroup members
				try {
					$filters = Array(Array('key' => 'host_groups', 'op' => '>=', 'val' => 'name'));
					$aHosts = $this->BACKENDS[$backendId]->getHostState(
																						Array($OBJ->getName() => 
																							Array('OBJS' => Array($OBJ),
																										'options' => $this->parseOptions($OBJ)
																						)), $filters);
				} catch(BackendException $e) {
					$OBJ->setBackendProblem($this->CORE->getLang()->getText('Connection Problem (Backend: [BACKENDID]): [MSG]',
				  																						Array('BACKENDID' => $backendId, 'MSG' => $e->getMessage())));
				}
				
				// Now fetch the service state counts for all hostgroup members
				try {
					$filters = Array(Array('key' => 'host_groups', 'op' => '>=', 'val' => 'name'));
					$aServiceStateCounts = $this->BACKENDS[$backendId]->getHostStateCounts(
																								Array($OBJ->getName() => 
																									Array('OBJS' => Array($OBJ),
																												'options' => $this->parseOptions($OBJ)
																								)), $filters);
				} catch(BackendException $e) {
					$aServiceStateCounts = Array();
				}
				
				$members = Array();
				foreach($aHosts AS $name => $aHost) {
					$HOBJ = new NagVisHost($this->CORE, $this, $backendId, $name);
					
					// Append contents of the array to the object properties
					$HOBJ->setObjectInformation($aHost);
					
					// The services have to know how they should handle hard/soft 
					// states. This is a little dirty but the simplest way to do this
					// until the hard/soft state handling has moved from backend to the
					// object classes.
					$HOBJ->setConfiguration($OBJ->getObjectConfiguration());
					
					// Put state counts to the object
					if(isset($aServiceStateCounts[$name])) {
						$HOBJ->setStateCounts($aServiceStateCounts[$name]);
					}
					
					// Fetch summary state and output
					$HOBJ->fetchSummariesFromCounts();
					
					$members[] = $HOBJ;
				}

				$OBJ->setMembers($members);
			}
	
	}
	
	private function fetchStateCounts($backendId, $type, $aObjs) {
		switch($type) {
			case 'servicegroupMemberState':
				$filters = Array(Array('key' => 'groups', 'op' => '>=', 'val' => 'name'));
				$aCounts = $this->BACKENDS[$backendId]->getServicegroupStateCounts($aObjs, $filters);
			break;
			case 'hostgroupMemberState':
				$filters = Array(Array('key' => 'groups', 'op' => '>=', 'val' => 'name'));
				$aCounts = $this->BACKENDS[$backendId]->getHostgroupStateCounts($aObjs, $filters);
			break;
			case 'serviceState':
				$filters = Array(
										Array('key' => 'host_name', 'op' => '=', 'val' => 'name'),
										Array('key' => 'service_description', 'op' => '=', 'service_description')
									);
				$aCounts = $this->BACKENDS[$backendId]->getServiceState($aObjs, $filters);
			break;
			case 'hostState':
				$filters = Array(Array('key' => 'host_name', 'op' => '=', 'val' => 'name'));
				$aCounts = $this->BACKENDS[$backendId]->getHostState($aObjs, $filters);
			break;
			case 'hostMemberState':
				$filters = Array(Array('key' => 'host_name', 'op' => '=', 'val' => 'name'));
				$aCounts = $this->BACKENDS[$backendId]->getHostStateCounts($aObjs, $filters);
			break;
		}

		foreach($aObjs AS $name => $opts)
			if(isset($aCounts[$name]))
				foreach($opts['OBJS'] AS $OBJ)
					if($type == 'serviceState' || $type == 'hostState')
						$OBJ->setState($aCounts[$name]);
					else
						$OBJ->setStateCounts($aCounts[$name]);
			else
				foreach($opts['OBJS'] AS $OBJ) {
					$OBJ->setBackendProblem($this->CORE->getLang()->getText('The object "[OBJ]" does not exist ([TYPE]).',
			         		                                                             Array('OBJ' => $name, 'TYPE' => $type)));
				}
	}

	private function fetchHostMemberDetails($backendId, $aObjs) {
		try {
			$filters = Array(Array('key' => 'host_name', 'op' => '=', 'val' => 'name'));
			$aMembers = $this->BACKENDS[$backendId]->getServiceState($aObjs, $filters);
		} catch(BackendException $e) {
			$aMembers = Array();
		}

		foreach($aObjs AS $name => $opts) {
			if(isset($aMembers[$name])) {
				foreach($opts['OBJS'] AS $OBJ) {
					$members = Array();
					foreach($aMembers[$name] AS $service => $details) {
						$MOBJ = new NagVisService($this->CORE, $this, $backendId, $OBJ->getName(), $details['service_description']);
						$MOBJ->setState($details);
						$members[] = $MOBJ;
					}
				
					$OBJ->setMembers($members);
				}
			} else {
				foreach($opts['OBJS'] AS $OBJ)
					$OBJ->setBackendProblem($this->CORE->getLang()->getText('The object "[OBJ]" has no services.',
		                                                                     Array('OBJ' => $OBJ->getName())));
			}
		}
	}
	
	/**
	 * Loads all backends and prints an error when no backend defined
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function loadBackends() {
		$aBackends = $this->CORE->getDefinedBackends();
		
		if(!count($aBackends)) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('noBackendDefined'));
		}
	}
	
	/**
	 * Checks for existing backend file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkBackendExists($backendId, $printErr) {
		if(isset($backendId) && $backendId != '') {
			if(file_exists($this->CORE->getMainCfg()->getValue('paths','class').'GlobalBackend'.$this->CORE->getMainCfg()->getValue('backend_'.$backendId,'backendtype').'.php')) {
				return TRUE;
			} else {
				if($printErr == 1) {
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('backendNotExists','BACKENDID~'.$backendId.',BACKENDTYPE~'.$this->CORE->getMainCfg()->getValue('backend_'.$backendId,'backendtype')));
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Initializes a backend
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function initializeBackend($backendId) {
		if($this->checkBackendExists($backendId, 1)) {
			$backendClass = 'GlobalBackend'.$this->CORE->getMainCfg()->getValue('backend_'.$backendId,'backendtype');
			$this->BACKENDS[$backendId] = new $backendClass($this->CORE,$backendId);
			
			// Mark backend as initialized
			$this->aInitialized[$backendId] = true;
			
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Checks for an initialized backend
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkBackendInitialized($backendId, $printErr) {
		if(isset($this->aInitialized[$backendId])) {
			return true;
		} else {
			// Try to initialize backend
			if($this->initializeBackend($backendId)) {
				return true;
			} else {
				if($printErr == 1) {
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('backendNotInitialized','BACKENDID~'.$backendId.',BACKENDTYPE~'.$this->CORE->getMainCfg()->getValue('backend_'.$backendId,'backendtype')));
				}
				return false;
			}
		}
	}
	
	/**
	 * Checks if the given feature is provided by the given backend
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkBackendFeature($backendId, $feature, $printErr = 1) {
		$backendClass = 'GlobalBackend'.$this->CORE->getMainCfg()->getValue('backend_'.$backendId,'backendtype');
		if(method_exists($backendClass, $feature)) {
			return true;
		} else {
			if($printErr == 1) {
				new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The requested feature [FEATURE] is not provided by the backend (Backend-ID: [BACKENDID], Backend-Type: [BACKENDTYPE]). The requested view may not be available using this backend.', Array('FEATURE' => htmlentities($feature), 'BACKENDID' => $backendId, 'BACKENDTYPE' => $this->CORE->getMainCfg()->getValue('backend_'.$backendId,'backendtype'))));
			}
			return false;
		}
	}
}
?>
