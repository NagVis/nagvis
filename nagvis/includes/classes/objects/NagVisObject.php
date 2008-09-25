<?php
/*****************************************************************************
 *
 * NagVisObject.php - Abstract class of an object in NagVis with all necessary 
 *                  informations which belong to the object handling in NagVis
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
class NagVisObject {
	var $CORE;
	
	var $conf;
	
	// "Global" Configuration variables for all objects
	var $type;
	var $x;
	var $y;
	var $z;
	var $icon;
	
	var $hover_menu;
	var $hover_childs_show;
	var $hover_childs_sort;
	var $hover_childs_order;
	var $hover_childs_limit;
	var $label_show;
	var $recognize_services;
	var $only_hard_states;
	
	var $iconPath;
	var $iconHtmlPath;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisObject(&$CORE) {
		$this->CORE = &$CORE;
		
		$this->conf = Array();
	}
	
	function get($option) {
		return $this->{$option};
	}
	
	/**
	 * Get method for x coordinate of the object
	 *
	 * @return	Integer		x coordinate on the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getX() {
		return $this->x;
	}
	
	/**
	 * Get method for y coordinate of the object
	 *
	 * @return	Integer		y coordinate on the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getY() {
		return $this->y;
	}
	
	/**
	 * Get method for z coordinate of the object
	 *
	 * @return	Integer		z coordinate on the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getZ() {
		return $this->z;
	}
	
	/**
	 * Get method for type of the object
	 *
	 * @return	String		Type of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getType() {
		return $this->type;
	}
	
	/**
	 * Get method for the name of the object
	 *
	 * @return	String		Name of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getName() {
		if($this->type == 'service') {
			return $this->host_name;
		} else {
			return $this->{$this->getType().'_name'};
		}
	}
	
	/**
	 * Get method for the hover template of the object
	 *
	 * @return	String		Hover template of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverTemplate() {
		return $this->hover_template;
	}
	
	/**
	 * Set method for the object coords
	 *
	 * @return	Array		Array of the objects coords
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setMapCoords($arrCoords) {
		$this->x = $arrCoords['x'];
		$this->y = $arrCoords['y'];
		$this->z = $arrCoords['z'];
	}
	
	/**
	 * PUBLIC setConfiguration()
	 *
	 * Sets options of the object
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setConfiguration($obj) {
		foreach($obj AS $key => $val) {
			$this->conf[$key] = $val;
			$this->{$key} = $val;
		}
	}
	
	/**
	 * PUBLIC setObjectInformation()
	 *
	 * Sets extended informations of the object
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setObjectInformation($obj) {
		foreach($obj AS $key => $val) {
			$this->{$key} = $val;
		}
	}
	
	/**
	 * PULBLIC getObjectInformation()
	 *
	 * Gets all necessary informations of the object as array
	 *
	 * @return	Array		Object configuration
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getObjectInformation() {
		$arr = Array();
		
		// Need to remove some options which are not interesting
		$arrDenyKeys = Array('CORE', 'BACKEND', 'MAPCFG', 'MAP', 'GRAPHIC', 'conf',
			'services', 'fetchedChildObjects', 'childObjects', 'members', 'objects',
			'linkedMaps');
		
		foreach($this AS $key => $val) {
			if(!in_array($key, $arrDenyKeys)) {
				$arr[$key] = $val;
			}
		}
		
		// Save the number of childs
		switch($this->getType()) {
			case 'host':
				$arr['num_members'] = $this->getNumServices();
			break;
			case 'hostgroup':
			case 'servicegroup':
				$arr['num_members'] = $this->getNumMembers();
			break;
			case 'map':
				$arr['num_members'] = $this->getNumObjects();
			break;
		}
		
		/**
		 * FIXME: Find another place for that! This is a bad place for language strings!
		 */
		
		if($this->type == 'service') {
			$name = 'hostname';
		} else {
			$name = $this->type . 'name';
		}
		
		// Get the child name label
		switch($this->type) {
			case 'host':
				$childType = 'servicename';
			break;
			case 'hostgroup':
				$childType = 'hostname';
			break;
			case 'servicegroup':
				$childType = 'servicename';
			break;
			default:
				$childType = 'objectname';
			break;
		}
		
		$arr['lang_obj_type'] = $this->CORE->LANG->getText($this->type);
		$arr['lang_name'] = $this->CORE->LANG->getText($name);
		$arr['lang_child_name'] = $this->CORE->LANG->getText($childType);
		$arr['lang_child_name1'] = $this->CORE->LANG->getText('hostname');
		$arr['lang_last_status_refresh'] = $this->CORE->LANG->getText('lastStatusRefresh');
		
		// I want only "name" in js
		if($this->type != 'shape' && $this->type != 'textbox') {
			$arr['name'] = $this->getName();
			if($this->type == 'service') {
				unset($arr['host_name']);
			} else {
				unset($arr[$this->getType().'_name']);
			}
			
			// Little hack: Overwrite the options with correct state informations
			$arr = array_merge($arr, $this->getObjectStateInformations());
		}
		
		
		return $arr;
	}
	
	/**
	 * PULBLIC getObjectConfiguration()
	 *
	 * Gets the configuration of the object
	 *
	 * @return	Array		Object configuration
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getObjectConfiguration() {
		// There have to be removed some options which are only for this object
		$arr = $this->conf;
		unset($arr['id']);
		unset($arr['type']);
		unset($arr['host_name']);
		unset($arr[$this->getType().'_name']);
		unset($arr['service_description']);
		return $arr;
	}
	
	/**
	 * PUBLIC replaceMacros()
	 *
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceMacros() {
		return TRUE;
	}
	
	/**
	 * PUBLIC getHoverMenu
	 *
	 * Creates a hover box for objects
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverMenu() {
		$ret = '';
		
		if($this->hover_menu) {
			$sRequest = $this->CORE->MAINCFG->getValue('paths','htmlbase').'/nagvis/ajax_handler.php?action=getObjectStates&ty=complete&m[]=&t[]='.$this->getType().'&n1[]='.$this->getName().'&i[]=a'.md5(time()).'&n2[]=';
			
			if($this->getType() == 'service') {
				$ret .= 'onmouseover="displayHoverMenu(replaceHoverTemplateMacros(\'0\', new NagVisHost(getSyncRequest(\''.$sRequest.$this->service_description.'\')[0]), getHoverTemplate(\''.$this->getHoverTemplate().'\')),'.($this->hover_delay*1000).',\'\');" onmouseout=" return hideHoverMenu();"';
			} else {
				$ret .= 'onmouseover="displayHoverMenu(replaceHoverTemplateMacros(\'0\', new NagVisHost(getSyncRequest(\''.$sRequest.'\')[0]), getHoverTemplate(\''.$this->getHoverTemplate().'\')),'.($this->hover_delay*1000).',\'\');" onmouseout=" return hideHoverMenu();"';
			}
			
			return $ret;
		}
	}
	
	/**
	 * PRIVATE getUrl()
	 *
	 * Returns the url for the object link
	 *
	 * @return	String	URL
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getUrl() {
		return $this->url;
	}
	
	/**
	 * PRIVATE getUrlTarget()
	 *
	 * Returns the target frame for the object link
	 *
	 * @return	String	Target
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getUrlTarget() {
		return $this->url_target;
	}
	
	/**
	 * PRIVATE getHoverTemplateChildReplacements
	 *
	 * Get the hover template child replacement options
	 *
	 * @return	String		HTML code for the hover menu
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverChildsJson() {
		$arrObjects = Array();
		$arrRet = Array();
		
		switch($this->type) {
			case 'host':
				$arrObjects = $this->getServices();
			break;
			case 'hostgroup':
				$arrObjects = $this->getMembers();
			break;
			case 'servicegroup':
				$arrObjects = $this->getMembers();
			break;
			case 'map':
				$arrObjects = $this->getMapObjects();
			break;
		}
		
		// Sort the array of child objects by the sort option
		switch($this->hover_childs_sort) {
			case 's':
				// Order by State
				usort($arrObjects, Array("NagVisObject", "sortObjectsByState"));
			break;
			case 'a':
			default:
				// Order alhpabetical
				usort($arrObjects, Array("NagVisObject", "sortObjectsAlphabetical"));
			break;
		}
		
		// If the sorted array should be reversed
		if($this->hover_childs_order == 'desc') {
			$arrObjects = array_reverse($arrObjects);
		}
		
		// Count only once, not in loop header
		$numObjects = count($arrObjects);
		
		// Loop all child object until all looped or the child limit is reached
		for($i = 0; $i <= $this->hover_childs_limit && $i < $numObjects; $i++) {
			// Only get the next childs when this is no loop
			if($arrObjects[$i]->getType() != 'map' || ($arrObjects[$i]->getType() == 'map' && $this->MAPCFG->getName() != $arrObjects[$i]->MAPCFG->getName())) {
				if($arrObjects[$i]->getType() != 'textbox' && $arrObjects[$i]->getType() != 'shape') {
					$arrRet[] =  $arrObjects[$i]->parseJson();
				}
			}
		}
		
		return $arrRet;
	}
	
	/**
	 * PRIVATE STATIC sortObjectsAlphabetical()
	 *
	 * Sorts the both alhabeticaly by the name
	 *
	 * @param	OBJ		First object to sort
	 * @param	OBJ		Second object to sort
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	static function sortObjectsAlphabetical($OBJ1, $OBJ2) {
		// Do not sort shapes and textboxes
		if($OBJ1->getType() == 'shape' || $OBJ1->getType() == 'textbox' || $OBJ2->getType() == 'shape' || $OBJ2->getType() == 'textbox') {
			return 0;
		}

		if($OBJ1->getType() == 'service') {
			$name1 = strtolower($OBJ1->getName().$OBJ1->getServiceDescription());
		} else {
			$name1 = strtolower($OBJ1->getName());
		}
		
		if($OBJ2->getType() == 'service') {
			$name2 = strtolower($OBJ2->getName().$OBJ2->getServiceDescription());
		} else {
			$name2 = strtolower($OBJ2->getName());
		}

		if ($name1 == $name2) {
			return 0;
		} elseif($name1 > $name2) {
			return +1;
		} else {
			return -1;
		}
	}
	
	/**
	 * PRIVATE STATIC sortObjectsByState()
	 *
	 * Sorts the both by state of the object
	 *
	 * @param	OBJ		First object to sort
	 * @param	OBJ		Second object to sort
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	static function sortObjectsByState($OBJ1, $OBJ2) {
		$arrStates = Array('UNREACHABLE' => 6, 'DOWN' => 5, 'CRITICAL' => 5, 'WARNING' => 4, 'UNKNOWN' => 3, 'ERROR' => 2, 'UP' => 1, 'OK' => 1, 'PENDING' => 0);
		
		// Textboxes and shapes does not have getSummaryState method, exclude them here
		if(method_exists($OBJ1, 'getSummaryState') && method_exists($OBJ2, 'getSummaryState')) {
			$state1 = $OBJ1->getSummaryState();
			$state2 = $OBJ2->getSummaryState();
		} else {
			return 0;
		}
		
		if($arrStates[$state1] == $arrStates[$state2]) {
			return 0;
		} elseif($arrStates[$state1] < $arrStates[$state2]) {
			return +1;
		} else {
			return -1;
		}
	}
}
?>
