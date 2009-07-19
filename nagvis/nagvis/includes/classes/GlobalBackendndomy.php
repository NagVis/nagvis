<?php
/*****************************************************************************
 *
 * GlobalBackendndomy.php - backend class for handling object and state 
 *                           information stored in the NDO database.
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

class GlobalBackendndomy implements GlobalBackendInterface {
	private $CORE;
	private $CONN;
	private $backendId;
	private $dbName;
	private $dbUser;
	private $dbPass;
	private $dbHost;
	private $dbPrefix;
	private $dbInstanceName;
	private $dbInstanceId;
	private $objConfigType;
	
	private $hostCache;
	private $serviceCache;
	private $hostAckCache;
	
	// Define the backend local configuration options
	private static $validConfig = Array(
		'dbhost' => Array('must' => 1,
			'editable' => 1,
			'default' => 'localhost',
			'match' => MATCH_STRING_NO_SPACE),
		'dbport' => Array('must' => 0,
			'editable' => 1,
			'default' => '3306',
			'match' => MATCH_INTEGER),
		'dbname' => Array('must' => 1,
			'editable' => 1,
			'default' => 'nagios',
			'match' => MATCH_STRING_NO_SPACE),
		'dbuser' => Array('must' => 1,
			'editable' => 1,
			'default' => 'root',
			'match' => MATCH_STRING_NO_SPACE),
		'dbpass' => Array('must' => 0,
			'editable' => 1,
			'default' => '',
			'match' => MATCH_STRING_EMPTY),
		'dbprefix' => Array('must' => 0,
			'editable' => 1,
			'default' => 'nagios_',
			'match' => MATCH_STRING_NO_SPACE_EMPTY),
		'dbinstancename' => Array('must' => 0,
			'editable' => 1,
			'default' => 'default',
			'match' => MATCH_STRING_NO_SPACE),
		'maxtimewithoutupdate' => Array('must' => 0,
			'editable' => 1,
			'default' => '180',
			'match' => MATCH_INTEGER));
	
	/**
	 * Constructor
	 * Reads needed configuration parameters, connects to the Database
	 * and checks that Nagios is running
	 *
	 * @param	config $MAINCFG
	 * @param	String $backendId
	 * @author	Andreas Husch <downanup@nagios-wiki.de>
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $backendId) {
		$this->CORE = $CORE;
		
		$this->backendId = $backendId;
		
		$this->hostCache = Array();
		$this->serviceCache = Array();
		$this->hostAckCache = Array();
		
		$this->dbName = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'dbname');
		$this->dbUser = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'dbuser');
		$this->dbPass = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'dbpass');
		$this->dbHost = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'dbhost');
		$this->dbPort = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'dbport');
		$this->dbPrefix = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'dbprefix');
		$this->dbInstanceName = $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'dbinstancename');
		
		if($this->checkMysqlSupport() && $this->connectDB() && $this->checkTablesExists()) {
			// Set the instanceId
			$this->dbInstanceId = $this->getInstanceId();
			
			// Do some checks to make sure that Nagios is running and the Data at the DB is ok
			$QUERYHANDLE = $this->mysqlQuery('SELECT is_currently_running, UNIX_TIMESTAMP(status_update_time) AS status_update_time FROM '.$this->dbPrefix.'programstatus WHERE instance_id='.$this->dbInstanceId);
			$nagiosstate = mysql_fetch_array($QUERYHANDLE);
			
			// Free memory
			mysql_free_result($QUERYHANDLE);
			
			// Check that Nagios reports itself as running	
			if ($nagiosstate['is_currently_running'] != 1) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('nagiosNotRunning', Array('BACKENDID' =>$this->backendId)));
			}
			
			// Be suspicious and check that the data at the db is not older that "maxTimeWithoutUpdate" too
			if($_SERVER['REQUEST_TIME'] - $nagiosstate['status_update_time'] > $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'maxtimewithoutupdate')) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('nagiosDataNotUpToDate', Array('BACKENDID' => $this->backendId, 'TIMEWITHOUTUPDATE' => $this->CORE->MAINCFG->getValue('backend_'.$backendId, 'maxtimewithoutupdate'))));
			}
			
			/**
			 * It looks like there is a problem with the config_type value at some
			 * installations. The NDO docs and mailinglist say that the flag
			 * config_type marks the objects as being read from retention data or read
			 * from configuration. Until NagVis 1.3b3 only objects with config_type=1
			 * were queried.
			 * Cause of some problem reports that there are NO objects with
			 * config_type=1 in the DB this check was added. If there is at least one
			 * object with config_type=1 NagVis only recognizes objects with that
			 * value set. If there is no object with config_type=1 all objects with
			 * config_type=0 are recognized.
			 *
			 * http://www.nagios-portal.de/wbb/index.php?page=Thread&threadID=9269
			 */
			 if($this->checkConfigTypeObjects()) {
				 $this->objConfigType = 1;
			 } else {
				 $this->objConfigType = 0;
			 }
		} else {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * PRIVATE Method checkTablesExists
	 *
	 * Checks if the needed tables are in the DB
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkTablesExists() {
		if(mysql_num_rows($this->mysqlQuery('SHOW TABLES LIKE \''.$this->dbPrefix.'programstatus\'')) == 0) {
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('noTablesExists', Array('BACKENDID' => $this->backendId, 'PREFIX' => $this->dbPrefix)));
			return FALSE;
		} else {
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
	private function connectDB() {
		// don't want to see mysql errors from connecting - only want our error messages
		$oldLevel = error_reporting(0);
		
		$this->CONN = mysql_connect($this->dbHost.':'.$this->dbPort, $this->dbUser, $this->dbPass);
		
		if(!$this->CONN){
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('errorConnectingMySQL', Array('BACKENDID' => $this->backendId,'MYSQLERR' => mysql_error())));
			return FALSE;
		}
		
		$returnCode = mysql_select_db($this->dbName, $this->CONN);
		
		// set the old level of reporting back
		error_reporting($oldLevel);
		
		if(!$returnCode){
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('errorSelectingDb', Array('BACKENDID' => $this->backendId, 'MYSQLERR' => mysql_error($this->CONN))));
			return FALSE;
		} else {
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
	private function checkMysqlSupport() {
		// Check availability of PHP MySQL
		if (!extension_loaded('mysql')) {
			dl('mysql.so');
			if (!extension_loaded('mysql')) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('mysqlNotSupported', Array('BACKENDID' => $this->backendId)));
				return FALSE;
			} else {
				return TRUE;
			}
		} else {
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
	private function getInstanceId() {
		$intInstanceId = NULL;
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT instance_id FROM '.$this->dbPrefix.'instances WHERE instance_name=\''.$this->dbInstanceName.'\'');
		
		if(mysql_num_rows($QUERYHANDLE) == 1) {
			$ret = mysql_fetch_array($QUERYHANDLE);
			$intInstanceId = $ret['instance_id'];
		} elseif(mysql_num_rows($QUERYHANDLE) == 0) {
			// ERROR: Instance name not valid
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('backendInstanceNameNotValid', Array('BACKENDID' => $this->backendId, 'NAME' => $this->dbInstanceName)));
		} else {
			// ERROR: Given Instance name is not unique
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('backendInstanceNameNotUniq', Array('BACKENDID' => $this->backendId, 'NAME' => $this->dbInstanceName)));
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $intInstanceId;
	}
	
	/**
	 * PRIVATE Method mysqlQuery
	 *
	 * @param   String      MySQL Query
	 * @return	Handle      Query Handle
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function mysqlQuery($query) {
		$QUERYHANDLE = mysql_query($query, $this->CONN) or die(mysql_error());
		return $QUERYHANDLE;
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
	 * @author	Andreas Husch <downanup@nagios-wiki.de>
	 */
	public function getObjects($type,$name1Pattern='',$name2Pattern='') {
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
				
				if($name1Pattern != '' && $name2Pattern != '') {
					$filter = ' name1=\''.$name1Pattern.'\' AND name2=\''.$name2Pattern.'\' AND ';
				} else if($name1Pattern != '') {
					$filter = ' name1=\''.$name1Pattern.'\' AND ';
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
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $ret;
	}
	
	/**
	 * PRIVATE Method checkForIsActiveObjects
	 *
	 * Checks if there are some object records with is_active=1
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkForIsActiveObjects() {
		if(mysql_num_rows($this->mysqlQuery('SELECT object_id FROM '.$this->dbPrefix.'objects WHERE is_active=1')) > 0) {
			return TRUE;
		} else {
			return FALSE;	
		}
	}
	
	/**
	 * PRIVATE Method checkConfigTypeObjects
	 *
	 * Checks if there are some object records with config_type=1
	 *
	 * @return	Boolean
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkConfigTypeObjects() {
		if(mysql_num_rows($this->mysqlQuery('SELECT host_id FROM '.$this->dbPrefix.'hosts WHERE config_type=1 AND instance_id='.$this->dbInstanceId.' LIMIT 1')) > 0) {
			return TRUE;
		} else {
			return FALSE;	
		}
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
			$QUERYHANDLE = $this->mysqlQuery('SELECT problem_has_been_acknowledged 
			FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'hoststatus AS h 
			WHERE (o.objecttype_id=1 AND o.name1 = binary \''.$hostName.'\' AND o.instance_id='.$this->dbInstanceId.') AND h.host_object_id=o.object_id LIMIT 1');
			
			$data = mysql_fetch_array($QUERYHANDLE);
			
			// Free memory
			mysql_free_result($QUERYHANDLE);
			
			// It's unnessecary to check if the value is 0, everything not equal to 1 is FALSE
			if(isset($data['problem_has_been_acknowledged']) && $data['problem_has_been_acknowledged'] == '1') {
				$return = TRUE;
			} else {
				$return = FALSE;
			}
			
			// Save to cache
			$this->hostAckCache[$hostName] = $return;
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
			
			$QUERYHANDLE = $this->mysqlQuery('SELECT 
				o.object_id, alias, display_name, address, 
				has_been_checked, 
				last_hard_state, 
				UNIX_TIMESTAMP(last_hard_state_change) AS last_hard_state_change, 
				UNIX_TIMESTAMP(last_state_change) AS last_state_change, 
				current_state, 
				output, perfdata, 
				h.notes, 
				problem_has_been_acknowledged, 
				statusmap_image, 
				UNIX_TIMESTAMP(last_check) AS last_check, UNIX_TIMESTAMP(next_check) AS next_check, 
				hs.state_type, hs.current_check_attempt, hs.max_check_attempts, 
				UNIX_TIMESTAMP(dh.scheduled_start_time) AS downtime_start, UNIX_TIMESTAMP(dh.scheduled_end_time) AS downtime_end, 
				dh.author_name AS downtime_author, dh.comment_data AS downtime_data
			FROM 
				'.$this->dbPrefix.'hosts AS h, 
				'.$this->dbPrefix.'objects AS o 
			LEFT JOIN
				'.$this->dbPrefix.'hoststatus AS hs
				ON hs.host_object_id=o.object_id
			LEFT JOIN
				'.$this->dbPrefix.'scheduleddowntime AS dh
				ON dh.object_id=o.object_id AND NOW()>dh.scheduled_start_time AND NOW()<dh.scheduled_end_time
			WHERE 
				(o.objecttype_id=1 AND o.name1 = binary \''.$hostName.'\' AND o.instance_id='.$this->dbInstanceId.') 
				AND (h.config_type='.$this->objConfigType.' AND h.instance_id='.$this->dbInstanceId.' AND h.host_object_id=o.object_id) 
			LIMIT 1');
			
			if(mysql_num_rows($QUERYHANDLE) == 0) {
				$arrReturn['state'] = 'ERROR';
				$arrReturn['output'] = $this->CORE->LANG->getText('hostNotFoundInDB', Array('BACKENDID' => $this->backendId, 'HOST' => $hostName));
			} else {
				$data = mysql_fetch_array($QUERYHANDLE);
				
				// Free memory
				mysql_free_result($QUERYHANDLE);
				
				$arrReturn['alias'] = $data['alias'];
				$arrReturn['display_name'] = $data['display_name'];
				$arrReturn['address'] = $data['address'];
				$arrReturn['statusmap_image'] = $data['statusmap_image'];
				$arrReturn['notes'] = $data['notes'];
				
				// Add Additional information to array
				$arrReturn['perfdata'] = $data['perfdata'];
				$arrReturn['last_check'] = $data['last_check'];
				$arrReturn['next_check'] = $data['next_check'];
				$arrReturn['state_type'] = $data['state_type'];
				$arrReturn['current_check_attempt'] = $data['current_check_attempt'];
				$arrReturn['max_check_attempts'] = $data['max_check_attempts'];
				$arrReturn['last_state_change'] = $data['last_state_change'];
				$arrReturn['last_hard_state_change'] = $data['last_hard_state_change'];
				
				// If there is a downtime for this object, save the data
				if(isset($data['downtime_start']) && $data['downtime_start'] != '') {
					$arrReturn['in_downtime'] = 1;
					$arrReturn['downtime_start'] = $data['downtime_start'];
					$arrReturn['downtime_end'] = $data['downtime_end'];
					$arrReturn['downtime_author'] = $data['downtime_author'];
					$arrReturn['downtime_data'] = $data['downtime_data'];
				}
				
				/**
				 * Only recognize hard states. There was a discussion about the implementation
				 * This is a new handling of only_hard_states. For more details, see: 
				 * http://www.nagios-portal.de/wbb/index.php?page=Thread&threadID=8524
				 *
				 * Thanks to Andurin and fredy82
				 */
				if($onlyHardstates == 1) {
					if($data['state_type'] != '0') {
						$data['current_state'] = $data['current_state'];
					} else {
						$data['current_state'] = $data['last_hard_state'];
					}
				}
				
				if($data['has_been_checked'] == '0' || $data['current_state'] == '') {
					$arrReturn['state'] = 'PENDING';
					$arrReturn['output'] = $this->CORE->LANG->getText('hostIsPending', Array('HOST' => $hostName));
				} elseif($data['current_state'] == '0') {
					// Host is UP
					$arrReturn['state'] = 'UP';
					$arrReturn['output'] = $data['output'];
				} else {
					// Host is DOWN/UNREACHABLE/UNKNOWN
					
					// Store acknowledgement state in array
					$arrReturn['problem_has_been_acknowledged'] = $data['problem_has_been_acknowledged'];
					
					// Save to host ack cache
					$this->hostAckCache[$hostName] = $data['problem_has_been_acknowledged'];
					
					// Store state and output in array
					switch($data['current_state']) {
						case '1': 
							$arrReturn['state'] = 'DOWN';
							$arrReturn['output'] = $data['output'];
						break;
						case '2':
							$arrReturn['state'] = 'UNREACHABLE';
							$arrReturn['output'] = $data['output'];
						break;
						case '3':
							$arrReturn['state'] = 'UNKNOWN';
							$arrReturn['output'] = $data['output'];
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
			
			if(isset($serviceName) && $serviceName != '') {
				$QUERYHANDLE = $this->mysqlQuery('SELECT 
					o.object_id, o.name1, o.name2, 
					s.display_name, 
					s.notes, 
					h.address, 
					ss.has_been_checked, ss.last_hard_state, ss.current_state, 
					UNIX_TIMESTAMP(ss.last_hard_state_change) AS last_hard_state_change, 
					UNIX_TIMESTAMP(ss.last_state_change) AS last_state_change, 
					ss.output, ss.perfdata, ss.problem_has_been_acknowledged, 
					UNIX_TIMESTAMP(ss.last_check) AS last_check, UNIX_TIMESTAMP(ss.next_check) AS next_check, 
					ss.state_type, ss.current_check_attempt, ss.max_check_attempts,
					UNIX_TIMESTAMP(dh.scheduled_start_time) AS downtime_start, UNIX_TIMESTAMP(dh.scheduled_end_time) AS downtime_end, 
					dh.author_name AS downtime_author, dh.comment_data AS downtime_data
					FROM 
						'.$this->dbPrefix.'services AS s,
						'.$this->dbPrefix.'hosts AS h,
						'.$this->dbPrefix.'objects AS o
					LEFT JOIN
						'.$this->dbPrefix.'servicestatus AS ss
						ON ss.service_object_id=o.object_id
					LEFT JOIN
						'.$this->dbPrefix.'scheduleddowntime AS dh
						ON dh.object_id=o.object_id AND NOW()>dh.scheduled_start_time AND NOW()<dh.scheduled_end_time
					WHERE 
						(o.objecttype_id=2 AND o.name1 = binary \''.$hostName.'\' AND o.name2 = binary \''.$serviceName.'\' AND o.instance_id='.$this->dbInstanceId.')
						AND (s.config_type='.$this->objConfigType.' AND s.instance_id='.$this->dbInstanceId.' AND s.service_object_id=o.object_id)
                        AND (h.config_type='.$this->objConfigType.' AND h.instance_id='.$this->dbInstanceId.' AND h.host_object_id=s.host_object_id)
						AND ss.service_object_id=o.object_id 
					LIMIT 1');
			} else {
				$QUERYHANDLE = $this->mysqlQuery('SELECT 
					o.object_id, o.name1, o.name2,
					s.display_name, 
					s.notes, 
					h.address, 
					ss.has_been_checked, ss.last_hard_state, ss.current_state, 
					UNIX_TIMESTAMP(ss.last_hard_state_change) AS last_hard_state_change, 
					UNIX_TIMESTAMP(ss.last_state_change) AS last_state_change, 
					ss.output, ss.perfdata, ss.problem_has_been_acknowledged, 
					UNIX_TIMESTAMP(ss.last_check) AS last_check, UNIX_TIMESTAMP(ss.next_check) AS next_check, 
					ss.state_type, ss.current_check_attempt, ss.max_check_attempts,
					UNIX_TIMESTAMP(dh.scheduled_start_time) AS downtime_start, UNIX_TIMESTAMP(dh.scheduled_end_time) AS downtime_end, 
					dh.author_name AS downtime_author, dh.comment_data AS downtime_data
					FROM 
						'.$this->dbPrefix.'services AS s,
						'.$this->dbPrefix.'hosts AS h,
						'.$this->dbPrefix.'objects AS o
					LEFT JOIN
						'.$this->dbPrefix.'servicestatus AS ss
						ON ss.service_object_id=o.object_id
					LEFT JOIN
						'.$this->dbPrefix.'scheduleddowntime AS dh
						ON dh.object_id=o.object_id AND NOW()>dh.scheduled_start_time AND NOW()<dh.scheduled_end_time
					WHERE 
						(o.objecttype_id=2 AND o.name1 = binary \''.$hostName.'\' AND o.instance_id='.$this->dbInstanceId.') 
						AND (s.config_type='.$this->objConfigType.' AND s.instance_id='.$this->dbInstanceId.' AND s.service_object_id=o.object_id) 
                        AND (h.config_type='.$this->objConfigType.' AND h.instance_id='.$this->dbInstanceId.' AND h.host_object_id=s.host_object_id)
						');
			}
			
			if(mysql_num_rows($QUERYHANDLE) == 0) {
				if(isset($serviceName) && $serviceName != '') {
					$arrReturn['state'] = 'ERROR';
					$arrReturn['output'] = $this->CORE->LANG->getText('serviceNotFoundInDB', Array('BACKENDID' => $this->backendId, 'SERVICE' => $serviceName, 'HOST' => $hostName));
				} else {
					// If the method should fetch all services of the host and does not find
					// any services for this host, don't return anything => The message
					// that the host has no services is added by the frontend
				}
			} else {
				while($data = mysql_fetch_array($QUERYHANDLE)) {
					$arrTmpReturn = Array();
					
					$arrTmpReturn['service_description'] = $data['name2'];
					$arrTmpReturn['display_name'] = $data['display_name'];
					$arrTmpReturn['alias'] = $data['display_name'];
					$arrTmpReturn['address'] = $data['address'];
					$arrTmpReturn['notes'] = $data['notes'];
					
					// Add additional information to array
					$arrTmpReturn['perfdata'] = $data['perfdata'];
					$arrTmpReturn['last_check'] = $data['last_check'];
					$arrTmpReturn['next_check'] = $data['next_check'];
					$arrTmpReturn['state_type'] = $data['state_type'];
					$arrTmpReturn['current_check_attempt'] = $data['current_check_attempt'];
					$arrTmpReturn['max_check_attempts'] = $data['max_check_attempts'];
					$arrTmpReturn['last_state_change'] = $data['last_state_change'];
					$arrTmpReturn['last_hard_state_change'] = $data['last_hard_state_change'];
					
					// If there is a downtime for this object, save the data
					if(isset($data['downtime_start']) && $data['downtime_start'] != '') {
						$arrTmpReturn['in_downtime'] = 1;
						$arrTmpReturn['downtime_start'] = $data['downtime_start'];
						$arrTmpReturn['downtime_end'] = $data['downtime_end'];
						$arrTmpReturn['downtime_author'] = $data['downtime_author'];
						$arrTmpReturn['downtime_data'] = $data['downtime_data'];
					}
					
					/**
					 * Only recognize hard states. There was a discussion about the implementation
					 * This is a new handling of only_hard_states. For more details, see: 
					 * http://www.nagios-portal.de/wbb/index.php?page=Thread&threadID=8524
					 *
					 * Thanks to Andurin and fredy82
					 */
					if($onlyHardstates == 1) {
						if($data['state_type'] != '0') {
							$data['current_state'] = $data['current_state'];
						} else {
							$data['current_state'] = $data['last_hard_state'];
						}
					}
					
					if($data['has_been_checked'] == '0' || $data['current_state'] == '') {
						$arrTmpReturn['state'] = 'PENDING';
						$arrTmpReturn['output'] = $this->CORE->LANG->getText('serviceNotChecked', Array('SERVICE' => $data['name2']));
					} elseif($data['current_state'] == '0') {
						// Host is UP
						$arrTmpReturn['state'] = 'OK';
						$arrTmpReturn['output'] = $data['output'];
					} else {
						// Host is DOWN/UNREACHABLE/UNKNOWN
						
						/**
						 * If state is not OK (=> WARN, CRIT, UNKNOWN) and service is not 
						 * acknowledged => check for acknowledged host
						 */
						if($data['problem_has_been_acknowledged'] != 1) {
							$arrTmpReturn['problem_has_been_acknowledged'] = $this->getHostAckByHostname($hostName);
						} else {
							$arrTmpReturn['problem_has_been_acknowledged'] = $data['problem_has_been_acknowledged'];
						}
						
						// Store state and output in array
						switch($data['current_state']) {
							case '1': 
								$arrTmpReturn['state'] = 'WARNING';
								$arrTmpReturn['output'] = $data['output'];
							break;
							case '2':
								$arrTmpReturn['state'] = 'CRITICAL';
								$arrTmpReturn['output'] = $data['output'];
							break;
							case '3':
								$arrTmpReturn['state'] = 'UNKNOWN';
								$arrTmpReturn['output'] = $data['output'];
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
						$arrReturn[strtr($data['name2'],' ','_')] = $arrTmpReturn;
					}
				}
			}
			
			// Free memory
			mysql_free_result($QUERYHANDLE);
			
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
		$arrReturn = Array();
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT o1.name1
		FROM
		`'.$this->dbPrefix.'objects` AS o1,
		`'.$this->dbPrefix.'hosts` AS h1
		LEFT OUTER JOIN `'.$this->dbPrefix.'host_parenthosts` AS ph1 ON h1.host_id=ph1.host_id
		WHERE o1.objecttype_id=1
		AND (h1.config_type='.$this->objConfigType.' AND h1.instance_id='.$this->dbInstanceId.' AND h1.host_object_id=o1.object_id) 
		AND ph1.parent_host_object_id IS null');
		
		while($data = mysql_fetch_array($QUERYHANDLE)) {
			$arrReturn[] = $data['name1'];
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $arrReturn;
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
		$arrChildNames = Array();
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT o2.name1
		FROM
		`'.$this->dbPrefix.'objects` AS o1,
		`'.$this->dbPrefix.'hosts` AS h1,
		`'.$this->dbPrefix.'host_parenthosts` AS ph1,
		`'.$this->dbPrefix.'hosts` AS h2,
		`'.$this->dbPrefix.'objects` AS o2
		WHERE o1.objecttype_id=1 AND o1.name1=\''.$hostName.'\'
		AND (h1.config_type='.$this->objConfigType.' AND h1.instance_id='.$this->dbInstanceId.' AND h1.host_object_id=o1.object_id)
		AND o1.object_id=ph1.parent_host_object_id
		AND (h2.config_type='.$this->objConfigType.' AND h2.instance_id='.$this->dbInstanceId.' AND h2.host_id=ph1.host_id)
		AND o2.objecttype_id=1 AND h2.host_object_id=o2.object_id');
		while($data = mysql_fetch_array($QUERYHANDLE)) {
			$arrChildNames[] = $data['name1'];
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $arrChildNames;
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
		$arrReturn = Array();
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT 
				o2.name1
			FROM 
				'.$this->dbPrefix.'objects AS o,
				'.$this->dbPrefix.'hostgroups AS hg,
				'.$this->dbPrefix.'hostgroup_members AS hgm,
				'.$this->dbPrefix.'objects AS o2
			WHERE 
				(o.objecttype_id=3 AND o.name1 = binary \''.$hostgroupName.'\' AND o.instance_id='.$this->dbInstanceId.') 
				AND (hg.config_type='.$this->objConfigType.' AND hg.instance_id='.$this->dbInstanceId.' AND hg.hostgroup_object_id=o.object_id) 
				AND hgm.hostgroup_id=hg.hostgroup_id 
				AND (o2.objecttype_id=1 AND o2.object_id=hgm.host_object_id)');
		
		while($data = mysql_fetch_array($QUERYHANDLE)) {
			// Assign actual dataset to return array
			$arrReturn[] = $data['name1'];
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $arrReturn;
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
		$arrReturn = Array();
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT 
				o2.name1, o2.name2 
			FROM 
				'.$this->dbPrefix.'objects AS o,
				'.$this->dbPrefix.'servicegroups AS sg,
				'.$this->dbPrefix.'servicegroup_members AS sgm,
				'.$this->dbPrefix.'objects AS o2
			WHERE 
				(o.objecttype_id=4 AND o.name1 = binary \''.$servicegroupName.'\' AND o.instance_id='.$this->dbInstanceId.') 
				AND (sg.config_type='.$this->objConfigType.' AND sg.instance_id='.$this->dbInstanceId.' AND sg.servicegroup_object_id=o.object_id) 
				AND sgm.servicegroup_id=sg.servicegroup_id 
				AND (o2.objecttype_id=2 AND o2.object_id=sgm.service_object_id)');
	
		while($data = mysql_fetch_array($QUERYHANDLE)) {
			// Assign actual dataset to return array
			$arrReturn[] = Array('host_name' => $data['name1'], 'service_description' => $data['name2']);
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $arrReturn;
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
		$arrReturn = Array();
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT 
				o.object_id, sg.alias
			FROM 
				'.$this->dbPrefix.'objects AS o,
				'.$this->dbPrefix.'servicegroups AS sg
			WHERE 
				(o.objecttype_id=4 AND o.name1 = binary \''.$servicegroupName.'\' AND o.instance_id='.$this->dbInstanceId.') 
				AND (sg.config_type='.$this->objConfigType.' AND sg.instance_id='.$this->dbInstanceId.' AND sg.servicegroup_object_id=o.object_id)
				LIMIT 1');
		
		$data = mysql_fetch_array($QUERYHANDLE);
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		$arrReturn['alias'] = $data['alias'];
		
		return $arrReturn;
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
	public function getHostgroupInformations($groupName) {
		$arrReturn = Array();
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT 
				o.object_id, g.alias
			FROM 
				'.$this->dbPrefix.'objects AS o,
				'.$this->dbPrefix.'hostgroups AS g
			WHERE 
				(o.objecttype_id=3 AND o.name1 = binary \''.$groupName.'\' AND o.instance_id='.$this->dbInstanceId.') 
				AND (g.config_type='.$this->objConfigType.' AND g.instance_id='.$this->dbInstanceId.' AND g.hostgroup_object_id=o.object_id)
				LIMIT 1');
		
		$data = mysql_fetch_array($QUERYHANDLE);
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		$arrReturn['alias'] = $data['alias'];
		
		return $arrReturn;
	}
}
?>
