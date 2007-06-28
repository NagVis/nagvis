<?php
/**
 * This Class handles the NagVis configuration file
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalMapCfg {
	var $MAINCFG;
	
	var $name;
	var $image;
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
			'global' => Array('type' => Array(	'must' => 0,
												'match' => MATCH_OBJECTTYPE),
							'allowed_for_config' => Array('must' => 1,
												'match' => MATCH_STRING_NO_SPACE),
							'allowed_user' => Array('must' => 1,
												'match' => MATCH_STRING_NO_SPACE),
							'map_image' => Array('must' => 1,
												'match' => MATCH_PNGFILE),
							'alias' => Array(	'must' => 0,
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
							'iconset' => Array(	'must' => 0,
												'default' => $this->MAINCFG->getValue('defaults', 'icons'),
												'match' => MATCH_STRING_NO_SPACE),
							'background_color' => Array('must' => 0,
												'default' => $this->MAINCFG->getValue('defaults', 'backgroundcolor'),
												'match' => MATCH_COLOR),
							'hover_template' => Array('must' => 0,
												'default' => $this->MAINCFG->getValue('defaults', 'hovertemplate'),
												'match' => MATCH_STRING_NO_SPACE),
							'header_template' => Array('must' => 0,
												'default' => $this->MAINCFG->getValue('defaults', 'headertemplate'),
												'match' => MATCH_STRING_NO_SPACE),
							'label_show' => Array('must' => 0,
												'default' => '0',
												'match' => MATCH_BOOLEAN),
							'label_x' => Array(	'must' => 0,
												'default' => '-20',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_y' => Array(	'must' => 0,
												'default' => '+20',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_width' => Array('must' => 0,
												'default' => 'auto',
												'match' => MATCH_INTEGER),
							'label_background' => Array('must' => 0,
												'default' => 'transparent',
												'match' => MATCH_COLOR)),
			'host' => Array('type' => Array(	'must' => 0,
												'match' => MATCH_OBJECTTYPE),
							'backend_id' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'name' => Array(	'must' => 1,
												'match' => '/$.+^/i'),
							'x' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'y' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'z' => Array(		'must' => 0,
												'default' => 1,
												'match' => MATCH_INTEGER),
							'recognize_services' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'only_hard_states' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'iconset' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'hover_template' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'hover_url' => Array('must' => 0,
												'match' => '/$.+^/i'),
							'line_type' => Array('must' => 0,
												'match' => MATCH_INTEGER),
							'url' => Array(		'must' => 0,
												'match' => '/$.+^/i'),
							'label_show' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'label_text' => Array('must' => 0,
												'default' => '[name]',
												'match' => '/$.+^/i'),
							'label_x' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_y' => Array(	'must' => 0,
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
							'backend_id' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'name' => Array(	'must' => 1,
												'match' => '/$.+^/i'),
							'x' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'y' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'z' => Array(		'must' => 0,
												'default' => 1,
												'match' => MATCH_INTEGER),
							'recognize_services' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'only_hard_states' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'iconset' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'hover_url' => Array('must' => 0,
												'match' => '/$.+^/i'),
							'hover_template' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'line_type' => Array('must' => 0,
												'match' => MATCH_INTEGER),
							'url' => Array(		'must' => 0,
												'match' => '/$.+^/i'),
							'label_show' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'label_text' => Array('must' => 0,
												'default' => '[name]',
												'match' => '/$.+^/i'),
							'label_x' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_y' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_width' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER),
							'label_background' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_COLOR)),
			'service' => Array('type' => Array(	'must' => 0,
												'match' => MATCH_OBJECTTYPE),
							'backend_id' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'name' => Array(	'must' => 1,
												'match' => '/$.+^/i'),
							'service_description' => Array('must' => 1,
												'match' => '/$.+^/i'),
							'x' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'y' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'z' => Array(		'must' => 0,
												'default' => 1,
												'match' => MATCH_INTEGER),
							'only_hard_states' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'iconset' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'hover_url' => Array('must' => 0,
												'match' => '/$.+^/i'),
							'hover_template' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'line_type' => Array('must' => 0,
												'match' => MATCH_INTEGER),
							'url' => Array(		'must' => 0,
												'match' => '/$.+^/i'),
							'label_show' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'label_text' => Array('must' => 0,
												'default' => '[name] [service_description]',
												'match' => '/$.+^/i'),
							'label_x' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_y' => Array(	'must' => 0,
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
							'backend_id' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'name' => Array(	'must' => 1,
												'match' => '/$.+^/i'),
							'x' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'y' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'z' => Array(		'must' => 0,
												'default' => 1,
												'match' => MATCH_INTEGER),
							'only_hard_states' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'iconset' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'hover_url' => Array('must' => 0,
												'match' => ''),
							'hover_template' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'line_type' => Array('must' => 0,
												'match' => MATCH_INTEGER),
							'url' => Array(		'must' => 0,
												'match' => '/$.+^/i'),
							'label_show' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'label_text' => Array('must' => 0,
												'default' => '[name]',
												'match' => '/$.+^/i'),
							'label_x' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_y' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_width' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER),
							'label_background' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_COLOR)),
			'map' => Array('type' => Array(		'must' => 0,
												'match' => MATCH_OBJECTTYPE),
							'name' => Array(	'must' => 1,
												'match' => MATCH_STRING_NO_SPACE),
							'x' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'y' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'z' => Array(		'must' => 0,
												'default' => 1,
												'match' => MATCH_INTEGER),
							'only_hard_states' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'iconset' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'url' => Array(		'must' => 0,
												'match' => '/$.+^/i'),
							'hover_url' => Array('must' => 0,
												'match' => '/$.+^/i'),
							'hover_template' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_STRING_NO_SPACE),
							'label_show' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_BOOLEAN),
							'label_text' => Array('must' => 0,
												'default' => '[name]',
												'match' => '/$.+^/i'),
							'label_x' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_y' => Array(	'must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER_PRESIGN),
							'label_width' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_INTEGER),
							'label_background' => Array('must' => 0,
												'default' => '',
												'match' => MATCH_COLOR)),
			'textbox' => Array('type' => Array(	'must' => 0,
												'match' => MATCH_OBJECTTYPE),
							'text' => Array(	'must' => 1,
												'match' => '/$.+^/i'),
							'x' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'y' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'z' => Array(		'must' => 0,
												'default' => 1,
												'match' => MATCH_INTEGER),
							'w' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'background_color' => Array('must' => 0,
												'default' => '#C0C0C0',
												'match' => MATCH_COLOR)),
			'shape' => Array('type' => Array(	'must' => 0,
												'match' => MATCH_OBJECTTYPE),
							'icon' => Array(	'must' => 1,
												'match' => MATCH_PNGFILE),
							'x' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'y' => Array(		'must' => 1,
												'match' => MATCH_INTEGER),
							'z' => Array(		'must' => 0,
												'default' => 1,
												'match' => MATCH_INTEGER),
							'url' => Array(		'must' => 0,
												'match' => '/$.+^/i'),
							'hover_url' => Array('must' => 0,
												'match' => '/$.+^/i')));
		
		
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
		$this->validConfig['host']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['host']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['host']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['host']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['host']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		$this->validConfig['hostgroup']['recognize_services']['default'] = $this->getValue('global', 0, 'recognize_services');
		$this->validConfig['hostgroup']['only_hard_states']['default'] = $this->getValue('global', 0, 'only_hard_states');
		$this->validConfig['hostgroup']['backend_id']['default'] = $this->getValue('global', 0, 'backend_id');
		$this->validConfig['hostgroup']['iconset']['default'] = $this->getValue('global', 0, 'iconset');
		$this->validConfig['hostgroup']['hover_template']['default'] = $this->getValue('global', 0, 'hover_template');
		$this->validConfig['hostgroup']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['hostgroup']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['hostgroup']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['hostgroup']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['hostgroup']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		$this->validConfig['service']['only_hard_states']['default'] = $this->getValue('global', 0, 'only_hard_states');
		$this->validConfig['service']['backend_id']['default'] = $this->getValue('global', 0, 'backend_id');
		$this->validConfig['service']['iconset']['default'] = $this->getValue('global', 0, 'iconset');
		$this->validConfig['service']['hover_template']['default'] = $this->getValue('global', 0, 'hover_template');
		$this->validConfig['service']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['service']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['service']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['service']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['service']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		$this->validConfig['servicegroup']['only_hard_states']['default'] = $this->getValue('global', 0, 'only_hard_states');
		$this->validConfig['servicegroup']['backend_id']['default'] = $this->getValue('global', 0, 'backend_id');
		$this->validConfig['servicegroup']['iconset']['default'] = $this->getValue('global', 0, 'iconset');
		$this->validConfig['servicegroup']['hover_template']['default'] = $this->getValue('global', 0, 'hover_template');
		$this->validConfig['servicegroup']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['servicegroup']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['servicegroup']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['servicegroup']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['servicegroup']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		$this->validConfig['map']['only_hard_states']['default'] = $this->getValue('global', 0, 'only_hard_states');
		$this->validConfig['map']['iconset']['default'] = $this->getValue('global', 0, 'iconset');
		$this->validConfig['map']['hover_template']['default'] = $this->getValue('global', 0, 'hover_template');
		$this->validConfig['map']['label_show']['default'] = $this->getValue('global', 0, 'label_show');
		$this->validConfig['map']['label_x']['default'] = $this->getValue('global', 0, 'label_x');
		$this->validConfig['map']['label_y']['default'] = $this->getValue('global', 0, 'label_y');
		$this->validConfig['map']['label_width']['default'] = $this->getValue('global', 0, 'label_width');
		$this->validConfig['map']['label_background']['default'] = $this->getValue('global', 0, 'label_background');
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::getObjectDefaults()');
	}
	
	/**
	 * Reads which map image should be used
	 *
	 * @return	String	MapImage
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getImage() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::getImage()');
		$ret = $this->image = $this->getValue('global', 0, 'map_image');
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::getImage()');
		return $ret;
	}
	
	/**
	 * Deletes the map image
	 *
	 * @param	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function deleteImage($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::deleteImage('.$printErr.')');
		if($this->checkMapImageWriteable($printErr)) {
			if(unlink($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::deleteImage(): TRUE');
				return TRUE;
			} else {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		        $FRONTEND->messageToUser('ERROR','couldNotDeleteMapImage','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::deleteImage(): FALSE');
				return FALSE;
			}
		}
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
	 * Deletes the map configfile
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function deleteMapConfig() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::deleteMapConfig()');
		// is file writeable?
		if($this->checkMapConfigWriteable(0)) {
			if(unlink($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg')) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::deleteMapConfig(): TRUE');
				return TRUE;
			} else {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		        $FRONTEND->messageToUser('ERROR','couldNotDeleteMapCfg','MAPPATH~'.$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::deleteMapConfig(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::deleteMapConfig(): FALSE');
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
				$types = Array('global'=>0,'host'=>0,'service'=>0,'hostgroup'=>0,'servicegroup'=>0,'map'=>0,'textbox'=>0,'shape'=>0);
				
				// read file in array
				if (DEBUG&&DEBUGLEVEL&2) debug('Start reading map configuration');
				$file = file($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
				if (DEBUG&&DEBUGLEVEL&2) debug('End reading map configuration');
				$createArray = Array('allowed_user','allowed_for_config');
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
								$entry = explode('=',$file[$l], 2);
								// entry[0]: var name, entry[1]: value
								$entry[0] = trim($entry[0]);
								if(isset($entry[1])) {
									$entry[1] = trim($entry[1]);
									if(in_array($entry[0],$createArray)) {
										$this->mapConfig[$define[1]][$type][$entry[0]] = explode(',',str_replace(' ','',$entry[1]));
									} else {
										// NagVis 1.2: Renamed "*_name" to "name", handle the old maps
										if(preg_match('/_name$/i',$entry[0])) {
											$this->mapConfig[$define[1]][$type]['name'] = $entry[1];
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
				
				if($this->checkMapConfigIsValid(1)) {
					$this->getImage();
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
	 * Writes the element from array to the config file
	 *
	 * @param	String	$type	Type of the Element
	 * @param	Integer	$id		Id of the Element
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function writeElement($type,$id) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::writeElement('.$type.','.$id.')');
		if($this->checkMapConfigExists(1) && $this->checkMapConfigReadable(1) && $this->checkMapConfigWriteable(1)) {
			// read file in array
			$file = file($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
			
			// number of lines in the file
			$l = 0;
			// number of elements of the given type
			$a = 0;
			// done?!
			$done = FALSE;
			while(isset($file[$l]) && $file[$l] != '' && $done == FALSE) {
				// ignore comments
				if(!ereg('^#',$file[$l]) && !ereg('^;',$file[$l])) {
					$defineCln = explode('{', $file[$l]);
					$define = explode(' ',$defineCln[0]);
					// select only elements of the given type
					if(isset($define[1]) && trim($define[1]) == $type) {
						// check if element exists
						if($a == $id) {
							// check if element is an array...
							if(is_array($this->mapConfig[$type][$a])) {
								// ...array: update!
								
								// choose first parameter line
								$l++;
								
								
								// loop parameters from array
								foreach($this->mapConfig[$type][$id] AS $key => $val) {
									// if key is not type
									if($key != 'type') {
										$cfgLines = 0;
										$cfgLine = '';
										$cfgLineNr = 0;
										// Parameter aus Datei durchlaufen
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
											unset($file[$cfgLineNr]);
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
				if($file[count($file)-1] != "\n") {
					$file[] = "\n";
				}
				$file[] = 'define '.$type." {\n";
				foreach($this->mapConfig[$type][$id] AS $key => $val) {
					if(isset($val) && $val != '') {
						$file[] = $key.'='.$val."\n";
					}
				}
				$file[] = "}\n";
				$file[] = "\n";
			}
			
			// open file for writing and replace it
		 	$fp = fopen($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg','w');
		 	fwrite($fp,implode('',$file));
		 	fclose($fp);
		 	if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::writeElement(): TRUE');
			return TRUE;
		} else {
		 	if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::writeElement(): FALSE');
			return FALSE;
		} 
	}
	
	/**
	 * Checks for existing map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkMapImageExists($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapImageExists('.$printErr.')');
		if($this->image != '') {
			//if(file_exists($this->MAINCFG->getValue('paths', 'map').$this->image)) {
			if(@fclose(@fopen($this->MAINCFG->getValue('paths', 'map').$this->image, 'r'))) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageExists(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','backgroundNotExists','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageExists(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageExists(): FALSE');
			return FALSE;
		}
	}
	
    /**
	 * Checks for readable map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkMapImageReadable($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapImageReadable('.$printErr.')');
		if($this->image != '') {
			if(is_readable($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageReadable(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','backgroundNotReadable','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageReadable(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageReadable(): FALSE');
			return FALSE;
		}
	}
	
    /**
	 * Checks for writeable map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkMapImageWriteable($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapImageWriteable('.$printErr.')');
		if($this->image != '') {
			//FIXME: is_writable doesn't check write permissions
			if($this->checkMapImageExists($printErr) /*&& is_writable($this->MAINCFG->getValue('paths', 'map').$this->image)*/) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageWriteable(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','backgroundNotWriteable','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageWriteable(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageWriteable(): FALSE');
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
	 * Checks for writeable map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkMapConfigWriteable($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapConfigWriteable('.$printErr.')');
		if($this->checkMapConfigExists($printErr) && is_writeable($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg')) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigWriteable(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
	            $FRONTEND->messageToUser('ERROR','mapCfgNotWriteable','MAP~'.$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigWriteable(): FALSE');
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
						// FIXME: check valid value format
						if(!array_key_exists($key,$this->validConfig[$type])) {
							// unknown atribute
							if($printErr == 1) {
								$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
					            $FRONTEND->messageToUser('ERROR','unknownAttribute','MAPNAME~'.$this->name.',ATTRIBUTE~'.$key.',TYPE~'.$type);
							}
							if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapConfigIsValid(): FALSE');
							return FALSE;
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
