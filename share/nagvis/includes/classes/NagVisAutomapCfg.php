<?php
/*****************************************************************************
 *
 * NagVisAutomapCfg.php - Class for handling the NagVis automap configuration
 * files
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: lars@vertical-visions.de)
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
class NagVisAutomapCfg extends GlobalMapCfg {
	/**
	 * Class Constructor
	 *
	 * @param	GlobalCore      $CORE
	 * @param	String			$name		Name of the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $name) {
		// Fix the automap name (backward compatible to old automap=1)
		if(isset($name) && $name === '1') {
			$name = '__automap';
		}
		
		// Start of the parent constructor
		parent::__construct($CORE, $name);
		
		// Override the default map configuration path with automap path
		$this->setConfigFile($CORE->MAINCFG->getValue('paths', 'automapcfg').$name.'.cfg');
		
		// Re-initialize the cache
		$this->initCache();
	}
}
?>
