<?php
/*******************************************************************************
 *
 * CoreAuthorisationModMultisite.php - Authorsiation module based on the
 *                                     permissions granted in Check_MK multisite
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

class CoreAuthorisationModMultisite extends CoreAuthorisationModule {
    public $rolesConfigurable = false;
    private $file;
    private $permissions;

    public function __construct() {
        $this->file = cfg('global', 'authorisation_multisite_file');

        if ($this->file == '') {
            throw new NagVisException(l('No auth file configured. Please specify the option authorisation_multisite_file in main configuration'));
        }

        if (!file_exists($this->file)) {
            throw new NagVisException(l('Unable to open auth file ([FILE]).',
                ['FILE' => $this->file]));
        }

        $this->readFile();
    }

    private function getPermissions($username) {
        # Add implicit permissions. These are basic permissions
        # which are needed for most users.
        $perms =  [
            ['Overview',  'view',               '*'],
            ['General',   'getContextTemplate', '*'],
            ['General',   'getHoverTemplate',   '*'],
            ['User',      'setOption',          '*'],
            ['Multisite', 'getMaps',            '*'],
        ];

        # Gather NagVis related permissions
        $nagvis_permissions = [];
        global $mk_roles;
        foreach ($mk_roles as $role_id => $permissions) {
            foreach ($permissions as $perm_id) {
                if (strpos($perm_id, 'nagvis.') === 0) {
                    $key = substr($perm_id, 7);
                    if (!isset($nagvis_permissions[$key])) {
                        $nagvis_permissions[$key] = null;
                    }
                }
            }
        }

        # Loop the multisite NagVis related permissions and add them
        foreach ($nagvis_permissions as $p => $_unused) {
            if (may($username, 'nagvis.' . $p)) {
                $parts = explode('_', $p);
                if (count($parts) == 3) {
                    // Add native multisite permissions
                    $perms[] = $parts;
                } else {
                    // Special permissions with two parts are controlling the permissions
                    // on the maps the user is explicitly permitted for by its contactgroup
                    // memberships
                    foreach (permitted_maps($username) as $map_name) {
                        $perms[] = array_merge($parts, [$map_name]);
                    }
                }
            }
        }

        return $perms;
    }

    private function readFile() {
        require_once($this->file);
        $this->permissions = [];
        foreach (all_users() as $username => $user) {
            $this->permissions[$username] = [
                'permissions' => $this->getPermissions($username),
                'language'    => $user['language'],
            ];
        }
    }

    public function getUserRoles($userId) {
        return [];
    }

    public function getAllRoles() {
        return [];
    }

    public function getRoleId($sRole) {
        return false;
    }

    public function getAllPerms() {
        return [];
    }

    public function getRolePerms($roleId) {
        return [];
    }

    public function checkRoleExists($name) {
        return false;
    }

    public function parsePermissions($sUsername = null) {
        global $AUTH;
        if ($sUsername === null) {
            $username = $AUTH->getUser();
        } else {
            $username = $sUsername;
        }

        if (
            !isset($this->permissions[$username])
            || !isset($this->permissions[$username]['permissions'])
        ) {
            return [];
        }

        # Array ( [0] => Overview [1] => view [2] => * )
        $perms = [];
        foreach ($this->permissions[$username]['permissions'] as $value) {
            // Module entry
            if (!isset($perms[$value[0]])) {
                $perms[$value[0]] = [];
            }

            if (!isset($perms[$value[0]][$value[1]])) {
                $perms[$value[0]][$value[1]] = [];
            }

            if (!isset($perms[$value[0]][$value[1]][$value[2]])) {
                $perms[$value[0]][$value[1]][$value[2]] = [];
            }
        }

        return $perms;
    }

    public function getUserId($username) {
        return $username;
    }

    /**
     * This authorization backend does not implement any writeable code.
     * It is simply read-only.
     */

    public function renameMapPermissions($old_name, $new_name) {
        return false;
    }

    public function deletePermission($mod, $name) {
        return false;
    }

    public function createPermission($mod, $name) {
        return false;
    }

    public function roleUsedBy($roleId) {
        return false;
    }

    public function deleteRole($roleId) {
        return false;
    }

    public function deleteUser($userId) {
        return false;
    }

    public function updateUserRoles($userId, $roles) {
        return false;
    }

    public function updateRolePerms($roleId, $perms) {
        return false;
    }

    public function createRole($name) {
        return false;
    }
}

