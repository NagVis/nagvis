<?php
/*****************************************************************************
 *
 * NagVisShape.php - Class of a Shape in NagVis with all necessary 
 *                  information which belong to the object handling in NagVis
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
class NagVisShape extends NagVisStatelessObject {
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		String	 	Image of the shape
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $icon) {
		$this->iconPath = $CORE->MAINCFG->getValue('paths', 'shape');
		$this->iconHtmlPath = $CORE->MAINCFG->getValue('paths', 'htmlshape');
		
		$this->icon = $icon;
		$this->type = 'shape';
		parent::__construct($CORE);
	}
	
	/**
	 * PUBLIC parseJson()
	 *
	 * Parses the object in json format
	 *
	 * @return	String		JSON code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseJson() {
		$this->setIconPath();
		
		return parent::parseJson();
	}
	
	/**
	 * PUBLIC getHoverMenu()
	 *
	 *Gets the hover menu of a shape if it is requested by configuration
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHoverMenu() {
		if(isset($this->hover_url) && $this->hover_url != '') {
			parent::getHoverMenu();
		}
	}
	
	/**
	 * PUBLIC fetchIcon()
	 *
	 * Just a dummy here (Shape won't need an icon)
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchIcon() {
		// Nothing to do here, icon is set in constructor
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE setIconPath()
	 *
	 * Parses the HTML-Code of an icon
	 *
	 * @param	Boolean	$link		Add a link to the icon
	 * @param	Boolean	$hoverMenu	Add a hover menu to the icon
	 * @return	String	String with Html Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function setIconPath() {
		if(preg_match('/^\[(.*)\]$/',$this->icon,$match) > 0) {
			$this->icon = $match[1];
		} else {
			$this->icon = $this->iconHtmlPath.$this->icon;
		}
	}
}
?>
