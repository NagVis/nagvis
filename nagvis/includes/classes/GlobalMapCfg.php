<?php
/*****************************************************************************
 *
 * GlobalMapCfg.php - Class for handling the map configuration files of NagVis
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
	var $CORE;
	var $BACKGROUND;
	
	var $name;
	var $mapConfig;
	
	// Array for config validation
	var $validConfig;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalCore	$CORE
	 * @param	String			$name		Name of the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalMapCfg(&$CORE, $name='') {
		$this->CORE = &$CORE;
		$this->name	= $name;
		
		$this->validConfig = Array(
			'global' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'allowed_for_config' => Array('must' => 1,
					'match' => MATCH_STRING),
				'allowed_user' => Array('must' => 1,
					'match' => MATCH_STRING),
				'map_image' => Array('must' => 1,
					'match' => MATCH_PNG_GIF_JPG_FILE),
				'alias' => Array('must' => 0,
					'default' => $this->getName(),
					'match' => MATCH_STRING),
				'usegdlibs' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'usegdlibs'),
					'match' => MATCH_BOOLEAN),
				'show_in_lists' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'showinlists'),
					'match' => MATCH_BOOLEAN),
				'backend_id' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'backend'),
					'match' => MATCH_STRING_NO_SPACE),
				'recognize_services' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'recognizeservices'),
					'match' => MATCH_BOOLEAN),
				'only_hard_states' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'onlyhardstates'),
					'match' => MATCH_BOOLEAN),
				'iconset' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'icons'),
					'match' => MATCH_STRING_NO_SPACE),
				'background_color' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'backgroundcolor'),
					'match' => MATCH_COLOR),
				'hover_template' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'hovertemplate'),
					'match' => MATCH_STRING_NO_SPACE),
				'hover_timeout' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'hovertimeout'),
					'match' => MATCH_INTEGER),
				'hover_menu' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'hovermenu'),
					'match' => MATCH_BOOLEAN),
				'hover_delay' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'hoverdelay'),
					'match' => MATCH_INTEGER),
				'header_menu' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'headermenu'),
					'match' => MATCH_BOOLEAN),
				'header_template' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'headertemplate'),
					'match' => MATCH_STRING_NO_SPACE),
				'url_target' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'urltarget'),
					'match' => MATCH_STRING_NO_SPACE),
				'label_show' => Array('must' => 0,
					'default' => '0',
					'match' => MATCH_BOOLEAN),
				'label_x' => Array('must' => 0,
					'default' => '-20',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_y' => Array('must' => 0,
					'default' => '+20',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_width' => Array('must' => 0,
					'default' => 'auto',
					'match' => MATCH_INTEGER),
				'label_background' => Array('must' => 0,
					'default' => 'transparent',
					'match' => MATCH_COLOR),
				'label_border' => Array('must' => 0,
					'default' => '#000000',
					'match' => MATCH_COLOR),
				'in_maintenance' => Array('must' => 0,
					'default' => '0',
					'match' => MATCH_BOOLEAN),
				'hover_childs_show' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'hoverchildsshow'),
					'match' => MATCH_BOOLEAN),
				'hover_childs_sort' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'hoverchildssort'),
					'match' => MATCH_STRING_NO_SPACE),
				'hover_childs_order' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'hoverchildsorder'),
					'match' => MATCH_ORDER),
				'hover_childs_limit' => Array('must' => 0,
					'default' => $this->CORE->MAINCFG->getValue('defaults', 'hoverchildslimit'),
					'match' => MATCH_INTEGER)),
			'host' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'backend_id' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'host_name' => Array('must' => 1,
					'match' => MATCH_STRING),
				'x' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'y' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'z' => Array('must' => 0,
					'default' => 1,
					'match' => MATCH_INTEGER),
				'recognize_services' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'only_hard_states' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'iconset' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_menu' => Array('must' => 0,
					'match' => MATCH_BOOLEAN),
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_timeout' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'hover_delay' => Array('must' => 0,
					'match' => MATCH_INTEGER),
				'hover_url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'line_type' => Array('must' => 0,
					'match' => MATCH_INTEGER),
				'url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'url_target' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'label_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'label_text' => Array('must' => 0,
					'default' => '[name]',
					'match' => MATCH_ALL),
				'label_x' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_y' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_width' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'label_background' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'label_border' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'hover_childs_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'hover_childs_sort' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_childs_order' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_ORDER),
				'hover_childs_limit' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER)),
			'hostgroup' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'backend_id' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hostgroup_name' => Array('must' => 1,
					'match' => MATCH_STRING),
				'x' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'y' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'z' => Array('must' => 0,
					'default' => 1,
					'match' => MATCH_INTEGER),
				'recognize_services' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'only_hard_states' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'iconset' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_menu' => Array('must' => 0,
					'match' => MATCH_BOOLEAN),
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_timeout' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'hover_delay' => Array('must' => 0,
					'match' => MATCH_INTEGER),
				'hover_url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'line_type' => Array('must' => 0,
					'match' => MATCH_INTEGER),
				'url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'url_target' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'label_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'label_text' => Array('must' => 0,
					'default' => '[name]',
					'match' => MATCH_ALL),
				'label_x' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_y' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_width' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'label_background' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'label_border' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'hover_childs_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'hover_childs_sort' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_childs_order' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_ORDER),
				'hover_childs_limit' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER)),
			'service' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'backend_id' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'host_name' => Array('must' => 1,
					'match' => MATCH_STRING),
				'service_description' => Array('must' => 1,
					'match' => MATCH_STRING),
				'x' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'y' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'z' => Array('must' => 0,
					'default' => 1,
					'match' => MATCH_INTEGER),
				'only_hard_states' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'iconset' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_menu' => Array('must' => 0,
					'match' => MATCH_BOOLEAN),
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_timeout' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'hover_delay' => Array('must' => 0,
					'match' => MATCH_INTEGER),
				'hover_url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'line_type' => Array('must' => 0,
					'match' => MATCH_INTEGER),
				'url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'url_target' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'label_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'label_text' => Array('must' => 0,
					'default' => '[name] [service_description]',
					'match' => MATCH_ALL),
				'label_x' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_y' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_width' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'label_background' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'label_border' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'hover_childs_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'hover_childs_sort' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_childs_order' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_ORDER),
				'hover_childs_limit' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER)),
			'servicegroup' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'backend_id' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'servicegroup_name' => Array('must' => 1,
					'match' => MATCH_STRING),
				'x' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'y' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'z' => Array('must' => 0,
					'default' => 1,
					'match' => MATCH_INTEGER),
				'only_hard_states' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'iconset' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_menu' => Array('must' => 0,
					'match' => MATCH_BOOLEAN),
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_timeout' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'hover_delay' => Array('must' => 0,
					'match' => MATCH_INTEGER),
				'hover_url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'line_type' => Array('must' => 0,
					'match' => MATCH_INTEGER),
				'url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'url_target' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'label_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'label_text' => Array('must' => 0,
					'default' => '[name]',
					'match' => MATCH_ALL),
				'label_x' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_y' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_width' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'label_background' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'label_border' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'hover_childs_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'hover_childs_sort' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_childs_order' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_ORDER),
				'hover_childs_limit' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER)),
			'map' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'map_name' => Array('must' => 1,
					'match' => MATCH_STRING_NO_SPACE),
				'x' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'y' => Array('must' => 1,
					'match' => MATCH_FLOAT),
				'z' => Array('must' => 0,
					'default' => 1,
					'match' => MATCH_INTEGER),
				'only_hard_states' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'iconset' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'url_target' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'hover_menu' => Array('must' => 0,
					'match' => MATCH_BOOLEAN),
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_timeout' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'hover_delay' => Array('must' => 0,
					'match' => MATCH_INTEGER),
				'hover_url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'label_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'label_text' => Array('must' => 0,
					'default' => '[name]',
					'match' => MATCH_ALL),
				'label_x' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_y' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER_PRESIGN),
				'label_width' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER),
				'label_background' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'label_border' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_COLOR),
				'hover_childs_show' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_BOOLEAN),
				'hover_childs_sort' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'hover_childs_order' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_ORDER),
				'hover_childs_limit' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_INTEGER)),
			'textbox' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'text' => Array('must' => 1,
					'match' => MATCH_ALL),
				'x' => Array('must' => 1,
					'match' => MATCH_INTEGER),
				'y' => Array('must' => 1,
					'match' => MATCH_INTEGER),
				'z' => Array('must' => 0,
					'default' => 1,
					'match' => MATCH_INTEGER),
				'w' => Array('must' => 1,
					'match' => MATCH_TEXTBOX_WIDTH),
				'background_color' => Array('must' => 0,
					'default' => '#C0C0C0',
					'match' => MATCH_COLOR),
				'border_color' => Array('must' => 0,
					'default' => '#000000',
					'match' => MATCH_COLOR)),
			'shape' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'icon' => Array('must' => 1),
				'x' => Array('must' => 1,
					'match' => MATCH_INTEGER),
				'y' => Array('must' => 1,
					'match' => MATCH_INTEGER),
				'z' => Array('must' => 0,
					'default' => 1,
					'match' => MATCH_INTEGER),
				'url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'url_target' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'hover_url' => Array('must' => 0,
					'match' => MATCH_STRING_URL),
				'hover_delay' => Array('must' => 0,
					'match' => MATCH_INTEGER)),
			'template' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'name' => Array('must' => 1,
					'match' => MATCH_STRING_NO_SPACE)));
		
	}
	
	/**
	 * Gets the default values for the objects 
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getObjectDefaults() {
		$aVars = Array('recognize_services',
			'only_hard_states',
			'backend_id',
			'iconset',
			'hover_menu',
			'hover_template',
			'hover_timeout',
			'hover_delay',
			'hover_url',
			'label_x',
			'label_y',
			'label_width',
			'label_background',
			'label_border',
			'url_target',
			'hover_childs_show',
			'hover_childs_sort',
			'hover_childs_order',
			'hover_childs_limit');
		foreach($aVars As $sVar) {
			$sTmp = $this->getValue('global', 0, $sVar);
			$this->validConfig['host'][$sVar]['default'] = $sTmp;
			$this->validConfig['hostgroup'][$sVar]['default'] = $sTmp;
			$this->validConfig['servicegroup'][$sVar]['default'] = $sTmp;

			if($sVar != 'recognize_services') {
				$this->validConfig['service'][$sVar]['default'] = $sTmp;
			}
			
			if($sVar != 'recognize_services' && $sVar != 'backend_id') {
				$this->validConfig['map'][$sVar]['default'] = $sTmp;
			}
			
			if($sVar == 'url_target' || $sVar == 'hover_delay') {
				$this->validConfig['shape'][$sVar]['default'] = $sTmp;
			}
		}
	}
	
	/**
	 * Initializes the background image
	 *
	 * @return	GlobalBackground
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackground() {
		$RET = new GlobalBackground($this->CORE, $this->getValue('global', 0, 'map_image'));
		return $RET;
	}
	
	/**
	 * Creates a new Configfile
	 *
	 * @return	Boolean	Is Successful?
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function createMapConfig() {
		// does file exists?
		if(!$this->checkMapConfigReadable(FALSE)) {
			if($this->CORE->MAINCFG->checkMapCfgFolderWriteable(TRUE)) {
				// create empty file
				$fp = fopen($this->CORE->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg', 'w');
				fclose($fp); 
				// set permissions
				chmod($this->CORE->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg',0666);
				
					return TRUE;
				} else {
					return FALSE;
				}
		} else {
			// file exists & is readable
			return FALSE;
		}
	}
	
	/**
	 * Reads the map config file (copied from readFile->readNagVisCfg())
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readMapConfig($onlyGlobal = 0) {
		if($this->name != '') {
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
								'template' => 0);
				
				// Read file in array (Don't read empty lines and ignore new line chars)
				$file = file($this->CORE->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
				
				// Create an array for these options
				$createArray = Array('allowed_user' => 1,
									'allowed_for_config' => 1,
									'use' => 1);
				
				$l = 0;
				
				// This variables do set which object is currently being filled
				$sObjType = '';
				$iObjTypeId = '';
				
				// Loop each line
				$iNumLines = count($file);
				for($l = 0; $l < $iNumLines; $l++) {
					// Remove spaces, newlines, tabs, etc. (http://de.php.net/rtrim)
					$file[$l] = rtrim($file[$l]);
					// Don't recognize empty lines
					if($file[$l] != '') {
						// Don't recognize comments and empty lines, do nothing with ending delimiters
						$sFirstChar = substr($file[$l], 0, 1);
						if($sFirstChar != ';' && $sFirstChar != '#' && $sFirstChar != '}') {
							// Determine if this is a new object definition
							if(strpos($file[$l], 'define') !== FALSE) {
								// If only the global section should be read break the loop after the global section
								if($onlyGlobal == 1 && $types['global'] == 1) {
									break;
								}
								
								$iDelimPos = strpos($file[$l], '{', 8);
								$sObjType = substr($file[$l], 7, ($iDelimPos - 8));
								
								if(isset($sObjType) && isset($this->validConfig[$sObjType])) {
									// This is a new definition and it's a valid one
									$iObjTypeId = $types[$sObjType];
									$this->mapConfig[$sObjType][$iObjTypeId] = Array('type' => $sObjType);
									// increase type index
									$types[$sObjType]++;
								} else {
									// unknown object type
									$FRONTEND = new GlobalPage($this->CORE);
									$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('unknownObject','TYPE~'.$type));
									return FALSE;
								}
							} else {
								// This is another attribute
								$iDelimPos = strpos($file[$l], '=');
								$sKey = trim(substr($file[$l],0,$iDelimPos));
								$sValue = trim(substr($file[$l],($iDelimPos+1)));
								
								if(isset($createArray[$sKey])) {
									$this->mapConfig[$sObjType][$iObjTypeId][$sKey] = explode(',', $sValue);
								} else {
									$this->mapConfig[$sObjType][$iObjTypeId][$sKey] = $sValue;
								}
							}
						}
					}
				}
				
				/**
				 * The default values refer to global settings in the validConfig array - so they have to be 
				 * defined here and mustn't be defined in the array at creation.
				 * Cause of the default values should refer to the truely defined settings in global area they have to be read here.
				 */
				if($onlyGlobal != 1) {
					$this->getObjectDefaults();
					
					if(isset($this->mapConfig['template'])) {
						// Removes the numeric indexes and replaces them with the template name
						$this->fixTemplateIndexes();
						// Merge the objects with the linked templates
						$this->mergeTemplates();
					}
				}                               
				
				if($this->checkMapConfigIsValid(1)) {
					$this->BACKGROUND = $this->getBackground();
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Removes the numeric indexes and replaces them with the template name
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fixTemplateIndexes() {
		foreach($this->mapConfig['template'] AS $id => $element) {
			if(isset($element['name']) && $element['name'] != '') {
				$this->mapConfig['template'][$element['name']] = $element;
				unset($this->mapConfig['template'][$id]);
			}
		}
	}
	
	/**
	 * Merges the object which "use" a template with the template values
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function mergeTemplates() {
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
							if(isset($this->mapConfig['template'][$templateName]) && is_array($this->mapConfig['template'][$templateName])) {
								// merge object array with template object array (except type and name atribute)
								$tmpArray = $this->mapConfig['template'][$templateName];
								unset($tmpArray['type']);
								unset($tmpArray['name']);
								$this->mapConfig[$type][$id] = array_merge($element,$tmpArray);
							}
						}
					}
				}
			}
		}
		
		// Everything is merged: The templates are not interesting anymore
		unset($this->mapConfig['template']);
	}
	
	/**
	 * Checks for existing config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMapConfigExists($printErr) {
		if($this->name != '') {
			if(file_exists($this->CORE->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg')) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->CORE);
					$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('mapCfgNotExists','MAP~'.$this->CORE->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg'));
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMapConfigReadable($printErr) {
		if($this->name != '') {
			if(is_readable($this->CORE->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg')) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->CORE);
					$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('mapCfgNotReadable','MAP='.$this->CORE->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg'));
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks if the config file is valid
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMapConfigIsValid($printErr) {
		// check given objects and attributes
		foreach($this->mapConfig AS $type => $elements) {
			foreach($elements AS $id => $element) {
				// loop validConfig for checking: => missing "must" atributes
				foreach($this->validConfig[$type] AS $key => $val) {
					if(isset($val['must']) && $val['must'] == '1') {
						// value is "must"
						if(!isset($element[$key]) || $element[$key] == '') {
							// a "must" value is missing or empty
							$FRONTEND = new GlobalPage($this->CORE);
							$FRONTEND->messageToUser('ERROR',$this->CORE->LANG->getText('mustValueNotSet','MAPNAME~'.$this->name.',ATTRIBUTE~'.$key.',TYPE~'.$type.',ID~'.$id));
						}
					}
				}
				
				// loop given elements for checking: => all given atributes valid
				foreach($element AS $key => $val) {
					// check for valid atributes
					if(!isset($this->validConfig[$type][$key])) {
						// unknown atribute
						if($printErr == 1) {
							$FRONTEND = new GlobalPage($this->CORE);
							$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('unknownAttribute','MAPNAME~'.$this->name.',ATTRIBUTE~'.$key.',TYPE~'.$type));
						}
						return FALSE;
					} elseif(isset($this->validConfig[$type][$key]['deprecated']) && $this->validConfig[$type][$key]['deprecated'] == 1) {
						// deprecated option
						if($printErr) {
							new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('mapDeprecatedOption', 'MAP~'.$this->getName().',ATTRIBUTE~'.$key.',TYPE~'.$type), $this->CORE->MAINCFG->getValue('paths','htmlbase'));
						}
						return FALSE;
					} else {
						// The object has a match regex, it can be checked
						if(isset($this->validConfig[$type][$key]['match'])) {
							
							if(is_array($val)) {
								// This is an array
								
								// Loop and check each element
								foreach($val AS $key2 => $val2) {
									if(!preg_match($this->validConfig[$type][$key]['match'], $val2)) {
										// wrong format
										if($printErr) {
											$FRONTEND = new GlobalPage($this->CORE);
											$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('wrongValueFormatMap','MAP~'.$this->getName().',TYPE~'.$type.',ATTRIBUTE~'.$key));
										}
										return FALSE;
									}
								}
							} else {
								// This is a string value
								
								if(!preg_match($this->validConfig[$type][$key]['match'],$val)) {
									// Wrong format
									if($printErr) {
										$FRONTEND = new GlobalPage($this->CORE);
										$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('wrongValueFormatMap','MAP~'.$this->getName().',TYPE~'.$type.',ATTRIBUTE~'.$key));
									}
									return FALSE;
								}
							}
						}
					}
				}
			}
		}
		return TRUE;
	}
	
	/**
	 * Gets all definitions of type $type
	 *
	 * @param	String	$type
	 * @return	Array	All elements of this type
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getDefinitions($type) {
		if(isset($this->mapConfig[$type]) && count($this->mapConfig[$type]) > 0) {
			return $this->mapConfig[$type];
		} else {
			return Array();
		}
	}
	
	/**
	 * Gets the last modification time of the configuration file
	 *
	 * @return	Integer Unix timestamp with last modification time
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getFileModificationTime() {
		if($this->checkMapConfigReadable(1)) {
			$time = filemtime($this->CORE->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
			return $time;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Deletes an element of the specified type to the config array
	 *
	 * @param	String	$type
	 * @param	Integer	$id
	 * @return	Boolean	TRUE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function deleteElement($type,$id) {
		$this->mapConfig[$type][$id] = '';
		return TRUE;
	}
	
	/**
	 * Adds an element of the specified type to the config array
	 *
	 * @param	String	$type
	 * @param	Array	$properties
	 * @return	Integer	Id of the Element
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function addElement($type,$properties) {
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
	function setValue($type, $id, $key, $value) {
		$this->mapConfig[$type][$id][$key] = $value;
		return TRUE;
	}
	
	/**
	 * Gets a config value in the array
	 *
	 * @param	String	$type
	 * @param	Integer	$id
	 * @param	String	$key
	 * @param	Boolean	$ignoreDefault
	 * @return	String	Value
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getValue($type, $id, $key, $ignoreDefault=FALSE) {
		if(isset($this->mapConfig[$type][$id]) && isset($this->mapConfig[$type][$id][$key]) && $this->mapConfig[$type][$id][$key] != '') {
			return $this->mapConfig[$type][$id][$key];
		} elseif(!$ignoreDefault) {
			if(isset($this->validConfig[$type][$key]['default'])) {
				return $this->validConfig[$type][$key]['default'];
			}
		}
	}
	
	/**
	 * Gets the mapName
	 *
	 * @return	String	MapName
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getName() {
		return $this->name;	
	}
	
	/**
	 * Gets the map alias
	 *
	 * @return	String	Map alias
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getAlias() {
		return $this->getValue('global', 0, 'alias');	
	}
}
?>
