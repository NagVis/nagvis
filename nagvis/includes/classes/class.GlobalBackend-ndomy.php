<?php
##########################################################################
##                 NagVis - The Nagios Visualisation                    ##
##########################################################################
## GlobalBackend_ndomy.php Backend module to fetch the status from      ##
##                         Nagios NDO Mysql DB. All not special to one  ##
##                         Backend related things should removed here!  ##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence,          ##
## please see attached "LICENCE" file                                   ##
##########################################################################
## This Backend is maintained by Andreas Husch (dowanup@nagios-wiki.de) ##
##########################################################################

class GlobalBackendndomy {
	var $MAINCFG;
	var $LANG;
	var $backendId;
	var $dbName;
	var $dbUser;
	var $dbPass;
	var $dbHost;
	var $dbPrefix;
	var $dbInstanceName;
	var $dbInstanceId;
	
	/**
	* Constructor
	* Reads needed configuration paramters, connects to the Database
	* and checks that Nagios is running
	*
	* @param	config $MAINCFG
	* @param	String $backendId
	* @author	Andreas Husch <downanup@nagios-wiki.de>
	* @author	Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function GlobalBackendndomy(&$MAINCFG,$backendId) {
		$this->MAINCFG = &$MAINCFG;
		$this->backendId = $backendId;
		
		$this->dbName = $this->MAINCFG->getValue('backend_'.$backendId, 'dbname');
		$this->dbUser = $this->MAINCFG->getValue('backend_'.$backendId, 'dbuser');
		$this->dbPass = $this->MAINCFG->getValue('backend_'.$backendId, 'dbpass');
		$this->dbHost = $this->MAINCFG->getValue('backend_'.$backendId, 'dbhost');
		$this->dbPort = $this->MAINCFG->getValue('backend_'.$backendId, 'dbport');
		$this->dbPrefix = $this->MAINCFG->getValue('backend_'.$backendId, 'dbprefix');
		$this->dbInstanceName = $this->MAINCFG->getValue('backend_'.$backendId, 'dbinstancename');
		
		// initialize a language object for later error messages which be given out as state output
		$this->LANG = new GlobalLanguage($this->MAINCFG,'backend:ndomy');

		// Check availability of PHP MySQL
		if (!extension_loaded('mysql')) {
			dl('mysql.so');

			if (!extension_loaded('mysql')) {
				//Error Box
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
				$FRONTEND->messageToUser('ERROR','mysqlNotSupported','BACKENDID~'.$this->backendId);
			}
		}
		
		// don't want to see mysql errors from connecting - we only want our error messages
		$oldLevel = error_reporting(0);

		$CONN = mysql_connect($this->dbHost.':'.$this->dbPort, $this->dbUser, $this->dbPass);
		$returnCode = mysql_select_db($this->dbName, $CONN);
		
		if($returnCode != TRUE){
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
			$FRONTEND->messageToUser('ERROR','errorSelectingDb','BACKENDID~'.$this->backendId);
		}
		
		// we set the old level of reporting back
		error_reporting($oldLevel);
		
		// check if tables exists in database
		$QUERYHANDLE = mysql_query("SHOW TABLES LIKE '".$this->dbPrefix."%'");
		if(mysql_num_rows($QUERYHANDLE) == 0) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
			$FRONTEND->messageToUser('ERROR','noTablesExists','BACKENDID~'.$this->backendId.',PREFIX~'.$this->dbPrefix);
		}
		
		// Set the instanceId
		$this->dbInstanceId = $this->getInstanceId();
		
		// Do some checks to make sure that Nagios is running and the Data at the DB are ok
		$QUERYHANDLE = mysql_query("SELECT is_currently_running, status_update_time FROM ".$this->dbPrefix."programstatus WHERE instance_id = '".$this->dbInstanceId."'");
		$nagiosState = mysql_fetch_array($QUERYHANDLE);
	
		// Check that Nagios reports itself as running	
		if ($nagiosState['is_currently_running'] != 1) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
			$FRONTEND->messageToUser('ERROR','nagiosNotRunning','BACKENDID~'.$this->backendId);
		}
        
		// Be suspiciosly and check that the data at the db are not older that "maxTimeWithoutUpdate" too
		if(time() - strtotime($nagiosState['status_update_time']) > $this->MAINCFG->getValue('backend_'.$backendId, 'maxtimewithoutupdate')) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
			$FRONTEND->messageToUser('ERROR','nagiosDataNotUpToDate','BACKENDID~'.$this->backendId.',TIMEWITHOUTUPDATE~'.$maxTimeWithOutUpdate);
		}
			
		return 0;
	}
	
	/**
	 * PRIVATE Method getInstanceId
	 *
	 * Returns the instanceId of the instanceName
	 *
	 * @return	String $ret
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getInstanceId() {
		$QUERYHANDLE = mysql_query("SELECT instance_id FROM ".$this->dbPrefix."instances WHERE instance_name='".$this->dbInstanceName."'");
		$ret = mysql_fetch_array($QUERYHANDLE);
		
		return $ret['instance_id'];
	}


	/**
	* PUBLIC Method getObjects
	* 
	* Return the objects configured at Nagios wich are matching the given pattern. 
	* This is needed for WUI, e.g. to populate drop down lists.
	*
	* @param	string $type, string $name1Pattern, string $name2Pattern
	* @return	array $ret
	* @author	Lars Michelsen <larsi@nagios-wiki.de>
	* @author	Andreas Husch <downanup@nagios-wiki.de>
	*/
	function getObjects($type,$name1Pattern='',$name2Pattern='') {
		$ret = Array();
		$filter = '';
		
		switch($type) {
			case 'host':
				$objectType = 1;
				
				if($name1Pattern != '') {
					$filter = " name1='".$name1Pattern."' AND ";
				}
			break;
			case 'service':
				$objectType = 2;
				
				if($name1Pattern != '') {
					$filter = " name1='".$name1Pattern."' AND ";
				} elseif($name1Pattern != '' && $name2Pattern != '') {
					$filter = " name1='".$name1Pattern."' AND name2='".$name2Pattern."' AND ";
				}
			break;
			case 'hostgroup':
				$objectType = 3;
				
				if($name1Pattern != '') {
					$filter = " name1='".$name1Pattern."' AND ";
				}
			break;
			case 'servicegroup':
				$objectType = 4;
				
				if($name1Pattern != '') {
					$filter = " name1='".$name1Pattern."' AND ";
				}
			break;
			default:
				return Array();
			break;
		}
		
		$QUERYHANDLE = mysql_query("SELECT object_id,name1,name2 FROM ".$this->dbPrefix."objects WHERE objecttype_id='".$objectType."' AND ".$filter." is_active='1' AND instance_id='".$this->dbInstanceId."' ORDER BY name1");
		while($data = mysql_fetch_array($QUERYHANDLE)) {
			$ret[] = Array('name1' => $data['name1'],
							'name2' => $data['name2']);
		}
		
		return $ret;
	}
	

	/**
	* PUBLIC Method checkStates
	*	
	* Returns the State of the given object
	*
	* @param	string $Type, string $Name, boolean $RecognizeServices, string $ServiceName, boolean $onlyHardStates
	* @return	array $state
	* @author	m.luebben, Andreas Husch <downanup@nagios-wiki.de>
	*/
	function checkStates($Type,$Name,$RecognizeServices,$ServiceName="",$onlyHardStates=0) {
		if(isset($Name)) {
			switch($Type) {
				case 'host':
					$state = $this->findStateHost($Name,$RecognizeServices,$onlyHardStates);
				break;
				case 'service':
					$state = $this->findStateService($Name,$ServiceName,$onlyHardStates);
				break;
				case 'hostgroup':
					$state = $this->findStateHostgroup($Name,$RecognizeServices,$onlyHardStates);
				break;
				case 'servicegroup':
					$state = $this->findStateServicegroup($Name,$onlyHardStates);
				break;
				default:
					//FIXME Error Box (should normally never happen)
				break;
			}
		}
		/**
		* Case that this Backend could not find any state for the given object
		* This should normally never happen
		*/
		if(!isset($state)) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:global'));
			$FRONTEND->messageToUser('WARNING','noStateSet');
			$FRONTEND->printPage();
		}
		
		return $state;
	}


	/**
	* PRIVATE Method findStateHost
	*
	* Returns the Nagios State for a single Host
	*
	* @param	string $hostName, boolean $recognizeServices, boolean $onlyHardStates
	* @return	array $state
	* @author	Lars Michelsen <larsi@nagios-wiki.de>
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	*/
	function findStateHost($hostName,$recognizeServices,$onlyHardStates) {

		$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE objecttype_id = '1' AND name1 = binary '".$hostName."' AND instance_id='".$this->dbInstanceId."'");
		$hostObjectId = mysql_fetch_row($QUERYHANDLE);  

		if (mysql_num_rows($QUERYHANDLE) == 0) {
			$state['State'] = 'ERROR';
			$state['Output'] = $this->LANG->getMessageText('hostNotFoundInDB','HOST~'.$hostName);
			
			return $state;
		}

		$queryString="SELECT last_hard_state, UNIX_TIMESTAMP(last_hard_state_change) AS last_hard_state_change, UNIX_TIMESTAMP(last_state_change) AS last_state_change, current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."hoststatus  WHERE host_object_id = '".$hostObjectId[0]."' AND instance_id='".$this->dbInstanceId."'";
		$QUERYHANDLE = mysql_query($queryString);
		
		if(mysql_num_rows($QUERYHANDLE) <= 0) {
			/** 
			 * This is the special case, that a host is definied but in pending state or that it
			 * was deleted from Nagios but is still present at NDO DB
			 */
			$state['State'] = 'UNKOWN';
			$state['Output'] = $this->LANG->getMessageText('hostIsPending','HOST~'.$hostName);
			$hostState = $state['State'];
		} else {
			$hostState = mysql_fetch_array($QUERYHANDLE);
			
			/**
			 * SF.net #1587073
			 * if "last_hard_state" != OK && last_hard_state_change <= last_state_change => state = current_state
			 *
			 * last_hard_state in ndo_hoststatus seems not to change if a host returns from State != OK by a successfull service check.
			 * i think it changes only if a host check returns an OK. so if there are no host checks scheduled the last_hard_state will 
			 * always stay as it is.
			 */
			if($onlyHardStates == 1) {
				if($hostState['last_hard_state'] != '0' && $hostState['last_hard_state_change'] <= $hostState['last_state_change']) {
					$hostState['current_state'] = $hostState['current_state'];
				} else {
					$hostState['current_state'] = $hostState['last_hard_state'];
				}
			}
			
			if ($hostState['current_state'] == '0') {
				// Host is UP
				$state['State'] = 'UP';
				$state['Output'] = $hostState['output'];
			} elseif ($hostState['current_state'] == '1' || $hostState['current_state'] == '2' || $hostState['current_state'] == '3') {
				// Host is DOWN/UNREACHABLE/UNKNOWN
				if($hostState['problem_has_been_acknowledged'] == 1) {
					$state['State'] = 'ACK';
				} else {
					switch($hostState['current_state']) {
						case '1': 
							$state['State'] = 'DOWN';
						break;
						case '2':
							$state['State'] = 'UNREACHABLE';
						break;
						case '3':
							$state['State'] = 'UNKNOWN';
						break;
					}
				}
				$state['Output'] = $hostState['output'];
			}
			
			$hostState = $state['State'];
			
			/**
			* Check the Services of the Host if requested and the Host is UP (makes no sence if the host is DOWN ;-), 
			* this also makes shure that a host ACK will automatically ACK all services.
			*/
			if($recognizeServices == 1 && $hostState == "UP") {
				//Initialise Vars
				$servicesOk = 0;
				$servicesWarning = 0;
				$servicesCritical = 0;
				$servicesUnknown = 0;
				$servicesAck = 0;
				
				//Get the object ids from all services of this host
				$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE objecttype_id='2' AND name1='".$hostName."' AND instance_id='".$this->dbInstanceId."'");
				while($services = mysql_fetch_array($QUERYHANDLE)) {
					$objectId = $services['object_id'];
					
					//Query the Servicestates
					if($onlyHardStates == 1) {
						$queryString2="SELECT last_hard_state AS current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."servicestatus WHERE service_object_id = '".$objectId."' AND instance_id='".$this->dbInstanceId."'";
					} else {
						$queryString2="SELECT current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."servicestatus WHERE service_object_id = '".$objectId."' AND instance_id='".$this->dbInstanceId."'";
					}

					$QUERYHANDLE_2 = mysql_query($queryString2);
					$currentService = mysql_fetch_array($QUERYHANDLE_2);				
					if($currentService['current_state'] == 0) {
						$servicesOk++;
					}
					elseif($currentService['problem_has_been_acknowledged'] == 1) {
						$servicesAck++;				
					}
					elseif($currentService['current_state'] == 1) {
						$servicesWarning++;
					}
					elseif($currentService['current_state'] == 2) {
						$servicesCritical++;
					}
					elseif($currentService['current_state'] == 3) {
						$servicesUnknown++;
					}
				}
				
				if($servicesCritical > 0) {
					$state['Count'] = $servicesCritical;
					$state['Output'] = "Host is UP but there are ".$servicesCritical." CRITICAL, " .$servicesWarning. " WARNING and " .$servicesUnknown. " UNKNOWN Services";
					$state['State'] = "CRITICAL";
				}
				elseif($servicesWarning > 0) {
					$state['Count'] = $servicesWarning;
					$state['Output'] = "Host is UP but there are " .$servicesWarning. " WARNING and " .$servicesUnknown. " UNKNOWN Services";
					$state['State'] = "WARNING";		
				}
				elseif($servicesUnknown > 0) {
					$state['Count'] = $servicesUnknown;
					$state['Output'] = "Host is UP but there are ".$servicesUnknown." Services in UNKNOWN state";
					$state['State'] = "UNKNOWN";
					
				}
				elseif($servicesAck > 0) {
					$state['Count'] = $servicesAck;
					$state['Output'] = "Host is UP but ".$servicesAck." services are in a NON-OK State but all are ACKNOWLEDGED";
					$state['State'] = "ACK";
				}
				elseif($servicesOk > 0) {
					$state['Count'] = $servicesOk;
					$state['Output'] = "Host is UP and all ".$servicesOk." services are OK";
					//This must be set before by the host, but to be consitend with the other ifs i define it again here:
					$state['State'] = "UP";		
				}
			}
		}
        return $state;
	}
	

	/**
	* PRIVATE Method findStateHostgroup
	*
	* Returns the State for a single Hostgroup 
	*
	* @param	string $hostGroupName, boolean $recognzieServices
	* @return	array $state
	* @author	Andreas Husch (downanup@nagios-wiki.de
	*/
	function findStateHostgroup($hostGroupName,$recognizeServices,$onlyHardStates) {
		//First we have to get the hostgroup_id
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE objecttype_id='3' AND name1 = binary '".$hostGroupName."' AND instance_id='".$this->dbInstanceId."'");
		$objectId = mysql_fetch_row($QUERYHANDLE);
		
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			$state['State'] = "ERROR";
			$state['Output'] = $this->LANG->getMessageText('hostGroupNotFoundInDB','HOSTGROUP~'.$hostGroupName);
			return($state);
		}

		$QUERYHANDLE = mysql_query("SELECT hostgroup_id FROM ".$this->dbPrefix."hostgroups WHERE hostgroup_object_id='".$objectId[0]."' AND instance_id='".$this->dbInstanceId."'");
		$hostGroupId = mysql_fetch_row($QUERYHANDLE);
		
		$hostsCritical=0;
		$hostsWarning=0;
		$hostsUnknown=0;
		$hostsAck=0;
		$hostsOk=0;

		//Now we have the Group Id and can get the hosts
		$QUERYHANDLE = mysql_query("SELECT host_object_id FROM ".$this->dbPrefix."hostgroup_members WHERE hostgroup_id='".$hostGroupId[0]."' AND instance_id='".$this->dbInstanceId."'");	
		while($hosts = mysql_fetch_array($QUERYHANDLE)) {
			$objectId = $hosts['host_object_id'];
			//Get the Host Name for the objectId Again so we can use our own host function
			//this ist not really nice because the name gets changed back to the id there, maybe split the host funktions in to parts
			$QUERYHANDLE_2 = mysql_query("SELECT name1 FROM ".$this->dbPrefix."objects WHERE (objecttype_id = '1' AND object_id = '".$objectId."' AND instance_id='".$this->dbInstanceId."')");
			$hostName = mysql_fetch_array($QUERYHANDLE_2);  
			
			$currentHostState = $this->findStateHost($hostName['name1'],$recognizeServices,$onlyHardStates);
			if($currentHostState['State'] == "UP") {
				$hostsOk++;
			} elseif($currentHostState['State'] == "ACK") {
				$hostsAck++;				
			} elseif($currentHostState['State'] == "WARNING") {
				$hostsWarning++;
			} elseif($currentHostState['State'] == "DOWN" || $currentHostState['State'] == "UNREACHABLE" || $currentHostState['State'] == "CRITICAL") {
				$hostsCritical++;
			} elseif($currentHostState['State'] == "UNKNOWN") {
				$hostsUnknown++;
			}
		}
	
		if($hostsCritical > 0) {
			$state['Count'] = $hostsCritical;
			$state['Output'] = $hostsCritical." Hosts are CRITICAL, " .$hostsWarning. " WARNING and " .$hostsUnknown. " UNKNOWN";
			$state['State'] = "CRITICAL";
		} elseif($hostsWarning > 0) {
			$state['Count'] = $hostsWarning;
			$state['Output'] = $hostsWarning. " Hosts are WARNING and " .$hostsUnknown. " UNKNOWN";
			$state['State'] = "WARNING";		
		} elseif($hostsUnknown > 0) {
			$state['Count'] = $hostsUnknown;
			$state['Output'] = $hostsUnknown." are in UNKNOWN state";
			$state['State'] = "UNKNOWN";
		} elseif($hostsAck > 0) {
			$state['Count'] = $hostsAck;
			$state['Output'] = $hostsAck." Hosts are in a NON-OK State but all errors are ACKNOWLEDGED";
			$state['State'] = "ACK";
		} elseif($hostsOk > 0) {
			$state['Count'] = $hostsOk;
			$state['Output'] = "All " .$hostsOk. " Hosts are OK";
			//This must be set before by the host, but to be consitend with the other ifs i define it again here:
			$state['State'] = "UP";		
		}

		return $state;
	}


	/**
	* PRIVATE Method findStateService
	*
	* Returns the State for a single Service
	*
	* @param	string $hostName, string $serviceName
	* @return	array $state
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	*/
	function findStateService($hostName,$serviceName,$onlyHardStates) {
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE (objecttype_id = '2' AND name1 = binary '".$hostName."' AND name2 = binary '".$serviceName."' AND instance_id='".$this->dbInstanceId."')");
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			$state['State'] = "ERROR";
			$state['Output'] = $this->LANG->getMessageText('serviceNotFoundInDB','SERVICE~'.$serviceName.',HOST~'.$hostName);
			return($state);
		}
		$serviceObjectId = mysql_fetch_row($QUERYHANDLE);
		
		$queryString="SELECT has_been_checked, last_hard_state, current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."servicestatus WHERE service_object_id = '".$serviceObjectId[0]."' AND instance_id='".$this->dbInstanceId."'";
		

		$QUERYHANDLE = mysql_query($queryString);
		$service = mysql_fetch_array($QUERYHANDLE);				
		if($service['has_been_checked'] == 0) {
			$state['Count'] = 1;
			$state['State'] = 'UNKOWN';
			$state['Output'] = $this->LANG->getMessageText('serviceNotChecked','SERVICE~'.$serviceName);
		} elseif($service['current_state'] == 0) {
			$state['Count'] = 1;
			$state['State'] = 'OK';
			$state['Output'] = $service['output'];
		} elseif($service['problem_has_been_acknowledged'] == 1) {
			$state['Count'] = 1;
			$state['State'] = 'ACK';
			$state['Output'] = $service['output'];			
		} elseif($service['current_state'] == 1) {
			$state['Count'] = 1;
			$state['State'] = 'WARNING';
			$state['Output'] = $service['output'];		
		} elseif($service['current_state'] == 2) {
			$state['Count'] = 1;
			$state['State'] = 'CRITICAL';
			$state['Output'] = $service['output'];		
		} elseif($service['current_state'] == 3) {
			$state['Count'] = 1;
			$state['State'] = 'UNKNOWN';
			$state['Output'] = $service['output'];	
		} elseif($service['last_hard_state'] == 0 && $onlyHardStates == 1) {
			$state['Count'] = 1;
			$state['State'] = 'OK';
			$state['Output'] = $service['output'];
		}
		
		return $state;
	}


	/**
	* PRIVATE Method findStateServicegroup	
	*	
	* Returns the State for a Servicegroup
	*
	* @param	string $serviceGroupName
	* @return	arrray $state
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	*/
	function findStateServicegroup($serviceGroupName,$onlyHardStates) {
		//First we have to get the servicegroup_id
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE objecttype_id='4' AND name1 = binary '".$serviceGroupName."' AND instance_id='".$this->dbInstanceId."'");
		$objectId = mysql_fetch_row($QUERYHANDLE);
	
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			$state['State'] = "ERROR";
			$state['Output'] = $this->LANG->getMessageText('serviceGroupNotFoundInDB','SERVICEGROUP~'.$serviceGroupName);
			return($state);
		}
		
		$QUERYHANDLE = mysql_query("SELECT servicegroup_id FROM ".$this->dbPrefix."servicegroups WHERE servicegroup_object_id='".$objectId[0]."' AND instance_id='".$this->dbInstanceId."'");
		$serviceGroupId = mysql_fetch_row($QUERYHANDLE);
		
		$servicesCritical=0;
		$servicesWarning=0;
		$servicesUnknown=0;
		$servicesAck=0;
		$servicesOk=0;

		//Now we have the Group Id and can get the hosts
		$QUERYHANDLE = mysql_query("SELECT service_object_id FROM ".$this->dbPrefix."servicegroup_members WHERE servicegroup_id='".$serviceGroupId[0]."' AND instance_id='".$this->dbInstanceId."'");	
		while($services = mysql_fetch_array($QUERYHANDLE)) {
			$objectId = $services['service_object_id'];

			//Query the Servicestates
			if($onlyHardStates == 1) {
				$queryString="SELECT last_hard_state AS current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."servicestatus WHERE service_object_id = '".$objectId."' AND instance_id='".$this->dbInstanceId."'";
			} else {
				$queryString="SELECT current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."servicestatus WHERE service_object_id = '".$objectId."' AND instance_id='".$this->dbInstanceId."'";
			}	
			$QUERYHANDLE_2 = mysql_query($queryString);
			$currentService = mysql_fetch_array($QUERYHANDLE_2);				
			if($currentService['current_state'] == 0) {
				$servicesOk++;
			} elseif($currentService['problem_has_been_acknowledged'] == 1) {
				$servicesAck++;				
			} elseif($currentService['current_state'] == 1) {
				$servicesWarning++;
			} elseif($currentService['current_state'] == 2) {
				$servicesCritical++;
			} elseif($currentService['current_state'] == 3) {
				$servicesUnknown++;
			}
		}
	
		if($servicesCritical > 0) {
			$state['Count'] = $servicesCritical;
			$state['Output'] = $servicesCritical." CRITICAL, " .$servicesWarning. " WARNING and " .$servicesUnknown. " UNKNOWN Services";
			$state['State'] = 'CRITICAL';
		} elseif($servicesWarning > 0) {
			$state['Count'] = $servicesWarning;
			$state['Output'] = $servicesWarning. " WARNING and " .$servicesUnknown. " UNKNOWN Services";
			$state['State'] = 'WARNING';		
		} elseif($servicesUnknown > 0) {
			$state['Count'] = $servicesUnknown;
			$state['Output'] = $servicesUnknown." Services in UNKNOWN state";
			$state['State'] = 'UNKNOWN';
			
		} elseif($servicesAck > 0) {
			$state['Count'] = $servicesAck;
			$state['Output'] = $servicesAck." services are in a NON-OK State but all are ACKNOWLEDGED";
			$state['State'] = 'ACK';
		} elseif($servicesOk > 0) {
			$state['Count'] = $servicesOk;
			$state['Output'] = "All ".$servicesOk." services are OK";
			$state['State'] = 'OK';		
		}

		return $state;
	}
}
?>