<?php
/*******************************************************************************
 *
 * CoreMySQLHandler.php - Class to handle MySQL databases
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
class CoreMySQLHandler {
    private $DB = null;
    private $file = null;

    public function __construct() {}

    // First check if the php installation supports MySQL and then try to connect
    public function open($host, $port, $db, $user, $pw) {
        if($this->checkMySQLSupport())
            if($this->connectDB($host, $port, $db, $user, $pw))
                return true;
        return false;
    }

    /**
     * PRIVATE Method connectDB
     *
     * Connects to DB
     *
     * @return	Boolean
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function connectDB($host, $port, $db, $user, $pw) {
        // don't want to see mysql errors from connecting - only want our error messages
        $oldLevel = error_reporting(0);

        $this->DB = mysql_connect($host.':'.$port, $user, $pw);

        if(!$this->DB) {
            throw new NagVisException(l('errorConnectingMySQL',
                                       Array('BACKENDID' => 'MySQLHandler',
                                             'MYSQLERR'  => mysql_error())));
        }

        $returnCode = mysql_select_db($db, $this->DB);

        // set the old level of reporting back
        error_reporting($oldLevel);

        if(!$returnCode){
            throw new NagVisException(l('errorSelectingDb',
                         Array('BACKENDID' => 'MySQLHandler',
                               'MYSQLERR'  => mysql_error($this->DB))));
        } else {
            return true;
        }
    }

    public function tableExist($table) {
        return mysql_num_rows($this->query('SHOW TABLES LIKE \''.$table.'\'')) > 0;
    }

    public function query($query) {
        $HANDLE = mysql_query($query, $this->DB) or die(mysql_error());
        return $HANDLE;
    }

    public function count($query) {
      return mysql_num_rows($this->query($query));
    }

    public function fetchAssoc($RES) {
        return mysql_fetch_assoc($RES);
    }

    public function close() {
        $this->DB = null;
    }

    public function escape($s) {
        return "'".mysql_real_escape_string($s)."'";
    }

    private function checkMySQLSupport($printErr = 1) {
        if(!extension_loaded('mysql')) {
            if($printErr === 1) {
                throw new NagVisException(l('Your PHP installation does not support mysql. Please check if you installed the PHP module.'));
            }
            return false;
        } else {
            return true;
        }
    }

    public function deletePermissions($mod, $name) {
        // Only create when not existing
        if($this->count('SELECT `mod` FROM perms WHERE `mod`='.$this->escape($mod).' AND `act`=\'view\' AND obj='.$this->escape($name)) > 0) {
            if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: delete permissions for '.$mod.' '.$name);
            $this->query('DELETE FROM perms WHERE `mod`='.$this->escape($mod).' AND obj='.$this->escape($name).'');
            $this->query('DELETE FROM roles2perms WHERE permId=(SELECT permId FROM perms WHERE `mod`='.$this->escape($mod).' AND obj='.$this->escape($name).')');
        } else {
            if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: won\'t delete '.$mod.' permissions '.$name);
        }
    }

    public function createMapPermissions($name) {
        // Only create when not existing
        if($this->count('SELECT `mod` FROM perms WHERE `mod`=\'Map\' AND `act`=\'view\' AND obj='.$this->escape($name)) <= 0) {
            if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: create permissions for map '.$name);
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'view\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'edit\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'delete\', '.$this->escape($name).')');
        } else {
            if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: won\'t create permissions for map '.$name);
        }

        return true;
    }

    public function createRotationPermissions($name) {
        // Only create when not existing
        if($this->count('SELECT `mod` FROM perms WHERE `mod`=\'Rotation\' AND `act`=\'view\' AND obj='.$this->escape($name)) <= 0) {
            if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: create permissions for rotation '.$name);
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Rotation\', \'view\', '.$this->escape($name).')');
        } else {
            if(DEBUG&&DEBUGLEVEL&2) debug('MySQLHandler: won\'t create permissions for rotation '.$name);
        }

        return true;
    }

    private function addRolePerm($roleId, $mod, $act, $obj) {
        // Only create when not existing
        if($this->count('SELECT `roleId` FROM roles2perms WHERE `roleId`='.$roleId.' AND `permId`=(SELECT permId FROM perms WHERE `mod`=\''.$mod.'\' AND `act`=\''.$act.'\' AND obj=\''.$obj.'\')') <= 0) {
            $this->query('INSERT INTO roles2perms (roleId, permId) VALUES ('.$roleId.', (SELECT permId FROM perms WHERE `mod`=\''.$mod.'\' AND `act`=\''.$act.'\' AND obj=\''.$obj.'\'))');
        }
    }

    public function createPerm($mod, $act, $obj) {
        $this->query('INSERT IGNORE INTO perms (`mod`, `act`, `obj`) VALUES (\''.$mod.'\', \''.$act.'\', \''.$obj.'\')');
    }

    public function updateDb() {
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
	// Create permissions for Action/peform/*
        $this->createPerm('Action', 'perform', '*');
        
        // Assign the new permission to the managers, users
        $RES = $this->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\'');
        while($data = $this->fetchAssoc($RES))
            $this->addRolePerm($data['roleId'], 'Action', 'perform', '*');

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1080500)
            $this->updateDbVersion();
    }

    private function updateDb1070023() {
	// Create permissions for Action/peform/*
        $this->createPerm('Action', 'perform', '*');
        
        // Assign the new permission to the managers, users
        $RES = $this->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\'');
        while($data = $this->fetchAssoc($RES))
            $this->addRolePerm($data['roleId'], 'Action', 'perform', '*');

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1070023)
            $this->updateDbVersion();
    }

    private function updateDb1060500() {
	// Create permissions for Url/view/*
        $this->createPerm('Url', 'view', '*');
        
        // Assign the new permission to the managers, users, guests
        $RES = $this->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\' or name=\'Guests\'');
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

        $data = $this->fetchAssoc($this->query('SELECT roleId FROM roles WHERE name=\'Guests\''));
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
        $this->createPerm('User', 'setOption', '*');

        // Assign the new permission to the managers, users, guests
        $RES = $this->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\' or name=\'Guests\'');
        while($data = $this->fetchAssoc($RES))
            $this->addRolePerm($data['roleId'], 'User', 'setOption', '*');

        // Only apply the new version when this is the real release or newer
        // (While development the version string remains on the old value)
        if(GlobalCore::getInstance()->versionToTag(CONST_VERSION) >= 1060022)
            $this->updateDbVersion();
    }


    private function updateDb1050400() {
        // Create permissions for the multisite webservice
        $this->createPerm('Multisite', 'getMaps', '*');

        // Assign the new permission to the managers
        $RES = $this->query('SELECT roleId FROM roles WHERE name=\'Managers\' or \'Users (read-only)\' or name=\'Guests\'');
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
        $this->createPerm('ManageBackgrounds', 'manage', '*');
        $this->createPerm('ManageShapes',      'manage', '*');
        $this->createPerm('Map',               'manage', '*');

        // Assign the new permission to the managers
        $RES = $this->query('SELECT roleId FROM roles WHERE name=\'Managers\'');
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

    private function getDbVersion() {
        $data = $this->fetchAssoc($this->query('SELECT version FROM version'));
        return $data['version'];
    }

    private function updateDbVersion() {
        $this->query('UPDATE version SET version=\'' . CONST_VERSION . '\'');
    }

    public function createInitialDb() {
        $this->query('CREATE TABLE users (userId INTEGER AUTO_INCREMENT, name VARCHAR(100), password VARCHAR(40), PRIMARY KEY(userId), UNIQUE(name))');
        $this->query('CREATE TABLE roles (roleId INTEGER AUTO_INCREMENT, name VARCHAR(100), PRIMARY KEY(roleId), UNIQUE(name))');
        $this->query('CREATE TABLE perms (`permId` INTEGER AUTO_INCREMENT, `mod` VARCHAR(100), `act` VARCHAR(100), `obj` VARCHAR(100), PRIMARY KEY(`permId`), UNIQUE(`mod`, `act`, `obj`))');
        $this->query('CREATE TABLE users2roles (userId INTEGER, roleId INTEGER, PRIMARY KEY(userId, roleId))');
        $this->query('CREATE TABLE roles2perms (roleId INTEGER, permId INTEGER, PRIMARY KEY(roleId, permId))');

        $this->query('CREATE TABLE version (version VARCHAR(100), PRIMARY KEY(version))');
        $this->query('INSERT INTO version (version) VALUES (\''.CONST_VERSION.'\')');

        $this->query('INSERT INTO users (userId, name, password) VALUES (1, \'admin\', \'868103841a2244768b2dbead5dbea2b533940e20\')');
        $this->query('INSERT INTO users (userId, name, password) VALUES (2, \'guest\', \'7f09c620da83db16ef9b69abfb8edd6b849d2d2b\')');
        $this->query('INSERT INTO roles (roleId, name) VALUES (1, \'Administrators\')');
        $this->query('INSERT INTO roles (roleId, name) VALUES (2, \'Users (read-only)\')');
        $this->query('INSERT INTO roles (roleId, name) VALUES (3, \'Guests\')');
        $this->query('INSERT INTO roles (roleId, name) VALUES (4, \'Managers\')');

        // Access controll: Full access to everything
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'*\', \'*\', \'*\')');

        // Access controll: Overview module levels
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Overview\', \'view\', \'*\')');

        // Access controll: Access to all General actions
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'General\', \'*\', \'*\')');

	// Create permissions for Action/peform/*
        $this->createPerm('Action', 'perform', '*');

        // Access controll: Map module levels for demo maps
        foreach(GlobalCore::getInstance()->demoMaps AS $map) {
            $this->createMapPermissions($map);
        }

        // Access controll: Rotation module levels for rotation "demo"
        $this->createRotationPermissions('demo');

        // Access controll: Change own password
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ChangePassword\', \'change\', \'*\')');

        // Access controll: View maps via multisite
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Multisite\', \'getMaps\', \'*\')');

        // Access controll: Search objects on maps
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Search\', \'view\', \'*\')');

        // Access controll: Authentication: Logout
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Auth\', \'logout\', \'*\')');

        // Access controll: Summary permissions for viewing/editing/deleting all maps
        $this->createMapPermissions('*');

        // Access controll: Rotation module levels for viewing all rotations
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Rotation\', \'view\', \'*\')');

        // Access controll: Manage users
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'manage\', \'*\')');

        // Access controll: Manage roles
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'manage\', \'*\')');

        // Access control: WUI Management pages
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageBackgrounds\', \'manage\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageShapes\', \'manage\', \'*\')');

        // Access controll: Edit/Delete maps
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'manage\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'add\', \'*\')');

        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'MainCfg\', \'edit\', \'*\')');

        /*
         * Administrators handling
         */

        $data = $this->fetchAssoc($this->query('SELECT roleId FROM roles WHERE name=\'Administrators\''));

        // Role assignment: admin => Administrators
        $this->query('INSERT INTO users2roles (userId, roleId) VALUES (1, '.$data['roleId'].')');

        // Access assignment: Administrators => * * *
        $this->addRolePerm($data['roleId'], '*', '*', '*');

        /*
         * Managers handling
         */

        $data = $this->fetchAssoc($this->query('SELECT roleId FROM roles WHERE name=\'Managers\''));

        // Permit all actions in General module
        $this->addRolePerm($data['roleId'], 'General', '*', '*');
        
        // Managers are allowed to perform actions
        $this->addRolePerm($data['roleId'], 'Action', 'perform', '*');

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

        $data = $this->fetchAssoc($this->query('SELECT roleId FROM roles WHERE name=\'Users (read-only)\''));

        // Permit all actions in General module
        $this->addRolePerm($data['roleId'], 'General', '*', '*');
        
        // Users are allowed to perform actions
        $this->addRolePerm($data['roleId'], 'Action', 'perform', '*');

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

        $data = $this->fetchAssoc($this->query('SELECT roleId FROM roles WHERE name=\'Guests\''));

        // Role assignment: guest => Guests
        $this->query('INSERT INTO users2roles (userId, roleId) VALUES (2, '.$data['roleId'].')');

        // Permit all actions in General module
        $this->addRolePerm($data['roleId'], 'General', '*', '*');

        // Access assignment: Guests => Allowed to view the overview
        $this->addRolePerm($data['roleId'], 'Overview', 'view', '*');

        // Access assignment: Guests => Allowed to view the demo maps
        foreach(GlobalCore::getInstance()->demoMaps AS $map) {
            $this->addRolePerm($data['roleId'], 'Map', 'view', $map);
        }

        // Access assignment: Guests => Allowed to view the demo rotation
        $this->addRolePerm($data['roleId'], 'Rotation', 'view', 'demo');

        // Access assignment: Guests => Allowed to change their passwords
        $this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');

        // Access assignment: Guests => Allowed to view their maps via multisite
        $this->addRolePerm($data['roleId'], 'Multisite', 'getMaps', '*');

        // Access assignment: Guests => Allowed to search objects
        $this->addRolePerm($data['roleId'], 'Search', 'view', '*');

        // Access assignment: Guests => Allowed to logout
        $this->addRolePerm($data['roleId'], 'Auth', 'logout', '*');
    }
}

/*
 * FIXME: Cleaned up permissions - should be removed from perms and roles2perms
 *        -> same for SQLite db
 *
 			$this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'getMapProperties\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'getMapObjects\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'getObjectStates\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'doEdit\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'doDelete\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'doRename\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'modifyObject\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'createObject\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'deleteObject\', '.$this->escape($name).')');
            $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'addModify\', '.$this->escape($name).')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageBackgrounds\', \'view\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageBackgrounds\', \'doCreate\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageBackgrounds\', \'doUpload\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageBackgrounds\', \'doDelete\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageShapes\', \'view\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageShapes\', \'doUpload\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageShapes\', \'doDelete\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Overview\', \'getOverviewRotations\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Overview\', \'getOverviewProperties\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Overview\', \'getOverviewMaps\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ChangePassword\', \'view\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'view\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'getUserRoles\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'getAllRoles\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'doAdd\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'doEdit\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'UserMgmt\', \'doDelete\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'view\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'getRolePerms\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'doAdd\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'doEdit\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'RoleMgmt\', \'doDelete\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageBackgrounds\', \'view\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageBackgrounds\', \'doCreate\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageBackgrounds\', \'doUpload\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageBackgrounds\', \'doDelete\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageShapes\', \'view\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageShapes\', \'doUpload\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'ManageShapes\', \'doDelete\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'Map\', \'doAdd\', \'*\')');
        $this->query('INSERT INTO perms (`mod`, `act`, obj) VALUES (\'MainCfg\', \'doEdit\', \'*\')');
*/
?>
