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
		
		$this->dbName = $this->CORE->getMainCfg()->getValue('backend_'.$backendId, 'dbname');
		$this->dbUser = $this->CORE->getMainCfg()->getValue('backend_'.$backendId, 'dbuser');
		$this->dbPass = $this->CORE->getMainCfg()->getValue('backend_'.$backendId, 'dbpass');
		$this->dbHost = $this->CORE->getMainCfg()->getValue('backend_'.$backendId, 'dbhost');
		$this->dbPort = $this->CORE->getMainCfg()->getValue('backend_'.$backendId, 'dbport');
		$this->dbPrefix = $this->CORE->getMainCfg()->getValue('backend_'.$backendId, 'dbprefix');
		$this->dbInstanceName = $this->CORE->getMainCfg()->getValue('backend_'.$backendId, 'dbinstancename');
		
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
				throw new BackendConnectionProblem($this->CORE->getLang()->getText('nagiosNotRunning', Array('BACKENDID' =>$this->backendId)));
			}
			
			// Be suspicious and check that the data at the db is not older that "maxTimeWithoutUpdate" too
			if($_SERVER['REQUEST_TIME'] - $nagiosstate['status_update_time'] > $this->CORE->getMainCfg()->getValue('backend_'.$backendId, 'maxtimewithoutupdate')) {
				throw new BackendConnectionProblem($this->CORE->getLang()->getText('nagiosDataNotUpToDate', Array('BACKENDID' => $this->backendId, 'TIMEWITHOUTUPDATE' => $this->CORE->getMainCfg()->getValue('backend_'.$backendId, 'maxtimewithoutupdate'))));
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
			throw new BackendConnectionProblem($this->CORE->getLang()->getText('noTablesExists', Array('BACKENDID' => $this->backendId, 'PREFIX' => $this->dbPrefix)));
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
			throw new BackendConnectionProblem($this->CORE->getLang()->getText('errorConnectingMySQL', Array('BACKENDID' => $this->backendId,'MYSQLERR' => mysql_error())));
			return FALSE;
		}
		
		$returnCode = mysql_select_db($this->dbName, $this->CONN);
		
		// set the old level of reporting back
		error_reporting($oldLevel);
		
		if(!$returnCode){
			throw new BackendConnectionProblem($this->CORE->getLang()->getText('errorSelectingDb', Array('BACKENDID' => $this->backendId, 'MYSQLERR' => mysql_error($this->CONN))));
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
			throw new BackendConnectionProblem($this->CORE->getLang()->getText('mysqlNotSupported', Array('BACKENDID' => $this->backendId)));
			return FALSE;
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
			throw new BackendConnectionProblem($this->CORE->getLang()->getText('backendInstanceNameNotValid', Array('BACKENDID' => $this->backendId, 'NAME' => $this->dbInstanceName)));
		} else {
			// ERROR: Given Instance name is not unique
			throw new BackendConnectionProblem($this->CORE->getLang()->getText('backendInstanceNameNotUniq', Array('BACKENDID' => $this->backendId, 'NAME' => $this->dbInstanceName)));
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
	 * PUBLIC Method getObjectsEx
	 * 
	 * Return all objects configured at Nagios plus some additional information. 
	 * This is needed for gmap, e.g. to populate lists.
	 *
	 * @param	string $type
	 * @return	array $ret
	 * @author	Roman Kyrylych <rkyrylych@op5.com>
	 */
	public function getObjectsEx($type) {
		$ret = Array();

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
	 * PRIVATE parseFilter()
	 *
	 * Parses the filter array to backend 
	 *
	 * @param   Array     List of objects to query
	 * @param   Array     List of filters to apply
	 * @param   String    Table to use for filtering
   * @param   Boolean   Split the filter by options
	 * @return  String    Parsed filters
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function parseFilter($objects, $filters, $table) {
		$aFilters = Array();
		foreach($objects AS $OBJS) {
			$objFilters = Array();
			foreach($filters AS $filter) {
				// Array('key' => 'host_name', 'operator' => '=', 'name'),
				switch($filter['key']) {
					case 'host_name':
					case 'host_groups':
					case 'service_groups':
					case 'hostgroup_name':
					case 'group_name':
					case 'groups':
					case 'servicegroup_name':
					case 'service_description':
						if($filter['key'] != 'service_description')
							$val = $OBJS[0]->getName();
						else
							$val = $OBJS[0]->getServiceDescription();
						
						// Translate field names
						if($filter['key'] == 'service_description')
							$filter['key'] = 'name2';
						else
							$filter['key'] = 'name1';
						
						if($filter['op'] == '>=')
							$filter['op'] = '=';
						
						$objFilters[] = ' '.$table.'.'.$filter['key'].$filter['op']." binary '".$val."' ";
					break;
					default:
						throw new BackendConnectionProblem('Invalid filter key ('.$filter['key'].')');
					break;
				}
			}
			
			$aFilters[] = implode(' AND ', $objFilters);
		}
		
		return implode(' OR ', $aFilters);
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
			
			$data = mysql_fetch_assoc($QUERYHANDLE);
			
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
	 * PUBLIC getHostStateOld()
	 *
	 * Returns the Nagios state and additional information for the requested host
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostState($objects, $options, $filters) {
		$arrReturn = Array();
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT 
			o.object_id, alias, display_name, address, o.name1,
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
			(o.objecttype_id=1 AND ('.$this->parseFilter($objects, $filters, 'o').') AND o.instance_id='.$this->dbInstanceId.') 
			AND (h.config_type='.$this->objConfigType.' AND h.instance_id='.$this->dbInstanceId.' AND h.host_object_id=o.object_id)');
		
		while($data = mysql_fetch_assoc($QUERYHANDLE)) {
			$arrTmpReturn = Array();
			
			$arrTmpReturn['alias'] = $data['alias'];
			$arrTmpReturn['display_name'] = $data['display_name'];
			$arrTmpReturn['address'] = $data['address'];
			$arrTmpReturn['statusmap_image'] = $data['statusmap_image'];
			$arrTmpReturn['notes'] = $data['notes'];
			
			// Add Additional information to array
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
			if($options & 1)
				if($data['state_type'] != '0')
					$data['current_state'] = $data['current_state'];
				else
					$data['current_state'] = $data['last_hard_state'];
			
			if($data['has_been_checked'] == '0' || $data['current_state'] == '') {
				$arrTmpReturn['state'] = 'PENDING';
				$arrTmpReturn['output'] = $this->CORE->getLang()->getText('hostIsPending', Array('HOST' => $data['name1']));
			} elseif($data['current_state'] == '0') {
				// Host is UP
				$arrTmpReturn['state'] = 'UP';
				$arrTmpReturn['output'] = $data['output'];
			} else {
				// Host is DOWN/UNREACHABLE/UNKNOWN
				
				// Store acknowledgement state in array
				$arrTmpReturn['problem_has_been_acknowledged'] = $data['problem_has_been_acknowledged'];
				
				// Store state and output in array
				switch($data['current_state']) {
					case '1': 
						$arrTmpReturn['state'] = 'DOWN';
						$arrTmpReturn['output'] = $data['output'];
					break;
					case '2':
						$arrTmpReturn['state'] = 'UNREACHABLE';
						$arrTmpReturn['output'] = $data['output'];
					break;
					case '3':
						$arrTmpReturn['state'] = 'UNKNOWN';
						$arrTmpReturn['output'] = $data['output'];
					break;
					default:
						$arrTmpReturn['state'] = 'UNKNOWN';
						$arrTmpReturn['output'] = 'GlobalBackendndomy::getHostState: Undefined state!';
					break;
				}
			}

			$arrReturn[$data['name1']] = $arrTmpReturn;
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $arrReturn;
	}
	
	/**
	 * PUBLIC getServiceState()
	 *
	 * Returns the state and additional information of the requested service
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getServiceState($objects, $options, $filters) {
		$arrReturn = Array();
		
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
				(o.objecttype_id=2 AND ('.$this->parseFilter($objects, $filters, 'o').'))
				AND (s.config_type='.$this->objConfigType.' AND s.instance_id='.$this->dbInstanceId.' AND s.service_object_id=o.object_id) 
				AND (h.config_type='.$this->objConfigType.' AND h.instance_id='.$this->dbInstanceId.' AND h.host_object_id=s.host_object_id)
				');
		
		while($data = mysql_fetch_assoc($QUERYHANDLE)) {
			$arrTmpReturn = Array();

			if(isset($objects[$data['name1'].'~~'.$data['name2']])) {
				$specific = true;
				$key = $data['name1'].'~~'.$data['name2'];
			} else {
				$specific = false;
				$key = $data['name1'];
			}
			
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
			if($options & 1)
				if($data['state_type'] != '0')
					$data['current_state'] = $data['current_state'];
				else
					$data['current_state'] = $data['last_hard_state'];
			
			if($data['has_been_checked'] == '0' || $data['current_state'] == '') {
				$arrTmpReturn['state'] = 'PENDING';
				$arrTmpReturn['output'] = $this->CORE->getLang()->getText('serviceNotChecked', Array('SERVICE' => $data['name2']));
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
					$arrTmpReturn['problem_has_been_acknowledged'] = $this->getHostAckByHostname($data['name1']);
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
			
			if($specific) {
				$arrReturn[$key] = $arrTmpReturn;
			} else {
				if(!isset($arrReturn[$key]))
					$arrReturn[$key] = Array();
				
				$arrReturn[$key][] = $arrTmpReturn;
			}
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $arrReturn;
	}

	/**
	 * PUBLIC getHostStateCounts()
	 *
	 * @param   Array     List of objects to query
	 * @param   Array     List of filters to apply
	 * @return  Array     List of states and counts
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostStateCounts($objects, $options, $filters) {
		if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'current_state';
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT 
			o.name1,
			SUM(IF(ss.has_been_checked=0,1,0)) AS pending,
			SUM(IF((ss.'.$stateAttr.'=0 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND hs.scheduled_downtime_depth=0),1,0)) AS ok,
			SUM(IF((ss.'.$stateAttr.'=0 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR hs.scheduled_downtime_depth!=0)),1,0)) AS ok_downtime,
			SUM(IF((ss.'.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND hs.scheduled_downtime_depth=0),1,0)) AS warning,
			SUM(IF((ss.'.$stateAttr.'=1 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR hs.scheduled_downtime_depth!=0)),1,0)) AS warning_downtime,
			SUM(IF((ss.'.$stateAttr.'=1 AND ss.has_been_checked!=0 AND (ss.problem_has_been_acknowledged=1 OR hs.problem_has_been_acknowledged=1)),1,0)) AS warning_ack,
			SUM(IF((ss.'.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND hs.scheduled_downtime_depth=0),1,0)) AS critical,
			SUM(IF((ss.'.$stateAttr.'=2 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR hs.scheduled_downtime_depth!=0)),1,0)) AS critical_downtime,
			SUM(IF((ss.'.$stateAttr.'=2 AND ss.has_been_checked!=0 AND (ss.problem_has_been_acknowledged=1 OR hs.problem_has_been_acknowledged=1)),1,0)) AS critical_ack,
			SUM(IF((ss.'.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND hs.scheduled_downtime_depth=0),1,0)) AS unknown,
			SUM(IF((ss.'.$stateAttr.'=3 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR hs.scheduled_downtime_depth!=0)),1,0)) AS unknown_downtime,
			SUM(IF((ss.'.$stateAttr.'=3 AND ss.has_been_checked!=0 AND (ss.problem_has_been_acknowledged=1 OR hs.problem_has_been_acknowledged=1)),1,0)) AS unknown_ack
			FROM 
				'.$this->dbPrefix.'hoststatus AS hs,
				'.$this->dbPrefix.'services AS s,
				'.$this->dbPrefix.'hosts AS h,
				'.$this->dbPrefix.'objects AS o
			LEFT JOIN
				'.$this->dbPrefix.'servicestatus AS ss
				ON ss.service_object_id=o.object_id
			WHERE 
				(o.objecttype_id=2 AND ('.$this->parseFilter($objects, $filters, 'o').'))
				AND (s.config_type='.$this->objConfigType.' AND s.instance_id='.$this->dbInstanceId.' AND s.service_object_id=o.object_id) 
				AND (h.config_type='.$this->objConfigType.' AND h.instance_id='.$this->dbInstanceId.' AND h.host_object_id=s.host_object_id)
				AND (hs.host_object_id=h.host_object_id) 
				GROUP BY h.host_object_id');

		$arrReturn = Array();
		while($data = mysql_fetch_assoc($QUERYHANDLE)) {
			$arrReturn[$data['name1']] = Array(
				'PENDING' => Array(
					'normal' => $data['pending'],
				),
				'OK' => Array(
					'normal' => $data['ok'],
					'downtime' => $data['ok_downtime'],
				),
				'WARNING' => Array(
					'normal' => $data['warning'],
					'ack' => $data['warning_ack'],
					'downtime' => $data['warning_downtime'],
				),
				'CRITICAL' => Array(
					'normal' => $data['critical'],
					'ack' => $data['critical_ack'],
					'downtime' => $data['critical_downtime'],
				),
				'UNKNOWN' => Array(
					'normal' => $data['unknown'],
					'ack' => $data['unknown_ack'],
					'downtime' => $data['unknown_downtime'],
				),
			);
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $arrReturn;
	}

	public function getHostgroupStateCounts($objects, $options, $filters) {
		if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'current_state';
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT 
			o.name1,
			SUM(IF(hs.has_been_checked=0,1,0)) AS pending,
			SUM(IF((hs.'.$stateAttr.'=0 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth=0),1,0)) AS up,
			SUM(IF((hs.'.$stateAttr.'=0 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth!=0),1,0)) AS up_downtime,
			SUM(IF((hs.'.$stateAttr.'=1 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth=0 AND hs.problem_has_been_acknowledged=0),1,0)) AS down,
			SUM(IF((hs.'.$stateAttr.'=1 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth!=0),1,0)) AS down_downtime,
			SUM(IF((hs.'.$stateAttr.'=1 AND hs.has_been_checked!=0 AND hs.problem_has_been_acknowledged=1),1,0)) AS down_ack,
			SUM(IF((hs.'.$stateAttr.'=2 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth=0 AND hs.problem_has_been_acknowledged=0),1,0)) AS unreachable,
			SUM(IF((hs.'.$stateAttr.'=2 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth!=0),1,0)) AS unreachable_downtime,
			SUM(IF((hs.'.$stateAttr.'=2 AND hs.has_been_checked!=0 AND hs.problem_has_been_acknowledged=1),1,0)) AS unreachable_ack,
			SUM(IF((hs.'.$stateAttr.'=3 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth=0 AND hs.problem_has_been_acknowledged=0),1,0)) AS unknown,
			SUM(IF((hs.'.$stateAttr.'=3 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth!=0),1,0)) AS unknown_downtime,
			SUM(IF((hs.'.$stateAttr.'=3 AND hs.has_been_checked!=0 AND hs.problem_has_been_acknowledged=1),1,0)) AS unknown_ack
			FROM 
				'.$this->dbPrefix.'objects AS o,
				'.$this->dbPrefix.'hostgroups AS hg,
				'.$this->dbPrefix.'hostgroup_members AS hgm,
				'.$this->dbPrefix.'objects AS o2
			LEFT JOIN
	     '.$this->dbPrefix.'hoststatus AS hs
		    ON hs.host_object_id=o2.object_id
			WHERE 
				(o.objecttype_id=3 AND ('.$this->parseFilter($objects, $filters, 'o', false).') AND o.instance_id='.$this->dbInstanceId.') 
				AND (hg.config_type='.$this->objConfigType.' AND hg.instance_id='.$this->dbInstanceId.' AND hg.hostgroup_object_id=o.object_id) 
				AND hgm.hostgroup_id=hg.hostgroup_id 
				AND (o2.objecttype_id=1 AND o2.object_id=hgm.host_object_id)
			GROUP BY o.object_id');
		
		$arrReturn = Array();
		while($data = mysql_fetch_assoc($QUERYHANDLE)) {
			$arrReturn[$data['name1']] = Array(
				'PENDING' => Array(
					'normal'    => $data['pending'],
				),
				'UP' => Array(
					'normal'    => $data['up'],
					'downtime'  => $data['up_downtime'],
				),
				'DOWN' => Array(
					'normal'    => $data['down'],
					'ack'       => $data['down_ack'],
					'downtime'  => $data['down_downtime'],
				),
				'UNREACHABLE' => Array(
					'normal'    => $data['unreachable'],
					'ack'       => $data['unreachable_ack'],
					'downtime'  => $data['unreachable_downtime'],
				),
			);
		}
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT 
			o.name1,
			SUM(IF(ss.has_been_checked=0,1,0)) AS pending,
			SUM(IF((ss.'.$stateAttr.'=0 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.scheduled_downtime_depth=0),1,0)) AS ok,
			SUM(IF((ss.'.$stateAttr.'=0 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR ss.scheduled_downtime_depth!=0)),1,0)) AS ok_downtime,
			SUM(IF((ss.'.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.scheduled_downtime_depth=0),1,0)) AS warning,
			SUM(IF((ss.'.$stateAttr.'=1 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR ss.scheduled_downtime_depth!=0)),1,0)) AS warning_downtime,
			SUM(IF((ss.'.$stateAttr.'=1 AND ss.has_been_checked!=0 AND (ss.problem_has_been_acknowledged=1 OR ss.problem_has_been_acknowledged=1)),1,0)) AS warning_ack,
			SUM(IF((ss.'.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.scheduled_downtime_depth=0),1,0)) AS critical,
			SUM(IF((ss.'.$stateAttr.'=2 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR ss.scheduled_downtime_depth!=0)),1,0)) AS critical_downtime,
			SUM(IF((ss.'.$stateAttr.'=2 AND ss.has_been_checked!=0 AND (ss.problem_has_been_acknowledged=1 OR ss.problem_has_been_acknowledged=1)),1,0)) AS critical_ack,
			SUM(IF((ss.'.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.scheduled_downtime_depth=0),1,0)) AS unknown,
			SUM(IF((ss.'.$stateAttr.'=3 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR ss.scheduled_downtime_depth!=0)),1,0)) AS unknown_downtime,
			SUM(IF((ss.'.$stateAttr.'=3 AND ss.has_been_checked!=0 AND (ss.problem_has_been_acknowledged=1 OR ss.problem_has_been_acknowledged=1)),1,0)) AS unknown_ack
			FROM 
				'.$this->dbPrefix.'objects AS o,
				'.$this->dbPrefix.'hostgroups AS hg,
				'.$this->dbPrefix.'hostgroup_members AS hgm,
				'.$this->dbPrefix.'services AS s,
				'.$this->dbPrefix.'objects AS o2
			LEFT JOIN
				'.$this->dbPrefix.'servicestatus AS ss
				ON ss.service_object_id=o2.object_id
			WHERE 
				(o.objecttype_id=3 AND ('.$this->parseFilter($objects, $filters, 'o', false).') AND o.instance_id='.$this->dbInstanceId.') 
				AND (hg.config_type='.$this->objConfigType.' AND hg.instance_id='.$this->dbInstanceId.' AND hg.hostgroup_object_id=o.object_id) 
				AND hgm.hostgroup_id=hg.hostgroup_id 
				AND (s.config_type='.$this->objConfigType.' AND s.instance_id='.$this->dbInstanceId.' AND s.host_object_id=hgm.host_object_id)
				AND (o2.objecttype_id=2 AND s.service_object_id=o2.object_id)
			GROUP BY o.object_id');
		
		while($data = mysql_fetch_assoc($QUERYHANDLE)) {
			// Special operator for PENDING cause it is set by the hosts initial
			// FIXME: Maybe split PENDING to SPENDING and PENDING to have it separated
			//        in NagVis. Otherwise pending hosts are counted as services.
			$arrReturn[$data['name1']]['PENDING']['normal'] += $data['pending'];
			$arrReturn[$data['name1']]['OK']['normal'] = $data['ok'];
			$arrReturn[$data['name1']]['OK']['downtime'] = $data['ok_downtime'];
			$arrReturn[$data['name1']]['WARNING']['normal'] = $data['warning'];
			$arrReturn[$data['name1']]['WARNING']['ack'] = $data['warning_ack'];
			$arrReturn[$data['name1']]['WARNING']['downtime'] = $data['warning_downtime'];
			$arrReturn[$data['name1']]['CRITICAL']['normal'] = $data['critical'];
			$arrReturn[$data['name1']]['CRITICAL']['ack'] = $data['critical_ack'];
			$arrReturn[$data['name1']]['CRITICAL']['downtime'] = $data['critical_downtime'];
			$arrReturn[$data['name1']]['UNKNOWN']['normal'] = $data['unknown'];
			$arrReturn[$data['name1']]['UNKNOWN']['ack'] = $data['unknown_ack'];
			$arrReturn[$data['name1']]['UNKNOWN']['downtime'] = $data['unknown_downtime'];
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $arrReturn;
	}
	
	public function getServicegroupStateCounts($objects, $options, $filters) {
		if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'current_state';
		
		// FIXME: Recognize host ack/downtime
		$QUERYHANDLE = $this->mysqlQuery('SELECT 
			o.name1,
			SUM(IF(ss.has_been_checked=0,1,0)) AS pending,
			SUM(IF((ss.'.$stateAttr.'=0 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.scheduled_downtime_depth=0),1,0)) AS ok,
			SUM(IF((ss.'.$stateAttr.'=0 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0),1,0)) AS ok_downtime,
			SUM(IF((ss.'.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0),1,0)) AS warning,
			SUM(IF((ss.'.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0),1,0)) AS warning_downtime,
			SUM(IF((ss.'.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.problem_has_been_acknowledged=1),1,0)) AS warning_ack,
			SUM(IF((ss.'.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0),1,0)) AS critical,
			SUM(IF((ss.'.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0),1,0)) AS critical_downtime,
			SUM(IF((ss.'.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.problem_has_been_acknowledged=1),1,0)) AS critical_ack,
			SUM(IF((ss.'.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0),1,0)) AS unknown,
			SUM(IF((ss.'.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0),1,0)) AS unknown_downtime,
			SUM(IF((ss.'.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.problem_has_been_acknowledged=1),1,0)) AS unknown_ack
			FROM 
				'.$this->dbPrefix.'objects AS o,
				'.$this->dbPrefix.'servicegroups AS sg,
				'.$this->dbPrefix.'servicegroup_members AS sgm,
				'.$this->dbPrefix.'services AS s,
				'.$this->dbPrefix.'objects AS o2
			LEFT JOIN
				'.$this->dbPrefix.'servicestatus AS ss
				ON ss.service_object_id=o2.object_id
			WHERE 
				(o.objecttype_id=4 AND ('.$this->parseFilter($objects, $filters, 'o', false).') AND o.instance_id='.$this->dbInstanceId.') 
				AND (sg.config_type='.$this->objConfigType.' AND sg.instance_id='.$this->dbInstanceId.' AND sg.servicegroup_object_id=o.object_id) 
				AND sgm.servicegroup_id=sg.servicegroup_id 
				AND (s.config_type='.$this->objConfigType.' AND s.instance_id='.$this->dbInstanceId.' AND s.service_object_id=sgm.service_object_id)
				AND (o2.objecttype_id=2 AND s.service_object_id=o2.object_id)
			GROUP BY o.object_id');
		
		$arrReturn = Array();
		while($data = mysql_fetch_assoc($QUERYHANDLE)) {
			$arrReturn[$data['name1']] = Array(
				'PENDING' => Array(
					'normal' => $data['pending'],
				),
				'OK' => Array(
					'normal' => $data['ok'],
					'downtime' => $data['ok_downtime'],
				),
				'WARNING' => Array(
					'normal' => $data['warning'],
					'ack' => $data['warning_ack'],
					'downtime' => $data['warning_downtime'],
				),
				'CRITICAL' => Array(
					'normal' => $data['critical'],
					'ack' => $data['critical_ack'],
					'downtime' => $data['critical_downtime'],
				),
				'UNKNOWN' => Array(
					'normal' => $data['unknown'],
					'ack' => $data['unknown_ack'],
					'downtime' => $data['unknown_downtime'],
				),
			);
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $arrReturn;
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
	 * PUBLIC getDirectParentNamesByHostName()
	 *
	 * Gets the names of all parent hosts. New in 1.5. Showing parent layers on
	 * the automap is only possible when the backend provides this method.
	 *
	 * @param		String		Name of host to get the parents of
	 * @return	Array			Array with hostnames
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getDirectParentNamesByHostName($hostName) {
		$aParentNames = Array();
		
		$QUERYHANDLE = $this->mysqlQuery('SELECT o2.name1
		FROM
		`'.$this->dbPrefix.'objects` AS o1,
		`'.$this->dbPrefix.'hosts` AS h1,
		`'.$this->dbPrefix.'host_parenthosts` AS ph1,
		`'.$this->dbPrefix.'objects` AS o2
		WHERE o1.objecttype_id=1 AND o1.name1=\''.$hostName.'\'
		AND (h1.config_type='.$this->objConfigType.' AND h1.instance_id='.$this->dbInstanceId.' AND h1.host_object_id=o1.object_id)
		AND h1.host_id=ph1.host_id
		AND o2.objecttype_id=1 AND o2.object_id=ph1.parent_host_object_id');
		while($data = mysql_fetch_array($QUERYHANDLE)) {
			$aParentNames[] = $data['name1'];
		}
		
		// Free memory
		mysql_free_result($QUERYHANDLE);
		
		return $aParentNames;
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
