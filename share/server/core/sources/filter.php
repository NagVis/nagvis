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

function list_hostgroups() {
    global $_BACKEND;
    try {
        $aRet = Array();
        $objs = $_BACKEND->getBackend($_GET[''])->getObjects('hostgroup');
        foreach($objs AS $obj) {
            $aRet[] = $obj['name1'];
        }
        return $aRet;
    } catch(BackendConnectionProblem $e) {
        return array();
    }
}

$viewParams = array_merge($viewParams, array(
    'filterGroup' => array(
        'default' => '',
        'list'    => 'list_hostgroups',
    )
));

function filter_hostgroup($map_config) {
    if(!isset($_GET['filterGroup']) || $_GET['filterGroup'] == '')
        return;

    //$filter_group = $_GET['filterGroup'];
    //$_BACKEND->getBackend($map_config[0]['backend_id']);
    // FIXME: To be coded
}

function process_filter($MAPCFG, $map_name, &$map_config, $explicit = true) {
    global $filter_processed;
    // Skip implicit calls if already processed explicit
    if(!$explicit && $filter_processed)
        return;
    $filter_processed = true;

    filter_hostgroup($map_config);
}

?>
