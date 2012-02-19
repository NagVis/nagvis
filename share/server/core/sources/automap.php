<?php

// options to be modyfiable by the user(url)
global $viewParams;
$viewParams = array(
    'automap' => array(
        'root',
        'render_mode',
        'show',
        'backend_id',
        'filter_by_state',
        'filter_by_ids',
        'child_layers',
        'parent_layers',
        'ignore_hosts',
        'margin',
    ),
);

function list_automap_render_modes() {
    return Array(
        'directed'    => 'directed',
        'undirected'  => 'undirected',
        'radial'      => 'radial',
        'circular'    => 'circular',
        'undirected2' => 'undirected2',
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
    'show' => array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_STRING_EMPTY,
        'field_type' => 'dropdown',
        'list'       => 'list_automaps',
    ),
    'root' => array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_STRING_NO_SPACE_EMPTY,
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
     * -1, this means no limitation.
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
        'default'    => '0.3',
        'match'      => MATCH_FLOAT_EMPTY,
    ),
);

// FIXME: At the moment use old mechanism. Need to be recoded
function process_automap($MAPCFG, $map_name, &$map_config) {}
function changed_automap($MAPCFG, $compare_time) {}

?>
