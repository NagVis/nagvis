<?php

/**
 * @return string[]
 */
function getMapObjectTypes()
{
    return [
        'host',
        'hostgroup',
        'service',
        'servicegroup',
        'map',
        'line',
        'textbox',
        'shape',
        'container',
        'dyngroup',
        'aggr',
    ];
}

/**
 * @return array
 * @throws NagVisException
 */
function listMapNames()
{
    global $CORE, $AUTHORISATION;
    $list = [];
    $maps = $CORE->getAvailableMaps();
    foreach ($maps as $map_name) {
        if ($AUTHORISATION->isPermitted('Map', 'view', $map_name)) {
            $list[$map_name] = $map_name;
        }
    }

    return $list;
}

/**
 * @return array
 * @throws NagVisException
 */
function listMapImages()
{
    global $CORE;
    $options = $CORE->getAvailableBackgroundImages();
    array_unshift($options, 'none');
    return $options;
}

/**
 * @return string[]
 */
function listLineTypes()
{
    return [
        '10' => '-------><-------',
        '11' => '--------------->',
        '12' => '----------------',
        '13' => '---%---><---%---',
        '14' => '--%+BW-><-%+BW--',
        '15' => '---BW--><--BW---',
    ];
}

/**
 * @return string[]
 */
function listStatelessLineTypes()
{
    return [
        '10' => '-------><-------',
        '11' => '--------------->',
        '12' => '----------------',
    ];
}

/**
 * @return string[]
 */
function listLineArrows()
{
    return [
        'forward' => '------->',
        'back'    => '<-------',
        'both'    => '<------>',
        'none'    => '--------',
    ];
}

/**
 * @return array
 */
function listGadgetTypes()
{
    return [
        'img'  => l('Image'),
        'html' => l('HTML Code'),
    ];
}

/**
 * @return array
 * @throws NagVisException
 */
function listGadgets()
{
    global $CORE;
    return $CORE->getAvailableGadgets();
}

/**
 * @return string[]
 */
function listViewTypesContainer()
{
    return ['inline', 'iframe'];
}

/**
 * @return string[]
 */
function listViewTypesObj()
{
    return ['icon', 'line', 'gadget'];
}

/**
 * @return string[]
 */
function listViewTypes()
{
    return ['icon', 'line'];
}

/**
 * @return array
 */
function listDynGroupTypes()
{
    return [
        'host'    => l('Hosts'),
        'service' => l('Services')
    ];
}

/**
 * @return array
 */
function listZoomFactors()
{
    return [
        10     => ' 10%',
        25     => ' 25%',
        50     => ' 50%',
        75     => ' 75%',
        100    => '100%',
        125    => '125%',
        150    => '150%',
        200    => '200%',
        'fill' => l('Fill screen'),
    ];
}

/**
 * @param string $type
 * @param GlobalMapCfg $MAPCFG
 * @param string $objId
 * @param array $attrs
 * @return array|string[]
 * @throws NagVisException
 */
function getObjectNames($type, $MAPCFG, $objId, $attrs)
{
    global $_BACKEND;
    $backendIds = false;
    if (isset($attrs['backend_id']) && $attrs['backend_id'] != '') {
        $backendIds = $attrs['backend_id'];
    } elseif ($objId !== null) {
        $backendIds = $MAPCFG->getValue($objId, 'backend_id');
    }
    if (!$backendIds) {
        $backendIds = $MAPCFG->getValue(0, 'backend_id');
    }

    // Return simply nothing when a user just choosen to insert multiple backends
    if (isset($attrs['backend_id']) && $attrs['backend_id'] == ['<<<other>>>']) {
        return [];
    }

    // Initialize the backend
    foreach ($backendIds as $backendId) {
        $_BACKEND->checkBackendExists($backendId, true);
        $_BACKEND->checkBackendFeature($backendId, 'getObjects', true);
    }

    $name1 = '';
    if ($type === 'service') {
        if (isset($attrs['host_name']) && $attrs['host_name'] != '') {
            $name1 = $attrs['host_name'];
        } else {
            $name1 = $MAPCFG->getValue($objId, 'host_name');
        }

        if ($name1 == '') {
            return [];
        }
    }

    // Read all objects of the requested type from the backend
    $ret = ['' => ''];
    foreach ($backendIds as $backendId) {
        $objs = $_BACKEND->getBackend($backendId)->getObjects($type, $name1, '');
        foreach ($objs as $obj) {
            if ($type !== 'service') {
                $ret[$obj['name1']] = $obj['name1'];
            } else {
                $ret[$obj['name2']] = $obj['name2'];
            }
        }
    }

    natcasesort($ret);
    return $ret;
}

/**
 * @param GlobalMapCfg $MAPCFG
 * @param string $objId
 * @param array $attrs
 * @return array|string[]
 * @throws NagVisException
 */
function listHostNames($MAPCFG, $objId, $attrs)
{
    return getObjectNames('host', $MAPCFG, $objId, $attrs);
}

/**
 * @param GlobalMapCfg $MAPCFG
 * @param string $objId
 * @param array $attrs
 * @return array|string[]
 * @throws NagVisException
 */
function listHostgroupNames($MAPCFG, $objId, $attrs)
{
    return getObjectNames('hostgroup', $MAPCFG, $objId, $attrs);
}

/**
 * @param GlobalMapCfg $MAPCFG
 * @param string $objId
 * @param array $attrs
 * @return array|string[]
 * @throws NagVisException
 */
function listServiceNames($MAPCFG, $objId, $attrs)
{
    return getObjectNames('service', $MAPCFG, $objId, $attrs);
}

/**
 * @param GlobalMapCfg $MAPCFG
 * @param string $objId
 * @param array $attrs
 * @return array|string[]
 * @throws NagVisException
 */
function listServicegroupNames($MAPCFG, $objId, $attrs)
{
    return getObjectNames('servicegroup', $MAPCFG, $objId, $attrs);
}

/**
 * @param GlobalMapCfg $MAPCFG
 * @param string $objId
 * @param array $attrs
 * @return array|string[]
 * @throws NagVisException
 */
function listAggrNames($MAPCFG, $objId, $attrs)
{
    return getObjectNames('aggr', $MAPCFG, $objId, $attrs);
}

/**
 * @return array
 */
function listTemplateNames()
{
    return [];
}

/**
 * @param GlobalMapCfg $MAPCFG
 * @param string $objId
 * @param array $attrs
 * @return array|string[]
 * @throws NagVisException
 */
function listShapes($MAPCFG, $objId, $attrs)
{
    global $CORE;
    // Return simply nothing when a user just choosen to insert "other" icon
    if (isset($attrs['icon']) && $attrs['icon'] == '<<<other>>>') {
        return [];
    }
    return $CORE->getAvailableShapes();
}

/**
 * @param GlobalMapCfg $MAPCFG
 * @param string $objId
 * @param array $attrs
 * @return array|string[]
 */
function listSources($MAPCFG, $objId, $attrs)
{
    global $CORE;
    // Return simply nothing when a user just choosen to insert "other" sources
    if (isset($attrs['sources']) && $attrs['sources'] == '<<<other>>>') {
        return [];
    }
    return $CORE->getSelectableSources();
}

$mapConfigVars = [
    'type' => [
        'must'       => 0,
        'match'      => MATCH_OBJECTTYPE,
        'field_type' => 'hidden',
    ],
    'object_id' => [
        'must'       => 0,
        'match'      => MATCH_OBJECTID,
        'field_type' => 'readonly',
    ],
    'map_image' => [
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_PNG_GIF_JPG_FILE_OR_URL_NONE,
        'field_type' => 'dropdown',
        'list'       => 'listMapImages',
    ],
    'alias' => [
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_STRING
    ],
    'sources' => [
        'must'       => 0,
        'default'    => [],
        'array'      => true,
        'other'      => true,
        'match'      => MATCH_STRING,
        'field_type' => 'hidden',
        'list'       => 'listSources',
    ],
    'backend_id' => [
        'must'       => 0,
        'default'    => cfg('defaults', 'backend'),
        'match'      => MATCH_BACKEND_ID,
        'array'      => true,
        'other'      => true,
        'field_type' => 'dropdown',
        'list'       => 'listBackendIds',
    ],
    'background_color' => [
        'must'       => 0,
        'default'    => cfg('defaults', 'backgroundcolor'),
        'field_type' => 'color',
        'match'      => MATCH_COLOR
    ],
    'default_params' => [
        'must'       => 0,
        'default'    => '',
        'deprecated' => true,
        'match'      => MATCH_STRING_URL_EMPTY,
        'field_type' => 'hidden',
    ],
    'parent_map' => [
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_MAP_NAME_EMPTY,
        'field_type' => 'dropdown',
        'list'       => 'listMapNames',
    ],

    'context_menu' => [
        'must'       => 0,
        'default'    => cfg('defaults', 'contextmenu'),
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ],
    'context_template' => [
        'must'          => 0,
        'default'       => cfg('defaults', 'contexttemplate'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'context_menu',
        'depends_value' => '1',
        'list'          => 'listContextTemplates',
    ],

    'event_on_load' => [
        'must'       => 0,
        'editable'   => 1,
        'default'    => cfg('defaults', 'event_on_load'),
        'field_type' => 'boolean',
        'match'      => MATCH_BOOLEAN,
    ],
    'event_repeat_interval' => [
        'must'       => 0,
        'editable'   => 1,
        'default'    => cfg('defaults', 'event_repeat_interval'),
        'match'      => MATCH_INTEGER,
    ],
    'event_repeat_duration' => [
        'must'       => 0,
        'editable'   => 1,
        'default'    => cfg('defaults', 'event_repeat_duration'),
        'match'      => MATCH_INTEGER_PRESIGN,
    ],

    'event_background' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventbackground'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],

    'event_highlight' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventhighlight'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'event_highlight_interval' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventhighlightinterval'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_highlight',
        'depends_value' => '1'
    ],
    'event_highlight_duration' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventhighlightduration'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_highlight',
        'depends_value' => '1'
    ],

    'event_log' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventlog'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'event_log_level' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventloglevel'),
        'match' => MATCH_STRING_NO_SPACE,
        'depends_on' => 'event_log',
        'depends_value' => '1'
    ],
    'event_log_events' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventlogevents'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_log',
        'depends_value' => '1'
    ],
    'event_log_height' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventlogheight'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_log',
        'depends_value' => '1'
    ],
    'event_log_hidden' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventloghidden'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean',
        'depends_on' => 'event_log',
        'depends_value' => '1'
    ],

    'event_scroll' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventscroll'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'event_sound' => [
        'must' => 0,
        'default' => cfg('defaults', 'eventsound'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],

    'exclude_members' => [
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_REGEX,
    ],
    'exclude_member_states' => [
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_REGEX,
    ],

    'grid_show' => [
        'must' => 0,
        'default' => intval(cfg('wui', 'grid_show')),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'grid_color' => [
        'must'          => 0,
        'default'       => cfg('wui', 'grid_color'),
        'match'         => MATCH_COLOR,
        'field_type'    => 'color',
        'depends_on'    => 'grid_show',
        'depends_value' => '1'
    ],
    'grid_steps' => [
        'must' => 0,
        'default' => intval(cfg('wui', 'grid_steps')),
        'match' => MATCH_INTEGER,
        'depends_on' => 'grid_show',
        'depends_value' => '1'
    ],

    'header_menu' => [
        'must' => 0,
        'default'        => cfg('defaults', 'headermenu'),
        'match'          => MATCH_BOOLEAN,
        'field_type'     => 'boolean'
    ],
    'header_template' => [
        'must'           => 0,
        'default'        => cfg('defaults', 'headertemplate'),
        'match'          => MATCH_STRING_NO_SPACE,
        'field_type'     => 'dropdown',
        'depends_on'     => 'header_menu',
        'depends_value'  => '1',
        'list'           => 'listHeaderTemplates'
    ],
    'header_fade' => [
        'must'           => 0,
        'default'        => cfg('defaults', 'headerfade'),
        'match'          => MATCH_BOOLEAN,
        'field_type'     => 'hidden',
        'depends_on'     => 'header_menu',
        'deprecated'     => true,
        'depends_value'  => '1'
    ],

    'hover_menu' => [
        'must' => 0,
        'default' => cfg('defaults', 'hovermenu'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'hover_delay' => [
        'must' => 0,
        'default' => cfg('defaults', 'hoverdelay'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'hover_menu',
        'depends_value' => '1'
    ],
    'hover_template' => [
        'must'          => 0,
        'default'       => cfg('defaults', 'hovertemplate'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'list'          => 'listHoverTemplates',
    ],
    'hover_url' => [
        'must'          => 0,
        'match'         => MATCH_STRING_URL,
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'default'       => '',
    ],
    'hover_childs_show' => [
        'must' => 0,
        'default' => cfg('defaults', 'hoverchildsshow'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean',
        'depends_on' => 'hover_menu',
        'depends_value' => '1'
    ],
    'hover_childs_limit' => [
        'must' => 0,
        'default' => cfg('defaults', 'hoverchildslimit'),
        'match' => MATCH_INTEGER_PRESIGN,
        'depends_on' => 'hover_menu',
        'depends_value' => '1'
    ],
    'hover_childs_order' => [
        'must'          => 0,
        'default'       => cfg('defaults', 'hoverchildsorder'),
        'match'         => MATCH_ORDER,
        'field_type'    => 'dropdown',
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'list'          => 'listHoverChildOrders',
    ],
    'hover_childs_sort' => [
        'must'          => 0,
        'default'       => cfg('defaults', 'hoverchildssort'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'list'          => 'listHoverChildSorters',
    ],

    'iconset' => [
        'must'          => 0,
        'default'       => cfg('defaults', 'icons'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'icon',
        'list'          => 'listIconsets',
    ],
    'icon_size' => [
        'must'          => 0,
        'default'       => cfg('defaults', 'icon_size'),
        'match'         => MATCH_INTEGER,
        'depends_on'    => 'view_type',
        'depends_value' => 'icon',
        'array'         => true,
    ],
    'line_type' => [
        'must'          => 0,
        'default'       => '11',
        'match'         => MATCH_LINE_TYPE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
        'list'          => 'listLineTypes',
    ],
    'line_color' => [
        'must'          => 0,
        'default'       => '#ffffff',
        'field_type'    => 'color',
        'match'         => MATCH_COLOR,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ],
    'line_color_border' => [
        'must'          => 0,
        'default'       => '#000000',
        'field_type'    => 'color',
        'match'         => MATCH_COLOR,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ],
    'line_cut' => [
        'must'          => 0,
        'default'       => '0.5',
        'match'         => MATCH_FLOAT,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ],
    'line_label_show' => [
        'must'          => 0,
        'default'       => '1',
        'match'         => MATCH_BOOLEAN,
        'field_type'    => 'boolean',
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ],
    'line_label_pos_in' => [
        'must'          => 0,
        'default'       => '0.5',
        'match'         => MATCH_FLOAT,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ],
    'line_label_pos_out' => [
        'must'          => 0,
        'default'       => '0.5',
        'match'         => MATCH_FLOAT,
        'depends_on'    => 'view_type',
        'depends_value' => 'line'
    ],
    'line_label_in' => [
        'must'          => 0,
        'default'       => 'in',
        'match'         => MATCH_STRING,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ],
    'line_label_out' => [
        'must'          => 0,
        'default'       => 'out',
        'match'         => MATCH_STRING,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ],
    'line_label_y_offset' => [
        'must'          => 0,
        'default'       => 2,
        'match'         => MATCH_INTEGER,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ],
    'line_width' => [
        'must'          => 0,
        'default'       => cfg('defaults', 'line_width'),
        'match'         => MATCH_INTEGER,
        'depends_on'    => 'view_type',
        'depends_value' => 'line'
    ],
    'line_weather_colors' => [
        'must'          => 0,
        'default'       => cfg('defaults', 'line_weather_colors'),
        'match'         => MATCH_WEATHER_COLORS,
        'depends_on'    => 'view_type',
        'depends_value' => 'line'
    ],

    'in_maintenance' => [
        'must'       => 0,
        'default'    => '0',
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ],

    'label_show' => [
        'must'       => 0,
        'default'    => cfg('defaults', 'label_show'),
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ],
    'label_text' => [
        'must'          => 0,
        'default'       => '[name]',
        'match'         => MATCH_ALL,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ],
    'label_x' => [
        'must'          => 0,
        'default'       => 'center',
        'match'         => MATCH_LABEL_X,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ],
    'label_y' => [
        'must'          => 0,
        'default'       => 'bottom',
        'match'         => MATCH_LABEL_Y,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ],
    'label_width' => [
        'must' => 0,
        'default' => 'auto',
        'match' => MATCH_TEXTBOX_WIDTH,
        'depends_on' => 'label_show',
        'depends_value' => '1'
    ],
    'label_background' => [
        'must'          => 0,
        'default'       => 'transparent',
        'field_type'    => 'color',
        'match'         => MATCH_COLOR,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ],
    'label_border' => [
        'must'          => 0,
        'default'       => '#e5e5e5',
        'field_type'    => 'color',
        'match'         => MATCH_COLOR,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ],
    'label_style' => [
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_STYLE,
        'depends_on' => 'label_show',
        'depends_value' => '1'
    ],
    'label_maxlen' => [
        'must'          => 0,
        'default'       => 0,
        'match'         => MATCH_INTEGER,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ],

    'only_hard_states' => [
        'must' => 0,
        'default' => cfg('defaults', 'onlyhardstates'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'recognize_services' => [
        'must' => 0,
        'default' => cfg('defaults', 'recognizeservices'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'show_in_lists' => [
        'must' => 0,
        'default' => cfg('defaults', 'showinlists'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'show_in_multisite' => [
        'must' => 0,
        'default'    => cfg('defaults', 'showinmultisite'),
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'ignore_linked_maps_summary_state' => [
        'must'       => 0,
        'default'    => '0',
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],
    'stylesheet' => [
        'must' => 0,
        'default' => cfg('defaults', 'stylesheet'),
        'match' => MATCH_STRING_NO_SPACE
    ],
    'url_target' => [
        'must' => 0,
        'default' => cfg('defaults', 'urltarget'),
        'match' => MATCH_STRING_NO_SPACE
    ],

    'x' => [
        'must' => 1,
        'match' => MATCH_COORDS_MULTI
    ],
    'y' => [
        'must' => 1,
        'match' => MATCH_COORDS_MULTI
    ],
    'z' => [
        'must' => 0,
        'default' => 10,
        'match' => MATCH_INTEGER
    ],
    'view_type' => [
        'must'          => 0,
        'default'       => 'icon',
        'match'         => MATCH_VIEW_TYPE,
        'field_type'    => 'dropdown',
        'list'          => 'listViewTypes',
    ],
    'view_type_obj' => [
        'must'          => 0,
        'default'       => 'icon',
        'match'         => MATCH_VIEW_TYPE_OBJ,
        'field_type'    => 'dropdown',
        'list'          => 'listViewTypesObj',
    ],
    'url' => [
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_URL_EMPTY,
    ],
    'url_mandatory' => [
        'must' => 1,
        'default' => '',
        'match' => MATCH_STRING_URL,
    ],
    'use' => [
        'must'    => 0,
        'default' => [],
        'array'   => true,
        'match'   => MATCH_STRING_NO_SPACE,
    ],

    'gadget_url' => [
        'must'          => 0,
        'match'         => MATCH_STRING_URL,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'gadget',
        'default'       => '',
        'list'          => 'listGadgets',
    ],
    'gadget_type' => [
        'must'          => 0,
        'match'         => MATCH_GADGET_TYPE,
        'depends_on'    => 'view_type',
        'depends_value' => 'gadget',
        'default'       => 'img',
        'list'          => 'listGadgetTypes',
        'field_type'    => 'dropdown',
        'deprecated'    => true,
    ],
    'gadget_scale' => [
        'must' => 0,
        'default' => 100,
        'match' => MATCH_INTEGER,
        'depends_on' => 'view_type',
        'depends_value' => 'gadget',
    ],
    'gadget_opts' => [
        'must' => 0,
        'default' => '',
        'match' => MATCH_GADGET_OPT,
        'depends_on' => 'view_type',
        'depends_value' => 'gadget',
    ],

    // GLOBAL SPECIFIC OPTIONS
    'zoom' => [
        'must'          => 0,
        'default'       => 100,
        'match'         => MATCH_ZOOM_FACTOR,
        'field_type'    => 'dropdown',
        'list'          => 'listZoomFactors',
    ],
    'zoombar' => [
        'must' => 0,
        'default'    => cfg('defaults', 'zoombar'),
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ],

    // HOST SPECIFIC OPTIONS

    'host_name' => [
        'must' => 1,
        'match' => MATCH_STRING,
        'field_type' => 'dropdown',
        'list' => 'listHostNames',
    ],
    'host_url' => [
        'must'    => 0,
        'default' => cfg('defaults', 'hosturl'),
        'match'   => MATCH_STRING_URL_EMPTY,
    ],

    // HOSTGROUP SPECIFIC OPTIONS

    'hostgroup_name' => [
        'must' => 1,
        'match' => MATCH_STRING,
        'field_type' => 'dropdown',
        'list' => 'listHostgroupNames',
    ],
    'hostgroup_url' => [
        'must' => 0,
        'default' => cfg('defaults', 'hostgroupurl'),
        'match' => MATCH_STRING_URL_EMPTY
    ],

    // SERVICE SPECIFIC OPTIONS

    'service_description' => [
        'must'       => 1,
        'match'      => MATCH_SERVICE_DESCRIPTION,
        'field_type' => 'dropdown',
        'list'       => 'listServiceNames',
    ],
    'service_label_text' => [
        'must'          => 0,
        'default'       => '[service_description]',
        'match'         => MATCH_ALL,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ],
    'service_url' => [
        'must' => 0,
        'default' => cfg('defaults', 'serviceurl'),
        'match'  => MATCH_STRING_URL_EMPTY,
    ],

    // SERVICEGROUP SPECIFIC OPTIONS

    'servicegroup_name' => [
        'must'       => 1,
        'match'      => MATCH_STRING,
        'field_type' => 'dropdown',
        'list'       => 'listServicegroupNames',
    ],
    'servicegroup_url' => [
        'must'    => 0,
        'default' => cfg('defaults', 'servicegroupurl'),
        'match'   => MATCH_STRING_URL_EMPTY,
    ],

    // MAP SPECIFIC OPTIONS

    'map_name' => [
        'must'       => 1,
        'match'      => MATCH_STRING_NO_SPACE,
        'field_type' => 'dropdown',
        'list'       => 'listMapNames',
    ],
    'map_url' => [
        'must' => 0,
        'default' => cfg('defaults', 'mapurl'),
        'match' => MATCH_STRING_URL_EMPTY,
    ],

    // TEXTBOX SPECIFIC OPTIONS

    'text' => [
        'must' => 1,
        'match' => MATCH_ALL,
        'field_type'    => 'textarea',
    ],
    'border_color' => [
        'must'       => 0,
        'default'    => '#e5e5e5',
        'field_type' => 'color',
        'match'      => MATCH_COLOR,
    ],
    'style' => [
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_STYLE,
    ],
    'h' => [
        'must'    => 0,
        'default' => 'auto',
        'match'   => MATCH_TEXTBOX_HEIGHT,
    ],
    'w' => [
        'must'    => 0,
        'default' => 'auto',
        'match'   => MATCH_TEXTBOX_WIDTH,
    ],
    'textbox_z' => [
        'must'    => 0,
        'default' => 5,
        'match'   => MATCH_INTEGER
    ],

    // SHAPE SPECIFIC OPTIONS

    'icon' => [
        'must'       => 1,
        'match'      => MATCH_PNG_GIF_JPG_FILE_OR_URL,
        'field_type' => 'dropdown',
        'other'      => true,
        'list'       => 'listShapes',
    ],
    'enable_refresh' => [
        'must'       => 0,
        'default'    => 0,
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ],
    'shape_z' => [
        'must'       => 0,
        'default'    => 1,
        'match'      => MATCH_INTEGER
    ],

    // TEMPLATE SPECIFIC OPTIONS

    'name' => [
        'must'  => 1,
        'match' => MATCH_STRING_NO_SPACE,
        'list'  => 'listTemplateNames',
    ],

    // STATELESS LINE SPECIFIC OPTIONS

    'view_type_line' => [
        'must'          => 1,
        'default'       => 'line',
        'match'         => MATCH_VIEW_TYPE,
        'field_type'    => 'hidden',
    ],

    'line_type_line' => [
        'must'          => 0,
        'default'       => '11',
        'match'         => MATCH_LINE_TYPE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
        'list'          => 'listStatelessLineTypes',
    ],

    // CONTAINER SPECIFIC OPTIONS

    'view_type_container' => [
        'must'          => 0,
        'default'       => 'inline',
        'match'         => MATCH_VIEW_TYPE_CONTAINER,
        'field_type'    => 'dropdown',
        'list'          => 'listViewTypesContainer',
    ],

    // DYNAMIC GROUP SPECIFIC OPTIONS

    'dyngroup_name' => [
        'must'       => 1,
        'match'      => MATCH_STRING,
        'default'    => '',
    ],
    'object_types' => [
        'must'       => 1,
        'default'    => '',
        'field_type' => 'dropdown',
        'match'      => MATCH_DYN_GROUP_TYPES,
        'list'       => 'listDynGroupTypes',
    ],
    'object_filter' => [
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_LIVESTATUS_FILTER,
    ],
    'dyngroup_url' => [
        'must'       => 0,
        'default'    => cfg('defaults', 'dyngroupurl'),
        'match'      => MATCH_STRING_URL_EMPTY,
    ],

    // AGGREGATION SPECIFIC

    'aggr_name' => [
        'must'       => 1,
        'match'      => MATCH_STRING,
        'field_type' => 'dropdown',
        'list'       => 'listAggrNames',
    ],
    'aggr_url' => [
        'must'       => 0,
        'default'    => cfg('defaults', 'aggrurl'),
        'match'      => MATCH_STRING_URL_EMPTY,
    ],
];

// STATELESS LINE SPECIFIC OPTIONS
$mapConfigVars['hover_menu_line'] = $mapConfigVars['hover_menu'];
$mapConfigVars['hover_menu_line']['default'] = '0';

//
// map configuration variable registration
//

$mapConfigVarMap['global'] = [
    'general' => [
        'sources' => null,
        'alias' => null,
        'parent_map' => null,
        'in_maintenance' => null,
        'show_in_lists' => null,
        'show_in_multisite' => null,
        'ignore_linked_maps_summary_state' => null,
    ],
    'appearance' => [
        'map_image' => null,
        'background_color' => null,
        'grid_show' => null,
        'grid_color' => null,
        'grid_steps' => null,
        'header_menu' => null,
        'header_template' => null,
        'header_fade' => null,
        'stylesheet' => null,
        'zoom' => null,
        'zoombar' => null,
    ],
    'object_defaults' => [
        'backend_id' => null,
        'context_menu' => null,
        'context_template' => null,
        'exclude_members' => null,
        'exclude_member_states' => null,
        'hover_menu' => null,
        'hover_delay' => null,
        'hover_template' => null,
        'hover_childs_show' => null,
        'hover_childs_limit' => null,
        'hover_childs_order' => null,
        'hover_childs_sort' => null,
        'iconset' => null,
        'icon_size' => null,
        'line_type' => null,
        'line_color' => null,
        'line_cut' => null,
        'line_width' => null,
        'line_weather_colors' => null,
        'label_show' => null,
        'label_text' => null,
        'label_x' => null,
        'label_y' => null,
        'label_width' => null,
        'label_background' => null,
        'label_border' => null,
        'label_style' => null,
        'label_maxlen' => null,
        'only_hard_states' => null,
        'recognize_services' => null,
        'url_target' => null,
        'host_url' => null,
        'hostgroup_url' => null,
        'service_url' => null,
        'servicegroup_url' => null,
        'map_url' => null,
        'dyngroup_url' => null,
        'aggr_url' => null,
    ],
    'events' => [
        'event_on_load' => null,
        'event_repeat_interval' => null,
        'event_repeat_duration' => null,
        'event_background' => null,
        'event_highlight' => null,
        'event_highlight_interval' => null,
        'event_highlight_duration' => null,
        'event_log' => null,
        'event_log_level' => null,
        'event_log_events' => null,
        'event_log_height' => null,
        'event_log_hidden' => null,
        'event_scroll' => null,
        'event_sound' => null,
    ],
    'hidden' => [
        'type' => null,
        'object_id' => null,
        'default_params' => null,
    ],
];

$mapConfigVarMap['host'] = [
    'general' => [
        'host_name' => null,
        'backend_id' => null,
        'x' => null,
        'y' => null,
        'z' => null,
        'object_id' => null,
    ],
    'appearance' => [
        'view_type_obj' => 'view_type',
        'iconset' => null,
        'icon_size' => null,
        'line_type' => null,
        'line_cut' => null,
        'line_width' => null,
        'line_weather_colors' => null,
        'gadget_url' => null,
        'gadget_type' => null,
        'gadget_scale' => null,
        'gadget_opts' => null,
    ],
    'state' => [
        'exclude_members' => null,
        'exclude_member_states' => null,
        'only_hard_states' => null,
        'recognize_services' => null,
    ],
    'actions' => [
        'context_menu' => null,
        'context_template' => null,
        'hover_menu' => null,
        'hover_delay' => null,
        'hover_template' => null,
        'hover_url' => null,
        'hover_childs_show' => null,
        'hover_childs_sort' => null,
        'hover_childs_order' => null,
        'hover_childs_limit' => null,
        'host_url' => 'url',
        'url_target' => null,
    ],
    'label' => [
        'label_show' => null,
        'label_text' => null,
        'label_x' => null,
        'label_y' => null,
        'label_width' => null,
        'label_background' => null,
        'label_border' => null,
        'label_style' => null,
        'label_maxlen' => null,
    ],
    'hidden' => [
        'type' => null,
        'use' => null,
    ],
];

$mapConfigVarMap['hostgroup'] = [
    'general' => [
        'hostgroup_name' => null,
        'backend_id' => null,
        'x' => null,
        'y' => null,
        'z' => null,
        'object_id' => null,
    ],
    'appearance' => [
        'view_type_obj' => 'view_type',
        'iconset' => null,
        'icon_size' => null,
        'line_type' => null,
        'line_cut' => null,
        'line_width' => null,
        'line_weather_colors' => null,
        'gadget_url' => null,
        'gadget_type' => null,
        'gadget_scale' => null,
        'gadget_opts' => null,
    ],
    'state' => [
        'exclude_members' => null,
        'exclude_member_states' => null,
        'only_hard_states' => null,
        'recognize_services' => null,
    ],
    'actions' => [
        'context_menu' => null,
        'context_template' => null,
        'hover_menu' => null,
        'hover_delay' => null,
        'hover_template' => null,
        'hover_url' => null,
        'hover_childs_show' => null,
        'hover_childs_sort' => null,
        'hover_childs_order' => null,
        'hover_childs_limit' => null,
        'hostgroup_url' => 'url',
        'url_target' => null,
    ],
    'label' => [
        'label_show' => null,
        'label_text' => null,
        'label_x' => null,
        'label_y' => null,
        'label_width' => null,
        'label_background' => null,
        'label_border' => null,
        'label_style' => null,
        'label_maxlen' => null,
    ],
    'hidden' => [
        'type' => null,
        'use' => null,
    ],
];

$mapConfigVarMap['service'] = [
    'general' => [
        'host_name' => null,
        'service_description' => null,
        'backend_id' => null,
        'x' => null,
        'y' => null,
        'z' => null,
        'object_id' => null,
    ],
    'appearance' => [
        'view_type_obj' => 'view_type',
        'iconset' => null,
        'icon_size' => null,
        'line_type' => null,
        'line_cut' => null,
        'line_label_show' => null,
        'line_label_in' => null,
        'line_label_out' => null,
        'line_label_pos_in' => null,
        'line_label_pos_out' => null,
        'line_label_y_offset' => null,
        'line_width' => null,
        'line_weather_colors' => null,
        'gadget_url' => null,
        'gadget_type' => null,
        'gadget_scale' => null,
        'gadget_opts' => null,

    ],
    'state' => [
        'only_hard_states' => null,
    ],
    'actions' => [
        'context_menu' => null,
        'context_template' => null,
        'hover_menu' => null,
        'hover_template' => null,
        'hover_delay' => null,
        'hover_url' => null,
        'hover_childs_show' => null,
        'hover_childs_sort' => null,
        'hover_childs_order' => null,
        'hover_childs_limit' => null,
        'service_url' => 'url',
        'url_target' => null,
    ],
    'label' => [
        'label_show' => null,
        'service_label_text' => 'label_text',
        'label_x' => null,
        'label_y' => null,
        'label_width' => null,
        'label_background' => null,
        'label_border' => null,
        'label_style' => null,
        'label_maxlen' => null,
    ],
    'hidden' => [
        'type' => null,
        'use' => null,
    ],
];

$mapConfigVarMap['servicegroup'] = [
    'general' => [
        'servicegroup_name' => null,
        'backend_id' => null,
        'x' => null,
        'y' => null,
        'z' => null,
        'object_id' => null,
    ],
    'appearance' => [
        'view_type_obj' => 'view_type',
        'iconset' => null,
        'icon_size' => null,
        'line_type' => null,
        'line_cut' => null,
        'line_width' => null,
        'line_weather_colors' => null,
        'gadget_url' => null,
        'gadget_type' => null,
        'gadget_scale' => null,
        'gadget_opts' => null,
    ],
    'state' => [
        // FIXME: exclude_members / exclude_member_states ?
        'only_hard_states' => null,
    ],
    'actions' => [
        'context_menu' => null,
        'context_template' => null,
        'exclude_members' => null,
        'exclude_member_states' => null,
        'hover_menu' => null,
        'hover_delay' => null,
        'hover_template' => null,
        'hover_url' => null,
        'hover_childs_show' => null,
        'hover_childs_sort' => null,
        'hover_childs_order' => null,
        'hover_childs_limit' => null,
        'servicegroup_url' => 'url',
        'url_target' => null,
    ],
    'label' => [
        'label_show' => null,
        'label_text' => null,
        'label_x' => null,
        'label_y' => null,
        'label_width' => null,
        'label_background' => null,
        'label_border' => null,
        'label_style' => null,
        'label_maxlen' => null,
    ],
    'hidden' => [
        'type' => null,
        'use' => null,
    ],
];

$mapConfigVarMap['map'] = [
    'general' => [
        'map_name'              => null,
        'x'                     => null,
        'y'                     => null,
        'z'                     => null,
        'object_id'             => null,
    ],
    'appearance' => [
        'view_type_obj'         => 'view_type',
        'iconset'               => null,
        'icon_size'             => null,
        'line_type'             => null,
        'line_cut'              => null,
        'line_width'            => null,
        'line_weather_colors'   => null,
        'gadget_url'            => null,
        'gadget_type'           => null,
        'gadget_scale'          => null,
        'gadget_opts'           => null,
    ],
    'state' => [
        'exclude_members'       => null,
        'exclude_member_states' => null,
        'only_hard_states'      => null,
    ],
    'actions' => [
        'context_menu'          => null,
        'context_template'      => null,
        'hover_menu'            => null,
        'hover_template'        => null,
        'hover_delay'           => null,
        'hover_url'             => null,
        'hover_childs_show'     => null,
        'hover_childs_sort'     => null,
        'hover_childs_order'    => null,
        'hover_childs_limit'    => null,
        'map_url'               => 'url',
        'url_target'            => null,
    ],
    'label' => [
        'label_show'            => null,
        'label_text'            => null,
        'label_x'               => null,
        'label_y'               => null,
        'label_width'           => null,
        'label_background'      => null,
        'label_border'          => null,
        'label_style'           => null,
        'label_maxlen'          => null,
    ],
    'hidden' => [
        'type'                  => null,
        'use'                   => null,
    ],
];

$mapConfigVarMap['line'] = [
    'general' => [
        'x' => null,
        'y' => null,
        'z' => null,
        'object_id' => null,
    ],
    'appearance' => [
        'view_type_line'    => 'view_type',
        'line_type_line'    => 'line_type',
        'line_cut'          => null,
        'line_width'        => null,
        'line_color'        => null,
        'line_color_border' => null,
    ],
    'actions' => [
        'context_menu'      => null,
        'context_template'  => null,
        'hover_menu_line'   => 'hover_menu',
        'hover_template'    => null,
        'hover_url'         => null,
        'hover_delay'       => null,
        'url'               => null,
        'url_target'        => null,
    ],
    'hidden' => [
        'type' => null,
        'use'  => null,
    ],
];

$mapConfigVarMap['textbox'] = [
    'general' => [
        'text' => null,
        'x' => null,
        'y' => null,
        'textbox_z' => 'z',
        'w' => null,
        'h' => null,
        'object_id' => null,
    ],
    'appearance' => [
        'style' => null,
        'background_color' => null,
        'border_color' => null,
    ],
    'actions' => [
        'context_menu' => null,
        'context_template' => null,

    ],
    'hidden' => [
        'type' => null,
        'use' => null,
    ],
    // See also: core/sources/worldmap.php (textbox-specific options)
];

$mapConfigVarMap['shape'] = [
    'general' => [
        'icon' => null,
        'x' => null,
        'y' => null,
        'shape_z' => 'z',
        'enable_refresh' => null,
        'object_id' => null,
    ],
    'actions' => [
        'context_menu' => null,
        'context_template' => null,
        'hover_menu' => null,
        'hover_url' => null,
        'hover_delay' => null,
        'url' => null,
        'url_target' => null,
    ],
    'hidden' => [
        'type' => null,
        'use' => null,
    ],
];

$mapConfigVarMap['template'] = [
    'general' => [
        'name' => null,
        'object_id' => null,
    ],
    'hidden' => [
        'type' => null,
        'use' => null,
    ],
];

$mapConfigVarMap['container'] = [
    'general' => [
        'url_mandatory' => 'url',
        'view_type_container' => 'view_type',
        'enable_refresh' => null,
        'x' => null,
        'y' => null,
        'textbox_z' => 'z',
        'w' => null,
        'h' => null,
        'object_id' => null,
    ],
    'appearance' => [
        'style' => null,
        'background_color' => null,
        'border_color' => null,
    ],
    'actions' => [
        'context_menu' => null,
        'context_template' => null,
    ],
    'hidden' => [
        'type' => null,
        'use' => null,
    ],
];

$mapConfigVarMap['dyngroup'] = [
    'general' => [
        'dyngroup_name' => 'name',
        'backend_id'    => null,
        'object_types'  => null,
        'object_filter' => null,
        'x'             => null,
        'y'             => null,
        'z'             => null,
        'object_id'     => null,
    ],
    'appearance' => [
        'view_type_obj' => 'view_type',
        'iconset' => null,
        'icon_size' => null,
        'line_type' => null,
        'line_cut' => null,
        'line_width' => null,
        'line_weather_colors' => null,
        'gadget_url' => null,
        'gadget_type' => null,
        'gadget_scale' => null,
        'gadget_opts' => null,
    ],
    'state' => [
        'only_hard_states' => null,
        'recognize_services' => null,
    ],
    'actions' => [
        'context_menu' => null,
        'context_template' => null,
        'exclude_members' => null,
        'exclude_member_states' => null,
        'hover_menu' => null,
        'hover_delay' => null,
        'hover_template' => null,
        'hover_url' => null,
        'hover_childs_show' => null,
        'hover_childs_sort' => null,
        'hover_childs_order' => null,
        'hover_childs_limit' => null,
        'dyngroup_url' => 'url',
        'url_target' => null,
    ],
    'label' => [
        'label_show' => null,
        'label_text' => null,
        'label_x' => null,
        'label_y' => null,
        'label_width' => null,
        'label_background' => null,
        'label_border' => null,
        'label_style' => null,
        'label_maxlen' => null,
    ],
    'hidden' => [
        'type'          => null,
        'use' => null,
    ],
];

$mapConfigVarMap['aggr'] = [
    'general' => [
        'aggr_name'             => 'name',
        'backend_id'            => null,
        'x'                     => null,
        'y'                     => null,
        'z'                     => null,
        'object_id'             => null,
    ],
    'appearance' => [
        'view_type_obj'         => 'view_type',
        'iconset'               => null,
        'icon_size'             => null,
        'line_type'             => null,
        'line_cut'              => null,
        'line_width'            => null,
        'line_weather_colors'   => null,
        'gadget_url'            => null,
        'gadget_type'           => null,
        'gadget_scale'          => null,
        'gadget_opts'           => null,
    ],
    'state' => [
        'exclude_members'       => null,
        'exclude_member_states' => null,
        'only_hard_states'      => null,
    ],
    'actions' => [
        'context_menu'          => null,
        'context_template'      => null,
        'hover_menu'            => null,
        'hover_delay'           => null,
        'hover_template'        => null,
        'hover_url'             => null,
        'hover_childs_show'     => null,
        'hover_childs_sort'     => null,
        'hover_childs_order'    => null,
        'hover_childs_limit'    => null,
        'aggr_url'              => 'url',
        'url_target'            => null,
    ],
    'label' => [
        'label_show'            => null,
        'label_text'            => null,
        'label_x'               => null,
        'label_y'               => null,
        'label_width'           => null,
        'label_background'      => null,
        'label_border'          => null,
        'label_style'           => null,
        'label_maxlen'          => null,

    ],
    'hidden' => [
        'type'                  => null,
        'use'                   => null,
    ],
];
