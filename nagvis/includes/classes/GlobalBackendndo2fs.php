<?php
/*****************************************************************************
 *
 * GlobalBackendndo2fs.php - backend class for handling object and state 
 *                            information stored in ndo2fs output.
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

class GlobalBackendndo2fs {
	private $CORE;
	private $backendId;
	private $instanceName;
	
	private $hostCache;
	private $serviceCache;
	private $hostAckCache;
	private $oParentlistCache;
	private $iObjIdCounter;
	
	/**
	 * Constructor
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(&$CORE, $backendId) {
		$this->CORE = &$CORE;
		
		$this->backendId = $backendId;
		
		$this->iObjIdCounter = 0;
		
		$this->hostCache = Array();
		$this->serviceCache = Array();
		$this->hostAckCache = Array();
		
		$this->instanceName = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'instancename');
		$this->path = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'path').'/'.$this->instanceName;
		
		if(!$this->checkFileStructure()) {
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('ndo2fsFileStructureNotValid', 'BACKENDID~'.$this->backendId.',TIMEWITHOUTUPDATE~'.$this->CORE->MAINCFG->getValue('backend_'.$backendId, 'maxtimewithoutupdate')));
		}
		
		if(!$this->checkLastUpdateTime()) {
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('nagiosDataNotUpToDate', 'BACKENDID~'.$this->backendId.',TIMEWITHOUTUPDATE~'.$this->CORE->MAINCFG->getValue('backend_'.$backendId, 'maxtimewithoutupdate')));
		}
		
		return TRUE;
	}
	
	/**
	 * Checks if the file structure looks like valid ndo2fs
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkFileStructure() {
		if(file_exists($this->path.'/PROCESS_STATUS') 
			&& file_exists($this->path.'/PROGRAM_STATUS') 
			&& file_exists($this->path.'/HOSTS')
			&& file_exists($this->path.'/VIEWS')) {
			
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks if the data in process status file is up-to-date (in defined range)
	 *
	 * @return  Boolean
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkLastUpdateTime() {
		$oStatus = json_decode(file_get_contents($this->path.'/PROCESS_STATUS'));
		
		if($_SERVER['REQUEST_TIME'] - $oStatus->LASTCOMMANDCHECK > $this->CORE->MAINCFG->getValue('backend_'.$this->backendId, 'maxtimewithoutupdate')) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * PUBLIC Method getObjects
	 * 
	 * Return the objects configured at Nagios which are matching the given pattern. 
	 * This is needed for WUI, e.g. to populate drop down lists.
	 *
	 * @param	string $type, string $name1Pattern, string $name2Pattern
	 * @return	array $ret
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getObjects($type, $name1Pattern='', $name2Pattern='') {
		$ret = Array();
		$filter = '';
		$sDirectory = '';
		
		switch($type) {
			case 'host':
				$sDirectory = '/tmp/ndo2fs/default/HOSTS';
			break;
			case 'service':
				$oServices = json_decode(file_get_contents($this->path.'/VIEWS/SERVICELIST'));
				foreach($oServices->$name1Pattern AS $service) {
					$ret[] = Array('name1' => $name1Pattern, 'name2' => $service);
				}
			break;
			case 'hostgroup':
				$sDirectory = '/tmp/ndo2fs/default/HOSTGROUPS';
			break;
			case 'servicegroup':
				$sDirectory = '/tmp/ndo2fs/default/SERVICEGROUPS';
			break;
			default:
				return Array();
			break;
		}
		
		if($sDirectory != '') {
			if ($handle = opendir($sDirectory)) {
				while(false !== ($file = readdir($handle))) {
					if($file != '..' && $file != '.') {
						$ret[] = Array('name1' => $file, 'name2' => '');
					}				
				}
			}
			closedir($handle);
		}
		
		return $ret;
	}
	
	/**
	 * PRIVATE Method getHostAckByHostname
	 *
	 * Returns if a host state has been acknowledged. The method doesn't check
	 * if the host is in OK/DOWN, it only checks the has_been_acknowledged flag.
	 *
	 * @param	string $hostName
	 * @return	bool $ack
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHostAckByHostname($hostName) {
		$return = FALSE;
		
		// Read from cache or fetch from NDO
		if(isset($this->hostAckCache[$hostName])) {
			$return = $this->hostAckCache[$hostName];
		} else {
			if(file_exists($this->path.'/HOSTS/'.$hostName)) {
				$oStatus = json_decode(file_get_contents($this->path.'/HOSTS/'.$hostName.'/STATUS'));
				
				// It's unnessecary to check if the value is 0, everything not equal to 1 is FALSE
				if(isset($oStatus->PROBLEMHASBEENACKNOWLEDGED) && $oStatus->PROBLEMHASBEENACKNOWLEDGED == '1') {
					$return = TRUE;
				} else {
					$return = FALSE;
				}
				
				// Save to cache
				$this->hostAckCache[$hostName] = $return;
			}
		}
		
		return $return;
	}
	
	/**
	 * PUBLIC getHostState()
	 *
	 * Returns the Nagios state and additional information for the requested host
	 *
	 * @param		String	$hostName
	 * @param		Boolean	$onlyHardstates
	 * @return	array		$state
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHostState($hostName, $onlyHardstates) {
		if(isset($this->hostCache[$hostName.'-'.$onlyHardstates])) {
			return $this->hostCache[$hostName.'-'.$onlyHardstates];
		} else {
			$arrReturn = Array();
			
			if(file_exists($this->path.'/HOSTS/'.$hostName)) {
				$oConfig = json_decode(file_get_contents($this->path.'/HOSTS/'.$hostName.'/CONFIG'));
				$oStatus = json_decode(file_get_contents($this->path.'/HOSTS/'.$hostName.'/STATUS'));
				
				$arrReturn['object_id'] = $this->iObjIdCounter++;
				
				$arrReturn['alias'] = $oConfig->HOSTALIAS;
				$arrReturn['display_name'] = $oConfig->DISPLAYNAME;
				$arrReturn['address'] = $oConfig->HOSTADDRESS;
				$arrReturn['statusmap_image'] = $oConfig->STATUSMAPIMAGE;
				
				// Add Additional information to array
				$arrReturn['perfdata'] = $oStatus->PERFDATA;
				$arrReturn['last_check'] = $oStatus->LASTHOSTCHECK;
				$arrReturn['next_check'] = $oStatus->NEXTHOSTCHECK;
				$arrReturn['state_type'] = $oStatus->STATETYPE;
				$arrReturn['current_check_attempt'] = $oStatus->CURRENTCHECKATTEMPT;
				$arrReturn['max_check_attempts'] = $oStatus->MAXCHECKATTEMPTS;
				$arrReturn['last_state_change'] = $oStatus->LASTSTATECHANGE;
				$arrReturn['last_hard_state_change'] = $oStatus->LASTHARDSTATECHANGE;
				
				// If there is a downtime for this object, save the data
				// FIXME!
				/*if(isset($data['downtime_start']) && $data['downtime_start'] != '') {
					$arrReturn['in_downtime'] = 1;
					$arrReturn['downtime_start'] = $data['downtime_start'];
					$arrReturn['downtime_end'] = $data['downtime_end'];
					$arrReturn['downtime_author'] = $data['downtime_author'];
					$arrReturn['downtime_data'] = $data['downtime_data'];
				}*/
				
				/**
				 * Only recognize hard states. There was a discussion about the implementation
				 * This is a new handling of only_hard_states. For more details, see: 
				 * http://www.nagios-portal.de/wbb/index.php?page=Thread&threadID=8524
				 *
				 * Thanks to Andurin and fredy82
				 */
				if($onlyHardstates == 1) {
					if($oStatus->STATETYPE != '0') {
						$oStatus->CURRENTSTATE = $oStatus->CURRENTSTATE;
					} else {
						$oStatus->CURRENTSTATE = $oStatus->LASTHARDSTATE;
					}
				}
				
				if($oStatus->HASBEENCHECKED == '0' || $oStatus->CURRENTSTATE == '') {
					$arrReturn['state'] = 'PENDING';
					$arrReturn['output'] = $this->CORE->LANG->getText('hostIsPending','HOST~'.$hostName);
				} elseif($oStatus->CURRENTSTATE == '0') {
					// Host is UP
					$arrReturn['state'] = 'UP';
					$arrReturn['output'] = $oStatus->OUTPUT;
				} else {
					// Host is DOWN/UNREACHABLE/UNKNOWN
					
					// Store acknowledgement state in array
					$arrReturn['problem_has_been_acknowledged'] = $oStatus->PROBLEMHASBEENACKNOWLEDGED;
					
					// Save acknowledgement status to host ack cache
					$this->hostAckCache[$hostName] = $oStatus->PROBLEMHASBEENACKNOWLEDGED;
					
					// Store state and output in array
					switch($data['current_state']) {
						case '1': 
							$arrReturn['state'] = 'DOWN';
							$arrReturn['output'] = $oStatus->OUTPUT;
						break;
						case '2':
							$arrReturn['state'] = 'UNREACHABLE';
							$arrReturn['output'] = $oStatus->OUTPUT;
						break;
						case '3':
							$arrReturn['state'] = 'UNKNOWN';
							$arrReturn['output'] = $oStatus->OUTPUT;
						break;
						default:
							$arrReturn['state'] = 'UNKNOWN';
							$arrReturn['output'] = 'GlobalBackendndomy::getHostState: Undefined state!';
						break;
					}
				}
			} else {
				$arrReturn['state'] = 'ERROR';
				$arrReturn['output'] = $this->CORE->LANG->getText('hostNotFoundInDB','HOST~'.$hostName);
			}
			
			// Write return array to cache
			$this->hostCache[$hostName.'-'.$onlyHardstates] = $arrReturn;
			
			return $arrReturn;
		}
	}
	
	/**
	 * PUBLIC getServiceState()
	 *
	 * Returns the state and additional information of the requested service
	 *
	 * @param		String 	$hostName
	 * @param		String 	$serviceName
	 * @param		Boolean	$onlyHardstates
	 * @return	Array		$state
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getServiceState($hostName, $serviceName, $onlyHardstates) {
		if(isset($this->serviceCache[$hostName.'-'.$serviceName.'-'.$onlyHardstates])) {
			return $this->serviceCache[$hostName.'-'.$serviceName.'-'.$onlyHardstates];
		} else {
			$arrReturn = Array();
			$aServObj = Array();
			
			if(isset($serviceName) && $serviceName != '') {
				if(file_exists($this->path.'/HOSTS/'.$hostName)) {
					$oServices = json_decode(file_get_contents($this->path.'/VIEWS/SERVICELIST'));
					
					$serviceName = strtr($serviceName,' ','_');
					
					if(file_exists($this->path.'/HOSTS/'.$hostName.'/'.$serviceName)) {
						$oConfig = json_decode(file_get_contents($this->path.'/HOSTS/'.$hostName.'/'.$serviceName.'/CONFIG'));
						$oStatus = json_decode(file_get_contents($this->path.'/HOSTS/'.$hostName.'/'.$serviceName.'/STATUS'));
						
						$aServObj[] = Array($oConfig, $oStatus);
					}
				}
			} else {
				if(file_exists($this->path.'/HOSTS/'.$hostName)) {
					$oServices = json_decode(file_get_contents($this->path.'/VIEWS/SERVICELIST'));
					
					foreach($oServices->{$hostName} AS $service) {
						if(file_exists($this->path.'/HOSTS/'.$hostName.'/'.$service)) {
							$service = strtr($service,' ','_');
							
							$oConfig = json_decode(file_get_contents($this->path.'/HOSTS/'.$hostName.'/'.$service.'/CONFIG'));
							$oStatus = json_decode(file_get_contents($this->path.'/HOSTS/'.$hostName.'/'.$service.'/STATUS'));
							
							$aServObj[] = Array($oConfig, $oStatus);
						}
					}
				}
			}
			
			$numServices = count($aServObj);
			if($numServices <= 0) {
				if(isset($serviceName) && $serviceName != '') {
					$arrReturn['state'] = 'ERROR';
					$arrReturn['output'] = $this->CORE->LANG->getText('serviceNotFoundInDB','SERVICE~'.$serviceName.',HOST~'.$hostName);
				} else {
					// If the method should fetch all services of the host and does not find
					// any services for this host, don't return anything => The message
					// that the host has no services is added by the frontend
				}
			} else {
				for($i = 0; $i < $numServices; $i++) {
					$arrTmpReturn = Array();
					
					$arrTmpReturn['object_id'] = $this->iObjIdCounter++;
					
					$arrTmpReturn['service_description'] = $aServObj[$i][0]->SERVICEDESCRIPTION;
					$arrTmpReturn['display_name'] = $aServObj[$i][0]->DISPLAYNAME;
					$arrTmpReturn['alias'] = $aServObj[$i][0]->DISPLAYNAME;
					//$arrTmpReturn['address'] = $aServObj[$i][0]->address'];
					
					// Add additional information to array
					$arrTmpReturn['perfdata'] = $aServObj[$i][1]->PERFDATA;
					$arrTmpReturn['last_check'] = $aServObj[$i][1]->LASTSERVICECHECK;
					$arrTmpReturn['next_check'] = $aServObj[$i][1]->NEXTSERVICECHECK;
					$arrTmpReturn['state_type'] = $aServObj[$i][1]->STATETYPE;
					$arrTmpReturn['current_check_attempt'] = $aServObj[$i][1]->CURRENTCHECKATTEMPT;
					$arrTmpReturn['max_check_attempts'] = $aServObj[$i][1]->MAXCHECKATTEMPTS;
					$arrTmpReturn['last_state_change'] = $aServObj[$i][1]->LASTSTATECHANGE;
					$arrTmpReturn['last_hard_state_change'] = $aServObj[$i][1]->LASTHARDSTATECHANGE;
					
					// If there is a downtime for this object, save the data
					// FIXME!
					/*if(isset($data['downtime_start']) && $data['downtime_start'] != '') {
						$arrTmpReturn['in_downtime'] = 1;
						$arrTmpReturn['downtime_start'] = $data['downtime_start'];
						$arrTmpReturn['downtime_end'] = $data['downtime_end'];
						$arrTmpReturn['downtime_author'] = $data['downtime_author'];
						$arrTmpReturn['downtime_data'] = $data['downtime_data'];
					}*/
					
					/**
					 * Only recognize hard states. There was a discussion about the implementation
					 * This is a new handling of only_hard_states. For more details, see: 
					 * http://www.nagios-portal.de/wbb/index.php?page=Thread&threadID=8524
					 *
					 * Thanks to Andurin and fredy82
					 */
					if($onlyHardstates == 1) {
						if($aServObj[$i][1]->STATETYPE != '0') {
							$aServObj[$i][1]->CURRENTSTATE = $aServObj[$i][1]->CURRENTSTATE;
						} else {
							$aServObj[$i][1]->CURRENTSTATE = $aServObj[$i][1]->LASTHARDSTATE;
						}
					}
					
					if($aServObj[$i][1]->HASBEENCHECKED == '0' || $aServObj[$i][1]->CURRENTSTATE == '') {
						$arrTmpReturn['state'] = 'PENDING';
						$arrTmpReturn['output'] = $this->CORE->LANG->getText('serviceNotChecked','SERVICE~'.$aServObj[$i][0]->SERVICEDESCRIPTION);
					} elseif($aServObj[$i][1]->CURRENTSTATE == '0') {
						// Host is UP
						$arrTmpReturn['state'] = 'OK';
						$arrTmpReturn['output'] = $aServObj[$i][1]->OUTPUT;
					} else {
						// Host is DOWN/UNREACHABLE/UNKNOWN
						
						/**
						 * If state is not OK (=> WARN, CRIT, UNKNOWN) and service is not 
						 * acknowledged => check for acknowledged host
						 */
						if($aServObj[$i][1]->PROBLEMHASBEENACKNOWLEDGED != 1) {
							$arrTmpReturn['problem_has_been_acknowledged'] = $this->getHostAckByHostname($hostName);
						} else {
							$arrTmpReturn['problem_has_been_acknowledged'] = $aServObj[$i][1]->PROBLEMHASBEENACKNOWLEDGED;
						}
						
						// Store state and output in array
						switch($data['current_state']) {
							case '1': 
								$arrTmpReturn['state'] = 'WARNING';
								$arrTmpReturn['output'] = $aServObj[$i][1]->OUTPUT;
							break;
							case '2':
								$arrTmpReturn['state'] = 'CRITICAL';
								$arrTmpReturn['output'] = $aServObj[$i][1]->OUTPUT;
							break;
							case '3':
								$arrTmpReturn['state'] = 'UNKNOWN';
								$arrTmpReturn['output'] = $aServObj[$i][1]->OUTPUT;
							break;
							default:
								$arrTmpReturn['state'] = 'UNKNOWN';
								$arrTmpReturn['output'] = 'GlobalBackendndomy::getServiceState: Undefined state!';
							break;
						}
					}
					
					// If more than one service is expected, append the current return information to return array
					if(isset($serviceName) && $serviceName != '') {
						$arrReturn = $arrTmpReturn;
					} else {
						// Assign actual dataset to return array
						$arrReturn[strtr($aServObj[$i][0]->SERVICEDESCRIPTION,' ','_')] = $arrTmpReturn;
					}
				}
			}
			
			// Write return array to cache
			$this->serviceCache[$hostName.'-'.$serviceName.'-'.$onlyHardstates] = &$arrReturn;
			
			return $arrReturn;
		}
	}
	
	/**
	 * PUBLIC Method getHostNamesWithNoParent
	 *
	 * Gets all hosts with no parent host. This method is needed by the automap 
	 * to get the root host.
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHostNamesWithNoParent() {
		$aReturn = Array();
		
		if(isset($this->oParentlistCache)) {
			$oParents = $this->oParentlistCache;
		} else {
			$oParents = json_decode(file_get_contents($this->path.'/VIEWS/PARENTLIST'));
			$this->oParentlistCache = $oParents;
		}
		
		if(isset($oParents) && isset($oParents->_NONE_)) {
			$aReturn = $oParents->_NONE_;
		}
		
		return $aReturn;
	}
	
	/**
	 * PUBLIC Method getDirectChildNamesByHostName
	 *
	 * Gets the names of all child hosts
	 *
	 * @param		String		Name of host to get the children of
	 * @return	Array			Array with hostnames
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getDirectChildNamesByHostName($hostName) {
		$aReturn = Array();
		
		if(isset($this->oParentlistCache)) {
			$oParents = $this->oParentlistCache;
		} else {
			$oParents = json_decode(file_get_contents($this->path.'/VIEWS/PARENTLIST'));
			$this->oParentlistCache = $oParents;
		}
		
		if(isset($oParents) && isset($oParents->$hostName)) {
			$aReturn = $oParents->$hostName;
		}
		
		return $aReturn;
	}
	
	/**
	 * PUBLIC Method getHostsByHostgroupName
	 *
	 * Gets all hosts of a hostgroup
	 *
	 * @param		String		Name of hostgroup to get the hosts of
	 * @return	Array			Array with hostnames
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHostsByHostgroupName($hostgroupName) {
		$aReturn = Array();
		
		if(file_exists($this->path.'/HOSTGROUPS/'.$hostgroupName)) {
			$oMeta = json_decode(file_get_contents($this->path.'/HOSTGROUPS/'.$hostgroupName.'/META'));
			$aReturn = $oMeta->HOSTGROUPMEMBER;
		}
		
		return $aReturn;
	}
	
	/**
	 * PUBLIC Method getServicesByServicegroupName
	 *
	 * Gets all services of a servicegroup
	 *
	 * @param		String		Name of servicegroup to get the services of
	 * @return	Array			Array with hostnames and service descriptions
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getServicesByServicegroupName($servicegroupName) {
		$aReturn = Array();
		
		if(file_exists($this->path.'/SERVICEGROUPS/'.$servicegroupName)) {
			$oMeta = json_decode(file_get_contents($this->path.'/SERVICEGROUPS/'.$servicegroupName.'/META'));
			
			foreach($oMeta->SERVICEGROUPMEMBER AS $member) {
				$a = explode(';', $member);
				$aReturn[] = Array('host_name' => $a[0], 'service_description' => $a[1]);
			}
		}
		
		return $aReturn;
	}
    
    /**
	 * PUBLIC Method getServicegroupInformations
	 *
	 * Gets information like the alias for a servicegroup
	 *
	 * @param	String		    Name of servicegroup
	 * @return	Array			Array with object information
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getServicegroupInformations($servicegroupName) {
		$aReturn = Array();
		
		if(file_exists($this->path.'/SERVICEGROUPS/'.$servicegroupName)) {
			$oMeta = json_decode(file_get_contents($this->path.'/SERVICEGROUPS/'.$servicegroupName.'/META'));
			
			$aReturn['alias'] = $oMeta->SERVICEGROUPALIAS;
			$aReturn['object_id'] = $this->iObjIdCounter++;
		}
		
		return $aReturn;
	}
    
    /**
	 * PUBLIC Method getHostgroupInformations
	 *
	 * Gets information like the alias for a hostgroup
	 *
	 * @param	String		    Name of group
	 * @return	Array			Array with object information
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHostgroupInformations($hostgroupName) {
		$arrReturn = Array();
		
		if(file_exists($this->path.'/HOSTGROUPS/'.$hostgroupName)) {
			$oMeta = json_decode(file_get_contents($this->path.'/HOSTGROUPS/'.$hostgroupName.'/META'));
			
			$aReturn['alias'] = $oMeta->HOSTGROUPALIAS;
			$aReturn['object_id'] = $this->iObjIdCounter++;
			
		}
		
		return $arrReturn;
	}
}
?>
