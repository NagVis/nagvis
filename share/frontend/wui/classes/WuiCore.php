<?php
/*****************************************************************************
 *
 * WuiCore.php - The core of NagVis WUI pages
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
	protected static $MAINCFG = null;
	private static $instance = null;
	
	/**
	 * Class Constructor
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	private function __construct() {}
	
	/**
	 * Getter function to initialize MAINCFG
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getMainCfg() {
		if(parent::$MAINCFG === null) {
			// Initialize main configuration when not set yet
			parent::$MAINCFG = new WuiMainCfg(CONST_MAINCFG);
			parent::$MAINCFG->init();
			
			// Set WuiCore MAINCFG too
			self::$MAINCFG = parent::$MAINCFG;
		}
		
		return parent::$MAINCFG;
	}
	
	/**
	 * Static method for getting the instance
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
}
?>
