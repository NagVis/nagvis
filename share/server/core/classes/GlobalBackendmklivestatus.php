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
	private $socketPath = '';
	
	// These are the backend local configuration options
	private static $validConfig = Array(
		'socket_path' => Array('must' => 1,
		  'editable' => 1,
		  'default' => '/usr/local/nagios/var/rw/live',
		  'match' => MATCH_STRING_PATH));
	
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
		
		$this->socketPath = GlobalCore::getInstance()->getMainCfg()->getValue('backend_'.$backendId, 'socket_path');
		
		// Run preflight checks
		if(!$this->checkSocketExists()) {
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('The livestatus socket [SOCKET] in backend [BACKENDID] does not exist', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
		}
		
		return true;
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
		$sock = socket_create(AF_UNIX, SOCK_STREAM, 0);
		
		if($sock == false) {
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Could not create livestatus socket [SOCKET] in backend [BACKENDID].', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
			return Array();
		}
		
		$result = socket_connect($sock, $this->socketPath);
		
		if($result == false) {
			new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Unable to connect to the [SOCKET] in backend [BACKENDID]: [MSG]', Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath, 'MSG' => socket_strerror(socket_last_error($sock)))));
			return Array();
		}
		
		// Query to get a json formated array back
		socket_write($sock, $query . "OutputFormat:json\n");
		socket_shutdown($sock, 1);
		
		$read = '';
		while('' != ($r = socket_read($sock, 65536))) {
			$read .= $r;
		}
		
		socket_close($sock);
		
		return json_decode($read);
	}
	
	/**
	 * PRIVATE queryLivestatusSingle()
	 *
	 * Queries the livestatus socket for a single row
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
	 * PRIVATE queryLivestatusSinglecolumn()
	 *
	 * Queries the livestatus socket for a single column
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
			$list = $line[0];
			if($list) {
				$members = explode(",", $list);
				foreach ($members as $m) {
					$result[] = $m;
				}
			}
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
	 * Returns the Nagios state and additional information for the requested hostgroup
	 *
	 * FIXME: Not implemented in this backend
	 *
	 * @param	String		$hostgroupName
	 * @param	Boolean		$onlyHardstates
	 * @return	array		$state
	 * @author	Roman Kyrylych <rkyrylych@op5.com>
	 */
	public function getHostgroupState($hostgroupName, $onlyHardstates) {
		$arrReturn = Array();
		
		return $arrReturn;
	}
	
	/**
	 * PUBLIC getServicegroupState()
	 *
	 * Returns the Nagios state and additional information for the requested servicegroup
	 *
	 * FIXME: Not implemented in this backend
	 *
	 * @param	String		$servicegroupName
	 * @param	Boolean		$onlyHardstates
	 * @return	array		$state
	 * @author	Roman Kyrylych <rkyrylych@op5.com>
	 */
	public function getServicegroupState($servicegroupName, $onlyHardstates) {
		$arrReturn = Array();
		
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
		
		$l = $this->queryLivestatus(
		  "GET hosts\n".
		  "Columns: state plugin_output alias display_name ".
		  "address notes last_check next_check state_type ".
		  "current_attempt max_attempts last_state_change ".
		  "last_hard_state_change statusmap_image perf_data ".
		  "last_hard_state acknowledged downtimes\n".
		  "Filter: name = ".$hostName."\n");
		
		if(count($l) == 0) {
			$arrReturn['state'] = 'ERROR';
			$arrReturn['output'] = GlobalCore::getInstance()->getLang()->getText('hostNotFoundInDB', Array('BACKENDID' => $this->backendId, 'HOST' => $hostName));
			return $arrReturn;
		}
		
		$e = $l[0];
		
		/**
		 * Only recognize hard states. There was a discussion about the implementation
		 * This is a new handling of only_hard_states. For more details, see: 
		 * http://www.nagios-portal.de/wbb/index.php?page=Thread&threadID=8524
		 *
		 * Thanks to Andurin and fredy82
		 */
		if($onlyHardstates == 1) {
			// $e[8]: state_type
			if($e[8] == '0') {
				// state = last hard state
				$e[0] = $e[15];
			}
		}
		
		switch ($e[0]) {
			case "0": $state = "UP"; break;
			case "1": $state = "DOWN"; break;
			case "2": $state = "UNREACHABLE"; break;
			default: $state = "UNKNOWN"; break;
		}
		
		// If there is a downtime for this object, save the data
		// $e[17]: downtimes
		if(isset($e[17]) && $e[17] != '') {
			$arrReturn['in_downtime'] = 1;
			
			// FIXME: echo -e 'GET downtimes\nFilter: description = HTTP' | ../src/check_mk-1.1.0beta1/livestatus/unixcat  ../nagios/var/rw/live
			// FIXME: Read downtime details
			/*$sFile = $this->pathPersistent.'/DOWNTIME/'.$hostName;
			if(file_exists($sFile)) {
				$oDowntime = json_decode(file_get_contents($sFile));
				
				$arrReturn['downtime_start'] = $oDowntime->STARTTIME;
				$arrReturn['downtime_end'] = $oDowntime->ENDTIME;
				$arrReturn['downtime_author'] = $oDowntime->AUTHORNAME;
				$arrReturn['downtime_data'] = $oDowntime->COMMENT;
			}*/
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
		$arrReturn['problem_has_been_acknowledged'] = $e[16];
		
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
		$query = 
		  "GET services\n" .
		  "Filter: host_name = ".$hostName."\n";
		
		if(isset($serviceName) && $serviceName != '') {
			$query .= "Filter: description = ".$serviceName."\n";
		}
		
		$query .= "Columns: description display_name state ".
		  "host_alias host_address plugin_output notes last_check next_check ".
		  "state_type current_attempt max_attempts last_state_change ".
		  "last_hard_state_change scheduled_downtime_depth perf_data ".
		  "last_hard_state acknowledged host_acknowledged downtimes host_downtimes\n";
		
		$l = $this->queryLivestatus($query);
		
		reset($l);
		
		$result = Array();
		$arrReturn = Array();
		
		if(count($l) <= 0) {
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
				
				/**
				 * Only recognize hard states. There was a discussion about the implementation
				 * This is a new handling of only_hard_states. For more details, see: 
				 * http://www.nagios-portal.de/wbb/index.php?page=Thread&threadID=8524
				 *
				 * Thanks to Andurin and fredy82
				 */
				if($onlyHardstates == 1) {
					// state_type
					if($e[9] == '0') {
						// state = last hard state
						$e[2] = $e[16];
					}
				}
				
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
				// $e[17]: acknowledged
				if($state != 'OK' && $e[17] != 1) {
					// $e[18]: host_acknowledged
					$arrTmpReturn['problem_has_been_acknowledged'] = $e[18];
				} else {
					$arrTmpReturn['problem_has_been_acknowledged'] = $e[17];
				}
				
				// Handle host/service downtimes
				// $e[19]: downtimes
				if(isset($e[19]) && $e[19] != '') {
					$arrTmpReturn['in_downtime'] = 1;
					
					// FIXME: echo -e 'GET downtimes\nFilter: description = HTTP' | ../src/check_mk-1.1.0beta1/livestatus/unixcat  ../nagios/var/rw/live
					/* FIXME: downtime information
					$sFile = $this->pathPersistent.'/DOWNTIME/'.$hostName.'::'.strtr($aServObj[$i][0]->SERVICEDESCRIPTION,' ','_');
					if(file_exists($sFile)) {
						$oDowntime = json_decode(file_get_contents($sFile));
						
						$arrTmpReturn['downtime_start'] = $oDowntime->STARTTIME;
						$arrTmpReturn['downtime_end'] = $oDowntime->ENDTIME;
						$arrTmpReturn['downtime_author'] = $oDowntime->AUTHORNAME;
						$arrTmpReturn['downtime_data'] = $oDowntime->COMMENT;
					}*/
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
				$arrTmpReturn['in_downtime'] = $e[14] > 0;
				$arrTmpReturn['perfdata'] = $e[15];
				
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
			$parts = explode('|', $line);
			$x = Array('host_name' => $parts[0], 'service_description' => $parts[1]);
			$result[] = $x;
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
}
?>
