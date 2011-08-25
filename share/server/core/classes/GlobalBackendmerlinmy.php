<?php
/*****************************************************************************
 *
 * GlobalBackendmerlinmy.php - backend class for handling object and state
 *                             information stored in the Merlin database.
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
 * @author	Roman Kyrylych <rkyrylych@op5.com>
 */

class GlobalBackendmerlinmy implements GlobalBackendInterface {
    private $CORE;
    private $CONN;
    private $backendId;
    private $dbName;
    private $dbUser;
    private $dbPass;
    private $dbHost;

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
            'default' => 'merlin',
            'match' => MATCH_STRING_NO_SPACE),
        'dbuser' => Array('must' => 1,
            'editable' => 1,
            'default' => 'merlin',
            'match' => MATCH_STRING_NO_SPACE),
        'dbpass' => Array('must' => 0,
            'editable' => 1,
            'default' => 'merlin',
            'match' => MATCH_STRING_EMPTY),
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
     * @author	Lars Michelsen <lars@vertical-visions.de>
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function __construct($CORE, $backendId) {
        $this->CORE = $CORE;

        $this->backendId = $backendId;

        $this->hostCache = Array();
        $this->serviceCache = Array();
        $this->hostAckCache = Array();

        $this->dbName = cfg('backend_'.$backendId, 'dbname');
        $this->dbUser = cfg('backend_'.$backendId, 'dbuser');
        $this->dbPass = cfg('backend_'.$backendId, 'dbpass');
        $this->dbHost = cfg('backend_'.$backendId, 'dbhost');
        $this->dbPort = cfg('backend_'.$backendId, 'dbport');

        $this->checkMysqlSupport();
        $this->connectDB();
        $this->checkTablesExists();
    }

    /**
     * PRIVATE Method checkTablesExists
     *
     * Checks if the needed tables are in the DB
     *
     * @return	Boolean
     * @author	Lars Michelsen <lars@vertical-visions.de>
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    private function checkTablesExists() {
        if(mysql_num_rows($this->mysqlQuery("SHOW TABLES LIKE 'program_status'")) == 0) {
            throw new BackendConnectionProblem(l('noTablesExists', Array('BACKENDID' => $this->backendId, 'PREFIX' => '')));
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
            throw BackendConnectionProblem(l('errorConnectingMySQL', Array('BACKENDID' => $this->backendId,'MYSQLERR' => mysql_error())));
        }

        $returnCode = mysql_select_db($this->dbName, $this->CONN);

        // set the old level of reporting back
        error_reporting($oldLevel);

        if(!$returnCode){
            throw BackendConnectionProblem(l('errorSelectingDb', Array('BACKENDID' => $this->backendId, 'MYSQLERR' => mysql_error($this->CONN))));
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
            throw BackendConnectionProblem(l('mysqlNotSupported', Array('BACKENDID' => $this->backendId)));
        }
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
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getObjects($type,$name1Pattern='',$name2Pattern='') {
        $ret = Array();
        $filter = '';

        if(in_array($type, array('host', 'hostgroup', 'servicegroup'))) {
            if($name1Pattern != '') {
                $filter = " WHERE {$type}_name = '$name1Pattern'";
            }
            $QUERYHANDLE = $this->mysqlQuery("SELECT {$type}_name AS name1, alias AS name2 FROM $type".$filter." ORDER BY {$type}_name");
        } elseif($type == 'service') {
            if($name1Pattern != '' && $name2Pattern != '') {
                $filter = " WHERE host_name = '$name1Pattern' AND service_description = '$name2Pattern'";
            } else if($name1Pattern != '') {
                $filter = " WHERE host_name = '$name1Pattern'";
            }
            $QUERYHANDLE = $this->mysqlQuery('SELECT host_name AS name1, service_description AS name2 FROM service'.$filter.' ORDER BY host_name');
        } else {
            return Array();
        }

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

        if($type == 'host') {
            $QUERYHANDLE = $this->mysqlQuery('SELECT host_name AS name, address, alias FROM host ORDER BY host_name');
        } elseif($type == 'service') {
            $QUERYHANDLE = $this->mysqlQuery('SELECT host_name AS host, service_description AS description FROM service ORDER BY host_name');
        } elseif(in_array($type, array('hostgroup', 'servicegroup'))) {
            $QUERYHANDLE = $this->mysqlQuery("SELECT {$type}_name AS name, alias FROM $type ORDER BY {$type}_name");
        } else {
            return Array();
        }

        while($data = mysql_fetch_assoc($QUERYHANDLE)) {
            $ret[] = $data;
        }

        // Free memory
        mysql_free_result($QUERYHANDLE);

        return $ret;
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
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    private function getHostAckByHostname($hostName) {
        $return = FALSE;

        // Read from cache or fetch from NDO
        if(isset($this->hostAckCache[$hostName])) {
            $return = $this->hostAckCache[$hostName];
        } else {
            $QUERYHANDLE = $this->mysqlQuery("SELECT problem_has_been_acknowledged FROM host WHERE host_name = '$hostName' LIMIT 1");

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

    public function getHostState($query, $options, $filters) {
    }

    /**
     * PUBLIC getHostState()
     *
     * Returns the Nagios state and additional information for the requested host
     *
     * @param	String		$hostName
     * @param	Boolean		$onlyHardstates
     * @param   Array   Optional array of filters (Not implemented in this backend)
     * @return	array		$state
     * @author	Lars Michelsen <lars@vertical-visions.de>
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getHostStateOld($hostName, $onlyHardstates = null, $filter = null) {
        if(isset($this->hostCache[$hostName.'-'.$onlyHardstates])) {
            return $this->hostCache[$hostName.'-'.$onlyHardstates];
        } else {
            $arrReturn = Array();

            $QUERYHANDLE = $this->mysqlQuery('SELECT host.id AS id, alias, display_name, address, has_been_checked,'
                .' last_hard_state, UNIX_TIMESTAMP(last_hard_state_change) AS last_hard_state_change,'
                .' UNIX_TIMESTAMP(last_state_change) AS last_state_change, current_state,'
                .' output, perf_data, notes, problem_has_been_acknowledged, statusmap_image,'
                .' UNIX_TIMESTAMP(last_check) AS last_check, UNIX_TIMESTAMP(next_check) AS next_check,'
                .' state_type, current_attempt, max_check_attempts,'
                .' UNIX_TIMESTAMP(sdt.start_time) AS downtime_start, UNIX_TIMESTAMP(sdt.end_time) AS downtime_end,'
                .' sdt.author_name AS downtime_author, sdt.comment_data AS downtime_data'
                .' FROM host LEFT JOIN scheduled_downtime AS sdt ON sdt.host_name = host.host_name'
                .' AND NOW() > sdt.start_time AND NOW() < sdt.end_time'
                ." WHERE host.host_name = '$hostName' LIMIT 1");

            if(mysql_num_rows($QUERYHANDLE) == 0) {
                $arrReturn['state'] = 'ERROR';
                $arrReturn['output'] = l('hostNotFoundInDB', Array('BACKENDID' => $this->backendId, 'HOST' => $hostName));
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
                $arrReturn['perfdata'] = $data['perf_data'];
                $arrReturn['last_check'] = $data['last_check'];
                $arrReturn['next_check'] = $data['next_check'];
                $arrReturn['state_type'] = $data['state_type'];
                $arrReturn['current_check_attempt'] = $data['current_attempt'];
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

                if($data['current_state'] == '') {
                    $arrReturn['state'] = 'UNCHECKED';
                    $arrReturn['output'] = l('hostIsPending', Array('HOST' => $hostName));
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

    public function getServiceState($query, $options, $filters) {
    }

    /**
     * PUBLIC getServiceState()
     *
     * Returns the state and additional information of the requested service
     *
     * @param	String		$hostName
     * @param	String 		$serviceName
     * @param	Boolean		$onlyHardstates
     * @return	Array		$state
     * @author	Lars Michelsen <lars@vertical-visions.de>
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getServiceStateOld($hostName, $serviceName = null, $onlyHardstates = null) {
        if(isset($this->serviceCache[$hostName.'-'.$serviceName.'-'.$onlyHardstates])) {
            return $this->serviceCache[$hostName.'-'.$serviceName.'-'.$onlyHardstates];
        } else {
            $arrReturn = Array();


            $QUERYHANDLE = $this->mysqlQuery('SELECT s.id, s.host_name AS name1, s.service_description AS name2,'
                .' s.display_name, s.notes, h.address, s.has_been_checked, s.last_hard_state, s.current_state,'
                .' UNIX_TIMESTAMP(s.last_hard_state_change) AS last_hard_state_change, UNIX_TIMESTAMP(s.last_state_change) AS last_state_change,'
                .' s.output, s.perf_data, s.problem_has_been_acknowledged,'
                .' UNIX_TIMESTAMP(s.last_check) AS last_check, UNIX_TIMESTAMP(s.next_check) AS next_check,'
                .' s.state_type, s.current_attempt, s.max_check_attempts,'
                .' UNIX_TIMESTAMP(sdt.start_time) AS downtime_start, UNIX_TIMESTAMP(sdt.end_time) AS downtime_end,'
                .' sdt.author_name AS downtime_author, sdt.comment_data AS downtime_data'
                .' FROM  host AS h, service AS s LEFT JOIN scheduled_downtime AS sdt ON sdt.host_name = s.host_name'
                .' AND sdt.service_description = s.service_description AND NOW() > sdt.start_time AND NOW() < sdt.end_time'
                ." WHERE s.host_name = '$hostName' AND h.host_name = s.host_name"
                .((isset($serviceName) && $serviceName != '')? " AND s.service_description = '$serviceName' LIMIT 1" : ''));

            if(mysql_num_rows($QUERYHANDLE) == 0) {
                if(isset($serviceName) && $serviceName != '') {
                    $arrReturn['state'] = 'ERROR';
                    $arrReturn['output'] = l('serviceNotFoundInDB', Array('BACKENDID' => $this->backendId, 'SERVICE' => $serviceName, 'HOST' => $hostName));
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
                    $arrTmpReturn['perfdata'] = $data['perf_data'];
                    $arrTmpReturn['last_check'] = $data['last_check'];
                    $arrTmpReturn['next_check'] = $data['next_check'];
                    $arrTmpReturn['state_type'] = $data['state_type'];
                    $arrTmpReturn['current_check_attempt'] = $data['current_attempt'];
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

                    if($data['current_state'] == '') {
                        $arrTmpReturn['state'] = 'PENDING';
                        $arrTmpReturn['output'] = l('serviceNotChecked', Array('SERVICE' => $data['name2']));
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

    public function getHostStateCounts($query, $options, $filters) {}
    public function getHostgroupStateCounts($query, $options, $filters) {}
    public function getServicegroupStateCounts($query, $options, $filters) {}
    public function getDirectParentNamesByHostName($hostName) {}

    /**
     * PUBLIC getHostgroupState()
     *
     * Returns the Nagios state and additional information for the requested hostgroup
     *
     * @param	  String		$hostgroupName
     * @param	  Boolean		$onlyHardstates
     * @param   Array     Optional array of filters (Not implemented in this backend)
     * @return	array		$state
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getHostgroupState($hostgroupName, $onlyHardstates, $filter = null)
    {
        $arrReturn = Array();

        $QUERYHANDLE = $this->mysqlQuery('SELECT host.host_name, last_hard_state, current_state, state_type'
            .' FROM host LEFT JOIN scheduled_downtime AS sdt ON sdt.host_name = host.host_name'
            .' AND NOW() > sdt.start_time AND NOW() < sdt.end_time'
            .' LEFT JOIN host_hostgroup AS hhg ON hhg.host = host.id'
            .' LEFT JOIN hostgroup AS hg ON hg.id = hhg.hostgroup'
            ." WHERE hg.hostgroup_name = '$hostgroupName'");

        if(mysql_num_rows($QUERYHANDLE) == 0) {
            $arrReturn = false;
        } else {
            while($data = mysql_fetch_array($QUERYHANDLE))
            {
                $arrRow = Array('name' => $data['host_name']);

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

                if($data['current_state'] == '') {
                    $arrRow['state'] = 'UNCHECKED';
                } elseif($data['current_state'] == '0') {
                    // Host is UP
                    $arrRow['state'] = 'UP';
                } else {
                    // Host is DOWN/UNREACHABLE/UNKNOWN

                    // Store state and output in array
                    switch($data['current_state']) {
                        case '1':
                            $arrRow['state'] = 'DOWN';
                        break;
                        case '2':
                            $arrRow['state'] = 'UNREACHABLE';
                        break;
                        case '3':
                            $arrRow['state'] = 'UNKNOWN';
                        break;
                        default:
                            $arrRow['state'] = 'UNKNOWN';
                        break;
                    }
                }

                $arrReturn[] = $arrRow;
            }

            // Free memory
            mysql_free_result($QUERYHANDLE);
        }

        return $arrReturn;
    }

    /**
     * PUBLIC getServicegroupState()
     *
     * Returns the Nagios state and additional information for the requested servicegroup
     *
     * @param	String		$servicegroupName
     * @param	Boolean		$onlyHardstates
     * @param   Array   Optional array of filters (Not implemented in this backend)
     * @return	array		$state
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getServicegroupState($servicegroupName, $onlyHardstates, $filter = null)
    {
        $arrReturn = Array();

        $QUERYHANDLE = $this->mysqlQuery('SELECT s.host_name, s.service_description,'
            .' s.last_hard_state, s.current_state, s.state_type'
            .' FROM  host AS h, service AS s LEFT JOIN scheduled_downtime AS sdt ON sdt.host_name = s.host_name'
            .' AND sdt.service_description = s.service_description AND NOW() > sdt.start_time AND NOW() < sdt.end_time'
            .' LEFT JOIN service_servicegroup AS ssg ON ssg.service = service.id'
            .' LEFT JOIN servicegroup AS sg ON sg.id = ssg.servicegroup'
            ." WHERE sg.servicegroup_name = '$servicegroupName'");

        if(mysql_num_rows($QUERYHANDLE) == 0) {
            $arrReturn = false;
        } else {
            while($data = mysql_fetch_array($QUERYHANDLE)) {
                $arrRow = Array('host' => $data['host_name'],
                    'description' => $data['service_description']);

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

                if($data['current_state'] == '') {
                    $arrRow['state'] = 'PENDING';
                } elseif($data['current_state'] == '0') {
                    // Host is UP
                    $arrRow['state'] = 'OK';
                } else {
                    // Host is DOWN/UNREACHABLE/UNKNOWN

                    // Store state and output in array
                    switch($data['current_state']) {
                        case '1':
                            $arrRow['state'] = 'WARNING';
                        break;
                        case '2':
                            $arrRow['state'] = 'CRITICAL';
                        break;
                        case '3':
                            $arrRow['state'] = 'UNKNOWN';
                        break;
                        default:
                            $arrRow['state'] = 'UNKNOWN';
                        break;
                    }
                }

                $arrReturn[] = $arrRow;
            }
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
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getHostNamesWithNoParent() {
        $arrReturn = Array();

        $QUERYHANDLE = $this->mysqlQuery('SELECT host_name FROM host'
            .' LEFT JOIN host_parents ON host.id = host_parents.host'
            .' WHERE host_parents.parents IS NULL');

        while($data = mysql_fetch_array($QUERYHANDLE)) {
            $arrReturn[] = $data['host_name'];
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
     * @param	String			Name of host to get the children of
     * @return	Array			Array with hostnames
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getDirectChildNamesByHostName($hostName) {
        $arrChildNames = Array();

        $QUERYHANDLE = $this->mysqlQuery('SELECT host_name FROM host'
            .' LEFT JOIN host_parents ON host.id = host_parents.host'
            ." WHERE host_parents.parents = (SELECT id FROM host WHERE host_name = '$hostName')");
        while($data = mysql_fetch_array($QUERYHANDLE)) {
            $arrChildNames[] = $data['host_name'];
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
     * @param	String			Name of hostgroup to get the hosts of
     * @return	Array			Array with hostnames
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getHostsByHostgroupName($hostgroupName) {
        $arrReturn = Array();

        $QUERYHANDLE = $this->mysqlQuery('SELECT host_name FROM host'
            .' LEFT JOIN host_hostgroup ON id = host WHERE hostgroup ='
            ." (SELECT id FROM hostgroup WHERE hostgroup_name = '$hostgroupName')");

        while($data = mysql_fetch_array($QUERYHANDLE)) {
            // Assign actual dataset to return array
            $arrReturn[] = $data['host_name'];
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
     * @param	String			Name of servicegroup to get the services of
     * @return	Array			Array with hostnames and service descriptions
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getServicesByServicegroupName($servicegroupName) {
        $arrReturn = Array();

        $QUERYHANDLE = $this->mysqlQuery('SELECT host_name, service_description FROM service'
            .' LEFT JOIN service_servicegroup ON id = service WHERE servicegroup ='
            ." (SELECT id FROM servicegroup WHERE servicegroup_name = '$servicegroupName')");

        while($data = mysql_fetch_array($QUERYHANDLE)) {
            // Assign actual dataset to return array
            $arrReturn[] = $data;
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
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getServicegroupInformations($servicegroupName) {
        $arrReturn = Array();

        $QUERYHANDLE = $this->mysqlQuery("SELECT id, alias FROM servicegroup WHERE servicegroup_name = '$servicegroupName' LIMIT 1");

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
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    public function getHostgroupInformations($groupName) {
        $arrReturn = Array();

        $QUERYHANDLE = $this->mysqlQuery("SELECT id, alias FROM hostgroup WHERE hostgroup_name = '$groupName' LIMIT 1");

        $data = mysql_fetch_array($QUERYHANDLE);

        // Free memory
        mysql_free_result($QUERYHANDLE);

        $arrReturn['alias'] = $data['alias'];

        return $arrReturn;
    }
}
?>