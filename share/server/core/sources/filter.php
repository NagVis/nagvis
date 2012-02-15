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

$filter_processed = false;

$viewParams = array_merge($viewParams, array(
    'filterGroup' => array(
        'default' => '',
        'list'    => 'listHostgroupNames',
    )
));

function filter_hostgroup(&$map_config, $params) {
    if($params['filterGroup'] == '')
        return;

    // FIXME: To be coded
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
