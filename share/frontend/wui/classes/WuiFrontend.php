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
		
		$htmlBase = $CORE->getMainCfg()->getValue('paths', 'htmlbase');
		$htmlJs = $CORE->getMainCfg()->getValue('paths', 'htmljs');
		
		$prop = Array('title'=> $CORE->getMainCfg()->getValue('internal', 'title') . ' WUI',
					  'cssIncludes'=>Array($CORE->getMainCfg()->getValue('paths','htmlpagetemplates').'/default.css',
						                     './css/wui.css',
						                     './css/office_xp/office_xp.css'),
					  'jsIncludes'=>Array(
							$htmlJs.'frontendEventlog.js',
							$htmlJs.'ajax.js',
							$htmlJs.'frontendMessage.js',
							$htmlJs.'nagvis.js',
							$htmlJs.'popupWindow.js',
							$htmlBase.'/frontend/wui/js/wui.js',
					  	$htmlBase.'/frontend/wui/js/ajax.js',
					  	$htmlBase.'/frontend/wui/js/addmodify.js',
					  	$htmlBase.'/frontend/wui/js/EditMainCfg.js',
					  	$htmlBase.'/frontend/wui/js/BackgroundManagement.js',
					  	$htmlBase.'/frontend/wui/js/BackendManagement.js',
					  	$htmlBase.'/frontend/wui/js/MapManagement.js',
					  	$htmlBase.'/frontend/wui/js/ShapeManagement.js',
						  $htmlBase.'/frontend/wui/js/jsdomenu.js',
						  $htmlBase.'/frontend/wui/js/jsdomenu.inc.js',
						  $htmlBase.'/frontend/wui/js/wz_jsgraphics.js',
						  $htmlBase.'/frontend/wui/js/wz_dragdrop.js'),
					  'extHeader'=> '<style type="text/css">body.main { background-color: '.$this->MAPCFG->getValue('global',0, 'background_color').'; }</style>',
					  'allowedUsers' => $this->MAPCFG->getValue('global', 0, 'allowed_for_config'),
					  'languageRoot' => 'nagvis');
		parent::__construct($CORE, $prop);
	}
}
?>
