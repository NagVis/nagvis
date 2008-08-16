<?php
/*****************************************************************************
 *
 * WuiCore.php - The core of NagVis WUI pages
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
class WuiCore extends GlobalCore {
	public $MAINCFG;
	public $LANG;
	
	/**
	 * Class Constructor
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct() {
		// Load the main configuration
		$this->MAINCFG = new WuiMainCfg(CONST_MAINCFG);
		
		// Initialize language
		$this->LANG = new GlobalLanguage($this->MAINCFG);
	} 
}
?>
