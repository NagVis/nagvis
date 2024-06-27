<?php
/*****************************************************************************
 *
 * GlobalBackendnagiosbp.php - backend class for connecting NagVis directly
 *                             to NagiosBP using the NagiosBP JSON webservice.
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

/**
 * @author Lars Michelsen <lm@larsmichelsen.com>
 */

/**
 * Vorschlag Nagios-BP:
 * - Nur angefragte Infos ausgeben:
 *   - Nicht immer Prio Definitionen mitschicken
 *   - Nur bestimmte Attribute von Business Prozessen holen
 * - Apache Error Handler mit JSON Code als Ausgabe (z.B. 500er wenn Nagios nicht läuft)
 * - Können Services in Components auch den Status Output anzeigen?
 */

if (!function_exists('l')) {
    require_once('GlobalBackendInterface.php');
    require_once('../defines/matches.php');
}

class GlobalBackendnagiosbp implements GlobalBackendInterface
{
    /** @var string */
    private $backendId = '';

    /** @var string */
    private $baseUrl   = '';

    /** @var false|resource|string */
    private $context   = '';

    /** @var array */
    private $cache     = [];

    /** @var array These are the backend local configuration options */
    private static $validConfig = [
        'base_url' => [
            'must'     => 1,
            'editable' => 1,
            'default'  => 'http://localhost/nagios/cgi-bin/nagios-bp.cgi',
            'match'    => MATCH_STRING_URL,
        ],
        'auth_user' => [
            'must'     => 0,
            'editable' => 1,
            'default'  => '',
            'match'    => MATCH_STRING,
        ],
        'auth_pass' => [
            'must'     => 0,
            'editable' => 1,
            'default'  => '',
            'match'    => MATCH_STRING,
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
                'user_agent' => 'NagVis NagiosBP Backend',
                'timeout'    => 5,
        ];

        $username = cfg('backend_' . $backendId, 'auth_user');
        $password = cfg('backend_' . $backendId, 'auth_pass');
        if ($username && $password) {
            $authCred = base64_encode($username . ':' . $password);
            $httpContext['header'] = 'Authorization: Basic ' . $authCred . "\r\n";
        }

        $this->context = stream_context_create(['http' => $httpContext]);
    }

    /**************************************************************************
     * HELPERS
     *************************************************************************/

    /**
     * @param string $key
     * @return string
     */
    private function bpUrl($key)
    {
        return $this->baseUrl . '?tree=' . $key;
    }

    /**
     * The real data fetching method. This performs the HTTP GET and cares
     * about parsing, validating and processing the response.
     *
     * @param string $params
     * @return mixed
     * @throws BackendConnectionProblem
     * @throws BackendInvalidResponse
     */
    private function getUrl($params)
    {
        $url = $this->baseUrl . '?outformat=json&' . $params;

        // Is there some cache to use? The cache is not persisted. It is available
        // until the request has finished.
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }

        //DEBUG:
        //$fh = fopen('/tmp/bp', 'a');
        //fwrite($fh, $url."\n\n");
        //fclose($fh);

        $s = @file_get_contents($url, false, $this->context);
        if ($s === false) {
            throw new BackendConnectionProblem(l('Unable to fetch data from URL [U]: [M]',
                ['U' => $url, 'M' => json_encode(error_get_last())]));
        }

        //DEBUG:
        //$fh = fopen('/tmp/bp', 'a');
        //fwrite($fh, $s."\n\n");
        //fclose($fh);

        // Validate
        // FIXME: HTTP 200
        // FIXME: Content-Type: ...json...

        // Decode the json response
        // json_decode returns null on syntax problems
        $obj = json_decode(iso8859_1_to_utf8($s), true);
        if ($obj === null || !isset($obj['json_created'])) {
            throw new BackendInvalidResponse(l('The response has an invalid format in backend [BACKENDID].',
                ['BACKENDID' => $this->backendId]));
        }

        // Check age of 'json_created'
        $created = strptime($obj['json_created'], '%Y-%m-%d %H:%M:%S');
        if ($created < strtotime('-60 seconds')) {
            throw new BackendInvalidResponse(l('Response data is too old (json_created: [C])',
                ['C' => $obj['json_created']]));
        }

        // Cache the valid response
        $this->cache[$url] = $obj;

        return $obj;
    }

    /**
     * Returns the identifiers of all business processes
     *
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendInvalidResponse
     */
    private function getProcessIDs()
    {
        $o = $this->getUrl('');
        return array_keys($o['business_processes']);
    }

    /**
     * Returns the identifiers and names of all business processes
     *
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendInvalidResponse
     */
    private function getProcessNames()
    {
        $o = $this->getUrl('');
        $names = [];
        foreach ($o['business_processes'] as $key => $bp) {
            $names[$key] = $bp['display_name'];
        }
        ksort($names);
        return $names;
    }

    /**
     * @param string|null $state
     * @return int|mixed
     */
    private function getBPState($state)
    {
        if ($state == null) {
            return UNKNOWN;
        }
        return state_num($state);
    }

    /**
     * @param array $bp
     * @return array|array[]
     * @throws BackendException
     */
    private function getBPCounts($bp)
    {
        $c = [
            PENDING => [
                'normal'   => 0,
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
        foreach ($bp['components'] as $component) {
            $s = $this->getBPState($component['hardstate']);
            if (!isset($c[$s])) {
                throw new BackendException(l('Invalid state: "[S]"',
                    ['S' => $s]));
            }
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
     *
     * @param string $type
     * @param string $name1Pattern
     * @param string $name2Pattern
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendInvalidResponse
     */
    public function getObjects($type, $name1Pattern = '', $name2Pattern = '')
    {
        if ($type !== 'servicegroup') {
            return [];
        }

        $result = [];
        foreach ($this->getProcessNames() as $id => $name) {
            $result[] = ['name1' => $id, 'name2' => $name];
        }
        return $result;
    }

    /**
     * Returns the service state counts for a list of servicegroups. Using
     * the given objects and filters.
     *
     * This backend transforms all business processes to servicegroups. Each
     * business process component is used as a service.
     *
     * @param array<array<NagVisObject>> $objects
     * @param int $options
     * @param array $filters
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendException
     * @throws BackendInvalidResponse
     */
    public function getServicegroupStateCounts($objects, $options, $filters)
    {
        $o = $this->getUrl('');
        $bps = $o['business_processes'];

        $ret = [];
        foreach ($objects as $key => $OBJS) {
            if (!isset($bps[$key])) {
                continue;
            }
            $bp = $bps[$key];

            $ret[$key] = [
                'details' => [
                    ALIAS => $bp['display_name'],
                    // This forces the BP state to be the summary state of the BP object
                    STATE => $this->getBPState($bp['hardstate']),
                ],
                'counts'  => $this->getBPCounts($bp),
            ];

            // Add optional outputs which replaces the NagVis summary_output
            if (isset($bp['external_info'])) {
                $ret[$key]['output'] = $bp['external_info'];
            }

            // Forces the URL to point to nagios-bp if the current url does not point to a map
            if (!str_contains($OBJS[0]->getUrl(), 'show=')) {
                $ret[$key]['attrs'] = [
                    'url' => $this->bpUrl($key),
                ];
            }
        }

        return $ret;
    }

    /**
     * Returns the state with detailed information of a list of services. Using
     * the given objects and filters.
     *
     * @param array $objects
     * @param int $options
     * @param array $filters
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendInvalidResponse
     */
    public function getServiceState($objects, $options, $filters)
    {
        $o = $this->getUrl('');
        $bps = $o['business_processes'];

        $ret = [];
        foreach ($objects as $key => $OBJS) {
            if (!isset($bps[$key])) {
                continue;
            }
            $bp = $bps[$key];

            // Initialize the service list
            // Add the aggregation summary state
            if (!isset($ret[$key])) {
                $ret[$key] = [
                    #Array(
                    #    'host_name'           => '_BP_',
                    #    'service_description' => 'Summary',
                    #    'state'               => $this->getBPState($bp['hardstate']),
                    #    'output'              => '',
                    #),
                ];

                #if (isset($bp['external_info']))
                #    $ret[$key][0]['output'] = $bp['external_info'];
            }

            // Add the services
            // This can be real services or e.g. other business processes
            foreach ($bp['components'] as $comp) {
                if (isset($comp['service'])) {
                    // Service
                    //$ret[$key][] = Array(
                    //    STATE => $this->getBPState($comp['hardstate']),
                    //    OUTPUT => '',
                    //    ALIAS => $comp['host'],
                    //    DESCRIPTION => $comp['service'],
                    //);
                    $ret[$key][] = [
                        $this->getBPState($comp['hardstate']),
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
                        $comp['service'],  // display name
                        $comp['service'],  // alias
                        '',  // address
                        '',  // notes
                        '', // check command
                        null,
                        null, // dt author
                        null, // dt data
                        null, // dt start
                        null, // dt end
                        0, // staleness
                        $comp['service'] // descr
                    ];
                } else {
                    // BP
                    $childBP = [
                        $this->getBPState($comp['hardstate']),
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
                        $comp['display_name'],  // display name
                        $comp['display_name'],  // alias
                        '',  // address
                        '',  // notes
                        '', // check command
                        null,
                        null, // dt author
                        null, // dt data
                        null, // dt start
                        null, // dt end
                        0, // staleness
                        $comp['display_name'] // descr
                    ];

                    if (isset($bps[$comp['subprocess']]['external_info'])) {
                        $childBP[OUTPUT] = $bps[$comp['subprocess']]['external_info'];
                    }

                    $ret[$key][] = $childBP;
                }
            }
        }

        return $ret;
    }

    /**
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
     * @param array $objects
     * @param int $options
     * @param array $filters
     * @return array
     */
    public function getHostState($objects, $options, $filters)
    {
        return [];
    }

    /**
     * @param array $objects
     * @param int $options
     * @param array $filters
     * @return array
     */
    public function getHostMemberCounts($objects, $options, $filters)
    {
        return [];
    }

    /**
     * @param array $objects
     * @param int $options
     * @param array $filters
     * @return array
     */
    public function getHostgroupStateCounts($objects, $options, $filters)
    {
        return [];
    }

    /**
     * @return array
     */
    public function getHostNamesWithNoParent()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getDirectChildNamesByHostName($hostName)
    {
        return [];
    }

    /**
     * @return array
     */
    public function getDirectParentNamesByHostName($hostName)
    {
        return [];
    }

    /**
     * @param string $hostName
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

    function l($s, $a)
    {
        return $s . ' ' . json_encode($a);
    }

    function cfg($sec, $opt)
    {
        if ($opt == 'base_url') {
            return 'http://127.0.0.1/nagiosbp/cgi-bin/nagios-bp.cgi';
        }
        if ($opt == 'auth_user') {
            return 'omdadmin';
        }
        if ($opt == 'auth_pass') {
            return 'omd';
        }
    }

    $O = new GlobalBackendnagiosbp([], 't');
    print_r($O->getProcessIDs());
    print_r($O->getProcessNames());
}
