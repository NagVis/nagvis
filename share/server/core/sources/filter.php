<?php

/**
 * This is a collection of filters which can be used by other sources
 * to remove some objects from the map config during map config processing
 * One example is to filter the map by a given hostgroup, by state or
 * by user permissions.
 *
 * This function is applied after processing all configured sources if not
 * executed earlier by one or several sources.
 */

global $filter_processed;
$filter_processed = false;

global $viewParams;
if(!isset($viewParams))
    $viewParams = array();
$viewParams = array_merge($viewParams, array(
    'filterGroup' => array(
        'must'    => false,
        'default' => '',
        'list'    => 'listHostgroupNames',
    )
));

function filter_hostgroup(&$map_config, $p) {
    if($p['filterGroup'] == '')
        return;

    // Initialize the backend
    global $_BACKEND;
    $_BACKEND->checkBackendExists($p['backend_id'], true);
    $_BACKEND->checkBackendFeature($p['backend_id'], 'getHostNamesInHostgroup', true);

    $hosts = $_BACKEND->getBackend($p['backend_id'])->getHostNamesInHostgroup($p['filterGroup']);

    // Remove all hosts not found in the hostgroup
    $hosts = array_flip($hosts);
    foreach($map_config AS $object_id => $obj)
        if(isset($obj['host_name']) && !isset($hosts[$obj['host_name']]))
            unset($map_config[$object_id]);
}

function params_filter($MAPCFG, &$map_config) {
    $p = array();

    $p['backend_id'] = isset($_GET['backend_id']) ? $_GET['backend_id'] : $MAPCFG->getValue(0, 'backend_id');
    $p['filterGroup'] = isset($_GET['filterGroup']) ? $_GET['filterGroup'] : '';
    
    return $p;
}

function process_filter($MAPCFG, $map_name, &$map_config, $explicit = true) {
    global $filter_processed;
    // Skip implicit calls if already processed explicit
    if(!$explicit && $filter_processed)
        return;
    $filter_processed = true;

    $params = params_filter($MAPCFG, $map_config);

    filter_hostgroup($map_config, $params);
}

?>
