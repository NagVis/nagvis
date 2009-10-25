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
	var $MAPCFG;
	
	var $MAP;
	
	/**
	* Constructor
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function WuiFrontend($CORE, $MAPCFG) {
		$this->MAPCFG = $MAPCFG;
		
		$prop = Array('title'=> $CORE->getMainCfg()->getValue('internal', 'title') . ' WUI',
					  'cssIncludes'=>Array('../nagvis-js/css/style.css',
						                     './css/wui.css',
						                     './css/office_xp/office_xp.css'),
					  'jsIncludes'=>Array(
							'../nagvis-js/js/frontendEventlog.js',
							'../nagvis-js/js/ajax.js',
							'../nagvis-js/js/frontendMessage.js',
							'../nagvis-js/js/nagvis.js',
					  	'./js/popupWindow.js',
							'./js/wui.js',
					  	'./js/ajax.js',
					  	'./js/addmodify.js',
					  	'./js/EditMainCfg.js',
					  	'./js/BackgroundManagement.js',
					  	'./js/BackendManagement.js',
					  	'./js/MapManagement.js',
					  	'./js/ShapeManagement.js',
						  './js/jsdomenu.js',
						  './js/jsdomenu.inc.js',
						  './js/wz_jsgraphics.js',
						  './js/wz_dragdrop.js'),
					  'extHeader'=> '<style type="text/css">body.main { background-color: '.$this->MAPCFG->getValue('global',0, 'background_color').'; }</style>',
					  'allowedUsers' => $this->MAPCFG->getValue('global', 0, 'allowed_for_config'),
					  'languageRoot' => 'nagvis');
		parent::__construct($CORE, $prop);
	}
	
	function checkPreflight() {
		if(!$this->checkPHPMBString(1)) {
			exit;
		}
	}
	
	function checkPHPMBString($printErr=1) {
		if (!extension_loaded('mbstring')) {
			if($printErr) {
				new GlobalMessage('ERROR', $this->CORE->getLang()->getText('phpModuleNotLoaded','MODULE~mbstring'));
			}
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	/**
	* If enabled, the map is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getMap() {
		$this->addBodyLines('<div id="mymap" class="map">');
		$this->MAP = new WuiMap($this->CORE, $this->MAPCFG);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines('</div>');
	}
}
?>
