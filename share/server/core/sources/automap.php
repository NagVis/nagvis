<?php

//$automap_params = array(
//    'preview': array(
//        'default' => 0,
//        'match'   => null,
//    ),
//    'backend': array(
//);

/**
 * Get root host object by NagVis configuration or by backend.
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
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
            $hostsWithoutParent = $_BACKEND->getBackend($params['backend'])->getHostNamesWithNoParent();
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

function automap_load_params($map_config) {
    // Fetch option array from defaultparams string (extract variable
    // names and values)
    if(isset($map_config[0]['default_params'])) {
        $params = explode('&', $map_config[0]['default_params']);
        unset($params[0]);
    } else {
        $params = array();
    }

    foreach($params AS $set) {
        $arrSet = explode('=',$set);
        // Only load default option when the option has not been set before
        if(!isset($params[$arrSet[0]]) || $params[$arrSet[0]] == '') {
            $params[$arrSet[0]] = $arrSet[1];
        }
    }

    // Set default preview option
    if(!isset($params['preview']) || $params['preview'] == '')
        $params['preview'] = 0;

    if(!isset($params['backend']) || $params['backend'] == '') {
        if(isset($map_config[0]['backend_id']))
            $params['backend'] = $map_config[0]['backend_id'];
        else
            $params['backend'] = cfg('defaults', 'backend');
    }

    /**
     * This is the name of the root host, user can set this via URL. If no
     * hostname is given NagVis tries to take configured host from main
     * configuration or read the host which has no parent from backend
     */
    if(!isset($params['root']) || $params['root'] == '')
        $params['root'] = automap_get_root_hostname($params);

    /**
     * This sets how many child layers should be displayed. Default value is -1,
     * this means no limitation.
     */
    if(!isset($params['childLayers']) || $params['childLayers'] == '')
        $params['childLayers'] = -1;

    /**
     * This sets how many parent layers should be displayed. Default value is
     * -1, this means no limitation.
     */
    if(!isset($params['parentLayers']) || $params['parentLayers'] == '')
        $params['parentLayers'] = 0;
    if(!isset($params['renderMode']) || $params['renderMode'] == '')
        $params['renderMode'] = 'undirected';
    if(!isset($params['width']) || $params['width'] == '')
        $params['width'] = 1024;
    if(!isset($params['height']) || $params['height'] == '')
        $params['height'] = 786;
    if(!isset($params['ignoreHosts']) || $params['ignoreHosts'] == '')
        $params['ignoreHosts'] = '';
    if(!isset($params['filterGroup']) || $params['filterGroup'] == '')
        $params['filterGroup'] = '';
    if(!isset($params['filterByState']) || $params['filterByState'] == '0')
        $params['filterByState'] = '';

    return $params;
}

function automap_fetch_root($params, $obj_conf) {
    global $_BACKEND;
    $hostObject = new NagVisHost(GlobalCore::getInstance(), $_BACKEND, $params['backend'], $params['root']);
    $hostObject->setConfiguration($obj_conf);
    return $hostObject;
}

function automap_get_object_tree($params, $root_obj, $obj_conf, &$map_objects, &$ignore_hosts) {
    $root_obj->fetchChilds($params['childLayers'],
                           $obj_conf,
                           $ignore_hosts,
                           $map_objects);

    // Get all parent object information from backend when needed
    if(isset($arams['parentLayers']) && $params['parentLayers'] != 0) {
        global $_BACKEND;
        // If some parent layers are requested: It should be checked if the used
        // backend supports this
        if($_BACKEND->checkBackendFeature($this->backend_id, 'getDirectParentNamesByHostName')) {
            $root_obj->fetchParents($params['parentLayers'],
                                   $obj_conf,
                                   $ignore_hosts,
                                   $map_objects);
        }
    }
}

$automap_obj_conf = null;

/**
 * Gets the configuration of the objects using the global configuration
 *
 * @param   Strin   Optional: Name of the object to get the config for
 * @return	Array		Object configuration
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function automap_object_config(&$map_config) {
    $keys = array_keys($map_config);
    // Use either the __dummy__ host or the global section for gathering
    // the default configuration
    if(isset($keys[1]))
        $objectId = $keys[1];
    else
        $objectId = 0;

    /*
     * Get object default configuration from configuration file
     */
    $object_conf = array();
    foreach($map_config AS $key => $val) {
        if($key != 'type'
             && $key != 'backend_id'
             && $key != 'host_name'
             && $key != 'object_id'
             && $key != 'x'
             && $key != 'y'
             && $key != 'line_width') {
            $object_conf[$key] = $val;
        }
    }

    // Delete the dummy object when it has been used
    if($objectId != 0)
        unset($map_config[$objectId]);

    return $object_conf;
}

function process_automap($map_name, &$map_config) {
    // First load the automap configuration parameters
    $params = automap_load_params($map_config);

    // Get default settings for objects
    $obj_conf = automap_object_config($map_config);

    // This source does not directly honor the existing map configs. It saves
    // the existing config to use it later for modifying some object parameters.
    // The existing map config must not create new objects. The truth about the
    // existing objects comes only from this source.
    $saved_config = $map_config;
    $map_config = array();

    // Gather the root object
    $root_obj = automap_fetch_root($params, $obj_conf);

    $map_objects  = array($params['root'] => $root_obj);
    $ignore_hosts = array();

    // Get the object trees
    automap_get_object_tree($params, $root_obj, $obj_conf, $map_objects, $ignore_hosts);

    //// Now restore the user defined coordinates
    $i = 0;
    foreach($saved_config AS $object_id => $object) {
        if(isset($map_config[$object_id])) {
            $map_config[$object_id]['x'] = $object['x'];
            $map_config[$object_id]['y'] = $object['y'];
        }
    }

    //// FIXME: And now use graphviz to gather the rest of the coordinates
}

function changed_automap($compareTime) {
    // FIXME: Reload on changed parameters or restarted nagios
    return true;
}

?>
