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
	 * @author	Lars Michelsen <lars@vertical-visions.de>
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
			$QUERYHANDLE = $this->mysqlQuery('SELECT is_currently_running, status_update_time FROM '.$this->dbPrefix.'programstatus WHERE instance_id='.$this->dbInstanceId);
			$nagiosState = mysql_fetch_array($QUERYHANDLE);
			
			// Check that Nagios reports itself as running	
			if ($nagiosState['is_currently_running'] != 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
				$FRONTEND->messageToUser('ERROR','nagiosNotRunning','BACKENDID~'.$this->backendId);
			}
			 
			// Be suspiciosly and check that the data at the db are not older that "maxTimeWithoutUpdate" too
			if(time() - strtotime($nagiosState['status_update_time']) > $this->MAINCFG->getValue('backend_'.$backendId, 'maxtimewithoutupdate')) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
				$FRONTEND->messageToUser('ERROR','nagiosDataNotUpToDate','BACKENDID~'.$this->backendId.',TIMEWITHOUTUPDATE~'.$this->MAINCFG->getValue('backend_'.$backendId, 'maxtimewithoutupdate'));
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
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkTablesExists() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::checkTablesExists()');
		if(mysql_num_rows($this->mysqlQuery('SHOW TABLES LIKE \''.$this->dbPrefix.'programstatus\'')) == 0) {
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
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function connectDB() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::connectDB()');
		// don't want to see mysql errors from connecting - only want our error messages
		$oldLevel = error_reporting(0);
		
		$this->CONN = mysql_connect($this->dbHost.':'.$this->dbPort, $this->dbUser, $this->dbPass);
		$returnCode = mysql_select_db($this->dbName, $this->CONN);
		
		// set the old level of reporting back
		error_reporting($oldLevel);
		
		if(!$returnCode){
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:ndomy'));
			$FRONTEND->messageToUser('ERROR','errorSelectingDb','BACKENDID~'.$this->backendId.',MYSQLERR~'.mysql_error());
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::connectDB(): FALSE');
			return FALSE;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::connectDB(): TRUE');
			return TRUE;
		}
	}
	
	/**
	 * PRIVATE Method checkMysqlSupport
	 *
	 * Checks if MySQL is supported in this PHP version
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
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
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getInstanceId() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::getInstanceId()');
		$QUERYHANDLE = $this->mysqlQuery('SELECT instance_id FROM '.$this->dbPrefix.'instances WHERE instance_name=\''.$this->dbInstanceName.'\' LIMIT 1');
		$ret = mysql_fetch_array($QUERYHANDLE);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::getInstanceId(): '.$ret['instance_id']);
		return $ret['instance_id'];
	}
	
	/**
	 * PRIVATE Method mysqlQuery
	 *
	 * @param   String      MySQL Query
	 * @return	Handle      Query Handle
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function mysqlQuery($query) {
		return mysql_query($query,$this->CONN);
	}

	/**
	 * PUBLIC Method getObjects
	 * 
	 * Return the objects configured at Nagios wich are matching the given pattern. 
	 * This is needed for WUI, e.g. to populate drop down lists.
	 *
	 * @param	string $type, string $name1Pattern, string $name2Pattern
	 * @return	array $ret
	 * @author	Lars Michelsen <lars@vertical-visions.de>
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
		
		/**
		 * is_active default value is 0.
		 * When broker option is -1 or BROKER_RETENTION_DATA is activated the 
		 * current objects have is_active=1 and the deprecated object have 
		 * is_active=0. Workaround: Check if there is any is_active=1, then use the
		 * is_active filter.
		 *
		 * For details see:
		 * https://sourceforge.net/tracker/index.php?func=detail&aid=1839631&group_id=132019&atid=725179
		 * http://www.nagios-portal.de/forum/thread.php?postid=59971#post59971
		 */
		if($this->checkForIsActiveObjects()) {
			$isActiveFilter = ' is_active=1 AND';
		} else {
			$isActiveFilter = '';
		}
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT name1,name2 FROM '.$this->dbPrefix.'objects WHERE objecttype_id='.$objectType.' AND '.$filter.$isActiveFilter.' instance_id='.$this->dbInstanceId.' ORDER BY name1');
		while($data = mysql_fetch_array($QUERYHANDLE)) {
			$ret[] = Array('name1' => $data['name1'],'name2' => $data['name2']);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::getObjects(): '.$ret);
		return $ret;
	}
	
	/**
	 * PRIVATE Method checkIsActiveObjects
	 *
	 * Checks if there are some object records with is_active=1
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkForIsActiveObjects() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::checkForIsActiveObjects()');
		if(mysql_num_rows($this->mysqlQuery('SELECT object_id FROM '.$this->dbPrefix.'objects WHERE is_active=1')) > 0) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkForIsActiveObjects(): TRUE');
			return TRUE;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkForIsActiveObjects(): FALSE');
			return FALSE;	
		}
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
	function checkStates($type,$name,$recognizeServices,$serviceName='',$onlyHardStates=0) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::checkStates('.$type.','.$name.','.$recognizeServices.','.$serviceName.','.$onlyHardStates.')');
		if(isset($name)) {
			switch($type) {
				case 'host':
					$arrReturn = $this->findStateHost($name,$recognizeServices,$onlyHardStates);
				break;
				case 'service':
					$arrReturn = $this->findStateService($name,$serviceName,$onlyHardStates);
				break;
				case 'hostgroup':
					$arrReturn = $this->findStateHostgroup($name,$recognizeServices,$onlyHardStates);
				break;
				case 'servicegroup':
					$arrReturn = $this->findStateServicegroup($name,$onlyHardStates);
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
		if(!isset($arrReturn)) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:global'));
			$FRONTEND->messageToUser('WARNING','noStateSet');
			$FRONTEND->printPage();
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkStates(): Array(...)');
		return $arrReturn;
	}
	
	/**
	 * PRIVATE Method getHostAckByHostname
	 *
	 * Returns if a host state has been acknowledged. The method doesn't check
	 * if the host is in OK/DOWN, It only checks.the has_been_acknowledged flag.
	 *
	 * @param	string $hostName
	 * @return	bool $ack
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHostAckByHostname($hostName) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::getHostAckByHostname('.$hostName.')');
		$return = FALSE;
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT problem_has_been_acknowledged 
		FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'hoststatus AS h 
		WHERE (o.objecttype_id=1 AND o.name1 = binary \''.$hostName.'\' AND o.instance_id='.$this->dbInstanceId.') AND h.host_object_id=o.object_id LIMIT 1');
		
		$data = mysql_fetch_array($QUERYHANDLE);
		// It's unnessecary to check if the value is 0, everything not equal to 1 is FALSE
		if(isset($data['problem_has_been_acknowledged']) && $data['problem_has_been_acknowledged'] == '1') {
			if(DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::getHostAckByHostname(): TRUE');
			return TRUE;
		} else {
			if(DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::getHostAckByHostname(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * PRIVATE Method findStateHost
	 *
	 * Returns the Nagios State for a single Host
	 *
	 * @param	string $hostName
	 * @param	bool   $recognizeServices
	 * @param	bool   $onlyHardStates
	 * @return	array $state
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 * @author	Andreas Husch (downanup@nagios-wiki.de)
	 */
	function findStateHost($hostName,$recognizeServices,$onlyHardStates) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateHost('.$hostName.','.$recognizeServices.','.$onlyHardStates.')');
		$arrReturn = Array();
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT has_been_checked, last_hard_state, UNIX_TIMESTAMP(last_hard_state_change) AS last_hard_state_change, UNIX_TIMESTAMP(last_state_change) AS last_state_change, current_state, output, problem_has_been_acknowledged 
		FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'hoststatus AS h 
		WHERE (o.objecttype_id=1 AND o.name1 = binary \''.$hostName.'\' AND o.instance_id='.$this->dbInstanceId.') AND h.host_object_id=o.object_id LIMIT 1');
		
		if(mysql_num_rows($QUERYHANDLE) == 0) {
			$arrReturn['State'] = 'ERROR';
			$arrReturn['Output'] = $this->LANG->getMessageText('hostNotFoundInDB','HOST~'.$hostName);
		} else {
			$data = mysql_fetch_array($QUERYHANDLE);
			
			/**
			 * SF.net #1587073
			 * if "last_hard_state" != OK && last_hard_state_change <= last_state_change => state = current_state
			 *
			 * last_hard_state in ndo_hoststatus seems not to change if a host returns from State != OK by a successfull service check.
			 * i think it changes only if a host check returns an OK. so if there are no host checks scheduled the last_hard_state will 
			 * always stay as it is.
			 */
			if($onlyHardStates == 1) {
				if($data['last_hard_state'] != '0' && $data['last_hard_state_change'] <= $data['last_state_change']) {
					$data['current_state'] = $data['current_state'];
				} else {
					$data['current_state'] = $data['last_hard_state'];
				}
			}
			
			if($data['has_been_checked'] == '0') {
				$arrReturn['State'] = 'UNKNOWN';
				$arrReturn['Output'] = $this->LANG->getMessageText('hostIsPending','HOST~'.$hostName);
			} elseif($data['current_state'] == '0') {
				// Host is UP
				$arrReturn['State'] = 'UP';
				$arrReturn['Output'] = $data['output'];
				
				// Service recognition only makes sense if host is UP
				if($recognizeServices == 1) {
					$arrNumChilds = Array('OK' => 0, 'WARNING' => 0, 'CRITICAL' => 0, 'UNKNOWN' => 0, 'ACK' => 0);
					
					// Get states of all services on this host
					$arrServices = $this->findStateService($hostName,'',$onlyHardStates);
					
					// Count service states
					foreach($arrServices AS $arrService) {
						$arrNumChilds[$arrService['State']]++;
					}
					
					if($arrNumChilds['CRITICAL'] > 0) {
						$arrReturn['Count'] = $arrNumChilds['CRITICAL'];
						$arrReturn['Output'] = 'Host is UP but there are '.$arrNumChilds['CRITICAL'].' CRITICAL, ' .$arrNumChilds['WARNING']. ' WARNING and ' .$arrNumChilds['UNKNOWN']. ' UNKNOWN Services';
						$arrReturn['State'] = 'CRITICAL';
					} elseif($arrNumChilds['WARNING'] > 0) {
						$arrReturn['Count'] = $arrNumChilds['WARNING'];
						$arrReturn['Output'] = 'Host is UP but there are ' .$arrNumChilds['WARNING']. ' WARNING and ' .$arrNumChilds['UNKNOWN']. ' UNKNOWN Services';
						$arrReturn['State'] = 'WARNING';		
					} elseif($arrNumChilds['UNKNOWN'] > 0) {
						$arrReturn['Count'] = $arrNumChilds['UNKNOWN'];
						$arrReturn['Output'] = 'Host is UP but there are '.$arrNumChilds['UNKNOWN'].' Services in UNKNOWN state';
						$arrReturn['State'] = 'UNKNOWN';
					} elseif($arrNumChilds['ACK'] > 0) {
						$arrReturn['Count'] = $arrNumChilds['ACK'];
						$arrReturn['Output'] = 'Host is UP but '.$arrNumChilds['ACK'].' services are in a NON-OK State but all are ACKNOWLEDGED';
						$arrReturn['State'] = 'ACK';
					} elseif($arrNumChilds['OK'] > 0) {
						$arrReturn['Count'] = $arrNumChilds['OK'];
						$arrReturn['Output'] = 'Host is UP and all '.$arrNumChilds['OK'].' services are OK';
						$arrReturn['State'] = 'UP';		
					}
				}
			} else {
				// Host is DOWN/UNREACHABLE/UNKNOWN
				if($data['problem_has_been_acknowledged'] == 1) {
					$arrReturn['State'] = 'ACK';
					$arrReturn['Output'] = $data['output'];
				} else {
					switch($data['current_state']) {
						case '1': 
							$arrReturn['State'] = 'DOWN';
							$arrReturn['Output'] = $data['output'];
						break;
						case '2':
							$arrReturn['State'] = 'UNREACHABLE';
							$arrReturn['Output'] = $data['output'];
						break;
						case '3':
							$arrReturn['State'] = 'UNKNOWN';
							$arrReturn['Output'] = $data['output'];
						break;
						default:
							$arrReturn['State'] = 'UNKNOWN';
							$arrReturn['Output'] = 'GlobalBackendndomy::findStateHost: Undefined state!';
						break;
					}
				}
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateHost(): Array(..)');
		return $arrReturn;
	}
	
	/**
	 * PRIVATE Method findStateService
	 *
	 * Returns the State for a single Service
	 *
	 * @param	string $hostName
	 * @param	string $serviceName
	 * @param	bool   $onlyHardStates
	 * @return	array $state
	 * @author	Andreas Husch (downanup@nagios-wiki.de)
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function findStateService($hostName,$serviceName,$onlyHardStates) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateService('.$hostName.','.$serviceName.','.$onlyHardStates.')');
		$arrReturn = Array();
		
		if(isset($serviceName) && $serviceName != '') {
			$QUERYHANDLE = $this->mysqlQuery('SELECT name2, has_been_checked, last_hard_state, last_hard_state_change, current_state, last_state_change, output, problem_has_been_acknowledged 
				FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'servicestatus AS s
				WHERE (o.objecttype_id=2 AND o.name1 = binary \''.$hostName.'\' AND o.name2 = binary \''.$serviceName.'\' AND o.instance_id='.$this->dbInstanceId.') AND s.service_object_id=o.object_id LIMIT 1');
		} else {
			$QUERYHANDLE = $this->mysqlQuery('SELECT name2, has_been_checked, last_hard_state, last_hard_state_change, current_state, last_state_change, output, problem_has_been_acknowledged 
				FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'servicestatus AS s
				WHERE (o.objecttype_id=2 AND o.name1 = binary \''.$hostName.'\' AND o.instance_id='.$this->dbInstanceId.') AND s.service_object_id=o.object_id');
		}
		
		// Count results
		$iResults = mysql_num_rows($QUERYHANDLE);
		
		if($iResults == 0) {
			$arrReturn['State'] = 'ERROR';
			$arrReturn['Output'] = $this->LANG->getMessageText('serviceNotFoundInDB','SERVICE~'.$serviceName.',HOST~'.$hostName);
		} else {
			while($data = mysql_fetch_array($QUERYHANDLE)) {
				$arrTmpReturn = Array();
				
				if($onlyHardStates == 1) {
					if($data['last_hard_state'] != '0' && $data['last_hard_state_change'] <= $data['last_state_change']) {
						//$data['current_state'] = $data['current_state'];
					} else {
						$data['current_state'] = $data['last_hard_state'];
					}
				}
				
				/**
				 * If state is not OK (=> WARN, CRIT, UNKNOWN) and service is not 
				 * acknowledged => check for acknowledged host
				 */
				if($data['current_state'] > 0 && $data['problem_has_been_acknowledged'] != 1) {
					$data['problem_has_been_acknowledged'] = $this->getHostAckByHostname($hostName);
				}
				
				
				if($data['has_been_checked'] == 0) {
					$arrTmpReturn['Count'] = 1;
					$arrTmpReturn['State'] = 'UNKNOWN';
					$arrTmpReturn['Output'] = $this->LANG->getMessageText('serviceNotChecked','SERVICE~'.$data['name2']);
				} elseif($data['current_state'] == 0) {
					$arrTmpReturn['Count'] = 1;
					$arrTmpReturn['State'] = 'OK';
					$arrTmpReturn['Output'] = $data['output'];
				} elseif($data['problem_has_been_acknowledged'] == 1) {
					$arrTmpReturn['Count'] = 1;
					$arrTmpReturn['State'] = 'ACK';
					$arrTmpReturn['Output'] = $data['output'];
				} elseif($data['current_state'] == 1) {
					$arrTmpReturn['Count'] = 1;
					$arrTmpReturn['State'] = 'WARNING';
					$arrTmpReturn['Output'] = $data['output'];
				} elseif($data['current_state'] == 2) {
					$arrTmpReturn['Count'] = 1;
					$arrTmpReturn['State'] = 'CRITICAL';
					$arrTmpReturn['Output'] = $data['output'];
				} elseif($data['current_state'] == 3) {
					$arrTmpReturn['Count'] = 1;
					$arrTmpReturn['State'] = 'UNKNOWN';
					$arrTmpReturn['Output'] = $data['output'];
				} else {
					// Fallback: Undefined state
					$arrTmpReturn['Count'] = 1;
					$arrTmpReturn['State'] = 'UNKNOWN';
					$arrTmpReturn['Output'] = 'GlobalBackendndomy::findStateService: Undefined state!';
				}
				
				// If more than one service were found append the current to return array
				if($iResults == 1) {
						$arrReturn = $arrTmpReturn;
				} else {
						// Assign actual dataset to return array
						$arrReturn[$data['name2']] = $arrTmpReturn;
				}
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateService(): Array(...)');
		return $arrReturn;
	}

	/**
	 * PRIVATE Method findStateHostgroup
	 *
	 * Returns the State for a single Hostgroup 
	 *
	 * @param	string $hostGroupName
	 * @param	bool   $recognizeServices
	 * @param	bool   $onlyHardStates
	 * @return	array $state
	 * @author	Andreas Husch (downanup@nagios-wiki.de)
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function findStateHostgroup($hostGroupName,$recognizeServices,$onlyHardStates) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateHostgroup('.$hostGroupName.','.$recognizeServices.','.$onlyHardStates.')');
		$arrReturn = Array();
		$arrNumChilds = Array('OK' => 0, 'WARNING' => 0, 'CRITICAL' => 0, 'UNKNOWN' => 0, 'ACK' => 0);
		
		//First we have to get the hostgroup_id
		$QUERYHANDLE = $this->mysqlQuery('SELECT h.hostgroup_id 
			FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'hostgroups AS h 
			WHERE (o.objecttype_id=3 AND o.name1 = binary \''.$hostGroupName.'\' AND o.instance_id='.$this->dbInstanceId.') 
			AND h.hostgroup_object_id=o.object_id LIMIT 1');
		
		if(mysql_num_rows($QUERYHANDLE) == 0) {
			$arrReturn['State'] = 'ERROR';
			$arrReturn['Output'] = $this->LANG->getMessageText('hostGroupNotFoundInDB','HOSTGROUP~'.$hostGroupName);
		} else {
			$hostGroupId = mysql_fetch_row($QUERYHANDLE);
			
			//Now we have the Group Id and can get the hosts
			$QUERYHANDLE = $this->mysqlQuery('SELECT o.name1 
				FROM '.$this->dbPrefix.'hostgroup_members AS h,'.$this->dbPrefix.'objects AS o 
				WHERE (h.hostgroup_id='.$hostGroupId[0].' AND h.instance_id='.$this->dbInstanceId.') 
				AND (o.objecttype_id=1 AND h.host_object_id=o.object_id)');	
			while($data = mysql_fetch_array($QUERYHANDLE)) {
				// Get state of the current looping host
				$currentHostState = $this->findStateHost($data['name1'],$recognizeServices,$onlyHardStates);
				
				// Count state
				if($currentHostState['State'] == 'UP') {
					$arrNumChilds['OK']++;
				} elseif($currentHostState['State'] == 'DOWN' || $currentHostState['State'] == 'UNREACHABLE' || $currentHostState['State'] == 'CRITICAL') {
					$arrNumChilds['CRITICAL']++;
				} else {
					$arrNumChilds[$currentHostState['State']]++;
				}
			}
		
			if($arrNumChilds['CRITICAL'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['CRITICAL'];
				$arrReturn['Output'] = $arrNumChilds['CRITICAL'].' Hosts are CRITICAL, ' .$arrNumChilds['WARNING']. ' WARNING and ' .$arrNumChilds['UNKNOWN']. ' UNKNOWN';
				$arrReturn['State'] = 'CRITICAL';
			} elseif($arrNumChilds['WARNING'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['WARNING'];
				$arrReturn['Output'] = $arrNumChilds['WARNING']. ' Hosts are WARNING and ' .$arrNumChilds['UNKNOWN']. ' UNKNOWN';
				$arrReturn['State'] = 'WARNING';		
			} elseif($arrNumChilds['UNKNOWN'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['UNKNOWN'];
				$arrReturn['Output'] = $arrNumChilds['UNKNOWN'].' are in UNKNOWN state';
				$arrReturn['State'] = 'UNKNOWN';
			} elseif($arrNumChilds['ACK'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['ACK'];
				$arrReturn['Output'] = $arrNumChilds['ACK'].' Hosts are in a NON-OK State but all errors are ACKNOWLEDGED';
				$arrReturn['State'] = 'ACK';
			} elseif($arrNumChilds['OK'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['OK'];
				$arrReturn['Output'] = 'All ' .$arrNumChilds['OK']. ' Hosts are OK';
				$arrReturn['State'] = 'UP';		
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateHostgroup(): Array(..)');
		return $arrReturn;
	}

	/**
	 * PRIVATE Method findStateServicegroup	
	 *	
	 * Returns the State for a Servicegroup
	 *
	 * @param	string $serviceGroupName
	 * @param	bool   $onlyHardStates
	 * @return	arrray $state
	 * @author	Andreas Husch (downanup@nagios-wiki.de)
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function findStateServicegroup($serviceGroupName,$onlyHardStates) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateServicegroup('.$serviceGroupName.','.$onlyHardStates.')');
		$arrReturn = Array();
		$arrNumChilds = Array('OK' => 0, 'WARNING' => 0, 'CRITICAL' => 0, 'UNKNOWN' => 0, 'ACK' => 0);
		
		//First we have to get the servicegroup_id
		$QUERYHANDLE = $this->mysqlQuery('SELECT s.servicegroup_id 
			FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'servicegroups AS s 
			WHERE (o.objecttype_id=4 AND o.name1 = binary \''.$serviceGroupName.'\' AND o.instance_id='.$this->dbInstanceId.') 
			AND s.config_type=1 AND s.servicegroup_object_id=o.object_id');
		
		if(mysql_num_rows($QUERYHANDLE) == 0) {
			$arrReturn['State'] = 'ERROR';
			$arrReturn['Output'] = $this->LANG->getMessageText('serviceGroupNotFoundInDB','SERVICEGROUP~'.$serviceGroupName);
		} else {
			$data = mysql_fetch_array($QUERYHANDLE);
			
			$QUERYHANDLE = $this->mysqlQuery('SELECT o.name1, o.name2
				FROM '.$this->dbPrefix.'servicegroup_members AS h,'.$this->dbPrefix.'objects AS o 
				WHERE (h.servicegroup_id='.$data['servicegroup_id'].' AND h.instance_id='.$this->dbInstanceId.') 
				AND (o.objecttype_id=2 AND h.service_object_id=o.object_id)');	
			
			while($data1 = mysql_fetch_array($QUERYHANDLE)) {
				// Get service states
				$currentState = $this->findStateService($data1['name1'],$data1['name2'],$onlyHardStates);
				// Count state
				$arrNumChilds[$currentState['State']]++;
			}
			
			if($arrNumChilds['CRITICAL'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['CRITICAL'];
				$arrReturn['Output'] = $arrNumChilds['CRITICAL'].' CRITICAL, ' .$arrNumChilds['WARNING']. ' WARNING and ' .$arrNumChilds['UNKNOWN']. ' UNKNOWN Services';
				$arrReturn['State'] = 'CRITICAL';
			} elseif($arrNumChilds['WARNING'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['WARNING'];
				$arrReturn['Output'] = $arrNumChilds['WARNING']. ' WARNING and ' .$arrNumChilds['UNKNOWN']. ' UNKNOWN Services';
				$arrReturn['State'] = 'WARNING';		
			} elseif($arrNumChilds['UNKNOWN'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['UNKNOWN'];
				if($arrNumChilds['ACK'] == 1) {
					$arrReturn['Output'] = $arrNumChilds['UNKNOWN'].' Service in UNKNOWN state';
				} else {
					$arrReturn['Output'] = $arrNumChilds['UNKNOWN'].' Services in UNKNOWN state';
				}
				$arrReturn['State'] = 'UNKNOWN';
			} elseif($arrNumChilds['ACK'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['ACK'];
				if($arrNumChilds['ACK'] == 1) {
					$arrReturn['Output'] = $arrNumChilds['ACK'].' Service is in a NON-OK State but is ACKNOWLEDGED';
				} else {
					$arrReturn['Output'] = $arrNumChilds['ACK'].' Services are in a NON-OK State but all are ACKNOWLEDGED';
				}
				$arrReturn['State'] = 'ACK';
			} elseif($arrNumChilds['OK'] > 0) {
				$arrReturn['Count'] = $arrNumChilds['OK'];
				if($arrNumChilds['ACK'] == 1) {
					$arrReturn['Output'] = $arrNumChilds['OK'].' Service is OK';
				} else {
					$arrReturn['Output'] = 'All '.$arrNumChilds['OK'].' Services are OK';
				}
				$arrReturn['State'] = 'OK';
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateServicegroup(): Array(...)');
		return $arrReturn;
	}
}
?>