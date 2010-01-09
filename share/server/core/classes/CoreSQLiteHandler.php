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
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (1, \'*\', \'*\', \'*\')');
		
		// Access controll: Overview module levels
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (2, \'Overview\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (3, \'Overview\', \'getOverviewRotations\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (4, \'Overview\', \'getOverviewProperties\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (5, \'Overview\', \'getOverviewMaps\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (6, \'Overview\', \'getOverviewAutomaps\', \'*\')');
		
		// Access controll: Access to all General actions
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (7, \'General\', \'*\', \'*\')');
		
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
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (29, \'ChangePassword\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (30, \'ChangePassword\', \'change\', \'*\')');
		
		// Access controll: Search objects on maps
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (31, \'Search\', \'view\', \'*\')');
		
		// Access controll: Authentication: Logout
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (32, \'Auth\', \'logout\', \'*\')');
		
		// Access controll: Summary permissions for viewing all maps
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (33, \'Map\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (34, \'Map\', \'getMapProperties\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (35, \'Map\', \'getMapObjects\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (36, \'Map\', \'getObjectStates\', \'*\')');
		
		// Access controll: Summary permissions for viewing all automaps
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (37, \'AutoMap\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (38, \'AutoMap\', \'getAutomapProperties\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (39, \'AutoMap\', \'getAutomapObjects\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (40, \'AutoMap\', \'getObjectStates\', \'*\')');
		
		// Access controll: Rotation module levels for viewing all rotations
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (41, \'Rotation\', \'view\', \'*\')');
		
		// Access controll: Manage users
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (42, \'UserMgmt\', \'manage\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (43, \'UserMgmt\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (44, \'UserMgmt\', \'getUserRoles\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (45, \'UserMgmt\', \'getAllRoles\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (46, \'UserMgmt\', \'doAdd\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (47, \'UserMgmt\', \'doEdit\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (48, \'UserMgmt\', \'doDelete\', \'*\')');
		
		// Access controll: Manage roles
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (49, \'RoleMgmt\', \'manage\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (50, \'RoleMgmt\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (51, \'RoleMgmt\', \'getRolePerms\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (52, \'RoleMgmt\', \'doAdd\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (53, \'RoleMgmt\', \'doEdit\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (54, \'RoleMgmt\', \'doDelete\', \'*\')');
		
		// Access controll: Edit/Delete maps and automaps
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (55, \'Map\', \'edit\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (56, \'Map\', \'delete\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (57, \'AutoMap\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (58, \'AutoMap\', \'delete\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (59, \'Map\', \'doEdit\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (60, \'Map\', \'doDelete\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (61, \'Map\', \'doRename\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (62, \'AutoMap\', \'doEdit\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (63, \'AutoMap\', \'doDelete\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (64, \'AutoMap\', \'doRename\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (65, \'Map\', \'modifyObject\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (66, \'Map\', \'createObject\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (67, \'Map\', \'deleteObject\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (68, \'AutoMap\', \'modifyObject\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (69, \'AutoMap\', \'createObject\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (70, \'AutoMap\', \'deleteObject\', \'*\')');
		
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (71, \'Map\', \'add\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (72, \'Map\', \'doAdd\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (73, \'AutoMap\', \'add\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (74, \'AutoMap\', \'doAdd\', \'*\')');
		
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (75, \'MainCfg\', \'edit\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (76, \'MainCfg\', \'doEdit\', \'*\')');
		
		/*
		 * Administrators handling
		 */
		 
		// Role assignment: nagiosadmin => Administrators
		$this->DB->query('INSERT INTO users2roles (userId, roleId) VALUES (1, 1)');
		
		// Access assignment: Administrators => * * *
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (1, 1)');
		
		/*
		 * Managers handling
		 */
		
		// Access assignment: Managers => Allowed to edit/delete all maps
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 55)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 56)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 59)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 60)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 61)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 65)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 66)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 67)');
		
		// Access assignment: Managers => Allowed to create maps
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 71)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 72)');
		
		// Access assignment: Managers => Allowed to edit/delete all automaps
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 57)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 58)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 62)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 63)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 64)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 68)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 69)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 70)');
		
		// Access assignment: Managers => Allowed to create automaps
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 73)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 74)');
		
		// Access assignment: Managers => Allowed to view the overview
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 2)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 3)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 4)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 5)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 6)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 7)');
		
		// Access assignment: Managers => Allowed to view all maps
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 33)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 34)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 35)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 36)');
		
		// Access assignment: Managers => Allowed to view all rotations
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 41)');
		
		// Access assignment: Managers => Allowed to view all automaps
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 37)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 38)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 39)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 40)');
		
		// Access assignment: Managers => Allowed to change their passwords
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 29)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 30)');
		
		// Access assignment: Managers => Allowed to search objects
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 31)');
		
		// Access assignment: Managers => Allowed to logout
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 32)');
		
		
		/*
		 * Users handling
		 */
		
		// Access assignment: Users => Allowed to view the overview
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 2)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 3)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 4)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 5)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 6)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 7)');
		
		// Access assignment: Users => Allowed to view all maps
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 33)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 34)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 35)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 36)');
		
		// Access assignment: Users => Allowed to view all rotations
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 41)');
		
		// Access assignment: Users => Allowed to view all automaps
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 37)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 38)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 39)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 40)');
		
		// Access assignment: Users => Allowed to change their passwords
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 29)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 30)');
		
		// Access assignment: Users => Allowed to search objects
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 31)');
		
		// Access assignment: Users => Allowed to logout
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 32)');
		
		/*
		 * Guest handling
		 */
		
		// Role assignment: guest => Guests
		$this->DB->query('INSERT INTO users2roles (userId, roleId) VALUES (2, 3)');
		
		// Access assignment: Guests => Allowed to view the overview
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 2)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 3)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 4)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 5)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 6)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 7)');
		
		// Access assignment: Guests => Allowed to view the demo map
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 8)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 9)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 10)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 11)');
		
		// Access assignment: Guests => Allowed to view the demo2 map
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 12)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 13)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 14)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 15)');
		
		// Access assignment: Guests => Allowed to view the demo-map map
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 16)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 17)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 18)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 19)');
		
		// Access assignment: Guests => Allowed to view the demo-server map
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 20)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 21)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 22)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 23)');
		
		// Access assignment: Guests => Allowed to view the demo rotation
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 24)');
		
		// Access assignment: Guests => Allowed to view the __automap automap
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 25)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 26)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 27)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 28)');
		
		// Access assignment: Guests => Allowed to change their passwords
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 29)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 30)');
		
		// Access assignment: Guests => Allowed to search objects
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 31)');
		
		// Access assignment: Guests => Allowed to logout
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (3, 32)');
	}
}
?>
