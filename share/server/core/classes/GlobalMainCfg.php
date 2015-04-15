<?php
/*****************************************************************************
 *
 * GlobalMainCfg.php - Class for handling the main configuration of NagVis
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/

function listMultisiteSnapinLayouts() {
    return Array(
        'tree' => l('Show the map tree'),
        'list' => l('Show a flat list'),
    );
}

function listAvailableLanguages() {
    global $CORE;
    return $CORE->getAvailableLanguages();
}

/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalMainCfg {
    private $useCache = true;
    private $CACHE;

    protected $config = Array();
    protected $preUserConfig = null;
    protected $runtimeConfig = Array();
    protected $stateWeight;
    protected $onlyUserConfig = false;

    protected $configFiles;

    protected $validConfig;

    /**
     * Class Constructor
     *
     * @param	Array $configFile    List of paths to configuration files
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct() {
        $this->validConfig = Array(
            'global' => Array(
                'audit_log' => Array(
                    'must'       => 0,
                    'editable'   => 1,
                    'default'    => 0,
                    'match'      => MATCH_BOOLEAN,
                    'field_type' => 'boolean',
                ),
                'authmodule' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 'CoreAuthModSQLite',
                    'match' => MATCH_STRING),

                'authorisationmodule' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 'CoreAuthorisationModSQLite',
                    'match' => MATCH_STRING),

                'authorisation_multisite_file' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => '',
                    'depends_on'    => 'authorisationmodule',
                    'depends_value' => 'CoreAuthorisationModMultisite',
                    'match'         => MATCH_STRING_PATH,
                ),

                'authorisation_group_perms_file' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => '',
                    'depends_on'    => 'authorisationmodule',
                    'depends_value' => 'CoreAuthorisationModGroup',
                    'match'         => MATCH_STRING_PATH,
                ),
                'authorisation_group_backends' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => array(),
                    'array'         => true,
                    'depends_on'    => 'authorisationmodule',
                    'depends_value' => 'CoreAuthorisationModGroup',
                    'match'         => MATCH_STRING_NO_SPACE,
                ),

                'controls_size' => Array(
		    'must'     => 1,
                    'editable' => 1,
                    'default'  => 10,
                    'match'    => MATCH_INTEGER
                ),
                'dateformat' => Array(
                    'must'     => 1,
                    'editable' => 1,
                    'default'  => 'Y-m-d H:i:s',
                    'match'    => MATCH_STRING
                ),

                'dialog_ack_sticky' => Array(
                    'must'       => 1,
                    'editable'   => 1,
                    'default'    => 1,
                    'field_type' => 'boolean',
                    'match'      => MATCH_BOOLEAN
                ),
                'dialog_ack_notify' => Array(
                    'must'       => 1,
                    'editable'   => 1,
                    'default'    => 1,
                    'field_type' => 'boolean',
                    'match'      => MATCH_BOOLEAN
                ),
                'dialog_ack_persist' => Array(
                    'must'       => 1,
                    'editable'   => 1,
                    'default'    => 0,
                    'field_type' => 'boolean',
                    'match'      => MATCH_BOOLEAN
                ),

                'displayheader' => Array('must' => 1,
                    'editable' => 1,
                    'deprecated' => 1,
                    'default' => '1',
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'file_group' => Array('must' => 0,
                    'default' => '',
                    'match' => MATCH_STRING),
                'file_mode' => Array(
                    'must'    => 1,
                    'default' => 660,
                    'match'   => MATCH_INTEGER_EMPTY
                ),

                'geomap_server' => array(
                    'must'    => 1,
                    'default' => 'http://geomap.nagvis.org/',
                    'match'   => MATCH_STRING_URL,
                ),

                'http_proxy' => array(
                    'must'    => 0,
                    'default' => null,
                    'match'   => MATCH_STRING_URL,
                ),
                'http_proxy_auth' => array(
                    'must'    => 0,
                    'default' => null,
                    'match'   => MATCH_NOT_EMPTY,
                ),
                'http_timeout' => array(
                    'must'    => 1,
                    'default' => 2,
                    'match'   => MATCH_INTEGER,
                ),

                'language_detection' => Array('must' => 1,
                    'editable' => 1,
                    'array' => true,
                    'default' => Array('user', 'session', 'browser', 'config'),
                    'match' => MATCH_STRING_NO_SPACE),
                'language_available' => Array('must' => 1,
                    'editable' => 1,
                    'array' => true,
                    'default' => Array('de_DE', 'en_US', 'es_ES', 'fr_FR', 'pt_BR'),
                    'match' => MATCH_STRING_NO_SPACE),
                'language' => Array(
                    'must'       => 1,
                    'editable'   => 1,
                    'default'    => 'en_US',
                    'field_type' => 'dropdown',
                    'list'       => 'listAvailableLanguages',
                    'match'      => MATCH_LANGUAGE_EMPTY
                ),
                'logonmodule' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 'LogonMixed',
                    'match' => MATCH_STRING),

                'logonenvvar' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 'REMOTE_USER',
                    'depends_on' => 'logonmodule',
                    'depends_value' => 'LogonEnv',
                    'match' => MATCH_STRING),
                'logonenvcreateuser' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '1',
                    'field_type' => 'boolean',
                    'depends_on' => 'logonmodule',
                    'depends_value' => 'LogonEnv',
                    'match' => MATCH_BOOLEAN),
                'logonenvcreaterole' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 'Guests',
                    'depends_on' => 'logonmodule',
                    'depends_value' => 'LogonEnv',
                    'match' => MATCH_STRING),

                'logon_multisite_htpasswd' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => '',
                    'depends_on'    => 'logonmodule',
                    'depends_value' => 'LogonMultisite',
                    'match'         => MATCH_STRING_PATH,
                ),
                'logon_multisite_serials' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => '',
                    'depends_on'    => 'logonmodule',
                    'depends_value' => 'LogonMultisite',
                    'match'         => MATCH_STRING_PATH,
                ),
                'logon_multisite_secret' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => '',
                    'depends_on'    => 'logonmodule',
                    'depends_value' => 'LogonMultisite',
                    'match'         => MATCH_STRING_PATH,
                ),
                'logon_multisite_createuser' => Array(
                    'must'          => 1,
                    'editable'      => 1,
                    'default'       => '1',
                    'field_type'    => 'boolean',
                    'depends_on'    => 'logonmodule',
                    'depends_value' => 'LogonMultisite',
                    'match'         => MATCH_BOOLEAN),
                'logon_multisite_createrole' => Array(
                    'must'          => 1,
                    'editable'      => 1,
                    'default'       => 'Guests',
                    'depends_on'    => 'logonmodule',
                    'depends_value' => 'LogonMultisite',
                    'match'         => MATCH_STRING
                ),

                'multisite_snapin_layout' => Array(
                    'must'        => 0,
                    'editable'    => 1,
                    'default'     => 'list',
                    'match'       => MATCH_STRING_NO_SPACE,
                    'field_type'  => 'dropdown',
                    'list'        => 'listMultisiteSnapinLayouts',
                ),

                'user_filtering' => Array(
                    'must'       => 0,
                    'editable'   => 1,
                    'default'    => 0,
                    'field_type' => 'boolean',
                    'match'      => MATCH_BOOLEAN
                ),

                'refreshtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '60',
                    'match' => MATCH_INTEGER),

                'sesscookiedomain' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING),
                'sesscookiepath' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING),
                'sesscookieduration' => Array('must' => 1,
                    'editable'    => 1,
                    'default'     => '86400',
                    'match'       => MATCH_STRING),
                'sesscookiesecure' => Array(
                    'must'        => 0,
                    'editable'    => 1,
                    'default'     => 0,
                    'field_type'  => 'boolean',
                    'match'       => MATCH_BOOLEAN
                ),
                'sesscookiehttponly' => Array(
                    'must'        => 0,
                    'editable'    => 1,
                    'default'     => 0,
                    'field_type'  => 'boolean',
                    'match'       => MATCH_BOOLEAN
                ),
                'shinken_features' => Array('must' => 1,
                    'editable'    => 1,
                    'default'     => '0',
                    'field_type'    => 'boolean',
                    'match'         => MATCH_BOOLEAN),

                'staleness_threshold' => Array(
                    'must'        => 1,
                    'editable'    => 1,
                    'default'     => '1.5',
                    'match'       => MATCH_FLOAT,
                ),

                'startmodule' => Array('must' => 1,
                    'editable'    => 1,
                    'default'     => 'Overview',
                    'match'       => MATCH_STRING),
                'startaction' => Array('must' => 1,
                    'editable'    => 1,
                    'default'     => 'view',
                    'match'       => MATCH_STRING),
                'startshow'   => Array('must' => 0,
                    'editable'    => 1,
                    'default'     => '',
                    'match'       => MATCH_STRING_EMPTY
                ),
            ),
            'defaults' => Array(
                'backend' => Array(
                    'must'        => 0,
                    'editable'    => 1,
                    'default'     => array('live_1'),
                    'array'       => true,
                    'field_type'  => 'dropdown',
                    'list'        => 'listBackendIds',
                    'match'       => MATCH_BACKEND_ID
                ),
                'backgroundcolor' => Array(
                    'must'        => 0,
                    'editable'    => 1,
                    'default'     => 'transparent',
                    'match'       => MATCH_COLOR
                ),
                'contextmenu' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 1,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'contexttemplate' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 'default',
                    'depends_on'    => 'contextmenu',
                    'depends_value' => 1,
                    'field_type'    => 'dropdown',
                    'list'          => 'listContextTemplates',
                    'match'         => MATCH_STRING_NO_SPACE
                ),
                'stylesheet' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING_NO_SPACE),

                'event_on_load' => Array(
                    'must'       => 0,
                    'editable'   => 1,
                    'default'    => 0,
                    'field_type' => 'boolean',
                    'match'      => MATCH_BOOLEAN
                ),
                'event_repeat_interval' => Array(
                    'must'       => 0,
                    'editable'   => 1,
                    'default'    => 0,
                    'match'      => MATCH_INTEGER,
                ),
                'event_repeat_duration' => Array(
                    'must'       => 0,
                    'editable'   => 1,
                    'default'    => -1,
                    'match'      => MATCH_INTEGER_PRESIGN,
                ),

                'eventbackground' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '0',
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'eventhighlight' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '1',
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'eventhighlightinterval' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '500',
                    'depends_on' => 'eventhighlight',
                    'depends_value' => 1,
                    'match' => MATCH_INTEGER),
                'eventhighlightduration' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '10000',
                    'depends_on' => 'eventhighlight',
                    'depends_value' => 1,
                    'match' => MATCH_INTEGER),
                'eventlog' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '0',
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'eventloglevel' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 'info',
                    'depends_on' => 'eventlog',
                    'depends_value' => 1,
                    'match' => MATCH_STRING_NO_SPACE),
                'eventlogheight' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '100',
                    'depends_on' => 'eventlog',
                    'depends_value' => 1,
                    'match' => MATCH_INTEGER),
                'eventlogevents' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '24',
                    'depends_on' => 'eventlog',
                    'depends_value' => 1,
                    'match' => MATCH_INTEGER),
                'eventloghidden' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 1,
                    'depends_on' => 'eventlog',
                    'depends_value' => 1,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'eventscroll' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '1',
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'eventsound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '1',
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),

                'headermenu' => Array(
                    'must'       => 1,
                    'editable'   => 1,
                    'default'    => '1',
                    'field_type' => 'boolean',
                    'match'      => MATCH_BOOLEAN
                ),
                'headertemplate' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 'default',
                    'depends_on'    => 'headermenu',
                    'depends_value' => 1,
                    'field_type'    => 'dropdown',
                    'list'          => 'listHeaderTemplates',
                    'match'         => MATCH_STRING_NO_SPACE,
),
                'headerfade' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 0,
                    'depends_on'    => 'headermenu',
                    'depends_value' => 1,
                    'field_type'    => 'boolean',
                    'match'         => MATCH_BOOLEAN
                ),
                'header_show_states' => Array(
                    'must'       => 1,
                    'editable'   => 1,
                    'default'    => 1,
                    'field_type' => 'boolean',
                    'match'      => MATCH_BOOLEAN
                ),
                'hovermenu' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '1',
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'hovertemplate' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 'default',
                    'depends_on'    => 'hovermenu',
                    'depends_value' => 1,
                    'field_type'    => 'dropdown',
                    'list'          => 'listHoverTemplates',
                    'match'         => MATCH_STRING_NO_SPACE,
                ),
                'hovertimeout' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '5',
                    'deprecated' => 1,
                    'match' => MATCH_INTEGER),
                'hoverdelay' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '0',
                    'depends_on' => 'hovermenu',
                    'depends_value' => 1,
                    'match' => MATCH_INTEGER),
                'hoverchildsshow' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '1',
                    'depends_on' => 'hovermenu',
                    'depends_value' => 1,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'hoverchildslimit' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '10',
                    'depends_on' => 'hovermenu',
                    'depends_value' => 1,
                    'match' => MATCH_INTEGER_PRESIGN),
                'hoverchildsorder' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 'asc',
                    'depends_on'    => 'hovermenu',
                    'depends_value' => 1,
                    'field_type'    => 'dropdown',
                    'list'          => 'listHoverChildOrders',
                    'match'         => MATCH_ORDER
                ),
                'hoverchildssort' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 's',
                    'depends_on'    => 'hovermenu',
                    'depends_value' => 1,
                    'field_type'    => 'dropdown',
                    'list'          => 'listHoverChildSorters',
                    'match'         => MATCH_STRING_NO_SPACE,
                ),
                'icons' => Array(
                    'must'       => 1,
                    'editable'   => 1,
                    'default'    => 'std_medium',
                    'field_type' => 'dropdown',
                    'list'       => 'listIconsets',
                    'match'      => MATCH_STRING_NO_SPACE
                ),
                'onlyhardstates' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 0,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'recognizeservices' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 1,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'showinlists' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 1,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'showinmultisite' => Array('must' => 0,
                    'editable'                => 1,
                    'default'                 => 1,
                    'field_type'              => 'boolean',
                    'match'                   => MATCH_BOOLEAN),
                'urltarget' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '_self',
                    'match' => MATCH_STRING_NO_SPACE),
                'mapurl' => Array('must' => 0,
                    'default' => '[htmlbase]/index.php?mod=Map&act=view&show=[map_name]',
                    'match' => MATCH_STRING_URL_EMPTY),
                'hosturl' => Array('must' => 0,
                    'default' => '[htmlcgi]/status.cgi?host=[host_name]',
                    'match' => MATCH_STRING_URL_EMPTY),
                'hostgroupurl' => Array('must' => 0,
                    'default' => '[htmlcgi]/status.cgi?hostgroup=[hostgroup_name]',
                    'match' => MATCH_STRING_URL_EMPTY),
                'serviceurl' => Array('must' => 0,
                    'default' => '[htmlcgi]/extinfo.cgi?type=2&host=[host_name]&service=[service_description]',
                    'match' => MATCH_STRING_URL_EMPTY),
                'servicegroupurl' => Array('must' => 0,
                    'default' => '[htmlcgi]/status.cgi?servicegroup=[servicegroup_name]&style=detail',
                    'match' => MATCH_STRING_URL_EMPTY),
                'dyngroupurl' => Array(
                    'must'    => 0,
                    'default' => '',
                    'match'   => MATCH_STRING_URL_EMPTY
                ),
                'aggrurl' => Array(
                    'must'    => 0,
                    'default' => '',
                    'match'   => MATCH_STRING_URL_EMPTY
                ),
                'view_template' => Array(
                    'must'     => 0,
                    'editable' => 1,
                    'default'  => 'default',
                    'match'    => MATCH_STRING_NO_SPACE
                ),
                'label_show' => Array(
                    'must'       => 0,
                    'editable'   => 1,
                    'default'    => '0',
                    'match'      => MATCH_BOOLEAN, 
                    'field_type' => 'boolean',
                ),
                'line_weather_colors' => Array(
                    'must'       => 0,
                    'editable'   => 1,
                    'default'    => '10:#8c00ff,25:#2020ff,40:#00c0ff,55:#00f000,70:#f0f000,85:#ffc000,100:#ff0000',
                    'match'      => MATCH_WEATHER_COLORS,
                ),
                'zoombar' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 0,
                    'field_type'    => 'boolean',
                    'match'         => MATCH_BOOLEAN
                ),
                'zoom_scale_objects' => array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 1,
                    'field_type'    => 'boolean',
                    'match'         => MATCH_BOOLEAN
                ),
            ),
            'wui' => Array(
                'allowedforconfig' => Array(
                    'must' => 0,
                    'editable' => 1,
                    'deprecated' => 1,
                    'default' => Array('EVERYONE'),
                    'match' => MATCH_STRING),
                'autoupdatefreq' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '25',
                    'deprecated' => 1,
                    'field_type' => 'dropdown',
                    'match' => MATCH_INTEGER),
                'headermenu' => Array('must' => 1,
                    'editable' => 1,
                    'deprecated' => 1,
                    'default' => '1',
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'headertemplate' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 'default',
                    'deprecated'    => 1,
                    'depends_on'    => 'headermenu',
                    'depends_value' => 1,
                    'field_type'    => 'dropdown',
                    'list'          => 'listHeaderTemplates',
                    'match'         => MATCH_STRING_NO_SPACE
                ),
                'maplocktime' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '5',
                    'match' => MATCH_INTEGER),
                'grid_show' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 0,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'grid_color' => Array('must' => 0,
                    'editable' => 1,
                    'depends_on' => 'grid_show',
                    'depends_value' => 1,
                    'default' => '#D5DCEF',
                    'match' => MATCH_COLOR),
                'grid_steps' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 32,
                    'depends_on' => 'grid_show',
                    'depends_value' => 1,
                    'match' => MATCH_INTEGER)),
            'paths' => Array(
                'base' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING_PATH),
                'local_base' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING_PATH),
                'cfg' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'sources' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'actions' => Array(
                    'must'       => 0,
                    'editable'   => 0,
                    'default'    => '',
                    'field_type' => 'hidden',
                    'match'      => MATCH_STRING_PATH
                ),
                'icons' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'images' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'js' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'wuijs' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'field_type' => 'hidden',
                    'deprecated' => 1,
                    'match' => MATCH_STRING_PATH),
                'shapes' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'language' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'class' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'server' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'doc' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'var' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'sharedvar' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'mapcfg' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'geomap' => Array(
                    'must'       => 0,
                    'editable'   => 0,
                    'default'    => '',
                    'field_type' => 'hidden',
                    'match'      => MATCH_STRING_PATH,
                ),
                'profiles' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'automapcfg' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'deprecated' => 1,
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'gadgets' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'styles' => Array(
                    'must'       => 0,
                    'editable'   => 0,
                    'default'    => '',
                    'field_type' => 'hidden',
                    'match'      => MATCH_STRING_PATH),
                'backgrounds' => Array(
                    'must'       => 0,
                    'editable'   => 0,
                    'default'    => '',
                    'field_type' => 'hidden',
                    'match'      => MATCH_STRING_PATH),
                'templates' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'htmlbase' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '/nagvis',
                    'match' => MATCH_STRING_PATH),
                'local_htmlbase' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '/nagvis',
                    'match' => MATCH_STRING_PATH),
                'htmlcgi' => Array('must' => 1,
                    'editable' => 1,
                    'field_type' => 'hidden',
                    'default' => '/nagios/cgi-bin',
                    'match' => MATCH_STRING_URL),
                'htmlcss' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'htmlimages' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '/',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'htmljs' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'htmlwuijs' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'field_type' => 'hidden',
                    'deprecated' => 1,
                    'match' => MATCH_STRING_PATH),
                'sounds' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'templateimages' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH),
                'htmlsharedvar' => Array('must' => 0,
                    'editable' => 0,
                    'default' => '',
                    'field_type' => 'hidden',
                    'match' => MATCH_STRING_PATH)),
            'backend' => Array(
                'backendtype' => Array('must' => 1,
                    'editable' => 0,
                    'default' => '',
                    'match' => MATCH_STRING_NO_SPACE),
                'backendid' => Array('must' => 1,
                    'editable' => 0,
                    'default' => '',
                    'match' => MATCH_STRING_NO_SPACE),
                'statushost' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING_NO_SPACE_EMPTY),
                'htmlcgi' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING_URL),
                'custom_1' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING_URL_EMPTY),
                'custom_2' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING_URL_EMPTY),
                'custom_3' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING_URL_EMPTY),
                'options' => Array()),
            'rotation' => Array(
                'rotationid' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 'demo',
                    'match' => MATCH_STRING_NO_SPACE),
                'interval' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_INTEGER),
                'maps' => Array(
                    'must'     => 1,
                    'editable' => 1,
                    'default'  => 'demo,demo2',
                    'match'    => MATCH_STRING_URL)
                ),
            'action' => Array(
                'action_type' => Array(
                    'must'     => 1,
                    'editable' => 0,
                    'default'  => '',
                    'match'    => MATCH_STRING_NO_SPACE
                ),
                'action_id' => Array(
                    'must'     => 1,
                    'editable' => 0,
                    'default'  => '',
                    'match'    => MATCH_STRING_NO_SPACE
                ),
                'condition' => Array(
                    'must'     => 0,
                    'editable' => 1,
                    'default'  => '',
                    'match'    => MATCH_CONDITION
                ),
                'obj_type' => Array(
                    'must'     => 1,
                    'editable' => 1,
                    'array'    => true,
                    'default'  => array('host', 'service'),
                    'match'    => MATCH_STRING
                ),
                'client_os' => Array(
                    'must'     => 0,
                    'editable' => 1,
                    'array'    => true,
                    'default'  => array(),
                    'match'    => MATCH_STRING
                ),
                'options' => Array()
            ),
            'automap' => Array(
                'defaultparams' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '&childLayers=2',
                    'match' => MATCH_STRING_URL),
                'defaultroot' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '<<<monitoring>>>',
                    'match' => MATCH_STRING_NO_SPACE_EMPTY),
                'graphvizpath' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '/usr/local/bin/',
                    'match' => MATCH_STRING_PATH),
                'showinlists' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '1',
                    'field_type' => 'boolean',
                    'deprecated' => 1,
                    'match' => MATCH_BOOLEAN)
                ),
            'index' => Array(
                'cellsperrow' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '4',
                    'deprecated' => 1,
                    'match' => MATCH_INTEGER),
                'headermenu' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '1',
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'headertemplate' => Array(
                    'must'          => 0,
                    'editable'      => 1,
                    'default'       => 'default',
                    'depends_on'    => 'headermenu',
                    'depends_value' => 1,
                    'field_type'    => 'dropdown',
                    'list'          => 'listHeaderTemplates',
                    'match'         => MATCH_STRING_NO_SPACE,
                ),
                'showautomaps' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 1,
                    'field_type' => 'boolean',
                    'deprecated' => 1,
                    'match' => MATCH_BOOLEAN),
                'showmaps' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 1,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'showgeomap' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 0,
                    'deprecated' => 1,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'showmapthumbs' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 0,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'showrotations' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 1,
                    'field_type' => 'boolean',
                    'match' => MATCH_BOOLEAN),
                'backgroundcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '#ffffff',
                    'match' => MATCH_COLOR)),
            'worker' => Array(
                'interval' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '10',
                    'match' => MATCH_INTEGER),
                'updateobjectstates' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '30',
                    'match' => MATCH_INTEGER),
                'requestmaxparams' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 0,
                    'match' => MATCH_INTEGER),
                'requestmaxlength' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 1900,
                    'match' => MATCH_INTEGER)),
            'states' => Array(
                'unreachable' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 9,
                    'match' => MATCH_INTEGER),
                'unreachable_stale' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 6,
                    'match' => MATCH_INTEGER),
                'unreachable_ack' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 6,
                    'match' => MATCH_INTEGER),
                'unreachable_ack_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'unreachable_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 6,
                    'match' => MATCH_INTEGER),
                'unreachable_downtime_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'unreachable_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#F1811B',
                    'match' => MATCH_COLOR),
                'unreachable_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#F1811B',
                    'match' => MATCH_COLOR),
                'unreachable_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 'std_unreachable.mp3',
                    'match' => MATCH_MP3_FILE),
                'down' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 10,
                    'match' => MATCH_INTEGER),
                'down_stale' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 6,
                    'match' => MATCH_INTEGER),
                'down_ack' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 6,
                    'match' => MATCH_INTEGER),
                'down_ack_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'down_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '6',
                    'match' => MATCH_INTEGER),
                'down_downtime_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'down_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#FF0000',
                    'match' => MATCH_COLOR),
                'down_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#FF0000',
                    'match' => MATCH_COLOR),
                'down_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 'std_down.mp3',
                    'match' => MATCH_MP3_FILE),
                'critical' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 8,
                    'match' => MATCH_INTEGER),
                'critical_stale' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 6,
                    'match' => MATCH_INTEGER),
                'critical_ack' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 6,
                    'match' => MATCH_INTEGER),
                'critical_ack_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'critical_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 6,
                    'match' => MATCH_INTEGER),
                'critical_downtime_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'critical_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#FF0000',
                    'match' => MATCH_COLOR),
                'critical_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#FF0000',
                    'match' => MATCH_COLOR),
                'critical_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 'std_critical.mp3',
                    'match' => MATCH_MP3_FILE),
                'warning' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 7,
                    'match' => MATCH_INTEGER),
                'warning_stale' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 5,
                    'match' => MATCH_INTEGER),
                'warning_ack' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 5,
                    'match' => MATCH_INTEGER),
                'warning_ack_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'warning_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 5,
                    'match' => MATCH_INTEGER),
                'warning_downtime_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'warning_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#FFFF00',
                    'match' => MATCH_COLOR),
                'warning_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#FFFF00',
                    'match' => MATCH_COLOR),
                'warning_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => 'std_warning.mp3',
                    'match' => MATCH_MP3_FILE),
                'unknown' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 4,
                    'match' => MATCH_INTEGER),
                'unknown_stale' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 3,
                    'match' => MATCH_INTEGER),
                'unknown_ack' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 3,
                    'match' => MATCH_INTEGER),
                'unknown_ack_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'unknown_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 3,
                    'match' => MATCH_INTEGER),
                'unknown_downtime_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'unknown_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#FFCC66',
                    'match' => MATCH_COLOR),
                'unknown_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#FFCC66',
                    'match' => MATCH_COLOR),
                'unknown_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_MP3_FILE),
                'error' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 4,
                    'match' => MATCH_INTEGER),
                'error_stale' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 3,
                    'match' => MATCH_INTEGER),
                'error_ack' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 3,
                    'match' => MATCH_INTEGER),
                'error_ack_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'error_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 3,
                    'match' => MATCH_INTEGER),
                'error_downtime_bgcolor' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_COLOR),
                'error_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#0000FF',
                    'match' => MATCH_COLOR),
                'error_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#0000FF',
                    'match' => MATCH_COLOR),
                'error_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_MP3_FILE),
                'up' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 2,
                    'match' => MATCH_INTEGER),
                'up_stale' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 3,
                    'match' => MATCH_INTEGER),
                'up_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 2,
                    'match' => MATCH_INTEGER),
                'up_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#00FF00',
                    'match' => MATCH_COLOR),
                'up_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#00FF00',
                    'match' => MATCH_COLOR),
                'up_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_MP3_FILE),
                'ok' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 1,
                    'match' => MATCH_INTEGER),
                'ok_stale' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 2,
                    'match' => MATCH_INTEGER),
                'ok_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 1,
                    'match' => MATCH_INTEGER),
                'ok_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#00FF00',
                    'match' => MATCH_COLOR),
                'ok_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#00FF00',
                    'match' => MATCH_COLOR),
                'ok_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_MP3_FILE),
                'pending' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 0,
                    'match' => MATCH_INTEGER),
                'pending_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 0,
                    'match' => MATCH_INTEGER),
                'pending_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#C0C0C0',
                    'match' => MATCH_COLOR),
                'pending_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#C0C0C0',
                    'match' => MATCH_COLOR),
                'pending_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_MP3_FILE),
                'unchecked' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 0,
                    'match' => MATCH_INTEGER),
                'unchecked_downtime' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 0,
                    'match' => MATCH_INTEGER),
                'unchecked_bgcolor' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#C0C0C0',
                    'match' => MATCH_COLOR),
                'unchecked_color' => Array('must' => 1,
                    'editable' => 1,
                    'default' => '#C0C0C0',
                    'match' => MATCH_COLOR),
                'unchecked_sound' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_MP3_FILE)
            ),
            'auth_mysql' => Array(
                'dbhost' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 'localhost',
                    'match' => MATCH_STRING_NO_SPACE),
                'dbport' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '3306',
                    'match' => MATCH_INTEGER),
                'dbname' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 'nagvis-auth',
                    'match' => MATCH_STRING_NO_SPACE),
                'dbuser' => Array('must' => 1,
                    'editable' => 1,
                    'default' => 'root',
                    'match' => MATCH_STRING_NO_SPACE),
                'dbpass' => Array('must' => 0,
                    'editable' => 1,
                    'default' => '',
                    'match' => MATCH_STRING_EMPTY),
            ),
            'internal' => Array(
                'version' => Array('must' => 1,
                    'editable' => 0,
                    'default' => CONST_VERSION,
                    'locked' => 1,
                    'match' => MATCH_STRING_NO_SPACE),
                'title' => Array('must' => 1,
                    'editable' => 0,
                    'default' => 'NagVis ' . CONST_VERSION,
                    'locked' => 1,
                    'match' => MATCH_STRING)
            )
        );

        // Detect the cookie domain to use
        //$this->setCookieDomainByEnv();

        // Try to get the base path via $_SERVER['SCRIPT_FILENAME']
        $this->validConfig['paths']['base']['default'] = $this->getBasePath();
        $this->setPathsByBase($this->getValue('paths','base'), $this->getValue('paths','htmlbase'));

        // Define the main configuration files
        $this->setConfigFiles($this->getConfigFiles());
    }

    /**
     * Loads the custom action definitions from their files
     */
    private function fetchCustomActions() {
        foreach(GlobalCore::getInstance()->getAvailableCustomActions() AS $action_file) {
            $configVars = array();

            if(file_exists(path('sys', 'local', 'actions'))) {
                include(path('sys', 'local', 'actions') . '/'. $action_file);
            } else {
                include(path('sys', 'global', 'actions') . '/'. $action_file);
            }

            $name = substr($action_file, 0, -4);

            // Feed the valid config array to get the options from the sources
            $this->validConfig['action']['options'][$name] = $configVars;
        }
    }

    public function setConfigFiles($arr) {
        $this->configFiles = $arr;
    }

    /**
     * Returns an array of all config files to be used by NagVis.
     * The paths are given as paths.
     */
    public function getConfigFiles() {
        // Get all files from the conf.d directory
        $files = GlobalCore::getInstance()->listDirectory(CONST_MAINCFG_DIR, MATCH_MAINCFG_FILE, null, null, 0, null, false);
        foreach($files AS $key => $filename) {
            $files[$key] = CONST_MAINCFG_DIR . '/' . $filename;
        }

        // Add the user controlled config file
        $files[] = CONST_MAINCFG;

        return $files;
    }

    public function init($onlyUserConfig = false, $cacheSuffix = '') {
        $this->onlyUserConfig = $onlyUserConfig;
        // Get the valid configuration definitions from the available backends
        $this->getBackendValidConf();

        // Load valid config definitions registered by custom actions
        $this->fetchCustomActions();

        // Use the newest file as indicator for using the cache or not
        $this->CACHE = new GlobalFileCache(CONST_MAINCFG, CONST_MAINCFG_CACHE.'-'.CONST_VERSION.'-cache'.$cacheSuffix);
        $this->PUCACHE = new GlobalFileCache(array_slice($this->configFiles, 0, count($this->configFiles) - 1),
                                             CONST_MAINCFG_CACHE.'-pre-user-'.CONST_VERSION.'-cache'.$cacheSuffix);

  	if($this->CACHE->isCached(false) === -1
           || $this->PUCACHE->isCached(false) === -1
           || $this->PUCACHE->getCacheFileAge() < filemtime(CONST_MAINCFG_DIR)) {
            // The cache is too old. Load all config files
            foreach($this->configFiles AS $configFile) {
                // Only proceed when the configuration file exists and is readable
                if(!GlobalCore::getInstance()->checkExisting($configFile, true)
                   || !GlobalCore::getInstance()->checkReadable($configFile, true))
                    return false;
                $this->readConfig($configFile, true, $configFile == end($this->configFiles));
            }
            $this->CACHE->writeCache($this->config, true);
            if($this->preUserConfig !== null)
                $this->PUCACHE->writeCache($this->preUserConfig, true);
        } else {
            // Use the cache!
            $this->config = $this->CACHE->getCache();
            $this->preUserConfig = $this->PUCACHE->getCache();
        }

        // Update the cache time
        $this->useCache = $this->CACHE->isCached(false);

        // Parse the state weight array
        $this->parseStateWeight();

        // want to reduce the paths in the NagVis config, but don't want to hardcode the paths relative from the bases
        $this->setPathsByBase($this->getValue('paths','base'),$this->getValue('paths','htmlbase'));

        // set default value
        $this->validConfig['rotation']['interval']['default'] = $this->getValue('global','refreshtime');
        $this->validConfig['backend']['htmlcgi']['default'] = $this->getValue('paths','htmlcgi');
    }

    /**
     * Gets the cookie domain from the webservers environment and sets the
     * session cookie domain to this value
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function setCookieDomainByEnv() {
        if(isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== '') {
            $this->validConfig['global']['sesscookiedomain']['default'] = $_SERVER['SERVER_NAME'];
        }
    }

    /**
     * Gets the valid configuration definitions from the available backends. The
     * definitions were moved to the backends so it is easier to create new
     * backends without any need to modify the main configuration
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function getBackendValidConf() {
        // Get the configuration options from the backends
        $aBackends = GlobalCore::getInstance()->getAvailableBackends();

        foreach($aBackends AS $backend) {
            $class = 'GlobalBackend'.$backend;

            // FIXME: Does not work in PHP 5.2 (http://bugs.php.net/bug.php?id=31318)
            //$this->validConfig['backend']['options'][$backend] = $class->getValidConfig();
            // I'd prefer to use the above but for the moment I use the fix below

            if (is_callable(array($class, 'getValidConfig'))) {
                $this->validConfig['backend']['options'][$backend] = call_user_func(Array('GlobalBackend'.$backend, 'getValidConfig'));
                //$this->validConfig['backend']['options'][$backend] = call_user_func('GlobalBackend'.$backend.'::getValidConfig');
            }
        }
    }

    /**
     * Gets the base path
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function setPathsByBase($base, $htmlBase) {
        $this->validConfig['paths']['cfg']['default']                = $base.'etc/';
        $this->validConfig['paths']['mapcfg']['default']             = $base.'etc/maps/';
        $this->validConfig['paths']['geomap']['default']             = $base.'etc/geomap';
        $this->validConfig['paths']['profiles']['default']           = $base.'etc/profiles';
        $this->validConfig['global']['authorisation_group_perms_file']['default'] = $base.'etc/perms.db';

        $this->validConfig['paths']['var']['default']                = $base.'var/';
        $this->validConfig['paths']['sharedvar']['default']          = $base.HTDOCS_DIR.'/var/';
        $this->validConfig['paths']['htmlsharedvar']['default']      = $htmlBase.'/var/';

        $this->validConfig['paths']['language']['default']           = $base.HTDOCS_DIR.'/frontend/nagvis-js/locale';
        $this->validConfig['paths']['class']['default']              = $base.HTDOCS_DIR.'/server/core/classes/';
        $this->validConfig['paths']['server']['default']             = $base.HTDOCS_DIR.'/server/core';
        $this->validConfig['paths']['doc']['default']                = $base.HTDOCS_DIR.'/docs';

        $this->validConfig['paths']['htmlcss']['default']            = $htmlBase.'/frontend/nagvis-js/css/';

        $this->validConfig['paths']['js']['default']                 = $base.HTDOCS_DIR.'/frontend/nagvis-js/js/';
        $this->validConfig['paths']['htmljs']['default']             = $htmlBase.'/frontend/nagvis-js/js/';

        $this->validConfig['paths']['images']['default']             = $base.HTDOCS_DIR.'/frontend/nagvis-js/images/';
        $this->validConfig['paths']['htmlimages']['default']         = $htmlBase.'/frontend/nagvis-js/images/';

        $this->validConfig['paths']['templates']['default']          = 'userfiles/templates/';
        $this->validConfig['paths']['styles']['default']             = 'userfiles/styles/';
        $this->validConfig['paths']['gadgets']['default']            = 'userfiles/gadgets/';
        $this->validConfig['paths']['backgrounds']['default']        = 'userfiles/images/maps/';
        $this->validConfig['paths']['icons']['default']              = 'userfiles/images/iconsets/';
        $this->validConfig['paths']['shapes']['default']             = 'userfiles/images/shapes/';
        $this->validConfig['paths']['sounds']['default']             = 'userfiles/sounds/';
        $this->validConfig['paths']['sources']['default']            = 'server/core/sources';
        $this->validConfig['paths']['actions']['default']            = 'server/core/actions';

        $this->validConfig['paths']['templateimages']['default']     = 'userfiles/images/templates/';

        // This option directly relies on the configured htmlBase by default
        $this->validConfig['global']['sesscookiepath']['default']    = $htmlBase;
    }

    /**
     * Gets the base path
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author	Lars Michelsen <lars@vertical-visions.de>
     * @author	Roman Kyrylych <rkyrylych@op5.com>
     */
    private function getBasePath() {
        // Go 3 levels up from nagvis/share/nagvis to nagvis base path
        return realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../../..') . '/';
        // Note: the method below causes problems when <docroot>/nagvis is a symlink to <nagvis-base>/share
        // return realpath(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))).'/';
    }

    /**
     * Reads the specified config file
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function readConfig($configFile, $printErr=1, $isUserMainCfg = False) {
        $numComments = 0;
        $sec = '';

        // read thx config file line by line in array $file
        $file = file($configFile);

        // Count the lines before the loop (only counts once)
        $countLines = count($file);

        // Separate the options from the site configuration and add it later
        // when the user did not define anything different.
        // This is needed to keep the lines of the maincfg file in correct order
        $tmpConfig = null;
        if($isUserMainCfg) {
            $this->preUserConfig = $this->config;
            $this->config = Array();
        }

        // loop trough array
        for ($i = 0; $i < $countLines; $i++) {
            // cut spaces from beginning and end
            $line = trim($file[$i]);

            // get first char of actual line
            $firstChar = substr($line,0,1);

            // check what's in this line
            if($firstChar == ';' || $line == '') {
                if($isUserMainCfg) {
                    // comment...
                    $key = 'comment_'.($numComments++);
                    $val = trim($line);

                    if(isset($sec) && $sec != '') {
                        $this->config[$sec][$key] = $val;
                    } else {
                        $this->config[$key] = $val;
                    }
                }
            } elseif ((substr($line, 0, 1) == '[') && (substr($line, -1, 1)) == ']') {
                // section
                $sec = trim(substr($line, 1, strlen($line)-2));

                // write to array
                if(!isset($this->config[$sec])) {
                    if(preg_match('/^backend_/i', $sec)) {
                        $this->config[$sec] = Array();
                        $this->config[$sec]['backendid'] = str_replace('backend_', '', $sec);
                    } elseif(preg_match('/^rotation_/i', $sec)) {
                        $this->config[$sec] = Array();
                        $this->config[$sec]['rotationid'] = str_replace('rotation_', '', $sec);
                    } elseif(preg_match('/^action_/i', $sec)) {
                        $this->config[$sec] = Array();
                        $this->config[$sec]['action_id'] = str_replace('action_', '', $sec);
                    } else {
                        $this->config[$sec] = Array();
                    }
                }
            } else {
                // parameter...

                // separate string in an array
                $arr = explode('=', $line);
                // read key from array and delete it
                $key = strtolower(trim($arr[0]));
                unset($arr[0]);
                // build string from rest of array
                $val = trim(implode('=', $arr));

                // remove " at beginning and at the end of the string
                if ((substr($val,0,1) == '"') && (substr($val,-1,1)=='"')) {
                    $val = substr($val,1,strlen($val)-2);
                }

                // Try to get the valid config array. But be aware. This is not the whole
                // truth. Since we might not know the (backend|action)_type, there are some
                // vars missing in this array. But this is ok for us ... for the moment.
                if (substr($sec, 0, 7) == 'action_') {
                    $validConfig = $this->validConfig['action'];

                } elseif (substr($sec, 0, 8) == 'backend_') {
                    $validConfig = $this->validConfig['action'];

                } elseif (isset($this->validConfig[$sec])) {
                    $validConfig = $this->validConfig[$sec];
                } else {
                    $validConfig = array();
                }

                // Special options (Arrays)
                if(isset($validConfig[$key]['array']) && $validConfig[$key]['array'] === true) {
                    $val = $this->stringToArray($val);

                } elseif(substr($sec, 0, 9) == 'rotation_' && $key == 'maps') {
                    // Explode comma separated list to array
                    $val = explode(',', $val);

                    // Check if an element has a label defined
                    foreach($val AS $id => $element) {
                        if(preg_match("/^([^\[.]+:)?(\[(.+)\]|(.+))$/", $element, $arrRet)) {
                            $label = '';
                            $map = '';

                            // When no label is set, set map or url as label
                            if($arrRet[1] != '') {
                                $label = substr($arrRet[1],0,-1);
                            } else {
                                if($arrRet[3] != '') {
                                    $label = $arrRet[3];
                                } else {
                                    $label = $arrRet[4];
                                }
                            }

                            if(isset($arrRet[4]) && $arrRet[4] != '') {
                                // Remove leading/trailing spaces
                                $map = $arrRet[4];
                            }

                            // Remove surrounding spaces
                            $label = trim($label);
                            $map = trim($map);

                            // Save the extracted information to an array
                            $val[$id] = Array('label' => $label, 'map' => $map, 'url' => $arrRet[3], 'target' => '');
                        }
                    }
                }

                // write in config array
                if(isset($sec))
                    $this->config[$sec][$key] = $val;
                else
                    $this->config[$key] = $val;
            }
        }

        // Reapply the separated config
        if($isUserMainCfg && $this->preUserConfig !== null) {
            foreach($this->preUserConfig AS $sec => $opts) {
                foreach($opts AS $opt => $val) {
                    if(!isset($this->config[$sec]))
                        $this->config[$sec] = $opts;
                    elseif(!isset($this->config[$sec][$opt]))
                        $this->config[$sec][$opt] = $val;
                }
            }
        }


        return $this->checkMainConfigIsValid(1);
    }

    /**
     * Returns the computed (merged) valid configuration for the instanciated section
     * of a specific type. Is used for "backend" and "action" at the moment.
     */
    private function getInstanceableValidConfig($what, $sec) {
        $ty = $this->getValue($sec, ($what == 'backend' ? 'backendtype' : 'action_type'));

        if(isset($this->validConfig[$what]['options'][$ty])
             && is_array($this->validConfig[$what]['options'][$ty])) {
            return array_merge($this->validConfig[$what], $this->validConfig[$what]['options'][$ty]);
        } else {
            return $this->validConfig[$what];
        }
    }

    /**
     * Checks if the main config file is valid
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkMainConfigIsValid($printErr) {
        // check given objects and attributes
        foreach($this->config AS $type => &$vars) {
            if(!preg_match('/^comment_/',$type)) {
                if(isset($this->validConfig[$type]) || preg_match('/^(backend|rotation|action)_/', $type)) {
                    // loop validConfig for checking: => missing "must" atributes
                    if(preg_match('/^backend_/', $type)) {
                        $arrValidConfig = $this->getInstanceableValidConfig('backend', $type);

                    } elseif(preg_match('/^rotation_/', $type)) {
                        $arrValidConfig = $this->validConfig['rotation'];

                    } elseif(preg_match('/^action_/', $type)) {
                        $arrValidConfig = $this->getInstanceableValidConfig('action', $type);

                    } else {
                        $arrValidConfig = $this->validConfig[$type];
                    }
                    foreach($arrValidConfig AS $key => &$val) {
                        if((isset($val['must']) && $val['must'] == '1')) {
                            // value is "must"
                            if($this->getValue($type,$key) === null) {
                                // a "must" value is missing or empty
                                throw new NagVisException(l('The needed attribute [ATTRIBUTE] is missing in section [TYPE] in main configuration file. Please take a look at the documentation.',
                                                            Array('ATTRIBUTE' => $key, 'TYPE' => $type)));
                                return FALSE;
                            }
                        }
                    }

                    // loop given elements for checking: => all given attributes valid
                    foreach($vars AS $key => $val) {
                        if(!preg_match('/^comment_/', $key)) {
                            if(preg_match('/^backend_/', $type)) {
                                $ty = $this->getValue($type,'backendtype');
                                if(isset($this->validConfig['backend']['options'][$ty])
                                     && is_array($this->validConfig['backend']['options'][$ty])) {
                                    $arrValidConfig = array_merge($this->validConfig['backend'], $this->validConfig['backend']['options'][$ty]);
                                } else {
                                    $arrValidConfig = $this->validConfig['backend'];
                                }

                            } elseif(preg_match('/^rotation_/', $type)) {
                                $arrValidConfig = $this->validConfig['rotation'];

                            } elseif(preg_match('/^action_/', $type)) {
                                $ty = $this->getValue($type, 'action_type');
                                if(isset($this->validConfig['action']['options'][$ty])
                                     && is_array($this->validConfig['action']['options'][$ty])) {
                                    $arrValidConfig = array_merge($this->validConfig['action'], $this->validConfig['action']['options'][$ty]);
                                } else {
                                    $arrValidConfig = $this->validConfig['action'];
                                }

                            } else {
                                $arrValidConfig = $this->validConfig[$type];
                            }

                            if(!isset($arrValidConfig[$key])) {
                                // unknown attribute
                                if($printErr) {
                                    throw new NagVisException(l('Unknown value [ATTRIBUTE] used in section [TYPE] in main configuration file.',
                                                                Array('ATTRIBUTE' => $key, 'TYPE' => $type)));
                                }
                                return FALSE;
                            } elseif(isset($arrValidConfig[$key]['deprecated']) && $arrValidConfig[$key]['deprecated'] == 1) {
                                // deprecated option
                                if($printErr) {
                                    throw new NagVisException(l('The attribute [ATTRIBUTE] in section [TYPE] in main configuration file is deprecated. Please take a look at the documentation for updating your configuration.',
                                                                Array('ATTRIBUTE' => $key, 'TYPE' => $type)));
                                }
                                return FALSE;
                            } else {
                                // Workaround to get the configured string back
                                if(preg_match('/^rotation_/', $type) && $key == 'maps') {
                                    foreach($val AS $intId => $arrStep) {
                                        if(isset($arrStep['label']) && $arrStep['label'] != '') {
                                            $label = $arrStep['label'].':';
                                        }

                                        $val[$intId] = $label.$arrStep['url'].$arrStep['map'];
                                    }
                                }

                                if(isset($val) && is_array($val)) {
                                    $val = implode(',',$val);
                                }

                                // valid attribute, now check for value format
                                if(!preg_match($arrValidConfig[$key]['match'],$val)) {
                                    // wrong format
                                    if($printErr) {
                                        throw new NagVisException(l('The attribute [ATTRIBUTE] in section [TYPE] in main configuration file does not match the correct format. Please review your configuration.',
                                                                    Array('ATTRIBUTE' => $key, 'TYPE' => $type)));
                                    }
                                    return FALSE;
                                }

                                // Check if the configured backend is defined in main configuration file
                                if(!$this->onlyUserConfig && $type == 'defaults' && $key == 'backend' && !isset($this->config['backend_'.$val])) {
                                    if($printErr) {
                                        throw new NagVisException(l('backendNotDefined', Array('BACKENDID' => $val)));
                                    }
                                    return FALSE;
                                }
                            }
                        }
                    }
                } else {
                    // unknown type
                    if($printErr) {
                        throw new NagVisException(
                            l('The section [TYPE] is not supported in main configuration. Please take a look at the documentation.',
                              Array('TYPE' => $type)));
                    }
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Returns the last modification time of the configuration file
     *
     * @return	Integer	Unix Timestamp
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getConfigFileAge() {
        $newest = 0;
        foreach($this->configFiles AS $configFile) {
            $age = filemtime($configFile);
            $newest = ($age > $newest ? $age : $newest);
        }
        return $newest;
    }

    /**
     * Public Adaptor for the isCached method of CACHE object
     *
     * @return  Boolean  Result
     * @return  Integer  Unix timestamp of cache creation time or -1 when not cached
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function isCached() {
        return $this->useCache;
    }

    /**
     * Sets a config setting
     *
     * @param	String	$sec	Section
     * @param	String	$var	Variable
     * @param	String	$val	Value
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setValue($sec, $var, $val) {
        if(isset($this->config[$sec][$var]) && $val == '') {
            // Value is empty and there is an entry in the config array
            unset($this->config[$sec][$var]);
        } elseif(!isset($this->config[$sec][$var]) && $val == '') {
            // Value is empty and there is nothing in config array yet
        } else {
            // Value is set
            if(isset($this->validConfig[$sec][$var]['array']) && $this->validConfig[$sec][$var]['array'] == true && !is_array($val)) {
                $val = $this->stringToArray($val);
            }

            $this->config[$sec][$var] = $val;
        }
        return TRUE;
    }

    public function unsetValue($sec, $var) {
        unset($this->config[$sec][$var]);
    }

    public function getPath($type, $loc, $var, $relfile = '') {
        $lb = $this->getValue('paths', 'local_base', True) . HTDOCS_DIR;
        $b  = $this->getValue('paths', 'base') . HTDOCS_DIR;

        $lh = $this->getValue('paths', 'local_htmlbase', True);
        $h  = $this->getValue('paths', 'htmlbase');

        // Get the relative path
        if(isset($this->config['paths']) && isset($this->config['paths'][$var]))
            $relpath = $this->config['paths'][$var];
        else
            $relpath = $this->validConfig['paths'][$var]['default'];

        // Compute the full system paths
        $l_file = $lb !== FALSE && $lb !== '' ? $lb . '/' . $relpath . $relfile : null;
        $file   = $b . '/' . $relpath . $relfile;

        // Decide which path to return
        // When $loc is set to local it returns the local path
        // When $loc is set to global it returns the global path
        // When $loc is empty it checks if the local one exist and returns this when
        // existing. Otherwise it returns the global one when existing. When the global
        // is also not existant it returns an empty string
        if($loc === 'local' || ($loc === '' && $l_file && file_exists($l_file)))
            return $type == 'sys' ? $l_file : $lh . '/' . $relpath . $relfile;
        elseif($loc === 'global' || ($loc === '' && file_exists($file)))
            return $type == 'sys' ? $file : $h . '/' . $relpath . $relfile;
        else
            return '';
    }

    /**
     * returns the hard coded default value of a config option
     * FIXME: Needs to be simplified
     */
    public function getDefaultValue($sec, $var) {
        // Speed up this method by first checking for major sections and only if
        // they don't match try to match the backend_ and rotation_ sections
        if($sec == 'global' || $sec == 'defaults' || $sec == 'paths') {
            return $this->validConfig[$sec][$var]['default'];
        } elseif(strpos($sec, 'backend_') === 0) {

            // Choose the backend type (Configured one or the system default)
            $backendType = '';
            if(isset($this->config[$sec]['backendtype']) && $this->config[$sec]['backendtype'] !== '') {
                $backendType = $this->config[$sec]['backendtype'];
            } else {
                $backendType = $this->validConfig['backend']['backendtype']['default'];
            }

            // This value could be emtpy - so only check if it is set
            if(isset($this->validConfig['backend']['options'][$backendType][$var]['default'])) {
                return $this->validConfig['backend']['options'][$backendType][$var]['default'];
            } else {
                // This value could be emtpy - so only check if it is set
                if(isset($this->validConfig['backend'][$var]['default'])) {
                    return $this->validConfig['backend'][$var]['default'];
                }
            }

        } elseif(strpos($sec, 'rotation_') === 0) {
            if(isset($this->config[$sec]) && is_array($this->config[$sec])) {
                return $this->validConfig['rotation'][$var]['default'];
            } else {
                return null;
            }

        } elseif(strpos($sec, 'action_') === 0) {
            if(!isset($this->config[$sec]['action_type']))
                return null;
            $ty = $this->config[$sec]['action_type'];

            // This value could be emtpy - so only check if it is set
            if(isset($this->validConfig['action']['options'][$ty][$var]['default'])) {
                return $this->validConfig['action']['options'][$ty][$var]['default'];
            } else {
                // This value could be emtpy - so only check if it is set
                if(isset($this->validConfig['action'][$var]['default'])) {
                    return $this->validConfig['action'][$var]['default'];
                }
            }

        } else {
            return $this->validConfig[$sec][$var]['default'];
        }
    }

    /**
     * Returns the value of a main configuration option. Either the hard coded default
     * value, or the configured one
     */
    public function getValue($sec, $var, $ignoreDefault=false, $ignoreUserConfig=false) {
        if ($ignoreUserConfig && $this->preUserConfig !== null)
            $arr = $this->preUserConfig;
        else
            $arr = $this->config;

        if (isset($arr[$sec]) && isset($arr[$sec][$var])) {
            return $arr[$sec][$var];
        }
        elseif (!$ignoreDefault) {
            return $this->getDefaultValue($sec, $var);
        }
        else {
            return null;
        }
    }

    /**
     * A getter to provide all section names of main configuration
     *
     * @return  Array  List of all sections as values
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getSections() {
        $aRet = Array();
        foreach($this->config AS $key => $var) {
            $aRet[] = $key;
        }
        return $aRet;
    }

    /**
     * Sets a runtime config value
     *
     * @param	String	$var	Variable
     * @param	String	$val	Value
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setRuntimeValue($var, $val) {
        $this->runtimeConfig[$var] = $val;
        return TRUE;
    }

    /**
     * Gets a runtime config value
     *
     * @param	String	$var	Variable
     * @return	String	$val	Value
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getRuntimeValue($var) {
        return isset($this->runtimeConfig[$var]) ? $this->runtimeConfig[$var] : '';
    }

    /**
     * Parses general settings
     *
     * @return	String 	JSON Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parseGeneralProperties() {
        $p = Array(
          'controls_size'      => intval($this->getValue('global', 'controls_size')),
          'date_format'        => $this->getValue('global', 'dateformat'),
          'path_base'          => $this->getValue('paths','htmlbase'),
          'path_cgi'           => $this->getValue('paths','htmlcgi'),
          'path_sounds'        => $this->getPath('html', 'global', 'sounds'),
          'path_iconsets'      => $this->getPath('html', 'global', 'icons'),
          'path_shapes'        => $this->getPath('html', 'global', 'shapes'),
          'path_images'        => $this->getValue('paths','htmlimages'),
          'path_server'        => $this->getValue('paths','htmlbase').'/server/core/ajax_handler.php',
          'internal_title'     => $this->getValue('internal', 'title'),
          'header_show_states' => intval($this->getValue('defaults', 'header_show_states')),
          'zoom_scale_objects' => intval($this->getValue('defaults', 'zoom_scale_objects')),
        );

        // Add custom action configuration
        $p['actions'] = array();
        foreach (GlobalCore::getInstance()->getDefinedCustomActions() as $id) {
            $p['actions'][$id] = array(
                'obj_type'  => $this->getValue('action_'.$id, 'obj_type'),
                'client_os' => $this->getValue('action_'.$id, 'client_os'),
                'condition' => $this->getValue('action_'.$id, 'condition')
            );
        }

        return json_encode($p);
    }

    /**
     * Parses the settings for the javascript worker
     *
     * @return	String 	JSON Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parseWorkerProperties() {
        return json_encode(Array(
            'worker_interval'             => $this->getValue('worker', 'interval'),
            'worker_update_object_states' => $this->getValue('worker', 'updateobjectstates'),
            'worker_request_max_params'   => $this->getValue('worker', 'requestmaxparams'),
            'worker_request_max_length'   => $this->getValue('worker', 'requestmaxlength')
        ));
    }

    /**
     * Populates the state weight structure, provided by hardcoded defaults
     * and maybe the user configuration
     */
    private function parseStateWeight() {
        $arr = Array();

        foreach($this->validConfig['states'] AS $lowState => $aVal) {
            $key = explode('_', $lowState);

            // Convert state values to int
            $last_part = $key[sizeof($key) - 1];
            if($last_part != 'color' && $last_part != 'bgcolor' && $last_part != 'sound') {
                $val = intval($this->getValue('states', $lowState));
            } else {
                $val = $this->getValue('states', $lowState);
            }

            $state = state_num(strtoupper($key[0]));

            // First create array when not exists
            if(!isset($arr[$state])) {
                $arr[$state] = Array();
            }

            if(isset($key[1]) && isset($key[2])) {
                // at the moment only bg colors of substates
                $arr[$state][$key[1].'_'.$key[2]] = $val;
            } elseif(isset($key[1])) {
                // ack/downtime
                $arr[$state][$key[1]] = $val;
            } else {
                // normal state definition
                $arr[$state]['normal'] = $val;
            }
        }

        $this->stateWeight = $arr;
    }

    /**
     * Returns an array with the state weight configuration
     *
     * @return  Array
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getStateWeight() {
        return $this->stateWeight;
    }

    /**
     * Returns an array with the state weight configuration for the
     * JS frontend, which is not yet aware of numeric state codes. So
     * translate the states here.
     */
    public function getStateWeightJS() {
        $arr = array();
        foreach ($this->stateWeight AS $state => $val) {
            $arr[state_str($state)] = $val;
        }
        return $arr;
    }

    /**
     * FIXME: Below you will find all WUI specific function. All need to be reviewed
     */

    /**
     * Gets all information about an object type
     *
     * @param   String  Type to get the information for
     * @return  Array   The validConfig array
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    function getValidObjectType($type) {
        return $this->validConfig[$type];
    }

    /**
     * Gets the valid configuration array
     *
     * @return	Array The validConfig array
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    function getValidConfig() {
        return $this->validConfig;
    }

    /**
     * Gets the configuration array
     *
     * @return	Array The validConfig array
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    function getConfig() {
        return $this->config;
    }

    /**
     * Sets a config section in the config array
     *
     * @param	String	$sec	Section
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    function setSection($sec) {
        // Try to append new backends after already defined
        if(preg_match('/^backend_/', $sec)) {
            $lastBackendIndex = 0;
            $i = 0;
            // Loop all sections to find the last defined backend
            foreach($this->config AS $type => $vars) {
                // If the current section is a backend
                if(preg_match('/^backend_/', $type)) {
                    $lastBackendIndex = $i;
                }
                $i++;
            }

            if($lastBackendIndex != 0) {
                // Append the new section after the already defined
                $slicedBefore = array_slice($this->config, 0, ($lastBackendIndex + 1));
                $slicedAfter = array_slice($this->config, ($lastBackendIndex + 1));
                $tmp[$sec] = Array();
                $this->config = array_merge($slicedBefore,$tmp,$slicedAfter);
            } else {
                // If no defined backend found, add it to the EOF
                $this->config[$sec] = Array();
            }
        } else {
            $this->config[$sec] = Array();
        }

        return TRUE;
    }

    /**
     * Deletes a config section in the config array
     *
     * @param	String	$sec	Section
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    function delSection($sec) {
        $this->config[$sec] = '';
        unset($this->config[$sec]);

        return TRUE;
    }

    /**
     * Writes the config file completly from array $this->configFile
     *
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    function writeConfig() {
        // Check for config file write permissions
        if(!$this->checkNagVisConfigWriteable(1))
            return false;

        $content = '';
        foreach($this->config as $key => $item) {
            if(is_array($item)) {
                $content .= '['.$key.']'."\n";
                foreach ($item as $key2 => $item2) {
                    if(substr($key2,0,8) == 'comment_') {
                        $content .= $item2."\n";
                    } else {
                        if(is_numeric($item2) || is_bool($item2)) {
                            // Don't apply config options which are set to the same
                            // value in the pre user config files
                            if($this->preUserConfig !== null
                               && isset($this->preUserConfig[$key])
                               && isset($this->preUserConfig[$key][$key2])
                               && $item2 == $this->preUserConfig[$key][$key2])
                                continue;
                            $content .= $key2."=".$item2."\n";
                        } else {
                            if(is_array($item2) && preg_match('/^rotation_/i', $key) && $key2 == 'maps') {
                                $val = '';
                                // Check if an element has a label defined
                                foreach($item2 AS $intId => $arrStep) {
                                    $seperator = ',';
                                    $label = '';
                                    $step = '';

                                    if($intId == 0)
                                        $seperator = '';

                                    if(isset($arrStep['map']) && $arrStep['map'] != '')
                                        $step = $arrStep['map'];
                                    else
                                        $step = '['.$arrStep['url'].']';

                                    if(isset($arrStep['label']) && $arrStep['label'] != '' && $arrStep['label'] != $step)
                                        $label = $arrStep['label'].':';

                                    // Save the extracted information to an array
                                    $val .= $seperator.$label.$step;
                                }

                                $item2 = $val;
                            }

                            // Don't write the backendid/rotationid attributes (Are internal)
                            if($key2 !== 'backendid' && $key2 !== 'rotationid' && $key2 !== 'action_id') {
                                // Don't apply config options which are set to the same
                                // value in the pre user config files
                                if($this->preUserConfig !== null
                                   && isset($this->preUserConfig[$key])
                                   && isset($this->preUserConfig[$key][$key2])
                                   && $item2 == $this->preUserConfig[$key][$key2])
                                    continue;

                                if (substr($key, 0, 8) == 'backend_')
                                    $arrValidConfig = $this->getInstanceableValidConfig('backend', $key);
                                elseif (substr($key, 0, 9) == 'rotation_')
                                    $arrValidConfig = $this->validConfig['rotation'];
                                elseif (substr($key, 0, 7) == 'action_')
                                    $arrValidConfig = $this->getInstanceableValidConfig('action', $key);
                                else
                                    $arrValidConfig = $this->validConfig[$key];

                                if(isset($arrValidConfig[$key2]['array']) && $arrValidConfig[$key2]['array'] === true)
                                    $item2 = implode(',', $item2);

                                $content .= $key2.'="'.$item2.'"'."\n";
                            }
                        }
                    }
                }
            } elseif(substr($key,0,8) == 'comment_')
                $content .= $item."\n";
        }

        $cfgFile = $this->configFiles[count($this->configFiles)-1];
        if(!$handle = fopen($cfgFile, 'w+'))
            throw new NagVisException(l('mainCfgNotWriteable'));

        if(!fwrite($handle, $content))
            throw new NagVisException(l('mainCfgCouldNotWriteMainConfigFile'));

        fclose($handle);
        GlobalCore::getInstance()->setPerms($cfgFile);

        return TRUE;
    }

    /**
     * Checks for writeable config file
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    function checkNagVisConfigWriteable($printErr) {
        return GlobalCore::getInstance()->checkWriteable($this->configFiles[count($this->configFiles)-1], $printErr);
    }

    /**
   * Transforms a string option to an array with trimmed values
   *
   * @param  String  Comma separated value
   * @return Array   Exploded Array
   */
    private function stringToArray($val) {
        // Explode comma separated list to array
        $val = explode(',', $val);

        // Trim surrounding spaces on each element
        foreach($val AS $trimKey => $trimVal)
            $val[$trimKey] = trim($trimVal);

        return $val;
    }

    public function getSectionTitle($sec) {
        $titles = array(
            'global'   => l('Global Settings'),
            'defaults' => l('Object Defaults'),
            'index'    => l('Overview Page'),
            'worker'   => l('State Updates'),
            'states'   => l('States'),
            'paths'    => l('Paths'),
            'automap'  => l('Automap'),
        );
        if (isset($titles[$sec]))
            return $titles[$sec];
        else
            return $sec;
    }

    /**
     * Returns the name of the list function for the given map config option
     */
    public function getListFunc($sec, $key) {
        if(isset($this->validConfig[$sec][$key]['list']))
            return $this->validConfig[$sec][$key]['list'];
        else
            throw new NagVisException(l('No "list" function registered for option "[OPT]" of type "[TYPE]"',
                                                                       Array('OPT' => $sec, 'TYPE' => $key)));
    }

    /**
     * Finds out if an attribute has dependant attributes
     */
    public function hasDependants($sec, $key) {
        foreach ($this->validConfig[$sec] AS $arr)
            if (isset($arr['depends_on']) && $arr['depends_on'] == $key)
                return true;
        return false;
    }
}
?>
