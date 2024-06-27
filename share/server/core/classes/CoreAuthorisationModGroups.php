<?php
/*******************************************************************************
 *
 * CoreAuthorisationModGroups.php - Authorsiation module based on the
 *                                     permissions granted in Check_MK Groups
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

class CoreAuthorisationModGroups extends CoreAuthorisationModule
{
    /** @var GlobalFileCache */
    private $CACHE;

    /** @var bool */
    public $rolesConfigurable = false;

    /** @var string */
    private $file;

    /** @var string[] */
    private $backends;

    /** @var array|null */
    private $group_perms;

    /** @var array */
    private $user_groups;

    /** @var array */
    private $perms;

    /**
     * @throws NagVisException
     */
    public function __construct()
    {
        $this->file     = cfg('global', 'authorisation_group_perms_file');

        if ($this->file == '') {
            throw new NagVisException(
                l(
                    'No group permission file specified. Please configure one via the option authorisation_group_perms_file in global section of the main configuration.'
                )
            );
        }

        if (!file_exists($this->file)) {
            throw new NagVisException(l('Unable to open auth file ([FILE]).',
                ['FILE' => $this->file]));
        }

        $this->backends = cfg('global', 'authorisation_group_backends');
        if (!$this->backends) {
            $this->backends = cfg('defaults', 'backend');
        }

        $cacheFile = cfg('paths', 'var') . 'group-perms-' . CONST_VERSION . '-cache';
        $this->CACHE = new GlobalFileCache($this->file, $cacheFile);

        $this->readFile();
        $this->fetchUserGroups();
        $this->calcPermissions();
    }

    /**
     * @return void
     * @throws NagVisException
     */
    private function readFile()
    {
        $json = iso8859_1_to_utf8(file_get_contents($this->file));
        $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#", '', $json);
        $this->group_perms = json_decode($json, true);
        if ($this->group_perms === null) {
            throw new NagVisException(l('The permissions file [FILE] could not be parsed.',
                ['FILE' => $this->file]));
        }
    }

    /**
     * @return void
     * @throws NagVisException
     */
    private function fetchUserGroups()
    {
        global $_BACKEND;

        // FIXME: Create a cache and use it!

        $this->user_groups = [];
        foreach ($this->backends as $backend_id) {
            $_BACKEND->checkBackendFeature($backend_id, 'getContactsWithGroups');
            try {
                $contacts = $_BACKEND->getBackend($backend_id)->getContactsWithGroups();
            } catch (BackendConnectionProblem $e) {
                continue; // skip this backend silently
            }

            foreach ($contacts as $contact => $groups) {
                if (!isset($this->user_groups[$contact])) {
                    $this->user_groups[$contact] = [];
                }

                foreach ($groups as $group) {
                    if (!isset($this->user_groups[$contact][$group])) {
                        $this->user_groups[$contact][$group] = 1;
                    }
                }
            }
        }

        // FIXME: Write $this->user_groups to cache
    }

    /**
     * @return void
     */
    private function calcPermissions()
    {
        foreach (array_keys($this->user_groups) as $username) {
            $this->perms[$username] = $this->calcUserPermissions($username);
        }
    }

    /**
     * @param string $username
     * @return array
     */
    private function calcUserPermissions($username)
    {
        # Add implicit permissions. These are basic permissions
        # which are needed for most users.
        $perms =  [
            ['Overview',  'view',               '*'],
            ['General',   'getContextTemplate', '*'],
            ['General',   'getHoverTemplate',   '*'],
            ['User',      'setOption',          '*'],
            ['Multisite', 'getMaps',            '*'],
            ['Auth',      'logout',             '*'],
        ];

        if (!isset($this->user_groups[$username])) {
            return [];
        }

        // get groups of user and summarize the permissions
        foreach (array_keys($this->user_groups[$username]) as $groupname) {
            if (!isset($this->group_perms[$groupname])) {
                continue;
            }
            foreach ($this->group_perms[$groupname] as $key => $value) {
                if ($key == 'admin' && $value == 1) {
                    // Grant full access for admins
                    $perms[] = ['*', '*', '*'];
                } else {
                    // Handle detailed map show/edit permissions for "normal users"
                    foreach ($value as $mapname) {
                        $perms[] = ['Map', $key, $mapname];
                        if ($key == 'edit') {
                            $perms[] = ['Map', 'del', $mapname];
                        }
                    }
                }
            }
        }

        return $perms;
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getUserRoles($userId)
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAllRoles()
    {
        return [];
    }

    /**
     * @param string $sRole
     * @return false
     */
    public function getRoleId($sRole)
    {
        return false;
    }

    /**
     * @return array
     */
    public function getAllPerms()
    {
        return [];
    }

    /**
     * @param int $roleId
     * @return array
     */
    public function getRolePerms($roleId)
    {
        return [];
    }

    /**
     * @param string $name
     * @return false
     */
    public function checkRoleExists($name)
    {
        return false;
    }

    /**
     * @param string|null $sUsername
     * @return array
     */
    public function parsePermissions($sUsername = null)
    {
        global $AUTH;
        if ($sUsername === null) {
            $username = $AUTH->getUser();
        } else {
            $username = $sUsername;
        }

        if (!isset($this->perms[$username])) {
            return [];
        }

        # Array ( [0] => Overview [1] => view [2] => * )
        $perms = [];
        foreach ($this->perms[$username] as $value) {
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

    /**
     * @param string $username
     * @return string
     */
    public function getUserId($username)
    {
        return $username;
    }

    /**
     * This authorization backend does not implement any writeable code.
     * It is simply read-only.
     */

    /**
     * @param string $old_name
     * @param string $new_name
     * @return false
     */
    public function renameMapPermissions($old_name, $new_name)
    {
        return false;
    }

    /**
     * @param string $mod
     * @param string $name
     * @return false
     */
    public function deletePermission($mod, $name)
    {
        return false;
    }

    /**
     * @param string $mod
     * @param string $name
     * @return false
     */
    public function createPermission($mod, $name)
    {
        return false;
    }

    /**
     * @param int $roleId
     * @return false
     */
    public function roleUsedBy($roleId)
    {
        return false;
    }

    /**
     * @param int $roleId
     * @return false
     */
    public function deleteRole($roleId)
    {
        return false;
    }

    /**
     * @param int $userId
     * @return false
     */
    public function deleteUser($userId)
    {
        return false;
    }

    /**
     * @param int $userId
     * @param array $roles
     * @return false
     */
    public function updateUserRoles($userId, $roles)
    {
        return false;
    }

    /**
     * @param int $roleId
     * @param array $perms
     * @return false
     */
    public function updateRolePerms($roleId, $perms)
    {
        return false;
    }

    /**
     * @param string $name
     * @return false
     */
    public function createRole($name)
    {
        return false;
    }
}
