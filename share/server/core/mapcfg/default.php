<?php

function listMapNames() {
    global $CORE, $AUTHORISATION;
    $list = Array();
    $maps = $CORE->getAvailableMaps();
    foreach($maps AS $key => $val)
        if($AUTHORISATION->isPermitted('Map', 'view', $val))
            $list[$key] = $val;

    return $list;
}

function listMapImages() {
    global $CORE;
    $options = $CORE->getAvailableBackgroundImages();
    array_unshift($options, 'none');
    return $options;
}

function listLineTypes() {
    return Array(
        '10' => '-------><-------',
        '11' => '--------------->',
        '12' => '----------------',
        '13' => '---%---><---%---',
        '14' => '--%+BW-><-%+BW--',
        '15' => '---BW--><--BW---',
    );
}

function listLineArrows() {
    return Array(
        'forward' => '------->',
        'back'    => '<-------',
        'both'    => '<------>',
        'none'    => '--------',
    );
}

function listGadgetTypes() {
    return Array(
        'img'  => l('Image'),
        'html' => l('HTML Code'),
    );
}

function listGadgets() {
    global $CORE;
    return $CORE->getAvailableGadgets();
}

function listViewTypesContainer() {
    return Array('inline', 'iframe');
}

function listViewTypesObj() {
    return Array('icon', 'line', 'gadget');
}

function listViewTypes() {
    return Array('icon', 'line');
}

function listDynGroupTypes() {
    return Array(
        'host'    => l('Hosts'),
        'service' => l('Services')
    );
}

function listZoomFactors() {
    return Array(
        10     => ' 10%',
        25     => ' 25%',
        50     => ' 50%',
        75     => ' 75%',
        100    => '100%',
        125    => '125%',
        150    => '150%',
        200    => '200%',
        'fill' => l('Fill screen'),
    );
}


function getObjectNames($type, $MAPCFG, $objId, $attrs) {
    global $_BACKEND;
    if(isset($attrs['backend_id']) && $attrs['backend_id'] != '')
        $backendIds = explode(',', $attrs['backend_id']);
    else
        $backendIds = $MAPCFG->getValue($objId, 'backend_id');

    // Return simply nothing when a user just choosen to insert multiple backends
    if(isset($attrs['backend_id']) && $attrs['backend_id'] == '<<<other>>>')
        return array();

    // Initialize the backend
    foreach($backendIds as $backendId) {
        $_BACKEND->checkBackendExists($backendId, true);
        $_BACKEND->checkBackendFeature($backendId, 'getObjects', true);
    }

    $name1 = '';
    if($type === 'service') {
        if(isset($attrs['host_name']) && $attrs['host_name'] != '')
            $name1 = $attrs['host_name'];
        else
            $name1 = $MAPCFG->getValue($objId, 'host_name');

        if($name1 == '')
            return Array();
    }

    // Read all objects of the requested type from the backend
    $aRet = Array();
    foreach($backendIds as $backendId) {
        $objs = $_BACKEND->getBackend($backendId)->getObjects($type, $name1, '');
        foreach($objs AS $obj) {
            if($type !== 'service')
                $aRet[] = $obj['name1'];
            else
                $aRet[] = $obj['name2'];
        }
    }

    natcasesort($aRet);
    return $aRet;
}

function listHostNames($MAPCFG, $objId, $attrs) {
    return getObjectNames('host', $MAPCFG, $objId, $attrs);
}

function listHostgroupNames($MAPCFG, $objId, $attrs) {
    return getObjectNames('hostgroup', $MAPCFG, $objId, $attrs);
}

function listServiceNames($MAPCFG, $objId, $attrs) {
    return getObjectNames('service', $MAPCFG, $objId, $attrs);
}

function listServicegroupNames($MAPCFG, $objId, $attrs) {
    return getObjectNames('servicegroup', $MAPCFG, $objId, $attrs);
}

function listAggrNames($MAPCFG, $objId, $attrs) {
    return getObjectNames('aggr', $MAPCFG, $objId, $attrs);
}

function listTemplateNames() {
    return Array();
}

function listShapes($MAPCFG, $objId, $attrs) {
    global $CORE;
    // Return simply nothing when a user just choosen to insert "other" icon
    if(isset($attrs['icon']) && $attrs['icon'] == '<<<other>>>')
        return array();
    return $CORE->getAvailableShapes();
}

function listSources($MAPCFG, $objId, $attrs) {
    global $CORE;
    // Return simply nothing when a user just choosen to insert "other" sources
    if(isset($attrs['sources']) && $attrs['sources'] == '<<<other>>>')
        return array();
    return $CORE->getSelectableSources();
}

$mapConfigVars = Array(
    'type' => Array(
        'must'       => 0,
        'match'      => MATCH_OBJECTTYPE,
        'field_type' => 'hidden',
    ),
    'object_id' => Array(
        'must'       => 0,
        'match'      => MATCH_OBJECTID,
        'field_type' => 'hidden',
    ),
    'map_image' => Array(
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_PNG_GIF_JPG_FILE_OR_URL_NONE,
        'field_type' => 'dropdown',
        'list'       => 'listMapImages',
    ),
    'alias' => Array(
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_STRING
    ),
    'sources' => Array(
        'must'       => 0,
        'default'    => array(),
        'array'      => true,
	'other'      => true,
        'match'      => MATCH_STRING,
        'field_type' => 'hidden',
        'list'       => 'listSources',
    ),
    'backend_id' => Array(
        'must'       => 0,
        'default'    => cfg('defaults', 'backend'),
        'match'      => MATCH_BACKEND_ID,
        'array'      => true,
	'other'      => true,
        'field_type' => 'dropdown',
        'list'       => 'listBackendIds',
    ),
    'background_color' => Array(
        'must'       => 0,
        'default'    => cfg('defaults', 'backgroundcolor'),
        'field_type' => 'color',
        'match'      => MATCH_COLOR),
    'default_params' => Array(
        'must'       => 0,
        'default'    => '',
        'deprecated' => true,
        'match'      => MATCH_STRING_URL_EMPTY,
        'field_type' => 'hidden',
    ),
    'parent_map' => Array(
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_MAP_NAME_EMPTY,
        'field_type' => 'dropdown',
        'list'       => 'listMapNames',
    ),

    'context_menu' => Array(
        'must'       => 0,
        'default'    => cfg('defaults', 'contextmenu'),
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ),
    'context_template' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'contexttemplate'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'context_menu',
        'depends_value' => '1',
        'list'          => 'listContextTemplates',
    ),

    'event_on_load' => Array(
        'must'       => 0,
        'editable'   => 1,
        'default'    => cfg('defaults', 'event_on_load'),
        'field_type' => 'boolean',
        'match'      => MATCH_BOOLEAN,
    ),
    'event_repeat_interval' => Array(
        'must'       => 0,
        'editable'   => 1,
        'default'    => cfg('defaults', 'event_repeat_interval'),
        'match'      => MATCH_INTEGER,
    ),
    'event_repeat_duration' => Array(
        'must'       => 0,
        'editable'   => 1,
        'default'    => cfg('defaults', 'event_repeat_duration'),
        'match'      => MATCH_INTEGER_PRESIGN,
    ),

    'event_background' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventbackground'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),

    'event_highlight' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventhighlight'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'event_highlight_interval' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventhighlightinterval'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_highlight',
        'depends_value' => '1'),
    'event_highlight_duration' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventhighlightduration'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_highlight',
        'depends_value' => '1'),

    'event_log' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventlog'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'event_log_level' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventloglevel'),
        'match' => MATCH_STRING_NO_SPACE,
        'depends_on' => 'event_log',
        'depends_value' => '1'),
    'event_log_events' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventlogevents'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_log',
        'depends_value' => '1'),
    'event_log_height' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventlogheight'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_log',
        'depends_value' => '1'),
    'event_log_hidden' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventloghidden'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean',
        'depends_on' => 'event_log',
        'depends_value' => '1'),

    'event_scroll' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventscroll'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'event_sound' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventsound'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),

    'exclude_members' => Array(
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_REGEX,
    ),
    'exclude_member_states' => Array(
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_REGEX,
    ),

    'grid_show' => Array(
        'must' => 0,
        'default' => intval(cfg('wui', 'grid_show')),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'grid_color' => Array(
        'must'          => 0,
        'default'       => cfg('wui', 'grid_color'),
        'match'         => MATCH_COLOR,
        'field_type'    => 'color',
        'depends_on'    => 'grid_show',
        'depends_value' => '1'),
    'grid_steps' => Array(
        'must' => 0,
        'default' => intval(cfg('wui', 'grid_steps')),
        'match' => MATCH_INTEGER,
        'depends_on' => 'grid_show',
        'depends_value' => '1'),

    'header_menu' => Array(
        'must' => 0,
        'default'        => cfg('defaults', 'headermenu'),
        'match'          => MATCH_BOOLEAN,
        'field_type'     => 'boolean'),
    'header_template' => Array(
        'must'           => 0,
        'default'        => cfg('defaults', 'headertemplate'),
        'match'          => MATCH_STRING_NO_SPACE,
        'field_type'     => 'dropdown',
        'depends_on'     => 'header_menu',
        'depends_value'  => '1',
        'list'           => 'listHeaderTemplates'),
    'header_fade' => Array(
        'must' => 0,
        'default'        => cfg('defaults', 'headerfade'),
        'match'          => MATCH_BOOLEAN,
        'field_type'     => 'boolean',
        'depends_on'     => 'header_menu',
        'depends_value'  => '1'),

    'hover_menu' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hovermenu'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'hover_delay' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hoverdelay'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'hover_menu',
        'depends_value' => '1'),
    'hover_template' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'hovertemplate'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'list'          => 'listHoverTemplates',
     ),
    'hover_url' => Array(
        'must'          => 0,
        'match'         => MATCH_STRING_URL,
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'default'       => '',
    ),
    'hover_childs_show' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hoverchildsshow'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean',
        'depends_on' => 'hover_menu',
        'depends_value' => '1'),
    'hover_childs_limit' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hoverchildslimit'),
        'match' => MATCH_INTEGER_PRESIGN,
        'depends_on' => 'hover_menu',
        'depends_value' => '1'),
    'hover_childs_order' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'hoverchildsorder'),
        'match'         => MATCH_ORDER,
        'field_type'    => 'dropdown',
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'list'          => 'listHoverChildOrders',
    ),
    'hover_childs_sort' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'hoverchildssort'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'list'          => 'listHoverChildSorters',
    ),

    'iconset' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'icons'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'icon',
        'list'          => 'listIconsets',
    ),
    'line_type' => Array(
        'must'          => 0,
        'default'       => '11',
        'match'         => MATCH_LINE_TYPE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
        'list'          => 'listLineTypes',
    ),
    // At the moment this value is only used for the automap to controll
    // the style of the connector arrow. But maybe this attribute can be
    // used on regular maps for line objects too.
    'line_arrow' => Array(
        'must'          => 0,
        'default'       => 'forward',
        'match'         => MATCH_LINE_ARROW,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
        'field_type'    => 'dropdown',
        'list'          => 'listLineArrows',
    ),
    'line_color' => Array(
        'must'          => 0,
        'default'       => '#ffffff',
        'field_type'    => 'color',
        'match'         => MATCH_COLOR,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ),
    'line_color_border' => Array(
        'must'          => 0,
        'default'       => '#000000',
        'field_type'    => 'color',
        'match'         => MATCH_COLOR,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ),
    'line_cut' => Array(
        'must'          => 0,
        'default'       => '0.5',
        'match'         => MATCH_FLOAT,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ),
    'line_label_show' => Array(
        'must'          => 0,
        'default'       => '1',
        'match'         => MATCH_BOOLEAN,
        'field_type'    => 'boolean',
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ),
    'line_label_pos_in' => Array(
        'must'          => 0,
        'default'       => '0.5',
        'match'         => MATCH_FLOAT,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ),
    'line_label_pos_out' => Array(
        'must'          => 0,
        'default'       => '0.5',
        'match'         => MATCH_FLOAT,
        'depends_on'    => 'view_type',
        'depends_value' => 'line'
    ),
    'line_label_y_offset' => Array(
        'must'          => 0,
        'default'       => 2,
        'match'         => MATCH_INTEGER,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ),
    'line_width' => Array(
        'must' => 0,
        'default' => '3',
        'match' => MATCH_INTEGER,
        'depends_on' => 'view_type',
        'depends_value' => 'line'),
    'line_weather_colors' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'line_weather_colors'),
        'match'         => MATCH_WEATHER_COLORS,
        'depends_on'    => 'view_type',
        'depends_value' => 'line'),

    'in_maintenance' => Array(
        'must'       => 0,
        'default'    => '0',
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ),

    'label_show' => Array(
        'must'       => 0,
        'default'    => '0',
        'default'    => cfg('defaults', 'label_show'),
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ),
    'label_text' => Array(
        'must'          => 0,
        'default'       => '[name]',
        'match'         => MATCH_ALL,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ),
    'label_x' => Array(
        'must'          => 0,
        'default'       => 'center',
        'match'         => MATCH_LABEL_X,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ),
    'label_y' => Array(
        'must'          => 0,
        'default'       => 'bottom',
        'match'         => MATCH_LABEL_Y,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ),
    'label_width' => Array(
        'must' => 0,
        'default' => 'auto',
        'match' => MATCH_TEXTBOX_WIDTH,
        'depends_on' => 'label_show',
        'depends_value' => '1'),
    'label_background' => Array(
        'must'          => 0,
        'default'       => 'transparent',
        'field_type'    => 'color',
        'match'         => MATCH_COLOR,
        'depends_on'    => 'label_show',
        'depends_value' => '1'),
    'label_border' => Array(
        'must'          => 0,
        'default'       => '#e5e5e5',
        'field_type'    => 'color',
        'match'         => MATCH_COLOR,
        'depends_on'    => 'label_show',
        'depends_value' => '1'),
    'label_style' => Array(
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_STYLE,
        'depends_on' => 'label_show',
        'depends_value' => '1'),
    'label_maxlen' => Array(
        'must'          => 0,
        'default'       => 0,
        'match'         => MATCH_INTEGER,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ),

    'only_hard_states' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'onlyhardstates'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'recognize_services' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'recognizeservices'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'show_in_lists' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'showinlists'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'show_in_multisite' => Array(
        'must' => 0,
        'default'    => cfg('defaults', 'showinmultisite'),
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'stylesheet' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'stylesheet'),
        'match' => MATCH_STRING_NO_SPACE),
    'url_target' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'urltarget'),
        'match' => MATCH_STRING_NO_SPACE),

    'x' => Array(
        'must' => 1,
        'match' => MATCH_COORDS_MULTI
    ),
    'y' => Array(
        'must' => 1,
        'match' => MATCH_COORDS_MULTI
    ),
    'z' => Array(
        'must' => 0,
        'default' => 10,
        'match' => MATCH_INTEGER
    ),
    'view_type' => Array(
        'must'          => 0,
        'default'       => 'icon',
        'match'         => MATCH_VIEW_TYPE,
        'field_type'    => 'dropdown',
        'list'          => 'listViewTypes',
    ),
    'view_type_obj' => Array(
        'must'          => 0,
        'default'       => 'icon',
        'match'         => MATCH_VIEW_TYPE_OBJ,
        'field_type'    => 'dropdown',
        'list'          => 'listViewTypesObj',
    ),
    'url' => Array(
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_URL_EMPTY,
    ),
    'url_mandatory' => Array(
        'must' => 1,
        'default' => '',
        'match' => MATCH_STRING_URL,
    ),
    'use' => Array(
        'must'    => 0,
        'default' => array(),
        'array'   => true,
        'match'   => MATCH_STRING_NO_SPACE,
    ),

    'gadget_url' => Array(
        'must'          => 0,
        'match'         => MATCH_STRING_URL,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'gadget',
        'default'       => '',
        'list'          => 'listGadgets',
    ),
    'gadget_type' => Array(
        'must'          => 0,
        'match'         => MATCH_GADGET_TYPE,
        'depends_on'    => 'view_type',
        'depends_value' => 'gadget',
        'default'       => 'img',
        'list'          => 'listGadgetTypes',
        # FIXME: switch to 'deprecated' => true, one day
        'field_type'    => 'hidden',
    ),
    'gadget_scale' => Array('must' => 0,
        'default' => 100,
        'match' => MATCH_INTEGER,
        'depends_on' => 'view_type',
        'depends_value' => 'gadget',
    ),
    'gadget_opts' => Array('must' => 0,
        'default' => '',
        'match' => MATCH_GADGET_OPT,
        'depends_on' => 'view_type',
        'depends_value' => 'gadget',
    ),

    // GLOBAL SPECIFIC OPTIONS
    'zoom' => Array(
        'must'          => 0,
        'default'       => 100,
        'match'         => MATCH_ZOOM_FACTOR,
        'field_type'    => 'dropdown',
        'list'          => 'listZoomFactors',
    ),
    'zoombar' => Array(
        'must' => 0,
        'default'    => cfg('defaults', 'zoombar'),
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean'
    ),

    // HOST SPECIFIC OPTIONS

    'host_name' => Array(
        'must' => 1,
        'match' => MATCH_STRING,
        'field_type' => 'dropdown',
        'list' => 'listHostNames',
    ),
    'host_url' => Array(
        'must'    => 0,
        'default' => cfg('defaults', 'hosturl'),
        'match'   => MATCH_STRING_URL_EMPTY,
    ),

    // HOSTGROUP SPECIFIC OPTIONS

    'hostgroup_name' => Array(
        'must' => 1,
        'match' => MATCH_STRING,
        'field_type' => 'dropdown',
        'list' => 'listHostgroupNames',
    ),
    'hostgroup_url' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hostgroupurl'),
        'match' => MATCH_STRING_URL_EMPTY
    ),

    // SERVICE SPECIFIC OPTIONS

    'service_description' => Array(
        'must'       => 1,
        'match'      => MATCH_STRING,
        'field_type' => 'dropdown',
        'list'       => 'listServiceNames',
    ),
    'service_label_text' => Array(
        'must'          => 0,
        'default'       => '[service_description]',
        'match'         => MATCH_ALL,
        'depends_on'    => 'label_show',
        'depends_value' => '1'
    ),
    'service_url' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'serviceurl'),
        'match'  => MATCH_STRING_URL_EMPTY,
    ),

    // SERVICEGROUP SPECIFIC OPTIONS

    'servicegroup_name' => Array(
        'must'       => 1,
        'match'      => MATCH_STRING,
        'field_type' => 'dropdown',
        'list'       => 'listServicegroupNames',
    ),
    'servicegroup_url' => Array(
        'must'    => 0,
        'default' => cfg('defaults', 'servicegroupurl'),
        'match'   => MATCH_STRING_URL_EMPTY,
    ),

    // MAP SPECIFIC OPTIONS

    'map_name' => Array(
        'must'       => 1,
        'match'      => MATCH_STRING_NO_SPACE,
        'field_type' => 'dropdown',
        'list'       => 'listMapNames',
    ),
    'map_url' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'mapurl'),
        'match' => MATCH_STRING_URL_EMPTY,
    ),

    // TEXTBOX SPECIFIC OPTIONS

    'text' => Array(
        'must' => 1,
        'match' => MATCH_ALL,
    ),
    'border_color' => Array(
        'must'       => 0,
        'default'    => '#e5e5e5',
        'field_type' => 'color',
        'match'      => MATCH_COLOR,
    ),
    'style' => Array(
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_STYLE,
    ),
    'h' => Array(
        'must'    => 0,
        'default' => 'auto',
        'match'   => MATCH_TEXTBOX_HEIGHT,
    ),
    'w' => Array(
        'must'    => 0,
        'default' => 'auto',
        'match'   => MATCH_TEXTBOX_WIDTH,
    ),
    'textbox_z' => Array(
        'must'    => 0,
        'default' => 5,
        'match'   => MATCH_INTEGER
    ),

    // SHAPE SPECIFIC OPTIONS

    'icon' => Array(
        'must'       => 1,
        'match'      => MATCH_PNG_GIF_JPG_FILE_OR_URL,
        'field_type' => 'dropdown',
	'other'      => true,
        'list'       => 'listShapes',
    ),
    'enable_refresh' => Array(
        'must'       => 0,
        'default'    => 0,
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ),
    'shape_z' => Array(
        'must'       => 0,
        'default'    => 1,
        'match'      => MATCH_INTEGER
    ),

    // TEMPLATE SPECIFIC OPTIONS

    'name' => Array(
        'must'  => 1,
        'match' => MATCH_STRING_NO_SPACE,
        'list'  => 'listTemplateNames',
    ),
    
    // STATELESS LINE SPECIFIC OPTIONS
    
    'view_type_line' => Array(
        'must'          => 1,
        'default'       => 'line',
        'match'         => MATCH_VIEW_TYPE,
        'field_type'    => 'hidden',
    ),

    // CONTAINER SPECIFIC OPTIONS

    'view_type_container' => Array(
        'must'          => 0,
        'default'       => 'inline',
        'match'         => MATCH_VIEW_TYPE_CONTAINER,
        'field_type'    => 'dropdown',
        'list'          => 'listViewTypesContainer',
    ),

    // DYNAMIC GROUP SPECIFIC OPTIONS

    'dyngroup_name' => Array(
        'must'       => 1,
        'match'      => MATCH_STRING,
        'default'    => '',
    ),
    'object_types' => Array(
        'must'       => 1,
        'default'    => '',
        'field_type' => 'dropdown',
        'match'      => MATCH_DYN_GROUP_TYPES,
        'list'       => 'listDynGroupTypes', 
    ),
    'object_filter' => Array(
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_LIVESTATUS_FILTER,
    ),
    'dyngroup_url' => Array(
        'must'       => 0,
        'default'    => cfg('defaults', 'dyngroupurl'),
        'match'      => MATCH_STRING_URL_EMPTY,
    ),

    // AGGREGATION SPECIFIC

    'aggr_name' => Array(
        'must'       => 1,
        'match'      => MATCH_STRING,
        'field_type' => 'dropdown',
        'list'       => 'listAggrNames',
    ),
    'aggr_url' => Array(
        'must'       => 0,
        'default'    => cfg('defaults', 'aggrurl'),
        'match'      => MATCH_STRING_URL_EMPTY,
    ),
);

// STATELESS LINE SPECIFIC OPTIONS
$mapConfigVars['context_menu_line'] = $mapConfigVars['context_menu'];
$mapConfigVars['context_menu_line']['default'] = '0';
$mapConfigVars['hover_menu_line'] = $mapConfigVars['hover_menu'];
$mapConfigVars['hover_menu_line']['default'] = '0';

//
// map configuration variable registration
//

$mapConfigVarMap['global'] = Array(
    'type' => null,
    'object_id' => null,
    'sources' => null,
    'map_image' => null,
    'alias' => null,
    'backend_id' => null,
    'background_color' => null,
    'default_params' => null,
    'parent_map' => null,
    'context_menu' => null,
    'context_template' => null,
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
    'exclude_members' => null,
    'exclude_member_states' => null,
    'grid_show' => null,
    'grid_color' => null,
    'grid_steps' => null,
    'header_menu' => null,
    'header_template' => null,
    'header_fade' => null,
    'hover_menu' => null,
    'hover_delay' => null,
    'hover_template' => null,
    'hover_childs_show' => null,
    'hover_childs_limit' => null,
    'hover_childs_order' => null,
    'hover_childs_sort' => null,
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_color' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'in_maintenance' => null,
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
    'show_in_lists' => null,
    'show_in_multisite' => null,
    'stylesheet' => null,
    'url_target' => null,
    'zoom' => null,
    'zoombar' => null,
);

$mapConfigVarMap['host'] = Array(
    'type' => null,
    'object_id' => null,
    'host_name' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'backend_id' => null,
    'view_type_obj' => 'view_type',
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'gadget_url' => null,
    'gadget_type' => null,
    'gadget_scale' => null,
    'gadget_opts' => null,
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
    'host_url' => 'url',
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['hostgroup'] = Array(
    'type' => null,
    'object_id' => null,
    'hostgroup_name' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'backend_id' => null,
    'view_type_obj' => 'view_type',
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'gadget_url' => null,
    'gadget_type' => null,
    'gadget_scale' => null,
    'gadget_opts' => null,
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
    'hostgroup_url' => 'url',
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['service'] = Array(
    'type' => null,
    'object_id' => null,
    'host_name' => null,
    'service_description' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'backend_id' => null,
    'view_type_obj' => 'view_type',
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_label_show' => null,
    'line_label_pos_in' => null,
    'line_label_pos_out' => null,
    'line_label_y_offset' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'gadget_url' => null,
    'gadget_type' => null,
    'gadget_scale' => null,
    'gadget_opts' => null,
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
    'label_show' => null,
    'service_label_text' => 'label_text',
    'label_x' => null,
    'label_y' => null,
    'label_width' => null,
    'label_background' => null,
    'label_border' => null,
    'label_style' => null,
    'label_maxlen' => null,
    'only_hard_states' => null,
    'service_url' => 'url',
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['servicegroup'] = Array(
    'type' => null,
    'object_id' => null,
    'servicegroup_name' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'backend_id' => null,
    'view_type_obj' => 'view_type',
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'gadget_url' => null,
    'gadget_type' => null,
    'gadget_scale' => null,
    'gadget_opts' => null,
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
    'servicegroup_url' => 'url',
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['map'] = Array(
    'type'                  => null,
    'object_id'             => null,
    'map_name'              => null,
    'x'                     => null,
    'y'                     => null,
    'z'                     => null,
    'view_type_obj'         => 'view_type',
    'iconset'               => null,
    'line_type'             => null,
    'line_arrow'            => null,
    'line_cut'              => null,
    'line_width'            => null,
    'line_weather_colors'   => null,
    'gadget_url'            => null,
    'gadget_type'           => null,
    'gadget_scale'          => null,
    'gadget_opts'           => null,
    'context_menu'          => null,
    'context_template'      => null,
    'exclude_members'       => null,
    'exclude_member_states' => null,
    'hover_menu'            => null,
    'hover_template'        => null,
    'hover_delay'           => null,
    'hover_url'             => null,
    'hover_childs_show'     => null,
    'hover_childs_sort'     => null,
    'hover_childs_order'    => null,
    'hover_childs_limit'    => null,
    'label_show'            => null,
    'label_text'            => null,
    'label_x'               => null,
    'label_y'               => null,
    'label_width'           => null,
    'label_background'      => null,
    'label_border'          => null,
    'label_style'           => null,
    'label_maxlen'          => null,
    'only_hard_states'      => null,
    'map_url'               => 'url',
    'url_target'            => null,
    'use'                   => null,
);

$mapConfigVarMap['line'] = Array(
    'type' => null,
    'object_id' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'view_type_line' => 'view_type',
    'line_type' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_color' => null,
    'line_color_border' => null,
    'context_menu_line' => 'context_menu',
    'context_template' => null,
    'hover_menu_line' => 'hover_menu',
    'hover_template' => null,
    'hover_url' => null,
    'hover_delay' => null,
    'url' => null,
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['textbox'] = Array(
    'type' => null,
    'object_id' => null,
    'text' => null,
    'x' => null,
    'y' => null,
    'textbox_z' => 'z',
    'w' => null,
    'h' => null,
    'background_color' => null,
    'border_color' => null,
    'context_menu' => null,
    'context_template' => null,
    'style' => null,
    'use' => null,
);

$mapConfigVarMap['shape'] = Array(
    'type' => null,
    'object_id' => null,
    'icon' => null,
    'x' => null,
    'y' => null,
    'shape_z' => 'z',
    'context_menu' => null,
    'context_template' => null,
    'enable_refresh' => null,
    'hover_menu' => null,
    'hover_url' => null,
    'hover_delay' => null,
    'url' => null,
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['template'] = Array(
    'type' => null,
    'name' => null,
    'object_id' => null,
);

$mapConfigVarMap['container'] = Array(
    'type' => null,
    'object_id' => null,

    'url_mandatory' => 'url',
    'view_type_container' => 'view_type',
    'enable_refresh' => null,

    'x' => null,
    'y' => null,
    'textbox_z' => 'z',
    'w' => null,
    'h' => null,

    'background_color' => null,
    'border_color' => null,
    'context_menu' => null,
    'context_template' => null,

    'style' => null,
    'use' => null,
);

$mapConfigVarMap['dyngroup'] = array(
    'type'          => null,
    'object_id'     => null,
    'dyngroup_name' => 'name',
    'object_types'  => null,
    'object_filter' => null,
    'x'             => null,
    'y'             => null,
    'z'             => null,
    'backend_id'    => null,
    'view_type_obj' => 'view_type',
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'gadget_url' => null,
    'gadget_type' => null,
    'gadget_scale' => null,
    'gadget_opts' => null,
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
    'dyngroup_url' => 'url',
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['aggr'] = Array(
    'type'                  => null,
    'object_id'             => null,
    'aggr_name'             => 'name',
    'x'                     => null,
    'y'                     => null,
    'z'                     => null,
    'backend_id'            => null,
    'view_type_obj'         => 'view_type',
    'iconset'               => null,
    'line_type'             => null,
    'line_arrow'            => null,
    'line_cut'              => null,
    'line_width'            => null,
    'line_weather_colors'   => null,
    'gadget_url'            => null,
    'gadget_type'           => null,
    'gadget_scale'          => null,
    'gadget_opts'           => null,
    'context_menu'          => null,
    'context_template'      => null,
    'exclude_members'       => null,
    'exclude_member_states' => null,
    'hover_menu'            => null,
    'hover_delay'           => null,
    'hover_template'        => null,
    'hover_url'             => null,
    'hover_childs_show'     => null,
    'hover_childs_sort'     => null,
    'hover_childs_order'    => null,
    'hover_childs_limit'    => null,
    'label_show'            => null,
    'label_text'            => null,
    'label_x'               => null,
    'label_y'               => null,
    'label_width'           => null,
    'label_background'      => null,
    'label_border'          => null,
    'label_style'           => null,
    'label_maxlen'          => null,
    'only_hard_states'      => null,
    'aggr_url'              => 'url',
    'url_target'            => null,
    'use'                   => null,
);

?>
