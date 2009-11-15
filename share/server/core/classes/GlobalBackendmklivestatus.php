<?php
/*****************************************************************************
 *
 * GlobalBackendmklivestatus.php
 *
 * Backend class for handling object and state information using the 
 * livestatus NEB module. For mor information about CheckMK's Livestatus 
 * Module please visit: http://mathias-kettner.de/checkmk_livestatus.html                          
 *
 * Copyright (c) 2009 NagVis Project  (Contact: info@nagvis.org),
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
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Unable to connect to livestatus socket. The socket [SOCKET] in backend [BACKENDID] does not exist', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
		}
		
		if(!function_exists('socket_create')) {
			new GlobalMessage('ERROR',  GlobalCore::getInstance()->getLang()->getText('The PHP function socket_create is not available. Maybe the sockets module is missing in your PHP installation. Needed by backend [BACKENDID].', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
    }

		
		return true;
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
			new GlobalMessage('ERROR',  GlobalCore::getInstance()->getLang()->getText('Unknown socket type given in backend [BACKENDID]', Array('BACKENDID' => $this->backendId)));
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
		$sock = false;
		
		// Create socket connection
		if($this->socketType === 'unix') {
			$sock = socket_create(AF_UNIX, SOCK_STREAM, 0);
		} elseif($this->socketType === 'tcp') {
			$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		}
		
		if($sock == false) {
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Could not create livestatus socket [SOCKET] in backend [BACKENDID].', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
			return Array();
		}
		
		// Connect to the socket
		if($this->socketType === 'unix') {
			$result = socket_connect($sock, $this->socketPath);
		} elseif($this->socketType === 'tcp') {
			$result = socket_connect($sock, $this->socketAddress, $this->socketPort);
		}
		
		if($result == false) {
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Unable to connect to the [SOCKET] in backend [BACKENDID]: [MSG]', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath, 'MSG' => socket_strerror(socket_last_error($sock)))));
			return Array();
		}
		
		// Query to get a json formated array back
		socket_write($sock, $query . "OutputFormat:json\n");
		socket_shutdown($sock, 1);
		
		// Read all information from the response and add it to a string
		$read = '';
		while('' != ($r = @socket_read($sock, 65536))) {
			$read .= $r;
		}
		
		// Catch problems occured while reading? 104: Connection reset by peer
		if(socket_last_error($sock) == 104) {
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Problem while reading from socket [SOCKET] in backend [BACKENDID]: [MSG]', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath, 'MSG' => socket_strerror(socket_last_error($sock)))));
			return Array();
		}
		
		// Important: The socket needs to be closed after reading
		socket_close($sock);
		
		// Decode the json response
		$obj = json_decode($read);
		
		// json_decode returns null on syntax problems
		if($obj === null) {
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('The response has an invalid format in backend [BACKENDID].', Array('BACKENDID' => $this->backendId)));
			return Array();
		} else {
			// Return the response object
			return $obj;
		}
	}
	
	/**
	 * PRIVATE queryLivestatusSingle()
	 *
	 * Queries the livestatus socket for a single field
	 *
	 * @param   String   Query to send to the socket
	 * @return  Array    Results of the query
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function queryLivestatusSingle($query) {
		$l = $this->queryLivestatus($query);
		if(isset($l[0][0])) {
			return $l[0][0];
		} else {
			return Array();
		}
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
	 * PRIVATE queryLivestatusSinglecolumn()
	 *
	 * Queries the livestatus socket for a single column in several rows
	 *
	 * @param   String   Query to send to the socket
	 * @return  Array    Results of the query
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function queryLivestatusSinglecolumn($query) {
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
				$l = $this->queryLivestatus("GET hosts\nColumns: name alias\n");
			break;
			case 'service':
				$query = "GET services\nColumns: description description\n";
				
				if($name1Pattern) {
					$query .= "Filter: host_name = " . $name1Pattern . "\n";
				}
				
				$l = $this->queryLivestatus($query);
			break;
			case 'hostgroup':
				$l = $this->queryLivestatus("GET hostgroups\nColumns: name alias\n");
			break;
			case 'servicegroup':
				$l = $this->queryLivestatus("GET servicegroups\nColumns: name alias\n");
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
	 * PUBLIC Method getObjectsEx
	 * 
	 * Return all objects configured at Nagios plus some additional information. 
	 * This is needed for gmap, e.g. to populate lists.
	 *
	 * FIXME: Not implemented in this backend
	 *
	 * @param   string  $type
	 * @return  array   $ret
	 * @author  Roman Kyrylych <rkyrylych@op5.com>
	 */
	public function getObjectsEx($type) {
		$ret = Array();

		return $ret;
	}
	
	/**
	 * PUBLIC getHostgroupState()
	 *
	 * Returns the state of all hosts in a given hostgroup
	 *
	 * @param   String    $hostgroupName
	 * @param   Boolean   $onlyHardstates
	 * @return  array     Array of host objects
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostgroupState($hostgroupName, $onlyHardstates) {
		$arrReturn = Array();
		
		$stateColumn = 'state';
			
		// When only hardstate handling is enabled dont fetch "state"
		// Fetch "hard_state"
		if($onlyHardstates == 1) {
			$stateColumn = 'hard_state';
		} 
			
		$numAttr = 19;
		$l = $this->queryLivestatus(
		  "GET hosts\n".
		  "Columns: name ".$stateColumn." plugin_output alias display_name ".
		  "address notes last_check next_check state_type ".
		  "current_attempt max_check_attempts last_state_change ".
		  "last_hard_state_change statusmap_image perf_data ".
		  "acknowledged scheduled_downtime_depth ".
		  "has_been_checked\n".
		  "Filter: groups >= ".$hostgroupName."\n");
		
		if(count($l) == 0) {
			$arrReturn['name'] = '';
			$arrReturn['state'] = 'ERROR';
			$arrReturn['output'] = GlobalCore::getInstance()->getLang()->getText('The hostgroup [NAME] could not be found in backend [BACKENDID].', Array('BACKENDID' => $this->backendId, 'NAME' => $hostgroupName));
			return Array($arrReturn);
		}
		
		// Loop all hosts
		foreach($l AS $e) {
			$numResp = count($e);
			if($numResp != $numAttr) {
				$arrReturn['name'] = $e[0];
				$arrReturn['state'] = 'ERROR';
				$arrReturn['output'] = GlobalCore::getInstance()->getLang()->getText('The response from the backend is not valid (Asked for [NUMASKED] attributes, got [NUMRESPONSE]) in backend [BACKENDID]', Array('BACKENDID' => $this->backendId, 'NUMASKED' => $numAttr, 'NUMRESPONSE' => $numResp));
				return Array($arrReturn);
			}
			
			$arrTmpReturn = Array();
			$arrTmpReturn['name'] = $e[0];
			
			// Catch pending objects
			// $e[18]: has_been_checked
			// $e[1]:  state
			if($e[18] == 0 || $e[1] === '') {
					$arrTmpReturn['state'] = 'PENDING';
					$arrTmpReturn['output'] = GlobalCore::getInstance()->getLang()->getText('hostIsPending', Array('HOST' => $e[0]));
			} else {
				
				switch ($e[1]) {
					case "0": $state = "UP"; break;
					case "1": $state = "DOWN"; break;
					case "2": $state = "UNREACHABLE"; break;
					default: $state = "UNKNOWN"; break;
				}
				
				// If there is a downtime for this object, save the data
				// $e[17]: scheduled_downtime_depth
				if(isset($e[17]) && $e[17] > 0) {
					$arrTmpReturn['in_downtime'] = 1;
					
					// This handles only the first downtime. But this is not backend
					// specific. The other backends do this as well.
					$d = $this->queryLivestatusSingle(
					  "GET downtimes\n".
					  "Columns: author comment start_time end_time\n".
					  "Filter: host_name = ".$e[0]."\n");
					
					$arrTmpReturn['downtime_author'] = $d[0];
					$arrTmpReturn['downtime_data'] = $d[1];
					$arrTmpReturn['downtime_start'] = $d[2];
					$arrTmpReturn['downtime_end'] = $d[3];
				}
				
				$arrTmpReturn['state'] = $state;
				$arrTmpReturn['output'] = $e[2];
				$arrTmpReturn['alias'] = $e[3]; 
				$arrTmpReturn['display_name'] = $e[4];
				$arrTmpReturn['address'] = $e[5];
				$arrTmpReturn['notes'] = $e[6];
				$arrTmpReturn['last_check'] = $e[7];
				$arrTmpReturn['next_check'] = $e[8];
				$arrTmpReturn['state_type'] = $e[9];
				$arrTmpReturn['current_check_attempt'] = $e[10];
				$arrTmpReturn['max_check_attempts'] = $e[11];
				$arrTmpReturn['last_state_change'] = $e[12];
				$arrTmpReturn['last_hard_state_change'] = $e[13];
				$arrTmpReturn['statusmap_image'] = $e[14];
				$arrTmpReturn['perfdata'] = $e[15];
				$arrTmpReturn['problem_has_been_acknowledged'] = $e[16];
			}
			
			$arrReturn[] = $arrTmpReturn;
		}
		
		return $arrReturn;
	}
	
	/**
	 * PUBLIC getServicegroupState()
	 *
	 * Returns the all services in a hostgroup
	 *
	 * @param   String    $servicegroupName
	 * @param   Boolean   $onlyHardstates
	 * @return  array     $state
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getServicegroupState($servicegroupName, $onlyHardstates) {
		$result = Array();
		$arrReturn = Array();
		
		$stateAttr = 'state';
		
		// When only hardstates were requested ask for the hardstate
		if($onlyHardstates) {
			$stateAttr = 'last_hard_state';
		}
		
		$l = $this->queryLivestatus("GET services\n" .
		  "Filter: groups >= ".$servicegroupName."\n".
		  "Columns: host_name description display_name ".$stateAttr." ".
		  "host_alias host_address plugin_output notes last_check next_check ".
		  "state_type current_attempt max_check_attempts last_state_change ".
		  "last_hard_state_change scheduled_downtime_depth perf_data ".
		  "acknowledged host_acknowledged host_scheduled_downtime_depth ".
		  "has_been_checked\n");
		
		if(!is_array($l) || count($l) <= 0) {
			$arrReturn['host'] = '';
			$arrReturn['description'] = '';
			$arrReturn['state'] = 'ERROR';
			$arrReturn['output'] = GlobalCore::getInstance()->getLang()->getText('The servicegroup [GROUP] could not be found in the backend [BACKENDID].', Array('BACKENDID' => $this->backendId, 'GROUP' => $servicegroupName));
			return Array($arrReturn);
		} else {
			foreach($l as $e) {
				$arrTmpReturn = Array();
				$arrTmpReturn['host'] = $e[0];
				$arrTmpReturn['description'] = $e[1];
				$arrTmpReturn['display_name'] = $e[2];
				
				// Catch pending objects
				// $e[20]: has_been_checked
				// $e[3]:  state
				if($e[20] == 0 || $e[3] === '') {
						$arrTmpReturn['state'] = 'PENDING';
						$arrTmpReturn['output'] = GlobalCore::getInstance()->getLang()->getText('serviceNotChecked', Array('SERVICE' => $e[1]));
				} else {
					
					switch ($e[3]) {
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
					// $e[17]: acknowledged
					// $e[18]: host_acknowledged
					if($state != 'OK' && ($e[17] == 1 || $e[18] == 1)) {
						$arrTmpReturn['problem_has_been_acknowledged'] = 1;
					} else {
						$arrTmpReturn['problem_has_been_acknowledged'] = 0;
					}
					
					// Handle host/service downtimes
					// $e[15]: scheduled_downtime_depth
					// $e[19]: host_scheduled_downtime_depth
					if((isset($e[15]) && $e[15] > 0) || (isset($e[19]) && $e[19] > 0)) {
						$arrTmpReturn['in_downtime'] = 1;
						
						// This handles only the first downtime. But this is not backend
						// specific. The other backends do this as well.
						$d = $this->queryLivestatusSingle(
						  "GET downtimes\n".
						  "Columns: author comment start_time end_time\n" .
						  "Filter: host_name = ".$e[0]."\n" .
						  "Filter: service_description = ".$e[1]."\n");
						
						$arrTmpReturn['downtime_author'] = $d[0];
						$arrTmpReturn['downtime_data'] = $d[1];
						$arrTmpReturn['downtime_start'] = $d[2];
						$arrTmpReturn['downtime_end'] = $d[3];
					}
					
					$arrTmpReturn['state'] = $state;
					$arrTmpReturn['alias'] = $e[4];
					$arrTmpReturn['address'] = $e[5];
					$arrTmpReturn['output'] = $e[6];
					$arrTmpReturn['notes'] = $e[7];
					$arrTmpReturn['last_check'] = $e[8];
					$arrTmpReturn['next_check'] = $e[9];
					$arrTmpReturn['state_type'] = $e[10];
					$arrTmpReturn['current_check_attempt'] = $e[11];
					$arrTmpReturn['max_check_attempts'] = $e[12];
					$arrTmpReturn['last_state_change'] = $e[13];
					$arrTmpReturn['last_hard_state_change'] = $e[14];
					$arrTmpReturn['perfdata'] = $e[16];
				}
				
				$arrReturn[] = $arrTmpReturn;
			}
		}
		
		return $arrReturn;
	}
	
	/**
	 * PUBLIC getHostState()
	 *
	 * Queries the livestatus socket for the state of a host
	 *
	 * @param   String   Name of the host to query
	 * @param   Boolean  Only recognize hardstates
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostState($hostName, $onlyHardstates) {
		$arrReturn = Array();
		
		$stateAttr = 'state';
		
		// When only hardstates were requested ask for the hardstate
		if($onlyHardstates) {
			$stateAttr = 'hard_state';
		}
		
		$e = $this->queryLivestatusSingleRow(
		  "GET hosts\n".
		  "Columns: ".$stateAttr." plugin_output alias display_name ".
		  "address notes last_check next_check state_type ".
		  "current_attempt max_check_attempts last_state_change ".
		  "last_hard_state_change statusmap_image perf_data ".
		  "acknowledged scheduled_downtime_depth has_been_checked state\n".
		  "Filter: name = ".$hostName."\n");
		
		if(count($e) == 0) {
			$arrReturn['state'] = 'ERROR';
			$arrReturn['output'] = GlobalCore::getInstance()->getLang()->getText('hostNotFoundInDB', Array('BACKENDID' => $this->backendId, 'HOST' => $hostName));
			return $arrReturn;
		}
		
		// Catch pending objects
		// $e[17]: has_been_checked
		// $e[0]:  state
		if($e[17] == 0 || $e[0] === '') {
			$arrReturn['state'] = 'PENDING';
			$arrReturn['output'] = GlobalCore::getInstance()->getLang()->getText('hostIsPending', Array('HOST' => $hostName));
			return $arrReturn;
		}
		
		switch ($e[0]) {
			case "0": $state = "UP"; break;
			case "1": $state = "DOWN"; break;
			case "2": $state = "UNREACHABLE"; break;
			default: $state = "UNKNOWN"; break;
		}
		
		/**
		 * Handle host/service acks
		 *
		 * If state is not OK (=> WARN, CRIT, UNKNOWN) and service is not 
		 * acknowledged => check for acknowledged host
		 */
		// $e[15]: acknowledged
		if($state != 'OK' && $e[15] == 1) {
			$arrTmpReturn['problem_has_been_acknowledged'] = 1;
		} else {
			$arrTmpReturn['problem_has_been_acknowledged'] = 0;
		}
		
		// If there is a downtime for this object, save the data
		// $e[16]: scheduled_downtime_depth
		if(isset($e[16]) && $e[16] > 0) {
			$arrReturn['in_downtime'] = 1;
			
			// This handles only the first downtime. But this is not backend
			// specific. The other backends do this as well.
			$d = $this->queryLivestatusSingle(
			  "GET downtimes\n".
			  "Columns: author comment start_time end_time\n" .
			  "Filter: host_name = ".$hostName."\n");
			
			$arrReturn['downtime_author'] = $d[0];
			$arrReturn['downtime_data'] = $d[1];
			$arrReturn['downtime_start'] = $d[2];
			$arrReturn['downtime_end'] = $d[3];
		}

		$arrReturn['state'] = $state;
		$arrReturn['output'] = $e[1];
		$arrReturn['alias'] = $e[2]; 
		$arrReturn['display_name'] = $e[3];
		$arrReturn['address'] = $e[4];
		$arrReturn['notes'] = $e[5];
		$arrReturn['last_check'] = $e[6];
		$arrReturn['next_check'] = $e[7];
		$arrReturn['state_type'] = $e[8];
		$arrReturn['current_check_attempt'] = $e[9];
		$arrReturn['max_check_attempts'] = $e[10];
		$arrReturn['last_state_change'] = $e[11];
		$arrReturn['last_hard_state_change'] = $e[12];
		$arrReturn['statusmap_image'] = $e[13];
		$arrReturn['perfdata'] = $e[14];
		
		return $arrReturn;
	}
	
	/**
	 * PUBLIC getServiceState()
	 *
	 * Queries the livestatus socket for a specific service
	 * or all services of a host
	 *
	 * @param   String   Name of the host to query
	 * @param   String   Name of the service to query
	 * @param   Boolean  Only recognize hardstates
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getServiceState($hostName, $serviceName, $onlyHardstates) {
		$stateAttr = 'state';
		
		// When only hardstates were requested ask for the hardstate
		if($onlyHardstates) {
			$stateAttr = 'last_hard_state';
		}
		
		$query = 
		  "GET services\n" .
		  "Filter: host_name = ".$hostName."\n";
		
		if(isset($serviceName) && $serviceName != '') {
			$query .= "Filter: description = ".$serviceName."\n";
		}
		
		$query .= "Columns: description display_name ".$stateAttr." ".
		  "host_alias host_address plugin_output notes last_check next_check ".
		  "state_type current_attempt max_check_attempts last_state_change ".
		  "last_hard_state_change perf_data scheduled_downtime_depth ".
		  "acknowledged host_acknowledged host_scheduled_downtime_depth ".
		  "has_been_checked\n";
		
		$l = $this->queryLivestatus($query);
		
		$result = Array();
		$arrReturn = Array();
		
		if(!is_array($l) || count($l) <= 0) {
			if(isset($serviceName) && $serviceName != '') {
				$arrReturn['state'] = 'ERROR';
				$arrReturn['output'] = GlobalCore::getInstance()->getLang()->getText('serviceNotFoundInDB', Array('BACKENDID' => $this->backendId, 'SERVICE' => $serviceName, 'HOST' => $hostName));
			} else {
				// If the method should fetch all services of the host and does not find
				// any services for this host, don't return anything => The message
				// that the host has no services is added by the frontend
			}
		} else {
			foreach($l as $e) {
				$arrTmpReturn = Array();
				$arrTmpReturn['service_description'] = $e[0];
				$arrTmpReturn['display_name'] = $e[1];
				
				// Catch pending objects
				// $e[17]: has_been_checked
				// $e[2]:  state
				if($e[19] == 0 || $e[2] === '') {
						$arrTmpReturn['state'] = 'PENDING';
						$arrTmpReturn['output'] = GlobalCore::getInstance()->getLang()->getText('serviceNotChecked', Array('SERVICE' => $e[0]));
						return $arrReturn;
				} else {
				
					switch ($e[2]) {
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
						$d = $this->queryLivestatusSingle(
						  "GET downtimes\n".
						  "Columns: author comment start_time end_time\n" .
						  "Filter: host_name = ".$hostName."\n" .
						  "Filter: service_description = ".$e[0]."\n");
						
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
				
				$result[] = $arrTmpReturn;
			}
			
			if(isset($serviceName) && $serviceName != '') {
				$arrReturn = $result[0];
			} else {
				$arrReturn = $result;
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
		return $this->queryLivestatusSinglecolumn("GET hosts\nColumns: name\nFilter: parents =\n");
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
	
	/**
	 * PUBLIC getHostsByHostgroupName()
	 *
	 * Queries the livestatus socket for all hosts in a hostgroup
	 *
	 * @param   String   Hostgroupname
	 * @return  Array    List of hostnames
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostsByHostgroupName($hostgroupName) {
		return $this->queryLivestatusList("GET hostgroups\nColumns: members\nFilter: name = ".$hostgroupName."\n");
	}
	
	/**
	 * PUBLIC getServicesByServicegroupName()
	 *
	 * Queries the livestatus socket for all services in a servicegroup
	 *
	 * @param   String   Servicegroup
	 * @return  Array    List of services
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getServicesByServicegroupName($servicegroupName) {
		$l = $this->queryLivestatusList("GET servicegroups\nColumns: members\nFilter: name = ".$servicegroupName."\n");
		
		$result = Array();
		foreach($l as $line) {
			$result[] = Array('host_name' => $line[0], 'service_description' => $line[1]);
		}
		
		return $result;
	}
	
	/**
	 * PUBLIC getServicegroupInformations()
	 *
	 * Queries the livestatus socket for additional servicegroup information
	 *
	 * @param   String   Servicegroup name
	 * @return  Array    List of attributes
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getServicegroupInformations($servicegroupName) {
		$l = $this->queryLivestatusSingle("GET servicegroups\nColumns: alias\nFilter: name = ".$servicegroupName."\n");
		return Array('alias' => $l);
	}
	
	/**
	 * PUBLIC getHostgroupInformations()
	 *
	 * Queries the livestatus socket for additional hostgroup information
	 *
	 * @param   String   Hostgroup name
	 * @return  Array    List of attributes
   * @author  Mathias Kettner <mk@mathias-kettner.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostgroupInformations($hostgroupName) {
		$l = $this->queryLivestatusSingle("GET hostgroups\nColumns: alias\nFilter: name = ".$hostgroupName."\n");
		return Array('alias' => $l);
	}
	
	/**
	 * PUBLIC getHostStateCounts()
	 *
	 * Queries the livestatus socket for host state counts. The information
	 * are used to calculate the summary output and the summary state of a 
	 * host and a well performing alternative to the existing recurisve
	 * algorithm.
	 *
	 * @param   String   Host name
	 * @param   Boolean  Only recognize hard states
	 * @return  Array    List of states and counts
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostStateCounts($hostName, $onlyHardstates) {
		$aReturn = Array();
		
		$stateAttr = 'state';
		
		// When only hardstates were requested ask for the hardstate
		if($onlyHardstates) {
			$stateAttr = 'last_hard_state';
		}
		
		// Get service information
		$services = $this->queryLivestatusSingleRow("GET services\n" .
		   "Filter: host_name = ".$hostName."\n" .
		   /*FIXME: Implement as optional filter: "Filter: in_notification_period = 1\n" .*/
		   // Count PENDING
		   "Stats: has_been_checked = 0\n" .
		   // Count OK
		   "Stats: ".$stateAttr." = 0\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "Stats: host_scheduled_downtime_depth = 1\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "Stats: host_scheduled_downtime_depth = 1\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "Stats: host_scheduled_downtime_depth = 1\n" .
		   "StatsOr: 2\n" .
		   "StatsAnd: 2\n");
		
		$aReturn['PENDING']['normal'] = $services[0];
		$aReturn['OK']['normal'] = $services[1];
		$aReturn['WARNING']['normal'] = $services[2];
		$aReturn['WARNING']['ack'] = $services[3];
		$aReturn['WARNING']['downtime'] = $services[4];
		$aReturn['CRITICAL']['normal'] = $services[5];
		$aReturn['CRITICAL']['ack'] = $services[6];
		$aReturn['CRITICAL']['downtime'] = $services[7];
		$aReturn['UNKNOWN']['normal'] = $services[8];
		$aReturn['UNKNOWN']['ack'] = $services[9];
		$aReturn['UNKNOWN']['downtime'] = $services[10];
		
		return $aReturn;
	}
	
	
	/**
	 * PUBLIC getHostgroupHostStateCounts()
	 *
	 * Queries the livestatus socket for a bunch of host state count.
	 * This seems to be the fastest way to get the information needed to
	 * build hover menus for the NagVis maps (state, substate, summary output)
	 *
	 * @param   String   Name of the hostgroup
	 * @param   Boolean  Only recognize hard states
	 * @return  Array    List of states and counts
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostgroupHostStateCounts($hostgroupName, $onlyHardstates) {
		$aReturn = Array();
		
		$stateAttr = 'state';
		
		// When only hardstates were requested ask for the hardstate
		if($onlyHardstates) {
			$stateAttr = 'last_hard_state';
		}
		
		// Get service information
		$query = "GET services\n" .
		         "Filter: host_groups >= ".$hostgroupName."\n" .
		         /*FIXME: Implement as optional filter: "Filter: in_notification_period = 1\n" .*/
						 // Count PENDING
		         "Stats: has_been_checked = 0\n" .
		         // Count OK
		         "Stats: ".$stateAttr." = 0\n" .
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
		         "Stats: scheduled_downtime_depth = 1\n" .
		         "Stats: host_scheduled_downtime_depth = 1\n" .
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
		         "Stats: scheduled_downtime_depth = 1\n" .
		         "Stats: host_scheduled_downtime_depth = 1\n" .
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
		         "Stats: scheduled_downtime_depth = 1\n" .
		         "Stats: host_scheduled_downtime_depth = 1\n" .
		         "StatsOr: 2\n" .
		         "StatsAnd: 2\n" .
		         "StatsGroupBy: host_name\n";
		
		$services = $this->queryLivestatus($query);
		
		foreach($services AS $service) {
			$aReturn[$service[0]] = Array();
			$aReturn[$service[0]]['PENDING']['normal'] = $service[1];
			$aReturn[$service[0]]['OK']['normal'] = $service[2];
			$aReturn[$service[0]]['WARNING']['normal'] = $service[3];
			$aReturn[$service[0]]['WARNING']['ack'] = $service[4];
			$aReturn[$service[0]]['WARNING']['downtime'] = $service[5];
			$aReturn[$service[0]]['CRITICAL']['normal'] = $service[6];
			$aReturn[$service[0]]['CRITICAL']['ack'] = $service[7];
			$aReturn[$service[0]]['CRITICAL']['downtime'] = $service[8];
			$aReturn[$service[0]]['UNKNOWN']['normal'] = $service[9];
			$aReturn[$service[0]]['UNKNOWN']['ack'] = $service[10];
			$aReturn[$service[0]]['UNKNOWN']['downtime'] = $service[11];
		}
		
		return $aReturn;
	}
	
	/**
	 * PUBLIC getHostgroupStateCounts()
	 *
	 * Queries the livestatus socket for hostgroup state counts. The information
	 * are used to calculate the summary output and the summary state of a 
	 * hostgroup and a well performing alternative to the existing recurisve
	 * algorithm.
	 *
	 * @param   String   Hostgroup name
	 * @param   Boolean  Only recognize hard states
	 * @return  Array    List of states and counts
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostgroupStateCounts($hostgroupName, $onlyHardstates) {
		$aReturn = Array();
		
		$stateAttr = 'state';
		
		// When only hardstates were requested ask for the hardstate
		if($onlyHardstates) {
			$stateAttr = 'hard_state';
		}
		
		// Get host information
		$hosts = $this->queryLivestatusSingleRow("GET hosts\n" .
		   "Filter: groups >= ".$hostgroupName."\n" .
		   /*FIXME: Implement as optional filter: "Filter: in_notification_period = 1\n" .*/
		   // Count PENDING
		   "Stats: has_been_checked = 0\n" .
		   // Count UP
		   "Stats: ".$stateAttr." = 0\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "StatsAnd: 2\n");
		
		$aReturn['PENDING']['normal'] = $hosts[0];
		$aReturn['UP']['normal'] = $hosts[1];
		$aReturn['DOWN']['normal'] = $hosts[2];
		$aReturn['DOWN']['ack'] = $hosts[3];
		$aReturn['DOWN']['downtime'] = $hosts[4];
		$aReturn['UNREACHABLE']['normal'] = $hosts[5];
		$aReturn['UNREACHABLE']['ack'] = $hosts[6];
		$aReturn['UNREACHABLE']['downtime'] = $hosts[7];
		
		// When only hardstates were requested ask for the hardstate
		if($onlyHardstates) {
			$stateAttr = 'last_hard_state';
		}
		
		// Get service information
		$services = $this->queryLivestatusSingleRow("GET services\n" .
		   "Filter: host_groups >= ".$hostgroupName."\n" .
		   /*FIXME: Implement as optional filter: "Filter: in_notification_period = 1\n" .*/
		   // Count PENDING
		   "Stats: has_been_checked = 0\n" .
		   // Count OK
		   "Stats: ".$stateAttr." = 0\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "Stats: host_scheduled_downtime_depth = 1\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "Stats: host_scheduled_downtime_depth = 1\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "Stats: host_scheduled_downtime_depth = 1\n" .
		   "StatsOr: 2\n" .
		   "StatsAnd: 2\n");
		
		$aReturn['PENDING']['normal'] = $services[0];
		$aReturn['OK']['normal'] = $services[1];
		$aReturn['WARNING']['normal'] = $services[2];
		$aReturn['WARNING']['ack'] = $services[3];
		$aReturn['WARNING']['downtime'] = $services[4];
		$aReturn['CRITICAL']['normal'] = $services[5];
		$aReturn['CRITICAL']['ack'] = $services[6];
		$aReturn['CRITICAL']['downtime'] = $services[7];
		$aReturn['UNKNOWN']['normal'] = $services[8];
		$aReturn['UNKNOWN']['ack'] = $services[9];
		$aReturn['UNKNOWN']['downtime'] = $services[10];
		
		return $aReturn;
	}
	
	/**
	 * PUBLIC getServicegroupStateCounts()
	 *
	 * Queries the livestatus socket for service state counts. The information
	 * are used to calculate the summary output and the summary state of a 
	 * group and a well performing alternative to the existing recurisve
	 * algorithm.
	 *
	 * @param   String   Servicegroup name
	 * @param   Boolean  Only recognize hard states
	 * @return  Array    List of states and counts
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getServicegroupStateCounts($servicegroupName, $onlyHardstates) {
		$aReturn = Array();
		
		$stateAttr = 'state';
		
		// When only hardstates were requested ask for the hardstate
		if($onlyHardstates) {
			$stateAttr = 'last_hard_state';
		}
		
		// Get service information
		$services = $this->queryLivestatusSingleRow("GET services\n" .
		   "Filter: groups >= ".$servicegroupName."\n" .
		   /*FIXME: Implement as optional filter: "Filter: in_notification_period = 1\n" .*/
		   // Count PENDING
		   "Stats: has_been_checked = 0\n" .
		   // Count OK
		   "Stats: ".$stateAttr." = 0\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "Stats: host_scheduled_downtime_depth = 1\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "Stats: host_scheduled_downtime_depth = 1\n" .
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
		   "Stats: scheduled_downtime_depth = 1\n" .
		   "Stats: host_scheduled_downtime_depth = 1\n" .
		   "StatsOr: 2\n" .
		   "StatsAnd: 2\n");
		
		$aReturn['PENDING']['normal'] = $services[0];
		$aReturn['OK']['normal'] = $services[1];
		$aReturn['WARNING']['normal'] = $services[2];
		$aReturn['WARNING']['ack'] = $services[3];
		$aReturn['WARNING']['downtime'] = $services[4];
		$aReturn['CRITICAL']['normal'] = $services[5];
		$aReturn['CRITICAL']['ack'] = $services[6];
		$aReturn['CRITICAL']['downtime'] = $services[7];
		$aReturn['UNKNOWN']['normal'] = $services[8];
		$aReturn['UNKNOWN']['ack'] = $services[9];
		$aReturn['UNKNOWN']['downtime'] = $services[10];
		
		return $aReturn;
	}
}
?>
