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
    /** @var bool */
    public $rolesConfigurable = true;

    /** @var CorePDOHandler|null */
    private $DB = null;

    /**
     * @return array
     */
    abstract public function getConfig();

    public function __construct()
    {
        $this->DB = new CorePDOHandler();

        // Open the database
        $config = $this->getConfig();
        if (!$this->DB->open($config['driver'], $config['params'], $config['username'], $config['password'])) {
            throw new NagVisException(l('Unable to open auth database ([DB]): [MSG]',
                [
                    'DB' => $this->DB->getDSN(),
                    'MSG' => json_encode($this->DB->error())
                ]));
        } elseif (!$this->DB->tableExist('users')) {
            // Create initial db scheme if needed
            $this->DB->createInitialDb();
        } else {
            // Maybe an update is needed
            $this->DB->updateDb();
        }
    }

    /**
     * @param string $old_name
     * @param string $new_name
     * @return void
     */
    public function renameMapPermissions($old_name, $new_name)
    {
        $this->DB->query('-perm-rename-map', ['old_name' => $old_name, 'new_name' => $new_name]);
    }

    /**
     * @param string $mod
     * @param string $name
     * @return bool
     */
    public function deletePermission($mod, $name)
    {
        if ($name === '') {
            return false;
        }

        switch ($mod) {
            case 'Map':
            case 'Rotation':
                $this->DB->deletePermissions($mod, $name);
                return true;
            default:
                return false;
        }
    }

    /**
     * @param string $mod
     * @param string $name
     * @return bool
     */
    public function createPermission($mod, $name)
    {
        if ($name === '') {
            return false;
        }

        switch ($mod) {
            case 'Map':
                return $this->DB->createMapPermissions($name);
            case 'Rotation':
                return $this->DB->createRotationPermissions($name);
            default:
                return false;
        }
    }

    /**
     * @param int $roleId
     * @return array
     */
    public function roleUsedBy($roleId)
    {
        $RES = $this->DB->query('-role-used-by', ['roleId' => $roleId]);
        $users = [];
        while ($data = $RES->fetch()) {
            $users[] = $data['name'];
        }

        return $users;
    }

    /**
     * @param int $roleId
     * @return bool
     */
    public function deleteRole($roleId)
    {
        // Delete role
        $this->DB->query('-role-delete-by-id', ['roleId' => $roleId]);

        // Delete role permissions
        $this->DB->query('-role-delete-perm-by-id', ['roleId' => $roleId]);

        // Check result
        if (!$this->checkRoleExists($roleId)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function deleteUser($userId)
    {
        // Delete user
        $this->DB->query('-user-delete', ['userId' => $userId]);

        // Delete user roles
        $this->DB->query('-user-delete-roles', ['userId' => $userId]);

        // Check result
        if ($this->checkUserExistsById($userId) <= 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $userId
     * @param array $roles
     * @return true
     */
    public function updateUserRoles($userId, $roles)
    {
        // First delete all role perms
        $this->DB->query('-role-delete-by-user-id', ['userId' => $userId]);

        // insert new user roles
        foreach ($roles as $roleId) {
            if ($roleId === '') {
                continue;
            }
            $this->DB->query('-role-add-user-by-id', ['userId' => $userId, 'roleId' => $roleId]);
        }

        return true;
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getUserRoles($userId)
    {
        $aRoles = [];

        // Get all the roles of the user
        $RES = $this->DB->query('-role-get-by-user', ['id' => $userId]);
        while ($data = $RES->fetch()) {
                $aRoles[] = $data;
        }

        return $aRoles;
    }

    /**
     * @return array
     */
    public function getAllRoles()
    {
        $aRoles = [];

        // Get all the roles of the user
        $RES = $this->DB->query('-role-get-all');
        while ($data = $RES->fetch()) {
                $aRoles[] = $data;
        }

        return $aRoles;
    }

    /**
     * @param string $sRole
     * @return int
     */
    public function getRoleId($sRole)
    {
        $ret = $this->DB->query('-role-get-by-name', ['name' => $sRole])->fetch();

        return intval($ret['roleId']);
    }

    /**
     * @return array
     */
    public function getAllPerms()
    {
        $aPerms = [];

        // Get all the roles of the user
        $RES = $this->DB->query('-perm-get-all');
        while ($data = $RES->fetch()) {
                  $aPerms[] = $data;
        }

        return $aPerms;
    }

    /**
     * @param int $roleId
     * @return array
     */
    public function getRolePerms($roleId)
    {
        $aRoles = [];

        // Get all the roles of the user
        $RES = $this->DB->query('-role-get-perm-by-id', ['roleId' => $roleId]);
        while ($data = $RES->fetch()) {
                  $aRoles[$data['permId']] = true;
        }

        return $aRoles;
    }

    /**
     * @param int $roleId
     * @param array $perms
     * @return true
     */
    public function updateRolePerms($roleId, $perms)
    {
        // First delete all role perms
        $this->DB->query('-role-delete-perm-by-id', ['roleId' => $roleId]);

        // insert new role perms
        foreach ($perms as $permId => $val) {
            if ($val === true) {
                $this->DB->query('-role-add-perm', ['roleId' => $roleId, 'permId' => $permId]);
            }
        }

        return true;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function checkRoleExists($name)
    {
        if ($this->DB->count('-role-count-by-name', ['name' => $name]) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function createRole($name)
    {
        $this->DB->query('-role-add', ['name' => $name]);

        // Check result
        if ($this->checkRoleExists($name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string|null $sUsername
     * @return array
     */
    public function parsePermissions($sUsername = null)
    {
        global $AUTH;
        $aPerms = [];

        if ($sUsername === null) {
            $sUsername = $AUTH->getUser();
        }

        // Only handle known users
        $userId = $this->getUserId($sUsername);
        if ($userId > 0) {
            // Get all the roles of the user
            $RES = $this->DB->query('-perm-get-by-user', ['id' => $userId]);

            while ($data = $RES->fetch()) {
                if (!isset($aPerms[$data['mod']])) {
                    $aPerms[$data['mod']] = [];
                }

                if (!isset($aPerms[$data['mod']][$data['act']])) {
                    $aPerms[$data['mod']][$data['act']] = [];
                }

                if (!isset($aPerms[$data['mod']][$data['act']][$data['obj']])) {
                    $aPerms[$data['mod']][$data['act']][$data['obj']] = [];
                }
            }
        }

        return $aPerms;
    }

    /**
     * @param int $id
     * @return int
     */
    private function checkUserExistsById($id)
    {
        return $this->DB->count('-user-count-by-id', ['userId' => $id]);
    }

    /**
     * @param string $sUsername
     * @return int
     */
    public function getUserId($sUsername)
    {
        $ret = $this->DB->query('-user-get-by-name', ['name' => $sUsername])->fetch();

        return intval($ret['userId']);
    }
}
