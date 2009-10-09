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

class GlobalBackendndo2fs implements GlobalBackendInterface {
	private $CORE;
	private $backendId;
	private $pathPersistent;
	private $pathVolatile;
	private $instanceName;
	
	private $hostCache;
	private $serviceCache;
	private $hostAckCache;
	private $oParentlistCache;
	
	// These are the backend local configuration options
	private static $validConfig = Array(
		'path' => Array('must' => 1,
			'editable' => 1,
			'default' => '/usr/local/ndo2fs/var',
			'match' => MATCH_STRING_PATH),
		'instancename' => Array('must' => 1,
			'editable' => 1,
			'default' => 'default',
			'match' => MATCH_STRING_NO_SPACE),
		'maxtimewithoutupdate' => Array('must' => 1,
			'editable' => 1,
			'default' => '180',
			'match' => MATCH_INTEGER));
	
	/**
	 * Constructor
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $backendId) {
		$this->CORE = $CORE;
		
		$this->backendId = $backendId;
		
		$this->hostCache = Array();
		$this->serviceCache = Array();
		$this->hostAckCache = Array();
		
		$this->instanceName = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'instancename');
		$this->pathPersistent = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'path').'/PERSISTENT/'.$this->instanceName;
		$this->pathVolatile = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'path').'/VOLATILE/'.$this->instanceName;
		
		if(!$this->checkFileStructure()) {
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('ndo2fsFileStructureNotValid', Array('BACKENDID' => $this->backendId, 'PATH' => $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'path'))));
		}
		
		if(!$this->checkLastUpdateTime()) {
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('nagiosDataNotUpToDate', Array('BACKENDID' => $this->backendId, 'TIMEWITHOUTUPDATE' => $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'maxtimewithoutupdate'))));
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
		if(file_exists($this->pathVolatile.'/PROCESS_STATUS') && is_readable($this->pathVolatile.'/PROCESS_STATUS')
			&& file_exists($this->pathVolatile.'/PROGRAM_STATUS') && is_readable($this->pathVolatile.'/PROGRAM_STATUS')
			&& file_exists($this->pathVolatile.'/HOSTS') && is_readable($this->pathVolatile.'/HOSTS')
			&& file_exists($this->pathVolatile.'/VIEWS') && is_readable($this->pathVolatile.'/VIEWS')) {
			
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
		$oStatus = json_decode(file_get_contents($this->pathVolatile.'/PROCESS_STATUS'));
		
		if($_SERVER['REQUEST_TIME'] - $oStatus->LASTCOMMANDCHECK > $this->CORE->MAINCFG->getValue('backend_'.$this->backendId, 'maxtimewithoutupdate')) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * PUBLIC Method getValidConfig
	 * 
	 * Returns the valid config for this backend
	 *
	 * @return	Array
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getValidConfig() {
		return self::$validConfig;
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
		$sFile = '';
		
		switch($type) {
			case 'host':
				$sFile = $this->pathVolatile.'/VIEWS/HOSTLIST';
			break;
			case 'service':
				$oServices = json_decode(file_get_contents($this->pathVolatile.'/VIEWS/SERVICELIST'));
				if(isset($oServices->$name1Pattern)) {
					foreach($oServices->$name1Pattern AS $service) {
						$ret[] = Array('name1' => $name1Pattern, 'name2' => $service);
					}
				}
			break;
			case 'hostgroup':
				$sFile = $this->pathVolatile.'/VIEWS/HOSTGROUPLIST';
			break;
			case 'servicegroup':
				$sFile = $this->pathVolatile.'/VIEWS/SERVICEGROUPLIST';
			break;
			default:
				return Array();
			break;
		}
		
		if($sFile != '') {
			if(file_exists($sFile)) {
				$oObjects = json_decode(file_get_contents($sFile));
				
				foreach($oObjects AS $sObject => $aObjects) {
					foreach($aObjects AS $sObj) {
						$ret[] = Array('name1' => $sObj, 'name2' => '');
					}
				}
			}
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
	private function getHostAckByHostname($hostName) {
		$return = FALSE;
		
		// Read from cache or fetch from NDO
		if(isset($this->hostAckCache[$hostName])) {
			$return = $this->hostAckCache[$hostName];
		} else {
			if(file_exists($this->pathVolatile.'/HOSTS/'.$hostName)) {
				$oStatus = json_decode(file_get_contents($this->pathVolatile.'/HOSTS/'.$hostName.'/STATUS'));
				
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
	public function getHostState($hostName, $onlyHardstates) {
		if(isset($this->hostCache[$hostName.'-'.$onlyHardstates])) {
			return $this->hostCache[$hostName.'-'.$onlyHardstates];
		} else {
			$arrReturn = Array();
			
			if(!file_exists($this->pathVolatile.'/HOSTS/'.$hostName)
			  || !file_exists($this->pathVolatile.'/HOSTS/'.$hostName.'/CONFIG')) {
				$arrReturn['state'] = 'ERROR';
				$arrReturn['output'] = $this->CORE->LANG->getText('hostNotFoundInDB', Array('BACKENDID' => $this->backendId, 'HOST' => $hostName));
			} elseif(!file_exists($this->pathVolatile.'/HOSTS/'.$hostName.'/STATUS')) {
				$arrReturn['state'] = 'PENDING';
				$arrReturn['output'] = $this->CORE->LANG->getText('hostIsPending', Array('HOST' => $hostName));
			} else {
				$oConfig = json_decode(file_get_contents($this->pathVolatile.'/HOSTS/'.$hostName.'/CONFIG'));
				$oStatus = json_decode(file_get_contents($this->pathVolatile.'/HOSTS/'.$hostName.'/STATUS'));
				
				$arrReturn['alias'] = $oConfig->HOSTALIAS;
				$arrReturn['display_name'] = $oConfig->DISPLAYNAME;
				$arrReturn['address'] = $oConfig->HOSTADDRESS;
				$arrReturn['statusmap_image'] = $oConfig->STATUSMAPIMAGE;
				$arrReturn['notes'] = $oConfig->NOTES;
				
				// Add Additional information to array
				$arrReturn['perfdata'] = $oStatus->PERFDATA;
				$arrReturn['last_check'] = $oStatus->LASTHOSTCHECK;
				
				// It seems in some cases the option is not set
				if(isset($oStatus->NEXTHOSTCHECK)) {
					$arrReturn['next_check'] = $oStatus->NEXTHOSTCHECK;
				} else {
					$arrReturn['next_check'] = 0;
				}
				
				$arrReturn['state_type'] = $oStatus->STATETYPE;
				$arrReturn['current_check_attempt'] = $oStatus->CURRENTCHECKATTEMPT;
				$arrReturn['max_check_attempts'] = $oStatus->MAXCHECKATTEMPTS;
				$arrReturn['last_state_change'] = $oStatus->LASTSTATECHANGE;
				$arrReturn['last_hard_state_change'] = $oStatus->LASTHARDSTATECHANGE;
				
				// If there is a downtime for this object, save the data
				if($oStatus->SCHEDULEDDOWNTIMEDEPTH > 0) {
					$arrReturn['in_downtime'] = 1;
					
					$sFile = $this->pathPersistent.'/DOWNTIME/'.$hostName;
					if(file_exists($sFile)) {
						$oDowntime = json_decode(file_get_contents($sFile));
						
						$arrReturn['downtime_start'] = $oDowntime->STARTTIME;
						$arrReturn['downtime_end'] = $oDowntime->ENDTIME;
						$arrReturn['downtime_author'] = $oDowntime->AUTHORNAME;
						$arrReturn['downtime_data'] = $oDowntime->COMMENT;
					}
				}
				
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
					$arrReturn['output'] = $this->CORE->LANG->getText('hostIsPending', Array('HOST' => $hostName));
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
					switch($oStatus->CURRENTSTATE) {
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
	public function getServiceState($hostName, $serviceName, $onlyHardstates) {
		if(isset($this->serviceCache[$hostName.'-'.$serviceName.'-'.$onlyHardstates])) {
			return $this->serviceCache[$hostName.'-'.$serviceName.'-'.$onlyHardstates];
		} else {
			$arrReturn = Array();
			$aServObj = Array();
			$numServices = 0;
			
			if(isset($serviceName) && $serviceName != '') {
				if(file_exists($this->pathVolatile.'/HOSTS/'.$hostName)) {
					$oServices = json_decode(file_get_contents($this->pathVolatile.'/VIEWS/SERVICELIST'));
					
					// Replace some bad chars
					$serviceName = strtr($serviceName,' /:\\','____');
					
					if(!file_exists($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$serviceName)
					  || !file_exists($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$serviceName.'/CONFIG')) {
						
						// Services which have no configuration information (Should not exist)
						$aServObj[] = Array(null, null);
					} elseif(!file_exists($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$serviceName.'/STATUS')) {
						
						// Read object configuration
						$oConfig = json_decode(file_get_contents($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$service.'/CONFIG'));
						
						// No status information: Service is pending
						$aServObj[] = Array($oConfig, null);
						
						// Count the services which are matching
						$numServices++;
					} else {
						$oConfig = json_decode(file_get_contents($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$serviceName.'/CONFIG'));
						$oStatus = json_decode(file_get_contents($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$serviceName.'/STATUS'));
						
						$aServObj[] = Array($oConfig, $oStatus);
						
						// Count the services which are matching
						$numServices++;
					}
				}
			} else {
				if(file_exists($this->pathVolatile.'/HOSTS/'.$hostName)) {
					$oServices = json_decode(file_get_contents($this->pathVolatile.'/VIEWS/SERVICELIST'));
					
					// Only try to loop when there are some services for this host
					if(isset($oServices->{$hostName})) {
						foreach($oServices->{$hostName} AS $service) {
							// Replace some bad chars
							$service = strtr($service,' /:\\','____');
							
							if(!file_exists($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$service)
							  || !file_exists($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$service.'/CONFIG')) {
								
								// Services which have no configuration information (Should not exist)
								// FIXME: Error handling
								echo "ndo2fs config not found: ".$this->pathVolatile.'/HOSTS/'.$hostName.'/'.$service.'/CONFIG'."<br>";
								$aServObj[] = Array(null, null);
							} elseif(!file_exists($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$service.'/STATUS')) {		
								
								// Read object configuration
								$oConfig = json_decode(file_get_contents($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$service.'/CONFIG'));
								
								// No status information: Service is pending
								$aServObj[] = Array($oConfig, null);
								
								// Count the services which are matching
								$numServices++;
							} else {
								
								// Read config and status informations
								$oConfig = json_decode(file_get_contents($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$service.'/CONFIG'));
								$oStatus = json_decode(file_get_contents($this->pathVolatile.'/HOSTS/'.$hostName.'/'.$service.'/STATUS'));
								
								$aServObj[] = Array($oConfig, $oStatus);
								
								// Count the services which are matching
								$numServices++;
							}
						}
					}
				}
			}
			
			if($numServices <= 0) {
				if(isset($serviceName) && $serviceName != '') {
					$arrReturn['state'] = 'ERROR';
					$arrReturn['output'] = $this->CORE->LANG->getText('serviceNotFoundInDB', Array('BACKENDID' => $this->backendId, 'SERVICE' => $serviceName, 'HOST' => $hostName));
				} else {
					// If the method should fetch all services of the host and does not find
					// any services for this host, don't return anything => The message
					// that the host has no services is added by the frontend
				}
			} else {
				$iSize = count($aServObj);
				for($i = 0; $i < $iSize; $i++) {
					$arrTmpReturn = Array();
					
					// Error handling: Service with no configuration
					if($aServObj[$i][0] == null && $aServObj[$i][1] == null) {
						continue;
					}
					
					/**
					 * Add config/general information to array
					 */
					
					$arrTmpReturn['service_description'] = $aServObj[$i][0]->SERVICEDESCRIPTION;
					$arrTmpReturn['display_name'] = $aServObj[$i][0]->DISPLAYNAME;
					$arrTmpReturn['alias'] = $aServObj[$i][0]->DISPLAYNAME;
					//$arrTmpReturn['address'] = $aServObj[$i][0]->address'];
					$arrTmpReturn['notes'] = $aServObj[$i][0]->NOTES;
					
					// Error handling: Pending service
					if($aServObj[$i][1] == null) {
						$arrTmpReturn['state'] = 'PENDING';
						$arrTmpReturn['output'] = $this->CORE->LANG->getText('serviceNotChecked', Array('SERVICE' => $aServObj[$i][0]->SERVICEDESCRIPTION));
					} else {
						/**
						 * Add status information to array
						 */
						
						$arrTmpReturn['perfdata'] = $aServObj[$i][1]->PERFDATA;
						$arrTmpReturn['last_check'] = $aServObj[$i][1]->LASTSERVICECHECK;
						
						// It seems this is not set in some cases
						if(isset($aServObj[$i][1]->NEXTSERVICECHECK)) {
							$arrTmpReturn['next_check'] = $aServObj[$i][1]->NEXTSERVICECHECK;
						} else {
							$arrTmpReturn['next_check'] = 0;
						}
						
						$arrTmpReturn['state_type'] = $aServObj[$i][1]->STATETYPE;
						$arrTmpReturn['current_check_attempt'] = $aServObj[$i][1]->CURRENTCHECKATTEMPT;
						$arrTmpReturn['max_check_attempts'] = $aServObj[$i][1]->MAXCHECKATTEMPTS;
						$arrTmpReturn['last_state_change'] = $aServObj[$i][1]->LASTSTATECHANGE;
						$arrTmpReturn['last_hard_state_change'] = $aServObj[$i][1]->LASTHARDSTATECHANGE;
						
						// If there is a downtime for this object, save the data
						if($aServObj[$i][1]->SCHEDULEDDOWNTIMEDEPTH > 0) {
							$arrTmpReturn['in_downtime'] = 1;
							
							$sFile = $this->pathPersistent.'/DOWNTIME/'.$hostName.'::'.strtr($aServObj[$i][0]->SERVICEDESCRIPTION,' ','_');
							if(file_exists($sFile)) {
								$oDowntime = json_decode(file_get_contents($sFile));
								
								$arrTmpReturn['downtime_start'] = $oDowntime->STARTTIME;
								$arrTmpReturn['downtime_end'] = $oDowntime->ENDTIME;
								$arrTmpReturn['downtime_author'] = $oDowntime->AUTHORNAME;
								$arrTmpReturn['downtime_data'] = $oDowntime->COMMENT;
							}
						}
						
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
							$arrTmpReturn['output'] = $this->CORE->LANG->getText('serviceNotChecked', Array('SERVICE' => $aServObj[$i][0]->SERVICEDESCRIPTION));
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
							switch($aServObj[$i][1]->CURRENTSTATE) {
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
	public function getHostNamesWithNoParent() {
		$aReturn = Array();
		
		if(isset($this->oParentlistCache)) {
			$oParents = $this->oParentlistCache;
		} else {
			$oParents = json_decode(file_get_contents($this->pathVolatile.'/VIEWS/PARENTLIST'));
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
	public function getDirectChildNamesByHostName($hostName) {
		$aReturn = Array();
		
		if(isset($this->oParentlistCache)) {
			$oParents = $this->oParentlistCache;
		} else {
			$oParents = json_decode(file_get_contents($this->pathVolatile.'/VIEWS/PARENTLIST'));
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
	public function getHostsByHostgroupName($hostgroupName) {
		$aReturn = Array();
		
		if(file_exists($this->pathVolatile.'/HOSTGROUPS/'.$hostgroupName)) {
			$oMeta = json_decode(file_get_contents($this->pathVolatile.'/HOSTGROUPS/'.$hostgroupName.'/META'));
			
			// The property does not exist in empty hostgroups
			if(isset($oMeta->HOSTGROUPMEMBER)) {
				$aReturn = $oMeta->HOSTGROUPMEMBER;
			}
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
	public function getServicesByServicegroupName($servicegroupName) {
		$aReturn = Array();
		
		if(file_exists($this->pathVolatile.'/SERVICEGROUPS/'.$servicegroupName)) {
			$oMeta = json_decode(file_get_contents($this->pathVolatile.'/SERVICEGROUPS/'.$servicegroupName.'/META'));
			
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
	public function getServicegroupInformations($servicegroupName) {
		$aReturn = Array();
		
		if(file_exists($this->pathVolatile.'/SERVICEGROUPS/'.$servicegroupName)) {
			$oMeta = json_decode(file_get_contents($this->pathVolatile.'/SERVICEGROUPS/'.$servicegroupName.'/META'));
			
			$aReturn['alias'] = $oMeta->SERVICEGROUPALIAS;
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
	public function getHostgroupInformations($hostgroupName) {
		$arrReturn = Array();
		
		if(file_exists($this->pathVolatile.'/HOSTGROUPS/'.$hostgroupName)) {
			$oMeta = json_decode(file_get_contents($this->pathVolatile.'/HOSTGROUPS/'.$hostgroupName.'/META'));
			
			$aReturn['alias'] = $oMeta->HOSTGROUPALIAS;
			
		}
		
		return $arrReturn;
	}
}
?>
