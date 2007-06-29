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
    // Objects
	var $MAINCFG;
	var $LANG;
	var $CONN;
	
	// DB vars
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
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT name1,name2 FROM '.$this->dbPrefix.'objects WHERE objecttype_id='.$objectType.' AND '.$filter.' is_active=1 AND instance_id='.$this->dbInstanceId.' ORDER BY name1');
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
	* @param	array &$obj
	* @return	array $state
	* @author	Lars Michelsen <lars@vertical-visions.de>
	* @author	Andreas Husch <downanup@nagios-wiki.de>
	*/
	function checkStates(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::checkStates(Array())');
		switch($obj['type']) {
			case 'host':
				$obj = array_merge($obj,$this->findStateHost($obj));
			break;
			case 'service':
				$obj = array_merge($obj,$this->findStateService($obj));
			break;
			case 'hostgroup':
				$obj = array_merge($obj,$this->findStateHostgroup($obj));
			break;
			case 'servicegroup':
				$obj = array_merge($obj,$this->findStateServicegroup($obj));
			break;
			default:
			    
			break;
		}
		/**
		 * Case that this Backend could not find any state for the given object
		 * This should normally never happen
		 */
		if(!isset($obj['state'])) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:global'));
			$FRONTEND->messageToUser('WARNING','noStateSet');
			$FRONTEND->printPage();
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::checkStates(): Array(...)');
		return $obj;
	}


	/**
	* PRIVATE Method findStateHost
	*
	* Returns the Nagios State for a single Host
	*
	* @param	array   $obj        Object Array
	* @return	array   $arrReturn  State Array
	* @author	Lars Michelsen <lars@vertical-visions.de>
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	*/
	function findStateHost(&$obj) {
	    $arrReturn = Array();
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateHost(Array())');
		$QUERYHANDLE = $this->mysqlQuery('SELECT last_hard_state, UNIX_TIMESTAMP(last_hard_state_change) AS last_hard_state_change, UNIX_TIMESTAMP(last_state_change) AS last_state_change, current_state, output, problem_has_been_acknowledged 
		FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'hoststatus AS h 
		WHERE (o.objecttype_id=1 AND o.name1 = binary \''.$obj['name'].'\' AND o.instance_id='.$this->dbInstanceId.') AND h.host_object_id=o.object_id LIMIT 1');
		
		if(mysql_num_rows($QUERYHANDLE) == 0) {
			$arrReturn['state'] = 'ERROR';
			$arrReturn['output'] = $this->LANG->getMessageText('hostNotFoundInDB','HOST~'.$hostName);
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
			if($obj['only_hard_states'] == 1) {
				if($data['last_hard_state'] != '0' && $data['last_hard_state_change'] <= $data['last_state_change']) {
					$data['current_state'] = $data['current_state'];
				} else {
					$data['current_state'] = $data['last_hard_state'];
				}
			}
			
			if ($data['current_state'] == '0') {
				// Host is UP
				$arrReturn['state'] = 'UP';
			} elseif ($data['current_state'] == '1' || $data['current_state'] == '2' || $data['current_state'] == '3') {
				// Host is DOWN/UNREACHABLE/UNKNOWN
				if($data['problem_has_been_acknowledged'] == 1) {
					$arrReturn['state'] = 'ACK';
				} else {
					switch($data['current_state']) {
						case '1': 
							$arrReturn['state'] = 'DOWN';
						break;
						case '2':
							$arrReturn['state'] = 'UNREACHABLE';
						break;
						case '3':
							$arrReturn['state'] = 'UNKNOWN';
						break;
					}
				}
			}
			$arrReturn['name'] = $obj['name'];
			$arrReturn['checkOutput'] = $data['output'];
			
			/**
			 * Check the Services of the Host if requested and the Host is UP (makes no sence if the host is DOWN ;-), 
			 * this also makes shure that a host ACK will automatically ACK all services.
			 */
			if($obj['recognize_services'] == 1 && $arrReturn['state'] == 'UP') {
			    $arrReturn['childs'] = $this->findStateService($obj);
			    $arrReturn['hostState'] = $arrReturn['state'];
				$arrReturn['state'] = $this->summarizeStates($arrReturn);
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateHost(): Array(...)');
        return $arrReturn;
	}
	
	/**
	* PRIVATE Method findStateService
	*
	* Returns the State for a single or multiple services
	*
	* @param	array   $obj        Object Array
	* @return	array   $arrReturn  State Array
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function findStateService(&$obj) {
	    $arrReturn = Array('global' => Array('OK' => 0, 'WARNING' => 0, 'CRITICAL' => 0, 'UNKNOWN' => 0, 'ACK' => 0));
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateService(Array())');
		
		if(isset($obj['service_description']) && $obj['service_description'] != '') {
    		$QUERYHANDLE = $this->mysqlQuery('SELECT name2, has_been_checked, last_hard_state, current_state, output, problem_has_been_acknowledged 
    		    FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'servicestatus AS s
    		    WHERE (o.objecttype_id=2 AND o.name1 = binary \''.$obj['name'].'\' AND o.name2 = binary \''.$obj['service_description'].'\' AND o.instance_id='.$this->dbInstanceId.') AND s.service_object_id=o.object_id LIMIT 1');
        } else {
            $QUERYHANDLE = $this->mysqlQuery('SELECT name2, has_been_checked, last_hard_state, current_state, output, problem_has_been_acknowledged 
    		    FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'servicestatus AS s
    		    WHERE (o.objecttype_id=2 AND o.name1 = binary \''.$obj['name'].'\' AND o.instance_id='.$this->dbInstanceId.') AND s.service_object_id=o.object_id');
        }
		
		$iResults = mysql_num_rows($QUERYHANDLE);
		
		if($iResults == 0) {
		    $arrReturn['name'] = $obj['service_description'];
			$arrReturn['state'] = 'ERROR';
			$arrReturn['checkOutput'] = $this->LANG->getMessageText('serviceNotFoundInDB','SERVICE~'.$obj['service_description'].',HOST~'.$obj['name']);
		} else {
			while($data = mysql_fetch_array($QUERYHANDLE)) {
			    $arrTmpReturn = Array();
		        $arrTmpReturn['name'] = $data['name2'];
    			if($data['has_been_checked'] == 0) {
    				$arrTmpReturn['state'] = 'UNKNOWN';
    				$arrTmpReturn['checkOutput'] = $this->LANG->getMessageText('serviceNotChecked','SERVICE~'.$obj['service_description']);
    			} elseif($data['current_state'] == 0) {
    				$arrTmpReturn['state'] = 'OK';
    				$arrTmpReturn['checkOutput'] = $data['output'];
    			} elseif($data['problem_has_been_acknowledged'] == 1) {
    				$arrTmpReturn['state'] = 'ACK';
    				$arrTmpReturn['checkOutput'] = $data['output'];			
    			} elseif($data['current_state'] == 1) {
    				$arrTmpReturn['state'] = 'WARNING';
    				$arrTmpReturn['checkOutput'] = $data['output'];		
    			} elseif($data['current_state'] == 2) {
    				$arrTmpReturn['state'] = 'CRITICAL';
    				$arrTmpReturn['checkOutput'] = $data['output'];		
    			} elseif($data['current_state'] == 3) {
    				$arrTmpReturn['state'] = 'UNKNOWN';
    				$arrTmpReturn['checkOutput'] = $data['output'];	
    			} elseif($data['last_hard_state'] == 0 && $obj['only_hard_states'] == 1) {
    				$arrTmpReturn['state'] = 'OK';
    				$arrTmpReturn['checkOutput'] = $data['output'];
    			}
    			
    			// If more than one service were found append the current to return array
    			if($iResults == 1) {
    			    $arrReturn = $arrTmpReturn;
    			} else {
    			    // Count status
    			    $arrReturn['global'][$arrTmpReturn['state']]++;
    			    // Assign actual dataset to return array
    			    $arrReturn[$arrTmpReturn['name']] = $arrTmpReturn;
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
	* @param	array   $obj        Object Array
	* @return	array   $arrReturn  State Array
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function findStateHostgroup(&$obj) {
	    $arrReturn = Array('childs' => Array('global' => Array('OK' => 0, 'UNKNOWN' => 0, 'ACK' => 0, 'WARNING' => 0, 'CRITICAL' => 0, 'ERROR' => 0, 'UP' => 0, 'DOWN' => 0, 'UNREACHABLE' => 0)));
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateHostgroup(Array(...))');
		
		//First we have to get the hostgroup_id
		$QUERYHANDLE = $this->mysqlQuery('SELECT h.hostgroup_id 
									FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'hostgroups AS h 
									WHERE (o.objecttype_id=3 AND o.name1 = binary \''.$obj['name'].'\' AND o.instance_id='.$this->dbInstanceId.') 
											AND h.hostgroup_object_id=o.object_id LIMIT 1');
		
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			$arrReturn['state'] = 'ERROR';
			$arrReturn['checkOutput'] = $this->LANG->getMessageText('hostGroupNotFoundInDB','HOSTGROUP~'.$obj['name']);
		} else {
			$data = mysql_fetch_row($QUERYHANDLE);
			
			// Get the hosts of the hostgroup
			$QUERYHANDLE = $this->mysqlQuery('SELECT o.name1 
										FROM '.$this->dbPrefix.'hostgroup_members AS h,'.$this->dbPrefix.'objects AS o 
										WHERE (h.hostgroup_id='.$data[0].' AND h.instance_id='.$this->dbInstanceId.') 
												AND (o.objecttype_id=1 AND h.host_object_id=o.object_id)');	
			while($data = mysql_fetch_array($QUERYHANDLE)) {
			    $arrHostObj = Array('name' => $data[0],'only_hard_states' => $obj['only_hard_states'],'recognize_services' => $obj['recognize_services']);
				// Get the states of all hosts in the hostgroup
				$arrReturn['childs'][$data[0]] = $this->findStateHost($arrHostObj);
				// Count status
    			$arrReturn['childs']['global'][$arrReturn['childs'][$data[0]]['state']]++;
			}
			// Summarize the states to get the state of the hostgroup
			$arrReturn['state'] = $this->summarizeStates($arrReturn);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateHostgroup(): Array(...)');
		return $arrReturn;
	}

	/**
	* PRIVATE Method findStateServicegroup	
	*	
	* Returns the State for a Servicegroup
	*
	* @param	array   $obj        Object Array
	* @return	array   $arrReturn  State Array
	* @author	Andreas Husch (downanup@nagios-wiki.de)
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function findStateServicegroup(&$obj) {
	    $arrReturn = Array('childs' => Array('global' => Array('OK' => 0, 'UNKNOWN' => 0, 'ACK' => 0, 'WARNING' => 0, 'CRITICAL' => 0, 'ERROR' => 0)));
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendndomy::findStateServicegroup(Array(...))');
		
		//First we have to get the servicegroup_id
		$QUERYHANDLE = $this->mysqlQuery('SELECT s.servicegroup_id 
									FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'servicegroups AS s 
									WHERE (o.objecttype_id=4 AND o.name1 = binary \''.$obj['name'].'\' AND o.instance_id='.$this->dbInstanceId.') 
											AND s.config_type=1 AND s.servicegroup_object_id=o.object_id');
		
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			$arrReturn['state'] = 'ERROR';
			$arrReturn['checkOutput'] = $this->LANG->getMessageText('serviceGroupNotFoundInDB','SERVICEGROUP~'.$obj['name']);
		} else {
			$data = mysql_fetch_array($QUERYHANDLE);
			
			$QUERYHANDLE = $this->mysqlQuery('SELECT o.name1, o.name2
											FROM '.$this->dbPrefix.'servicegroup_members AS h,'.$this->dbPrefix.'objects AS o 
											WHERE (h.servicegroup_id='.$data['servicegroup_id'].' AND h.instance_id='.$this->dbInstanceId.') 
													AND (o.objecttype_id=2 AND h.service_object_id=o.object_id)');	
			
			while($data1 = mysql_fetch_array($QUERYHANDLE)) {
				$arrServiceObj = Array('name' => $data1['name1'],'service_description' => $data1['name2'],'only_hard_states' => $obj['only_hard_states']);
				// Get the states of a service
				$arrReturn['childs'][] = $this->findStateService($arrServiceObj);
				// Count status
    			$arrReturn['childs']['global'][$arrReturn['childs'][(count($arrReturn['childs'])-2)]['state']]++;
			}
			// Summarize the states to get the state of the hostgroup
			$arrReturn['state'] = $this->summarizeStates($arrReturn);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendndomy::findStateServicegroup(): Array()');
		return $arrReturn;
	}
	
	/**
	 * Summarizes the state of the object and the childs
	 *
	 * @param	Array	Array with objects states
	 * @return	String	Object state (DOWN|CRITICAL|WARNING|UNKNOWN|ERROR)
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function summarizeStates(&$objState) {
	    $arrStates = Array();
	    if(isset($objState['state'])) {
            $arrStates[] = $objState['state'];
        }
        if(isset($objState['childs'])) {
	        if(isset($objState['childs']['global']['ERROR']) && $objState['childs']['global']['ERROR'] > 0) {
				$arrStates[] = 'ERROR';
			} elseif(isset($objState['childs']['global']['UNKNOWN']) && $objState['childs']['global']['UNKNOWN'] > 0) {
				$arrStates[] = 'UNKNOWN';
		    } if(isset($objState['childs']['global']['CRITICAL']) && $objState['childs']['global']['CRITICAL'] > 0) {
				$arrStates[] = 'CRITICAL';
			} elseif(isset($objState['childs']['global']['UNREACHABLE']) && $objState['childs']['global']['UNREACHABLE'] > 0) {
				$arrStates[] = 'UNREACHABLE';
		    } elseif(isset($objState['childs']['global']['DOWN']) && $objState['childs']['global']['DOWN'] > 0) {
		    	$arrStates[] = 'DOWN';
			} elseif(isset($objState['childs']['global']['WARNING']) && $objState['childs']['global']['WARNING'] > 0) {
				$arrStates[] = 'WARNING';
			} elseif(isset($objState['childs']['global']['ACK']) && $objState['childs']['global']['ACK'] > 0) {
				$arrStates[] = 'ACK';
			} elseif(isset($objState['childs']['global']['UP']) && $objState['childs']['global']['UP'] > 0) {
				$arrStates[] = 'UP';
			} elseif(isset($objState['childs']['global']['OK']) && $objState['childs']['global']['OK'] > 0) {
				$arrStates[] = 'OK';
			}
	    }
	    
	    // wrap only if there is sth. to wrap
	    if(count($arrStates) > 1) {
	        return $this->wrapState($arrStates);  
	    } else {
            return $arrStates[0];
	    }
	}
	
	/**
	 * Wraps all states in an Array to a summary state
	 *
	 * @param	Array	Array with objects states
	 * @return	String	Object state (DOWN|CRITICAL|WARNING|UNKNOWN|ERROR)
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function wrapState(&$objStates) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::wrapState(Array(...))');
		if(in_array('ERROR', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): ERROR');
			return 'ERROR';
		} elseif(in_array('UNKNOWN', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): UNKNOWN');
			return 'UNKNOWN';
		} elseif(in_array('CRITICAL', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): CRITICAL');
			return 'CRITICAL';
		} elseif(in_array('UNREACHABLE', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): UNREACHABLE');
			return 'UNREACHABLE';
		} elseif(in_array('DOWN', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): CRITICAL');
			return 'CRITICAL';
		} elseif(in_array('WARNING', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): WARNING');
			return 'WARNING';
		} elseif(in_array('ACK', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): ACK');
			return 'ACK';
		} elseif(in_array('UP', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): UP');
			return 'UP';
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): OK');
			return 'OK';
		}
	}
}
?>