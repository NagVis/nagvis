<?php
/**
 * This Class handles the NagVis configuration file
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalMapCfg {
	var $MAINCFG;
	var $BACKGROUND;
	
	var $name;
	var $mapConfig;
	
	// Array for config validation
	var $validConfig;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG	
	 * @param	String			$name		Name of the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalMapCfg(&$MAINCFG,$name='') {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::GlobalMapCfg($MAINCFG,'.$name.')');
		$this->MAINCFG = &$MAINCFG;
		$this->name	= $name;
		
		$this->validConfig = Array(
			'global' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'allowed_for_config' => Array('must' => 1,
					'match' => MATCH_STRING_NO_SPACE),
				'allowed_user' => Array('must' => 1,
					'match' => MATCH_STRING_NO_SPACE),
				'map_image' => Array('must' => 1,
					'match' => MATCH_PNGFILE),
				'alias' => Array('must' => 0,
					'default' => $this->getName(),
					'match' => MATCH_STRING),
				'usegdlibs' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'usegdlibs'),
					'match' => MATCH_BOOLEAN),
				'show_in_lists' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'showinlists'),
					'match' => MATCH_BOOLEAN),
				'backend_id' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'backend'),
					'match' => MATCH_STRING_NO_SPACE),
				'recognize_services' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'recognizeservices'),
					'match' => MATCH_BOOLEAN),
				'only_hard_states' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'onlyhardstates'),
					'match' => MATCH_BOOLEAN),
				'iconset' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'icons'),
					'match' => MATCH_STRING_NO_SPACE),
				'background_color' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'backgroundcolor'),
					'match' => MATCH_COLOR),
				'hover_template' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'hovertemplate'),
					'match' => MATCH_STRING_NO_SPACE),
				'hover_delay' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'hoverdelay'),
					'match' => MATCH_INTEGER),
				'header_template' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'headertemplate'),
					'match' => MATCH_STRING_NO_SPACE),
				'url_target' => Array('must' => 0,
					'default' => $this->MAINCFG->getValue('defaults', 'urltarget'),
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
					'match' => MATCH_COLOR)),
			'host' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'backend_id' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'host_name' => Array('must' => 1,
					'match' => MATCH_STRING_NO_SPACE),
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
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
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
					'match' => MATCH_COLOR)),
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
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
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
					'match' => MATCH_COLOR)),
			'service' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'backend_id' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'host_name' => Array('must' => 1,
					'match' => MATCH_STRING_NO_SPACE),
				'service_description' => Array('must' => 1),
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
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
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
					'match' => MATCH_COLOR)),
			'servicegroup' => Array('type' => Array('must' => 0,
					'match' => MATCH_OBJECTTYPE),
				'use' => Array('must' => 0,
					'match' => MATCH_STRING_NO_SPACE),
				'backend_id' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
				'servicegroup_name' => Array('must' => 1,
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
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
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
					'match' => MATCH_COLOR)),
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
				'hover_template' => Array('must' => 0,
					'default' => '',
					'match' => MATCH_STRING_NO_SPACE),
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
					'match' => MATCH_COLOR)),
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
					'match' => MATCH_INTEGER),
				'background_color' => Array('must' => 0,
					'default' => '#C0C0C0',
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
		
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::GlobalMapCfg()');
	}
	
	/**
	 * Gets the default values for the objects 
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getObjectDefaults() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::getObjectDefaults()');
		$this->validConfig['host']['recognize_services']['default'] = $this->getValue('global', 0, 'recognize_services');
		$this->validConfig['host']['only_hard_states']['default'] = $this->getValue('global', 0, 'only_hard_states');
		$this->validConfig['host']['backend_id']['default'] = $this->getValue('global', 0, 'backend_id');
		$this->validConfig['host']['iconset']['default'] = $this->getValue('global', 0, 'iconset');
		$this->validConfig['host']['hover_template']['default'] = $this->getValue('global', 0, 'hover_template');
		$this->validConfig['host']['hover_delay']['default'] = $this->getValue('global', 0, 'hover_delay');
		$this->validConfig['host']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['host']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['host']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['host']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['host']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		$this->validConfig['host']['url_target']['default'] = $this->getValue('global', 0, 'url_target');
		$this->validConfig['hostgroup']['recognize_services']['default'] = $this->getValue('global', 0, 'recognize_services');
		$this->validConfig['hostgroup']['only_hard_states']['default'] = $this->getValue('global', 0, 'only_hard_states');
		$this->validConfig['hostgroup']['backend_id']['default'] = $this->getValue('global', 0, 'backend_id');
		$this->validConfig['hostgroup']['iconset']['default'] = $this->getValue('global', 0, 'iconset');
		$this->validConfig['hostgroup']['hover_template']['default'] = $this->getValue('global', 0, 'hover_template');
		$this->validConfig['hostgroup']['hover_delay']['default'] = $this->getValue('global', 0, 'hover_delay');
		$this->validConfig['hostgroup']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['hostgroup']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['hostgroup']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['hostgroup']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['hostgroup']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		$this->validConfig['hostgroup']['url_target']['default'] = $this->getValue('global', 0, 'url_target');
		$this->validConfig['service']['only_hard_states']['default'] = $this->getValue('global', 0, 'only_hard_states');
		$this->validConfig['service']['backend_id']['default'] = $this->getValue('global', 0, 'backend_id');
		$this->validConfig['service']['iconset']['default'] = $this->getValue('global', 0, 'iconset');
		$this->validConfig['service']['hover_template']['default'] = $this->getValue('global', 0, 'hover_template');
		$this->validConfig['service']['hover_delay']['default'] = $this->getValue('global', 0, 'hover_delay');
		$this->validConfig['service']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['service']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['service']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['service']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['service']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		$this->validConfig['service']['url_target']['default'] = $this->getValue('global', 0, 'url_target');
		$this->validConfig['servicegroup']['only_hard_states']['default'] = $this->getValue('global', 0, 'only_hard_states');
		$this->validConfig['servicegroup']['backend_id']['default'] = $this->getValue('global', 0, 'backend_id');
		$this->validConfig['servicegroup']['iconset']['default'] = $this->getValue('global', 0, 'iconset');
		$this->validConfig['servicegroup']['hover_template']['default'] = $this->getValue('global', 0, 'hover_template');
		$this->validConfig['servicegroup']['hover_delay']['default'] = $this->getValue('global', 0, 'hover_delay');
		$this->validConfig['servicegroup']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['servicegroup']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['servicegroup']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['servicegroup']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['servicegroup']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		$this->validConfig['servicegroup']['url_target']['default'] = $this->getValue('global', 0, 'url_target');
		$this->validConfig['map']['only_hard_states']['default'] = $this->getValue('global', 0, 'only_hard_states');
		$this->validConfig['map']['iconset']['default'] = $this->getValue('global', 0, 'iconset');
		$this->validConfig['map']['hover_template']['default'] = $this->getValue('global', 0, 'hover_template');
		$this->validConfig['map']['hover_delay']['default'] = $this->getValue('global', 0, 'hover_delay');
		$this->validConfig['map']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['map']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['map']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['map']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['map']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		$this->validConfig['map']['url_target']['default'] = $this->getValue('global', 0, 'url_target');
		$this->validConfig['shape']['url_target']['default'] = $this->getValue('global', 0, 'url_target');
		$this->validConfig['shape']['hover_delay']['default'] = $this->getValue('global', 0, 'hover_delay');
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::getObjectDefaults()');
	}
	
	/**
	 * Initializes the background image
	 *
	 * @return	GlobalBackground
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackground() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::getImage()');
		$RET = new GlobalBackground($this->MAINCFG, $this->getValue('global', 0, 'map_image'));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::getImage()');
		return $RET;
	}
	
	/**
	 * Creates a new Configfile
	 *
	 * @return	Boolean	Is Successful?
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function createMapConfig() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::createMapConfig()');
		// does file exists?
		if(!$this->checkMapConfigReadable(0)) {
			if($this->MAINCFG->checkMapCfgFolderWriteable(1)) {
				// create empty file
				$fp = fopen($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg', 'w');
				fclose($fp); 
				// set permissions
				chmod($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg',0666);
				
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::createMapConfig(): TRUE');
					return TRUE;
				} else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::createMapConfig(): FALSE');
					return FALSE;
				}
		} else {
			// file exists & is readable
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::createMapConfig(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Reads the map config file (copied from readFile->readNagVisCfg())
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readMapConfig($onlyGlobal=0) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::readMapConfig('.$onlyGlobal.')');
		if($this->name != '') {
			if($this->checkMapConfigReadable(1)) {
				$this->mapConfig = Array();
				$types = Array('global'=>0,'host'=>0,'service'=>0,'hostgroup'=>0,'servicegroup'=>0,'map'=>0,'textbox'=>0,'shape'=>0,'template'=>0);
				
				// read file in array
				if (DEBUG&&DEBUGLEVEL&2) debug('Start reading map configuration');
				$file = file($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
				if (DEBUG&&DEBUGLEVEL&2) debug('End reading map configuration');
				$createArray = Array('allowed_user','allowed_for_config','use');
				$l = 0;
				
				if (DEBUG&&DEBUGLEVEL&2) debug('Start parsing map configuration');
				while(isset($file[$l]) && $file[$l] != '') {
					// tested all of them, seems the runtime is nearly the same
					// preg_match('/^(#|;)/',$file[$l])
					// (strpos($file[$l],'#') !== 0) && (strpos($file[$l],'#') !== 0)
					// !ereg('^(#|;)',$file[$l]) && !ereg('^;',$file[$l])
					if(!ereg('^(#|;)',$file[$l])) {
						$defineCln = explode('{', $file[$l]);
						$define = explode(' ',$defineCln[0]);
						if(isset($define[1]) && array_key_exists(trim($define[1]),$this->validConfig)) {
							$type = $types[$define[1]];
							$l++;
							$this->mapConfig[$define[1]][$type] = Array('type'=>$define[1]);
							while (isset($file[$l]) && trim($file[$l]) != '}') {
								if(!ereg('^(#|;)',$file[$l])) {
									$entry = explode('=',$file[$l], 2);
									$entry[0] = trim($entry[0]);
									if(isset($entry[1])) {
										$entry[1] = trim($entry[1]);
										if(in_array($entry[0],$createArray)) {
											$this->mapConfig[$define[1]][$type][$entry[0]] = explode(',',str_replace(' ','',$entry[1]));
										} else {
											$this->mapConfig[$define[1]][$type][$entry[0]] = $entry[1];
										}
									}
								}
								$l++;
							}
							// increase type index
							$types[$define[1]]++;
						}
					}
					$l++;
				}
				if (DEBUG&&DEBUGLEVEL&2) debug('End parsing map configuration');
				
				/**
				 * The default values refer to global settings in the validConfig array - so they have to be 
				 * defined here and mustn't be defined in the array at creation.
				 * Cause of the default values should refer to the truely defined settings in global area they have to be read here.
				 */
				$this->getObjectDefaults();
				
				if($onlyGlobal == 1) {
					$this->filterGlobal();	
				}
				
				if(isset($this->mapConfig['template'])) {
					// Removes the numeric indexes and replaces them with the template name
					$this->fixTemplateIndexes();
					// Merge the objects with the linked templates
					$this->mergeTemplates();
				}
				
				if($this->checkMapConfigIsValid(1)) {
					$this->BACKGROUND = $this->getBackground();
					if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::readMapConfig(): TRUE');
					return TRUE;
				} else {
					if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::readMapConfig(): FALSE');
					return FALSE;
				}
			} else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::readMapConfig(): TRUE');
				return FALSE;	
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::readMapConfig(): TRUE');
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
		foreach($this->mapConfig AS $type => $elements) {
			// Except global and templates (makes no sense)
			if($type != 'global' && $type != 'template') {
				// Loop all objects of that type
				foreach($elements AS $id => $element) {
					// Check for "use" value
					if(isset($element['use']) && is_array($element['use'])) {
						// loop all given templates
						foreach($element['use'] AS $templateName) {
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
	}
	
	/**
	 * Deletes all elements from the array, only global will be left
	 * Is needed in WUI to prevent config error warnings while loading the map credentials from
	 * global section of the map
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function filterGlobal() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::filterGlobal()');
		foreach($this->mapConfig AS $key => $val) {
			if($key != 'global') {
				unset($this->mapConfig[$key]);
			}
		}
		
		if(count($this->mapConfig) == 1) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::filterGlobal(): TRUE');
			return TRUE;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::filterGlobal(): FALSE');
			return FALSE;
		}
	}
	/**
	 * Checks for existing config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMapConfigExists($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapConfigExists('.$printErr.')');
		if($this->name != '') {
			if(file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg')) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigExists(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
					$FRONTEND->messageToUser('ERROR','mapCfgNotExists','MAP~'.$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigExists(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigExists(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapConfigReadable('.$printErr.')');
		if($this->name != '') {
			if($this->checkMapConfigExists($printErr) && is_readable($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg')) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigReadable(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
					$FRONTEND->messageToUser('ERROR','mapCfgNotReadable','MAP='.$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigReadable(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigReadable(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapConfigIsValid('.$printErr.')');
		// check given objects and attributes
		foreach($this->mapConfig AS $type => $elements) {
			if($type != 'template') {
				if(array_key_exists($type,$this->validConfig)) {
					foreach($elements AS $id => $element) {
						// loop validConfig for checking: => missing "must" atributes
						foreach($this->validConfig[$type] AS $key => $val) {
							if((isset($val['must']) && $val['must'] == '1')) {
								// value is "must"
								if(!isset($element[$key]) || $element[$key] == '') {
									// a "must" value is missing or empty
									$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
									$FRONTEND->messageToUser('ERROR','mustValueNotSet','MAPNAME~'.$this->name.',ATTRIBUTE~'.$key.',TYPE~'.$type.',ID~'.$id);
								}
							}
						}
						
						// loop given elements for checking: => all given atributes valid
						foreach($element AS $key => $val) {
							// check for valid atributes
							if(!array_key_exists($key,$this->validConfig[$type])) {
								// unknown atribute
								if($printErr == 1) {
									$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
									$FRONTEND->messageToUser('ERROR','unknownAttribute','MAPNAME~'.$this->name.',ATTRIBUTE~'.$key.',TYPE~'.$type);
								}
								if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigIsValid(): FALSE');
								return FALSE;
							} else {
								// FIXME: Only match non array values at the moment
								if(!is_array($val) && isset($this->validConfig[$type][$key]['match'])) {
									// valid attribute, now check for value format
									if(!preg_match($this->validConfig[$type][$key]['match'],$val)) {
										
										// wrong format
										if($printErr) {
											$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
											$FRONTEND->messageToUser('ERROR','wrongValueFormatMap','MAP~'.$this->getName().',TYPE~'.$type.',ATTRIBUTE~'.$key);
										}
										if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigIsValid(): FALSE');
										return FALSE;
									}
								}
							}
						}
					}	
				} else {
					// unknown type
					if($printErr == 1) {
						$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
						$FRONTEND->messageToUser('ERROR','unknownObject','TYPE~'.$type);
					}
					if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigIsValid(): FALSE');
					return FALSE;
				}
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigIsValid(): TRUE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::getDefinitions('.$type.')');
		if(isset($this->mapConfig[$type]) && count($this->mapConfig[$type]) > 0) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::getDefinitions(): Array(...)');
			return $this->mapConfig[$type];
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::getDefinitions(): Array()');
			return Array();
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::deleteElement('.$type.','.$id.')');
		$this->mapConfig[$type][$id] = '';
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::deleteElement()');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::addElement('.$type.',Array(...))');
		//$elementId = (count($this->getDefinitions($type))+1);
		$this->mapConfig[$type][] = $properties;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::addElement(): '.(count($this->mapConfig[$type])-1));
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::setValue('.$type.','.$id.','.$key.','.$value.')');
		$this->mapConfig[$type][$id][$key] = $value;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::setValue(): TRUE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::getValue('.$type.','.$id.','.$key.','.$ignoreDefault.')');
		if(isset($this->mapConfig[$type][$id]) && is_array($this->mapConfig[$type][$id]) && array_key_exists($key,$this->mapConfig[$type][$id]) && $this->mapConfig[$type][$id][$key] != '') {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::getValue(): '.$this->mapConfig[$type][$id][$key]);
			return $this->mapConfig[$type][$id][$key];
		} elseif(!$ignoreDefault) {
			if(isset($this->validConfig[$type][$key]['default'])) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::getValue(): '.$this->validConfig[$type][$key]['default']);
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::getName()');
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::getName(): '.$this->name);
		return $this->name;	
	}
}
?>
