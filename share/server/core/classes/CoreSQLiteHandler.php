<?php
/*******************************************************************************
 *
 * CoreSQLiteHandler.php - Class to handle SQLite databases
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
class CoreSQLiteHandler {
    private $DB = null;
    private $file = null;

    public function __construct() {}

    public function open($file) {
        // First check if the php installation supports sqlite
        if($this->checkSQLiteSupport()) {
            try {
                $this->DB = new PDO("sqlite:".$file);
                $this->file = $file;
            } catch(PDOException $e) {
    		echo $e->getMessage();
    		return false;
    	}

            if($this->DB === false || $this->DB === null) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function isWriteable() {
        return GlobalCore::getInstance()->checkWriteable(dirname($this->file))
                     && GlobalCore::getInstance()->checkWriteable($this->file);
    }

    public function tableExist($table) {
      $RET = $this->query('SELECT COUNT(*) AS num FROM sqlite_master WHERE type=\'table\' AND name='.$this->escape($table))->fetch(PDO::FETCH_ASSOC);
      return intval($RET['num']) > 0;
    }

    public function query($query) {
        return $this->DB->query($query);
    }

    public function exec($query) {
        return $this->DB->exec($query);
    }

    public function count($query) {
        $RET = $this->query($query)->fetch(PDO::FETCH_ASSOC);
      return intval($RET['num']) > 0;
    }

    public function fetchAssoc($RES) {
        return $RES->fetch(PDO::FETCH_ASSOC);
    }

    public function close() {
        $this->DB = null;
    }

    public function escape($s) {
        return $this->DB->quote($s);
    }

    private function checkSQLiteSupport($printErr = 1) {
        if(!class_exists('PDO')) {
            if($printErr === 1) {
                throw new NagVisException(l('Your PHP installation does not support PDO. Please check if you installed the PHP module.'));
            }
            return false;
        } elseif(!in_array('sqlite', PDO::getAvailableDrivers())) {
            if($printErr === 1) {
                throw new NagVisException(l('Your PHP installation does not support PDO SQLite (3.x). Please check if you installed the PHP module.'));
            }
            return false;
        } else {
            return true;
        }
    }

    public function deletePermissions($mod, $name) {
        // Only create when not existing
        if($this->count('SELECT COUNT(*) AS num FROM perms WHERE mod='.$this->escape($mod).' AND act=\'view\' AND obj='.$this->escape($name)) > 0) {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: delete permissions for '.$mod.' '.$name);
            $this->DB->query('DELETE FROM perms WHERE mod='.$this->escape($mod).' AND obj='.$this->escape($name).'');
            $this->DB->query('DELETE FROM roles2perms WHERE permId=(SELECT permId FROM perms WHERE mod='.$this->escape($mod).' AND obj='.$this->escape($name).')');
        } else {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: won\'t delete '.$mod.' permissions '.$name);
        }
    }

    public function createMapPermissions($name) {
        // Only create when not existing
        if($this->count('SELECT COUNT(*) AS num FROM perms WHERE mod=\'Map\' AND act=\'view\' AND obj='.$this->escape($name)) <= 0) {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: create permissions for map '.$name);
            $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'view\', '.$this->escape($name).')');
            $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'edit\', '.$this->escape($name).')');
            $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'delete\', '.$this->escape($name).')');
        } else {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: won\'t create permissions for map '.$name);
        }

        return true;
    }

    public function createRotationPermissions($name) {
        // Only create when not existing
        if($this->count('SELECT COUNT(*) AS num FROM perms WHERE mod=\'Rotation\' AND act=\'view\' AND obj='.$this->escape($name)) <= 0) {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: create permissions for rotation '.$name);
            $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Rotation\', \'view\', '.$this->escape($name).')');
        } else {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: won\'t create permissions for rotation '.$name);
        }

        return true;
    }

    private function addRolePerm($roleId, $mod, $act, $obj) {
        $this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES ('.$roleId.', (SELECT permId FROM perms WHERE mod=\''.$mod.'\' AND act=\''.$act.'\' AND obj=\''.$obj.'\'))');
    }

    public function updateDb() {
        // Perform pre 1.5b4 updates
        if(!$this->tableExist('version'))
            $this->updateDb1050024();

        // Read the current version from db
        $dbVersion = GlobalCore::getInstance()->versionToTag($this->getDbVersion());

        // Now perform the update for pre 1.5.3
        if($dbVersion < 1050300)
            $this->updateDb1050300();

        // Now perform the update for pre 1.5.4
        if($dbVersion < 1050400)
            $this->updateDb1050400();

        // Now perform the update for pre 1.6b2
        if($dbVersion < 1060022)
            $this->updateDb1060022();

        // Now perform the update for pre 1.6.1
        if($dbVersion < 1060100)
            $this->updateDb1060100();

        // Now perform the update for pre 1.6.5
        if($dbVersion < 1060500)
            $this->updateDb1060500();

        // Now perform the update for pre 1.7b3
        if($dbVersion < 1070023)
            $this->updateDb1070023();

        // Now perform the update for pre 1.8.5. Need to add the Action/perform
        // permission again since it was not added during db creation till this
        // release
        if($dbVersion < 1080500)
            $this->updateDb1080500();
    }

    private function updateDb1080500() {
	// Create permissions for Action/perform/*
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Action\', \'perform\', \'*\')');

        // Assign the new permission to the managers, users
        $RES = $this->DB->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\'');
        while($data = $this->fetchAssoc($RES))
            $this->addRolePerm($data['roleId'], 'Action', 'perform', '*');

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1080500)
            $this->updateDbVersion();
    }

    private function updateDb1070023() {
	// Create permissions for Action/perform/*
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Action\', \'perform\', \'*\')');

        // Assign the new permission to the managers, users
        $RES = $this->DB->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\'');
        while($data = $this->fetchAssoc($RES))
            $this->addRolePerm($data['roleId'], 'Action', 'perform', '*');

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1070023)
            $this->updateDbVersion();
    }

    private function updateDb1060500() {
	// Create permissions for Url/view/*
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Url\', \'view\', \'*\')');
        
        // Assign the new permission to the managers, users, guests
        $RES = $this->DB->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\' or name=\'Guests\'');
        while($data = $this->fetchAssoc($RES))
            $this->addRolePerm($data['roleId'], 'Url', 'view', '*');

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1060500)
            $this->updateDbVersion();
    }

    private function updateDb1060100() {
        // Access controll: Map module levels for the demo maps
        foreach(GlobalCore::getInstance()->demoMaps AS $map) {
            $this->createMapPermissions($map);
        }

        $data = $this->fetchAssoc($this->DB->query('SELECT roleId FROM roles WHERE name=\'Guests\''));
        // Access assignment: Guests => Allowed to view the demo maps
        foreach(GlobalCore::getInstance()->demoMaps AS $map) {
            $this->addRolePerm($data['roleId'], 'Map', 'view', $map);
        }

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1060100)
            $this->updateDbVersion();
    }

    private function updateDb1060022() {
	// Create permissions for User/setOption
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'User\', \'setOption\', \'*\')');

        // Assign the new permission to the managers, users, guests
        $RES = $this->DB->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\' or name=\'Guests\'');
        while($data = $this->fetchAssoc($RES))
            $this->addRolePerm($data['roleId'], 'User', 'setOption', '*');

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1060022)
            $this->updateDbVersion();

    }

    private function updateDb1050400() {
        // Create permissions for the multisite webservice
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Multisite\', \'getMaps\', \'*\')');

        // Assign the new permission to the managers, users, guests
        $RES = $this->DB->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\' or name=\'Guests\'');
        while($data = $this->fetchAssoc($RES)) {
            $this->addRolePerm($data['roleId'], 'Multisite', 'getMaps', '*');
        }

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1050400)
            $this->updateDbVersion();
    }

    private function updateDb1050300() {
        // Create permissions for WUI management pages
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'ManageBackgrounds\', \'manage\', \'*\')');
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'ManageShapes\', \'manage\', \'*\')');
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'manage\', \'*\')');

        // Assign the new permission to the managers
        $RES = $this->DB->query('SELECT roleId FROM roles WHERE name=\'Managers\'');
        while($data = $this->fetchAssoc($RES)) {
            $this->addRolePerm($data['roleId'], 'ManageBackgrounds', 'manage', '*');
            $this->addRolePerm($data['roleId'], 'ManageShapes', 'manage', '*');
            $this->addRolePerm($data['roleId'], 'Map', 'manage', '*');
        }

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1050300)
            $this->updateDbVersion();
    }

    private function updateDb1050024() {
        if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: Performing update to 1.5b4 scheme');

        $this->createVersionTable();

        // Add addModify permission
        $RES = $this->DB->query('SELECT obj FROM perms WHERE mod=\'Map\' AND act=\'view\'');
        while($data = $this->fetchAssoc($RES)) {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: Adding new addModify perms for map '.$data['obj']);
            $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'addModify\', '.$this->escape($data['obj']).')');
        }

        // Assign the addModify permission to the managers
        $RES = $this->DB->query('SELECT roleId FROM roles WHERE name=\'Managers\'');
        while($data = $this->fetchAssoc($RES)) {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: Assigning addModify perms to Managers role');
            $this->addRolePerm($data['roleId'], 'Map', 'addModify', '*');
        }
    }

    private function getDbVersion() {
        $data = $this->fetchAssoc($this->DB->query('SELECT version FROM version'));
        return $data['version'];
    }

    private function updateDbVersion() {
        $this->DB->query('UPDATE version SET version=\'' . CONST_VERSION . '\'');
    }

    private function createVersionTable() {
        $this->DB->query('CREATE TABLE version (version VARCHAR(100), PRIMARY KEY(version))');
        $this->DB->query('INSERT INTO version (version) VALUES (\''.CONST_VERSION.'\')');
    }

    public function createInitialDb() {
        $this->DB->query('CREATE TABLE users (userId INTEGER, name VARCHAR(100), password VARCHAR(40), PRIMARY KEY(userId), UNIQUE(name))');
        $this->DB->query('CREATE TABLE roles (roleId INTEGER, name VARCHAR(100), PRIMARY KEY(roleId), UNIQUE(name))');
        $this->DB->query('CREATE TABLE perms (permId INTEGER, mod VARCHAR(100), act VARCHAR(100), obj VARCHAR(100), PRIMARY KEY(permId), UNIQUE(mod,act,obj))');
        $this->DB->query('CREATE TABLE users2roles (userId INTEGER, roleId INTEGER, PRIMARY KEY(userId, roleId))');
        $this->DB->query('CREATE TABLE roles2perms (roleId INTEGER, permId INTEGER, PRIMARY KEY(roleId, permId))');

        $this->createVersionTable();

        // If running in OMD create the 'omdadmin' user instead of 'admin'
        if(GlobalCore::getInstance()->omdSite() !== null) {
            $this->DB->query('INSERT INTO users (userId, name, password) VALUES (1, \'omdadmin\', \'051e0bbcfb79ea2a3ce5c487cc111051aac51ae8\')');
        } else {
            $this->DB->query('INSERT INTO users (userId, name, password) VALUES (1, \'admin\', \'868103841a2244768b2dbead5dbea2b533940e20\')');
        }

        $this->DB->query('INSERT INTO users (userId, name, password) VALUES (2, \'guest\', \'a4e74a1d28ec981c945310d87f8d7b535d794cd2\')');
        $this->DB->query('INSERT INTO roles (roleId, name) VALUES (1, \'Administrators\')');
        $this->DB->query('INSERT INTO roles (roleId, name) VALUES (2, \'Users (read-only)\')');
        $this->DB->query('INSERT INTO roles (roleId, name) VALUES (3, \'Guests\')');
        $this->DB->query('INSERT INTO roles (roleId, name) VALUES (4, \'Managers\')');

        // Access controll: Full access to everything
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'*\', \'*\', \'*\')');

        // Access controll: Overview module levels
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Overview\', \'view\', \'*\')');

        // Access controll: Access to all General actions
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'General\', \'*\', \'*\')');

	// Create permissions for Action/peform/*
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Action\', \'perform\', \'*\')');

        // Access controll: Map module levels for the demo maps
        foreach(GlobalCore::getInstance()->demoMaps AS $map) {
            $this->createMapPermissions($map);
        }

        // Access controll: Rotation module levels for rotation "demo"
        $this->createRotationPermissions('demo');

        // Access controll: Change user options
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'User\', \'setOption\', \'*\')');

        // Access controll: Change own password
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'ChangePassword\', \'change\', \'*\')');

        // Access controll: View maps via multisite
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Multisite\', \'getMaps\', \'*\')');

        // Access controll: Search objects on maps
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Search\', \'view\', \'*\')');

        // Access controll: Authentication: Logout
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Auth\', \'logout\', \'*\')');

        // Access controll: Summary permissions for viewing/editing/deleting all maps
        $this->createMapPermissions('*');

        // Access controll: Rotation module levels for viewing all rotations
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Rotation\', \'view\', \'*\')');

        // Access controll: Manage users
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'UserMgmt\', \'manage\', \'*\')');

        // Access controll: Manage roles
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'RoleMgmt\', \'manage\', \'*\')');

        // Access control: WUI Management pages
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'ManageBackgrounds\', \'manage\', \'*\')');
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'ManageShapes\', \'manage\', \'*\')');

        // Access controll: Edit/Delete maps
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'manage\', \'*\')');
        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'add\', \'*\')');

        $this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'MainCfg\', \'edit\', \'*\')');

        /*
         * Administrators handling
         */

        $data = $this->fetchAssoc($this->DB->query('SELECT roleId FROM roles WHERE name=\'Administrators\''));

        // Role assignment: admin => Administrators
        $this->DB->query('INSERT INTO users2roles (userId, roleId) VALUES (1, '.$data['roleId'].')');

        // Access assignment: Administrators => * * *
        $this->addRolePerm($data['roleId'], '*', '*', '*');

        /*
         * Managers handling
         */

        $data = $this->fetchAssoc($this->DB->query('SELECT roleId FROM roles WHERE name=\'Managers\''));

        // Permit all actions in General module
        $this->addRolePerm($data['roleId'], 'General', '*', '*');

        // Managers are allowed to perform actions
        $this->addRolePerm($data['roleId'], 'Action', 'perform', '*');

        // Access assignment: Managers => Allowed to update user options
        $this->addRolePerm($data['roleId'], 'User', 'setOption', '*');

        // Access assignment: Managers => Allowed to edit/delete all maps
        $this->addRolePerm($data['roleId'], 'Map', 'manage', '*');
        $this->addRolePerm($data['roleId'], 'Map', 'delete', '*');
        $this->addRolePerm($data['roleId'], 'Map', 'edit', '*');

        // Access assignment: Managers => Allowed to create maps
        $this->addRolePerm($data['roleId'], 'Map', 'add', '*');

        // Access assignment: Managers => Allowed to manage backgrounds and shapes
        $this->addRolePerm($data['roleId'], 'ManageBackgrounds', 'manage', '*');
        $this->addRolePerm($data['roleId'], 'ManageShapes', 'manage', '*');

        // Access assignment: Managers => Allowed to view the overview
        $this->addRolePerm($data['roleId'], 'Overview', 'view', '*');

        // Access assignment: Managers => Allowed to view all maps
        $this->addRolePerm($data['roleId'], 'Map', 'view', '*');

        // Access assignment: Managers => Allowed to view all rotations
        $this->addRolePerm($data['roleId'], 'Rotation', 'view', '*');

        // Access assignment: Managers => Allowed to change their passwords
        $this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');

        // Access assignment: Managers => Allowed to view their maps via multisite
        $this->addRolePerm($data['roleId'], 'Multisite', 'getMaps', '*');

        // Access assignment: Managers => Allowed to search objects
        $this->addRolePerm($data['roleId'], 'Search', 'view', '*');

        // Access assignment: Managers => Allowed to logout
        $this->addRolePerm($data['roleId'], 'Auth', 'logout', '*');

        /*
         * Users handling
         */

        $data = $this->fetchAssoc($this->DB->query('SELECT roleId FROM roles WHERE name=\'Users (read-only)\''));

        // Users are allowed to perform actions
        $this->addRolePerm($data['roleId'], 'Action', 'perform', '*');

        // Permit all actions in General module
        $this->addRolePerm($data['roleId'], 'General', '*', '*');

        // Access assignment: Users => Allowed to update user options
        $this->addRolePerm($data['roleId'], 'User', 'setOption', '*');

        // Access assignment: Users => Allowed to view the overview
        $this->addRolePerm($data['roleId'], 'Overview', 'view', '*');

        // Access assignment: Users => Allowed to view all maps
        $this->addRolePerm($data['roleId'], 'Map', 'view', '*');

        // Access assignment: Users => Allowed to view all rotations
        $this->addRolePerm($data['roleId'], 'Rotation', 'view', '*');

        // Access assignment: Users => Allowed to change their passwords
        $this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');

        // Access assignment: Users => Allowed to view their maps via multisite
        $this->addRolePerm($data['roleId'], 'Multisite', 'getMaps', '*');

        // Access assignment: Users => Allowed to search objects
        $this->addRolePerm($data['roleId'], 'Search', 'view', '*');

        // Access assignment: Users => Allowed to logout
        $this->addRolePerm($data['roleId'], 'Auth', 'logout', '*');

        /*
         * Guest handling
         */

        $data = $this->fetchAssoc($this->DB->query('SELECT roleId FROM roles WHERE name=\'Guests\''));

        // Role assignment: guest => Guests
        $this->DB->query('INSERT INTO users2roles (userId, roleId) VALUES (2, '.$data['roleId'].')');

        // Permit all actions in General module
        $this->addRolePerm($data['roleId'], 'General', '*', '*');

        // Access assignment: Guests => Allowed to update user options
        $this->addRolePerm($data['roleId'], 'User', 'setOption', '*');

        // Access assignment: Guests => Allowed to view the overview
        $this->addRolePerm($data['roleId'], 'Overview', 'view', '*');

        // Access assignment: Guests => Allowed to view their maps via multisite
        $this->addRolePerm($data['roleId'], 'Multisite', 'getMaps', '*');

        // Access assignment: Guests => Allowed to view the demo maps
        foreach(GlobalCore::getInstance()->demoMaps AS $map) {
            $this->addRolePerm($data['roleId'], 'Map', 'view', $map);
        }

        // Access assignment: Guests => Allowed to view the demo rotation
        $this->addRolePerm($data['roleId'], 'Rotation', 'view', 'demo');

        // Access assignment: Guests => Allowed to change their passwords
        $this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');

        // Access assignment: Guests => Allowed to search objects
        $this->addRolePerm($data['roleId'], 'Search', 'view', '*');

        // Access assignment: Guests => Allowed to logout
        $this->addRolePerm($data['roleId'], 'Auth', 'logout', '*');
    }
}
?>
