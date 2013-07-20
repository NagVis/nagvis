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

// options to be modyfiable by the user(url)
global $viewParams;
$viewParams = array();

// Config variables to be registered for this source
global $configVars;
$configVars = array(
    'filter_group' => array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_STRING_EMPTY,
        'field_type' => 'dropdown',
        'list'       => 'listHostgroupNames',
    )
);

/**
 * This filters the current map config by a given hostgroup.
 * All hosts not found in this group are removed from the map.
 *
 * In case of the automap it does filter the object tree before this
 * place is reached. Means in case of an automap this function should
 * not change anything.
 */
function filter_hostgroup(&$map_config, $p) {
    if(!isset($p['filter_group']) || $p['filter_group'] == '')
        return;

    // Initialize the backend
    global $_BACKEND;
    $_BACKEND->checkBackendExists($p['backend_id'][0], true);
    $_BACKEND->checkBackendFeature($p['backend_id'][0], 'getHostNamesInHostgroup', true);

    $hosts = $_BACKEND->getBackend($p['backend_id'][0])->getHostNamesInHostgroup($p['filter_group']);

    // Remove all hosts not found in the hostgroup
    $hosts = array_flip($hosts);
    foreach($map_config AS $object_id => $obj)
        if(isset($obj['host_name']) && !isset($hosts[$obj['host_name']]))
            unset($map_config[$object_id]);
}

function process_filter($MAPCFG, $map_name, &$map_config, $params = null) {
    global $filter_processed;
    // Skip implicit calls if already processed explicit
    if($params === null && $filter_processed)
        return;
    $filter_processed = true;

    if($params === null)
        $params = $MAPCFG->getSourceParams();

    //filter_hostgroup($map_config, $params);
}

?>
