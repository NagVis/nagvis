<?php

class DynmapError extends MapSourceError {}

function dynmap_get_objects($MAPCFG, $p) {
    global $_BACKEND;
    $objects = array();

    $type = $p['dynmap_object_types'];
    $filter = str_replace('\n', "\n", $p['dynmap_object_filter']);
    foreach($MAPCFG->getValue(0, 'backend_id') AS $backend_id) {
        $ret = $_BACKEND->getBackend($backend_id)->getObjects($type, '', '', $filter);
        // only use the internal names
        foreach ($ret AS $key => $val) {
            if ($type == 'service') {
                $obj_id = $MAPCFG->genObjId($backend_id.'~~'.$val['name1'] . '~~' . $val['name2']);
                $object = array(
                    'type'                => $type,
                    'object_id'           => $obj_id,
                    'backend_id'          => array($backend_id),
                    'host_name'           => $val['name1'],
                    'service_description' => $val['name2'],
                );
            } else {
                $obj_id = $MAPCFG->genObjId($backend_id.'~~'.$val['name1']);
                $object = array(
                    'type'                => $type,
                    'object_id'           => $obj_id,
                    'backend_id'          => array($backend_id),
                    $type.'_name'         => $val['name1'],
                );
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
$viewParams = array(
    'dynmap' => array(),
);

function listDynObjectTypes($CORE) {
    return Array(
        'host'         => l('Hosts'),
        'service'      => l('Services'),
        'hostgroup'    => l('Hostgroup'),
        'servicegroup' => l('Servicegroup'),
    );
}

// Config variables to be registered for this source
global $configVars;
$configVars = array(
    'dynmap_object_types' => Array(
        'must'       => false,
        'default'    => '',
        'field_type' => 'dropdown',
        'match'      => MATCH_DYN_OBJECT_TYPES,
        'list'       => 'listDynObjectTypes', 
    ),
    'dynmap_object_filter' => Array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_LIVESTATUS_FILTER,
    ),
    'dynmap_init_x' => array(
        'must'       => false,
        'default'    => 20,
        'match'      => MATCH_COORD_SIMPLE,
    ),
    'dynmap_init_y' => array(
        'must'       => false,
        'default'    => 700,
        'match'      => MATCH_COORD_SIMPLE,
    ),
    'dynmap_offset_x' => array(
        'must'       => false,
        'default'    => 30,
        'match'      => MATCH_COORD_SIMPLE,
    ),
    'dynmap_offset_y' => array(
        'must'       => false,
        'default'    => 30,
        'match'      => MATCH_COORD_SIMPLE,
    ),
    'dynmap_per_row' => array(
        'must'       => false,
        'default'    => 30,
        'match'      => MATCH_COORD_SIMPLE,
    ),
);

function dynmap_object_in_grid($params, $map_object) {
    $top  = $params['dynmap_init_y'];
    $left = $params['dynmap_init_x'];
    $step_y = $params['dynmap_offset_y'];
    $step_x = $params['dynmap_offset_x'];

    return $map_object['x'] - $left > 0 && ($map_object['x'] - $left) % $step_x === 0
        && $map_object['y'] - $top > 0 && ($map_object['y'] - $top) % $step_y === 0;
}

function dynmap_sort_objects($o1, $o2) {
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

    // Now recalculate and reposition all map objects which are currently
    // positioned on the map using the grid mechanism. But first sort all
    // objects by their names.
    usort($objects, 'dynmap_sort_objects');

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
}

/**
 * Report as changed when the core has been restarted since caching
 */
function changed_dynmap($MAPCFG, $compare_time) {
    $params = $MAPCFG->getSourceParams();

    $t = dynmap_program_start($MAPCFG, $params);
    if($t > $compare_time)
        return true;

    return false;
}

?>
