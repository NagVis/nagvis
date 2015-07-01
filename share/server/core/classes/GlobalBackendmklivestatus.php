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

    private $CONNECT_EXC = null;
    private $SOCKET = null;
    private $socketType = '';
    private $socketPath = '';
    private $socketAddress = '';
    private $socketPort = 0;

    // These are the backend local configuration options
    private static $validConfig = Array(
        'socket' => Array(
          'must'      => 1,
          'editable'  => 1,
          'default'   => 'unix:/usr/local/nagios/var/rw/live',
          'match'     => MATCH_SOCKET,
        ),
        'timeout' => Array(
          'must'      => 1,
          'editable'  => 1,
          'default'   => 5,
          'match'     => MATCH_INTEGER,
        ),
    );

    /**
     * PUBLIC class constructor
     *
     * @param   String        ID if the backend
   * @author  Mathias Kettner <mk@mathias-kettner.de>
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct($backendId) {
        $this->backendId = $backendId;

        // Parse the socket params
        $this->parseSocket(cfg('backend_'.$backendId, 'socket'));

        // Run preflight checks
        if($this->socketType == 'unix' && !$this->checkSocketExists()) {
            throw new BackendConnectionProblem(l('Unable to connect to livestatus socket. The socket [SOCKET] in backend [BACKENDID] does not exist. Maybe Nagios is not running or restarting.',
                         Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
        }

        if(!function_exists('fsockopen')) {
            throw new BackendConnectionProblem(l('The PHP function fsockopen is not available. Needed by backend [BACKENDID].',
                               Array('BACKENDID' => $this->backendId, 'SOCKET' => $this->socketPath)));
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
            fclose($this->SOCKET);
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
            throw new BackendConnectionProblem(
              l('Unknown socket type given in backend [BACKENDID]',
                Array('BACKENDID' => $this->backendId)));
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
        return file_exists($this->socketPath);
    }

    /**
     * PRIVATE connectSocket()
     *
     * Connects to the livestatus socket when no connection is open
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function connectSocket() {
        // Only try to connect once per page. Re-raise the connection exception on
        // later tries to connect
        if($this->CONNECT_EXC != null)
            throw $this->CONNECT_EXC;

        // Connect to the socket
        // don't want to see the connection error messages - want to handle the
        // errors later with an own error message
        // FIXME: Maybe use pfsockopen in the future to use persistent connections
        if($this->socketType === 'unix') {
            $oldLevel = error_reporting(0);
            $this->SOCKET = fsockopen('unix://'.$this->socketPath, NULL, $errno, $errstr, (float) cfg('backend_'.$this->backendId, 'timeout'));
            error_reporting($oldLevel);
        } elseif($this->socketType === 'tcp') {
            $oldLevel = error_reporting(0);
            $this->SOCKET = fsockopen($this->socketAddress, $this->socketPort, $errno, $errstr, (float) cfg('backend_'.$this->backendId, 'timeout'));
            error_reporting($oldLevel);
        }

        if(!$this->SOCKET) {
            $this->SOCKET = null;
            $this->CONNECT_EXC = new BackendConnectionProblem(
                                     l('Unable to connect to the [SOCKET] in backend [BACKENDID]: [MSG]',
                                               Array('BACKENDID' => $this->backendId,
                                                     'SOCKET'    => $this->socketPath,
                                                     'MSG'       => $errstr)));
            throw $this->CONNECT_EXC;
        }
    }

    /*private function verifyLivestatusVersion() {
        $result = $this->queryLivestatusSingleColumn("GET status\nColumns: livestatus_version\n");
        $result[0] = '1.1.7rc1';
        $version = str_replace('.', '0',        $result[0]);
        $version = str_replace('i',  '0',       $version);
        $version = str_replace('b',  '1',       $version);
        $version = (int) str_replace('rc', '2', $version);
        if($version < 1010903) {
            throw new BackendConnectionProblem(
               l('The livestatus version [VERSION] used in backend [BACKENDID] is too old. Please update.',
                 Array('BACKENDID' => $this->backendId, 'VERSION' => $result[0])));
        }
    }*/

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
    private function queryLivestatus($query, $response = true) {
        // Only connect when no connection opened yet
        if($this->SOCKET === null) {
            $this->connectSocket();

            // Check if the livestatus version is OK
            //$this->verifyLivestatusVersion();
        }

        //$fh = fopen('/tmp/live', 'a');
        //fwrite($fh, $query."\n\n");
        //fclose($fh);

        // Query to get a json formated array back
        // Use KeepAlive with fixed16 header
        if($response)
            $query .= "OutputFormat: json\nKeepAlive: on\nResponseHeader: fixed16\n\n";
        // Disable regular error reporting to suppress php error messages
        $oldLevel = error_reporting(0);
        $write = fwrite($this->SOCKET, $query);
        error_reporting($oldLevel);

        if($write=== false)
            throw new BackendConnectionProblem(l('Problem while writing to socket [SOCKET] in backend [BACKENDID]: [MSG]',
                                                 Array('BACKENDID' => $this->backendId,
                                                       'SOCKET'    => $this->socketPath,
                                                       'MSG'       => 'Error while sending query to socket.')));

        if($write !== strlen($query))
            throw new BackendConnectionProblem(l('Problem while writing to socket [SOCKET] in backend [BACKENDID]: [MSG]',
                                                 Array('BACKENDID' => $this->backendId,
                                                       'SOCKET'    => $this->socketPath,
                                                       'MSG'       => 'Connection terminated.')));


        // Return here if no answer is expected
        if(!$response)
            return;

        // Read 16 bytes to get the status code and body size
        $read = $this->readSocket(16);

        // Catch problem while reading
        if($read === false)
            throw new BackendConnectionProblem(l('Problem while reading from socket [SOCKET] in backend [BACKENDID]: [MSG]',
                                                 Array('BACKENDID' => $this->backendId,
                                                       'SOCKET'    => $this->socketPath,
                                                       'MSG'       => 'Error while reading socket (header)')));

        // Extract status code
        $status = substr($read, 0, 3);

        // Extract content length
        $len = intval(trim(substr($read, 4, 11)));

        // Read socket until end of data
        $read = $this->readSocket($len);

        // Catch problem while reading
        if($read === false) {
            throw new BackendConnectionProblem(l('Problem while reading from socket [SOCKET] in backend [BACKENDID]: [MSG]',
                                                 Array('BACKENDID' => $this->backendId,
                                                       'SOCKET'    => $this->socketPath,
                                                       'MSG'       => 'Error while reading socket (content)')));
        }

        // Catch errors (Like HTTP 200 is OK)
        if($status != "200") {
            throw new BackendConnectionProblem(l('Problem while reading from socket [SOCKET] in backend [BACKENDID]: [MSG]',
                                                 Array('BACKENDID' => $this->backendId,
                                                       'SOCKET'    => $this->socketPath,
                                                       'MSG'       => $read)));
        }

        //$fh = fopen('/tmp/live', 'a');
        //fwrite($fh, $read."\n\n");
        //fclose($fh);

        // Decode the json response
        $obj = json_decode(utf8_encode($read));

        // TEST: Disable KeepAlive:
        //fclose($this->SOCKET);
        //$this->SOCKET = null;

        // json_decode returns null on syntax problems
        if($obj === null) {
            throw new BackendInvalidResponse(l('The response has an invalid format in backend [BACKENDID].',
                                               Array('BACKENDID' => $this->backendId)));
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
            if(($data = @fread($this->SOCKET, $len - $offset)) === false) {
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
     * PUBLIC query()
     * This is a special method which is currently unused within NagVis.
     * It has been added as interface to the std_lq.php script.
     */
    public function query($type, $query) {
        switch($type) {
            case 'column':
                return $this->queryLivestatusSingleColumn($query);
            break;
            case 'row':
                return $this->queryLivestatusSingleRow($query);
            break;
            default:
                return $this->queryLivestatus($query);
            break;
        }
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
    public function getObjects($type, $name1Pattern = '', $name2Pattern = '', $add_filter = '') {
        $ret = Array();
        $filter = '';
        $sFile = '';

        switch($type) {
            case 'host':
            case 'hostgroup':
            case 'servicegroup':
                $l = $this->queryLivestatus("GET ".$type."s\nColumns: name alias\n".$add_filter);
            break;
            case 'service':
                $query = "GET services\nColumns: host_name description\n";

                if($name1Pattern) {
                    $query .= "Filter: host_name = " . $name1Pattern . "\n";
                }
                $query .= $add_filter;

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
    private function parseFilter($objects, $filters, $isMemberQuery = false,
                                 $isCountQuery = false, $isHostQuery = true) {
        $aFilters = Array();
        foreach($objects AS $OBJS) {
            $objFilters = Array();
            foreach($filters AS $filter) {
                if ($isHostQuery && $filter['key'] == 'host_name')
                    $key = 'name';
                else
                    $key = $filter['key'];

                // Array('key' => 'host_name', 'operator' => '=', 'name'),
                switch($key) {
                    case 'name':
                    case 'host_name':
                    case 'host_groups':
                    case 'service_description':
                    case 'groups':
                    case 'service_groups':
                    case 'hostgroup_name':
                    case 'group_name':
                    case 'servicegroup_name':
                        if($key != 'service_description')
                            $val = $OBJS[0]->getName();
                        else
                            $val = $OBJS[0]->getServiceDescription();

                        $objFilters[] = 'Filter: '.$key.' '.$filter['op'].' '.$val."\n";
                    break;
                    default:
                        throw new BackendConnectionProblem('Invalid filter key ('.$key.')');
                    break;
                }
            }

            // is this a dynamic group with already compiled filters?
            if($isMemberQuery && $OBJS[0]->getType() == 'dyngroup') {
                return $OBJS[0]->getObjectFilter();
            }
            
            // Are there child exclude filters defined for this object?
            // The objType is the type of the objects to query the data for
            if($isMemberQuery && $OBJS[0]->hasExcludeFilters($isCountQuery)) {
                $filter = $OBJS[0]->getExcludeFilter($isCountQuery);
                $objType = $OBJS[0]->getType();

                if($objType == 'host') {
                    $parts = explode('~~', $filter);
                    if(!isset($parts[1]))
                        $objFilters[] = 'Filter: service_description !~~ '.$filter."\n";

                } elseif($objType == 'hostgroup' && $isHostQuery) {
                    $parts = explode('~~', $filter);
                    if(!isset($parts[1]))
                        $objFilters[] = 'Filter: host_name !~~ '.$parts[0]."\n";

                } elseif(($objType == 'hostgroup' && !$isHostQuery) || $objType == 'servicegroup') {
                    $parts = explode('~~', $filter);
                    if(isset($parts[1]))
                        $objFilters[] = 'Filter: host_name ~~ '.$parts[0]."\n"
                                       .'Filter: service_description ~~ '.$parts[1]."\n"
                                       ."Negate:\n";
                    else
                        $objFilters[] = 'Filter: host_name !~~ '.$parts[0]."\n";
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

    private function serviceStateStats($stateAttr) {
        $staleness_thresh = cfg('global', 'staleness_threshold');

        return
            // Count PENDING
            "Stats: has_been_checked = 0\n" .
            // Count OK
            "Stats: ".$stateAttr." = 0\n" .
            "Stats: has_been_checked != 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: host_scheduled_downtime_depth = 0\n" .
            "Stats: staleness < ".$staleness_thresh."\n" .
            "StatsAnd: 5\n" .
            // Count OK (STALE)
            "Stats: ".$stateAttr." = 0\n" .
            "Stats: has_been_checked != 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: host_scheduled_downtime_depth = 0\n" .
            "Stats: staleness >= ".$staleness_thresh."\n" .
            "StatsAnd: 5\n" .
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
            "Stats: staleness < ".$staleness_thresh."\n" .
            "StatsAnd: 6\n" .
            // Count WARNING (STALE)
            "Stats: ".$stateAttr." = 1\n" .
            "Stats: acknowledged = 0\n" .
            "Stats: host_acknowledged = 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: host_scheduled_downtime_depth = 0\n" .
            "Stats: staleness >= ".$staleness_thresh."\n" .
            "StatsAnd: 6\n" .
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
            "Stats: staleness < ".$staleness_thresh."\n" .
            "StatsAnd: 6\n" .
            // Count CRITICAL (STALE)
            "Stats: ".$stateAttr." = 2\n" .
            "Stats: acknowledged = 0\n" .
            "Stats: host_acknowledged = 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: host_scheduled_downtime_depth = 0\n" .
            "Stats: staleness >= ".$staleness_thresh."\n" .
            "StatsAnd: 6\n" .
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
            "Stats: staleness < ".$staleness_thresh."\n" .
            "StatsAnd: 6\n" .
            // Count UNKNOWN (STALE)
            "Stats: ".$stateAttr." = 3\n" .
            "Stats: acknowledged = 0\n" .
            "Stats: host_acknowledged = 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: host_scheduled_downtime_depth = 0\n" .
            "Stats: staleness >= ".$staleness_thresh."\n" .
            "StatsAnd: 6\n" .
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
            "StatsAnd: 2\n";
    }

    /**
     * PUBLIC getHostState()
     *
     * Queries the livestatus socket for the state of one or several hosts
     *
     * @param   Array     List of objects to query
     * @param   Array     List of filters to apply
     * @author  Mathias Kettner <mk@mathias-kettner.de>
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getHostState($objects, $options, $filters, $isMemberQuery = false) {
        $objFilter = $this->parseFilter($objects, $filters, $isMemberQuery, !COUNT_QUERY, HOST_QUERY);

        if($options & 1)
            $stateAttr = 'hard_state';
        else
            $stateAttr = 'state';

        $q = "GET hosts\n".
          "Columns: ".$stateAttr." plugin_output alias display_name ".
          "address notes last_check next_check state_type ".
          "current_attempt max_check_attempts last_state_change ".
          "last_hard_state_change perf_data acknowledged ".
          "scheduled_downtime_depth has_been_checked name ".
          "check_command custom_variable_names custom_variable_values staleness\n".
          $objFilter;

        $l = $this->queryLivestatus($q);

        $arrReturn = Array();

        if(is_array($l) && count($l) > 0) {
            foreach($l as $e) {
                // Catch unchecked objects
                // $e[16]: has_been_checked
                // $e[0]:  state
                if($e[16] == 0 || $e[0] === '') {
                    $arrReturn[$e[18]] = Array(
                        UNCHECKED,
                        l('hostIsPending', Array('HOST' => $e[18])),
                        null,
                        null,
                        null,
                    );
                    continue;
                }

                switch($e[0]) {
                    case "0": $state = UP; break;
                    case "1": $state = DOWN; break;
                    case "2": $state = UNREACHABLE; break;
                    default:  $state = UNKNOWN; break;
                }

                // 15: acknowledged
                $acknowledged = $state != UP && $e[14] == 1;

                // 19: keys, 20: values
                if(isset($e[19][0]) && isset($e[20][0]))
                    $custom_vars = array_combine($e[19], $e[20]);
                else
                    $custom_vars = null;

                // If there is a downtime for this object, save the data
                // $e[15]: scheduled_downtime_depth
                $dt_details = array(null, null, null, null);
                if(isset($e[15]) && $e[15] > 0) {
                    $in_downtime = true;

                    // This handles only the first downtime. But this is not backend
                    // specific. The other backends do this as well.
                    $data = $this->queryLivestatusSingleRow(
                        "GET downtimes\n".
                        "Columns: author comment start_time end_time\n" .
                        "Filter: host_name = ".$e[17]."\n");
                    if(isset($data[0]))
                        $dt_details = $data;
                } else {
                    $in_downtime = false;
                }

                $arrReturn[$e[17]] = Array(
                    $state,
                    $e[1],  // output
                    $acknowledged,
                    $in_downtime,
                    (float)$e[21], // staleness
                    $e[8],  // state type
                    $e[9],  // current attempt
                    $e[10], // max attempts
                    $e[6],  // last check
                    $e[7],  // next check
                    $e[12], // last hard state change
                    $e[11], // last state change
                    $e[13], // perfdata
                    $e[3],  // display name
                    $e[2], // alias
                    $e[4],  // address
                    $e[5],  // notes
                    $e[18], // check command
                    $custom_vars,
                    $dt_details[0], // downtime author
                    $dt_details[1], // downtime comment
                    $dt_details[2], // downtime start
                    $dt_details[3], // downtime end
                );
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
    public function getServiceState($objects, $options, $filters, $isMemberQuery = false) {
        $objFilter = $this->parseFilter($objects, $filters, $isMemberQuery, !COUNT_QUERY, !HOST_QUERY);

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
          "has_been_checked host_name check_command custom_variable_names custom_variable_values ".
          "staleness\n");

        $arrReturn = Array();
        if(is_array($l) && count($l) > 0) {
            foreach($l as $e) {
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
                    $svc = array_fill(0, EXT_STATE_SIZE, null);
                    $svc[DESCRIPTION]  = $e[0];
                    $svc[DISPLAY_NAME] = $e[1];
                    $svc[STATE]  = PENDING;
                    $svc[OUTPUT] = l('serviceNotChecked', Array('SERVICE' => $e[0]));
                } else {
                    switch ($e[2]) {
                        case "0": $state = OK; break;
                        case "1": $state = WARNING; break;
                        case "2": $state = CRITICAL; break;
                        case "3": $state = UNKNOWN; break;
                        default:  $state = UNKNOWN; break;
                    }

                    /**
                     * Handle host/service acks
                     *
                     * If state is not OK (=> WARN, CRIT, UNKNOWN) and service is not
                     * acknowledged => check for acknowledged host
                     */
                    // $e[16]: acknowledged
                    // $e[17]: host_acknowledged
                    $acknowledged = $state != OK && ($e[16] == 1 || $e[17] == 1);

                    // Handle host/service downtimes
                    // $e[15]: scheduled_downtime_depth
                    // $e[18]: host_scheduled_downtime_depth
                    $dt_details = array(null, null, null, null);
                    if((isset($e[15]) && $e[15] > 0) || (isset($e[18]) && $e[18] > 0)) {
                        $in_downtime = true;

                        // This handles only the first downtime. But this is not backend
                        // specific. The other backends do this as well.

                        // Handle host/service downtime difference
                        if(isset($e[15]) && $e[15] > 0) {
                            // Service downtime
                            $data = $this->queryLivestatusSingleRow(
                              "GET downtimes\n".
                              "Columns: author comment start_time end_time\n" .
                              "Filter: host_name = ".$e[20]."\n" .
                              "Filter: service_description = ".$e[0]."\n");
                        } else {
                            // Host downtime
                            $data = $this->queryLivestatusSingleRow(
                              "GET downtimes\n".
                              "Columns: author comment start_time end_time\n" .
                              "Filter: host_name = ".$e[20]."\n");
                        }
                        if(isset($data[0]))
                            $dt_details = $data;
                    } else {
                        $in_downtime = false;
                    }

                    if(isset($e[22][0]) && isset($e[23][0]))
                        $custom_vars = array_combine($e[22], $e[23]);
                    else
                        $custom_vars = null;

                    $svc = array(
                        $state,
                        $e[5],  // output
                        $acknowledged,
                        $in_downtime,
                        (float)$e[24], // staleness
                        $e[9],  // state type
                        $e[10], // current attempt
                        $e[11], // max check attempts
                        $e[7],  // last check
                        $e[8],  // next check
                        $e[13], // last hard state change
                        $e[12], // last state change
                        $e[14], // perfdata
                        $e[1],  // display name
                        $e[3],  // alias
                        $e[4],  // address
                        $e[6],  // notes
                        $e[21], // check command
                        $custom_vars,
                        $dt_details[0], // dt author
                        $dt_details[1], // dt data
                        $dt_details[2], // dt start
                        $dt_details[3], // dt end
                        $e[0], // descr
                    );
                }

                if($specific) {
                    $arrReturn[$key] = $svc;
                } else {
                    if(!isset($arrReturn[$key]))
                        $arrReturn[$key] = array();

                    $arrReturn[$key][] = $svc;
                }
            }
        }

        return $arrReturn;
    }

    /**
     * Queries the livestatus socket for host state counts. The information
     * are used to calculate the summary output and the summary state of a
     * host and a well performing alternative to the existing recurisve
     * algorithm.
     */
    public function getHostMemberCounts($objects, $options, $filters) {
        $objFilter = $this->parseFilter($objects, $filters, MEMBER_QUERY, COUNT_QUERY, !HOST_QUERY);

        if($options & 1)
            $stateAttr = 'last_hard_state';
        else
            $stateAttr = 'state';

        // Get service information
        $l = $this->queryLivestatus("GET services\n" .
            $objFilter.
            $this->serviceStateStats($stateAttr).
            "Columns: host_name host_alias\n");

        $arrReturn = Array();
        if(is_array($l) && count($l) > 0) {
            // livestatus previous 1.1.9i3 answers without host_alias - these users should update.
            if(!isset($l[0][13]))
                throw new BackendInvalidResponse(
                    l('Livestatus version used in backend [BACKENDID] is too old. Please update.',
                                                           Array('BACKENDID' => $this->backendId)));

            foreach($l as $e) {
                $arrReturn[$e[0]] = Array(
                    //'details' => Array('alias' => $e[1]),
                    'counts' => Array(
                        PENDING => Array(
                            'normal'   => intval($e[2]),
                        ),
                        OK => Array(
                            'normal'   => intval($e[3]),
                            'stale'    => intval($e[4]),
                            'downtime' => intval($e[5]),
                        ),
                        WARNING => Array(
                            'normal'   => intval($e[6]),
                            'stale'    => intval($e[7]),
                            'ack'      => intval($e[8]),
                            'downtime' => intval($e[9]),
                        ),
                        CRITICAL => Array(
                            'normal'   => intval($e[10]),
                            'stale'    => intval($e[11]),
                            'ack'      => intval($e[12]),
                            'downtime' => intval($e[13]),
                        ),
                        UNKNOWN => Array(
                            'normal'   => intval($e[14]),
                            'stale'    => intval($e[15]),
                            'ack'      => intval($e[16]),
                            'downtime' => intval($e[17]),
                        ),
                    )
                );
            }
        }

        return $arrReturn;
    }

    /**
     * Queries the livestatus socket a bunch of services matching a given livestatus filter.
     * It does not return all objects, instead it returns the state counts.
     */
    public function getServiceListCounts($options, $filter) {
        if($options & 1)
            $stateAttr = 'last_hard_state';
        else
            $stateAttr = 'state';

        // Get service information
        $l = $this->queryLivestatus("GET services\n" .
            $filter.
            $this->serviceStateStats($stateAttr)
        );

        $counts = Array();
        if(!is_array($l) || count($l) != 1)
            return array();
        $e = $l[0];
        return Array(
            PENDING => Array(
                'normal'   => intval($e[0]),
            ),
            OK => Array(
                'normal'   => intval($e[1]),
                'stale'    => intval($e[2]),
                'downtime' => intval($e[3]),
            ),
            WARNING => Array(
                'normal'   => intval($e[4]),
                'stale'    => intval($e[5]),
                'ack'      => intval($e[6]),
                'downtime' => intval($e[7]),
            ),
            CRITICAL => Array(
                'normal'   => intval($e[8]),
                'stale'    => intval($e[9]),
                'ack'      => intval($e[10]),
                'downtime' => intval($e[11]),
            ),
            UNKNOWN => Array(
                'normal'   => intval($e[12]),
                'stale'    => intval($e[13]),
                'ack'      => intval($e[14]),
                'downtime' => intval($e[15]),
            ),
        );
    }

    public function getHostAndServiceCounts($options, $host_filter, $service_filter, $by_group = true) {
        if ($by_group) {
            $host_suffix    = 'bygroup';
            $service_suffix = 'byhostgroup';

            $host_grouping    = "Columns: hostgroup_name hostgroup_alias\n";
            $service_grouping = "Columns: hostgroup_name\n";

            $hoffset = 2;
            $soffset = 1;
        } else {
            $host_suffix    = '';
            $service_suffix = '';

            $host_grouping    = '';
            $service_grouping = '';

            $hoffset = 0;
            $soffset = 0;
        }

        $staleness_thresh = cfg('global', 'staleness_threshold');

        if($options & 1)
            $stateAttr = 'hard_state';
        else
            $stateAttr = 'state';

        // Get host information
        $q = "GET hosts".$host_suffix."\n" .
            $host_filter.
            // Count UNCHECKED
            "Stats: has_been_checked = 0\n" .
            // Count UP
            "Stats: ".$stateAttr." = 0\n" .
            "Stats: has_been_checked != 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: staleness < ".$staleness_thresh."\n" .
            "StatsAnd: 4\n" .
            // Count UP(STALE)
            "Stats: ".$stateAttr." = 0\n" .
            "Stats: has_been_checked != 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: staleness >= ".$staleness_thresh."\n" .
            "StatsAnd: 4\n" .
            // Count UP (DOWNTIME)
            "Stats: ".$stateAttr." = 0\n" .
            "Stats: has_been_checked != 0\n" .
            "Stats: scheduled_downtime_depth > 0\n" .
            "StatsAnd: 3\n" .
            // Count DOWN
            "Stats: ".$stateAttr." = 1\n" .
            "Stats: acknowledged = 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: staleness < ".$staleness_thresh."\n" .
            "StatsAnd: 4\n" .
            // Count DOWN(STALE)
            "Stats: ".$stateAttr." = 1\n" .
            "Stats: acknowledged = 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: staleness >= ".$staleness_thresh."\n" .
            "StatsAnd: 4\n" .
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
            "Stats: staleness < ".$staleness_thresh."\n" .
            "StatsAnd: 4\n" .
            // Count UNREACHABLE(STALE)
            "Stats: ".$stateAttr." = 2\n" .
            "Stats: acknowledged = 0\n" .
            "Stats: scheduled_downtime_depth = 0\n" .
            "Stats: staleness >= ".$staleness_thresh."\n" .
            "StatsAnd: 4\n" .
            // Count UNREACHABLE(ACK)
            "Stats: ".$stateAttr." = 2\n" .
            "Stats: acknowledged = 1\n" .
            "StatsAnd: 2\n" .
            // Count UNREACHABLE(DOWNTIME)
            "Stats: ".$stateAttr." = 2\n" .
            "Stats: scheduled_downtime_depth > 0\n" .
            "StatsAnd: 2\n".
            $host_grouping;
        $l = $this->queryLivestatus($q);

        // If the method should fetch several objects and did not find
        // any object, don't return anything => The message
        // that the objects were not found is added by the core
        $arrReturn = Array();
        if(is_array($l) && count($l) > 0) {
            // livestatus previous 1.1.9i3 answers without hostgroup_alias - these users should update.
            if(!isset($l[0][8+$hoffset]))
                throw new BackendInvalidResponse(
                    l('Livestatus version used in backend [BACKENDID] is too old. Please update.',
                                                                        Array('BACKENDID' => $this->backendId)));
            foreach($l as $e) {
                $counts = array(
                    UNCHECKED => Array(
                        'normal'    => intval($e[0+$hoffset]),
                    ),
                    UP => Array(
                        'normal'    => intval($e[1+$hoffset]),
                        'stale'     => intval($e[2+$hoffset]),
                        'downtime'  => intval($e[3+$hoffset]),
                    ),
                    DOWN => Array(
                        'normal'    => intval($e[4+$hoffset]),
                        'stale'     => intval($e[5+$hoffset]),
                        'ack'       => intval($e[6+$hoffset]),
                        'downtime'  => intval($e[7+$hoffset]),
                    ),
                    UNREACHABLE => Array(
                        'normal'    => intval($e[8+$hoffset]),
                        'stale'     => intval($e[9+$hoffset]),
                        'ack'       => intval($e[10+$hoffset]),
                        'downtime'  => intval($e[11+$hoffset]),
                    ),
                );

                if(!$by_group) {
                    $arrReturn = $counts;
                } else {
                    $arrReturn[$e[0]] = Array(
                        'details' => Array(ALIAS => $e[1]),
                        'counts'  => $counts
                    );
                }
            }
        }

        // If recognize_services are disabled don't fetch service information
        if($options & 2)
            return $arrReturn;

        if($options & 1)
            $stateAttr = 'last_hard_state';
        else
            $stateAttr = 'state';

        // Get service information
        $l = $this->queryLivestatus("GET services".$service_suffix."\n" .
            $service_filter.
            $this->serviceStateStats($stateAttr).
            $service_grouping);

        if(is_array($l) && count($l) > 0) {
            foreach($l as $e) {
                $counts = array(
                    PENDING => array(
                        'normal'   => intval($e[0+$soffset])
                    ),
                    OK => array(
                        'normal'   => intval($e[1+$soffset]),
                        'stale'    => intval($e[2+$soffset]),
                        'downtime' => intval($e[3+$soffset]),
                    ),
                    WARNING => array(
                        'normal'   => intval($e[4+$soffset]),
                        'stale'    => intval($e[5+$soffset]),
                        'ack'      => intval($e[6+$soffset]),
                        'downtime' => intval($e[7+$soffset]),
                    ),
                    CRITICAL => array(
                        'normal'   => intval($e[8+$soffset]),
                        'stale'    => intval($e[9+$soffset]),
                        'ack'      => intval($e[10+$soffset]),
                        'downtime' => intval($e[11+$soffset]),
                    ),
                    UNKNOWN => array(
                        'normal'   => intval($e[12+$soffset]),
                        'stale'    => intval($e[13+$soffset]),
                        'ack'      => intval($e[14+$soffset]),
                        'downtime' => intval($e[15+$soffset]),
                    ),
                );
                
                if(!$by_group) {
                    $arrReturn += $counts;
                } else {
                    $arrReturn[$e[0]]['counts'] += $counts;
                }
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
        $host_filter = $this->parseFilter($objects, $filters, MEMBER_QUERY, COUNT_QUERY, HOST_QUERY);

        $service_filter = $this->parseFilter($objects, $filters, MEMBER_QUERY, COUNT_QUERY, !HOST_QUERY);
        // Little hack to correct the different field names
        $service_filter = str_replace(' groups ', ' host_groups ', $service_filter);

        return $this->getHostAndServiceCounts($options, $host_filter, $service_filter);
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
        $objFilter = $this->parseFilter($objects, $filters, MEMBER_QUERY, COUNT_QUERY, !HOST_QUERY);

        if($options & 1)
            $stateAttr = 'last_hard_state';
        else
            $stateAttr = 'state';

        // Get service information
        $l = $this->queryLivestatus("GET servicesbygroup\n" .
            $objFilter.
            $this->serviceStateStats($stateAttr).
            "Columns: servicegroup_name servicegroup_alias\n");

        // If the method should fetch several objects and did not find
        // any object, don't return anything => The message
        // that the objects were not found is added by the core
        $arrReturn = Array();
        if(is_array($l) && count($l) > 0) {
            // livestatus previous 1.1.9i3 answers without servicegroup_alias - these users should update.
            if(!isset($l[0][13]))
                throw new BackendInvalidResponse(
                    l('Livestatus version used in backend [BACKENDID] is too old. Please update.',
                                                                        Array('BACKENDID' => $this->backendId)));
            foreach($l as $e) {
                $arrReturn[$e[0]] = Array(
                    'details' => Array(ALIAS => $e[1]),
                    'counts' => Array(
                        PENDING => Array(
                            'normal'    => intval($e[2]),
                        ),
                        OK => Array(
                            'normal'    => intval($e[3]),
                            'stale'     => intval($e[4]),
                            'downtime'  => intval($e[5]),
                        ),
                        WARNING => Array(
                            'normal'    => intval($e[6]),
                            'stale'     => intval($e[7]),
                            'ack'       => intval($e[8]),
                            'downtime'  => intval($e[9]),
                        ),
                        CRITICAL => Array(
                            'normal'    => intval($e[10]),
                            'stale'     => intval($e[11]),
                            'ack'       => intval($e[12]),
                            'downtime'  => intval($e[13]),
                        ),
                        UNKNOWN => Array(
                            'normal'    => intval($e[14]),
                            'stale'     => intval($e[15]),
                            'ack'       => intval($e[16]),
                            'downtime'  => intval($e[17]),
                        ),
                    )
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

    public function getHostNamesInHostgroup($name) {
        $r = $this->queryLivestatusSingleColumn("GET hostgroups\nColumns: members\nFilter: name = ".$name."\n");
        return $r[0];
    }

    public function getProgramStart() {
	$r = $this->queryLivestatusSingleColumn("GET status\nColumns: program_start\n");
        if(isset($r[0]))
            return $r[0];
        else
            return -1;
    }

    public function getGeomapHosts($filterHostgroup = null) {
        $query = "GET hosts\nColumns: name custom_variable_names custom_variable_values alias\n";
        if($filterHostgroup) {
            $query .= "Filter: groups >= ".$filterHostgroup."\n";
        }
        $r = $this->queryLivestatus($query);
        $hosts = array();
        foreach($r AS $row) {
            if($row[1] && $row[2]) {
	        $custom_variables = array_combine($row[1], $row[2]);
                if(isset($custom_variables['LAT']) && isset($custom_variables['LONG'])) {
                    $hosts[] = array(
                        'name'       => $row[0],
                        'lat'        => $custom_variables['LAT'],
                        'long'       => $custom_variables['LONG'],
                        'alias'      => $row[3],
                        'backend_id' => $this->backendId,
                    );
                }
            }
        }
        return $hosts;
    }

    private function command($cmd) {
        return $this->queryLivestatus('COMMAND ['.time().'] '.$cmd."\n", false);
    }

    /**
     * Sends acknowledgement command to monitoring core
     */
    public function actionAcknowledge($what, $spec, $comment, $sticky, $notify, $persist, $user) {
        if($what == 'host')
            $what = 'HOST';
        elseif($what == 'service')
            $what = 'SVC';
        
        $sticky  = $sticky ? '2' : '0';
        $notify  = $notify ? '1' : '0';
        $persist = $notify ? '1' : '0';

        $this->command('ACKNOWLEDGE_'.$what.'_PROBLEM;'.$spec.';'.$sticky.';'.$notify.';'.$persist.';'.$user.';'.$comment);
    }
    /**
     * PUBLIC getDirectChildDependenciesNamesByHostName()
     *
     * Queries the livestatus socket for all direct childs dependencies of a host
     *
     * @param   String   Hostname
     * @return  Array    List of hostnames
     * @author  Thibault Cohen <thibault.cohen@savoirfairelinux.com>
     */
    public function getDirectChildDependenciesNamesByHostName($hostName, $min_business_impact=false) {
        $query = "GET hosts\nColumns: child_dependencies\nFilter: name = ".$hostName."\n";
        $raw_result = $this->queryLivestatusSingleColumn($query);
        if ($min_business_impact) {
            $query = "GET hosts\nColumns: name\nFilter: name = $raw_result[0][1]\n";
            foreach ($raw_result[0] as &$value) {
                $query = $query . "Filter: name = $value\nOr: 2\n";
            }
            $query = $query . "Filter: business_impact >= $min_business_impact\nAnd: 2\n";
            $result = $this->queryLivestatusSingleColumn($query);
        }
        else {
            $result = array();
            foreach ($raw_result[0] as &$value) {
                if (strpos($value, "/") == False) {
                    array_push($result, $value);
                }
            }
        }
        return $result;
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
    public function getDirectParentDependenciesNamesByHostName($hostName, $min_business_impact=false) {
        $query = "GET hosts\nColumns: parent_dependencies\nFilter: name = ".$hostName."\n";
        $raw_result = $this->queryLivestatusSingleColumn($query);
        if ($min_business_impact) {
            $query = "GET hosts\nColumns: name\nFilter: name = $raw_result[0][1]\n";
            foreach ($raw_result[0] as &$value) {
                $query = $query . "Filter: name = $value\nOr: 2\n";
            }
            $query = $query . "Filter: business_impact >= $min_business_impact\nAnd: 2\n";
            $result = $this->queryLivestatusSingleColumn($query);
        }
        else {
            $result = array();
            foreach ($raw_result[0] as &$value) {
                if (strpos($value, "/") == False) {
                    array_push($result, $value);
                }
            }
        }
        return $result;
    }

    /**
     * Returns an assoziative array with contact names as keys and
     * a list of their contactgroups as value
     */
    public function getContactsWithGroups() {
        $contacts = array();
        $query = "GET contactgroups\nColumns: name members\n";
        foreach($this->queryLivestatus($query) as $row) {
            foreach($row[1] as $contact) {
                if(!isset($contacts[$contact]))
                    $contacts[$contact] = array();
                $contacts[$contact][] = $row[0];
            }
        }
        return $contacts;
    }
}
?>
