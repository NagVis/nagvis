<?php
/*******************************************************************************
 *
 * CoreAuthorisationModMultisite.php - Authorsiation module based on the
 *                                     permissions granted in Check_MK multisite
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
    private $file;
    private $permissions;

    public function __construct() {
        $this->file = cfg('global', 'authorisation_multisite_file');

        if($this->file == '')
            throw new NagVisException(l('No auth file configured. Please specify the option authorisation_multisite_file in main configuration'));

        if(!file_exists($this->file))
            throw new NagVisException(l('Unable to open auth file ([FILE]).',
                                                Array('FILE' => $this->file)));

        $this->readFile();
    }

    private function getFolderMapName($folderPath) {
        return str_replace('/', '_', $folderPath);
    }

    private function getPermissions($username) {
        # Add implicit permissions. These are basic permissions
        # which are needed for most users.
        $perms =  array(
            array('Overview',  'view',               '*'),
            array('General',   'getContextTemplate', '*'),
            array('General',   'getHoverTemplate',   '*'),
            array('General',   'getCfgFileAges',     '*'),
            array('User',      'setOption',          '*'),
            array('Multisite', 'getMaps',            '*'),
        );

        $nagvis_permissions = array(
            array('*', '*', '*'),
            array('Map', 'view', '*'),
            array('Map', 'edit', '*'),
            array('Map', 'delete', '*'),
        );

        # Loop the multisite NagVis related permissions and add them
        foreach($nagvis_permissions AS $p) {
            if(may($username, 'nagvis.'.implode('_', $p))) {
                $perms[] = $p;
            }    
        }

        # WATO folder related permissions
        foreach(get_folder_permissions($username) AS $folder_path => $p) {
            if($p['read']) {
                $perms[] = array('Map', 'view', $this->getFolderMapName($folder_path));
            }
            if($p['write']) {
                $perms[] = array('Map', 'edit', $this->getFolderMapName($folder_path));
            }
        }

        return $perms;
    }

    private function readFile() {
        require_once($this->file);
        $this->permissions = array();
        foreach(all_users() AS $username => $user) {
            $this->permissions[$username] = array(
                'permissions' => $this->getPermissions($username),
                'language'    => $user['language'],
            );
        }
    }

    public function getUserRoles($userId) {
        return Array();
    }

    public function getAllRoles() {
        return Array();
    }

    public function getRoleId($sRole) {
        return false;
    }

    public function getAllPerms() {
        return array();
    }

    public function getRolePerms($roleId) {
        return array();
    }

    public function checkRoleExists($name) {
        return false;
    }

    public function parsePermissions() {
        global $AUTH;
        $username = $AUTH->getUser();

        if(!isset($this->permissions[$username])
           || !isset($this->permissions[$username]['permissions']))
            return array();
    
        # Array ( [0] => Overview [1] => view [2] => * )
        $perms = Array();
        foreach($this->permissions[$username]['permissions'] AS $value) {
            // Module entry
            if(!isset($perms[$value[0]]))
                $perms[$value[0]] = array();
            
            if(!isset($perms[$value[0]][$value[1]]))
                $perms[$value[0]][$value[1]] = array();
            
            if(!isset($perms[$value[0]][$value[1]][$value[2]]))
                $perms[$value[0]][$value[1]][$value[2]] = array();
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

    public function deletePermission($mod, $name) {
        return false;
    }

    public function createPermission($mod, $name) {
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
?>
