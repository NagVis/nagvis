<?php
/*****************************************************************************
 *
 * GlobalBackendmkbi.php - backend class for connecting NagVis directly
 *                             to Check_MK Business Intelligence via JSON
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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

// needed for isolated tests (direct calling)
if(!function_exists('l')) {
    require_once('GlobalBackendInterface.php');
    require_once('../defines/matches.php');
}

class GlobalBackendmkbi implements GlobalBackendInterface {
    private $backendId = '';
    private $baseUrl   = '';
    private $context   = '';
    private $cache     = Array();

    private static $bi_aggr_states = Array(
        -2 => 'ERROR',
        -1 => 'PENDING',
         0 => 'OK',
         1 => 'WARNING',
         2 => 'CRITICAL',
         3 => 'UNKNOWN',
         4 => 'UNREACHABLE'
    );

    private static $bi_short_states = Array(
        'PD' => 'PENDING',
        'OK' => 'OK',
        'WA' => 'WARNING',
        'CR' => 'CRITICAL',
        'UN' => 'UNKNOWN',
        'MI' => 'ERROR',
        'NA' => 'UNREACHABLE',
    );

    // These are the backend local configuration options
    private static $validConfig = Array(
        'base_url' => Array(
            'must'     => 1,
            'editable' => 1,
            'default'  => 'http://localhost/check_mk/',
            'match'    => MATCH_STRING_URL,
        ),
        'auth_user' => Array(
            'must'     => 0,
            'editable' => 1,
            'default'  => '',
            'match'    => MATCH_STRING,
        ),
        'auth_secret' => Array(
            'must'     => 0,
            'editable' => 1,
            'default'  => '',
            'match'    => MATCH_STRING,
        ),
    );

    /**
     * Basic initialization happens here
     */
    public function __construct($backendId) {
        $this->backendId = $backendId;

        $this->baseUrl = cfg('backend_'.$backendId, 'base_url');

        $httpContext = array( 
            'method'     => 'GET',
            'user_agent' => 'NagVis BI Backend',
            'timeout'    => 5,
        );

        // Always set the HTTP basic auth header
        $username = cfg('backend_'.$backendId, 'auth_user');
        $secret   = cfg('backend_'.$backendId, 'auth_secret');
        if($username && $secret) {
            $authCred = base64_encode($username.':'.$secret);
            $httpContext['header'] = 'Authorization: Basic '.$authCred."\r\n";
        }

        $this->context = stream_context_create(array('http' => $httpContext));
    }

    /**************************************************************************
     * HELPERS
     *************************************************************************/

    private function aggrUrl($name) {
        return $this->baseUrl.'view.py?view_name=aggr_single&aggr_name='.$name.'&po_aggr_expand=1';
    }

    /**
     * The real data fetching method. This performs the HTTP GET and cares
     * about parsing, validating and processing the response.
     */
    private function getUrl($params) {
        $url = $this->baseUrl.$params.'&output_format=json';
        $username = cfg('backend_'.$this->backendId, 'auth_user');
        $secret   = cfg('backend_'.$this->backendId, 'auth_secret');
        if ($username && $secret)
            $url .= '&_username='.$username.'&_secret='.$secret;

        // Is there some cache to use? The cache is not persisted. It is available
        // until the request has finished.
        if(isset($this->cache[$url]))
            return $this->cache[$url];

        //DEBUG:
        //$fh = fopen('/tmp/bi', 'a');
        //fwrite($fh, $url."\n\n");
        //fclose($fh);

        $s = @file_get_contents($url, false, $this->context);
        if($s === false)
            throw new BackendConnectionProblem(l('Unable to fetch data from URL [U]: [M]',
                                                Array('U' => $url, 'M' => json_encode(error_get_last()))));

        //DEBUG:
        //$fh = fopen('/tmp/bi', 'a');
        //fwrite($fh, $s."\n\n");
        //fclose($fh);

        if ($s[0] != '[')
            throw new BackendInvalidResponse(l('Invalid response ([BACKENDID]): [RESPONSE]',
                                                      Array('BACKENDID' => $this->backendId,
                                                            'RESPONSE'  => htmlentities($s, ENT_COMPAT, 'UTF-8'))));

        // Decode the json response
        // json_decode returns null on syntax problems
        $parsed = json_decode(utf8_encode($s), true);
        if ($parsed === null || !is_array($parsed))
            throw new BackendInvalidResponse(l('Invalid response ([BACKENDID]): [RESPONSE]',
                                                      Array('BACKENDID' => $this->backendId,
                                                            'RESPONSE'  => htmlentities($s, ENT_COMPAT, 'UTF-8'))));

        // transform structure of the response to have an array of associative arrays
        $obj = array();
        $head = array_shift($parsed); // extract header spec
        for ($i = 0; $i < count($parsed); $i++)
            $obj[] = array_combine($head, $parsed[$i]);

        // Cache the valid response
        $this->cache[$url] = $obj;

        return $obj;
    }

    /**
     * Returns the identifiers and names of all business processes
     */
    private function getAggregationNames() {
        $aggregations = $this->getUrl('view.py?view_name=aggr_all_api');
        $names = Array();
        foreach($aggregations AS $aggr) {
            $names[$aggr['aggr_name']] = $aggr['aggr_name'];
        }
        ksort($names);
        return $names;
    }

    // Transform BI state numbers to NagVis state names and then to
    // NagVis internal state numbers
    private function getAggrState($state) {
        return state_num(GlobalBackendmkbi::$bi_aggr_states[(int)$state]);
    }

    private function getAggrElements($aggr) {
        // remove leading/trailing newlines
        $raw_states = trim($aggr['aggr_treestate']);
        // replace multiple newlines with singe ones
        $raw_states = preg_replace("/[\n]+/", "\n", $raw_states);
        $parts = explode("\n", $raw_states);
        array_shift($parts); // Remove first entry, the summary state
        array_shift($parts);
        return array_chunk($parts, 2);
    }

    private function getAggrCounts($aggr) {
        $c = Array(
            PENDING => Array(
                'normal'   => 0,
            ),
            OK => Array(
                'normal'   => 0,
                'stale'    => 0,
                'downtime' => 0,
            ),
            WARNING => Array(
                'normal'   => 0,
                'stale'    => 0,
                'ack'      => 0,
                'downtime' => 0,
            ),
            CRITICAL => Array(
                'normal'   => 0,
                'stale'    => 0,
                'ack'      => 0,
                'downtime' => 0,
            ),
            UNKNOWN => Array(
                'normal'   => 0,
                'stale'    => 0,
                'ack'      => 0,
                'downtime' => 0,
            ),
        );

        // Add the single component state counts
        $elements = $this->getAggrElements($aggr);
        foreach ($elements AS $element) {
            // 0: state short code, 1: element name
            $s = state_num(GlobalBackendmkbi::$bi_short_states[$element[0]]);
            if(!isset($c[$s]))
                throw new BackendException(l('Invalid state: "[S]"',
                          Array('S' => $s)));
            $c[$s]['normal']++;
        }

        return $c;
    }

    /**************************************************************************
     * IMPLEMENTING THE NAGVIS BACKEND API BELOW
     *************************************************************************/

    /**
     * Used in WUI forms to populate the object lists when adding or modifying
     * objects in WUI.
     */
    public function getObjects($type, $name1Pattern = '', $name2Pattern = '') {
        if($type !== 'aggr')
            return array();

        $result = Array();
        foreach($this->getAggregationNames() AS $id => $name) {
            $result[] = Array('name1' => $id, 'name2' => $name);
        }
        return $result;
    }

    private function matchAggregation($aggregations, $key) {
        $aggr = null;
        foreach ($aggregations as $aggregation) {
            if ($aggregation['aggr_name'] == $key) {
                $aggr = $aggregation;
                break;
            }
        }
        return $aggr;
    }

    /**
     * Returns the service state counts for a list of aggregations. Using
     * the given objects and filters.
     */
    public function getAggrStateCounts($objects, $options, $filters) {
        $aggregations = $this->getUrl('view.py?view_name=aggr_all_api&po_aggr_expand=1&po_aggr_treetype=0');

        $ret = Array();
        foreach($objects AS $key => $OBJS) {
            $aggr = $this->matchAggregation($aggregations, $key);
            if ($aggr === null)
                continue; // did not find this aggregation
            $obj_url = $OBJS[0]->getUrl();
            $ret[$key] = Array(
                'details' => Array(
                    ALIAS => $aggr['aggr_name'],
                    // This forces the aggregation state to be the summary state of the object
                    STATE => $this->getAggrState($aggr['aggr_state_num']),
                ),
                'attrs' => Array(
                    // Forces the URL to point to the BI aggregate
                    'url' => $obj_url ? $obj_url : $this->aggrUrl($key),
                ),
                'counts'  => $this->getAggrCounts($aggr),
            );

            // Add optional outputs which replaces the NagVis summary_output
            if(isset($aggr['aggr_output']) && $aggr['aggr_output'] != '')
                $ret[$key]['output'] = $aggr['aggr_output'];
        }

        return $ret;
    }

    /**
     * Returns the state with detailed information of a list of services. Using
     * the given objects and filters.
     */
    public function getServiceState($objects, $options, $filters) {
        $aggregations = $this->getUrl('view.py?view_name=aggr_all_api&po_aggr_expand=1&po_aggr_treetype=0');

        $ret = Array();
        foreach($objects AS $key => $OBJS) {
            $aggr = $this->matchAggregation($aggregations, $key);
            if ($aggr === null)
                continue; // did not find this aggregation

            // Add the services
            // Add the single component state counts
            $elements = $this->getAggrElements($aggr);
            foreach ($elements AS $element) {
                // 0: state short code, 1: element name
                $s = state_num(GlobalBackendmkbi::$bi_short_states[$element[0]]);

                $child = array(
                    $s,
                    '',  // output
                    0,
                    0,
                    1,  // state type
                    1, // current attempt
                    1, // max check attempts
                    null,  // last check
                    null,  // next check
                    null, // last hard state change
                    null, // last state change
                    '', // perfdata
                    $element[1],  // display name
                    $element[1],  // alias
                    '',  // address
                    '',  // notes
                    '', // check command
                    null,
                    null, // dt author
                    null, // dt data
                    null, // dt start
                    null, // dt end
                    0, // staleness
                    $element[1] // descr
                );

                $ret[$key][] = $child;
            }
        }

        return $ret;
    }

    /**
     * PUBLIC Method getValidConfig
     * Returns the valid config for this backend
     */
    public static function getValidConfig() {
        return self::$validConfig;
    }

    /***************************************************************************
     * Not implemented methods
     **************************************************************************/

    public function getHostState($objects, $options, $filters) {
        return Array();
    }

    public function getHostMemberCounts($objects, $options, $filters) {
        return Array();
    }

    public function getHostgroupStateCounts($objects, $options, $filters) {
        return Array();
    }

    public function getServicegroupStateCounts($objects, $options, $filters) {

    }

    public function getHostNamesWithNoParent() {
        return Array();
    }

    public function getDirectChildNamesByHostName($hostName) {
        return Array();
    }

    public function getDirectParentNamesByHostName($hostName) {
        return Array();
    }

    public function getDirectChildDependenciesNamesByHostName($hostName) {
        return Array();
    }
}

if(!function_exists('l')) {
    require_once('GlobalBackendInterface.php');
    require_once('CoreExceptions.php');

    function l($s, $a = array()) {
        return $s . ' ' . json_encode($a);
    }

    function cfg($sec, $opt) {
        if($opt == 'base_url')
            return 'http://127.0.0.1/event/check_mk/';
        if($opt == 'auth_user')
            return 'bi-user';
        if($opt == 'auth_secret')
            return 'MATKBYXNV@YXLHSEJYND';
    }

    $O = new GlobalBackendmkbi('bi');
    print_r($O->getAggregationNames());
}

?>
