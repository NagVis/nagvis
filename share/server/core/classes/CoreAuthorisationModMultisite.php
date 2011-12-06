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
        $this->file = cfg('paths', 'cfg').'auth.multisite';

        if(!file_exists($this->file))
            throw new NagVisException(l('Unable to open auth file ([FILE]).',
                                                Array('FILE' => $this->file)));

        $this->readFile();
    }

    private function readFile() {
        $s = file_get_contents($this->file);
        $obj = json_decode(utf8_encode($s), true);
        if($obj === null)
            throw new NagVisException(l('Unable to parse data from auth file ([FILE]).',
                                                          Array('FILE' => $this->file)));

        $this->permissions = $obj;
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
