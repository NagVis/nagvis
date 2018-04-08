<?php

/*****************************************************************************
 *
 * GlobalBackendPDO.php - backend class for handling object and state
 *                           information stored in a relational database
 *                           (only used through the 'pgsql' and 'ndomy' classes)
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

class GlobalBackendPDO implements GlobalBackendInterface {
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
            'default' => '',
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
     */
    public function __construct($backendId) {
        $this->backendId = $backendId;

        $this->hostCache = Array();
        $this->serviceCache = Array();
        $this->hostAckCache = Array();

        $this->dbName = cfg('backend_'.$backendId, 'dbname');
        $this->dbUser = cfg('backend_'.$backendId, 'dbuser');
        $this->dbPass = cfg('backend_'.$backendId, 'dbpass');
        $this->dbHost = cfg('backend_'.$backendId, 'dbhost');
        $this->dbPort = cfg('backend_'.$backendId, 'dbport');
        $this->dbPrefix = cfg('backend_'.$backendId, 'dbprefix');
        $this->dbInstanceName = cfg('backend_'.$backendId, 'dbinstancename');

        $this->DB = new CorePDOHandler();
        if($this->DB->open($this->driverName(), array('dbhost' => $this->dbHost, 'dbport' => $this->dbPort, 'dbname' => $this->dbName), $this->dbUser, $this->dbPass) &&
           $this->checkTablesExists()) {
            // Set the instanceId
            $this->dbInstanceId = $this->getInstanceId();
            $this->re_op = $this->DB->getRegularExpressionOperator();
            $this->re_op_neg = $this->DB->getNegatedRegularExpressionOperator();

            // Do some checks to make sure that Nagios is running and the Data at the DB is ok
            $QUERYHANDLE = $this->DB->query('SELECT is_currently_running, UNIX_TIMESTAMP(status_update_time) AS status_update_time FROM '.$this->dbPrefix.'programstatus WHERE instance_id=:instance', array('instance' => $this->dbInstanceId));
            $nagiosstate = $QUERYHANDLE->fetch();

            // Check that Nagios reports itself as running
            if ($nagiosstate['is_currently_running'] != 1) {
                throw new BackendConnectionProblem(l('nagiosNotRunning', Array('BACKENDID' =>$this->backendId)));
            }

            // Be suspicious and check that the data at the db is not older that "maxTimeWithoutUpdate" too
            if($_SERVER['REQUEST_TIME'] - $nagiosstate['status_update_time'] > cfg('backend_'.$backendId, 'maxtimewithoutupdate')) {
                throw new BackendConnectionProblem(l('nagiosDataNotUpToDate', Array('BACKENDID' => $this->backendId, 'TIMEWITHOUTUPDATE' => cfg('backend_'.$backendId, 'maxtimewithoutupdate'))));
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
     */
    private function checkTablesExists() {
        if(!$this->DB->tableExist($this->dbPrefix.'programstatus')) {
            throw new BackendConnectionProblem(l('noTablesExists', Array('BACKENDID' => $this->backendId, 'PREFIX' => $this->dbPrefix)));
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
     */
    private function getInstanceId() {
        $intInstanceId = NULL;

        $QUERYHANDLE = $this->DB->query('SELECT instance_id FROM '.$this->dbPrefix.'instances WHERE instance_name=:instance', array('instance' => $this->dbInstanceName));

        $ret = $QUERYHANDLE->fetch();
        if($ret === false) {
            // ERROR: Instance name not valid
            throw new BackendConnectionProblem(l('backendInstanceNameNotValid', Array('BACKENDID' => $this->backendId, 'NAME' => $this->dbInstanceName)));
        } elseif($QUERYHANDLE->fetch()) {
            // ERROR: Given Instance name is not unique
            throw new BackendConnectionProblem(l('backendInstanceNameNotUniq', Array('BACKENDID' => $this->backendId, 'NAME' => $this->dbInstanceName)));
        } else {
            $intInstanceId = intval($ret['instance_id']);
        }

        return $intInstanceId;
    }

    /**
     * PUBLIC Method getValidConfig
     *
     * Returns the valid config for this backend
     *
     * @return	Array
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
     */
    public function getObjects($type,$name1Pattern='',$name2Pattern='') {
        $ret = Array();

        $max = 1;
        switch($type) {
            case 'host':
                $objectType = 1;
            break;
            case 'service':
                $objectType = 2;

                $max = 2;
            break;
            case 'hostgroup':
                $objectType = 3;
            break;
            case 'servicegroup':
                $objectType = 4;
            break;
            default:
                return Array();
            break;
        }

        $filter = '';
        $values = array('objectType' => $objectType, 'instance' => $this->dbInstanceId);
        if( $name1Pattern != '' ) {
            $filter = 'name1=:name1 AND ';
            $values['name1'] = $name1Pattern;
            if( $max == 2 && $name2Pattern != '' ) {
                $filter .= 'name2=:name2 AND ';
                $values['name2'] = $name2Pattern;
            }
        }

	/* All objects must have the is_active=1 flag enabled. */
	$QUERYHANDLE = $this->DB->query('SELECT name1,name2 FROM '.$this->dbPrefix.'objects
            WHERE objecttype_id=:objectType AND '.$filter.' is_active=1 AND instance_id=:instance ORDER BY name1',
            $values);
        while($data = $QUERYHANDLE->fetch()) {
            $ret[] = Array('name1' => $data['name1'],'name2' => $data['name2']);
        }

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
     */
    private function checkForIsActiveObjects() {
        if($this->DB->query('SELECT object_id FROM '.$this->dbPrefix.'objects WHERE is_active=1')->fetch()) {
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
     */
    private function checkConfigTypeObjects() {
        if($this->DB->query('SELECT host_id FROM '.$this->dbPrefix.'hosts WHERE config_type=1 AND instance_id=:instance LIMIT 1', array('instance' => $this->dbInstanceId))->fetch()) {
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
     */
    private function parseFilter($objects, $filters, $table, $childTable, $isMemberQuery = false,
                                           $isCountQuery = false, $isHostQuery = true) {
        $aFilters = array();
        $values = array();
        $idx = 1;
        foreach($objects AS $OBJS) {
            $objFilters = array();
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

                        $oid = "o$idx";
                        $idx++;
                        $objFilters[] = ' '.$table.'.'.$filter['key']." ".$filter['op']." :$oid ";
                        $values[$oid] = $val;
                    break;
                    default:
                        throw new BackendConnectionProblem('Invalid filter key ('.$filter['key'].')');
                    break;
                }
            }

            // Are there child exclude filters defined for this object?\
            // The objTupe is the type of the objects to query the data for
            if($isMemberQuery && $OBJS[0]->hasExcludeFilters($isCountQuery)) {
                $filter = $OBJS[0]->getExcludeFilter($isCountQuery);
                $objType = $OBJS[0]->getType();
                $query = null;
                $val = null;

                if($objType == 'host') {
                    $parts = explode('~~', $filter);
                    if(!isset($parts[1])) {
                        $query = " ".$childTable.".name2 ".$this->re_op_neg;
                        $val = $filter;
                    }

                } elseif($objType == 'hostgroup' && $isHostQuery) {
                    $parts = explode('~~', $filter);
                    if(!isset($parts[1])) {
                        $query = " ".$childTable.".name1 ".$this->re_op_neg;
                        $val = $parts[0];
                    }

                } elseif(($objType == 'hostgroup' && !$isHostQuery) || $objType == 'servicegroup') {
                    $parts = explode('~~', $filter);
                    if(isset($parts[1])) {
                        $objFilters[] = " NOT (".$childTable.".name1 ".$this->re_op." :o$idx "
                                       ." AND ".$childTable.".name2 ".$this->re_op." :o".($idx + 1).")";
                        $values["o$idx"] = $parts[0];
                        $values["o".($idx + 1)] = $parts[1];
                        $idx += 2;
                    } else {
                        $query = " ".$childTable.".name1 ".$this->re_op_neg;
                        $val = $parts[0];
                    }
                }

                if(isset($query)) {
                    $oid = "o$idx";
                    $idx++;
                    $objFilters[] = "$query :$oid";
                    $values[$oid] = $val;
                }
            }

            $aFilters[] = implode(' AND ', $objFilters);
        }

        return array('query' => implode(' OR ', $aFilters), 'params' => $values);
    }


    /**
     * PRIVATE Method getHostAckByHostname
     *
     * Returns if a host state has been acknowledged. The method doesn't check
     * if the host is in OK/DOWN, it only checks the has_been_acknowledged flag.
     *
     * @param	string $hostName
     * @return	bool $ack
     */
    private function getHostAckByHostname($hostName) {
        $return = 0;

        // Read from cache or fetch from NDO
        if(isset($this->hostAckCache[$hostName])) {
            $return = $this->hostAckCache[$hostName];
        } else {
            $QUERYHANDLE = $this->DB->query('SELECT problem_has_been_acknowledged
            FROM '.$this->dbPrefix.'objects AS o,'.$this->dbPrefix.'hoststatus AS h
            WHERE (o.objecttype_id=1 AND o.name1 = :hostName AND o.instance_id=:instance) AND h.host_object_id=o.object_id AND (o.is_active=1)
	    LIMIT 1
            ', array('hostName' => $hostName, 'instance' => $this->dbInstanceId));

            $data = $QUERYHANDLE->fetch();

            // It's unnessecary to check if the value is 0, everything not equal to 1 is FALSE
            if(isset($data['problem_has_been_acknowledged']) && $data['problem_has_been_acknowledged'] == '1') {
                $return = 1;
            } else {
                $return = 0;
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
     */
    public function getHostState($objects, $options, $filters, $isMemberQuery = false) {
        $arrReturn = Array();

        $filter = $this->parseFilter($objects, $filters, 'o', 'o', $isMemberQuery, false, HOST_QUERY);
        $QUERYHANDLE = $this->DB->query('SELECT
            o.object_id, alias, display_name, address, o.name1,
            has_been_checked,
            last_hard_state,
            UNIX_TIMESTAMP(last_hard_state_change) AS last_hard_state_change,
            UNIX_TIMESTAMP(last_state_change) AS last_state_change,
            current_state,
            output, perfdata,
            h.notes,
            problem_has_been_acknowledged,
            UNIX_TIMESTAMP(last_check) AS last_check, UNIX_TIMESTAMP(next_check) AS next_check,
            hs.state_type, hs.current_check_attempt, hs.max_check_attempts, hs.check_command,
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
            (o.objecttype_id=1 AND ('.$filter['query'].')
            AND o.instance_id=:instance)
            AND (h.config_type=:configType AND h.instance_id=:instance AND h.host_object_id=o.object_id)
            AND (o.is_active=1)
	    ', array_merge(
            $filter['params'],
            array('instance' => $this->dbInstanceId, 'configType' => $this->objConfigType)));

        while($data = $QUERYHANDLE->fetch()) {

            // If there is a downtime for this object, save the data
            $in_downtime = 0;
            $dt_details = array(null, null, null, null);
            if($this->DB->is_nonnull_int($data['downtime_start'])) {
                $in_downtime = 1;
                $dt_details = array($data['downtime_author'], $data['downtime_data'],
                                    $data['downtime_start'], $data['downtime_end']);
            }

            /**
                * Only recognize hard states. There was a discussion about the implementation
                * This is a new handling of only_hard_states. For more details, see:
                * http://www.nagios-portal.de/wbb/index.php?page=Thread&threadID=8524
                *
                * Thanks to Andurin and fredy82
                */
            if($options & 1)
                if(!$this->DB->eq_int($data['state_type'], 0))
                    $data['current_state'] = $data['current_state'];
                else
                    $data['current_state'] = $data['last_hard_state'];

            $acknowledged = 0;

            if($this->DB->null_or_eq_int($data['has_been_checked'], 0) || !$this->DB->is_nonnull_int($data['current_state'])) {
                $state = UNCHECKED;
                $output = l('hostIsPending', Array('HOST' => $data['name1']));
            } elseif($this->DB->eq_int($data['current_state'], 0)) {
                // Host is UP
                $state = UP;
                $output = $data['output'];
            } else {
                // Host is DOWN/UNREACHABLE/UNKNOWN

                $acknowledged = intval($data['problem_has_been_acknowledged']);

                // Store state and output in array
                switch(intval($data['current_state'])) {
                    case 1:
                        $state = DOWN;
                        $output = $data['output'];
                    break;
                    case 2:
                        $state = UNREACHABLE;
                        $output = $data['output'];
                    break;
                    case 3:
                        $state = UNKNOWN;
                        $output = $data['output'];
                    break;
                    default:
                        $state = UNKNOWN;
                        $output = 'GlobalBackendndomy::getHostState: Undefined state!';
                    break;
                }
            }

            $arrReturn[$data['name1']] = array(
                $state,
                $output,
                $acknowledged,
                $in_downtime,
                0, // staleness
                $data['state_type'],
                $data['current_check_attempt'],
                $data['max_check_attempts'],
                $data['last_check'],
                $data['next_check'],
                $data['last_hard_state_change'],
                $data['last_state_change'],
                $data['perfdata'],
                $data['display_name'],
                $data['alias'],
                $data['address'],
                $data['notes'],
                $data['check_command'],
                null, // custom_vars
                $dt_details[0], // downtime author
                $dt_details[1], // downtime comment
                $dt_details[2], // downtime start
                $dt_details[3], // downtime end
            );
        }

        return $arrReturn;
    }

    /**
     * PUBLIC getServiceState()
     *
     * Returns the state and additional information of the requested service
     */
    public function getServiceState($objects, $options, $filters, $isMemberQuery = false) {
        $arrReturn = Array();

        $filter = $this->parseFilter($objects, $filters, 'o', 'o', $isMemberQuery, false, !HOST_QUERY);
        $QUERYHANDLE = $this->DB->query('SELECT
            o.object_id, o.name1, o.name2,
            s.display_name,
            s.notes,
            h.address,
            ss.has_been_checked, ss.last_hard_state, ss.current_state,
            UNIX_TIMESTAMP(ss.last_hard_state_change) AS last_hard_state_change,
            UNIX_TIMESTAMP(ss.last_state_change) AS last_state_change,
            ss.output, ss.perfdata, ss.problem_has_been_acknowledged,
            UNIX_TIMESTAMP(ss.last_check) AS last_check, UNIX_TIMESTAMP(ss.next_check) AS next_check,
            ss.state_type, ss.current_check_attempt, ss.max_check_attempts, ss.check_command,
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
                (o.objecttype_id=2 AND o.is_active=1 AND ('.$filter['query'].'))
                AND (s.config_type=:configType AND s.instance_id=:instance AND s.service_object_id=o.object_id)
                AND (h.config_type=:configType AND h.instance_id=:instance AND h.host_object_id=s.host_object_id)
                ', array_merge(
            array('configType' => $this->objConfigType, 'instance' => $this->dbInstanceId),
            $filter['params']));

        while($data = $QUERYHANDLE->fetch()) {
            $arrTmpReturn = Array();

            if(isset($objects[$data['name1'].'~~'.$data['name2']])) {
                $specific = true;
                $key = $data['name1'].'~~'.$data['name2'];
            } else {
                $specific = false;
                $key = $data['name1'];
            }

            // If there is a downtime for this object, save the data
            $in_downtime = 0;
            $dt_details = array(null, null, null, null);
            if($this->DB->is_nonnull_int($data['downtime_start'])) {
                $in_downtime = 1;
                $dt_details = array($data['downtime_author'], $data['downtime_data'],
                                    $data['downtime_start'], $data['downtime_end']);
            }

            /**
                * Only recognize hard states. There was a discussion about the implementation
                * This is a new handling of only_hard_states. For more details, see:
                * http://www.nagios-portal.de/wbb/index.php?page=Thread&threadID=8524
                *
                * Thanks to Andurin and fredy82
                */
            if($options & 1)
                if(!$this->DB->eq_int($data['state_type'], 0))
                    $data['current_state'] = $data['current_state'];
                else
                    $data['current_state'] = $data['last_hard_state'];

            $acknowledged = 0;
            if($this->DB->null_or_eq_int($data['has_been_checked'], 0) || !$this->DB->is_nonnull_int($data['current_state'])) {
                $state = PENDING;
                $output = l('serviceNotChecked', Array('SERVICE' => $data['name2']));
            } elseif($this->DB->eq_int($data['current_state'], 0)) {
                // Host is UP
                $state = OK;
                $output = $data['output'];
            } else {
                // Host is DOWN/UNREACHABLE/UNKNOWN

                /**
                    * If state is not OK (=> WARN, CRIT, UNKNOWN) and service is not
                    * acknowledged => check for acknowledged host
                    */
                if(!$this->DB->eq_int($data['problem_has_been_acknowledged'], 1)) {
                    $acknowledged = $this->getHostAckByHostname($data['name1']);
                } else {
                    $acknowledged = intval($data['problem_has_been_acknowledged']);
                }

                // Store state and output in array
                switch(intval($data['current_state'])) {
                    case 1:
                        $state = WARNING;
                        $output = $data['output'];
                    break;
                    case 2:
                        $state = CRITICAL;
                        $output = $data['output'];
                    break;
                    case 3:
                        $state = UNKNOWN;
                        $output = $data['output'];
                    break;
                    default:
                        $state = UNKNOWN;
                        $output = 'GlobalBackendndomy::getServiceState: Undefined state!';
                    break;
                }
            }

            $svc = array(
                $state,
                $output,
                $acknowledged,
                $in_downtime,
                0, // staleness
                $data['state_type'],
                $data['current_check_attempt'],
                $data['max_check_attempts'],
                $data['last_check'],
                $data['next_check'],
                $data['last_hard_state_change'],
                $data['last_state_change'],
                $data['perfdata'],
                $data['display_name'],
                $data['display_name'],
                $data['address'],
                $data['notes'],
                $data['check_command'],
                null, //custom_vars
                $dt_details[0], // dt author
                $dt_details[1], // dt data
                $dt_details[2], // dt start
                $dt_details[3], // dt end
                $data['name2']
            );

            if($specific) {
                $arrReturn[$key] = $svc;
            } else {
                if(!isset($arrReturn[$key]))
                    $arrReturn[$key] = Array();

                $arrReturn[$key][] = $svc;
            }
        }

        return $arrReturn;
    }

    /**
    * PUBLIC getHostNamesProblematic()
    *
    * Queries PostgreSQL for hosts with problems
    * A problem is given when the host state != UP
    * or a service != OK
    *
    * @return Array of hostnames
    */

    public function getHostNamesProblematic() {
        $arrReturn = Array();

	$QUERYHANDLE = $this->DB->query('
	    select o.name1 as host_name
	    from '.$this->dbPrefix.'hoststatus as hs
	    LEFT JOIN '.$this->dbPrefix.'objects as o ON hs.host_object_id=o.object_id
	    WHERE o.is_active = 1
	    AND hs.current_state > 0
	    AND hs.config_type=:configType
	    AND o.instance_id=:instance
	    UNION
	    SELECT o.name1 AS host_name
	    FROM '.$this->dbPrefix.'servicestatus as ss
	    LEFT JOIN '.$this->dbPrefix.'objects as o ON ss.service_object_id=o.object_id
	    LEFT JOIN '.$this->dbPrefix.'services as s ON ss.service_object_id=s.service_object_id
	    WHERE s.config_type=:configType
	    AND ss.current_state > 0
	    AND o.is_active=1
	    AND o.instance_id=:instance',
        array('instance' => $this->dbInstanceId, 'configType' => $this->objConfigType)
	   );
        while($data = $QUERYHANDLE->fetch()) {
            $arrReturn[] = $data['name1'];
        }

        return $arrReturn;
    }

    /**
     * PUBLIC getHostMemberCounts()
     *
     * @param   Array     List of objects to query
     * @param   Array     List of filters to apply
     * @return  Array     List of states and counts
     */
    public function getHostMemberCounts($objects, $options, $filters) {
        if($options & 1)
            $stateAttr = 'CASE WHEN ss.state_type = 0 THEN ss.last_hard_state ELSE ss.current_state END';
        else
            $stateAttr = 'ss.current_state';

        $filter = $this->parseFilter($objects, $filters, 'o', 'o', MEMBER_QUERY, COUNT_QUERY, !HOST_QUERY);
        $QUERYHANDLE = $this->DB->query('SELECT
            o.name1, h.alias,
            SUM(CASE WHEN ss.has_been_checked=0 THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN ('.$stateAttr.'=0 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND hs.scheduled_downtime_depth=0) THEN 1 ELSE 0 END) AS ok,
            SUM(CASE WHEN ('.$stateAttr.'=0 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR hs.scheduled_downtime_depth!=0)) THEN 1 ELSE 0 END) AS ok_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND hs.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0 AND hs.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS warning,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR hs.scheduled_downtime_depth!=0)) THEN 1 ELSE 0 END) AS warning_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND ss.has_been_checked!=0 AND (ss.problem_has_been_acknowledged=1 OR hs.problem_has_been_acknowledged=1)) THEN 1 ELSE 0 END) AS warning_ack,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND hs.scheduled_downtime_depth=0) AND ss.problem_has_been_acknowledged=0 AND hs.problem_has_been_acknowledged=0 THEN 1 ELSE 0 END) AS critical,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR hs.scheduled_downtime_depth!=0)) THEN 1 ELSE 0 END) AS critical_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND ss.has_been_checked!=0 AND (ss.problem_has_been_acknowledged=1 OR hs.problem_has_been_acknowledged=1)) THEN 1 ELSE 0 END) AS critical_ack,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND hs.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0 AND hs.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS unknown,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND ss.has_been_checked!=0 AND (ss.scheduled_downtime_depth!=0 OR hs.scheduled_downtime_depth!=0)) THEN 1 ELSE 0 END) AS unknown_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND ss.has_been_checked!=0 AND (ss.problem_has_been_acknowledged=1 OR hs.problem_has_been_acknowledged=1)) THEN 1 ELSE 0 END) AS unknown_ack
            FROM
                '.$this->dbPrefix.'hoststatus AS hs,
                '.$this->dbPrefix.'services AS s,
                '.$this->dbPrefix.'hosts AS h,
                '.$this->dbPrefix.'objects AS o
            LEFT JOIN
                '.$this->dbPrefix.'servicestatus AS ss
                ON ss.service_object_id=o.object_id
            WHERE
                (o.objecttype_id=2 AND o.is_active=1 AND ('.$filter['query'].'))
                AND (s.config_type=:configType AND s.instance_id=:instance AND s.service_object_id=o.object_id)
                AND (h.config_type=:configType AND h.instance_id=:instance AND h.host_object_id=s.host_object_id)
                AND (hs.host_object_id=h.host_object_id)
                GROUP BY h.host_object_id, h.alias, o.name1',
                array_merge(
                    array('configType' => $this->objConfigType, 'instance' => $this->dbInstanceId),
                    $filter['params']));

        $arrReturn = Array();
        while($data = $QUERYHANDLE->fetch()) {
            $arrReturn[$data['name1']] = Array(
                //'details' => Array('alias' => $data['alias']),
                'counts' => Array(
                    PENDING => Array(
                        'normal' => intval($data['pending']),
                    ),
                    OK => Array(
                        'normal'   => intval($data['ok']),
                        'stale'    => 0,
                        'downtime' => intval($data['ok_downtime']),
                    ),
                    WARNING => Array(
                        'normal'   => intval($data['warning']),
                        'stale'    => 0,
                        'ack'      => intval($data['warning_ack']),
                        'downtime' => intval($data['warning_downtime']),
                    ),
                    CRITICAL => Array(
                        'normal'   => intval($data['critical']),
                        'stale'    => 0,
                        'ack'      => intval($data['critical_ack']),
                        'downtime' => intval($data['critical_downtime']),
                    ),
                    UNKNOWN => Array(
                        'normal'   => intval($data['unknown']),
                        'stale'    => 0,
                        'ack'      => intval($data['unknown_ack']),
                        'downtime' => intval($data['unknown_downtime']),
                    ),
                )
            );
        }

        return $arrReturn;
    }

    public function getHostgroupStateCounts($objects, $options, $filters) {
        if($options & 1)
            $stateAttr = 'CASE WHEN (hs.state_type = 0) THEN hs.last_hard_state ELSE hs.current_state END';
        else
            $stateAttr = 'hs.current_state';

        $filter = $this->parseFilter($objects, $filters, 'o', 'o2', MEMBER_QUERY, COUNT_QUERY, HOST_QUERY);
        $QUERYHANDLE = $this->DB->query('SELECT
            o.name1, hg.alias,
            SUM(CASE WHEN hs.has_been_checked=0 THEN 1 ELSE 0 END) AS unchecked,
            SUM(CASE WHEN ('.$stateAttr.'=0 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth=0) THEN 1 ELSE 0 END) AS up,
            SUM(CASE WHEN ('.$stateAttr.'=0 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS up_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth=0 AND hs.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS down,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS down_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND hs.has_been_checked!=0 AND hs.problem_has_been_acknowledged=1) THEN 1 ELSE 0 END) AS down_ack,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth=0 AND hs.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS unreachable,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS unreachable_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND hs.has_been_checked!=0 AND hs.problem_has_been_acknowledged=1) THEN 1 ELSE 0 END) AS unreachable_ack,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth=0 AND hs.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS unknown,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND hs.has_been_checked!=0 AND hs.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS unknown_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND hs.has_been_checked!=0 AND hs.problem_has_been_acknowledged=1) THEN 1 ELSE 0 END) AS unknown_ack
            FROM
                '.$this->dbPrefix.'objects AS o,
                '.$this->dbPrefix.'hostgroups AS hg,
                '.$this->dbPrefix.'hostgroup_members AS hgm,
                '.$this->dbPrefix.'objects AS o2
            LEFT JOIN
         '.$this->dbPrefix.'hoststatus AS hs
            ON hs.host_object_id=o2.object_id
            WHERE
                (o.objecttype_id=3 AND ('.$filter['query'].')
                 AND o.instance_id=:instance)
                AND (hg.config_type=:configType AND hg.instance_id=:instance AND hg.hostgroup_object_id=o.object_id)
                AND hgm.hostgroup_id=hg.hostgroup_id
                AND (o2.objecttype_id=1 AND o2.object_id=hgm.host_object_id)
                AND (o.is_active=1)
                AND (o2.is_active=1)
            GROUP BY o.object_id, hg.alias', array_merge(
            array('instance' => $this->dbInstanceId, 'configType' => $this->objConfigType),
            $filter['params']));

        $arrReturn = Array();
        while($data = $QUERYHANDLE->fetch()) {
            $arrReturn[$data['name1']] = Array(
                'details' => Array(ALIAS => $data['alias']),
                'counts' => Array(
                    UNCHECKED => Array(
                        'normal'    => intval($data['unchecked']),
                    ),
                    UP => Array(
                        'normal'    => intval($data['up']),
                        'stale'    => 0,
                        'downtime'  => intval($data['up_downtime']),
                    ),
                    DOWN => Array(
                        'normal'    => intval($data['down']),
                        'stale'    => 0,
                        'ack'       => intval($data['down_ack']),
                        'downtime'  => intval($data['down_downtime']),
                    ),
                    UNREACHABLE => Array(
                        'normal'    => intval($data['unreachable']),
                        'stale'    => 0,
                        'ack'       => intval($data['unreachable_ack']),
                        'downtime'  => intval($data['unreachable_downtime']),
                    ),
                ),
            );
        }

        if($options & 1)
            $stateAttr = 'CASE WHEN (ss.state_type = 0) THEN ss.last_hard_state ELSE ss.current_state END';
        else
            $stateAttr = 'ss.current_state';

        // If recognize_services are disabled don't fetch service information
        if($options & 2)
            return $arrReturn;

        // FIXME: Does not handle host downtimes/acks
        $filter = $this->parseFilter($objects, $filters, 'o', 'o2', MEMBER_QUERY, COUNT_QUERY, !HOST_QUERY);
        $QUERYHANDLE = $this->DB->query('SELECT
            o.name1,
            SUM(CASE WHEN ss.has_been_checked=0 THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN ('.$stateAttr.'=0 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0) THEN 1 ELSE 0 END) AS ok,
            SUM(CASE WHEN ('.$stateAttr.'=0 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS ok_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS warning,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS warning_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.problem_has_been_acknowledged=1) THEN 1 ELSE 0 END) AS warning_ack,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS critical,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS critical_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.problem_has_been_acknowledged=1) THEN 1 ELSE 0 END) AS critical_ack,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS unknown,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS unknown_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.problem_has_been_acknowledged=1) THEN 1 ELSE 0 END) AS unknown_ack
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
                (o.objecttype_id=3 AND ('.$filter['query'].')
                 AND o.instance_id=:instance)
                AND (hg.config_type=:configType AND hg.instance_id=:instance AND hg.hostgroup_object_id=o.object_id)
                AND hgm.hostgroup_id=hg.hostgroup_id
                AND (s.config_type=:configType AND s.instance_id=:instance AND s.host_object_id=hgm.host_object_id)
                AND (o2.objecttype_id=2 AND s.service_object_id=o2.object_id)
                AND (o.is_active=1)
                AND (o2.is_active=1)
            GROUP BY o.object_id', array_merge(
            array('instance' => $this->dbInstanceId, 'configType' => $this->objConfigType),
            $filter['params']));

        while($data = $QUERYHANDLE->fetch()) {
            $arrReturn[$data['name1']]['counts'][PENDING]['normal']    = intval($data['pending']);
            $arrReturn[$data['name1']]['counts'][OK]['normal']         = intval($data['ok']);
            $arrReturn[$data['name1']]['counts'][OK]['stale']          = 0;
            $arrReturn[$data['name1']]['counts'][OK]['downtime']       = intval($data['ok_downtime']);
            $arrReturn[$data['name1']]['counts'][WARNING]['normal']    = intval($data['warning']);
            $arrReturn[$data['name1']]['counts'][WARNING]['stale']     = 0;
            $arrReturn[$data['name1']]['counts'][WARNING]['ack']       = intval($data['warning_ack']);
            $arrReturn[$data['name1']]['counts'][WARNING]['downtime']  = intval($data['warning_downtime']);
            $arrReturn[$data['name1']]['counts'][CRITICAL]['normal']   = intval($data['critical']);
            $arrReturn[$data['name1']]['counts'][CRITICAL]['stale']    = 0;
            $arrReturn[$data['name1']]['counts'][CRITICAL]['ack']      = intval($data['critical_ack']);
            $arrReturn[$data['name1']]['counts'][CRITICAL]['downtime'] = intval($data['critical_downtime']);
            $arrReturn[$data['name1']]['counts'][UNKNOWN]['normal']    = intval($data['unknown']);
            $arrReturn[$data['name1']]['counts'][UNKNOWN]['stale']     = 0;
            $arrReturn[$data['name1']]['counts'][UNKNOWN]['ack']       = intval($data['unknown_ack']);
            $arrReturn[$data['name1']]['counts'][UNKNOWN]['downtime']  = intval($data['unknown_downtime']);
        }

        return $arrReturn;
    }

    public function getServicegroupStateCounts($objects, $options, $filters) {
        if($options & 1)
            $stateAttr = 'CASE WHEN (ss.state_type = 0) THEN ss.last_hard_state ELSE ss.current_state END';
        else
            $stateAttr = 'ss.current_state';

        // FIXME: Recognize host ack/downtime
        $filter = $this->parseFilter($objects, $filters, 'o', 'o2', MEMBER_QUERY, COUNT_QUERY, !HOST_QUERY);
        $QUERYHANDLE = $this->DB->query('SELECT
            o.name1, sg.alias,
            SUM(CASE WHEN ss.has_been_checked=0 THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN ('.$stateAttr.'=0 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.scheduled_downtime_depth=0) THEN 1 ELSE 0 END) AS ok,
            SUM(CASE WHEN ('.$stateAttr.'=0 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS ok_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS warning,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS warning_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=1 AND ss.has_been_checked!=0 AND ss.problem_has_been_acknowledged=1) THEN 1 ELSE 0 END) AS warning_ack,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS critical,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS critical_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=2 AND ss.has_been_checked!=0 AND ss.problem_has_been_acknowledged=1) THEN 1 ELSE 0 END) AS critical_ack,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth=0 AND ss.problem_has_been_acknowledged=0) THEN 1 ELSE 0 END) AS unknown,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.scheduled_downtime_depth!=0) THEN 1 ELSE 0 END) AS unknown_downtime,
            SUM(CASE WHEN ('.$stateAttr.'=3 AND ss.has_been_checked!=0 AND ss.problem_has_been_acknowledged=1) THEN 1 ELSE 0 END) AS unknown_ack
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
                (o.objecttype_id=4 AND ('.$filter['query'].')
                 AND o.instance_id=:instance)
                AND (sg.config_type=:configType AND sg.instance_id=:instance AND sg.servicegroup_object_id=o.object_id)
                AND sgm.servicegroup_id=sg.servicegroup_id
                AND (s.config_type=:configType AND s.instance_id=:instance AND s.service_object_id=sgm.service_object_id)
                AND (o2.objecttype_id=2 AND s.service_object_id=o2.object_id)
                AND (o.is_active=1)
                AND (o2.is_active=1)
            GROUP BY o.object_id, sg.alias', array_merge(
            array('instance' => $this->dbInstanceId, 'configType' => $this->objConfigType),
            $filter['params']));

        $arrReturn = Array();
        while($data = $QUERYHANDLE->fetch()) {
            $arrReturn[$data['name1']] = Array(
                'details' => Array(ALIAS => $data['alias']),
                'counts' => Array(
                    PENDING => Array(
                        'normal'   => intval($data['pending']),
                    ),
                    OK => Array(
                        'normal'   => intval($data['ok']),
                        'stale'    => 0,
                        'downtime' => intval($data['ok_downtime']),
                    ),
                    WARNING => Array(
                        'normal'   => intval($data['warning']),
                        'stale'    => 0,
                        'ack'      => intval($data['warning_ack']),
                        'downtime' => intval($data['warning_downtime']),
                    ),
                    CRITICAL => Array(
                        'normal'   => intval($data['critical']),
                        'stale'    => 0,
                        'ack'      => intval($data['critical_ack']),
                        'downtime' => intval($data['critical_downtime']),
                    ),
                    UNKNOWN => Array(
                        'normal'   => intval($data['unknown']),
                        'stale'    => 0,
                        'ack'      => intval($data['unknown_ack']),
                        'downtime' => intval($data['unknown_downtime']),
                    ),
                ),
            );
        }

        return $arrReturn;
    }

    /**
     * PUBLIC Method getHostNamesWithNoParent
     *
     * Gets all hosts with no parent host. This method is needed by the automap
     * to get the root host.
     */
    public function getHostNamesWithNoParent() {
        $arrReturn = Array();

        $QUERYHANDLE = $this->DB->query('SELECT o1.name1
        FROM
        '.$this->dbPrefix.'objects AS o1,
        '.$this->dbPrefix.'hosts AS h1
        LEFT OUTER JOIN '.$this->dbPrefix.'host_parenthosts AS ph1 ON h1.host_id=ph1.host_id
        WHERE o1.objecttype_id=1
        AND (h1.config_type=:configType AND h1.instance_id=:instance AND h1.host_object_id=o1.object_id)
        AND ph1.parent_host_object_id IS null
        AND (o1.is_active=1)
        ', array('configType' => $this->objConfigType, 'instance' => $this->dbInstanceId));

        while($data = $QUERYHANDLE->fetch()) {
            $arrReturn[] = $data['name1'];
        }

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
     */
    public function getDirectParentNamesByHostName($hostName) {
        $aParentNames = Array();

        $QUERYHANDLE = $this->DB->query('SELECT o2.name1
        FROM
        '.$this->dbPrefix.'objects AS o1,
        '.$this->dbPrefix.'hosts AS h1,
        '.$this->dbPrefix.'host_parenthosts AS ph1,
        '.$this->dbPrefix.'objects AS o2
        WHERE o1.objecttype_id=1 AND o1.name1=\''.$hostName.'\'
        AND (h1.config_type=:configType AND h1.instance_id=:instance AND h1.host_object_id=o1.object_id)
        AND h1.host_id=ph1.host_id
        AND o2.objecttype_id=1 AND o2.object_id=ph1.parent_host_object_id
        AND (o1.is_active=1)
        AND (o2.is_active=1)
        ', array('configType' => $this->objConfigType, 'instance' => $this->dbInstanceId));
        while($data = $QUERYHANDLE->fetch()) {
            $aParentNames[] = $data['name1'];
        }

        return $aParentNames;
    }

    /**
     * PUBLIC Method getDirectChildNamesByHostName
     *
     * Gets the names of all child hosts
     *
     * @param		String		Name of host to get the children of
     * @return	Array			Array with hostnames
     */
    public function getDirectChildNamesByHostName($hostName) {
        $arrChildNames = Array();

        $QUERYHANDLE = $this->DB->query('SELECT o2.name1
        FROM
        '.$this->dbPrefix.'objects AS o1,
        '.$this->dbPrefix.'hosts AS h1,
        '.$this->dbPrefix.'host_parenthosts AS ph1,
        '.$this->dbPrefix.'hosts AS h2,
        '.$this->dbPrefix.'objects AS o2
        WHERE o1.objecttype_id=1 AND o1.name1=:hostName
        AND (h1.config_type=:configType AND h1.instance_id=:instance AND h1.host_object_id=o1.object_id)
        AND o1.object_id=ph1.parent_host_object_id
        AND (h2.config_type=:configType AND h2.instance_id=:instance AND h2.host_id=ph1.host_id)
        AND o2.objecttype_id=1 AND h2.host_object_id=o2.object_id
        AND (o1.is_active=1)
        AND (o2.is_active=1)
        ', array('configType' => $this->objConfigType, 'instance' => $this->dbInstanceId, 'hostName' => $hostName));
        while($data = $QUERYHANDLE->fetch()) {
            $arrChildNames[] = $data['name1'];
        }

        return $arrChildNames;
    }

    /**
     * PUBLIC Method getHostsByHostgroupName
     *
     * Gets all hosts of a hostgroup
     *
     * @param		String		Name of hostgroup to get the hosts of
     * @return	Array			Array with hostnames
     */
    public function getHostsByHostgroupName($hostgroupName) {
        $arrReturn = Array();

        $QUERYHANDLE = $this->DB->query('SELECT
                o2.name1
            FROM
                '.$this->dbPrefix.'objects AS o,
                '.$this->dbPrefix.'hostgroups AS hg,
                '.$this->dbPrefix.'hostgroup_members AS hgm,
                '.$this->dbPrefix.'objects AS o2
            WHERE
                (o.objecttype_id=3 AND o.name1 = :hostgroupName AND o.instance_id=:instance)
                AND (hg.config_type=:configType AND hg.instance_id=:instance AND hg.hostgroup_object_id=o.object_id)
                AND hgm.hostgroup_id=hg.hostgroup_id
                AND (o2.objecttype_id=1 AND o2.object_id=hgm.host_object_id)
                AND (o.is_active=1)
                AND (o2.is_active=1)
                ', array('configType' => $this->objConfigType, 'instance' => $this->dbInstanceId, 'hostgroupName' => $hostgroupName));

        while($data = $QUERYHANDLE->fetch()) {
            // Assign actual dataset to return array
            $arrReturn[] = $data['name1'];
        }

        return $arrReturn;
    }

    /**
     * PUBLIC Method getHostNamesInHostgroup
     *
     * This is required to bypass the filter_group error in automap if you integrate nagvis with icinga2
     *
     * @param		String		Name of hostgroup to get the hosts of
     * @return	Array			Array with hostnames
     */
    public function getHostNamesInHostgroup($hostgroupName) {
    	return $this->getHostsByHostgroupName($hostgroupName); 
    }


    /**
     * PUBLIC Method getServicesByServicegroupName
     *
     * Gets all services of a servicegroup
     *
     * @param		String		Name of servicegroup to get the services of
     * @return	Array			Array with hostnames and service descriptions
     */
    public function getServicesByServicegroupName($servicegroupName) {
        $arrReturn = Array();

        $QUERYHANDLE = $this->DB->query('SELECT
                o2.name1, o2.name2
            FROM
                '.$this->dbPrefix.'objects AS o,
                '.$this->dbPrefix.'servicegroups AS sg,
                '.$this->dbPrefix.'servicegroup_members AS sgm,
                '.$this->dbPrefix.'objects AS o2
            WHERE
                (o.objecttype_id=4 AND o.name1 = :servicegroupName AND o.instance_id=:instance)
                AND (sg.config_type=:configType AND sg.instance_id=:instance AND sg.servicegroup_object_id=o.object_id)
                AND sgm.servicegroup_id=sg.servicegroup_id
                AND (o2.objecttype_id=2 AND o2.object_id=sgm.service_object_id)
                AND (o.is_active=1)
                AND (o2.is_active=1)
                ', array('configType' => $this->objConfigType, 'instance' => $this->dbInstanceId, 'servicegroupName' => $servicegroupName));

        while($data = $QUERYHANDLE->fetch()) {
            // Assign actual dataset to return array
            $arrReturn[] = Array('host_name' => $data['name1'], 'service_description' => $data['name2']);
        }

        return $arrReturn;
    }

    /**
     * PUBLIC Method getServicegroupInformations
     *
     * Gets information like the alias for a servicegroup
     *
     * @param	String		    Name of servicegroup
     * @return	Array			Array with object information
     */
    public function getServicegroupInformations($servicegroupName) {
        $arrReturn = Array();

        $QUERYHANDLE = $this->DB->query('SELECT
                o.object_id, sg.alias
            FROM
                '.$this->dbPrefix.'objects AS o,
                '.$this->dbPrefix.'servicegroups AS sg
            WHERE
                (o.objecttype_id=4 AND o.name1 = :servicegroupName AND o.instance_id=:instance)
                AND (sg.config_type=:configType AND sg.instance_id=:instance AND sg.servicegroup_object_id=o.object_id)
                AND (o.is_active=1)
                LIMIT 1',
                array('configType' => $this->objConfigType, 'instance' => $this->dbInstanceId, 'servicegroupName' => $servicegroupName));

        $data = $QUERYHANDLE->fetch();

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
     */
    public function getHostgroupInformations($groupName) {
        $arrReturn = Array();

        $QUERYHANDLE = $this->DB->query('SELECT
                o.object_id, g.alias
            FROM
                '.$this->dbPrefix.'objects AS o,
                '.$this->dbPrefix.'hostgroups AS g
            WHERE
                (o.objecttype_id=3 AND o.name1 = :groupName AND o.instance_id=:instance)
                AND (g.config_type=:configType AND g.instance_id=:instance AND g.hostgroup_object_id=o.object_id)
                AND (o.is_active=1)
                LIMIT 1',
                array('configType' => $this->objConfigType, 'instance' => $this->dbInstanceId, 'groupName' => $groupName));

        $data = $QUERYHANDLE->fetch();

        $arrReturn['alias'] = $data['alias'];

        return $arrReturn;
    }

        /**
     * PUBLIC getDirectChildDependenciesNamesByHostName()
     *
     * @param   String   Hostname
     * @return  Array    List of hostnames
     */
    public function getDirectChildDependenciesNamesByHostName($hostName, $min_business_impact=false) {
        return $this->getDirectChildNamesByHostName($hostName);
    }
    
    /*  
     * PUBLIC getDirectParentNamesByHostName()
     *
     * @param   String   Hostname
     * @return  Array    List of hostnames
     */
    public function getDirectParentDependenciesNamesByHostName($hostName, $min_business_impact=false) {
        return $this->getDirectParentNamesByHostName($hostName);
    }

    public function getProgramStart() {
        $QUERYHANDLE = $this->DB->query('SELECT UNIX_TIMESTAMP(program_start_time) AS program_start '
                                        .'FROM '.$this->dbPrefix.'programstatus WHERE instance_id=:instance',
                array('instance' => $this->dbInstanceId));
        $data = $QUERYHANDLE->fetch();
        if($data !== false && $this->DB->is_nonnull_int($data['program_start']))
            return intval($data['program_start']);
        else
            return -1;
    }


}
?>
