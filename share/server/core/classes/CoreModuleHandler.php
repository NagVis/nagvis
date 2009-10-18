<?php
/*******************************************************************************
 *
 * CoreModuleHandler.php - Class to handle core modules
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModuleHandler {
	protected $CORE;
	protected $aRegistered;
	protected $sPrefix;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aRegistered = Array();
		$this->sPrefix = 'CoreMod';
	}
	
	public function loadModule($sModule) {
		// Check if module class is registered
		if(isset($this->aRegistered[$this->sPrefix.$sModule]) && $this->aRegistered[$this->sPrefix.$sModule] === 'active') {
			$className = $this->sPrefix.$sModule;
			
			// create instance of module
			$MOD = new $className($this->CORE);
			
			// return instance
			return $MOD;
		} else {
			// Error handling
			new GlobalMessage('ERROR', $this->CORE->LANG->getText('The given module is not registered'));
			return null;
		}
	}
	
	public function regModule($sModule) {
		// Check if module class exists
    if(class_exists($this->sPrefix.$sModule)) {
			// Register the module at the module handler
			$this->aRegistered[$this->sPrefix.$sModule] = 'active';
			
			return true;
		} else {
			// Error handling
			new GlobalMessage('ERROR', $this->CORE->LANG->getText('The module class does not exist'));
			return false;
		}
	}
}

?>
