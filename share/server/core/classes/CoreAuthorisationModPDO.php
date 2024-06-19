<?php
/*******************************************************************************
 *
 * CoreAuthorisationModPDO.php - Authorsiation module using the PDO abstraction
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

abstract class CoreAuthorisationModPDO extends CoreAuthorisationModule {
    public $rolesConfigurable = true;
    private $DB = null;

    abstract public function getConfig();

    public function __construct() {
        $this->DB = new CorePDOHandler();

        // Open the database
        $config = $this->getConfig();
        if(!$this->DB->open($config['driver'], $config['params'], $config['username'], $config['password'])) {
            throw new NagVisException(l('Unable to open auth database ([DB]): [MSG]',
                [
                    'DB' => $this->DB->getDSN(),
                      'MSG' => json_encode($this->DB->error())
                ]));
        } elseif(!$this->DB->tableExist('users')) {
            // Create initial db scheme if needed
            $this->DB->createInitialDb();
        } else {
            // Maybe an update is needed
            $this->DB->updateDb();
        }
    }

    public function renameMapPermissions($old_name, $new_name) {
        $this->DB->query('-perm-rename-map', ['old_name' => $old_name, 'new_name' => $new_name]);
    }

    public function deletePermission($mod, $name) {
        if($name === '') {
            return false;
        }

        switch($mod) {
            case 'Map':
            case 'Rotation':
                return $this->DB->deletePermissions($mod, $name);
            default:
                return false;
            break;
        }
    }

    public function createPermission($mod, $name) {
        if($name === '') {
            return false;
        }

        switch($mod) {
            case 'Map':
                return $this->DB->createMapPermissions($name);
            case 'Rotation':
                return $this->DB->createRotationPermissions($name);
            default:
                return false;
            break;
        }
    }

    public function roleUsedBy($roleId) {
        $RES = $this->DB->query('-role-used-by', ['roleId' => $roleId]);
        $users = [];
        while($data = $RES->fetch()) {
            $users[] = $data['name'];
        }

        return $users;
    }

    public function deleteRole($roleId) {
        // Delete role
        $this->DB->query('-role-delete-by-id', ['roleId' => $roleId]);

        // Delete role permissions
        $this->DB->query('-role-delete-perm-by-id', ['roleId' => $roleId]);

        // Check result
        if(!$this->checkRoleExists($roleId)) {
            return true;
        } else {
            return false;
        }
    }

    public function deleteUser($userId) {
        // Delete user
        $this->DB->query('-user-delete', ['userId' => $userId]);

        // Delete user roles
        $this->DB->query('-user-delete-roles', ['userId' => $userId]);

        // Check result
        if($this->checkUserExistsById($userId) <= 0) {
            return true;
        } else {
            return false;
        }
    }

    public function updateUserRoles($userId, $roles) {
        // First delete all role perms
        $this->DB->query('-role-delete-by-user-id', ['userId' => $userId]);

        // insert new user roles
        foreach($roles as $roleId) {
            if ($roleId === '') {
                continue;
            }
            $this->DB->query('-role-add-user-by-id', ['userId' => $userId, 'roleId' => $roleId]);
        }

        return true;
    }

    public function getUserRoles($userId) {
        $aRoles = [];

        // Get all the roles of the user
      $RES = $this->DB->query('-role-get-by-user', ['id' => $userId]);
      while($data = $RES->fetch()) {
      	$aRoles[] = $data;
      }

      return $aRoles;
    }

    public function getAllRoles() {
        $aRoles = [];

        // Get all the roles of the user
      $RES = $this->DB->query('-role-get-all');
      while($data = $RES->fetch()) {
      	$aRoles[] = $data;
      }

      return $aRoles;
    }

    public function getRoleId($sRole) {
        $ret = $this->DB->query('-role-get-by-name', ['name' => $sRole])->fetch();

        return intval($ret['roleId']);
    }

    public function getAllPerms() {
        $aPerms = [];

        // Get all the roles of the user
      $RES = $this->DB->query('-perm-get-all');
      while($data = $RES->fetch()) {
      	$aPerms[] = $data;
      }

      return $aPerms;
    }

    public function getRolePerms($roleId) {
        $aRoles = [];

        // Get all the roles of the user
      $RES = $this->DB->query('-role-get-perm-by-id', ['roleId' => $roleId]);
      while($data = $RES->fetch()) {
      	$aRoles[$data['permId']] = true;
      }

      return $aRoles;
    }

    public function updateRolePerms($roleId, $perms) {
        // First delete all role perms
        $this->DB->query('-role-delete-perm-by-id', ['roleId' => $roleId]);

        // insert new role perms
        foreach($perms as $permId => $val) {
            if($val === true) {
                $this->DB->query('-role-add-perm', ['roleId' => $roleId, 'permId' => $permId]);
            }
        }

        return true;
    }

    public function checkRoleExists($name) {
        if($this->DB->count('-role-count-by-name', ['name' => $name]) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function createRole($name) {
        $this->DB->query('-role-add', ['name' => $name]);

        // Check result
        if($this->checkRoleExists($name)) {
            return true;
        } else {
            return false;
        }
    }

    public function parsePermissions($sUsername = null) {
        global $AUTH;
        $aPerms = [];

        if($sUsername === null) {
            $sUsername = $AUTH->getUser();
        }

        // Only handle known users
        $userId = $this->getUserId($sUsername);
        if($userId > 0) {
          // Get all the roles of the user
          $RES = $this->DB->query('-perm-get-by-user', ['id' => $userId]);

            while($data = $RES->fetch()) {
                if(!isset($aPerms[$data['mod']])) {
                    $aPerms[$data['mod']] = [];
                }

                if(!isset($aPerms[$data['mod']][$data['act']])) {
                    $aPerms[$data['mod']][$data['act']] = [];
                }

                if(!isset($aPerms[$data['mod']][$data['act']][$data['obj']])) {
                    $aPerms[$data['mod']][$data['act']][$data['obj']] = [];
                }
            }
        }

        return $aPerms;
    }

    private function checkUserExistsById($id) {
        return $this->DB->count('-user-count-by-id', ['userId' => $id]);
    }

    public function getUserId($sUsername) {
        $ret = $this->DB->query('-user-get-by-name', ['name' => $sUsername])->fetch();

        return intval($ret['userId']);
    }
}

