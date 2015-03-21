<?php
/*****************************************************************************
 *
 * GlobalBackendnagiosbp.php - backend class for connecting NagVis directly
 *                             to NagiosBP using the NagiosBP JSON webservice.
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

if(!function_exists('l')) {
    require_once('GlobalBackendInterface.php');
    require_once('../defines/matches.php');
}

class GlobalBackendnagiosbp implements GlobalBackendInterface {
    private $backendId = '';
    private $baseUrl   = '';
    private $context   = '';
    private $cache     = Array();

    // These are the backend local configuration options
    private static $validConfig = Array(
        'base_url' => Array(
            'must'     => 1,
            'editable' => 1,
            'default'  => 'http://localhost/nagios/cgi-bin/nagios-bp.cgi',
            'match'    => MATCH_STRING_URL,
        ),
        'auth_user' => Array(
            'must'     => 0,
            'editable' => 1,
            'default'  => '',
            'match'    => MATCH_STRING,
        ),
        'auth_pass' => Array(
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
                'user_agent' => 'NagVis NagiosBP Backend',
                'timeout'    => 5,
        );

        $username = cfg('backend_'.$backendId, 'auth_user');
        $password = cfg('backend_'.$backendId, 'auth_pass');
        if($username && $password) {
            $authCred = base64_encode($username.':'.$password);
            $httpContext['header'] = 'Authorization: Basic '.$authCred."\r\n";
        }

        $this->context = stream_context_create(array('http' => $httpContext));
    }

    /**************************************************************************
     * HELPERS
     *************************************************************************/

    private function bpUrl($key) {
        return $this->baseUrl . '?tree=' . $key;
    }

    /**
     * The real data fetching method. This performs the HTTP GET and cares
     * about parsing, validating and processing the response.
     */
    private function getUrl($params) {
        $url = $this->baseUrl . '?outformat=json&' . $params;

        // Is there some cache to use? The cache is not persisted. It is available
        // until the request has finished.
        if(isset($this->cache[$url]))
            return $this->cache[$url];

        //DEBUG:
        //$fh = fopen('/tmp/bp', 'a');
        //fwrite($fh, $url."\n\n");
        //fclose($fh);

        $s = @file_get_contents($url, false, $this->context);
        if($s === false)
            throw new BackendConnectionProblem(l('Unable to fetch data from URL [U]: [M]',
                                                Array('U' => $url, 'M' => json_encode(error_get_last()))));

        //DEBUG:
        //$fh = fopen('/tmp/bp', 'a');
        //fwrite($fh, $s."\n\n");
        //fclose($fh);

        // Validate
        // FIXME: HTTP 200
        // FIXME: Content-Type: ...json...

        // Decode the json response
        // json_decode returns null on syntax problems
        $obj = json_decode(utf8_encode($s), true);
        if($obj === null || !isset($obj['json_created']))
            throw new BackendInvalidResponse(l('The response has an invalid format in backend [BACKENDID].',
                                                      Array('BACKENDID' => $this->backendId)));

        // Check age of 'json_created'
        $created = strptime($obj['json_created'], '%Y-%m-%d %H:%M:%S');
        if($created < strtotime('-60 seconds'))
            throw new BackendInvalidResponse(l('Response data is too old (json_created: [C])', 
                                                          Array('C' => $obj['json_created'])));

        // Cache the valid response
        $this->cache[$url] = $obj;

        return $obj;
    }

    /**
     * Returns the identifiers of all business processes
     */
    private function getProcessIDs() {
        $o = $this->getUrl('');
        return array_keys($o['business_processes']);
    }

    /**
     * Returns the identifiers and names of all business processes
     */
    private function getProcessNames() {
        $o = $this->getUrl('');
        $names = Array();
        foreach($o['business_processes'] AS $key => $bp) {
            $names[$key] = $bp['display_name'];
        }
        ksort($names);
        return $names;
    }

    private function getBPState($state) {
        if($state == null)
            return UNKNOWN;
        return state_num($state);
    }

    private function getBPCounts($bp) {
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
        foreach($bp['components'] AS $component) {
            $s = $this->getBPState($component['hardstate']);
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
        if($type !== 'servicegroup')
            return Array();

        $result = Array();
        foreach($this->getProcessNames() AS $id => $name) {
            $result[] = Array('name1' => $id, 'name2' => $name);
        }
        return $result;
    }

    /**
     * Returns the service state counts for a list of servicegroups. Using
     * the given objects and filters.
     *
     * This backend transforms all business processes to servicegroups. Each
     * business process component is used as a service.
     */
    public function getServicegroupStateCounts($objects, $options, $filters) {
        $o = $this->getUrl('');
        $bps = $o['business_processes'];

        $ret = Array();
        foreach($objects AS $key => $OBJS) {
            if(!isset($bps[$key]))
                continue;
            $bp = $bps[$key];

            $ret[$key] = Array(
                'details' => Array(
                    ALIAS => $bp['display_name'],
                    // This forces the BP state to be the summary state of the BP object
                    STATE => $this->getBPState($bp['hardstate']),
                ),
                'counts'  => $this->getBPCounts($bp),
            );

            // Add optional outputs which replaces the NagVis summary_output
            if(isset($bp['external_info']))
                $ret[$key]['output'] = $bp['external_info'];

            // Forces the URL to point to nagios-bp if the current url does not point to a map
            if(strpos($OBJS[0]->getUrl(), 'show=') === false)
                $ret[$key]['attrs'] = array(
                    'url' => $this->bpUrl($key),
                );
        }

        return $ret;
    }

    /**
     * Returns the state with detailed information of a list of services. Using
     * the given objects and filters.
     */
    public function getServiceState($objects, $options, $filters) {
        $o = $this->getUrl('');
        $bps = $o['business_processes'];

        $ret = Array();
        foreach($objects AS $key => $OBJS) {
            if(!isset($bps[$key]))
                continue;
            $bp = $bps[$key];

            // Initialize the service list
            // Add the aggregation summary state
            if(!isset($ret[$key])) {
                $ret[$key] = Array(
                    #Array(
                    #    'host_name'           => '_BP_',
                    #    'service_description' => 'Summary',
                    #    'state'               => $this->getBPState($bp['hardstate']),
                    #    'output'              => '',
                    #),
                );

                #if(isset($bp['external_info']))
                #    $ret[$key][0]['output'] = $bp['external_info'];
            }

            // Add the services
            // This can be real services or e.g. other business processes
            foreach($bp['components'] AS $comp) {
                if(isset($comp['service'])) {
                    // Service
                    //$ret[$key][] = Array(
                    //    STATE => $this->getBPState($comp['hardstate']),
                    //    OUTPUT => '',
                    //    ALIAS => $comp['host'],
                    //    DESCRIPTION => $comp['service'],
                    //);
                    $ret[$key][] = array(
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
                    );
                } else {
                    // BP
                    $childBP = array(
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
                    );

                    if(isset($bps[$comp['subprocess']]['external_info']))
                        $childBP[OUTPUT] = $bps[$comp['subprocess']]['external_info'];

                    $ret[$key][] = $childBP;
                }
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

    function l($s, $a) {
        return $s . ' ' . json_encode($a);
    }

    function cfg($sec, $opt) {
        if($opt == 'base_url')
            return 'http://127.0.0.1/nagiosbp/cgi-bin/nagios-bp.cgi';
        if($opt == 'auth_user')
            return 'omdadmin';
        if($opt == 'auth_pass')
            return 'omd';
    }

    $O = new GlobalBackendnagiosbp(Array(), 't');
    print_r($O->getProcessIDs());
    print_r($O->getProcessNames());
}

?>
