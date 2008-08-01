<?php
/*****************************************************************************
 *
 * NagVisStatelessObject.php - Abstract class of a stateless object in NagVis
 *                  with all necessary informations which belong to the object
 *                  handling in NagVis
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
class NagVisStatelessObject extends NagVisObject {
	var $CORE;
	var $MAINCFG;
	var $LANG;
	
	// "Global" Configuration variables for all stateless objects
	var $label_show;
	var $recognize_services;
	var $only_hard_states;
	
	var $iconPath;
	var $iconHtmlPath;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalLanguage
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisStatelessObject(&$CORE) {
		$this->CORE = &$CORE;
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
		
		parent::NagVisObject($this->CORE);
	}
	
	/**
	 * PUBLIC fetchMembers()
	 *
	 * Just a dummy here
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMembers() {
	}
	
	/**
	 * Parses the HTML-Code of an icon
	 *
	 * @param	Boolean	$link		Add a link to the icon
	 * @param	Boolean	$hoverMenu	Add a hover menu to the icon
	 * @return	String	String with Html Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseIcon() {
		if(preg_match('/^\[(.*)\]$/',$this->icon,$match) > 0) {
			$imgPath = $match[1];
		} else {
			$imgPath = $this->iconHtmlPath.$this->icon;
		}
		
		$ret = '<div class="icon" style="left:'.$this->x.'px;top:'.$this->y.'px;z-index:'.$this->z.';">';
		if($this->getUrl() != '') {
			$ret .= '<a href="'.$this->getUrl().'" target="'.$this->getUrlTarget().'">';
		}
		$ret .= '<img src="'.$imgPath.'" '.$this->getHoverMenu().' alt="'.$this->type.'-'.$this->icon.'">';
		$ret .= '</a>';
		$ret .= '</div>';
		
		return $ret;
	}
}
?>
