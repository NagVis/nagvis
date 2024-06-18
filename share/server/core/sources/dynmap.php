<?php

class DynmapError extends MapSourceError {}

function dynmap_get_objects($MAPCFG, $p) {
    global $_BACKEND;
    $objects = [];

    $type = $p['dynmap_object_types'];
    $filter = str_replace('\n', "\n", $p['dynmap_object_filter']);
    foreach($MAPCFG->getValue(0, 'backend_id') AS $backend_id) {
        $ret = $_BACKEND->getBackend($backend_id)->getObjects($type, '', '', $filter);
        // only use the internal names
        foreach ($ret AS $key => $val) {
            if ($type == 'service') {
                $obj_id = $MAPCFG->genObjId($backend_id.'~~'.$val['name1'] . '~~' . $val['name2']);
                $object = [
                    'type'                => $type,
                    'object_id'           => $obj_id,
                    'backend_id'          => [$backend_id],
                    'host_name'           => $val['name1'],
                    'service_description' => $val['name2'],
                ];
            } else {
                $obj_id = $MAPCFG->genObjId($backend_id.'~~'.$val['name1']);
                $object = [
                    'type'                => $type,
                    'object_id'           => $obj_id,
                    'backend_id'          => [$backend_id],
                    $type.'_name'         => $val['name1'],
                ];
            }
            $objects[$obj_id] = $object;
        }
    }
    return $objects;
}

function dynmap_program_start($MAPCFG, $p) {
    global $_BACKEND;
    $newest = null;
    foreach($MAPCFG->getValue(0, 'backend_id') AS $backend_id) {
        $this_start = $_BACKEND->getBackend($backend_id)->getProgramStart();
        if($newest === null || $this_start > $newest) {
            $newest = $this_start;
        }
    }
    return $newest;
}

// Register this source as being selectable by the user
global $selectable;
$selectable = true;

// options to be modifiable by the user(url)
global $viewParams;
$viewParams = [
    'dynmap' => [],
];

function listDynObjectTypes($CORE) {
    return [
        'host'         => l('Hosts'),
        'service'      => l('Services'),
        'hostgroup'    => l('Hostgroup'),
        'servicegroup' => l('Servicegroup'),
    ];
}

// Config variables to be registered for this source
global $configVars;
$configVars = [
    'dynmap_object_types' => [
        'must'       => false,
        'default'    => '',
        'field_type' => 'dropdown',
        'match'      => MATCH_DYN_OBJECT_TYPES,
        'list'       => 'listDynObjectTypes',
    ],
    'dynmap_object_filter' => [
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_LIVESTATUS_FILTER,
    ],
    'dynmap_init_x' => [
        'must'       => false,
        'default'    => 20,
        'match'      => MATCH_COORD_SIMPLE,
    ],
    'dynmap_init_y' => [
        'must'       => false,
        'default'    => 700,
        'match'      => MATCH_COORD_SIMPLE,
    ],
    'dynmap_offset_x' => [
        'must'       => false,
        'default'    => 30,
        'match'      => MATCH_COORD_SIMPLE,
    ],
    'dynmap_offset_y' => [
        'must'       => false,
        'default'    => 30,
        'match'      => MATCH_COORD_SIMPLE,
    ],
    'dynmap_per_row' => [
        'must'       => false,
        'default'    => 30,
        'match'      => MATCH_COORD_SIMPLE,
    ],
    'dynmap_sort' => [
        'must'       => false,
        'default'    => 'a',
        'match'      => MATCH_STRING_NO_SPACE,
        'field_type' => 'dropdown',
        'list'       => 'listHoverChildSorters',
    ],
    'dynmap_order' => [
        'must'       => false,
        'default'    => 'asc',
        'match'      => MATCH_ORDER,
        'field_type' => 'dropdown',
        'list'       => 'listHoverChildOrders',
    ],
];

// Assign config variables to specific object types
global $configVarMap;
$configVarMap = [
    'global' => [
        'dynmap' => [
            'dynmap_object_types'  => null,
            'dynmap_object_filter' => null,
            'dynmap_init_x'        => null,
            'dynmap_init_y'        => null,
            'dynmap_offset_x'      => null,
            'dynmap_offset_y'      => null,
            'dynmap_per_row'       => null,
            'dynmap_sort'          => null,
            'dynmap_order'         => null,
        ],
    ],
];

function dynmap_object_in_grid($params, $map_object) {
    $top  = $params['dynmap_init_y'];
    $left = $params['dynmap_init_x'];
    $step_y = $params['dynmap_offset_y'];
    $step_x = $params['dynmap_offset_x'];

    return $map_object['x'] - $left > 0 && ($map_object['x'] - $left) % $step_x === 0
        && $map_object['y'] - $top > 0 && ($map_object['y'] - $top) % $step_y === 0;
}

function dynmap_sort_objects($MAPCFG, $map_name, &$map_config, &$params, &$objects) {
    global $g_dynmap_order, $g_map_obj, $_BACKEND;

    $g_dynmap_order = $params['dynmap_order'];

    // Now recalculate and reposition all map objects which are currently
    // positioned on the map using the grid mechanism. But first sort all
    // objects.
    switch($params['dynmap_sort']) {
        case 's':
            $SORT_MAPCFG = new GlobalMapCfg($map_name);
            $SORT_MAPCFG->gatherTypeDefaults(false);
            foreach ($objects AS $object_id => $object) {
                $SORT_MAPCFG->addElement($object['type'], $object, false, $object_id);
            }

            $g_map_obj = new NagVisMapObj($SORT_MAPCFG, !IS_VIEW);
            $g_map_obj->fetchMapObjects();
            $g_map_obj->queueState(GET_STATE, DONT_GET_SINGLE_MEMBER_STATES);
            $_BACKEND->execute();
            $g_map_obj->applyState();

            // Add keys for sorting to $objects entries
            // TODO: Improve this: Adding these temporary keys should not be necessary
            foreach($g_map_obj->getStateRelevantMembers() AS $OBJ) {
                $object_id = $OBJ->getObjectId();
                $objects[$object_id]['.state'] = $OBJ->sum[STATE];
                $objects[$object_id]['.sub_state'] = $OBJ->getSubState(SUMMARY_STATE);
            }

            usort($objects, 'dynmap_sort_objects_by_state');

            // Cleanup sort specific keys again
            foreach ($objects AS $object_id => $object) {
                unset($object['.state']);
                unset($object['.sub_state']);
            }
        break;
        case 'a':
        default:
            usort($objects, 'dynmap_sort_objects_by_name');
        break;
    }
}

function dynmap_sort_objects_by_name($o1, $o2) {
    $o1_str = '';
    $o2_str = '';
    if ($o1['type'] == 'service') {
        $o1_str .= $o1['host_name'];
        $o1_str .= $o1['service_description'];
        $o2_str .= $o2['host_name'];
        $o2_str .= $o2['service_description'];
    } else {
        $o1_str .= $o1[$o1['type'].'_name'];
        $o2_str .= $o2[$o2['type'].'_name'];
    }
    if($o1_str == $o2_str)
        return 0;
    return ($o1_str > $o2_str) ? 1 : -1;
}

function dynmap_sort_objects_by_state($o1, $o2) {
    global $g_dynmap_order;
    return NagVisObject::sortStatesByStateValues($o1['.state'], $o1['.sub_state'],
                                                 $o2['.state'], $o2['.sub_state'], $g_dynmap_order);
}

$g_dynmap_order = 'asc';

function process_dynmap($MAPCFG, $map_name, &$map_config) {
    $params = $MAPCFG->getSourceParams();

    // Load the list of objects
    $objects = dynmap_get_objects($MAPCFG, $params);
    $type = $params['dynmap_object_types'];

    // First remove all objects from the current map config which should 
    // not be there anymore (according to the backend response)
    foreach ($map_config AS $object_id => $obj) {
        if ($obj['type'] == $type && !isset($objects[$object_id])) {
            unset($map_config[$object_id]);
        }
    }

    // Now remove all entries from $objects which are on the map but not
    // positioned in the grid anymore (they have been repositioned by the user 
    // and do not need to be handled in further processing - except that they 
    // are to be added to the map unchanged again)
    foreach ($objects AS $object_id => $object) {
        if (isset($map_config[$object_id]) && !dynmap_object_in_grid($params, $map_config[$object_id])) {
            unset($objects[$object_id]);
        }
    }

    // Now check which entries in $objects have no object on the map and
    // create one object on the map for those objects.
    foreach ($objects AS $object_id => $object) {
        if (!isset($map_config[$object_id])) {
            $map_config[$object_id] = $object;
        }
    }

    dynmap_sort_objects($MAPCFG, $map_name, $map_config, $params, $objects);

    $x = $params['dynmap_init_x'];
    $y = $params['dynmap_init_y'];
    $step_x = $params['dynmap_offset_x'];
    $step_y = $params['dynmap_offset_y'];

    $in_this_row = 0;
    foreach ($objects AS $object) {
        $map_config[$object['object_id']]['x'] = $x;        
        $map_config[$object['object_id']]['y'] = $y;
        $x += $step_x;
        $in_this_row += 1;
        if ($in_this_row >= $params['dynmap_per_row']) {
            $x = $params['dynmap_init_x'];
            $y += $step_y;
            $in_this_row = 0;
        }
    }

    return true; // allow caching
}

/**
 * Report as changed when the core has been restarted since caching
 */
function changed_dynmap($MAPCFG, $compare_time) {
    $params = $MAPCFG->getSourceParams();

    $t = dynmap_program_start($MAPCFG, $params);
    if($t > $compare_time)
        return true;

    // When sorted by state the state of the objects is relevant for
    // the order of objects. Therefore we need to track state changes
    // here.
    if ($params["dynmap_sort"] === 's') {
        // TODO
        return true;
    }

    return false;
}

?>
