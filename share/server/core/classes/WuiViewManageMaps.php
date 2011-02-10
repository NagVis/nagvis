<?php
/*****************************************************************************
 *
 * WuiViewManageMaps.php - View to render manage maps page
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
class WuiViewManageMaps {
	private $CORE;
	private $AUTHENTICATION;
	private $AUTHORISATION;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(CoreAuthHandler $AUTHENTICATION, CoreAuthorisationHandler $AUTHORISATION) {
		$this->CORE = GlobalCore::getInstance();
		$this->AUTHENTICATION = $AUTHENTICATION;
		$this->AUTHORISATION = $AUTHORISATION;
	}
	
	/**
	 * Parses the information in html format
	 *
	 * @return	String 	String with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parse() {
		// Initialize template system
		$TMPL = New CoreTemplateSystem($this->CORE);
		$TMPLSYS = $TMPL->getTmplSys();
		
		$aData = Array(
			'htmlBase'              => $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'),
			'langCreateMap'         => $this->CORE->getLang()->getText('createMap'),
			'langCreate'            => $this->CORE->getLang()->getText('create'),
			'langRenameMap'         => $this->CORE->getLang()->getText('renameMap'),
			'langRename'            => $this->CORE->getLang()->getText('rename'),
			'langDeleteMap'         => $this->CORE->getLang()->getText('deleteMap'),
			'langDelete'            => $this->CORE->getLang()->getText('delete'),
			'langExportMap'         => $this->CORE->getLang()->getText('exportMap'),
			'langExport'            => $this->CORE->getLang()->getText('export'),
			'langImportMap'         => $this->CORE->getLang()->getText('importMap'),
			'langImport'            => $this->CORE->getLang()->getText('import'),
			'langChooseMap'         => $this->CORE->getLang()->getText('chooseMap'),
			'langChooseMapFile'     => $this->CORE->getLang()->getText('chooseMapFile'),
			'langNewMapName'        => $this->CORE->getLang()->getText('newMapName'),
			'langMapName'           => $this->CORE->getLang()->getText('mapName'),
			'langMapIconset'        => $this->CORE->getLang()->getText('mapIconset'),
			'langBackground'        => $this->CORE->getLang()->getText('background'),
			'availableBackgrounds'  => $this->CORE->getAvailableBackgroundImages(),
			'availableIconsets'     => $this->CORE->getAvailableIconsets(),
			'availableMaps'         => $this->CORE->getAvailableMaps(),
		);
		
		// Build page based on the template file and the data array
		return $TMPLSYS->get($TMPL->getTmplFile('default', 'wuiManageMaps'), $aData);
	}
}
?>
