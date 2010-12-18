<?php
/*****************************************************************************
 *
 * GlobalMapCfg.php - Class for handling the map configuration files of NagVis
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalMapCfg {
	private $CORE;
	public $BACKGROUND;
	private $CACHE;
	private $DCACHE;
	
	protected $name;
	protected $type = 'map';
	protected $mapConfig;
  protected $typeDefaults = Array();
	
	private $configFile = '';
	protected $cacheFile = '';
	protected $defaultsCacheFile = '';
	protected $mapLockPath;
	
	// Array for config validation
	protected static $validConfig = null;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalCore	$CORE
	 * @param	String			$name		Name of the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $name='') {
		$this->CORE = $CORE;
		$this->name	= $name;
		
		if(self::$validConfig == null) {
			self::$validConfig = Array(
				'global' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE,
						'field_type' => 'hidden'),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden'),
					'allowed_for_config' => Array('must' => 0,
						'deprecated' => 1,
						'match' => MATCH_STRING),
					'allowed_user' => Array('must' => 0,
						'deprecated' => 1,
						'match' => MATCH_STRING),
					'map_image' => Array('must' => 0,
						'match' => MATCH_PNG_GIF_JPG_FILE_OR_URL_NONE,
						'field_type' => 'dropdown'),
					'alias' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING),
					'backend_id' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'backend'),
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown'),
					'background_color' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'backgroundcolor'),
						'match' => MATCH_COLOR),
					'default_params' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('automap', 'defaultparams'),
						'match' => MATCH_STRING_URL,
						'field_type' => 'hidden'),
					'parent_map' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_MAP_NAME_EMPTY),
					
					'context_menu' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'contextmenu'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'context_template' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'contexttemplate'),
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'context_menu',
						'depends_value' => '1'),
					
					'event_background' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventbackground'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					
					'event_highlight' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventhighlight'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'event_highlight_interval' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventhighlightinterval'),
						'match' => MATCH_INTEGER,
						'depends_on' => 'event_highlight',
						'depends_value' => '1'),
					'event_highlight_duration' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventhighlightduration'),
						'match' => MATCH_INTEGER,
						'depends_on' => 'event_highlight',
						'depends_value' => '1'),
					
					'event_log' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventlog'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'event_log_level' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventloglevel'),
						'match' => MATCH_STRING_NO_SPACE,
						'depends_on' => 'event_log',
						'depends_value' => '1'),
					'event_log_height' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventlogheight'),
						'match' => MATCH_INTEGER,
						'depends_on' => 'event_log',
						'depends_value' => '1'),
					'event_log_hidden' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventloghidden'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean',
						'depends_on' => 'event_log',
						'depends_value' => '1'),
					
					'event_scroll' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventscroll'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'event_sound' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'eventsound'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					
					'grid_show' => Array('must' => 0,
						'default' => intval($this->CORE->getMainCfg()->getValue('wui', 'grid_show')),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'grid_color' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('wui', 'grid_color'),
						'match' => MATCH_COLOR,
						'depends_on' => 'grid_show',
						'depends_value' => '1'),
					'grid_steps' => Array('must' => 0,
						'default' => intval($this->CORE->getMainCfg()->getValue('wui', 'grid_steps')),
						'match' => MATCH_INTEGER,
						'depends_on' => 'grid_show',
						'depends_value' => '1'),
					
					'header_menu' => Array('must' => 0,
						'default'        => $this->CORE->getMainCfg()->getValue('defaults', 'headermenu'),
						'match'          => MATCH_BOOLEAN,
						'field_type'     => 'boolean'),
					'header_template' => Array('must' => 0,
						'default'        => $this->CORE->getMainCfg()->getValue('defaults', 'headertemplate'),
						'match'          => MATCH_STRING_NO_SPACE,
						'field_type'     => 'dropdown',
						'depends_on'     => 'header_menu',
						'depends_value'  => '1'),
					'header_fade' => Array('must' => 0,
						'default'        => $this->CORE->getMainCfg()->getValue('defaults', 'headerfade'),
						'match'          => MATCH_BOOLEAN,
						'field_type'     => 'boolean',
						'depends_on'     => 'header_menu',
						'depends_value'  => '1'),
					
					'hover_menu' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hovermenu'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'hover_delay' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hoverdelay'),
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_template' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hovertemplate'),
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_timeout' => Array('must' => 0,
						'deprecated' => '1',
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hovertimeout'),
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_show' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hoverchildsshow'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_limit' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hoverchildslimit'),
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_order' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hoverchildsorder'),
						'match' => MATCH_ORDER,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_sort' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hoverchildssort'),
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					
					'iconset' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'icons'),
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown'),
					'line_type' => Array('must' => 0,
						'match' => MATCH_LINE_TYPE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					// At the moment this value is only used for the automap to controll
					// the style of the connector arrow. But maybe this attribute can be
					// used on regular maps for line objects too.
					'line_arrow' => Array('must' => 0,
						'default' => 'forward',
						'match' => MATCH_LINE_ARROW,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_cut' => Array(
						'must'          => 0,
						'default'       => '0.5',
						'match'         => MATCH_FLOAT,
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_width' => Array('must' => 0,
						'default' => '3',
						'match' => MATCH_INTEGER,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_weather_colors' => Array('must' => 0,
						'default' => '10:#8c00ff,25:#2020ff,40:#00c0ff,55:#00f000,70:#f0f000,85:#ffc000,100:#ff0000',
						'match' => MATCH_WEATHER_COLORS,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					
					'in_maintenance' => Array('must' => 0,
						'default' => '0',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					
					'label_show' => Array('must' => 0,
						'default' => '0',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'label_text' => Array('must' => 0,
						'default' => '[name]',
						'match' => MATCH_ALL,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_x' => Array('must' => 0,
						'default' => '-20',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_y' => Array('must' => 0,
						'default' => '+20',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_width' => Array('must' => 0,
						'default' => 'auto',
						'match' => MATCH_TEXTBOX_WIDTH,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_background' => Array('must' => 0,
						'default' => 'transparent',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_border' => Array('must' => 0,
						'default' => '#000000',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_style' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_STYLE,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					
					'only_hard_states' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'onlyhardstates'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'recognize_services' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'recognizeservices'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'show_in_lists' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'showinlists'),
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'stylesheet' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'stylesheet'),
						'match' => MATCH_STRING_NO_SPACE),
					'url_target' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'urltarget'),
						'match' => MATCH_STRING_NO_SPACE),
				),
				
				'host' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE,
						'field_type' => 'hidden'),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden'),
					'host_name' => Array('must' => 1,
						'match' => MATCH_STRING,
						'field_type' => 'dropdown'),
					'x' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'y' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'z' => Array('must' => 0,
						'default' => 10,
						'match' => MATCH_INTEGER),
					'backend_id' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown'),
					
					'view_type' => Array('must' => 0,
						'default' => 'icon',
						'match' => MATCH_VIEW_TYPE,
						'field_type' => 'dropdown'),
					'iconset' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'icon'),
					'line_type' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_TYPE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					// At the moment this value is only used for the automap to controll
					// the style of the connector arrow. But maybe this attribute can be
					// used on regular maps for line objects too.
					'line_arrow' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_ARROW,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_cut' => Array(
						'must'          => 0,
						'default'       => '0.5',
						'match'         => MATCH_FLOAT,
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_weather_colors' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_WEATHER_COLORS,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					
					'context_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'context_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'context_menu',
						'depends_value' => '1'),
					
					'hover_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'hover_delay' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_timeout' => Array('must' => 0,
						'default' => '',
						'deprecated' => '1',
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_url' => Array('must' => 0,
						'match' => MATCH_STRING_URL,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_sort' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_order' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_ORDER,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_limit' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					
					'label_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'label_text' => Array('must' => 0,
						'default' => '[name]',
						'match' => MATCH_ALL,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_x' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_y' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_TEXTBOX_WIDTH,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_background' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_border' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_style' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_STYLE,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					
					'only_hard_states' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'recognize_services' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					
					'url' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hosturl'),
						'match' => MATCH_STRING_URL_EMPTY),
					'url_target' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE),
					
					'use' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE)),
				
				'hostgroup' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE,
						'field_type' => 'hidden'),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden'),
					'hostgroup_name' => Array('must' => 1,
						'match' => MATCH_STRING,
						'field_type' => 'dropdown'),
					'x' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'y' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'z' => Array('must' => 0,
						'default' => 10,
						'match' => MATCH_INTEGER),
					'backend_id' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown'),
					
					'view_type' => Array('must' => 0,
						'default' => 'icon',
						'match' => MATCH_VIEW_TYPE,
						'field_type' => 'dropdown'),
					'iconset' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'icon'),
					'line_type' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_TYPE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					// At the moment this value is only used for the automap to controll
					// the style of the connector arrow. But maybe this attribute can be
					// used on regular maps for line objects too.
					'line_arrow' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_ARROW,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_cut' => Array(
						'must'          => 0,
						'default'       => '0.5',
						'match'         => MATCH_FLOAT,
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_weather_colors' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_WEATHER_COLORS,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					
					'context_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'context_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'context_menu',
						'depends_value' => '1'),
					
					'hover_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'hover_delay' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_timeout' => Array('must' => 0,
						'default' => '',
						'deprecated' => '1',
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_url' => Array('must' => 0,
						'match' => MATCH_STRING_URL,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_sort' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_order' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_ORDER,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_limit' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					
					'label_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'label_text' => Array('must' => 0,
						'default' => '[name]',
						'match' => MATCH_ALL,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_x' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_y' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_TEXTBOX_WIDTH,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_background' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_border' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_style' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_STYLE,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					
					'only_hard_states' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'recognize_services' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					
					'url' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'hostgroupurl'),
						'match' => MATCH_STRING_URL_EMPTY),
					'url_target' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE),
					
					'use' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE)),
				
				'service' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE,
						'field_type' => 'hidden'),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden'),
					'host_name' => Array('must' => 1,
						'match' => MATCH_STRING,
						'field_type' => 'dropdown'),
					'service_description' => Array('must' => 1,
						'match' => MATCH_STRING,
						'field_type' => 'dropdown'),
					'x' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'y' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'z' => Array('must' => 0,
						'default' => 10,
						'match' => MATCH_INTEGER),
					'backend_id' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown'),
					'view_type' => Array('must' => 0,
						'default' => 'icon',
						'match' => MATCH_VIEW_TYPE_SERVICE,
						'field_type' => 'dropdown'),
					'iconset' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'icon'),
					'line_type' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_TYPE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					// At the moment this value is only used for the automap to controll
					// the style of the connector arrow. But maybe this attribute can be
					// used on regular maps for line objects too.
					'line_arrow' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_ARROW,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_cut' => Array(
						'must'          => 0,
						'default'       => '0.5',
						'match'         => MATCH_FLOAT,
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_label_show' => Array(
						'must'          => 0,
						'default'       => '1',
						'match'         => MATCH_BOOLEAN,
						'field_type'    => 'boolean',
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_label_pos_in' => Array(
						'must'          => 0,
						'default'       => '0.5',
						'match'         => MATCH_FLOAT,
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_label_pos_out' => Array(
						'must'          => 0,
						'default'       => '0.5',
						'match'         => MATCH_FLOAT,
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_weather_colors' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_WEATHER_COLORS,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'gadget_url' => Array('must' => 0,
						'match' => MATCH_STRING_URL,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'gadget'),
					'gadget_scale' => Array('must' => 0,
						'default' => 100,
						'match' => MATCH_INTEGER,
						'depends_on' => 'view_type',
						'depends_value' => 'gadget'),
					'gadget_opts' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_GADGET_OPT,
						'depends_on' => 'view_type',
						'depends_value' => 'gadget'),
					
					'context_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'context_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'context_menu',
						'depends_value' => '1'),
					
					'hover_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'hover_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_timeout' => Array('must' => 0,
						'default' => '',
						'deprecated' => '1',
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_delay' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_url' => Array('must' => 0,
						'match' => MATCH_STRING_URL,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_sort' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_order' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_ORDER,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_limit' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					
					'label_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'label_text' => Array('must' => 0,
						'default' => '[name] [service_description]',
						'match' => MATCH_ALL,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_x' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_y' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_TEXTBOX_WIDTH,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_background' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_border' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_style' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_STYLE,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					
					'only_hard_states' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					
					'url' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'serviceurl'),
						'match' => MATCH_STRING_URL_EMPTY),
					'url_target' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE),
					
					'use' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE)),
				
				'servicegroup' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE,
						'field_type' => 'hidden'),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden'),
					'servicegroup_name' => Array('must' => 1,
						'match' => MATCH_STRING,
						'field_type' => 'dropdown'),
					'x' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'y' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'z' => Array('must' => 0,
						'default' => 10,
						'match' => MATCH_INTEGER),
					'backend_id' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown'),
					
					'view_type' => Array('must' => 0,
						'default' => 'icon',
						'match' => MATCH_VIEW_TYPE,
						'field_type' => 'dropdown'),
					'iconset' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'icon'),
					'line_type' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_TYPE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					// At the moment this value is only used for the automap to controll
					// the style of the connector arrow. But maybe this attribute can be
					// used on regular maps for line objects too.
					'line_arrow' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_ARROW,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_cut' => Array(
						'must'          => 0,
						'default'       => '0.5',
						'match'         => MATCH_FLOAT,
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_weather_colors' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_WEATHER_COLORS,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					
					'context_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'context_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'context_menu',
						'depends_value' => '1'),
					
					'hover_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'hover_delay' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_timeout' => Array('must' => 0,
						'default' => '',
						'deprecated' => '1',
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_url' => Array('must' => 0,
						'match' => MATCH_STRING_URL,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_sort' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_order' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_ORDER,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_limit' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					
					'label_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'label_text' => Array('must' => 0,
						'default' => '[name]',
						'match' => MATCH_ALL,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_x' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_y' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_TEXTBOX_WIDTH,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_background' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_border' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_style' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_STYLE,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					
					'only_hard_states' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					
					'url' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'servicegroupurl'),
						'match' => MATCH_STRING_URL_EMPTY),
					'url_target' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE),
					
					'use' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE)),
				
				'map' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE,
						'field_type' => 'hidden'),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden'),
					'map_name' => Array('must' => 1,
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown'),
					'x' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'y' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'z' => Array('must' => 0,
						'default' => 10,
						'match' => MATCH_INTEGER),
					
					'view_type' => Array('must' => 0,
						'default' => 'icon',
						'match' => MATCH_VIEW_TYPE,
						'field_type' => 'dropdown'),
					'iconset' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'icon'),
					'line_type' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_TYPE,
						'field_type' => 'dropdown',
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					// At the moment this value is only used for the automap to controll
					// the style of the connector arrow. But maybe this attribute can be
					// used on regular maps for line objects too.
					'line_arrow' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_LINE_ARROW,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_cut' => Array(
						'must'          => 0,
						'default'       => '0.5',
						'match'         => MATCH_FLOAT,
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					'line_weather_colors' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_WEATHER_COLORS,
						'depends_on' => 'view_type',
						'depends_value' => 'line'),
					
					'context_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'context_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'context_menu',
						'depends_value' => '1'),
					
					'hover_menu' => Array('must' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'hover_template' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_timeout' => Array('must' => 0,
						'default' => '',
						'deprecated' => '1',
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_delay' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_url' => Array('must' => 0,
						'match' => MATCH_STRING_URL,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_sort' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_NO_SPACE,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_order' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_ORDER,
						'field_type' => 'dropdown',
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					'hover_childs_limit' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'hover_menu',
						'depends_value' => '1'),
					
					'label_show' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'label_text' => Array('must' => 0,
						'default' => '[name]',
						'match' => MATCH_ALL,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_x' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_y' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_INTEGER_PRESIGN,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_width' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_TEXTBOX_WIDTH,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_background' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_border' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_COLOR,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					'label_style' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_STYLE,
						'depends_on' => 'label_show',
						'depends_value' => '1'),
					
					'only_hard_states' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					
					'url' => Array('must' => 0,
						'default' => $this->CORE->getMainCfg()->getValue('defaults', 'mapurl'),
						'match' => MATCH_STRING_URL_EMPTY),
					'url_target' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE),
					
					'use' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE)),
				
				'line' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE,
						'field_type' => 'hidden'),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden'),
					'x' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'y' => Array('must' => 1,
						'match' => MATCH_COORDS_MULTI),
					'z' => Array('must' => 0,
						'default' => 10,
						'match' => MATCH_INTEGER),
					'line_type' => Array('must' => 1,
						'match' => MATCH_LINE_TYPE,
						'field_type' => 'dropdown'),
					'line_cut' => Array(
						'must'          => 0,
						'default'       => '0.5',
						'match'         => MATCH_FLOAT,
						'depends_on'    => 'view_type',
						'depends_value' => 'line'),
					'line_width' => Array('must' => 0,
						'default' => '3',
						'match' => MATCH_INTEGER),
					'line_color' => Array('must' => 0,
						'default' => '#ffffff',
						'match' => MATCH_COLOR),
					'line_color_border' => Array('must' => 0,
						'default' => '#000000',
						'match' => MATCH_COLOR),
					
					'hover_menu' => Array('must' => 0,
						'default' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'hover_url' => Array('must' => 0,
						'match' => MATCH_STRING_URL),
					'hover_delay' => Array('must' => 0,
						'match' => MATCH_INTEGER),
					
					'url' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_URL_EMPTY),
					'url_target' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE),
					
					'use' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE)),
				
				'textbox' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE,
						'field_type' => 'hidden'),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden'),
					'text' => Array('must' => 1,
						'match' => MATCH_ALL),
					'x' => Array('must' => 1,
						'match' => MATCH_INTEGER),
					'y' => Array('must' => 1,
						'match' => MATCH_INTEGER),
					'z' => Array('must' => 0,
						'default' => 5,
						'match' => MATCH_INTEGER),
					'background_color' => Array('must' => 0,
						'default' => '#C0C0C0',
						'match' => MATCH_COLOR),
					'border_color' => Array('must' => 0,
						'default' => '#000000',
						'match' => MATCH_COLOR),
					'style' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_STYLE),
					'use' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE),
					'h' => Array('must' => 0,
						'default' => 'auto',
						'match' => MATCH_TEXTBOX_HEIGHT),
					'w' => Array('must' => 1,
						'match' => MATCH_TEXTBOX_WIDTH)),
				
				'shape' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE,
						'field_type' => 'hidden'),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden'),
					'icon' => Array('must' => 1,
						'match' => MATCH_PNG_GIF_JPG_FILE_OR_URL,
						'field_type' => 'dropdown'),
					'x' => Array('must' => 1,
						'match' => MATCH_INTEGER),
					'y' => Array('must' => 1,
						'match' => MATCH_INTEGER),
					'z' => Array('must' => 0,
						'default' => 1,
						'match' => MATCH_INTEGER),
					'enable_refresh' => Array('must' => 0,
						'default' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					
					'hover_menu' => Array('must' => 0,
						'default' => 0,
						'match' => MATCH_BOOLEAN,
						'field_type' => 'boolean'),
					'hover_url' => Array('must' => 0,
						'match' => MATCH_STRING_URL),
					'hover_delay' => Array('must' => 0,
						'match' => MATCH_INTEGER),
					
					'url' => Array('must' => 0,
						'default' => '',
						'match' => MATCH_STRING_URL_EMPTY),
					'url_target' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE),
					
					'use' => Array('must' => 0,
						'match' => MATCH_STRING_NO_SPACE)),
				'template' => Array('type' => Array('must' => 0,
						'match' => MATCH_OBJECTTYPE),
					'name' => Array('must' => 1,
						'match' => MATCH_STRING_NO_SPACE),
					'object_id' => Array('must' => 0,
						'match' => MATCH_INTEGER,
						'field_type' => 'hidden')));
		}
		
		$this->mapLockPath = $this->CORE->getMainCfg()->getValue('paths', 'mapcfg').$this->name.'.lock';

		// Define the map configuration file when no one set until here
		if($this->configFile === '')
			$this->setConfigFile($this->CORE->getMainCfg()->getValue('paths', 'mapcfg').$name.'.cfg');

		if($this->cacheFile === '') {
			$this->cacheFile = $this->CORE->getMainCfg()->getValue('paths','var').$name.'.cfg-'.CONST_VERSION.'-cache';
			$this->defaultsCacheFile = $this->cacheFile.'-defs';
		}
		
		// Initialize the map configuration cache
		$this->initCache();
	}
	
	/**
	 * Gets the default values for the different object types
	 *
	 * @param   Boolean  Only fetch global type settings
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function gatherTypeDefaults($onlyGlobal) {
		if($onlyGlobal)
			$types = Array('global');
		else
			$types = array_keys(self::$validConfig);
		
		// Extract defaults from valid config array
		foreach($types AS $type) {
			if(!isset($this->typeDefaults[$type]))
				$this->typeDefaults[$type] = Array();
			
			foreach(array_keys(self::$validConfig[$type]) AS $key) {
				if(isset(self::$validConfig[$type][$key]['default']))
					$this->typeDefaults[$type][$key] = self::$validConfig[$type][$key]['default'];
			}
		}

		// Treating the alias
		$this->typeDefaults['global']['alias'] = $this->name;

		if($onlyGlobal)
			return true;
		
		// And now feed the typeDefaults array with the new
		// default options based on the global section of the current map
		$aVars = Array('recognize_services',
			'only_hard_states',
			'backend_id',
			'iconset',
			'line_type',
			'line_width',
			'line_arrow',
			'line_weather_colors',
			'context_menu',
			'context_template',
			'hover_menu',
			'hover_template',
			'hover_timeout',
			'hover_delay',
			'hover_url',
			'label_show',
			'label_text',
			'label_x',
			'label_y',
			'label_width',
			'label_background',
			'label_border',
			'label_style',
			'url_target',
			'hover_childs_show',
			'hover_childs_sort',
			'hover_childs_order',
			'hover_childs_limit');
		foreach($aVars As $sVar) {
			$sTmp = $this->getValue('global', 0, $sVar);
			
			$this->typeDefaults['host'][$sVar] = $sTmp;
			$this->typeDefaults['hostgroup'][$sVar] = $sTmp;
			
			// Handle exceptions for servicegroups
			if($sVar != 'recognize_services') {
				$this->typeDefaults['servicegroup'][$sVar] = $sTmp;
			}
			
			// Handle exceptions for services
			if($sVar != 'recognize_services') {
				$this->typeDefaults['service'][$sVar] = $sTmp;
			}
			
			// Handle exceptions for maps
			if($sVar != 'recognize_services' && $sVar != 'backend_id') {
				$this->typeDefaults['map'][$sVar] = $sTmp;
			}
			
			// Handle exceptions for shapes
			if($sVar == 'url_target' || $sVar == 'hover_delay') {
				$this->typeDefaults['shape'][$sVar] = $sTmp;
			}
			
			// Handle exceptions for lines
			if($sVar == 'url_target' || $sVar == 'hover_delay') {
				$this->typeDefaults['line'][$sVar] = $sTmp;
			}
		}
	}
	
	/**
	 * Initializes the map configuration file caching
	 *
	 * @param   String   Path to the configuration file
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function setConfigFile($file) {
		$this->configFile = $file;
	}
	
	/**
	 * Initializes the map configuration file caching
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function initCache() {
		if($this->cacheFile !== '') {
			$this->CACHE = new GlobalFileCache($this->CORE, $this->configFile, $this->cacheFile);
			$this->DCACHE = new GlobalFileCache($this->CORE, $this->configFile, $this->defaultsCacheFile);
		}
	}
	
	/**
	 * Initializes the background image
	 *
	 * @return	GlobalBackground
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getBackground() {
		$RET = new GlobalBackground($this->CORE, $this->getValue('global', 0, 'map_image'));
		return $RET;
	}
	
	/**
	 * Creates a new Configfile
	 *
	 * @return	Boolean	Is Successful?
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public function createMapConfig() {
		// does file exist?
		if($this->checkMapConfigReadable(false))
			return false;

		if(!$this->checkMapCfgFolderWriteable(true))
			return false;

		// create empty file
		fclose(fopen($this->configFile, 'w')); 
		$this->CORE->setPerms($this->configFile);
		return true;
	}
	
	/**
	 * Reads the map config file (copied from readFile->readNagVisCfg())
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function readMapConfig($onlyGlobal = 0, $resolveTemplates = true, $useCache = true) {
		if($this->name != '') {
			// Only use cache when there is
			// a) The cache should be used
			// b) When whole config file should be read
			// c) Some valid cache file
			// d) Some valid main configuration cache file
			// e) This cache file newer than main configuration cache file
			if($onlyGlobal == 0
			   && $useCache === true
			   && $this->CACHE->isCached() !== -1
				 && $this->CORE->getMainCfg()->isCached() !== -1
				 && $this->CACHE->isCached() >= $this->CORE->getMainCfg()->isCached()) {
				$this->mapConfig = $this->CACHE->getCache();
				$this->typeDefaults = $this->DCACHE->getCache();
				// Cache objects are not needed anymore
				$this->CACHE = null;
				$this->DCACHE = null;
				
				$this->BACKGROUND = $this->getBackground();
				
				return TRUE;
			} else {
				if($this->checkMapConfigExists(TRUE) && $this->checkMapConfigReadable(TRUE)) {
					$this->mapConfig = Array();
					// Array for counting objects
					$types = Array('global' => 0,
									'host' => 0,
									'service' => 0,
									'hostgroup' => 0,
									'servicegroup' => 0,
									'map' => 0,
									'textbox' => 0,
									'shape' => 0,
									'line' => 0,
									'template' => 0);
					
					// Read file in array (Don't read empty lines and ignore new line chars)
					$file = file($this->configFile, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
					
					// Create an array for these options
					$createArray = Array('use' => 1);
										
					// Don't read these keys
					$ignoreKeys = Array('object_id' => 0,
					                    'type' => 0);
					
					$l = 0;
					
					// These variables set which object is currently being filled
					$sObjType = '';
					$iObjTypeId = 0;
					$iObjId = 0;
					
					// Loop each line
					$iNumLines = count($file);
					$unknownObject = null;
					for($l = 0; $l < $iNumLines; $l++) {
						// Remove spaces, newlines, tabs, etc. (http://de.php.net/rtrim)
						$file[$l] = rtrim($file[$l]);

						// Don't recognize empty lines
						if($file[$l] == '')
							continue;

						// Don't recognize comments and empty lines, do nothing with ending delimiters
						$sFirstChar = substr(ltrim($file[$l]), 0, 1);
						if($sFirstChar == ';' || $sFirstChar == '#')
							continue;

						// This is an object ending. Reset the object type and skip to next line
						if($sFirstChar == '}') {
							$sObjType = '';
							$iObjTypeId = 0;

							// If only the global section should be read break the loop after the global section
							if($onlyGlobal == 1 && $types['global'] == 1)
								break;
							else
								continue;
						}

						// Determine if this is a new object definition
						if(strpos($file[$l], 'define') !== FALSE) {
							$sObjType = substr($file[$l], 7, (strpos($file[$l], '{', 8) - 8));
							if(!isset($sObjType) || !isset(self::$validConfig[$sObjType])) {
								new GlobalMessage('ERROR', $this->CORE->getLang()->getText('unknownObject',Array('TYPE' => $sObjType, 'MAPNAME' => $this->name)));
								return FALSE;
							} 

							// This is a new definition and it's a valid one
							
							// Get the type index
							$iObjTypeId = $types[$sObjType];
							
							$this->mapConfig[$sObjType][$iObjTypeId] = Array(
							  'type' => $sObjType,
							  'object_id' => $iObjId
							);
							
							// increase type index
							$types[$sObjType]++;
							
							// Increase the map object id to identify the object on the map
							$iObjId++;

							continue;
						}

						// This is another attribute. But it is only ok to proceed here when
						// there is an open object
						if($sObjType === '') {
							new GlobalMessage('ERROR',
																$this->CORE->getLang()->getText('Attribute definition out of object. In map [MAPNAME] at line #[LINE].',
																Array('MAPNAME' => $this->name, 'LINE' => $l+1)));
							return FALSE;
						}

						$iDelimPos = strpos($file[$l], '=');
						$sKey = trim(substr($file[$l],0,$iDelimPos));
						$sValue = trim(substr($file[$l],($iDelimPos+1)));
						
						if(isset($ignoreKeys[$sKey]))
							continue;

						if(isset($createArray[$sKey])) {
							$this->mapConfig[$sObjType][$iObjTypeId][$sKey] = explode(',', $sValue);
						} else {
							$this->mapConfig[$sObjType][$iObjTypeId][$sKey] = $sValue;
						}
					}
					
					// Gather the default values for the object types
					$this->gatherTypeDefaults($onlyGlobal);
						
					if($onlyGlobal == 0) {
						if(isset($this->mapConfig['template']) && $resolveTemplates == true) {
							// Merge the objects with the linked templates
							$this->mergeTemplates();
						}
					}

					// unknown object type found on map
					if($unknownObject)
						throw new MapCfgInvalid($unknownObject);
					
					try {
						$this->checkMapConfigIsValid();
						$this->BACKGROUND = $this->getBackground();
					} catch(MapCfgInvalid $e) {
						$this->BACKGROUND = $this->getBackground();
						throw $e;
					}
					
					if($onlyGlobal == 0) { 
						// Build cache
						if($useCache === true) {
							$this->CACHE->writeCache($this->mapConfig, 1);
							$this->DCACHE->writeCache($this->typeDefaults, 1);
							// Cache objects are not needed anymore
							$this->CACHE = null;
							$this->DCACHE = null;
						}
						
						// The automap also uses this method, so handle the different type
						if($this->type === 'automap') {
							$mod = 'AutoMap';
						} else {
							$mod = 'Map';
						}
						
						// Trigger the autorization backend to create new permissions when needed
						$AUTHORIZATION = $this->CORE->getAuthorization();
						if($AUTHORIZATION !== null) {
							$this->CORE->getAuthorization()->createPermission($mod, $this->getName());
						}
					}
					
					return TRUE;
				} else {
					return FALSE;
				}
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Gets the numeric index of a template by the name
	 *
	 * @param   String   Name of the template
	 * @return  Integer  ID of the template
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getTemplateIdByName($name) {
		foreach($this->mapConfig['template'] AS $id => $arr) {
			if(isset($arr['name']) && $arr['name'] === $name) {
				return $id;
			} 
		}
		
		return false;
	}
	
	/**
	 * Merges the object which "use" a template with the template values
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function mergeTemplates() {
		// Loop all objects
		foreach($this->mapConfig AS $type => &$elements) {
			// Except global and templates (makes no sense)
			if($type != 'global') {
				// Loop all objects of that type
				foreach($elements AS $id => &$element) {
					// Check for "use" value
					if(isset($element['use']) && is_array($element['use'])) {
						// loop all given templates
						foreach($element['use'] AS &$templateName) {
							$index = $this->getTemplateIdByName($templateName);
							
							if(isset($this->mapConfig['template'][$index]) && is_array($this->mapConfig['template'][$index])) {
								// merge object array with template object array (except type and name attribute)
								$tmpArray = $this->mapConfig['template'][$index];
								unset($tmpArray['type']);
								unset($tmpArray['name']);
								unset($tmpArray['object_id']);
								$this->mapConfig[$type][$id] = array_merge($tmpArray, $element);
							}
						}
					}
				}
			}
		}
		
		// Everything is merged: The templates are not relevant anymore
		unset($this->mapConfig['template']);
	}
	
	/**
	 * Checks for existing config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkMapConfigExists($printErr) {
		return GlobalCore::getInstance()->checkExisting($this->configFile, $printErr);
	}
	
	/**
	 * PROTECTED  checkMapConfigReadable()
	 *
	 * Checks for readable config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function checkMapConfigReadable($printErr) {
		return GlobalCore::getInstance()->checkReadable($this->configFile, $printErr);
	}
	
	/**
	 * Checks if the config file is valid
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkMapConfigIsValid() {
		// check given objects and attributes
		foreach($this->mapConfig AS $type => $elements) {
			if($type == 'global')
				$exception = 'MapCfgInvalid';
			else
				$exception = 'MapCfgInvalidObject';
			
			foreach($elements AS $id => $element) {
				// loop validConfig for checking: => missing "must" attributes
				foreach(self::$validConfig[$type] AS $key => $val) {
					if(isset($val['must']) && $val['must'] == '1') {
						// value is "must"
						if(!isset($element[$key]) || $element[$key] == '') {
							// a "must" value is missing or empty
							throw new $exception($this->CORE->getLang()->getText('mapCfgMustValueNotSet', Array('MAPNAME' => $this->name, 'ATTRIBUTE' => $key, 'TYPE' => $type, 'ID' => $id)));
						}
					}
				}
				
				// Don't check values in templates
				if($type !== 'template') {
					// loop given elements for checking: => all given attributes valid
					foreach($element AS $key => $val) {
						// check for valid attributes
						if(!isset(self::$validConfig[$type][$key])) {
							// unknown attribute
							throw new $exception($this->CORE->getLang()->getText('unknownAttribute', Array('MAPNAME' => $this->name, 'ATTRIBUTE' => $key, 'TYPE' => $type)));
						} elseif(isset(self::$validConfig[$type][$key]['deprecated']) && self::$validConfig[$type][$key]['deprecated'] == 1) {
							// deprecated option
							throw new $exception($this->CORE->getLang()->getText('mapDeprecatedOption', Array('MAP' => $this->getName(), 'ATTRIBUTE' => $key, 'TYPE' => $type)));
						} else {
							// The object has a match regex, it can be checked
							if(isset(self::$validConfig[$type][$key]['match'])) {
								if(is_array($val)) {
									// This is an array
									
									// Loop and check each element
									foreach($val AS $key2 => $val2) {
										if(!preg_match(self::$validConfig[$type][$key]['match'], $val2)) {
											// wrong format
											throw new $exception($this->CORE->getLang()->getText('wrongValueFormatMap', Array('MAP' => $this->getName(), 'TYPE' => $type, 'ATTRIBUTE' => $key)));
										}
									}
								} else {
									// This is a string value
									
									if(!preg_match(self::$validConfig[$type][$key]['match'],$val)) {
										// Wrong format
										throw new $exception($this->CORE->getLang()->getText('wrongValueFormatMap', Array('MAP' => $this->getName(), 'TYPE' => $type, 'ATTRIBUTE' => $key)));
									}
								}
							}
							
							// Check if the configured backend is defined in main configuration file
							if($key == 'backend_id' && !in_array($val, $this->CORE->getDefinedBackends())) {
								throw new $exception($this->CORE->getLang()->getText('backendNotDefined', Array('BACKENDID' => $val)));
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Gets valid keys for a specific object type
	 *
	 * @param   String  Specific object type
	 * @return  Array   Valid object keys
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getValidTypeKeys($sType) {
		$aRet = Array();
		foreach(self::$validConfig[$sType] AS $key => $arr) {
			if(!isset($arr['deprecated']) || $arr['deprecated'] != 1) {
				$aRet[] = $key;
			}
		}
		return $aRet;
	}
	
	/**
	 * Gets all valid object types
	 *
	 * @return  Array  Valid object types
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getValidObjectTypes() {
		$aRet = Array();
		foreach($this->typeDefaults AS $key => $arr) {
			$aRet[] = $key;
		}
		return $aRet;
	}

	/**
	 * Gets the default configuration on the map for the given type
	 *
	 * @return  Array  Array of default options
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getTypeDefaults($type) {
		return $this->typeDefaults[$type];
	}
	
	/**
	 * Gets all definitions of type $type
	 *
	 * @param	String	$type
	 * @return	Array	All elements of this type
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getDefinitions($type) {
		if(isset($this->mapConfig[$type]) && count($this->mapConfig[$type]) > 0) {
			return $this->mapConfig[$type];
		} else {
			return Array();
		}
	}
	
	/**
	 * Gets a list of available templates with optional regex filtering
	 * the templates are listed as keys
	 *
	 * @param   String  Filter regex
	 * @return  Array	  List of templates as keys
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getTemplateNames($strMatch = NULL) {
		$a = Array();
		foreach($this->getDefinitions('template') AS $id => $aOpts) {
			if($strMatch == NULL || ($strMatch != NULL && preg_match($strMatch, $aOpts['name']))) {
				$a[$aOpts['name']] = true;
			}
		}
		
		return $a;
	}
	
	/**
	 * Gets the last modification time of the configuration file
	 *
	 * @return	Integer Unix timestamp with last modification time
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getFileModificationTime() {
		if($this->checkMapConfigReadable(1)) {
			$time = filemtime($this->configFile);
			return $time;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable MapCfgFolder
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMapCfgFolderWriteable($printErr) {
		return GlobalCore::getInstance()->checkReadable(dirname($this->configFile), $printErr);
	}
	
	/**
	 * Deletes an element of the specified type from the config array
	 *
	 * @param	String	$type
	 * @param	Integer	$id
	 * @return	Boolean	TRUE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function deleteElement($type,$id) {
		unset($this->mapConfig[$type][$id]);
		return true;
	}
	
	/**
	 * Adds an element of the specified type to the config array
	 *
	 * @param	String	$type
	 * @param	Array	$properties
	 * @return	Integer	Id of the Element
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function addElement($type,$properties) {
		$this->mapConfig[$type][] = $properties;
		return count($this->mapConfig[$type])-1;
	}
	
	/**
	 * Sets a config value in the array
	 *
	 * @param	String	$type
	 * @param	Integer	$id
	 * @param	String	$key
	 * @param	String	$value
	 * @return	Boolean	TRUE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setValue($type, $id, $key, $value) {
		$this->mapConfig[$type][$id][$key] = $value;
		return TRUE;
	}
	
	/**
	 * Gets a config value from the array
	 *
	 * @param	String	$type
	 * @param	Integer	$id
	 * @param	String	$key
	 * @param	Boolean	$ignoreDefault
	 * @return	String	Value
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getValue($type, $id, $key, $ignoreDefault=FALSE) {
		if(isset($this->mapConfig[$type][$id]) && isset($this->mapConfig[$type][$id][$key])) {
			return $this->mapConfig[$type][$id][$key];
		} elseif(!$ignoreDefault) {
			if(isset($this->typeDefaults[$type][$key])) {
				return $this->typeDefaults[$type][$key];
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Gets the mapName
	 *
	 * @return	String	MapName
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getName() {
		return $this->name;	
	}
	
	/**
	 * Gets the map alias
	 *
	 * @return	String	Map alias
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAlias() {
		return $this->getValue('global', 0, 'alias');	
	}

	public function objIdToTypeAndNum($objId) {
		foreach($this->mapConfig AS $type => $objects)
			foreach($objects AS $typeId => $opts)
				if($opts['object_id'] == $objId)
					return Array($type, $typeId);
		return Array(null, null);
	}

	/**
	 * Only selects the wanted objects of the map and removes the others
	 *
	 * @param   Array of object types
	 * @param   Array of object ids
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function filterMapObjects($types, $objIds) {
		$newConfig =  Array();
		$numObjects = count($types);
		for($i = 0; $i < $numObjects; $i++) {
			$type = $types[$i];
			$id   = $objIds[$i];
			if(!isset($newConfig[$type]))
				$newConfig[$type] = Array();

			$matchedTypeId = null;
			foreach($this->mapConfig[$type] AS $typeId => $opts) {
				if($opts['object_id'] == $id) {
					$matchedTypeId = $typeId;
					break;
				}
			}
			if($matchedTypeId !== null)
				$newConfig[$type][$matchedTypeId] = $this->mapConfig[$type][$matchedTypeId];
		}
		$this->mapConfig = $newConfig;
	}

	/****************************************************************************
	 * EDIT STUFF BELOW
	 ***************************************************************************/

	/**
	 * Gets all information about an object type
	 *
	 * @param   String  Type to get the information for
	 * @return  Array   The validConfig array
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getValidObjectType($type) {
		return self::$validConfig[$type];
	}
	
	/**
	 * Gets the valid configuration array
	 *
	 * @return	Array The validConfig array
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getValidConfig() {
		return self::$validConfig;
	}
	
	/**
	 * Reads the configuration file of the map and 
	 * sends it as download to the client.
	 *
	 * @return	Boolean   Only returns FALSE if something went wrong
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function exportMap() {
		if($this->checkMapConfigReadable(1)) {
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.$this->getName().'.cfg');
			header('Content-Length: '.filesize($this->configFile));
			
			if(readfile($this->configFile)) {
				exit;
			} else {
				return FALSE;	
			}
		} else {
			return FALSE;	
		}
	}
	
	/**
	 * Deletes the map configfile
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function deleteMapConfig($printErr=1) {
		// is file writeable?
		if($this->checkMapConfigWriteable($printErr)) {
			if(unlink($this->configFile)) {
				// Also remove cache file
				if(file_exists($this->cacheFile))
					unlink($this->cacheFile);
				
				// And also remove the permission
				GlobalCore::getInstance()->getAuthorization()->deletePermission('Map', $this->name);
				
				return TRUE;
			} else {
				if($printErr)
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('couldNotDeleteMapCfg', Array('MAPPATH' => $this->configFile)));
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable map config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkMapConfigWriteable($printErr) {
		return GlobalCore::getInstance()->checkWriteable($this->configFile, $printErr);
	}
	
	/**
	 * Writes the element from array to the config file
	 *
	 * @param	String	$type	Type of the Element
	 * @param	Integer	$id		Id of the Element
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function writeElement($type,$id) {
		if($this->checkMapConfigExists(1) && $this->checkMapConfigReadable(1) && $this->checkMapConfigWriteable(1)) {
			// read file in array
			$file = file($this->configFile);
			
			// number of lines in the file
			$l = 0;
			// number of elements of the given type
			$a = 0;
			// done?!
			$done = FALSE;
			while(isset($file[$l]) && $file[$l] != '' && $done == FALSE) {
				// ignore comments
				if(!preg_match('/^#/',$file[$l]) && !preg_match('/^;/',$file[$l])) { 
					$defineCln = explode('{', $file[$l]);
					$define = explode(' ',$defineCln[0]);
					// select only elements of the given type
					if(isset($define[1]) && trim($define[1]) == $type) {
						// check if element exists
						if($a == $id) {
							// check if element is an array...
							if(isset($this->mapConfig[$type][$a]) && is_array($this->mapConfig[$type][$a])) {
								// ...array: update!
								
								// choose first parameter line
								$l++;
								
								// Loop all parameters from array
								foreach($this->mapConfig[$type][$id] AS $key => $val) {
									// if key is not type
									if($key != 'type' && $key != 'object_id') {
										$cfgLines = 0;
										$cfgLine = '';
										$cfgLineNr = 0;
										
										// Loop parameters from file (Find line for this option)
										while(isset($file[($l+$cfgLines)]) && trim($file[($l+$cfgLines)]) != '}') {
											$entry = explode('=',$file[$l+$cfgLines], 2);
											if($key == trim($entry[0])) {
												$cfgLineNr = $l+$cfgLines;
												if(is_array($val)) {
													$val = implode(',',$val);
												}
												$cfgLine = $key.'='.$val."\n";
											}
											$cfgLines++;	
										}
										
										if($cfgLineNr != 0 && $val != '') {
											// if a parameter was found in file and value is not empty, replace line
											$file[$cfgLineNr] = $cfgLine;
										} elseif($cfgLineNr != 0 && $val == '') {
											// if a paremter is not in array or a value is empty, delete the line in the file
											$file[$cfgLineNr] = '';
											$cfgLines--;
										} elseif($cfgLineNr == 0 && $val != '') {
											// if a parameter is was not found in array and a value is not empty, create line
											if(is_array($val)) {
												$val = implode(',',$val);
											}
											$neu = $key.'='.$val."\n";
											
											for($i = $l; $i < count($file);$i++) {
												$tmp = $file[$i];
												$file[$i] = $neu;
												$neu = $tmp;
											}
											$file[count($file)] = $neu;
										} elseif($cfgLineNr == 0 && $val == '') {
											// if a parameter is empty and a value is empty, do nothing
										}
									}
								}
								$l++;
							} else {
								// ...no array: delete!
								$cfgLines = 0;
								while(trim($file[($l+$cfgLines)]) != '}') {
									$cfgLines++;
								}
								$cfgLines++;
								
								for($i = $l; $i <= $l+$cfgLines;$i++) {
									unset($file[$i]);	
								}
							}
							
							$done = TRUE;
						}
						$a++;
					}
				}
				$l++;	
			}
			
			// reached end of file - couldn't find that element, create a new one...
			if($done == FALSE) {
				if(count($file) > 0 && $file[count($file)-1] != "\n") {
					$file[] = "\n";
				}
				$file[] = 'define '.$type." {\n";

				// Templates need a special handling here cause they can have all types
				// of options. So read all keys which are currently set
				if($type !== 'template') {
					$aKeys = $this->getValidTypeKeys($type);
				} else {
					$aKeys = array_keys($this->mapConfig[$type][$id]);
				}
				
				foreach($aKeys As $key) {
					$val = $this->getValue($type, $id, $key, TRUE);
					if(isset($val) && $val != '') {
						$file[] = $key.'='.$val."\n";
					}
				}
				$file[] = "}\n";
				$file[] = "\n";
			}
			
			// open file for writing and replace it
			$fp = fopen($this->configFile, 'w');
			fwrite($fp,implode('',$file));
			fclose($fp);
			
			// Also remove cache file
			if(file_exists($this->cacheFile))
				unlink($this->cacheFile);
			
			return TRUE;
		} else {
		 			return FALSE;
		} 
	}
	
	/**
	 * Gets lockfile information
	 *
	 * @param	Boolean $printErr
	 * @return	Array/Boolean   Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
   */
	public function checkMapLocked($printErr=1) {
		// read lockfile
		$lockdata = $this->readMapLock();
		if(is_array($lockdata)) {
			// Only check locks which are not too old
			if(time() - $lockdata['time'] < $this->CORE->getMainCfg()->getValue('wui','maplocktime') * 60) {
				// there is a lock and it should be recognized
				// check if this is the lock of the current user (Happens e.g. by pressing F5)
				if(GlobalCore::getInstance()->getAuthentication()->getUser() == $lockdata['user']
																						&& $_SERVER['REMOTE_ADDR'] == $lockdata['ip']) {
					// refresh the lock (write a new lock)
					$this->writeMapLock();
					// it's locked by the current user, so it's not locked for him
					return FALSE;
				}
				
				// message the user that there is a lock by another user,
				// the user can decide wether he want's to override it or not
				if($printErr == 1)
					print '<script>if(!confirm(\''.str_replace("\n", "\\n", $this->CORE->getLang()->getText('mapLocked',
									Array('MAP' =>  $this->name,       'TIME' => date('d.m.Y H:i', $lockdata['time']),
												'USER' => $lockdata['user'], 'IP' =>   $lockdata['ip']))).'\', \'\')) { history.back(); }</script>';
				return TRUE;
			} else {
				// delete lockfile & continue
				// try to delete map lock, if nothing to delete its OK
				$this->deleteMapLock();
				return FALSE;
			}
		} else {
			// no valid information in lock or no lock there
			// try to delete map lock, if nothing to delete its OK
			$this->deleteMapLock();
			return FALSE;
		}
	}
	
	/**
	 * Reads the contents of the lockfile
	 *
	 * @return	Array/Boolean   Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function readMapLock() {
		if($this->checkMapLockReadable(0)) {
			$fileContent = file($this->mapLockPath);
			// only recognize the first line, explode it by :
			$arrContent = explode(':',$fileContent[0]);
			// if there are more elements in the array it is OK
			if(count($arrContent) > 0) {
				return Array('time' => $arrContent[0], 'user' => $arrContent[1], 'ip' => trim($arrContent[2]));
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Writes the lockfile for a map
	 *
	 * @return	Boolean     Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function writeMapLock() {
		// Can an existing lock be updated?
		if($this->checkMapLockExists(0) && !$this->checkMapLockWriteable(0))
			return false;

		// If no map lock exists: Can a new one be created?
		if(!$this->checkMapLockExists(0) && !GlobalCore::getInstance()->checkWriteable(dirname($this->mapLockPath), 0))
			return false;

		// open file for writing and insert the needed information
		$fp = fopen($this->mapLockPath, 'w');
		fwrite($fp, time() . ':' . GlobalCore::getInstance()->getAuthentication()->getUser() . ':' . $_SERVER['REMOTE_ADDR']);
		fclose($fp);
		return true;
	}
	
	/**
	 * Deletes the lockfile for a map
	 *
	 * @return	Boolean     Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function deleteMapLock() {
		if($this->checkMapLockWriteable(0)) {
			return unlink($this->mapLockPath);
		} else {
			// no map lock to delete => OK
			return TRUE;   
		}
	}
	
	/**
	 * Checks for existing lockfile
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkMapLockExists($printErr) {
		return GlobalCore::getInstance()->checkExisting($this->mapLockPath, $printErr);
	}
	
	/**
	 * Checks for readable lockfile
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkMapLockReadable($printErr) {
		return GlobalCore::getInstance()->checkReadable($this->mapLockPath, $printErr);
	}
	
	/**
	 * Checks for writeable lockfile
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkMapLockWriteable($printErr) {
		return GlobalCore::getInstance()->checkWriteable($this->mapLockPath, $printErr);
	}
}
?>
