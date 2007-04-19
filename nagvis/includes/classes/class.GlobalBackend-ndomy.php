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
	var $CONN;
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::GlobalBackendndomy($MAINCFG,'.$backendId.')');
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
		
		if($this->checkMysqlSupport() && $this->connectDB() && $this->checkTablesExists()) {
			// Set the instanceId
			$this->dbInstanceId = $this->getInstanceId();
			
			// Do some checks to make sure that Nagios is running and the Data at the DB are ok
			$QUERYHANDLE = mysql_query('SELECT is_currently_running, status_update_time FROM '.$this->dbPrefix.'programstatus WHERE instance_id='.$this->dbInstanceId);
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
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::GlobalBackendndomy(): FALSE');
			return FALSE;
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::GlobalBackendndomy(): TRUE');
		return TRUE;
	}
	
	/**
	 * PRIVATE Method checkTablesExists
	 *
	 * Checks if there are the wanted tables in the DB
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function checkTablesExists() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::checkTablesExists()');
		if(mysql_num_rows(mysql_query('SHOW TABLES LIKE \''.$this->dbPrefix.'programstatus\'')) == 0) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
			$FRONTEND->messageToUser('ERROR','noTablesExists','BACKENDID~'.$this->backendId.',PREFIX~'.$this->dbPrefix);
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkTablesExists(): FALSE');
			return FALSE;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkTablesExists(): TRUE');
			return TRUE;	
		}
	}
	
	/**
	 * PRIVATE Method connectDB
	 *
	 * Connects to DB
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function connectDB() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::connectDB()');
		// don't want to see mysql errors from connecting - only want our error messages
		$oldLevel = error_reporting(0);

		$this->CONN = mysql_connect($this->dbHost.':'.$this->dbPort, $this->dbUser, $this->dbPass);
		$returnCode = mysql_select_db($this->dbName, $this->CONN);
		
		if($returnCode != TRUE){
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
			$FRONTEND->messageToUser('ERROR','errorSelectingDb','BACKENDID~'.$this->backendId);
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::connectDB(): FALSE');
			return FALSE;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::connectDB(): TRUE');
			return TRUE;
		}
		
		// set the old level of reporting back
		error_reporting($oldLevel);
	}
	
	/**
	 * PRIVATE Method checkMysqlSupport
	 *
	 * Checks if MySQL is supported in this PHP version
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function checkMysqlSupport() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::checkMysqlSupport()');
		// Check availability of PHP MySQL
		if (!extension_loaded('mysql')) {
			dl('mysql.so');
			if (!extension_loaded('mysql')) {
				//Error Box
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
				$FRONTEND->messageToUser('ERROR','mysqlNotSupported','BACKENDID~'.$this->backendId);
				
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkMysqlSupport(): FALSE');
				return FALSE;
			} else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkMysqlSupport(): TRUE');
				return TRUE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkMysqlSupport(): TRUE');
			return TRUE;	
		}
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::getInstanceId()');
		$QUERYHANDLE = mysql_query('SELECT instance_id FROM '.$this->dbPrefix.'instances WHERE instance_name=\''.$this->dbInstanceName.'\' LIMIT 1');
		$ret = mysql_fetch_array($QUERYHANDLE);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::getInstanceId(): '.$ret['instance_id']);
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::getObjects('.$type.','.$name1Pattern.','.$name2Pattern.')');
		$ret = Array();
		$filter = '';
		
		switch($type) {
			case 'host':
				$objectType = 1;
				
				if($name1Pattern != '') {
					$filter = ' name1=\''.$name1Pattern.'\' AND ';
				}
			break;
			case 'service':
				$objectType = 2;
				
				if($name1Pattern != '') {
					$filter = ' name1=\''.$name1Pattern.'\' AND ';
				} elseif($name1Pattern != '' && $name2Pattern != '') {
					$filter = ' name1=\''.$name1Pattern.'\' AND name2=\''.$name2Pattern.'\' AND ';
				}
			break;
			case 'hostgroup':
				$objectType = 3;
				
				if($name1Pattern != '') {
					$filter = ' name1=\''.$name1Pattern.'\' AND ';
				}
			break;
			case 'servicegroup':
				$objectType = 4;
				
				if($name1Pattern != '') {
					$filter = ' name1=\''.$name1Pattern.'\' AND ';
				}
			break;
			default:
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::getObjects(): Array()');
				return Array();
			break;
		}
		
		$QUERYHANDLE = mysql_query('SELECT name1,name2 FROM '.$this->dbPrefix.'objects WHERE objecttype_id='.$objectType.' AND '.$filter.' is_active=1 AND instance_id='.$this->dbInstanceId.' ORDER BY name1');
		while($data = mysql_fetch_array($QUERYHANDLE)) {
			$ret[] = Array('name1' => $data['name1'],'name2' => $data['name2']);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::getObjects(): '.$ret);
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
	function checkStates($Type,$Name,$RecognizeServices,$ServiceName='',$onlyHardStates=0) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::checkStates('.$Type.','.$Name.','.$RecognizeServices.','.$ServiceName.','.$onlyHardStates.')');
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
					// Should normally never reach this
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
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkStates(): Array(...)');
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
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateHost('.$hostName.','.$recognizeServices.','.$onlyHardStates.')');
		$QUERYHANDLE = mysql_query('SELECT last_hard_state, UNIX_TIMESTAMP(last_hard_state_change) AS last_hard_state_change, UNIX_TIMESTAMP(last_state_change) AS last_state_change, current_state, output, problem_has_been_acknowledged 
		FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'hoststatus AS h 
		WHERE (o.objecttype_id=1 AND o.name1 = binary \''.$hostName.'\' AND o.instance_id='.$this->dbInstanceId.') AND h.host_object_id=o.object_id LIMIT 1');
		
		if(mysql_num_rows($QUERYHANDLE) == 0) {
			$state['State'] = 'ERROR';
			$state['Output'] = $this->LANG->getMessageText('hostNotFoundInDB','HOST~'.$hostName);
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateHost(): Array(..)');
			return $state;
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
			if($recognizeServices == 1 && $hostState == 'UP') {
				//Initialise Vars
				$servicesOk = 0;
				$servicesWarning = 0;
				$servicesCritical = 0;
				$servicesUnknown = 0;
				$servicesAck = 0;
				
				//Get the object ids from all services of this host
				$QUERYHANDLE = mysql_query('SELECT last_hard_state, 
													UNIX_TIMESTAMP(last_hard_state_change) AS last_hard_state_change, 
													UNIX_TIMESTAMP(last_state_change) AS last_state_change, 
													current_state, output, problem_has_been_acknowledged 
											FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'servicestatus AS s 
											WHERE (o.objecttype_id=2 AND o.name1=\''.$hostName.'\' AND o.instance_id='.$this->dbInstanceId.') AND s.service_object_id=o.object_id');
				while($serviceState = mysql_fetch_array($QUERYHANDLE)) {
					/**
					 * SF.net #1587073
					 * if "last_hard_state" != OK && last_hard_state_change <= last_state_change => state = current_state
					 *
					 * last_hard_state in ndo_hoststatus seems not to change if a host returns from State != OK by a successfull service check.
					 * i think it changes only if a host check returns an OK. so if there are no host checks scheduled the last_hard_state will 
					 * always stay as it is.
					 */
					if($onlyHardStates == 1) {
						if($serviceState['last_hard_state'] != '0' && $serviceState['last_hard_state_change'] <= $serviceState['last_state_change']) {
							// $serviceState['current_state'] = $serviceState['current_state'];
						} else {
							$serviceState['current_state'] = $serviceState['last_hard_state'];
						}
					}
					
					if($serviceState['current_state'] == 0) {
						$servicesOk++;
					} elseif($serviceState['problem_has_been_acknowledged'] == 1) {
						$servicesAck++;				
					} elseif($serviceState['current_state'] == 1) {
						$servicesWarning++;
					} elseif($serviceState['current_state'] == 2) {
						$servicesCritical++;
					} elseif($serviceState['current_state'] == 3) {
						$servicesUnknown++;
					}
				}
				
				if($servicesCritical > 0) {
					$state['Count'] = $servicesCritical;
					$state['Output'] = 'Host is UP but there are '.$servicesCritical.' CRITICAL, ' .$servicesWarning. ' WARNING and ' .$servicesUnknown. ' UNKNOWN Services';
					$state['State'] = 'CRITICAL';
				} elseif($servicesWarning > 0) {
					$state['Count'] = $servicesWarning;
					$state['Output'] = 'Host is UP but there are ' .$servicesWarning. ' WARNING and ' .$servicesUnknown. ' UNKNOWN Services';
					$state['State'] = 'WARNING';		
				} elseif($servicesUnknown > 0) {
					$state['Count'] = $servicesUnknown;
					$state['Output'] = 'Host is UP but there are '.$servicesUnknown.' Services in UNKNOWN state';
					$state['State'] = 'UNKNOWN';
				} elseif($servicesAck > 0) {
					$state['Count'] = $servicesAck;
					$state['Output'] = 'Host is UP but '.$servicesAck.' services are in a NON-OK State but all are ACKNOWLEDGED';
					$state['State'] = 'ACK';
				} elseif($servicesOk > 0) {
					$state['Count'] = $servicesOk;
					$state['Output'] = 'Host is UP and all '.$servicesOk.' services are OK';
					//This must be set before by the host, but to be consitend with the other ifs i define it again here:
					$state['State'] = 'UP';		
				}
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateHost(): Array(..)');
        return $state;
	}
	

	/**
	* PRIVATE Method findStateHostgroup
	*
	* Returns the State for a single Hostgroup 
	*
	* @param	string $hostGroupName, boolean $recognzieServices
	* @return	array $state
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	* @author	Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function findStateHostgroup($hostGroupName,$recognizeServices,$onlyHardStates) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateHostgroup('.$hostGroupName.','.$recognizeServices.','.$onlyHardStates.')');
		$hostsCritical = 0;
		$hostsWarning = 0;
		$hostsUnknown = 0;
		$hostsAck = 0;
		$hostsOk = 0;
		
		//First we have to get the hostgroup_id
		$QUERYHANDLE = mysql_query('SELECT h.hostgroup_id 
									FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'hostgroups AS h 
									WHERE (o.objecttype_id=3 AND o.name1 = binary \''.$hostGroupName.'\' AND o.instance_id='.$this->dbInstanceId.') 
											AND h.hostgroup_object_id=o.object_id LIMIT 1');
		
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			$state['State'] = 'ERROR';
			$state['Output'] = $this->LANG->getMessageText('hostGroupNotFoundInDB','HOSTGROUP~'.$hostGroupName);
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateHostgroup(): Array(..)');
			return $state;
		} else {
			$hostGroupId = mysql_fetch_row($QUERYHANDLE);
			
			//Now we have the Group Id and can get the hosts
			$QUERYHANDLE = mysql_query('SELECT o.name1 
										FROM '.$this->dbPrefix.'hostgroup_members AS h,'.$this->dbPrefix.'objects AS o 
										WHERE (h.hostgroup_id='.$hostGroupId[0].' AND h.instance_id='.$this->dbInstanceId.') 
												AND (o.objecttype_id=1 AND h.host_object_id=o.object_id)');	
			while($data = mysql_fetch_array($QUERYHANDLE)) {
				$currentHostState = $this->findStateHost($data['name1'],$recognizeServices,$onlyHardStates);
				if($currentHostState['State'] == 'UP') {
					$hostsOk++;
				} elseif($currentHostState['State'] == 'ACK') {
					$hostsAck++;				
				} elseif($currentHostState['State'] == 'WARNING') {
					$hostsWarning++;
				} elseif($currentHostState['State'] == 'DOWN' || $currentHostState['State'] == 'UNREACHABLE' || $currentHostState['State'] == 'CRITICAL') {
					$hostsCritical++;
				} elseif($currentHostState['State'] == 'UNKNOWN') {
					$hostsUnknown++;
				}
			}
		
			if($hostsCritical > 0) {
				$state['Count'] = $hostsCritical;
				$state['Output'] = $hostsCritical.' Hosts are CRITICAL, ' .$hostsWarning. ' WARNING and ' .$hostsUnknown. ' UNKNOWN';
				$state['State'] = 'CRITICAL';
			} elseif($hostsWarning > 0) {
				$state['Count'] = $hostsWarning;
				$state['Output'] = $hostsWarning. ' Hosts are WARNING and ' .$hostsUnknown. ' UNKNOWN';
				$state['State'] = 'WARNING';		
			} elseif($hostsUnknown > 0) {
				$state['Count'] = $hostsUnknown;
				$state['Output'] = $hostsUnknown.' are in UNKNOWN state';
				$state['State'] = 'UNKNOWN';
			} elseif($hostsAck > 0) {
				$state['Count'] = $hostsAck;
				$state['Output'] = $hostsAck.' Hosts are in a NON-OK State but all errors are ACKNOWLEDGED';
				$state['State'] = 'ACK';
			} elseif($hostsOk > 0) {
				$state['Count'] = $hostsOk;
				$state['Output'] = 'All ' .$hostsOk. ' Hosts are OK';
				//This must be set before by the host, but to be consitend with the other ifs i define it again here:
				$state['State'] = 'UP';		
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateHostgroup(): Array(..)');
			return $state;
		}
	}


	/**
	* PRIVATE Method findStateService
	*
	* Returns the State for a single Service
	*
	* @param	string $hostName, string $serviceName
	* @return	array $state
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	* @author	Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function findStateService($hostName,$serviceName,$onlyHardStates) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateService('.$hostName.','.$serviceName.','.$onlyHardStates.')');
		$QUERYHANDLE = mysql_query('SELECT has_been_checked, last_hard_state, current_state, output, problem_has_been_acknowledged FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'servicestatus AS s
		WHERE (o.objecttype_id=2 AND o.name1 = binary \''.$hostName.'\' AND o.name2 = binary \''.$serviceName.'\' AND o.instance_id='.$this->dbInstanceId.') AND s.service_object_id=o.object_id LIMIT 1');
		
		if(mysql_num_rows($QUERYHANDLE) == 0) {
			$state['State'] = 'ERROR';
			$state['Output'] = $this->LANG->getMessageText('serviceNotFoundInDB','SERVICE~'.$serviceName.',HOST~'.$hostName);
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateService(): Array()');
			return $state;
		} else {
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
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateService(): Array(...)');
			return $state;
		}
	}


	/**
	* PRIVATE Method findStateServicegroup	
	*	
	* Returns the State for a Servicegroup
	*
	* @param	string $serviceGroupName
	* @return	arrray $state
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	* @author	Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function findStateServicegroup($serviceGroupName,$onlyHardStates) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateServicegroup('.$serviceGroupName.','.$onlyHardStates.')');
		$objs = Array('critical'=>0,'warning'=>0,'unknown'=>0,'ack'=>0,'ok'=>0);
		
		//First we have to get the servicegroup_id
		$QUERYHANDLE = mysql_query('SELECT s.servicegroup_id 
									FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'servicegroups AS s 
									WHERE (o.objecttype_id=4 AND o.name1 = binary \''.$serviceGroupName.'\' AND o.instance_id='.$this->dbInstanceId.') 
											AND s.config_type=1 AND s.servicegroup_object_id=o.object_id');
		
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			$state['State'] = 'ERROR';
			$state['Output'] = $this->LANG->getMessageText('serviceGroupNotFoundInDB','SERVICEGROUP~'.$serviceGroupName);
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateServicegroup(): Array()');
			return $state;
		} else {
			$data = mysql_fetch_array($QUERYHANDLE);
			
			$QUERYHANDLE = mysql_query('SELECT o.name1, o.name2
											FROM '.$this->dbPrefix.'servicegroup_members AS h,'.$this->dbPrefix.'objects AS o 
											WHERE (h.servicegroup_id='.$data['servicegroup_id'].' AND h.instance_id='.$this->dbInstanceId.') 
													AND (o.objecttype_id=2 AND h.service_object_id=o.object_id)');	
			
			while($data1 = mysql_fetch_array($QUERYHANDLE)) {
				$currentState = $this->findStateService($data1['name1'],$data1['name2'],$onlyHardStates);
				
				if($currentState['State'] == 'OK') {
					$objs['ok']++;
				} elseif($currentState['State'] == 'ACK') {
					$objs['ack']++;			
				} elseif($currentState['State'] == 'WARNING') {
					$objs['warning']++;
				} elseif($currentHostState['State'] == 'CRITICAL') {
					$objs['critical']++;
				} elseif($currentState['State'] == 'UNKNOWN') {
					$objs['unknown']++;
				}
			}
		
			if($objs['critical'] > 0) {
				$state['Count'] = $objs['critical'];
				$state['Output'] = $objs['critical'].' CRITICAL, ' .$objs['warning']. ' WARNING and ' .$objs['unknown']. ' UNKNOWN Services';
				$state['State'] = 'CRITICAL';
			} elseif($objs['warning'] > 0) {
				$state['Count'] = $objs['warning'];
				$state['Output'] = $objs['warning']. ' WARNING and ' .$objs['unknown']. ' UNKNOWN Services';
				$state['State'] = 'WARNING';		
			} elseif($objs['unknown'] > 0) {
				$state['Count'] = $objs['unknown'];
				$state['Output'] = $objs['unknown'].' Services in UNKNOWN state';
				$state['State'] = 'UNKNOWN';
			} elseif($objs['ack'] > 0) {
				$state['Count'] = $objs['ack'];
				$state['Output'] = $objs['ack'].' services are in a NON-OK State but all are ACKNOWLEDGED';
				$state['State'] = 'ACK';
			} elseif($objs['ok'] > 0) {
				$state['Count'] = $objs['ok'];
				$state['Output'] = 'All '.$objs['ok'].' services are OK';
				$state['State'] = 'OK';		
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateServicegroup(): Array(...)');
			return $state;
		}
	}
}
?>