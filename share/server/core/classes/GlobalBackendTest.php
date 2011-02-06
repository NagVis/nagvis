<?php
/*****************************************************************************
 *
 * GlobalBackendTest.php
 *
 * Copyright (c) 2010 NagVis Project  (Contact: info@nagvis.org),
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
 * @author  Lars Michelsen  <lars@vertical-visions.de>
 */
class GlobalBackendTest implements GlobalBackendInterface {
	private $CORE = null;
	private $backendId = '';

	private $obj = Array(
		'host' => Array(),
		'service' => Array(),
		'hostgroup' => Array(),
		'servicegroup' => Array(),
	);
	private $hostStates = Array(
		'UP'          => Array('normal' => 0, 'downtime' => 0),
		'DOWN'        => Array('normal' => 0, 'ack' => 0, 'downtime' => 0),
		'UNREACHABLE' => Array('normal' => 0, 'ack' => 0, 'downtime' => 0),
		'UNCHECKED'   => Array('normal' => 0, 'downtime' => 0),
	);
	private $serviceStates = Array(
		'OK'          => Array('normal' => 0, 'downtime' => 0),
		'WARNING'     => Array('normal' => 0, 'ack' => 0, 'downtime' => 0),
		'CRITICAL'    => Array('normal' => 0, 'ack' => 0, 'downtime' => 0),
		'UNKNOWN'     => Array('normal' => 0, 'ack' => 0, 'downtime' => 0),
		'PENDING'     => Array('normal' => 0, 'downtime' => 0),
	);

	private $canBeSoft = Array(
		'UP'          => Array('hard'),
		'DOWN'        => Array('hard', 'soft'),
		'UNREACHABLE' => Array('hard', 'soft'),
		'UNCHECKED'   => Array('hard'),
		'PENDING'     => Array('hard'),
		'OK'          => Array('hard'),
		'WARNING'     => Array('hard', 'soft'),
		'CRITICAL'    => Array('hard', 'soft'),
		'UNKNOWN'     => Array('hard'),
	);
	
	/**
	 * PUBLIC class constructor
	 *
	 * @param   GlobalCore    Instance of the NagVis CORE
	 * @param   String        ID if the backend
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $backendId) {
		$this->CORE = $CORE;
		$this->backendId = $backendId;
		//$this->now = time();
		$this->now = 1288565304;
		$this->genObj();

		if(isset($_POST['new']) || !file_exists('/usr/local/nagvis/etc/maps/test-gen.cfg')) {
			$this->genMapCfg('/usr/local/nagvis/etc/maps/test-gen.cfg');
		}

		return true;
	}

	private function host($name, $state, $stateType = 'hard', $substate = 'normal') {
		$host = Array('name'                     => $name,
			             'alias'                  => 'Alias host-'.$name,
				           'state'                  => $state,
				           'output'                 => 'Host with state '.$state,
				           'display_name'           => 'Display Name host-'.$name,
				           'address'                => 'localhost',
				           'notes'                  => '',
				           'last_check'             => $this->now-60,
				           'next_check'             => $this->now+60,
				           'state_type'             => $stateType == 'hard' ? 1 : 0,
				           'current_check_attempt'  => 3,
				           'max_check_attempts'     => 3,
				           'last_state_change'      => $this->now-60,
				           'last_hard_state_change' => $this->now-60,
				           'statusmap_image'        => '',
				 					 'perfdata'               => '',
				 					 'problem_has_been_acknowledged' => 0,
				 					 'in_downtime'            => 0,
		);

		if($substate == 'downtime') {
			$host['in_downtime']     = 1;
			$host['downtime_author'] = 'Kunibert';
			$host['downtime_data']   = 'xyz';
			$host['downtime_start']  = $this->now-60;
			$host['downtime_end']    = $this->now+60;
		} elseif($substate == 'ack')
			$host['problem_has_been_acknowledged'] = 1;

		return $host;
	}

	private function service($name1, $name2, $state, $stateType = 'hard', $substate = 'normal') {
		$s = Array(
				  'name'                          => $name1,
				  'service_description'           => $name2,
				  'display_name'                  => 'display name '.$name2,
				  'state'                         => $state,
				  'problem_has_been_acknowledged' => 0,
				  'in_downtime'                   => 0,
				  'alias'                         => 'alias '.$name2,
				  'address'                       => 'localhost',
				  'output'                        => 'output '.$name2,
				  'notes'                         => '',
				  'last_check'                    => $this->now-60,
				  'next_check'                    => $this->now+60,
				  'state_type'                    => $stateType == 'hard' ? 1 : 0,
				  'current_check_attempt'         => 3,
				  'max_check_attempts'            => 3,
				  'last_state_change'             => $this->now-60,
				  'last_hard_state_change'        => $this->now-60,
				  'perfdata'                      => '',
				);
		if($substate == 'downtime') {
			$s['in_downtime']     = 1;
			$s['downtime_author'] = 'Kunibert';
			$s['downtime_data']   = 'xyz';
			$s['downtime_start']  = $this->now-60;
			$s['downtime_end']    = $this->now+60;
		} elseif($substate == 'ack')
			$s['problem_has_been_acknowledged'] = 1;

		return $s;
	}

	private function hostgroup($name, $members) {
			return  Array('name'  => $name,
		                'alias' => 'Alias '.$name,
					          'members' => $members);
	}

	private function servicegroup($name, $members) {
		return Array('name'  => $name,
		             'alias' => 'Alias '.$name,
		             'members' => $members);
	}

	private function genObj() {
		/**
		 * a) HOSTS without services of all states/substates
		 */
		foreach($this->hostStates AS $state => $substates) {
			foreach($this->canBeSoft[$state] AS $stateType) {
				foreach(array_keys($substates) AS $substate) {
					$ident    = 'host-'.$state.'-'.$stateType.'-'.$substate;
					$hostname = $ident;

					$this->obj['host'][$hostname] = $this->host($hostname, $state, $stateType, $substate);
					$this->obj['service'][$hostname] = Array();
					$this->obj['hostgroup']['hostgroup-'.$ident] = $this->hostgroup('hostgroup-'.$ident, Array($hostname));
				}
			}
		}

		/**
		 * b) SERVICES of all states/substates
		 */
		foreach($this->serviceStates AS $state => $substates) {
			foreach($this->canBeSoft[$state] AS $stateType) {
				foreach(array_keys($substates) AS $substate) {
					$ident = 'service-'.$state.'-'.$substate;
					$hostname = 'host-'.$ident;
					$this->obj['host'][$hostname] = $this->host($hostname, 'UNCHECKED');
					$this->obj['service'][$hostname] = Array($this->service($hostname, $ident, $state, $stateType, $substate));
					$this->obj['hostgroup']['hostgroup-'.$ident] = $this->hostgroup('hostgroup-'.$ident, Array($hostname));
					$this->obj['servicegroup']['servicegroup-'.$ident] = $this->servicegroup('servicegroup-'.$ident, Array(Array($hostname, $ident)));
				}
			}
		}

		/**
		 * c) HOSTS of all states with one of all service states
		 */
		foreach($this->hostStates AS $hostState => $hostSubstates) {
			foreach($this->canBeSoft[$hostState] AS $hostStateType) {
				foreach(array_keys($hostSubstates) AS $hostSubstate) {
					// Now service stuff
					foreach($this->serviceStates AS $state => $substates) {
						foreach($this->canBeSoft[$state] AS $stateType) {
							foreach(array_keys($substates) AS $substate) {
								$ident = 'host-'.$hostState.'-'.$hostStateType.'-'.$hostSubstate.'-service-'.$state.'-'.$substate;
								$hostname = $ident;

								$this->obj['host'][$hostname] = $this->host($hostname, $hostState, $hostStateType, $hostSubstate);
								$this->obj['service'][$hostname] = Array($this->service($hostname, $ident, $state, $stateType, $substate));
								$this->obj['hostgroup']['hostgroup-'.$ident] = $this->hostgroup('hostgroup-'.$ident, Array($hostname));
								$this->obj['servicegroup']['servicegroup-'.$ident] = $this->servicegroup('servicegroup-'.$ident, Array(Array($hostname, $ident)));
							}
						}
					}
				}
			}
		}
	}

	function getAllTypeObjects($type) {
		if($type == 'service') {
			$s = Array();
			foreach($this->obj['service'] AS $services) {
				$s = array_merge($s, $services);
			}
			return $s;
		} else
			return $this->obj[$type];
	}

	function genMapCfg($path) {
		$f = "define global {\n"
		    ."  backend_id=test_1\n"
		    ."}\n"
				."\n";
		$x = 0;
		$y = 0;
		foreach(array_keys($this->obj) AS $type) {
			foreach($this->getAllTypeObjects($type) AS $obj) {
				$t = $type == 'service' ? 'host' : $type;
				$f .= "define ".$type." {\n"
				     ."  ".$t."_name=".$obj['name']."\n";
				if($type == 'service')
					$f .= "  service_description=".$obj['service_description']."\n";
				$f .= "  x=".$x."\n"
				     ."  y=".$y."\n"
			       ."}\n"
					   ."\n";
				$x += 22;
				if($x > 1800) {
					$y += 22;
					$x = 0;
				}
			}
			$y += 44;
			$x = 0;
		}

		file_put_contents($path, $f);
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
	public function __destruct() {}
	
	/**
	 * PUBLIC getValidConfig
	 * 
	 * Returns the valid config for this backend
	 *
	 * @return	Array
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getValidConfig() {
		return Array();
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
		switch($type) {
			case 'host':
			case 'hostgroup':
			case 'servicegroup':
				$l = $this->obj[$type];
			break;
			case 'service':
				if($name1Pattern) {
					$l = $this->obj[$type][$name1Pattern];
				} else {
					throw new BackendException('Unhandled query');
					exit;
				}
			break;
			default:
				return Array();
			break;
		}
		
		$result = Array();
		foreach($l as $entry) {
			if($type != 'service') {
				$result[] = Array('name1' => $entry['name'], 'name2' => $entry['alias']);
			} else {
				$result[] = Array('name1' => $entry['service_description'], 'name2' => $entry['service_description']);
			}
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
		/*if($options & 1)
			$stateAttr = 'hard_state';
		else
			$stateAttr = 'state';*/

		$arrReturn = Array();
		if(count($filters) == 1 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '=') {
			foreach($objects AS $OBJS) {
				$name = $OBJS[0]->getName();
				if(isset($this->obj['host'][$name]))
					$arrReturn[$name] = $this->obj['host'][$name];
			}
		} elseif(count($filters) == 1 && $filters[0]['key'] == 'host_groups' && $filters[0]['op'] == '>=') {
			foreach($objects AS $OBJS) {
				$name = $OBJS[0]->getName();
				foreach($this->obj['hostgroup'][$name]['members'] AS $hostname) {
					$host = $this->obj['host'][$hostname];
					$arrReturn[$hostname] = $this->obj['host'][$hostname];
				}
			}
		} else {
			throw new BackendException('Unhandled query - filters: '.json_encode($filters));
			exit;
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
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getServiceState($objects, $options, $filters) {
		$objFilter = $this->parseFilter($objects, $filters);
		/*if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'state';*/

		$arrReturn = Array();
		if(count($filters) == 1 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '=') {
			// All services of a host
			foreach($objects AS $OBJS) {
				$arrReturn[$OBJS[0]->getName()] = $this->obj['service'][$OBJS[0]->getName()];
			}
		} elseif(count($filters) == 1 && $filters[0]['key'] == 'service_groups' && $filters[0]['op'] == '>=') {
			// All services of a servicegroup
			foreach($objects AS $OBJS) {
				$name = $OBJS[0]->getName();

				// Skip not existing objects
				if(!isset($this->obj['servicegroup'][$name]))
					continue;

				foreach($this->obj['servicegroup'][$name]['members'] AS $attr) {
					list($name1, $name2) = $attr;
					foreach($this->obj['service'][$name1] AS $service) {
						if($service['service_description'] != $name2)
							continue;
						$arrReturn[$name1][] = $service;
					}
				}
			}
		} elseif(count($filters) == 2 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '='
			&& $filters[1]['key'] == 'service_description' && $filters[1]['op'] == '=') {
			// One specific service of a host
			foreach($objects AS $OBJS) {
				foreach($arrReturn[$OBJS[0]->getName()] = $this->obj['service'][$OBJS[0]->getName()] AS $service) {
					if($service['service_description'] == $OBJS[0]->getServiceDescription()) {
						$arrReturn[$OBJS[0]->getName().'~~'.$OBJS[0]->getServiceDescription()] = $service;
					}
				}
			}
		} else {
			throw new BackendException('Unhandled filter in backend (getServiceState): '.json_encode($filters));
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
		/*if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'state';*/

		$aReturn = Array();
		if(count($filters) == 1 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '=') {
			// Get service state counts of one host
			foreach($objects AS $OBJS) {
				$name = $OBJS[0]->getName();
				$aReturn[$name] = Array('counts' => $this->serviceStates);
				if(isset($this->obj['service'][$name]))
					foreach($this->obj['service'][$name] AS $service) {
						if($service['problem_has_been_acknowledged'] == 1)
							$aReturn[$name]['counts'][$service['state']]['ack']++;
						elseif($service['in_downtime'] == 1)
							$aReturn[$name]['counts'][$service['state']]['downtime']++;
						else
							$aReturn[$name]['counts'][$service['state']]['normal']++;
				}
			}
		} elseif(count($filters) == 1 && $filters[0]['key'] == 'host_groups' && $filters[0]['op'] == '>=') {
			// Get service state counts for all hosts in a hostgroup (separated by host)
			foreach($objects AS $OBJS) {
				$name = $OBJS[0]->getName();
				foreach($this->obj['hostgroup'][$name]['members'] AS $hostname) {
					$resp = $this->getHostStateCounts(Array(Array(new NagVisHost($this->CORE, $this, $this->backendId, $hostname))), $options, Array(Array('key' => 'host_name', 'op' => '=', 'val' => 'name')));
					$aReturn[$hostname] = $resp[$hostname];
				}
			}
		} else {
			throw new BackendException('Unhandled filter in backend (getHostStateCounts)');
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
	 * @param   Array     List of objects to query
	 * @param   Array     List of filters to apply
	 * @return  Array     List of states and counts
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHostgroupStateCounts($objects, $options, $filters) {
		/*if($options & 1)
			$stateAttr = 'hard_state';
		else
			$stateAttr = 'state';*/

		$aReturn = Array();
		// Get all host and service states of a hostgroup
		if(count($filters) == 1 && $filters[0]['key'] == 'groups' && $filters[0]['op'] == '>=') {
			foreach($objects AS $OBJS) {
				$name = $OBJS[0]->getName();
				if(!isset($aReturn[$name]))
					$aReturn[$name] = Array('counts' => array_merge($this->hostStates, $this->serviceStates));
				foreach($this->obj['hostgroup'][$name]['members'] AS $hostname) {
					$host = $this->obj['host'][$hostname];

					if($host['problem_has_been_acknowledged'] == 1)
						$aReturn[$name]['counts'][$host['state']]['ack']++;
					elseif($host['in_downtime'] == 1)
						$aReturn[$name]['counts'][$host['state']]['downtime']++;
					else
						$aReturn[$name]['counts'][$host['state']]['normal']++;

		      // If recognize_services are disabled don't fetch service information
					if($options & 2)
						continue;

					$resp = $this->getHostStateCounts(Array(Array(new NagVisHost($this->CORE, $this, $this->backendId, $hostname))), $options,
					                                                    Array(Array('key' => 'host_name', 'op' => '=', 'val' => 'name')));
					foreach($resp[$hostname]['counts'] AS $state => $substates)
						foreach($substates AS $substate => $count)
							$aReturn[$name]['counts'][$state][$substate] += $count;
				}
			}
		} else {
			throw new BackendException('Unhandled filter in backend (getHostStateCounts)');
			exit;
		}

		return $aReturn;
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
		/*if($options & 1)
			$stateAttr = 'last_hard_state';
		else
			$stateAttr = 'state';*/
		$aReturn = Array();
		// Get all service state counts of a servicegroup
		if(count($filters) == 1 && $filters[0]['key'] == 'groups' && $filters[0]['op'] == '>=') {
			foreach($objects AS $OBJS) {
				$name = $OBJS[0]->getName();

				// Skip not existing objects
				if(!isset($this->obj['servicegroup'][$name]))
					continue;

				if(!isset($aReturn[$name]))
					$aReturn[$name] = Array('counts' => Array());

				foreach($this->obj['servicegroup'][$name]['members'] AS $attr) {
					list($name1, $name2) = $attr;
					foreach($this->obj['service'][$name1] AS $service) {
						if($service['service_description'] != $name2)
							continue;

						$state = $service['state'];
						if(!isset($aReturn[$name]['counts'][$state]))
							$aReturn[$name]['counts'][$state] = $this->serviceStates[$state];

						if($service['problem_has_been_acknowledged'] == 1)
							$aReturn[$name]['counts'][$state]['ack']++;
						elseif($service['in_downtime'] == 1)
							$aReturn[$name]['counts'][$state]['downtime']++;
						else
							$aReturn[$name]['counts'][$state]['normal']++;
					}
				}
			}
		} else {
			throw new BackendException('Unhandled filter in backend (getServicegroupStateCounts): '.json_encode($aReturn));
		}
		return $aReturn;
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
