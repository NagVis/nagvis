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
	private $AUTHENTICATION;
	
	private $summarizePerms = Array(
		'MainCfg' => Array(
			'doEdit' => 'edit'
		),
		'Map' => Array(
			'getMapProperties' => 'view',
			'getMapObjects' => 'view',
			'getObjectStates' => 'view',
			'doAdd' => 'add',
			'doEdit' => 'edit',
			'doRename' => 'edit',
			'doDelete' => 'edit',
			'modifyObject' => 'edit',
			'createObject' => 'edit',
			'deleteObject' => 'edit',
		),
		'Overview' => Array(
			'getOverviewRotations' => 'view',
			'getOverviewProperties' => 'view',
			'getOverviewMaps' => 'view',
			'getOverviewAutomaps' => 'view',
		),
		'AutoMap' => Array(
			'getAutomapProperties' => 'view',
			'getAutomapObjects' => 'view',
			'parseAutomap' => 'view',
			'getObjectStates' => 'view',
			'doAdd' => 'add',
			'doEdit' => 'edit',
			'doRename' => 'edit',
			'doDelete' => 'edit',
			'modifyObject' => 'edit',
			'createObject' => 'edit',
			'deleteObject' => 'edit',
		),
		'ChangePassword' => Array(
			'view' => 'change',
		),
		'UserMgmt' => Array(
			'view' => 'manage',
			'getUserRoles' => 'manage',
			'getAllRoles' => 'manage',
			'doAdd' => 'manage',
			'doEdit' => 'manage',
			'doDelete' => 'manage',
		),
		'RoleMgmt' => Array(
			'view' => 'manage',
			'getRolePerms' => 'manage',
			'doAdd' => 'manage',
			'doEdit' => 'manage',
			'doDelete' => 'manage',
		));
	
	public function __construct(GlobalCore $CORE, CoreAuthHandler $AUTH, $sModule) {
		$this->sModuleName = $sModule;
		
		$this->CORE = $CORE;
		$this->AUTHENTICATION = $AUTH;
		$this->MOD = new $sModule($this->CORE, $AUTH);
	}
	
	public function createPermission($mod, $name) {
		return $this->MOD->createPermission($mod, $name);
	}
	
	public function getAuthentication() {
		return $this->AUTHENTICATION;
	}
	
	public function getModule() {
		return $this->sModuleName;
	}
	
	public function deleteRole($roleId) {
		// FIXME: First check if this is supported
		
		return $this->MOD->deleteRole($roleId);
	}
	
	public function deleteUser($userId) {
		// FIXME: First check if this is supported
		
		return $this->MOD->deleteUser($userId);
	}
	
	public function updateUserRoles($userId, $roles) {
		// FIXME: First check if this is supported
		
		return $this->MOD->updateUserRoles($userId, $roles);
	}
	
	public function getUserRoles($userId) {
		// FIXME: First check if this is supported
		
		return $this->MOD->getUserRoles($userId);
	}
	
	public function getAllRoles() {
		// FIXME: First check if this is supported
		
		return $this->MOD->getAllRoles();
	}

	private function sortPerms($a, $b) {
		return strcmp($a['mod'].$a['obj'].$a['act'], $b['mod'].$b['obj'].$b['act']);
	}
	
	public function getAllVisiblePerms() {
		$aReturn = Array();
		// FIXME: First check if this is supported
		
		$aPerms = $this->MOD->getAllPerms();
		
		// Filter perms to only display the visible ones
		foreach($aPerms AS $perm) {
			if(!isset($this->summarizePerms[$perm['mod']]) || (isset($this->summarizePerms[$perm['mod']]) && !isset($this->summarizePerms[$perm['mod']][$perm['act']]))) {
				$aReturn[] = $perm;
			}
		}

		usort($aReturn, Array($this, 'sortPerms'));
		
		return $aReturn;
	}
	
	public function checkRoleExists($name) {
		// FIXME: First check if this is supported
		
		return $this->MOD->checkRoleExists($name);
	}
	
	public function createRole($name) {
		// FIXME: First check if this is supported
		
		return $this->MOD->createRole($name);
	}
	
	public function getRolePerms($roleId) {
		// FIXME: First check if this is supported
		
		return $this->MOD->getRolePerms($roleId);
	}
	
	public function getUserId($sName) {
		// FIXME: First check if this is supported
		return $this->MOD->getUserId($sName);
	}
	
	public function getRoleId($sName) {
		// FIXME: First check if this is supported
		return $this->MOD->getRoleId($sName);
	}
	
	public function updateRolePerms($roleId, $perms) {
		// FIXME: First check if this is supported
		
		// Get all permissions
		$aPerms = $this->MOD->getAllPerms();
		
		// Resolve summarized perms
		foreach($perms AS $key => $value) {
			$aPerm = Array();
			
			// Get matching permission
			foreach($aPerms AS $perm) {
				if($perm['permId'] == $key) {
					$aPerm = $perm;
					break;
				}
			}
			
			$mod = $aPerm['mod'];
			$act = $aPerm['act'];
			
			// Check if this mod+act summarizes something
			if(isset($this->summarizePerms[$mod])) {
				foreach($this->summarizePerms[$mod] AS $summarizedAct => $summarizingAct) {
					if($summarizingAct === $act) {
						// Get the id of the summaried action
						foreach($aPerms AS $perm) {
							if($mod == $perm['mod'] && $summarizedAct == $perm['act']) {
								$summarizedActId = $perm['permId'];
								break;
							}
						}
			
						// Add the summarized action to the permissions array
						$perms[$summarizedActId] = $value;
					}
				}
			}
		}
		
		return $this->MOD->updateRolePerms($roleId, $perms);
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
