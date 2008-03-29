<?php
/*****************************************************************************
 *
 * WuiFrontend.php - Class of NagVis frontend
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
class WuiFrontend extends GlobalPage {
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	
	var $MAP;
	
	/**
	* Constructor
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function WuiFrontend(&$MAINCFG,&$MAPCFG) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		
		$this->LANG = new GlobalLanguage($this->MAINCFG,'wui:global');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('../nagvis/includes/css/style.css','./includes/css/wui.css','./includes/css/office_xp/office_xp.css'),
					  'jsIncludes'=>Array('./includes/js/wui.js',
					  	  './includes/js/ajax.js',
						  './includes/js/jsdomenu.js',
						  './includes/js/jsdomenu.inc.js',
						  './includes/js/wz_jsgraphics.js',
						  './includes/js/wz_dragdrop.js'),
					  'extHeader'=>Array("<style type=\"text/css\">body.main { background-color: ".$this->MAPCFG->getValue('global',0, 'background_color')."; }</style>"),
					  'allowedUsers' => $this->MAPCFG->getValue('global', 0,'allowed_for_config'),
					  'languageRoot' => 'wui:global');
		parent::GlobalPage($this->MAINCFG,$prop);
	}
	
	/**
	* If enabled, the map is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getMap() {
		$this->addBodyLines('<div id="mymap" class="map">');
		$this->MAP = new WuiMap($this->MAINCFG,$this->MAPCFG,$this->LANG);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines('</div>');
	}
	
	/**
	 * Adds the user messages to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMessages() {
		$this->addBodyLines($this->getUserMessages());	
	}
}
?>
