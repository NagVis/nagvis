<?php
/*******************************************************************************
 *
 * CoreAuthorisationHandler.php - Authorsiation handler
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
 * This class handles all authorisation tasks and is the glue between the
 * application and the different authorisation modules.
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreAuthorisationHandler {
	private $sModuleName = '';
	private $aPermissions = Array();
	
	private $CORE;
	private $MOD;
	
	public function __construct(GlobalCore $CORE, CoreAuthHandler $AUTH, $sModule) {
		$this->sModuleName = $sModule;
		
		$this->CORE = $CORE;
		$this->MOD = new $sModule($this->CORE, $AUTH);
	}
	
	public function getModule() {
		return $this->sModuleName;
	}
	
	public function parsePermissions() {
		$this->aPermissions = $this->MOD->parsePermissions();
		
		return $this->aPermissions;
	}
	
	public function isPermitted($sModule, $sAction, $sObj = null) {
		$bAutorized = false;
		
		// Module access?
		$modAccess = false;
		if(isset($this->aPermissions[$sModule])) {
			$modAccess = $sModule;
		} elseif(isset($this->aPermissions[AUTH_PERMISSION_WILDCARD])) {
			$modAccess = AUTH_PERMISSION_WILDCARD;
		}
		
		if($modAccess !== false) {
			// Action access?
			$actAccess = false;
			if(isset($this->aPermissions[$modAccess][$sAction])) {
				$actAccess = $sAction;
			} elseif(isset($this->aPermissions[$modAccess][AUTH_PERMISSION_WILDCARD])) {
				$actAccess = AUTH_PERMISSION_WILDCARD;
			}
			
			if($actAccess !== false) {
				// Have to check a particular object?
				if($sObj !== null) {
					// Object access?
					if(isset($this->aPermissions[$modAccess][$actAccess][$sObj])) {
						$bAutorized = true;
					} elseif(isset($this->aPermissions[$modAccess][$actAccess][AUTH_PERMISSION_WILDCARD])) {
						$bAutorized = true;
					} else {
						// FIXME: Logging
						//echo 'object denied';
						$bAutorized = false;
					}
				} else {
					$bAutorized = true;
				}
			} else {
				// FIXME: Logging
				//echo 'action denied';
				$bAutorized = false;
			}
		} else {
			// FIXME: Logging
			//echo 'module denied';
			$bAutorized = false;
		}
		
		// Authorized?
		if($bAutorized === true) {
			return true;
		} else {
			// FIXME: Logging
			return false;
		}
	}
}
?>
