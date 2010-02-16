<?php
/*******************************************************************************
 *
 * CoreSQLiteHandler.php - Class to handle SQLite databases
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
	
	public function __construct() {}
	
	public function open($file) {
		// First check if the php installation supports sqlite
		if($this->checkSQLiteSupport()) {
			try {
				$this->DB = new PDO("sqlite:".$file);
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
				new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Your PHP installation does not support PDO. Please check if you installed the PHP module.'));
			}
			return false;
		} elseif(!in_array('sqlite', PDO::getAvailableDrivers())) {
			if($printErr === 1) {
				new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Your PHP installation does not support PDO SQLite (3.x). Please check if you installed the PHP module.'));
			}
			return false;
		} else {
			return true;
		}
	}
	
	public function createMapPermissions($name) {
		// Only create when not existing
		if($this->count('SELECT COUNT(*) AS num FROM perms WHERE mod=\'Map\' AND act=\'view\' AND obj='.$this->escape($name)) <= 0) {
			debug('create permissions for map '.$name);
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'view\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'getMapProperties\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'getMapObjects\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'getObjectStates\', '.$this->escape($name).')');
			
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'edit\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'delete\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'doEdit\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'doDelete\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'doRename\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'modifyObject\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'createObject\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'deleteObject\', '.$this->escape($name).')');
		} else {
			debug('won\'t create permissions for map '.$name);
		}
		
		return true;
	}
	
	public function createAutoMapPermissions($name) {
		// Only create when not existing
		if($this->count('SELECT COUNT(*) AS num FROM perms WHERE mod=\'AutoMap\' AND act=\'view\' AND obj='.$this->escape($name)) <= 0) {
			debug('create permissions for automap '.$name);
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'view\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'getAutomapProperties\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'getAutomapObjects\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'getObjectStates\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'parseAutomap\', '.$this->escape($name).')');
			
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'edit\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'delete\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'doEdit\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'doDelete\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'doRename\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'modifyObject\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'createObject\', '.$this->escape($name).')');
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'deleteObject\', '.$this->escape($name).')');
		} else {
			debug('won\'t create permissions for automap '.$name);
		}
		
		return true;
	}
	
	public function createRotationPermissions($name) {
		// Only create when not existing
		if($this->count('SELECT COUNT(*) AS num FROM perms WHERE mod=\'Rotation\' AND act=\'view\' AND obj='.$this->escape($name)) <= 0) {
			debug('create permissions for rotation '.$name);
			$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Rotation\', \'view\', '.$this->escape($name).')');
		} else {
			debug('won\'t create permissions for rotation '.$name);
		}
		
		return true;
	}

	private function addRolePerm($roleId, $mod, $act, $obj) {
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES ('.$roleId.', (SELECT permId FROM perms WHERE mod=\''.$mod.'\' AND act=\''.$act.'\' AND obj=\''.$obj.'\'))');
	}
	
	public function createInitialDb() {
		$this->DB->query('CREATE TABLE users (userId INTEGER, name VARCHAR(100), password VARCHAR(40), PRIMARY KEY(userId), UNIQUE(name))');
		$this->DB->query('CREATE TABLE roles (roleId INTEGER, name VARCHAR(100), PRIMARY KEY(roleId), UNIQUE(name))');
		$this->DB->query('CREATE TABLE perms (permId INTEGER, mod VARCHAR(100), act VARCHAR(100), obj VARCHAR(100), PRIMARY KEY(permId), UNIQUE(mod,act,obj))');
		$this->DB->query('CREATE TABLE users2roles (userId INTEGER, roleId INTEGER, PRIMARY KEY(userId, roleId))');
		$this->DB->query('CREATE TABLE roles2perms (roleId INTEGER, permId INTEGER, PRIMARY KEY(roleId, permId))');
		
		$this->DB->query('INSERT INTO users (userId, name, password) VALUES (1, \'nagiosadmin\', \'7f09c620da83db16ef9b69abfb8edd6b849d2d2b\')');
		$this->DB->query('INSERT INTO users (userId, name, password) VALUES (2, \'guest\', \'7f09c620da83db16ef9b69abfb8edd6b849d2d2b\')');
		$this->DB->query('INSERT INTO roles (roleId, name) VALUES (1, \'Administrators\')');
		$this->DB->query('INSERT INTO roles (roleId, name) VALUES (2, \'Users (read-only)\')');
		$this->DB->query('INSERT INTO roles (roleId, name) VALUES (3, \'Guests\')');
		$this->DB->query('INSERT INTO roles (roleId, name) VALUES (4, \'Managers\')');
		
		// Access controll: Full access to everything
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'*\', \'*\', \'*\')');
		
		// Access controll: Overview module levels
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Overview\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Overview\', \'getOverviewRotations\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Overview\', \'getOverviewProperties\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Overview\', \'getOverviewMaps\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Overview\', \'getOverviewAutomaps\', \'*\')');
		
		// Access controll: Access to all General actions
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'General\', \'*\', \'*\')');
		
		// Access controll: Map module levels for map "demo"
		$this->createMapPermissions('demo');
		
		// Access controll: Map module levels for map "demo2"
		$this->createMapPermissions('demo2');
		
		// Access controll: Map module levels for map "demo-map"
		$this->createMapPermissions('demo-map');
		
		// Access controll: Map module levels for map "demo-server"
		$this->createMapPermissions('demo-server');
		
		// Access controll: Rotation module levels for rotation "demo"
		$this->createRotationPermissions('demo');
		
		// Access controll: Automap module levels for automap "__automap"
		$this->createAutoMapPermissions('__automap');
		
		// Access controll: Change own password
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'ChangePassword\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'ChangePassword\', \'change\', \'*\')');
	
		// Access controll: Search objects on maps
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Search\', \'view\', \'*\')');
		
		// Access controll: Authentication: Logout
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Auth\', \'logout\', \'*\')');
		
		// Access controll: Summary permissions for viewing/editing/deleting all maps
		$this->createMapPermissions('*');
		
		// Access controll: Summary permissions for viewing/editing/deleting all automaps
		$this->createAutoMapPermissions('*');
		
		// Access controll: Rotation module levels for viewing all rotations
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Rotation\', \'view\', \'*\')');
		
		// Access controll: Manage users
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'UserMgmt\', \'manage\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'UserMgmt\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'UserMgmt\', \'getUserRoles\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'UserMgmt\', \'getAllRoles\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'UserMgmt\', \'doAdd\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'UserMgmt\', \'doEdit\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'UserMgmt\', \'doDelete\', \'*\')');
		
		// Access controll: Manage roles
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'RoleMgmt\', \'manage\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'RoleMgmt\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'RoleMgmt\', \'getRolePerms\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'RoleMgmt\', \'doAdd\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'RoleMgmt\', \'doEdit\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'RoleMgmt\', \'doDelete\', \'*\')');
		
		// Access controll: Edit/Delete maps and automaps
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'add\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'Map\', \'doAdd\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'add\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'AutoMap\', \'doAdd\', \'*\')');
		
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'MainCfg\', \'edit\', \'*\')');
		$this->DB->query('INSERT INTO perms (mod, act, obj) VALUES (\'MainCfg\', \'doEdit\', \'*\')');
		
		/*
		 * Administrators handling
		 */
		
		$data = $this->fetchAssoc($this->DB->query('SELECT roleId FROM roles WHERE name=\'Administrators\''));
		 
		// Role assignment: nagiosadmin => Administrators
		$this->DB->query('INSERT INTO users2roles (userId, roleId) VALUES (1, '.$data['roleId'].')');
		
		// Access assignment: Administrators => * * *
		$this->addRolePerm($data['roleId'], '*', '*', '*');
		
		/*
		 * Managers handling
		 */
		
		$data = $this->fetchAssoc($this->DB->query('SELECT roleId FROM roles WHERE name=\'Managers\''));
		
		// Permit all actions in General module
		$this->addRolePerm($data['roleId'], 'General', '*', '*');
		
		// Access assignment: Managers => Allowed to edit/delete all maps
		$this->addRolePerm($data['roleId'], 'Map', 'delete', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'doDelete', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'edit', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'doEdit', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'doRename', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'modifyObject', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'createObject', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'deleteObject', '*');
		
		// Access assignment: Managers => Allowed to create maps
		$this->addRolePerm($data['roleId'], 'Map', 'add', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'doAdd', '*');
		
		// Access assignment: Managers => Allowed to edit/delete all automaps
		$this->addRolePerm($data['roleId'], 'AutoMap', 'delete', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'doDelete', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'edit', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'doEdit', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'doRename', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'modifyObject', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'createObject', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'deleteObject', '*');
		
		// Access assignment: Managers => Allowed to create automaps
		$this->addRolePerm($data['roleId'], 'AutoMap', 'add', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'doAdd', '*');
		
		// Access assignment: Managers => Allowed to view the overview
		$this->addRolePerm($data['roleId'], 'Overview', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewRotations', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewProperties', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewMaps', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewAutomaps', '*');
		//$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES ('.$data['roleId'].', )');
		
		// Access assignment: Managers => Allowed to view all maps
		$this->addRolePerm($data['roleId'], 'Map', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', '*');
		
		// Access assignment: Managers => Allowed to view all rotations
		$this->addRolePerm($data['roleId'], 'Rotation', 'view', '*');
		
		// Access assignment: Managers => Allowed to view all automaps
		$this->addRolePerm($data['roleId'], 'AutoMap', 'view', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapProperties', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapObjects', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getObjectStates', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'parseAutomap', '*');
		
		// Access assignment: Managers => Allowed to change their passwords
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'view', '*');
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');
		
		// Access assignment: Managers => Allowed to search objects
		$this->addRolePerm($data['roleId'], 'Search', 'view', '*');
		
		// Access assignment: Managers => Allowed to logout
		$this->addRolePerm($data['roleId'], 'Auth', 'logout', '*');
		
		/*
		 * Users handling
		 */
		
		$data = $this->fetchAssoc($this->DB->query('SELECT roleId FROM roles WHERE name=\'Users (read-only)\''));
		
		// Permit all actions in General module
		$this->addRolePerm($data['roleId'], 'General', '*', '*');
		
		// Access assignment: Users => Allowed to view the overview
		$this->addRolePerm($data['roleId'], 'Overview', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewRotations', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewProperties', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewMaps', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewAutomaps', '*');
		
		// Access assignment: Users => Allowed to view all maps
		$this->addRolePerm($data['roleId'], 'Map', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', '*');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', '*');
		
		// Access assignment: Users => Allowed to view all rotations
		$this->addRolePerm($data['roleId'], 'Rotation', 'view', '*');
		
		// Access assignment: Users => Allowed to view all automaps
		$this->addRolePerm($data['roleId'], 'AutoMap', 'view', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getMapProperties', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getMapObjects', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getObjectStates', '*');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'parseAutomap', '*');
		
		// Access assignment: Users => Allowed to change their passwords
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'view', '*');
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');
		
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
		
		// Access assignment: Guests => Allowed to view the overview
		$this->addRolePerm($data['roleId'], 'Overview', 'view', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewRotations', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewProperties', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewMaps', '*');
		$this->addRolePerm($data['roleId'], 'Overview', 'getOverviewAutomaps', '*');
		
		// Access assignment: Guests => Allowed to view the demo, demo2, demo-map and demo-servers map
		$this->addRolePerm($data['roleId'], 'Map', 'view', 'demo');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', 'demo');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', 'demo');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', 'demo');
		$this->addRolePerm($data['roleId'], 'Map', 'view', 'demo2');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', 'demo2');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', 'demo2');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', 'demo2');
		$this->addRolePerm($data['roleId'], 'Map', 'view', 'demo-map');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', 'demo-map');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', 'demo-map');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', 'demo-map');
		$this->addRolePerm($data['roleId'], 'Map', 'view', 'demo-server');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapProperties', 'demo-server');
		$this->addRolePerm($data['roleId'], 'Map', 'getMapObjects', 'demo-server');
		$this->addRolePerm($data['roleId'], 'Map', 'getObjectStates', 'demo-server');
		
		// Access assignment: Guests => Allowed to view the demo rotation
		$this->addRolePerm($data['roleId'], 'Rotation', 'view', 'demo');
		
		// Access assignment: Guests => Allowed to view the __automap automap
		$this->addRolePerm($data['roleId'], 'AutoMap', 'view', '__automap');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapProperties', '__automap');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getAutomapObjects', '__automap');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'getObjectStates', '__automap');
		$this->addRolePerm($data['roleId'], 'AutoMap', 'parseAutomap', '__automap');
		
		// Access assignment: Guests => Allowed to change their passwords
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'view', '*');
		$this->addRolePerm($data['roleId'], 'ChangePassword', 'change', '*');
		
		// Access assignment: Guests => Allowed to search objects
		$this->addRolePerm($data['roleId'], 'Search', 'view', '*');
		
		// Access assignment: Guests => Allowed to logout
		$this->addRolePerm($data['roleId'], 'Auth', 'logout', '*');
	}
}
?>
