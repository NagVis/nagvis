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
	var $MAINCFG;
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
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
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
	function parseMap() {
		$ret = '';
		$ret .= $this->getBackground();
		$ret .= $this->parseObjects();
		// Dynamicaly set favicon
		$ret .= $this->getFavicon();
		// Change title (add map alias and map state), set map name
		$ret .= '<script type="text/javascript" language="JavaScript">var htmlBase=\''.$this->MAINCFG->getValue('paths', 'htmlbase').'\'; var mapName=\''.$this->MAPCFG->getName().'\'; var showHoverMenu=false; var hoverMenu=\'\'; document.title=\''.$this->MAPCFG->getValue('global', 0, 'alias').' ('.$this->MAPOBJ->getSummaryState().') :: \'+document.title;</script>';
		
		return $ret;
	}
	
	/**
	 * Gets the background of the map
	 *
	 * @return	String HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackground() {
		return $this->getBackgroundHtml($this->MAINCFG->getValue('paths', 'htmlmap').$this->MAPCFG->BACKGROUND->getFileName());
	}
	
	/**
	 * Gets the favicon of the page representation the state of the map
	 *
	 * @return	String	HTML Code
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
		
		if(file_exists($this->MAINCFG->getValue('paths', 'images').'internal/favicon_'.$favicon.'.png')) {
			$favicon = $this->MAINCFG->getValue('paths', 'htmlimages').'internal/favicon_'.$favicon.'.png';
		} else {
			$favicon = $this->MAINCFG->getValue('paths', 'htmlimages').'internal/favicon.png';
		}
		return '<script type="text/javascript" language="JavaScript">favicon.change(\''.$favicon.'\'); </script>';
	}
	
	/**
	 * Parses the Objects
	 *
	 * @return	String Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseObjects() {
		$ret = '';
		foreach($this->MAPOBJ->getMapObjects() AS $OBJ) {
			switch(get_class($OBJ)) {
				case 'NagVisHost':
				case 'NagVisService':
				case 'NagVisHostgroup':
				case 'NagVisServicegroup':
				case 'NagVisMapObj':
				case 'NagVisShape':
				case 'NagVisTextbox':
					$ret .= $OBJ->parse();
				break;
			}
		}
		
		return $ret;
	}
}
?>
