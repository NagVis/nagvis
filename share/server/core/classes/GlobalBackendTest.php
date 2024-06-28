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
 * @author  Lars Michelsen  <lm@larsmichelsen.com>
 */
class GlobalBackendTest implements GlobalBackendInterface
{
    /** @var string */
    private $backendId = '';

    /** @var int */
    private $now;

    /** @var array[] These are the backend local configuration options */
    private static $validConfig = [
        'generate_mapcfg' => [
            'must'       => 1,
            'editable'   => 1,
            'default'    => 0,
            'field_type' => 'boolean',
            'match'      => MATCH_BOOLEAN
        ]
    ];

    /** @var array */
    private $parents = [];

    /** @var array */
    private $childs  = [];

    /** @var array[] */
    private $obj = [
        'host' => [],
        'service' => [],
        'hostgroup' => [],
        'servicegroup' => [],
    ];

    /** @var array[] */
    private $hostStates = [
        UP          => ['normal' => 0, 'stale' => 0, 'downtime' => 0],
        DOWN        => ['normal' => 0, 'stale' => 0, 'ack' => 0, 'downtime' => 0],
        UNREACHABLE => ['normal' => 0, 'stale' => 0, 'ack' => 0, 'downtime' => 0],
        UNCHECKED   => ['normal' => 0, 'stale' => 0, 'downtime' => 0],
    ];

    /** @var array[] */
    private $serviceStates = [
        OK          => ['normal' => 0, 'stale' => 0, 'downtime' => 0],
        WARNING     => ['normal' => 0, 'stale' => 0, 'ack' => 0, 'downtime' => 0],
        CRITICAL    => ['normal' => 0, 'stale' => 0, 'ack' => 0, 'downtime' => 0],
        UNKNOWN     => ['normal' => 0, 'stale' => 0, 'ack' => 0, 'downtime' => 0],
        PENDING     => ['normal' => 0, 'stale' => 0, 'downtime' => 0],
    ];

    /** @var array[] */
    private $canBeSoft = [
        UP          => ['hard'],
        DOWN        => ['hard', 'soft'],
        UNREACHABLE => ['hard', 'soft'],
        UNCHECKED   => ['hard'],
        PENDING     => ['hard'],
        OK          => ['hard'],
        WARNING     => ['hard', 'soft'],
        CRITICAL    => ['hard', 'soft'],
        UNKNOWN     => ['hard'],
    ];

    /**
     * @param string $backendId
     */
    public function __construct($backendId)
    {
        $this->backendId = $backendId;
        $this->now = time();
        $this->genObj();

        if (
            cfg('backend_' . $backendId, 'generate_mapcfg')
            && (isset($_POST['new']) || !file_exists(cfg('paths', 'mapcfg') . 'test-gen.cfg'))
        ) {
            $this->genMapCfg(cfg('paths', 'mapcfg') . 'test-gen.cfg');
        }

        return true;
    }

    /**
     * @param string $name
     * @param string $state
     * @param string $stateType
     * @param string $substate
     * @return array
     */
    private function host($name, $state, $stateType = 'hard', $substate = 'normal')
    {
        $ack = $substate == 'ack';
        $in_downtime = $substate == 'downtime';
        if ($in_downtime) {
            $downtime_author = 'Kunibert';
            $downtime_data   = 'xyz';
            $downtime_start  = $this->now - 60;
            $downtime_end    = $this->now + 60;
        } else {
            $downtime_author = null;
            $downtime_data   = null;
            $downtime_start  = null;
            $downtime_end    = null;
        }

        return [
            $state,
            'Host with state ' . $state, // output
            $ack,
            $in_downtime,
            0, // staleness
            $stateType == 'hard' ? 1 : 0,
            3, // current attempt
            3, // max attempts
            $this->now - 60, // last check
            $this->now + 60, // next check
            $this->now - 60, // last hard state change
            $this->now - 60, // last state change
            '', // perfdata
            'Display Name host-' . $name,
            'Alias host-' . $name,
            'localhost', // address
            '', // notes
            null, // check command
            null, // custom vars
            $downtime_author,
            $downtime_data,
            $downtime_start,
            $downtime_end,
        ];
    }

    /**
     * @param string $name1
     * @param string $name2
     * @param string $state
     * @param string $stateType
     * @param string $substate
     * @param string|null $output
     * @param string $perfdata
     * @return array
     */
    private function service(
        $name1,
        $name2,
        $state,
        $stateType = 'hard',
        $substate = 'normal',
        $output = null,
        $perfdata = ''
    ) {
        $ack = $substate == 'ack';
        $in_downtime = $substate == 'downtime';
        if ($in_downtime) {
            $downtime_author = 'Kunibert';
            $downtime_data   = 'xyz';
            $downtime_start  = $this->now - 60;
            $downtime_end    = $this->now + 60;
        } else {
            $downtime_author = null;
            $downtime_data   = null;
            $downtime_start  = null;
            $downtime_end    = null;
        }
        if ($output === null) {
            $output = 'output ' . $name2;
        } else {
            $output = 'empty output';
        }

        return [
            $state,
            $output,
            $ack,
            $in_downtime,
            0, // staleness
            $stateType == 'hard' ? 1 : 0,
            3, // current attempt
            3, // max attempts
            $this->now - 60, // last check
            $this->now + 60, // next check
            $this->now - 60, // last hard state change
            $this->now - 60, // last state change
            $perfdata,
            'display name ' . $name2,
            'alias ' . $name2,
            'localhost', // address
            '', // notes
            null, // check command
            null, // custom vars
            $downtime_author,
            $downtime_data,
            $downtime_start,
            $downtime_end,
            $name2
        ];
    }

    /**
     * @param string $name
     * @param string[] $members
     * @return array
     */
    private function hostgroup($name, $members)
    {
        return  [
            'name'    => $name,
            'alias'   => 'Alias ' . $name,
            'members' => $members
        ];
    }

    /**
     * @param string $name
     * @param array[] $members
     * @return array
     */
    private function servicegroup($name, $members)
    {
        return [
            'name'    => $name,
            'alias'   => 'Alias ' . $name,
            'members' => $members
        ];
    }

    /**
     * @return void
     */
    private function genObj()
    {
        /**
         * Generate objects for demo maps
         */
        $this->obj['host']['muc-gw1'] = $this->host('muc-gw1',      UP,   'hard', 'normal');
        $wan = $this->service('muc-gw1', 'Interface WAN', CRITICAL, 'hard', 'normal');
        $wan[PERFDATA] = 'in=98.13%;85;98 out=12.12%;85;98';
        $wan[OUTPUT]   = 'In: 98.13%, Out: 12.12%';
        $this->obj['service']['muc-gw1'] = [$wan];

        $this->obj['host']['muc-srv1'] = $this->host('muc-srv1',     UP,   'hard', 'normal');
        $this->obj['service']['muc-srv1'] = [
            $this->service(
                'muc-srv1',
                'CPU load',
                OK,
                'hard',
                'normal',
                'OK - 15min Load 0.05 at 2 CPUs',
                'load1=0.2;2;5;0; load5=0.24;2;5;0; load15=0.17;2;5;0;'
            ),
            $this->service(
                'muc-srv1',
                'Memory used',
                OK,
                'hard',
                'normal',
                'OK - 0.79 GB RAM+SWAP used (this is 20.9% of RAM size)',
                'ramused=807MB;;;0;3858 swapused=0MB;;;0;1909 memused=807MB;5787;7716;0;5768'
            ),
            $this->service(
                'muc-srv1',
                'Interface eth0',
                OK,
                'hard',
                'normal',
                'OK - [2] (up) 100MBit/s, in: 0.00B/s(0.0%), out: 0.00B/s(0.0%)',
                'in=0;;;0;12500000 inucast=0;;;; innucast=0;;;; indisc=0;;;; inerr=0;0.01;0.1;; out=0;;;0;12500000 outucast=0;;;; outnucast=0;;;; outdisc=0;;;; outerr=0;0.01;0.1;; outqlen=0;;;;'
            ),
            $this->service(
                'muc-srv1',
                'fs_/',
                OK,
                'hard',
                'normal',
                'OK',
                'OK - 73.3% used (84.71 of 115.5 GB), (levels at 80.0/90.0%), trend: +3.84MB / 24 hours',
                '/=86747.1757812MB;94641;106472;0;118302.46875'
            ),
            $this->service(
                'muc-srv1',
                'fs_/home',
                OK,
                'hard',
                'normal',
                'OK',
                'OK - 75.2% used (22.21 of 29.5 GB), (levels at 80.0/90.0%), trend: -27.68KB / 24 hours',
                '/usr=22746.109375MB;24190;27213;0;30237.746094'
            ),
            $this->service(
                'muc-srv1',
                'NTP Time',
                OK,
                'hard',
                'normal',
                'OK',
                'OK - stratum 2, offset 0.02 ms, jitter 0.01 ms'
            ),
        ];

        $this->obj['host']['muc-srv2'] = $this->host('muc-srv2', UP, 'hard', 'normal');
        $this->obj['service']['muc-srv2'] = [
            $this->service(
                'muc-srv2',
                'CPU load',
                OK,
                'hard',
                'normal',
                'OK - 15min Load 1.00 at 2 CPUs',
                'load1=1.6;2;5;0; load5=1.2;2;5;0; load15=1.00;2;5;0;'
            ),
        ];

        $this->obj['host']['muc-printer1'] = $this->host(
            'muc-printer1',
            DOWN,
            'hard',
            'normal'
        );
        $this->obj['service']['muc-printer1'] = [];
        $this->obj['host']['muc-printer2'] = $this->host(
            'muc-printer2',
            DOWN,
            'hard',
            'downtime'
        );
        $this->obj['service']['muc-printer2'] = [];
        $this->obj['hostgroup']['muc'] = $this->hostgroup(
            'muc',
            ['muc-gw1', 'muc-srv1', 'muc-srv2', 'muc-printer1', 'muc-printer2']
        );

        $this->obj['host']['ham-gw1'] = $this->host('ham-gw1', UP,   'hard', 'normal');
        $wan = $this->service('ham-gw1', 'Interface WAN', OK, 'hard', 'normal');
        $wan[PERFDATA] = 'in=77.24%;85;98 out=32.89%;85;98';
        $wan[OUTPUT]   = 'In: 77.24%, Out: 32.89%';
        $this->obj['service']['ham-gw1'] = [$wan];

        $this->obj['host']['ham-srv1'] = $this->host('ham-srv1', UP, 'hard', 'normal');
        $this->obj['service']['ham-srv1'] = [
            $this->service(
                'ham-srv1',
                'CPU load',
                OK,
                'hard',
                'normal',
                'OK - 15min Load 1.00 at 2 CPUs',
                'load1=1.6;2;5;0; load5=1.2;2;5;0; load15=1.00;2;5;0;'
            ),
        ];
        $this->obj['host']['ham-srv2'] = $this->host('ham-srv2', WARNING, 'hard', 'ack');
        $this->obj['service']['ham-srv2'] = [
            $this->service(
                'ham-srv2',
                'CPU load',
                OK,
                'hard',
                'normal',
                'OK - 15min Load 3.00 at 4 CPUs',
                'load1=5.0;10;20;0; load5=4.2;5;8;0; load15=3.0;3.5;4;0;'
            ),
        ];
        $this->obj['host']['ham-printer1'] = $this->host(
            'ham-printer1',
            UP,
            'hard',
            'normal'
        );
        $this->obj['service']['ham-printer1'] = [];
        $this->obj['hostgroup']['ham'] = $this->hostgroup(
            'ham',
            ['ham-gw1', 'ham-srv1', 'ham-srv2', 'ham-printer1']
        );

        $this->obj['host']['cgn-gw1'] = $this->host('cgn-gw1', UP, 'hard', 'normal');
        $wan = $this->service('cgn-gw1', 'Interface WAN', OK, 'hard', 'normal');
        $wan[PERFDATA] = 'in=19.34%;85;98 out=0.89%;85;98';
        $wan[OUTPUT] = 'In: 19.34%, Out: 0.89%';
        $this->obj['service']['cgn-gw1'] = [$wan];

        $this->obj['host']['cgn-srv1'] = $this->host('cgn-srv1', UP,   'hard', 'normal');
        $this->obj['service']['cgn-srv1'] = [];
        $this->obj['host']['cgn-srv2'] = $this->host('cgn-srv2', WARNING, 'hard', 'ack');
        $this->obj['service']['cgn-srv2'] = [];
        $this->obj['host']['cgn-srv3'] = $this->host('cgn-srv3', UP, 'hard', 'normal');
        $this->obj['service']['cgn-srv3'] = [];
        $this->obj['hostgroup']['cgn'] = $this->hostgroup(
            'cgn',
            ['cgn-gw1', 'cgn-srv1', 'cgn-srv2', 'cgn-srv3']
        );

        $this->obj['servicegroup']['load'] = $this->servicegroup(
            'load',
            [['muc-srv1', 'CPU load'], ['ham-srv1', 'CPU load'], ['ham-srv2', 'CPU load']]
        );

        $this->childs = [
            'muc-srv2' => ['muc-srv1', 'muc-gw1'],
            'muc-gw1'  => ['ham-gw1', 'cgn-gw1'],
            'cgn-gw1'  => ['cgn-srv1', 'cgn-srv2', 'cgn-srv3'],
            'ham-gw1'  => ['ham-srv1', 'ham-srv2', 'ham-printer1'],
        ];

        foreach ($this->childs as $parent => $childs) {
            foreach ($childs as $child) {
                if (!isset($this->parents[$child])) {
                    $this->parents[$child] = [$parent];
                } else {
                    $this->parents[$child][] = $parent;
                }
            }
        }

        /**
         * a) HOSTS without services of all states/substates
         */
        foreach ($this->hostStates as $state => $substates) {
            foreach ($this->canBeSoft[$state] as $stateType) {
                foreach (array_keys($substates) as $substate) {
                    $ident    = 'host-' . $state . '-' . $stateType . '-' . $substate;
                    $hostname = $ident;

                    $this->obj['host'][$hostname] = $this->host(
                        $hostname,
                        $state,
                        $stateType,
                        $substate
                    );
                    $this->obj['service'][$hostname] = [];
                    $this->obj['hostgroup']['hostgroup-' . $ident] = $this->hostgroup(
                        'hostgroup-' . $ident, [$hostname]
                    );
                }
            }
        }

        /**
         * b) SERVICES of all states/substates
         */
        foreach ($this->serviceStates as $state => $substates) {
            foreach ($this->canBeSoft[$state] as $stateType) {
                foreach (array_keys($substates) as $substate) {
                    $ident = 'service-' . $state . '-' . $substate;
                    $hostname = 'host-' . $ident;
                    $this->obj['host'][$hostname] = $this->host($hostname, UNCHECKED);
                    $this->obj['service'][$hostname] = [
                        $this->service(
                            $hostname,
                            $ident,
                            $state,
                            $stateType,
                            $substate
                        )
                    ];
                    $this->obj['hostgroup']['hostgroup-' . $ident] = $this->hostgroup(
                        'hostgroup-' . $ident,
                        [$hostname]
                    );
                    $this->obj['servicegroup']['servicegroup-' . $ident] = $this->servicegroup(
                        'servicegroup-' . $ident,
                        [[$hostname, $ident]]
                    );
                }
            }
        }

        /**
         * c) HOSTS of all states with one of all service states
         */
        foreach ($this->hostStates as $hostState => $hostSubstates) {
            foreach ($this->canBeSoft[$hostState] as $hostStateType) {
                foreach (array_keys($hostSubstates) as $hostSubstate) {
                    // Now service stuff
                    foreach ($this->serviceStates as $state => $substates) {
                        foreach ($this->canBeSoft[$state] as $stateType) {
                            foreach (array_keys($substates) as $substate) {
                                $ident = 'host-'
                                    . $hostState
                                    . '-'
                                    . $hostStateType
                                    . '-'
                                    . $hostSubstate
                                    . '-service-'
                                    . $state
                                    . '-'
                                    . $substate;
                                $hostname = $ident;

                                $this->obj['host'][$hostname] = $this->host(
                                    $hostname,
                                    $hostState,
                                    $hostStateType,
                                    $hostSubstate
                                );
                                $this->obj['service'][$hostname] = [
                                    $this->service(
                                        $hostname,
                                        $ident,
                                        $state,
                                        $stateType,
                                        $substate
                                    )
                                ];
                                $this->obj['hostgroup']['hostgroup-' . $ident] = $this->hostgroup(
                                    'hostgroup-' . $ident,
                                    [$hostname]
                                );
                                $this->obj['servicegroup']['servicegroup-' . $ident] = $this->servicegroup(
                                    'servicegroup-' . $ident,
                                    [[$hostname, $ident]]
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getHostNamesProblematic()
    {
        $a = [];
        foreach ($this->obj['host'] as $hostname => $host) {
            if ($host[0] != UP) {
                $a[] = $hostname;
            }
        }

        foreach ($this->obj['service'] as $hostname => $services) {
            foreach ($services as $service_desc => $service) {
                if ($service[0] != OK) {
                    $a[] = $hostname;
                }
            }
        }

        return $a;
    }

    /**
     * @param string $group
     * @return array|mixed
     */
    public function getHostNamesInHostgroup($group)
    {
        if (isset($this->obj['hostgroup'][$group])) {
            return $this->obj['hostgroup'][$group]['members'];
        } else {
            return [];
        }
    }

    /**
     * @return int
     */
    public function getProgramStart()
    {
        return -1;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getAllTypeObjects($type)
    {
        if ($type == 'service') {
            $s = [];
            foreach ($this->obj['service'] as $services) {
                $s = array_merge($s, $services);
            }
            return $s;
        } else {
            return $this->obj[$type];
        }
    }

    /**
     * @param string $path
     * @return void
     */
    public function genMapCfg($path)
    {
        $f = "define global {\n"
            . "  backend_id=test_1\n"
            . "}\n"
                . "\n";
        $x = 0;
        $y = 0;
        foreach (array_keys($this->obj) as $type) {
            foreach ($this->getAllTypeObjects($type) as $obj) {
                $t = $type == 'service' ? 'host' : $type;
                $f .= "define " . $type . " {\n"
                    . "  " . $t . "_name=" . $obj['name'] . "\n";
                if ($type == 'service') {
                    $f .= "  service_description=" . $obj['service_description'] . "\n";
                }
                $f .= "  x=" . $x . "\n"
                    . "  y=" . $y . "\n"
                    . "}\n"
                    . "\n";
                $x += 22;
                if ($x > 1800) {
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
     * The descrutcor closes the socket when some is open
     * at the moment when the class is destroyed. It is
     * important to close the socket in a clean way.
     *
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function __destruct()
    {
    }

    /**
     * Returns the valid config for this backend
     *
     * @return	array
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public static function getValidConfig()
    {
        return [];
    }

    /**
     * Queries the livestatus socket for a list of objects
     *
     * @param string $type Type of object
     * @param string $name1Pattern Name1 of the objecs
     * @param string $name2Pattern Name2 of the objecs
     * @return array Results of the query
     * @throws BackendException
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     * @author  Mathias Kettner <mk@mathias-kettner.de>
     */
    public function getObjects($type, $name1Pattern = '', $name2Pattern = '')
    {
        switch ($type) {
            case 'host':
            case 'hostgroup':
            case 'servicegroup':
                $l = $this->obj[$type];
                break;
            case 'service':
                if ($name1Pattern) {
                    $l = $this->obj[$type][$name1Pattern];
                } else {
                    throw new BackendException('Unhandled query');
                }
                break;
            default:
                return [];
        }

        $result = [];
        foreach ($l as $key => $entry) {
            if ($type == 'host') {
                $result[] = [
                    'name1' => $key,
                    'name2' => $entry[DISPLAY_NAME]
                ];
            } elseif ($type != 'service') {
                $result[] = [
                    'name1' => $key,
                    'name2' => $entry['alias']
                ];
            } else {
                $result[] = [
                    'name1' => $entry[DESCRIPTION],
                    'name2' => $entry[DESCRIPTION]
                ];
            }
        }

        return $result;
    }

    /**
     * Parses the filter array to backend
     *
     * @param array $objects List of objects to query
     * @param array $filters List of filters to apply
     * @return string Parsed filters
     * @throws BackendConnectionProblem
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    private function parseFilter($objects, $filters)
    {
        $aFilters = [];
        foreach ($objects as $OBJS) {
            $objFilters = [];
            foreach ($filters as $filter) {
                // Array('key' => 'host_name', 'operator' => '=', 'name'),
                switch ($filter['key']) {
                    case 'host_name':
                    case 'host_groups':
                    case 'service_description':
                    case 'groups':
                    case 'service_groups':
                    case 'hostgroup_name':
                    case 'group_name':
                    case 'servicegroup_name':
                        if ($filter['key'] != 'service_description') {
                            $val = $OBJS[0]->getName();
                        } else {
                            $val = $OBJS[0]->getServiceDescription();
                        }

                        $objFilters[] = 'Filter: ' . $filter['key'] . ' ' . $filter['op'] . ' ' . $val . "\n";
                        break;
                    default:
                        throw new BackendConnectionProblem('Invalid filter key (' . $filter['key'] . ')');
                }
            }

            // the object specific filters all need to match
            $count = count($objFilters);
            if ($count > 1) {
                $count = 'And: ' . $count . "\n";
            } else {
                $count = '';
            }

            $aFilters[] = implode('', $objFilters) . $count;
        }

        $count = count($aFilters);
        if ($count > 1) {
            $count = 'Or: ' . $count . "\n";
        } else {
            $count = '';
        }

        return implode('', $aFilters) . $count;
    }


    /**
     * Queries the livestatus socket for the state of a host
     *
     * @param array $objects List of objects to query
     * @param array $options List of filters to apply
     * @return array
     * @throws BackendException
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     * @author  Mathias Kettner <mk@mathias-kettner.de>
     */
    public function getHostState($objects, $options, $filters)
    {
        /*if ($options & 1)
            $stateAttr = 'hard_state';
        else
            $stateAttr = 'state';*/

        $arrReturn = [];
        if (count($filters) == 1 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '=') {
            foreach ($objects as $OBJS) {
                $name = $OBJS[0]->getName();
                if (isset($this->obj['host'][$name])) {
                    $arrReturn[$name] = $this->obj['host'][$name];
                }
            }
        } elseif (count($filters) == 1 && $filters[0]['key'] == 'host_groups' && $filters[0]['op'] == '>=') {
            foreach ($objects as $OBJS) {
                $name = $OBJS[0]->getName();

                if (!isset($this->obj['hostgroup'][$name])) {
                    continue;
                } // skip not existing hostgroups

                foreach ($this->obj['hostgroup'][$name]['members'] as $hostname) {
                    $host = $this->obj['host'][$hostname];
                    $arrReturn[$hostname] = $this->obj['host'][$hostname];
                }
            }
        } else {
            throw new BackendException('Unhandled query - filters: ' . json_encode($filters));
        }

        return $arrReturn;
    }

    /**
     * Queries the livestatus socket for a specific service
     * or all services of a host
     *
     * @param array $objects List of objects to query
     * @param int $options
     * @param array $filters List of filters to apply
     * @return array
     * @throws BackendConnectionProblem
     * @throws BackendException
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getServiceState($objects, $options, $filters)
    {
        $objFilter = $this->parseFilter($objects, $filters);
        /*if ($options & 1)
            $stateAttr = 'last_hard_state';
        else
            $stateAttr = 'state';*/

        $arrReturn = [];
        if (count($filters) == 1 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '=') {
            // All services of a host
            foreach ($objects as $OBJS) {
                if (isset($this->obj['service'][$OBJS[0]->getName()])) {
                    $arrReturn[$OBJS[0]->getName()] = $this->obj['service'][$OBJS[0]->getName()];
                }
            }
        } elseif (count($filters) == 1 && $filters[0]['key'] == 'service_groups' && $filters[0]['op'] == '>=') {
            // All services of a servicegroup
            foreach ($objects as $OBJS) {
                $name = $OBJS[0]->getName();

                // Skip not existing objects
                if (!isset($this->obj['servicegroup'][$name])) {
                    continue;
                }

                foreach ($this->obj['servicegroup'][$name]['members'] as $attr) {
                    list($name1, $name2) = $attr;
                    foreach ($this->obj['service'][$name1] as $service) {
                        if ($service[DESCRIPTION] != $name2) {
                            continue;
                        }
                        $arrReturn[$name1][] = $service;
                    }
                }
            }
        } elseif (
            count($filters) == 2
            && $filters[0]['key'] == 'host_name'
            && $filters[0]['op'] == '='
            && $filters[1]['key'] == 'service_description'
            && $filters[1]['op'] == '='
        ) {
            // One specific service of a host
            foreach ($objects as $OBJS) {
                if (!isset($this->obj['service'][$OBJS[0]->getName()])) {
                    continue;
                }
                foreach ($arrReturn[$OBJS[0]->getName()] = $this->obj['service'][$OBJS[0]->getName()] as $service) {
                    if ($service[DESCRIPTION] == $OBJS[0]->getServiceDescription()) {
                        $arrReturn[$OBJS[0]->getName() . '~~' . $OBJS[0]->getServiceDescription()] = $service;
                    }
                }
            }
        } else {
            throw new BackendException('Unhandled filter in backend (getServiceState): ' . json_encode($filters));
        }

        return $arrReturn;
    }

    /**
     * Queries the livestatus socket for host state counts. The information
     * are used to calculate the summary output and the summary state of a
     * host and a well performing alternative to the existing recurisve
     * algorithm.
     *
     * @param array $objects List of objects to query
     * @param int $options This is a mask of options to use during the query
     * @param array $filters List of filters to apply
     * @return array List of states and counts
     * @throws BackendException
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getHostMemberCounts($objects, $options, $filters)
    {
        /*if ($options & 1)
            $stateAttr = 'last_hard_state';
        else
            $stateAttr = 'state';*/

        $aReturn = [];
        if (count($filters) == 1 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '=') {
            // Get service state counts of one host
            foreach ($objects as $OBJS) {
                $name = $OBJS[0]->getName();
                $aReturn[$name] = ['counts' => $this->serviceStates];
                if (isset($this->obj['service'][$name])) {
                    foreach ($this->obj['service'][$name] as $service) {
                        if ($service[ACK] === true) {
                            $aReturn[$name]['counts'][$service[STATE]]['ack']++;
                        } elseif ($service[DOWNTIME] === true) {
                            $aReturn[$name]['counts'][$service[STATE]]['downtime']++;
                        }
                        else {
                            $aReturn[$name]['counts'][$service[STATE]]['normal']++;
                        }
                    }
                }
            }
        } elseif (count($filters) == 1 && $filters[0]['key'] == 'host_groups' && $filters[0]['op'] == '>=') {
            // Get service state counts for all hosts in a hostgroup (separated by host)
            foreach ($objects as $OBJS) {
                $name = $OBJS[0]->getName();

                if (!isset($this->obj['hostgroup'][$name])) {
                    continue;
                } // skip not existing hostgroups

                foreach ($this->obj['hostgroup'][$name]['members'] as $hostname) {
                    $resp = $this->getHostMemberCounts(
                        // TODO: what is $backendId? Seems to alternate between string and array
                        [[new NagVisHost($this->backendId, $hostname)]],
                        $options,
                        [['key' => 'host_name', 'op' => '=', 'val' => 'name']]
                    );
                    $aReturn[$hostname] = $resp[$hostname];
                }
            }
        } else {
            throw new BackendException('Unhandled filter in backend (getHostMemberCounts)');
        }

        return $aReturn;
    }

    /**
     * Queries the livestatus socket for hostgroup state counts. The information
     * are used to calculate the summary output and the summary state of a
     * hostgroup and a well performing alternative to the existing recurisve
     * algorithm.
     *
     * @param array $objects List of objects to query
     * @param int $options
     * @param array $filters List of filters to apply
     * @return array List of states and counts
     * @throws BackendException
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getHostgroupStateCounts($objects, $options, $filters)
    {
        /*if ($options & 1)
            $stateAttr = 'hard_state';
        else
            $stateAttr = 'state';*/

        $aReturn = [];
        // Get all host and service states of a hostgroup
        if (count($filters) == 1 && $filters[0]['key'] == 'groups' && $filters[0]['op'] == '>=') {
            foreach ($objects as $OBJS) {
                $name = $OBJS[0]->getName();
                if (!isset($aReturn[$name])) {
                    $aReturn[$name] = ['counts' => $this->serviceStates];
                    $aReturn[$name]['counts'] += $this->hostStates;
                }

                if (!isset($this->obj['hostgroup'][$name])) {
                    continue;
                } // skip not existing objects

                foreach ($this->obj['hostgroup'][$name]['members'] as $hostname) {
                    $host = $this->obj['host'][$hostname];

                    if ($host[ACK] === true) {
                        $aReturn[$name]['counts'][$host[STATE]]['ack']++;
                    }
                    elseif ($host[DOWNTIME] === true) {
                        $aReturn[$name]['counts'][$host[STATE]]['downtime']++;
                    } else {
                        $aReturn[$name]['counts'][$host[STATE]]['normal']++;
                    }

                    // If recognize_services are disabled don't fetch service information
                    if ($options & 2) {
                        continue;
                    }

                    // TODO: what is $backendId? Seems to alternate between string and array
                    $resp = $this->getHostMemberCounts(
                        [[new NagVisHost($this->backendId, $hostname)]],
                        $options,
                        [['key' => 'host_name', 'op' => '=', 'val' => 'name']]
                    );
                    foreach ($resp[$hostname]['counts'] as $state => $substates) {
                        foreach ($substates as $substate => $count) {
                            $aReturn[$name]['counts'][$state][$substate] += $count;
                        }
                    }
                }
            }
        } else {
            throw new BackendException('Unhandled filter in backend (getHostgroupStateCounts)');
        }

        return $aReturn;
    }

    /**
     * Queries the livestatus socket for servicegroup state counts. The information
     * are used to calculate the summary output and the summary state of a
     * servicegroup and a well performing alternative to the existing recurisve
     * algorithm.
     *
     * @param array $objects List of objects to query
     * @param array $options List of filters to apply
     * @return array List of states and counts
     * @throws BackendException
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getServicegroupStateCounts($objects, $options, $filters)
    {
        /*if ($options & 1)
            $stateAttr = 'last_hard_state';
        else
            $stateAttr = 'state';*/
        $aReturn = [];
        // Get all service state counts of a servicegroup
        if (count($filters) == 1 && $filters[0]['key'] == 'groups' && $filters[0]['op'] == '>=') {
            foreach ($objects as $OBJS) {
                $name = $OBJS[0]->getName();

                // Skip not existing objects
                if (!isset($this->obj['servicegroup'][$name])) {
                    continue;
                }

                if (!isset($aReturn[$name])) {
                    $aReturn[$name] = ['counts' => []];
                }

                foreach ($this->obj['servicegroup'][$name]['members'] as $attr) {
                    list($name1, $name2) = $attr;
                    foreach ($this->obj['service'][$name1] as $service) {
                        if ($service[DESCRIPTION] != $name2) {
                            continue;
                        }

                        $state = $service[STATE];
                        if (!isset($aReturn[$name]['counts'][$state])) {
                            $aReturn[$name]['counts'][$state] = $this->serviceStates[$state];
                        }

                        if ($service[ACK] === true) {
                            $aReturn[$name]['counts'][$state]['ack']++;
                        }
                        elseif ($service[DOWNTIME] === true) {
                            $aReturn[$name]['counts'][$state]['downtime']++;
                        } else {
                            $aReturn[$name]['counts'][$state]['normal']++;
                        }
                    }
                }
            }
        } else {
            throw new BackendException(
                'Unhandled filter in backend (getServicegroupStateCounts): ' . json_encode($aReturn)
            );
        }
        return $aReturn;
    }

    /**
     * @return string[]
     */
    public function getHostNamesWithNoParent()
    {
        return ['muc-srv2'];
    }

    /**
     * @param $hostName
     * @return array|mixed
     */
    public function getDirectChildNamesByHostName($hostName)
    {
        if (isset($this->childs[$hostName])) {
            return $this->childs[$hostName];
        } else {
            return [];
        }
    }

    /**
     * @param $hostName
     * @return array|mixed
     */
    public function getDirectParentNamesByHostName($hostName)
    {
        if (isset($this->parents[$hostName])) {
            return $this->parents[$hostName];
        } else {
            return [];
        }
    }

    /**
     * @param string $hostName Hostname
     * @param bool $min_business_impact
     * @return array List of hostnames
     * @author  Thibault Cohen <thibault.cohen@savoirfairelinux.com>
     */
    public function getDirectChildDependenciesNamesByHostName(
        $hostName,
        $min_business_impact = false
    ) {
        return $this->getDirectChildNamesByHostName($hostName);
    }

    /**
     * PUBLIC getDirectParentNamesByHostName()
     *
     * @param string $hostName Hostname
     * @param bool $min_business_impact
     * @return array List of hostnames
     * @author  Mathias Kettner <mk@mathias-kettner.de>
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getDirectParentDependenciesNamesByHostName(
        $hostName,
        $min_business_impact = false
    ) {
        return $this->getDirectParentNamesByHostName($hostName);
    }
}
