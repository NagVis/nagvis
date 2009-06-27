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
	private $BACKEND;
	public $MAPOBJ;
	private $numLineObjects;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $MAPCFG, $BACKEND, $getState=1) {
		parent::__construct($CORE, $MAPCFG);
		
		$this->BACKEND = $BACKEND;
		
		$this->numLineObjects = 0;
		
		$this->MAPOBJ = new NagVisMapObj($CORE, $BACKEND, $MAPCFG);
		
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
	public function parseMapJson() {
		$ret = '';
		$ret .= 'oGeneralProperties='.$this->CORE->MAINCFG->parseGeneralProperties().';'."\n";
		$ret .= 'oWorkerProperties='.$this->CORE->MAINCFG->parseWorkerProperties().';'."\n";
		$ret .= 'oFileAges='.$this->parseFileAges().';'."\n";
		$ret .= 'oPageProperties='.$this->parseMapPropertiesJson().';'."\n";
		$ret .= 'aInitialMapObjects='.$this->parseObjectsJson().';'."\n";
		$ret .= 'aMapObjects=Array();'."\n";
		
		// Kick of the worker
		$ret .= 'addDOMLoadEvent(function(){runWorker(0, \'map\')});';
		
		// This disables the context menu when someone clicked anywhere on the map
		$ret .= 'document.body.onmousedown = contextMouseDown;';
		
		return $ret;
	}
	
	/**
	 * Parses the Map and the Object options in json format
	 *
	 * @return	String 	String with JSON Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseMapPropertiesJson() {
		$arr = Array();
		$arr['map_name'] = $this->MAPCFG->getName();
		$arr['alias'] = $this->MAPCFG->getValue('global', 0, 'alias');
		$arr['background_image'] = $this->getBackgroundJson();
		$arr['background_color'] = $this->MAPCFG->getValue('global', 0, 'background_color');
		$arr['favicon_image'] = $this->getFavicon();
		$arr['page_title'] = $this->MAPCFG->getValue('global', 0, 'alias').' ('.$this->MAPOBJ->getSummaryState().') :: '.$this->CORE->MAINCFG->getValue('internal', 'title');
		$arr['event_background'] = $this->MAPCFG->getValue('global', 0, 'event_background');
		$arr['event_highlight'] = $this->MAPCFG->getValue('global', 0, 'event_highlight');
		$arr['event_highlight_interval'] = $this->MAPCFG->getValue('global', 0, 'event_highlight_interval');
		$arr['event_highlight_duration'] = $this->MAPCFG->getValue('global', 0, 'event_highlight_duration');
		$arr['event_log'] = $this->MAPCFG->getValue('global', 0, 'event_log');
		$arr['event_log_level'] = $this->MAPCFG->getValue('global', 0, 'event_log_level');
		$arr['event_log_height'] = $this->MAPCFG->getValue('global', 0, 'event_log_height');
		$arr['event_log_hidden'] = $this->MAPCFG->getValue('global', 0, 'event_log_hidden');
		$arr['event_scroll'] = $this->MAPCFG->getValue('global', 0, 'event_scroll');
		$arr['event_sound'] = $this->MAPCFG->getValue('global', 0, 'event_sound');
		
		return json_encode($arr);
	}
	
	/**
	 * Parses the config file ages
	 *
	 * @return	String 	JSON Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function parseFileAges() {
		$arr = Array();
		
		$arr['main_config'] = $this->CORE->MAINCFG->getConfigFileAge();
		$arr['map_config'] = $this->MAPCFG->getFileModificationTime();
		
		return json_encode($arr);
	}
	
	/**
	 * Gets the path to the background of the map
	 *
	 * @return	String  Javascript code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getBackgroundJson() {
		return $this->MAPCFG->BACKGROUND->getFile();
	}
	
	/**
	 * Gets the favicon of the page representation the state of the map
	 *
	 * @return	String	Path to the favicon
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getFavicon() {
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
	public function parseObjectsJson() {
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
