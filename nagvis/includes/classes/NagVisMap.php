<?php
/*****************************************************************************
 *
 * NagVisMap.php - Class for parsing the NagVis maps
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
class NagVisMap extends GlobalMap {
	var $CORE;
	var $MAPCFG;
	var $BACKEND;
	var $MAPOBJ;
	var $numLineObjects;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisMap(&$CORE,&$MAPCFG,&$BACKEND,$getState=1) {
		$this->CORE = &$CORE;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		
		$this->numLineObjects = 0;
		
		parent::GlobalMap($this->CORE, $this->MAPCFG);
		$this->MAPOBJ = new NagVisMapObj($this->CORE, $this->BACKEND, $this->MAPCFG);
		
		$this->MAPOBJ->fetchMembers();
		
		if($getState) {
			$this->MAPOBJ->fetchState();
		}
	}
	
	/**
	 * Parses the Map and the Objects
	 *
	 * @return	String 	String with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseMapJson() {
		$ret = '';
		$ret .= 'var oGeneralProperties='.$this->CORE->MAINCFG->parseGeneralProperties().';'."\n";
		$ret .= 'var oWorkerProperties='.$this->CORE->MAINCFG->parseWorkerProperties().';'."\n";
		$ret .= 'var oFileAges='.$this->parseFileAges().';'."\n";
		$ret .= 'var oMapProperties='.$this->parseMapPropertiesJson().';'."\n";
		$ret .= 'var aMapObjects=Array();'."\n";
		$ret .= 'var aInitialMapObjects='.$this->parseObjectsJson().';'."\n";
		
		$ret .= '
		var htmlBase=\''.$this->CORE->MAINCFG->getValue('paths', 'htmlbase').'\';';
		
		// Kick of the worker
		$ret .= 'runWorker(0, \'map\');';
		
		return $ret;
	}
	
	/**
	 * Parses the config file ages
	 *
	 * @return	String 	JSON Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseFileAges() {
		$arr = Array();
		
		$arr['main_config'] = $this->CORE->MAINCFG->getConfigFileAge();
		$arr['map_config'] = $this->MAPCFG->getFileModificationTime();
		
		return json_encode($arr);
	}
	
	/**
	 * Parses the Map and the Object options in json format
	 *
	 * @return	String 	String with JSON Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseMapPropertiesJson() {
		$arr = Array();
		$arr['map_name'] = $this->MAPCFG->getName();
		$arr['background_image'] = $this->getBackgroundJson();
		$arr['favicon_image'] = $this->getFavicon();
		$arr['page_title'] = $this->MAPCFG->getValue('global', 0, 'alias').' ('.$this->MAPOBJ->getSummaryState().') :: '.$this->CORE->MAINCFG->getValue('internal', 'title');
		$arr['event_log'] = $this->MAPCFG->getValue('global', 0, 'event_log');
		$arr['event_log_level'] = $this->MAPCFG->getValue('global', 0, 'event_log_level');
		
		return json_encode($arr);
	}
	
	/**
	 * Gets the path to the background of the map
	 *
	 * @return	String  Javascript code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackgroundJson() {
		return $this->CORE->MAINCFG->getValue('paths', 'htmlmap').$this->MAPCFG->BACKGROUND->getFileName();
	}
	
	/**
	 * Gets the favicon of the page representation the state of the map
	 *
	 * @return	String	Path to the favicon
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getFavicon() {
		if($this->MAPOBJ->getSummaryInDowntime()) {
			$favicon = 'downtime';
		} elseif($this->MAPOBJ->getSummaryAcknowledgement()) {
			$favicon = 'ack';
		} else {
			$favicon = strtolower($this->MAPOBJ->getSummaryState());
		}
		
		if(file_exists($this->CORE->MAINCFG->getValue('paths', 'images').'internal/favicon_'.$favicon.'.png')) {
			$favicon = $this->CORE->MAINCFG->getValue('paths', 'htmlimages').'internal/favicon_'.$favicon.'.png';
		} else {
			$favicon = $this->CORE->MAINCFG->getValue('paths', 'htmlimages').'internal/favicon.png';
		}
		return $favicon;
	}
	
	/**
	 * Parses the Objects
	 *
	 * @return	String  Json Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseObjectsJson() {
		$arrRet = Array();
		
		$i = 0;
		foreach($this->MAPOBJ->getMembers() AS $OBJ) {
			switch(get_class($OBJ)) {
				case 'NagVisHost':
				case 'NagVisService':
				case 'NagVisHostgroup':
				case 'NagVisServicegroup':
				case 'NagVisMapObj':
				case 'NagVisShape':
				case 'NagVisTextbox':
					$arrRet[] = $OBJ->parseJson();
				break;
			}
			$i++;
		}
		
		return json_encode($arrRet);
	}
}
?>
