<?php
/*****************************************************************************
 *
 * GlobalBackendmkbi.php - backend class for connecting NagVis directly
 *                             to Check_MK Business Intelligence via JSON
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

// needed for isolated tests (direct calling)
if (!function_exists('l')) {
    require_once('GlobalBackendInterface.php');
    require_once('../defines/matches.php');
}

class GlobalBackendmkbi implements GlobalBackendInterface
{
    /** @var string */
    private $backendId = '';

    /** @var string */
    private $baseUrl   = '';

    /** @var string|resource */
    private $context   = '';

    /** @var array */
    private $cache     = [];

    /** @var string[] */
    private static $bi_aggr_states = [
        -2 => 'ERROR',
        -1 => 'PENDING',
        0 => 'OK',
        1 => 'WARNING',
        2 => 'CRITICAL',
        3 => 'UNKNOWN',
        4 => 'UNREACHABLE'
    ];

    /** @var int[] */
    private static $bi_short_states = [
        'PD' => -1,
        'OK' =>  0,
        'WA' =>  1,
        'CR' =>  2,
        'UN' =>  3,
        'MI' => -2,
        'NA' =>  4,
    ];

    /** @var array These are the backend local configuration options */
    private static $validConfig = [
        'base_url' => [
            'must'     => 1,
            'editable' => 1,
            'default'  => 'http://localhost/check_mk/',
            'match'    => MATCH_STRING_URL,
        ],
        'auth_user' => [
            'must'     => 0,
            'editable' => 1,
            'default'  => '',
            'match'    => MATCH_STRING,
        ],
        'auth_secret' => [
            'must'     => 0,
            'editable' => 1,
            'default'  => '',
            'match'    => MATCH_STRING,
        ],
        'auth_secret_file' => [
            'must'     => 0,
            'editable' => 1,
            'default'  => '',
            'match'    => MATCH_STRING_PATH,
        ],
        'verify_peer' => [
            'must'       => 0,
            'editable'   => 1,
            'default'    => 1,
            'match'      => MATCH_BOOLEAN,
            'field_type' => 'boolean',
        ],
        'verify_depth' => [
            'must'       => 0,
            'editable'   => 1,
            'default'    => 3,
            'match'      => MATCH_INTEGER,
        ],
        'ca_path' => [
            'must'      => 0,
            'editable'  => 1,
            'default'   => '',
            'match'     => MATCH_STRING_PATH,
        ],
        'timeout' => [
            'must'      => 1,
            'editable'  => 1,
            'default'   => 5,
            'match'     => MATCH_INTEGER,
        ],
    ];

    /**
     * Basic initialization happens here
     *
     * @param string $backendId
     */
    public function __construct($backendId)
    {
        $this->backendId = $backendId;

        $this->baseUrl = cfg('backend_' . $backendId, 'base_url');

        $httpContext = [
            'method'     => 'GET',
            'user_agent' => 'NagVis BI Backend',
            'timeout'    => cfg('backend_' . $backendId, 'timeout'),
        ];

        $sslContext = [];

        if (cfg('backend_' . $backendId, 'verify_peer')) {
            $sslContext = [
                'verify_peer'      => true,
                'verify_peer_name' => false,
                'verify_depth'     => cfg('backend_' . $backendId, 'verify_depth'),
            ];
            $ca_path = cfg('backend_' . $backendId, 'ca_path');
            if ($ca_path) {
                $sslContext['cafile'] = $ca_path;
            }
        } else {
            $sslContext = [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ];
        }

        // Always set the HTTP basic auth header
        $username = cfg('backend_' . $backendId, 'auth_user');
        $secret = $this->getSecret();
        if ($username && $secret) {
            $authCred = base64_encode($username . ':' . $secret);
            $httpContext['header'] = 'Authorization: Basic ' . $authCred . "\r\n";
        }

        $this->context = stream_context_create([
            'http' => $httpContext,
            'ssl'  => $sslContext,
        ]);
    }

    /**************************************************************************
     * HELPERS
     *************************************************************************/

    /**
     * @return string|null
     */
    private function getSecret()
    {
        $secret_file_path = cfg('backend_' . $this->backendId, 'auth_secret_file');
        if ($secret_file_path) {
            return trim(file_get_contents($secret_file_path));
        } else {
            return cfg('backend_' . $this->backendId, 'auth_secret');
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function aggrUrl($name)
    {
        $html_cgi = cfg('backend_' . $this->backendId, 'htmlcgi');
        return $html_cgi . '/view.py?view_name=aggr_single&aggr_name=' . $name . '&po_aggr_expand=1';
    }

    /**
     * The real data fetching method. This performs the HTTP GET and cares
     * about parsing, validating and processing the response.
     *
     * @param string $params
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendInvalidResponse
     */
    private function getUrl($params)
    {
        $url = $this->baseUrl . $params . '&output_format=json';
        $username = cfg('backend_' . $this->backendId, 'auth_user');
        $secret   = $this->getSecret();
        if ($username && $secret) {
            $url .= '&_username=' . $username . '&_secret=' . $secret;
        }

        // Is there some cache to use? The cache is not persisted. It is available
        // until the request has finished.
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }

        //DEBUG:
        //$fh = fopen('/tmp/bi', 'a');
        //fwrite($fh, $url."\n\n");
        //fclose($fh);

        $s = @file_get_contents($url, false, $this->context);
        if ($s === false) {
            throw new BackendConnectionProblem(l('Unable to fetch data from URL [U]: [M]',
                ['U' => $url, 'M' => json_encode(error_get_last())]));
        }

        //DEBUG:
        //$fh = fopen('/tmp/bi', 'a');
        //fwrite($fh, $s."\n\n");
        //fclose($fh);

        if ($s[0] != '[') {
            throw new BackendInvalidResponse(l('Invalid response ([BACKENDID]): [RESPONSE]',
                [
                    'BACKENDID' => $this->backendId,
                    'RESPONSE' => htmlentities($s, ENT_COMPAT, 'UTF-8')
                ]));
        }

        // Decode the json response
        // json_decode returns null on syntax problems
        $parsed = json_decode(iso8859_1_to_utf8($s), true);
        if (!is_array($parsed)) {
            throw new BackendInvalidResponse(l('Invalid response ([BACKENDID]): [RESPONSE]',
                [
                    'BACKENDID' => $this->backendId,
                    'RESPONSE' => htmlentities($s, ENT_COMPAT, 'UTF-8')
                ]));
        }

        // transform structure of the response to have an array of associative arrays
        $obj = [];
        $head = array_shift($parsed); // extract header spec
        for ($i = 0; $i < count($parsed); $i++) {
            $obj[] = array_combine($head, $parsed[$i]);
        }

        // Cache the valid response
        $this->cache[$url] = $obj;

        return $obj;
    }

    /**
     * Returns the identifiers and names of all business processes
     *
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendInvalidResponse
     */
    private function getAggregationNames()
    {
        $aggregations = $this->getUrl('view.py?view_name=aggr_all_api&expansion_level=0');
        $names = [];
        foreach ($aggregations as $aggr) {
            $names[$aggr['aggr_name']] = $aggr['aggr_name'];
        }
        ksort($names);
        return $names;
    }

    /**
     * Transform BI state numbers to NagVis state names and then to
     * NagVis internal state numbers
     *
     * @param int $state
     * @return int
     */
    private function getAggrState($state)
    {
        return state_num(GlobalBackendmkbi::$bi_aggr_states[(int)$state]);
    }

    /**
     * @param array $aggr
     * @return array
     * @throws BackendException
     */
    private function getAggrElements($aggr)
    {
        if (is_array($aggr['aggr_treestate'])) {
            return $aggr['aggr_treestate']["nodes"];
        } else {
            return $this->getAggrElementsFromString($aggr["aggr_treestate"]);
        }
    }

    /**
     * Be compatible to Check_MK <1.2.9
     *
     * @param string $aggr_treestate
     * @return array
     * @throws BackendException
     */
    private function getAggrElementsFromString($aggr_treestate)
    {
        // remove leading/trailing newlines
        $raw_states = trim($aggr_treestate);
        // replace multiple newlines with singe ones
        $raw_states = preg_replace("/[\n]+/", "\n", $raw_states);
        $parts = explode("\n", $raw_states);
        array_shift($parts); // Remove first entry, the summary state
        array_shift($parts);
        $pairs = array_chunk($parts, 2);

        $elements = [];
        foreach ($pairs as $pair) {
            list($short_state, $title) = $pair;

            if (!isset(GlobalBackendmkbi::$bi_short_states[$short_state])) {
                throw new BackendException(l('Invalid state: "[S]"',
                    ['S' => $short_state]));
            }
            $bi_state = GlobalBackendmkbi::$bi_short_states[$short_state];

            $element = [
                "title"             => $title,
                "state"             => $bi_state,
                // unknown infos in old Check_MK versions:
                "assumed"           => false,
                "acknowledged"      => false,
                "in_downtime"       => false,
                "in_service_period" => false,
                // Create some kind of default output when aggregation does
                // not provide any detail output
                "output"            => l("BI-State is: [S]",
                    ["S" => GlobalBackendmkbi::$bi_aggr_states[$bi_state]]),
            ];
            $elements[] = $element;
        }

        return $elements;
    }

    /**
     * @param array $aggr
     * @return array|array[]
     * @throws BackendException
     */
    private function getAggrCounts($aggr)
    {
        $c = [
            PENDING => [
                'normal'   => 0,
                'downtime' => 0,
            ],
            OK => [
                'normal'   => 0,
                'stale'    => 0,
                'downtime' => 0,
            ],
            WARNING => [
                'normal'   => 0,
                'stale'    => 0,
                'ack'      => 0,
                'downtime' => 0,
            ],
            CRITICAL => [
                'normal'   => 0,
                'stale'    => 0,
                'ack'      => 0,
                'downtime' => 0,
            ],
            UNKNOWN => [
                'normal'   => 0,
                'stale'    => 0,
                'ack'      => 0,
                'downtime' => 0,
            ],
        ];

        // Add the single component state counts
        $elements = $this->getAggrElements($aggr);
        foreach ($elements as $element) {
            $state = $this->getAggrState($element["state"]);
            if ($element["in_downtime"]) {
                $c[$state]['downtime']++;
            }
            elseif ($element["acknowledged"]) {
                $c[$state]['ack']++;
            } else {
                $c[$state]['normal']++;
            }
        }

        return $c;
    }

    /**************************************************************************
     * IMPLEMENTING THE NAGVIS BACKEND API BELOW
     *************************************************************************/

    /**
     * Used in WUI forms to populate the object lists when adding or modifying
     * objects in WUI.
     *
     * @param string $type
     * @param string $name1Pattern
     * @param string $name2Pattern
     * @return array
     */
    public function getObjects($type, $name1Pattern = '', $name2Pattern = '')
    {
        if ($type !== 'aggr') {
            throw new BackendException(l('This backend only supports "Aggregation" objects.'));
        }

        $result = [];
        foreach ($this->getAggregationNames() as $id => $name) {
            $result[] = ['name1' => $id, 'name2' => $name];
        }
        return $result;
    }

    /**
     * @param array[] $aggregations
     * @param string $key
     * @return null
     */
    private function matchAggregation($aggregations, $key)
    {
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
     *
     * @param array $objects
     * @param $options
     * @param $filters
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendException
     * @throws BackendInvalidResponse
     */
    public function getAggrStateCounts($objects, $options, $filters)
    {
        $aggregations = $this->getUrl('view.py?view_name=aggr_all_api&expansion_level=1');

        $ret = [];
        foreach ($objects as $key => $OBJS) {
            $aggr = $this->matchAggregation($aggregations, $key);
            if ($aggr === null) {
                continue;
            } // did not find this aggregation
            $obj_url = $OBJS[0]->getUrl();

            $is_acknowledged = isset($aggr['aggr_acknowledged']) && $aggr['aggr_acknowledged'] == "1";
            $is_in_downtime = isset($aggr['aggr_in_downtime']) && $aggr['aggr_in_downtime'] == "1";

            $ret[$key] = [
                'details' => [
                    ALIAS => $aggr['aggr_name'],
                    // This forces the aggregation state to be the summary state of the object
                    STATE    => $this->getAggrState($aggr['aggr_state_num']),
                    OUTPUT   => "xxxxxxxxxxxxxx",
                    ACK      => $is_acknowledged == "1" ? 1 : 0,
                    DOWNTIME => $is_in_downtime == "1" ? 1 : 0,
                ],
                'attrs' => [
                    // Forces the URL to point to the BI aggregate
                    'url' => $obj_url ?: $this->aggrUrl($key),
                ],
                'counts'  => $this->getAggrCounts($aggr),
            ];

            // Add optional outputs which replaces the NagVis summary_output
            if (isset($aggr['aggr_output']) && $aggr['aggr_output'] != '') {
                $ret[$key]['output'] = $aggr['aggr_output'];
            }
        }

        return $ret;
    }

    /**
     * Returns the state with detailed information of a list of services. Using
     * the given objects and filters.
     *
     * @param array $objects
     * @param $options
     * @param $filters
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendException
     * @throws BackendInvalidResponse
     */
    public function getServiceState($objects, $options, $filters)
    {
        $aggregations = $this->getUrl('view.py?view_name=aggr_all_api&expansion_level=1');

        $ret = [];
        foreach ($objects as $key => $OBJS) {
            $aggr = $this->matchAggregation($aggregations, $key);
            if ($aggr === null) {
                continue;
            } // did not find this aggregation

            // Add the services
            // Add the single component state counts
            $elements = $this->getAggrElements($aggr);
            foreach ($elements as $element) {
                $child = [
                    $this->getAggrState($element["state"]),  // state
                    $element["output"],            // output
                    $element["acknowledged"],      // acknowledged
                    $element["in_downtime"],       // in downtime
                    1,  // state type
                    1, // current attempt
                    1, // max check attempts
                    null,  // last check
                    null,  // next check
                    null, // last hard state change
                    null, // last state change
                    '', // perfdata
                    $element["title"],  // display name
                    $element["title"],  // alias
                    '',  // address
                    '',  // notes
                    '', // check command
                    null,
                    null, // dt author
                    null, // dt data
                    null, // dt start
                    null, // dt end
                    0, // staleness
                    $element["title"] // descr
                ];

                $ret[$key][] = $child;
            }
        }

        return $ret;
    }

    /**
     * PUBLIC Method getValidConfig
     * Returns the valid config for this backend
     *
     * @return array
     */
    public static function getValidConfig()
    {
        return self::$validConfig;
    }

    /***************************************************************************
     * Not implemented methods
     **************************************************************************/

    /**
     * @param $objects
     * @param $options
     * @param $filters
     * @return array
     */
    public function getHostState($objects, $options, $filters)
    {
        return [];
    }

    /**
     * @param $objects
     * @param $options
     * @param $filters
     * @return array
     */
    public function getHostMemberCounts($objects, $options, $filters)
    {
        return [];
    }

    /**
     * @param $objects
     * @param $options
     * @param $filters
     * @return array
     */
    public function getHostgroupStateCounts($objects, $options, $filters)
    {
        return [];
    }

    /**
     * @param $objects
     * @param $options
     * @param $filters
     * @return void
     */
    public function getServicegroupStateCounts($objects, $options, $filters)
    {

    }

    /**
     * @return array
     */
    public function getHostNamesWithNoParent()
    {
        return [];
    }

    /**
     * @param $hostName
     * @return array
     */
    public function getDirectChildNamesByHostName($hostName)
    {
        return [];
    }

    /**
     * @param $hostName
     * @return array
     */
    public function getDirectParentNamesByHostName($hostName)
    {
        return [];
    }

    /**
     * @param $hostName
     * @return array
     */
    public function getDirectChildDependenciesNamesByHostName($hostName)
    {
        return [];
    }
}

if (!function_exists('l')) {
    require_once('GlobalBackendInterface.php');
    require_once('CoreExceptions.php');

    /**
     * @param string $s
     * @param array $a
     * @return string
     */
    function l($s, $a = [])
    {
        return $s . ' ' . json_encode($a);
    }

    /**
     * @param $sec
     * @param string $opt
     * @return string|void
     */
    function cfg($sec, $opt)
    {
        if ($opt == 'base_url') {
            return 'http://127.0.0.1/event/check_mk/';
        }
        if ($opt == 'auth_user') {
            return 'bi-user';
        }
        if ($opt == 'auth_secret') {
            return 'MATKBYXNV@YXLHSEJYND';
        }
    }

    $O = new GlobalBackendmkbi('bi');
    print_r($O->getAggregationNames());
}
