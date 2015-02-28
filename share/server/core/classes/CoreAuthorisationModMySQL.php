<?php
/*******************************************************************************
 *
 * CoreAuthorisationModMySQL.php - Authorsiation module based on MySQL
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
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreAuthorisationModMySQL extends CoreAuthorisationModule {
    public $rolesConfigurable = true;
    private $DB = null;

    public function __construct() {
        $this->DB = new CoreMySQLHandler();

        // Open the MySQL database
        if(!$this->DB->open(cfg('auth_mysql', 'dbhost'),
                                                cfg('auth_mysql', 'dbport'),
                                                cfg('auth_mysql', 'dbname'),
                                                cfg('auth_mysql', 'dbuser'),
                                                cfg('auth_mysql', 'dbpass'))) {
            throw new NagVisException(l('Unable to open auth database'));
        } else {
            // Create initial db scheme if needed
            if(!$this->DB->tableExist('users')) {
                $this->DB->createInitialDb();
            } else {
                // Maybe an update is needed
                $this->DB->updateDb();
            }
        }
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
        $RES = $this->DB->query(
            'SELECT users2roles.userId AS userId, users.name AS name '.
            'FROM users2roles LEFT JOIN users ON users2roles.userId=users.userId WHERE roldeId='.$this->DB->escape($roleId));
        $users = array();
        while($data = $this->DB->fetchAssoc($RES)) {
            $users[] = $data['name'];
        }

        return $users;
    }

    public function deleteRole($roleId) {
        // Delete user
        $this->DB->query('DELETE FROM roles WHERE roleId='.$this->DB->escape($roleId));

        // Delete role permissions
        $this->DB->query('DELETE FROM roles2perms WHERE roleId='.$this->DB->escape($roleId));

        // Check result
        return !$this->checkRoleExists($roleId);
    }

    public function deleteUser($userId) {
        // Delete user
        $this->DB->query('DELETE FROM users WHERE userId='.$this->DB->escape($userId));

        // Delete user roles
        $this->DB->query('DELETE FROM users2roles WHERE userId='.$this->DB->escape($userId));

        // Check result
        return $this->checkUserExistsById($userId) <= 0;
    }

    public function updateUserRoles($userId, $roles) {
        // First delete all role perms
        $this->DB->query('DELETE FROM users2roles WHERE userId='.$this->DB->escape($userId));

        // insert new user roles
        foreach($roles AS $roleId) {
            $this->DB->query('INSERT INTO users2roles (userId, roleId) VALUES ('.$this->DB->escape($userId).', '.$this->DB->escape($roleId).')');
        }

        return true;
    }

    public function getUserRoles($userId) {
        $aRoles = Array();

        // Get all the roles of the user
      $RES = $this->DB->query('SELECT users2roles.roleId AS roleId, roles.name AS name FROM users2roles LEFT JOIN roles ON users2roles.roleId=roles.roleId WHERE userId='.$this->DB->escape($userId));
      while($data = $this->DB->fetchAssoc($RES)) {
      	$aRoles[] = $data;
      }

      return $aRoles;
    }

    public function getAllRoles() {
        $aRoles = Array();

        // Get all the roles of the user
      $RES = $this->DB->query('SELECT roleId, name FROM roles ORDER BY name');
      while($data = $this->DB->fetchAssoc($RES)) {
      	$aRoles[] = $data;
      }

      return $aRoles;
    }

    public function getRoleId($sRole) {
        $ret = $this->DB->fetchAssoc($this->DB->query('SELECT roleId FROM roles WHERE name='.$this->DB->escape($sRole)));

        return intval($ret['roleId']);
    }

    public function getAllPerms() {
        $aPerms = Array();

        // Get all the roles of the user
      $RES = $this->DB->query('SELECT `permId`, `mod`, `act`, `obj` FROM perms ORDER BY `mod`, `act`, `obj`');
      while($data = $this->DB->fetchAssoc($RES)) {
      	$aPerms[] = $data;
      }

      return $aPerms;
    }

    public function getRolePerms($roleId) {
        $aRoles = Array();

        // Get all the roles of the user
      $RES = $this->DB->query('SELECT permId FROM roles2perms WHERE roleId='.$this->DB->escape($roleId));
      while($data = $this->DB->fetchAssoc($RES)) {
      	$aRoles[$data['permId']] = true;
      }

      return $aRoles;
    }

    public function updateRolePerms($roleId, $perms) {
        // First delete all role perms
        $this->DB->query('DELETE FROM roles2perms WHERE roleId='.$this->DB->escape($roleId));

        // insert new role perms
        foreach($perms AS $permId => $val) {
            if($val === true) {
                $this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES ('.$this->DB->escape($roleId).', '.$this->DB->escape($permId).')');
            }
        }

        return true;
    }

    public function checkRoleExists($name) {
        return $this->DB->count('SELECT name FROM roles WHERE name='.$this->DB->escape($name)) > 0;
    }

    public function createRole($name) {
        $this->DB->query('INSERT INTO roles (name) VALUES ('.$this->DB->escape($name).')');

        // Check result
        return $this->checkRoleExists($name);
    }

    public function parsePermissions($sUsername = null) {
        global $AUTH;
        $aPerms = Array();

        if($sUsername === null)
            $sUsername = $AUTH->getUser();

        // Only handle known users
        $userId = $this->getUserId($sUsername);
        if($userId > 0) {
          // Get all the roles of the user
          $RES = $this->DB->query('SELECT perms.mod AS `mod`, perms.act AS `act`, perms.obj AS `obj` '.
                                  'FROM users2roles '.
                                  'INNER JOIN roles2perms ON roles2perms.roleId = users2roles.roleId '.
                                  'INNER JOIN perms ON perms.permId = roles2perms.permId '.
                                  'WHERE users2roles.userId = '.$this->DB->escape($userId));

            while($data = $this->DB->fetchAssoc($RES)) {
                if(!isset($aPerms[$data['mod']])) {
                    $aPerms[$data['mod']] = Array();
                }

                if(!isset($aPerms[$data['mod']][$data['act']])) {
                    $aPerms[$data['mod']][$data['act']] = Array();
                }

                if(!isset($aPerms[$data['mod']][$data['act']][$data['obj']])) {
                    $aPerms[$data['mod']][$data['act']][$data['obj']] = Array();
                }
            }
        }

        return $aPerms;
    }

    private function checkUserExistsById($id) {
        return $this->DB->count('SELECT userId FROM users WHERE userId='.$this->DB->escape($id));
    }

    public function getUserId($sUsername) {
        $ret = $this->DB->fetchAssoc($this->DB->query('SELECT userId FROM users WHERE name='.$this->DB->escape($sUsername)));

        return intval($ret['userId']);
    }
}
?>
