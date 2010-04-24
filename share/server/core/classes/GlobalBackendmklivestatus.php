<?php
/*****************************************************************************
 *
 * GlobalBackendmklivestatus.php
 *
 * Backend class for handling object and state information using the 
 * livestatus NEB module. For mor information about CheckMK's Livestatus 
 * Module please visit: http://mathias-kettner.de/checkmk_livestatus.html                          
 *
 * Copyright (c) 2010 NagVis Project  (Contact: info@nagvis.org),
 *                    Mathias Kettner (Contact: mk@mathias-kettner.de)
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
 * @author  Mathias Kettner <mk@mathias-kettner.de>
 * @author  Lars Michelsen  <lars@vertical-visions.de>
 *
 * For mor information about CheckMK's Livestatus Module
 * please visit: http://mathias-kettner.de/checkmk_livestatus.html
 */
class GlobalBackendmklivestatus implements GlobalBackendInterface {
	private $backendId = '';
	
	private $SOCKET = null;
	private $socketType = '';
	private $socketPath = '';
	private $socketAddress = '';
	private $socketPort = 0;
	
	// These are the backend local configuration options
	private static $validConfig = Array(
		'socket' => Array('must' => 1,
		  'editable' => 1,
		  'default' => 'unix:/usr/local/nagios/var/rw/live',
		  'match' => MATCH_SOCKET));
	
	/**
	 * PUBLIC class constructor
	 *
	 * @param   GlobalCore    Instance of the NagVis CORE
	 * @param   String        ID if the backend
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $backendId) {
		$this->backendId = $backendId;
		
		// Parse the socket params
		$this->parseSocket(GlobalCore::getInstance()->getMainCfg()->getValue('backend_'.$backendId, 'socket'));
		
		// Run preflight checks
		if($this->socketType == 'unix' && !$this->checkSocketExists()) {
			throw new BackendConnectionProblem(GlobalCore::getInstance()->getLang()->getText('Unable to connect to livestatus socket. The socket [SOCKET] in backend [BACKENDID] does not exist. Maybe Nagios is not running or restarting.', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
		}
		
		if(!function_exists('socket_create')) {
			throw new BackendConnectionProblem(GlobalCore::getInstance()->getLang()->getText('The PHP function socket_create is not available. Maybe the sockets module is missing in your PHP installation. Needed by backend [BACKENDID].', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
    }
		
		return true;
	}

	/**
	 * PUBLIC class destructor
	 *
	 * The descrutcor closes the socket when some is open
	 * at the moment when the class is destroyed. It is
	 * important to close the socket in a clean way.
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __destruct() {
		if($this->SOCKET !== null) {
			socket_close($this->SOCKET);
			$this->SOCKET = null;
		}
	}
	
	/**
	 * PRIVATE parseSocket
	 * 
	 * Parses and sets the socket options
	 *
	 * @return  String    Parses the socket
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function parseSocket($socket) {
		// Explode the given socket definition
		list($type, $address) = explode(':', $socket, 2);
		
		if($type === 'unix') {
			$this->socketType = $type;
			$this->socketPath = $address;
		} elseif($type === 'tcp') {
			$this->socketType = $type;
			
			// Extract address and port
			list($address, $port) = explode(':', $address, 2);
			
			$this->socketAddress = $address;
			$this->socketPort = $port;
		} else {
			throw new BackendConnectionProblem(GlobalCore::getInstance()->getLang()->getText('Unknown socket type given in backend [BACKENDID]', Array('BACKENDID' => $this->backendId)));
		}
	}
	
	/**
	 * PUBLIC getValidConfig
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
	 * PRIVATE checkSocketExists()
	 *
	 * Checks if the socket exists
	 *
	 * @return  Boolean
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkSocketExists() {
		if(file_exists($this->socketPath)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * PRIVATE connectSocket()
	 *
	 * Connects to the livestatus socket when no connection is open
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function connectSocket() {
		// Create socket connection
		if($this->socketType === 'unix') {
			$this->SOCKET = socket_create(AF_UNIX, SOCK_STREAM, 0);
		} elseif($this->socketType === 'tcp') {
			$this->SOCKET = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		}
		
		if($this->SOCKET == false) {
			throw new BackendConnectionProblem(GlobalCore::getInstance()->getLang()->getText('Could not create livestatus socket [SOCKET] in backend [BACKENDID].', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
		}
		
		// Connect to the socket
		if($this->socketType === 'unix') {
			$result = socket_connect($this->SOCKET, $this->socketPath);
		} elseif($this->socketType === 'tcp') {
			$result = socket_connect($this->SOCKET, $this->socketAddress, $this->socketPort);
		}
		
		if($result == false) {
			throw new BackendConnectionProblem(GlobalCore::getInstance()->getLang()->getText('Unable to connect to the [SOCKET] in backend [BACKENDID]: [MSG]', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath, 'MSG' => socket_strerror(socket_last_error($this->SOCKET)))));
		}

		// Maybe set some socket options
		if($this->socketType === 'tcp') {
			// Disable Nagle's Alogrithm - Nagle's Algorithm is bad for brief protocols
			if(defined('TCP_NODELAY')) {
				socket_set_option($this->SOCKET, SOL_TCP, TCP_NODELAY, 1);
			} else {
				// See http://bugs.php.net/bug.php?id=46360
				socket_set_option($this->SOCKET, SOL_TCP, 1, 1);
			}
		}
	}
	
	/**
	 * PRIVATE queryLivestatus()
	 *
	 * Queries the livestatus socket and returns the result as array
	 *
	 * @param   String   Query to send to the socket
	 * @return  Array    Results of the query
	 * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function queryLivestatus($query) {
		// Only connect when no connection opened yet
		if($this->SOCKET === null) {
			$this->connectSocket();
		}
		
		// Query to get a json formated array back
		// Use KeepAlive with fixed16 header
		socket_write($this->SOCKET, $query . "OutputFormat:json\nKeepAlive: on\nResponseHeader: fixed16\n\n");
		
		// Read 16 bytes to get the status code and body size
		$read = $this->readSocket(16);
		
		// Catch problem while reading
		if($read === false) {
			throw new BackendConnectionProblem(GlobalCore::getInstance()->getLang()->getText('Problem while reading from socket [SOCKET] in backend [BACKENDID]: [MSG]', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath, 'MSG' => socket_strerror(socket_last_error($this->SOCKET)))));
		}
		
		// Extract status code
		$status = substr($read, 0, 3);
		
		// Extract content length
		$len = intval(trim(substr($read, 4, 11)));
		
		// Read socket until end of data
		$read = $this->readSocket($len);
		
		// Catch problem while reading
		if($read === false) {
			throw new BackendConnectionProblem(GlobalCore::getInstance()->getLang()->getText('Problem while reading from socket [SOCKET] in backend [BACKENDID]: [MSG]', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath, 'MSG' => socket_strerror(socket_last_error($this->SOCKET)))));
		}
		
		// Catch errors (Like HTTP 200 is OK)
		if($status != "200") {
			throw new BackendConnectionProblem(GlobalCore::getInstance()->getLang()->getText('Problem while reading from socket [SOCKET] in backend [BACKENDID]: [MSG]', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath, 'MSG' => $read)));
		}
		
		// Catch problems occured while reading? 104: Connection reset by peer
		if(socket_last_error($this->SOCKET) == 104) {
			throw new BackendConnectionProblem(GlobalCore::getInstance()->getLang()->getText('Problem while reading from socket [SOCKET] in backend [BACKENDID]: [MSG]', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath, 'MSG' => socket_strerror(socket_last_error($this->SOCKET)))));
		}
		
		// Decode the json response
		$obj = json_decode(utf8_encode($read));
		
		// TEST: Disable KeepAlive:
		//socket_close($this->SOCKET);
		//$this->SOCKET = null;
		
		// json_decode returns null on syntax problems
		if($obj === null) {
			throw new BackendInvalidResponse(GlobalCore::getInstance()->getLang()->getText('The response has an invalid format in backend [BACKENDID].', Array('BACKENDID' => $this->backendId)));
		} else {
			// Return the response object
			return $obj;
		}
	}

	/**
	 * PRIVATE readSocket()
	 *
	 * Method for reading a fixed amount of bytest from the socket
	 *
	 * @param   Integer  Number of bytes to read
	 * @return  String   The read bytes
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function readSocket($len) {
		$offset = 0;
		$socketData = '';
		
		while($offset < $len) {
			if(($data = @socket_read($this->SOCKET, $len - $offset)) === false) {
				return false;
			}
		
			if(($dataLen = strlen($data)) === 0) {
				break;
			}
			
			$offset += $dataLen;
			$socketData .= $data;
		}
		
		return $socketData;
	}
	
	/**
	 * PRIVATE queryLivestatusSingleRow()
	 *
	 * Queries the livestatus socket for a single row
	 *
	 * @param   String   Query to send to the socket
	 * @return  Array    Results of the query
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function queryLivestatusSingleRow($query) {
		$l = $this->queryLivestatus($query);
		if(isset($l[0])) {
			return $l[0];
		} else {
			return Array();
		}
	}
	
	/**
	 * PRIVATE queryLivestatusSingleColumn()
	 *
	 * Queries the livestatus socket for a single column in several rows
	 *
	 * @param   String   Query to send to the socket
	 * @return  Array    Results of the query
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function queryLivestatusSingleColumn($query) {
		$l = $this->queryLivestatus($query);
		
		$result = Array();
		foreach($l as $line) {
			$result[] = $line[0];
		}
		
		return $result;
	}
	
	/**
	 * PRIVATE queryLivestatusList()
	 *
	 * Queries the livestatus socket for a list of objects
	 *
	 * @param   String   Query to send to the socket
	 * @return  Array    Results of the query
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function queryLivestatusList($query) {
		$l = $this->queryLivestatus($query);
		
		$result = Array();
		foreach($l as $line) {
			$result = array_merge($result, $line[0]);
		}
		
		return $result;
	}
	
	/**
	 * PUBLIC getObjects()
	 *
	 * Queries the livestatus socket for a list of objects
	 *
	 * @param   String   Type of object
	 * @param   String   Name1 of the objecs
	 * @param   String   Name2 of the objecs
	 * @return  Array    Results of the query
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getObjects($type, $name1Pattern = '', $name2Pattern = '') {
		$ret = Array();
		$filter = '';
		$sFile = '';
		
		switch($type) {
			case 'host':
			case 'hostgroup':
			case 'servicegroup':
				$l = $this->queryLivestatus("GET ".$type."s\nColumns: name alias\n");
			break;
			case 'service':
				$query = "GET services\nColumns: description description\n";
				
				if($name1Pattern) {
					$query .= "Filter: host_name = " . $name1Pattern . "\n";
				}
				
				$l = $this->queryLivestatus($query);
			break;
			default:
				return Array();
			break;
		}
		reset($l);
		
		$result = Array();
		foreach($l as $entry) {
			$result[] = Array('name1' => $entry[0], 'name2' => $entry[1]);
		}
		
		return $result;
	}
	
	/**
	 * PRIVATE parseFilter()
	 *
	 * Parses the filter array to backend 
	 *
	 * @param   Array     List of objects to query
	 * @param   Array     List of filters to apply
	 * @return  String    Parsed filters
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function parseFilter($objects, $filters) {
		$aFilters = Array();
		foreach($objects AS $OBJS) {
			$objFilters = Array();
			foreach($filters AS $filter) {
				// Array('key' => 'host_name', 'operator' => '=', 'name'),
				switch($filter['key']) {
					case 'host_name':
					case 'host_groups':
					case 'service_description':
					case 'groups':
					case 'service_groups':
					case 'hostgroup_name':
					case 'group_name':
					case 'servicegroup_name':
						if($filter['key'] != 'service_description')
							$val = $OBJS[0]->getName();
						else
							$val = $OBJS[0]->getServiceDescription();
						
						$objFilters[] = 'Filter: '.$filter['key'].' '.$filter['op'].' '.$val."\n";
					break;
					default:
						throw new BackendConnectionProblem('Invalid filter key ('.$filter['key'].')');
					break;
				}
			}

			// the object specific filters all need to match
			$count = count($objFilters);
			if($count > 1)
				$count = 'And: '.$count."\n";
			else
				$count = '';

			$aFilters[] = implode($objFilters).$count;
		}

		$count = count($aFilters); 
		if($count > 1)
			$count = 'Or: '.$count."\n";
		else
			$count = '';
	
		return implode($aFilters).$count;
	}
	

	/**
	 * PUBLIC getHostState()
	 *
	 * Queries the livestatus socket for the state of a host
	 *
	 * @param   Array     List of objects to query
	 * @param   Array     List of filters to apply
	 * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostState($objects, $options, $filters) {
		$objFilter = $this->parseFilter($objects, $filters);
		
		if($options & 1)
			$stateAttr = 'hard_state';
		else
			$stateAttr = 'state';

		$q = "GET hosts\n".
		  "Columns: ".$stateAttr." plugin_output alias display_name ".
		  "address notes last_check next_check state_type ".
		  "current_attempt max_check_attempts last_state_change ".
		  "last_hard_state_change statusmap_image perf_data ".
		  "acknowledged scheduled_downtime_depth has_been_checked name\n".
		  $objFilter;

		$l = $this->queryLivestatus($q);

		$arrReturn = Array();
		
		if(is_array($l) && count($l) > 0) {
			foreach($l as $e) {
				$state = $e[0];
				
				// Catch pending objects
				// $e[17]: has_been_checked
				// $e[0]:  state
				if($e[17] == 0 || $state === '') {
					$arrReturn[$e[18]] = Array(
						'state' =>  'PENDING',
						'output' => GlobalCore::getInstance()->getLang()->getText('hostIsPending', Array('HOST' => $e[18]))
					);
					continue;
				}

				switch($state) {
					case "0": $state = "UP"; break;
					case "1": $state = "DOWN"; break;
					case "2": $state = "UNREACHABLE"; break;
					default:  $state = "UNKNOWN"; break;
				}
				
				$arrTmpReturn = Array(
				  'state'                  => $state,
				  'output'                 => $e[1],
				  'alias'                  => $e[2],
				  'display_name'           => $e[3],
				  'address'                => $e[4],
				  'notes'                  => $e[5],
				  'last_check'             => $e[6],
				  'next_check'             => $e[7],
				  'state_type'             => $e[8],
				  'current_check_attempt'  => $e[9],
				  'max_check_attempts'     => $e[10],
				  'last_state_change'      => $e[11],
				  'last_hard_state_change' => $e[12],
				  'statusmap_image'        => $e[13],
				  'perfdata'               => $e[14]
				);
				
				/**
				* Handle host/service acks
				*
				* If state is not OK (=> WARN, CRIT, UNKNOWN) and service is not 
				* acknowledged => check for acknowledged host
				*/
				// $e[15]: acknowledged
				if($state != 'UP' && $e[15] == 1) {
					$arrTmpReturn['problem_has_been_acknowledged'] = 1;
				} else {
					$arrTmpReturn['problem_has_been_acknowledged'] = 0;
				}
				
				// If there is a downtime for this object, save the data
				// $e[16]: scheduled_downtime_depth
				if(isset($e[16]) && $e[16] > 0) {
					$arrTmpReturn['in_downtime'] = 1;
					
					// This handles only the first downtime. But this is not backend
					// specific. The other backends do this as well.
					$d = $this->queryLivestatusSingleRow(
						"GET downtimes\n".
						"Columns: author comment start_time end_time\n" .
						"Filter: host_name = ".$e[18]."\n");
					
					$arrTmpReturn['downtime_author'] = $d[0];
					$arrTmpReturn['downtime_data'] = $d[1];
					$arrTmpReturn['downtime_start'] = $d[2];
					$arrTmpReturn['downtime_end'] = $d[3];
				}
		
				$arrReturn[$e[18]] = $arrTmpReturn;
			}
		}
		
		return $arrReturn;
	}
	
	/**
	 * PUBLIC getServiceState()
	 *
	 * Queries the livestatus socket for a specific service
	 * or all services of a host
	 *
	 * @param   Array     List of objects to query
	 * @param   Array     List of filters to apply
	 * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getServiceState($objects, $options, $filters) {
		$objFilter = $this->parseFilter($objects, $filters);
		
		if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'state';
		
		$l = $this->queryLivestatus(
		  "GET services\n" .
		  $objFilter.
		  "Columns: description display_name ".$stateAttr." ".
		  "host_alias host_address plugin_output notes last_check next_check ".
		  "state_type current_attempt max_check_attempts last_state_change ".
		  "last_hard_state_change perf_data scheduled_downtime_depth ".
		  "acknowledged host_acknowledged host_scheduled_downtime_depth ".
		  "has_been_checked host_name\n");

		$arrReturn = Array();
		if(is_array($l) && count($l) > 0) {
			foreach($l as $e) {
				$arrTmpReturn = Array();
				$arrTmpReturn['service_description'] = $e[0];
				$arrTmpReturn['display_name'] = $e[1];
				
				// test for the correct key
				if(isset($objects[$e[20].'~~'.$e[0]])) {
					$specific = true;
					$key = $e[20].'~~'.$e[0];
				} else {
					$specific = false;
					$key = $e[20];
				}
				
				// Catch pending objects
				// $e[19]: has_been_checked
				// $e[2]:  state
				if($e[19] == 0 || $e[2] === '') {
					$arrTmpReturn['state'] = 'PENDING';
					$arrTmpReturn['output'] = GlobalCore::getInstance()->getLang()->getText('serviceNotChecked', Array('SERVICE' => $e[0]));
				} else {
					$state = $e[2];
					
					switch ($state) {
						case "0": $state = "OK"; break;
						case "1": $state = "WARNING"; break;
						case "2": $state = "CRITICAL"; break;
						case "3": $state = "UNKNOWN"; break;
						default: $state = "UNKNOWN"; break;
					}
					
					/**
					 * Handle host/service acks
					 *
					 * If state is not OK (=> WARN, CRIT, UNKNOWN) and service is not 
					 * acknowledged => check for acknowledged host
					 */
					// $e[16]: acknowledged
					// $e[17]: host_acknowledged
					if($state != 'OK' && ($e[16] == 1 || $e[17] == 1)) {
						$arrTmpReturn['problem_has_been_acknowledged'] = 1;
					} else {
						$arrTmpReturn['problem_has_been_acknowledged'] = 0;
					}
					
					// Handle host/service downtimes
					// $e[15]: scheduled_downtime_depth
					// $e[18]: host_scheduled_downtime_depth
					if((isset($e[15]) && $e[15] > 0) || (isset($e[18]) && $e[18] > 0)) {
						$arrTmpReturn['in_downtime'] = 1;
						
						// This handles only the first downtime. But this is not backend
						// specific. The other backends do this as well.
						
						// Handle host/service downtime difference
						if(isset($e[15]) && $e[15] > 0) {
							// Service downtime
							$d = $this->queryLivestatusSingleRow(
							  "GET downtimes\n".
							  "Columns: author comment start_time end_time\n" .
							  "Filter: host_name = ".$e[20]."\n" .
							  "Filter: service_description = ".$e[0]."\n");
						} else {
							// Host downtime
							$d = $this->queryLivestatusSingleRow(
							  "GET downtimes\n".
							  "Columns: author comment start_time end_time\n" .
							  "Filter: host_name = ".$e[20]."\n");
						}
						
						$arrTmpReturn['downtime_author'] = $d[0];
						$arrTmpReturn['downtime_data'] = $d[1];
						$arrTmpReturn['downtime_start'] = $d[2];
						$arrTmpReturn['downtime_end'] = $d[3];
					}
					
					$arrTmpReturn['state'] = $state;
					$arrTmpReturn['alias'] = $e[3];
					$arrTmpReturn['address'] = $e[4];
					$arrTmpReturn['output'] = $e[5];
					$arrTmpReturn['notes'] = $e[6];
					$arrTmpReturn['last_check'] = $e[7];
					$arrTmpReturn['next_check'] = $e[8];
					$arrTmpReturn['state_type'] = $e[9];
					$arrTmpReturn['current_check_attempt'] = $e[10];
					$arrTmpReturn['max_check_attempts'] = $e[11];
					$arrTmpReturn['last_state_change'] = $e[12];
					$arrTmpReturn['last_hard_state_change'] = $e[13];
					$arrTmpReturn['perfdata'] = $e[14];
				}
				
				if($specific) {
					$arrReturn[$key] = $arrTmpReturn;
				} else {
					if(!isset($arrReturn[$key]))
						$arrReturn[$key] = Array();
					
					$arrReturn[$key][] = $arrTmpReturn;
				}
			}
		}

		return $arrReturn;
	}
	/**
	 * PUBLIC getHostStateCounts()
	 *
	 * Queries the livestatus socket for host state counts. The information
	 * are used to calculate the summary output and the summary state of a 
	 * host and a well performing alternative to the existing recurisve
	 * algorithm.
	 *
	 * @param   Array     List of objects to query
	 * @param   Bitmask   This is a mask of options to use during the query
	 * @param   Array     List of filters to apply
	 * @return  Array     List of states and counts
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostStateCounts($objects, $options, $filters) {
		$objFilter = $this->parseFilter($objects, $filters);
		
		if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'state';
	
		// Get service information
		$l = $this->queryLivestatus("GET services\n" .
			$objFilter.
			// Count PENDING
			"Stats: has_been_checked = 0\n" .
			// Count OK
			"Stats: ".$stateAttr." = 0\n" .
			"Stats: has_been_checked != 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 4\n" .
			// Count OK (DOWNTIME)
			"Stats: ".$stateAttr." = 0\n" .
			"Stats: has_been_checked != 0\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 3\n" .
			// Count WARNING
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: host_acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 5\n" .
			// Count WARNING(ACK)
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: acknowledged = 1\n" .
			"Stats: host_acknowledged = 1\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count WARNING(DOWNTIME)
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count CRITICAL
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: host_acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 5\n" .
			// Count CRITICAL(ACK)
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: acknowledged = 1\n" .
			"Stats: host_acknowledged = 1\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count CRITICAL(DOWNTIME)
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count UNKNOWN
			"Stats: ".$stateAttr." = 3\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: host_acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 5\n" .
			// Count UNKNOWN(ACK)
			"Stats: ".$stateAttr." = 3\n" .
			"Stats: acknowledged = 1\n" .
			"Stats: host_acknowledged = 1\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count UNKNOWN(DOWNTIME)
			"Stats: ".$stateAttr." = 3\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			"StatsGroupBy: host_name\n");
	
		$arrReturn = Array();
		if(is_array($l) && count($l) > 0) {
			foreach($l as $e) {
				$arrReturn[$e[0]] = Array(
					'PENDING' => Array(
						'normal' => $e[1],
					),
					'OK' => Array(
						'normal' => $e[2],
						'downtime' => $e[3],
					),
					'WARNING' => Array(
						'normal' => $e[4],
						'ack' => $e[5],
						'downtime' => $e[6],
					),
					'CRITICAL' => Array(
						'normal' => $e[7],
						'ack' => $e[8],
						'downtime' => $e[9],
					),
					'UNKNOWN' => Array(
						'normal' => $e[10],
						'ack' => $e[11],
						'downtime' => $e[12],
					),
				);
			}
		}
		
		return $arrReturn;
	}
	
	/**
	 * PUBLIC getHostgroupStateCounts()
	 *
	 * Queries the livestatus socket for hostgroup state counts. The information
	 * are used to calculate the summary output and the summary state of a 
	 * hostgroup and a well performing alternative to the existing recurisve
	 * algorithm.
	 *
	 * @param   Array     List of objects to query
	 * @param   Array     List of filters to apply
	 * @return  Array     List of states and counts
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostgroupStateCounts($objects, $options, $filters) {
		$objFilter = $this->parseFilter($objects, $filters);
		
		if($options & 1)
			$stateAttr = 'hard_state';
		else
			$stateAttr = 'state';
		
		// Get host information
		$l = $this->queryLivestatus("GET hostsbygroup\n" .
			$objFilter.
			// Count PENDING
			"Stats: has_been_checked = 0\n" .
			// Count UP
			"Stats: ".$stateAttr." = 0\n" .
			"Stats: has_been_checked != 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"StatsAnd: 3\n" .
			// Count UP (DOWNTIME)
			"Stats: ".$stateAttr." = 0\n" .
			"Stats: has_been_checked != 0\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"StatsAnd: 3\n" .
			// Count DOWN
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"StatsAnd: 3\n" .
			// Count DOWN(ACK)
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: acknowledged = 1\n" .
			"StatsAnd: 2\n" .
			// Count DOWN(DOWNTIME)
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"StatsAnd: 2\n" .
			// Count UNREACHABLE
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"StatsAnd: 3\n" .
			// Count UNREACHABLE(ACK)
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: acknowledged = 1\n" .
			"StatsAnd: 2\n" .
			// Count UNREACHABLE(DOWNTIME)
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"StatsAnd: 2\n".
			"StatsGroupBy: hostgroup_name\n");
		
		// If the method should fetch several objects and did not find
		// any object, don't return anything => The message
		// that the objects were not found is added by the core
		if(is_array($l) && count($l) > 0) {
			foreach($l as $e) {
				$arrReturn[$e[0]] = Array(
					'PENDING' => Array(
						'normal'    => $e[1],
					),
					'UP' => Array(
						'normal'    => $e[2],
						'downtime'  => $e[3],
					),
					'DOWN' => Array(
						'normal'    => $e[4],
						'ack'       => $e[5],
						'downtime'  => $e[6],
					),
					'UNREACHABLE' => Array(
						'normal'    => $e[7],
						'ack'       => $e[8],
						'downtime'  => $e[9],
					),
				);
			}
		}
		
		if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'state';
	
		// Little hack to correct the different field names
		$objFilter = str_replace(' groups ', ' host_groups ', $objFilter);
	
		// Get service information
		$l = $this->queryLivestatus("GET servicesbyhostgroup\n" .
			$objFilter.
			// Count PENDING
			"Stats: has_been_checked = 0\n" .
			// Count OK
			"Stats: ".$stateAttr." = 0\n" .
			"Stats: has_been_checked != 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 4\n" .
			// Count OK (Downtime)
			"Stats: ".$stateAttr." = 0\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsAnd: 3\n" .
			// Count WARNING
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: host_acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 5\n" .
			// Count WARNING(ACK)
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: acknowledged = 1\n" .
			"Stats: host_acknowledged = 1\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count WARNING(DOWNTIME)
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count CRITICAL
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: host_acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 5\n" .
			// Count CRITICAL(ACK)
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: acknowledged = 1\n" .
			"Stats: host_acknowledged = 1\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count CRITICAL(DOWNTIME)
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count UNKNOWN
			"Stats: ".$stateAttr." = 3\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: host_acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 5\n" .
			// Count UNKNOWN(ACK)
			"Stats: ".$stateAttr." = 3\n" .
			"Stats: acknowledged = 1\n" .
			"Stats: host_acknowledged = 1\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count UNKNOWN(DOWNTIME)
			"Stats: ".$stateAttr." = 3\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n".
			"StatsGroupBy: hostgroup_name\n");
		
		if(is_array($l) && count($l) > 0) {
			foreach($l as $e) {
				// Special operator for PENDING cause it is set by the hosts initial
				// FIXME: Maybe split PENDING to SPENDING and PENDING to have it separated
				//        in NagVis. Otherwise pending hosts are counted as services.
				$arrReturn[$e[0]]['PENDING']['normal'] += $e[1];
				$arrReturn[$e[0]]['OK']['normal'] = $e[2];
				$arrReturn[$e[0]]['OK']['downtime'] = $e[3];
				$arrReturn[$e[0]]['WARNING']['normal'] = $e[4];
				$arrReturn[$e[0]]['WARNING']['ack'] = $e[5];
				$arrReturn[$e[0]]['WARNING']['downtime'] = $e[6];
				$arrReturn[$e[0]]['CRITICAL']['normal'] = $e[7];
				$arrReturn[$e[0]]['CRITICAL']['ack'] = $e[8];
				$arrReturn[$e[0]]['CRITICAL']['downtime'] = $e[9];
				$arrReturn[$e[0]]['UNKNOWN']['normal'] = $e[10];
				$arrReturn[$e[0]]['UNKNOWN']['ack'] = $e[11];
				$arrReturn[$e[0]]['UNKNOWN']['downtime'] = $e[12];
			}
		}
		
		return $arrReturn;
	}
	
	/**
	 * PUBLIC getServicegroupStateCounts()
	 *
	 * Queries the livestatus socket for servicegroup state counts. The information
	 * are used to calculate the summary output and the summary state of a 
	 * servicegroup and a well performing alternative to the existing recurisve
	 * algorithm.
	 *
	 * @param   Array     List of objects to query
	 * @param   Array     List of filters to apply
	 * @return  Array     List of states and counts
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getServicegroupStateCounts($objects, $options, $filters) {
		$objFilter = $this->parseFilter($objects, $filters);
		
		if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'state';
	
		// Get service information
		$l = $this->queryLivestatus("GET servicesbygroup\n" .
			$objFilter.
			// Count PENDING
			"Stats: has_been_checked = 0\n" .
			// Count OK
			"Stats: ".$stateAttr." = 0\n" .
			"Stats: has_been_checked != 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 4\n" .
			// Count OK (Downtime)
			"Stats: ".$stateAttr." = 0\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsAnd: 3\n" .
			// Count WARNING
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: host_acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 5\n" .
			// Count WARNING(ACK)
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: acknowledged = 1\n" .
			"Stats: host_acknowledged = 1\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count WARNING(DOWNTIME)
			"Stats: ".$stateAttr." = 1\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count CRITICAL
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: host_acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 5\n" .
			// Count CRITICAL(ACK)
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: acknowledged = 1\n" .
			"Stats: host_acknowledged = 1\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count CRITICAL(DOWNTIME)
			"Stats: ".$stateAttr." = 2\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count UNKNOWN
			"Stats: ".$stateAttr." = 3\n" .
			"Stats: acknowledged = 0\n" .
			"Stats: host_acknowledged = 0\n" .
			"Stats: scheduled_downtime_depth = 0\n" .
			"Stats: host_scheduled_downtime_depth = 0\n" .
			"StatsAnd: 5\n" .
			// Count UNKNOWN(ACK)
			"Stats: ".$stateAttr." = 3\n" .
			"Stats: acknowledged = 1\n" .
			"Stats: host_acknowledged = 1\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n" .
			// Count UNKNOWN(DOWNTIME)
			"Stats: ".$stateAttr." = 3\n" .
			"Stats: scheduled_downtime_depth > 0\n" .
			"Stats: host_scheduled_downtime_depth > 0\n" .
			"StatsOr: 2\n" .
			"StatsAnd: 2\n".
			"StatsGroupBy: servicegroup_name\n");
		
		// If the method should fetch several objects and did not find
		// any object, don't return anything => The message
		// that the objects were not found is added by the core
		$arrReturn = Array();
		if(is_array($l) && count($l) > 0) {
			foreach($l as $e) {
				$arrReturn[$e[0]] = Array(
					'PENDING' => Array(
						'normal'    => $e[1],
					),
					'OK' => Array(
						'normal'    => $e[2],
						'downtime'  => $e[3],
					),
					'WARNING' => Array(
						'normal'    => $e[4],
						'ack'       => $e[5],
						'downtime'  => $e[6],
					),
					'CRITICAL' => Array(
						'normal'    => $e[7],
						'ack'       => $e[8],
						'downtime'  => $e[9],
					),
					'UNKNOWN' => Array(
						'normal'    => $e[10],
						'ack'       => $e[11],
						'downtime'  => $e[12],
					),
				);
			}
		}
		
		return $arrReturn;
	}
	
	/**
	 * PUBLIC getHostNamesWithNoParent()
	 *
	 * Queries the livestatus socket for all hosts without parent
	 *
	 * @return  Array    List of hostnames which have no parent
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostNamesWithNoParent() {
		return $this->queryLivestatusSingleColumn("GET hosts\nColumns: name\nFilter: parents =\n");
	}
	
	/**
	 * PUBLIC getDirectChildNamesByHostName()
	 *
	 * Queries the livestatus socket for all direct childs of a host
	 *
	 * @param   String   Hostname
	 * @return  Array    List of hostnames
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getDirectChildNamesByHostName($hostName) {
		return $this->queryLivestatusList("GET hosts\nColumns: childs\nFilter: name = ".$hostName."\n");
	}

	/*
	 * PUBLIC getDirectParentNamesByHostName()
	 *
	 * Queries the livestatus socket for all direct parents of a host
	 *
	 * @param   String   Hostname
	 * @return  Array    List of hostnames
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getDirectParentNamesByHostName($hostName) {
		return $this->queryLivestatusList("GET hosts\nColumns: parents\nFilter: name = ".$hostName."\n");
	}
}
?>
