<?php

// Register this source as being selectable by the user
global $selectable;
$selectable = true;

// options to be modyfiable by the user(url)
global $viewParams;
$viewParams = array(
    'automap' => array(
        'header_template',
        'root',
        'render_mode',
        'backend_id',
        'width',
        'height',
        'iconset',
        'filter_by_state',
        'filter_by_ids',
        'filter_group',
        'child_layers',
        'parent_layers',
        'ignore_hosts',
        'margin',
        'rankdir',
        'overlap',
        'label_show',
        'url_target',
    ),
);
if (cfg('global', 'shinken_features')) {
    array_push($viewParams['automap'], 'min_business_impact');
}

function list_automap_render_modes() {
    return Array(
        'directed'    => 'directed',
        'undirected'  => 'undirected',
        'radial'      => 'radial',
        'circular'    => 'circular',
        'undirected2' => 'undirected2',
        'undirected3' => 'undirected3',
    );
}

function list_automap_rankdirs() {
    return Array(
        'TB' => l('Top to bottom'),
        'LR' => l('Left to right'),
        'BT' => l('Bottom to top'),
        'RL' => l('Right to left'),
    );
}

function list_automap_overlaps() {
    return array(
        'true'     => l('true'),
        'false'    => l('false'),
        'scale'    => l('scale'),
        'scalexy'  => l('scalexy'),
        'ortho'    => l('ortho'),
        'orthoxy'  => l('orthoxy'),
        'orthoyx'  => l('orthoyx'),
        'compress' => l('compress'),
        'ipsep'    => l('ipsep'),
        'vpsc'     => l('vpsc'),
    );
}

function list_automaps($CORE) {
    $arr = array();
    foreach($CORE->getAvailableAutomaps() AS $mapName) {
        $arr[$mapName] = $mapName;
    }
    return $arr;
}

// Config variables to be registered for this source
global $configVars;
$configVars = array(
    'render_mode' => array(
        'must'       => false,
        'default'    => 'undirected',
        'match'      => MATCH_AUTOMAP_RENDER_MODE,
        'field_type' => 'dropdown',
        'list'       => 'list_automap_render_modes',
    ),
    'root' => array(
        'must'       => false,
        'default'    => '<<<monitoring>>>',
        'match'      => MATCH_AUTOMAP_HOSTNAME,
    ),
    'filter_by_state' => array(
        'must'       => false,
        'default'    => '0',
        'match'      => MATCH_BOOLEAN,
    ),
    'filter_by_ids' => array(
        'must'       => false,
        'hidden'     => true,
        'default'    => '',
        'match'      => MATCH_BOOLEAN,
    ),

    /**
     * This sets how many child layers should be displayed. Default value is -1,
     * this means no limitation.
     */
    'child_layers' => array(
        'must'       => false,
        'default'    => -1,
        'match'      => MATCH_INTEGER_PRESIGN_EMPTY,
    ),
    /**
     * This sets how many parent layers should be displayed. Default value is
     * 0, this means the direction is not fteched
     */
    'parent_layers' => array(
        'must'       => false,
        'default'    => 0,
        'match'      => MATCH_INTEGER_PRESIGN_EMPTY,
    ),

    'ignore_hosts' => array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_STRING_NO_SPACE_EMPTY,
    ),

    'margin' => array(
        'must'       => false,
        'default'    => '50',
        'match'      => MATCH_FLOAT_EMPTY,
    ),
    'rankdir' => array(
        'must'       => false,
        'default'    => 'LR',
        'match'      => MATCH_AUTOMAP_RANKDIR,
        'field_type' => 'dropdown',
        'list'       => 'list_automap_rankdirs',
    ),
    'overlap' => array(
        'must'       => false,
        'default'    => 'scale',
        'match'      => MATCH_AUTOMAP_OVERLAP,
        'field_type' => 'dropdown',
        'list'       => 'list_automap_overlaps',
    ),
);


if (cfg('global', 'shinken_features')) {
    function list_business_impact() {
        return Array(
            '0'          => '0_development',
            '1'          => '1_testing',
            '2'          => '2_standard',
            '3'          => '3_production',
            '4'          => '4_top_production',
            '5'          => '5_business_critical',
        );
    }
    $configVars['min_business_impact'] = array(
        'must'       => false,
        'default'    => '0_development',
        'match'      => MATCH_AUTOMAP_BUSINESS_IMPACT,
        'field_type' => 'dropdown',
        'list'       => 'list_business_impact',
    );
}

// Alter some global vars with automap specific things
$updateConfigVars = array(
    'label_show' => array(
        'default' => '1',
    ),
    'label_border' => array(
        'default' => 'transparent',
    ),
    'label_background' => array(
        'default' => '#ffffff',
    ),
    'label_maxlen' => array(
        'default' => '14',
    ),
    'line_width' => array(
        'default' => '1',
    ),
    'line_color' => array(
        'default' => '#000000',
    ),
);

$hiddenConfigVars = array(
    'line_type',
);

/**
 * Get root host object by NagVis configuration or by backend.
 */
function automap_get_root_hostname($params) {
    global $_BACKEND;
    /**
     * NagVis tries to take configured host from main
     * configuration or read the host which has no parent from backend
     * when the root cannot be fetched via backend it reads the default
     * value for the defaultroot
     */
    $defaultRoot = cfg('automap','defaultroot', TRUE);
    if(!isset($defaultRoot) || $defaultRoot == '') {
        try {
            $hostsWithoutParent = $_BACKEND->getBackend($params['backend_id'][0])->getHostNamesWithNoParent();
        } catch(BackendConnectionProblem $e) {}

        if(isset($hostsWithoutParent) && count($hostsWithoutParent) == 1)
            $defaultRoot = $hostsWithoutParent[0];
    }

    if(!isset($defaultRoot) || $defaultRoot == '') {
        $defaultRoot = cfg('automap','defaultroot');
    }

    // Could not get root host for the automap
    if(!isset($defaultRoot) || $defaultRoot == '') {
        throw new NagVisException(l('couldNotGetRootHostname'));
    } else {
        return $defaultRoot;
    }
}

function automap_load_params($MAPCFG) {
    $params = $MAPCFG->getSourceParams();

    if(isset($params['ignore_hosts'])) {
        $params['ignore_hosts'] = explode(',', $params['ignore_hosts']);
    }

    /**
     * This is the name of the root host, user can set this via URL. If no
     * hostname is given NagVis tries to take configured host from main
     * configuration or read the host which has no parent from backend
     */
    if(!isset($params['root']) || $params['root'] == '')
        $params['root'] = automap_get_root_hostname($params);
    
    return $params;
}

/**
 * Transforms a list of hostnames to object_ids using the object_id
 * translation file
 */
function automap_hostnames_to_object_ids($names) {
    global $automap_object_ids;

    $ids = array();
    foreach($names AS $name) {
        if(isset($automap_object_ids[$name])) {
            $ids[] = $automap_object_ids[$name];
        }/* else {
            echo 'DEBUG: Missing automap objid '.$name;
        }*/
    }
    return $ids;
}

/**
 * Loads the hostname to object_id mapping table from the central file
 */
function automap_load_object_ids() {
    global $automap_object_id_file, $automap_object_ids;
    if(!isset($automap_object_ids[0]))
        if(GlobalCore::getInstance()->checkExisting($automap_object_id_file, false))
            $automap_object_ids = json_decode(file_get_contents($automap_object_id_file), true);
        else
            $automap_object_ids = array();

    return $automap_object_ids;
}

/**
 * Saves the given hostname to object_id mapping table in the central file
 */
function automap_store_object_ids() {
    global $automap_object_id_file, $automap_object_ids;
    return file_put_contents($automap_object_id_file, json_encode($automap_object_ids)) !== false;
}

function automap_obj_base($MAPCFG, &$params, &$saved_config, $obj_name) {
    global $automap_object_ids, $automap_object_ids_changed;

    // Generate an object id if it does not exist
    if(!isset($automap_object_ids[$obj_name])) {
        $automap_object_ids[$obj_name] = $MAPCFG->genObjId($obj_name);
        $automap_object_ids_changed = true;
    }

    $obj = array();

    // Add maybe existing explicit config from saved_config. This includes
    // initial coordinates set by the user
    foreach($saved_config AS $conf) {
        if($conf['type'] == 'host' && $conf['host_name'] == $obj_name) {
            $obj = $conf;
        }
    }

    // Add the automap object_id
    $obj['object_id'] = $automap_object_ids[$obj_name];

    return $obj;
}

function automap_obj($MAPCFG, &$params, &$saved_config, $obj_name) {
    $obj = automap_obj_base($MAPCFG, $params, $saved_config, $obj_name);
    
    if ($obj_name === '<<<monitoring>>>') {
        $obj['host_name'] = 'Monitoring';
        $obj['type']      = 'shape';
        $obj['icon']      = 'std_nagvis.png';
        $obj['.width']    = 22;
        $obj['.height']   = 22;
    } else {
        $obj['type']      = 'host';
        $obj['host_name'] = $obj_name;

        // Default to params iconset
        if(!isset($obj['iconset']))
            $obj['iconset'] = $params['iconset'];

        // Calculate the size of the object for later auto positioning
        $size = iconset_size($obj['iconset']);
        $obj['.width']      = $size[0];
        $obj['.height']     = $size[1];
    }

    $obj['label_show']       = $MAPCFG->getValue(0, 'label_show');
    $obj['label_border']     = $MAPCFG->getValue(0, 'label_border');
    $obj['label_background'] = $MAPCFG->getValue(0, 'label_background');
    $obj['label_maxlen']     = $MAPCFG->getValue(0, 'label_maxlen');

    // Header menu has z-index 100, this object's label the below+1
    $obj['z']           = 98;
    $obj['.parents']    = array();
    $obj['.childs']     = array();

    return $obj;
}

function automap_connector($MAPCFG, &$params, &$saved_config, $from_obj, $to_obj) {
    $obj_name = $from_obj['object_id'].'x'.$to_obj['object_id'];

    $obj = automap_obj_base($MAPCFG, $params, $saved_config, $obj_name);

    $obj['type']       = 'line';
    $obj['view_type']  = 'line';
    $obj['line_type']  = '11';
    $obj['z']          = 90;
    $obj['line_color'] = $MAPCFG->getValue(0, 'line_color');
    $obj['line_width'] = $MAPCFG->getValue(0, 'line_width');
    $obj['line_arrow'] = $MAPCFG->getValue(0, 'line_arrow');
    $obj['x']          = $from_obj['object_id'] . '%+' . ($from_obj['.width']  / 2) . ','
                        .$to_obj['object_id']   . '%+' . ($to_obj['.width']    / 2);
    $obj['y']          = $from_obj['object_id'] . '%+' . ($from_obj['.height'] / 2) . ','
                        .$to_obj['object_id']   . '%+' . ($to_obj['.height']   / 2);

    return $obj;
}

/**
 * Gets all child/parent objects of this host from the backend. The child objects are
 * saved to the childObjects array
 */
function automap_fetch_tree($dir, $MAPCFG, $params, &$saved_config, $obj_name, $layers_left, &$this_tree_lvl) {
    if($layers_left == 0)
        return; // Stop recursion when the number of layers counted down

    $relations = array();
    try {
        global $_BACKEND;
        if($dir == 'childs') {
            if($obj_name == '<<<monitoring>>>') {
                try {
                    $relations = $_BACKEND->getBackend($params['backend_id'][0])->getHostNamesWithNoParent();
                } catch(BackendConnectionProblem $e) {
                    $relations = array();
                }
            } elseif (cfg('global', 'shinken_features')) {
                if ($params['min_business_impact']){
                    $tmp_array = array_flip(list_business_impact());
                    $min_business_impact = $tmp_array[$params['min_business_impact']];
                }
                $relations = $_BACKEND->getBackend($params['backend_id'][0])->getDirectChildDependenciesNamesByHostName($obj_name, $min_business_impact);
            } else {
                $relations = $_BACKEND->getBackend($params['backend_id'][0])->getDirectChildNamesByHostName($obj_name);
            }
        }
        elseif ($obj_name != '<<<monitoring>>>') {
            if (cfg('global', 'shinken_features')) {
                if ($params['min_business_impact']){
                    $tmp_array = array_flip(list_business_impact());
                    $min_business_impact = $tmp_array[$params['min_business_impact']];
                }
                $relations = $_BACKEND->getBackend($params['backend_id'][0])->getDirectParentDependenciesNamesByHostName($obj_name, $min_business_impact);
            } else {
                $relations = $_BACKEND->getBackend($params['backend_id'][0])->getDirectParentNamesByHostName($obj_name);
            }

            // When no parents can be found for one host, this is the root node,
            // add the <<<monitoring>>> host as parent
            if (count($relations) == 0)
                $relations[] = '<<<monitoring>>>';
        }
    } catch(BackendException $e) {}

    foreach($relations AS $rel_name) {
        if (in_array($rel_name, $params['ignore_hosts']) == True){
            continue;
        }
        $obj = automap_obj($MAPCFG, $params, $saved_config, $rel_name);

        // Add to tree
        $this_tree_lvl[$obj['object_id']] = $obj;

        // < 0 - there is no limit
        // > 0 - there is a limit but it is no reached yet
        if($layers_left < 0 || $layers_left > 0) {
            automap_fetch_tree($dir, $MAPCFG, $params, $saved_config, $rel_name, $layers_left - 1, $this_tree_lvl[$obj['object_id']]['.'.$dir]);
        }
    }
}

function automap_get_object_tree($MAPCFG, $params, &$saved_config) {
    $root_name = $params['root'];

    $root_obj = automap_obj($MAPCFG, $params, $saved_config, $root_name);

    // Initialize the tree with the root objects
    $tree = &$root_obj;

    automap_fetch_tree('childs', $MAPCFG, $params, $saved_config, $root_name, $params['child_layers'], $root_obj['.childs']);

    // Get all parent object information from backend when needed
    // If some parent layers are requested: It should be checked if the used
    // backend supports this
    if(isset($params['parent_layers']) && $params['parent_layers'] != 0) {
        global $_BACKEND;
        
        if($_BACKEND->checkBackendFeature($params['backend_id'][0], 'getDirectParentNamesByHostName')) {
            automap_fetch_tree('parents', $MAPCFG, $params, $saved_config, $root_name, $params['parent_layers'], $root_obj['.parents']);
        }
    }

    return $tree;
}

// allowed_ids - contains an assoziative array of object_ids to keep in the tree where 
//               the object_ids are the keys.
// obj         - is the base object in the tree to use as start point for filtering.
//               This is basically the root object of the automap.
function automap_filter_tree($allowed_ids, &$obj, $directions = null) {
    // Is the current object allowed to remain on the map on its own?
    $remain = isset($allowed_ids[$obj['object_id']]);

    if($directions == null) {
        $directions = array('parents', 'childs');
    }

    // Loop both directions
    foreach($directions as $dir) {
        foreach($obj['.'.$dir] AS $rel_id => &$rel) {
            // Or does a relative allow this object to remain on the map?
            $rel_remain = automap_filter_tree($allowed_ids, $rel, array($dir));

            // If there is no reason for this relative to remain on the map, remove it here
            if(!$rel_remain) {
                unset($obj['.'.$dir][$rel_id]);
            } else {
                // If at least one rel is allowed to remain, the ancestor must stay
                $remain = true;
            }
        }
    }

    return $remain;
}

/**
 * On automap updates not all objects are updated at once. Filter
 * the child tree by the given list of host object ids.
 */
function automap_filter_by_ids(&$obj, $params = null) {
    if(isset($params['filter_by_ids']) && $params['filter_by_ids'] != '') {
        $allowed_ids = array_flip(explode(',', $params['filter_by_ids']));
        automap_filter_tree($allowed_ids, $obj);
    }
}

/**
 * It is possible to filter the object tree by a hostgroup. In this mode 
 * the list of hostnames in this group is fetched from the backend and the
 * parent/child trees are filtered using this list.
 *
 * Added later: It is possible that a host of the given group is behind a
 * host which is not in the group. These 'connector' hosts need to be added
 * too. Those hosts will be added by default but this can be disabled by
 * config option. This sort of hosts should be visualized in another way.
 */
function automap_filter_by_group(&$obj, $params) {
    if(!isset($params['filter_group']) || $params['filter_group'] == '')
        return;

    global $_BACKEND;
    $_BACKEND->checkBackendExists($params['backend_id'][0], true);
    $_BACKEND->checkBackendFeature($params['backend_id'][0], 'getHostNamesInHostgroup', true);
    $hosts = $_BACKEND->getBackend($params['backend_id'][0])->getHostNamesInHostgroup($params['filter_group']);

    $allowed_ids = array_flip(automap_hostnames_to_object_ids($hosts));
    automap_filter_tree($allowed_ids, $obj);
}

/**
 * Links the object in the object tree to the map objects
 */
function automap_tree_to_map_config($MAPCFG, &$params, &$saved_config, &$map_config, &$tree) {
    if(isset($map_config[$tree['object_id']])) {
        return;
    }

    $map_config[$tree['object_id']] = $tree;
    
    // Remove internal attributes here
    unset($map_config[$tree['object_id']]['.childs']);
    unset($map_config[$tree['object_id']]['.parents']);

    foreach($tree['.childs'] AS $child) {
        automap_tree_to_map_config($MAPCFG, $params, $saved_config, $map_config, $child);
        $line = automap_connector($MAPCFG, $params, $saved_config, $tree, $child);
        $map_config[$line['object_id']] = $line;
    }

    foreach($tree['.parents'] AS $parent) {
        automap_tree_to_map_config($MAPCFG, $params, $saved_config, $map_config, $parent);
        $line = automap_connector($MAPCFG, $params, $saved_config, $tree, $parent);
        $map_config[$line['object_id']] = $line;
    }
}

function process_automap($MAPCFG, $map_name, &$map_config) {
    global $automap_object_id_file, $automap_object_ids, $automap_object_ids_changed;

    // Initialize global vars
    $automap_object_id_file = cfg('paths', 'var') . 'automap.hostids';
    $automap_object_ids = null;
    $automap_object_ids_changed = false;

    // Load the automap config parameters
    $params = automap_load_params($MAPCFG);

    // This source does not directly honor the existing map configs. It saves
    // the existing config to use it later for modifying some object parameters.
    // The existing map config must not create new objects. The truth about the
    // existing objects comes only from this source.
    $saved_config = $map_config;
    $map_config = array(0 => $saved_config[0]);

    // Get the object trees
    $tree = automap_get_object_tree($MAPCFG, $params, $saved_config);

    // Store the automap object_ids after processing the tree (all relevant object_ids)
    // have been gathered now
    if($automap_object_ids_changed) {
        automap_store_object_ids();
    }

    /**
     * Here we have all objects within the given layers in the tree - completely unfiltered!
     * Optionally: Apply the filters below
     */

    // Filter the objects by their object ids. This is needed during state update
    automap_filter_by_ids($tree, $params);
    // Maybe also filter by group
    automap_filter_by_group($tree, $params);

    // Transform the tree to a flat map config for regular map rendering
    automap_tree_to_map_config($MAPCFG, $params, $saved_config, $map_config, $tree);

    // Now use graphviz to calculate the coordinates of the objects
    // (where no manual coords have been set)
    process_automap_pos($MAPCFG, $map_name, $map_config, $tree, $params);

    // Remove "." leaded attributes
    // FIXME: Maybe move this to general "sources" processing
    foreach($map_config as $object_id => $conf)
        foreach(array_keys($conf) as $key)
            if($key[0] == '.')
                unset($map_config[$object_id][$key]);
}

function automap_program_start($p) {
    global $_BACKEND;
    $_BACKEND->checkBackendExists($p['backend_id'][0], true);
    $_BACKEND->checkBackendFeature($p['backend_id'][0], 'getProgramStart', true);
    return $_BACKEND->getBackend($p['backend_id'][0])->getProgramStart();
}

function changed_automap($MAPCFG, $compare_time) {
    $params = $MAPCFG->getSourceParams();
    return automap_program_start($params) > $compare_time;
}

?>
