<?php
/*******************************************************************************
 *
 * CoreAuthorisationHandler.php - Authorsiation handler
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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

    private $MOD;

    // FIXME: This is not really used anymore. It is only needed to hide the "hidden"
    // permissions from the user. Those hidden permissions are not used anymore. So
    // cleanup the auth DB and then remove this list.
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
            'addModify' => 'edit',
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
        'ManageShapes' => Array(
            'view'                 => 'manage',
            'doUpload'             => 'manage',
            'doDelete'             => 'manage',
        ),
        'ManageBackgrounds' => Array(
            'view'                 => 'manage',
            'doUpload'             => 'manage',
            'doCreate'             => 'manage',
            'doDelete'             => 'manage',
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

    public function __construct() {
        $this->sModuleName = cfg('global', 'authorisationmodule');
        $this->MOD = new $this->sModuleName();
    }

    public function renameMapPermissions($old_name, $new_name) {
        return $this->MOD->renameMapPermissions($old_name, $new_name);
    }

    public function createPermission($mod, $name) {
        return $this->MOD->createPermission($mod, $name);
    }

    public function deletePermission($mod, $name) {
        return $this->MOD->deletePermission($mod, $name);
    }

    public function getModule() {
        return $this->sModuleName;
    }

    public function rolesConfigurable() {
        return $this->MOD->rolesConfigurable;
    }

    public function deleteRole($roleId) {
        // FIXME: First check if this is supported

        return $this->MOD->deleteRole($roleId);
    }

    public function roleUsedBy($roleId) {
        return $this->MOD->roleUsedBy($roleId);
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

    public function cleanupPermissions() {
        global $CORE;

        // loop all map related permissions and check whether or not the map
        // is still available
        foreach ($this->getAllVisiblePerms() AS $perm) {
            if ($perm['mod'] == 'Map' && $perm['obj'] != '*') {
                if(count($CORE->getAvailableMaps('/^'.$perm['obj'].'$/')) <= 0) {
                    $this->deletePermission('Map', $perm['obj']);
                }
            }
        }
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
        return $this->MOD->updateRolePerms($roleId, $perms);
    }

    public function parsePermissions($sUsername = null) {
        $this->aPermissions = $this->MOD->parsePermissions($sUsername);
        return $this->aPermissions;
    }

    public function isPermitted($sModule, $sAction, $sObj = null) {
        // Module access?
        $access = Array();
        if(isset($this->aPermissions[$sModule]))
            $access[$sModule] = Array();
        if(isset($this->aPermissions[AUTH_PERMISSION_WILDCARD]))
            $access[AUTH_PERMISSION_WILDCARD] = Array();

        if(count($access) > 0) {
            // Action access?
            foreach($access AS $mod => $acts) {
                if(isset($this->aPermissions[$mod][$sAction]))
                    $access[$mod][$sAction] = Array();
                if(isset($this->aPermissions[$mod][AUTH_PERMISSION_WILDCARD]))
                    $access[$mod][AUTH_PERMISSION_WILDCARD] = Array();
            }

            if(count($access[$mod]) > 0) {
                // Don't check object permissions
                if($sObj === null)
                    return true;

                // Object access?
                foreach($access AS $mod => $acts) {
                    foreach($acts AS $act => $objs) {
                        if(isset($this->aPermissions[$mod][$act][$sObj]))
                            return true;
                        elseif(isset($this->aPermissions[$mod][$act][AUTH_PERMISSION_WILDCARD]))
                            return true;
                        else
                            if(DEBUG&&DEBUGLEVEL&2)
                                debug('Object access denied (Mod: '.$sModule.' Act: '.$sAction.' Object: '.$sObj);
                    }
                }
            } else
                if(DEBUG&&DEBUGLEVEL&2)
                    debug('Action access denied (Mod: '.$sModule.' Act: '.$sAction.' Object: '.$sObj);
        } else
            if(DEBUG&&DEBUGLEVEL&2)
                debug('Module access denied (Mod: '.$sModule.' Act: '.$sAction.' Object: '.$sObj);

        return false;
    }
}
?>
