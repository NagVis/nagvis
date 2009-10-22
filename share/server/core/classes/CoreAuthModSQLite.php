<?php
/*******************************************************************************
 *
 * CoreAuthModSQLite.php - Authentication module based on a SQLite database
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
class CoreAuthModSQLite extends CoreAuthModule {
	private $CORE;
	private $USERCFG;
	
	private $iUserId = -1;
	private $sUsername = '';
	private $sPassword = '';
	private $sPasswordHash = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		$this->DB = new CoreSQLiteHandler();
		
		// Open sqlite database
		if(!$this->DB->open($this->CORE->getMainCfg()->getValue('paths', 'cfg').'auth.db')) {
			// FIXME: Errorhandling
		} else {
			// Create initial db scheme if needed
			if(!$this->DB->tableExist('users')) {
				$this->createInitialDb();
			}
		}
	}
	
	private function createInitialDb() {
		$this->DB->query('CREATE TABLE users (userId INTEGER, name VARCHAR(100), password VARCHAR(40), PRIMARY KEY(userId))');
		$this->DB->query('CREATE TABLE roles (roleId INTEGER, name VARCHAR(100), PRIMARY KEY(roleId))');
		$this->DB->query('CREATE TABLE perms (permId INTEGER, mod VARCHAR(100), act VARCHAR(100), obj VARCHAR(100), PRIMARY KEY(permId))');
		$this->DB->query('CREATE TABLE users2roles (userId INTEGER, roleId INTEGER, PRIMARY KEY(userId, roleId))');
		$this->DB->query('CREATE TABLE roles2perms (roleId INTEGER, permId INTEGER, PRIMARY KEY(roleId, permId))');
		
		$this->DB->query('INSERT INTO users (userId, name, password) VALUES (1, \'nagiosadmin\', \'7f09c620da83db16ef9b69abfb8edd6b849d2d2b\')');
		$this->DB->query('INSERT INTO users (userId, name, password) VALUES (2, \'guest\', \'7f09c620da83db16ef9b69abfb8edd6b849d2d2b\')');
		$this->DB->query('INSERT INTO roles (roleId, name) VALUES (1, \'Administrators\')');
		$this->DB->query('INSERT INTO roles (roleId, name) VALUES (2, \'Users\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (1, \'*\', \'*\', \'*\')');
		
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (2, \'Overview\', \'view\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (3, \'Overview\', \'getOverviewRotations\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (4, \'Overview\', \'getOverviewProperties\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (5, \'Overview\', \'getOverviewMaps\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (6, \'Overview\', \'getOverviewAutomaps\', \'*\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (7, \'General\', \'*\', \'*\')');
		
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (8, \'Map\', \'view\', \'demo\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (9, \'Map\', \'getMapProperties\', \'demo\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (10, \'Map\', \'getMapObjects\', \'demo\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (11, \'Map\', \'getObjectStates\', \'demo\')');
		
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (12, \'Map\', \'view\', \'demo2\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (13, \'Map\', \'getMapProperties\', \'demo2\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (14, \'Map\', \'getMapObjects\', \'demo2\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (15, \'Map\', \'getObjectStates\', \'demo2\')');
		
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (16, \'Map\', \'view\', \'demo-map\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (17, \'Map\', \'getMapProperties\', \'demo-map\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (18, \'Map\', \'getMapObjects\', \'demo-map\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (19, \'Map\', \'getObjectStates\', \'demo-map\')');
		
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (20, \'Map\', \'view\', \'demo-server\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (21, \'Map\', \'getMapProperties\', \'demo-server\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (22, \'Map\', \'getMapObjects\', \'demo-server\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (23, \'Map\', \'getObjectStates\', \'demo-server\')');
		
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (24, \'Rotation\', \'view\', \'demo\')');
		
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (25, \'AutoMap\', \'view\', \'__automap\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (26, \'AutoMap\', \'getAutomapProperties\', \'__automap\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (27, \'AutoMap\', \'getAutomapObjects\', \'__automap\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (28, \'AutoMap\', \'getObjectStates\', \'__automap\')');
		
		// nagiosadmin => Administrators
		$this->DB->query('INSERT INTO users2roles (userId, roleId) VALUES (1, 1)');
		
		// guest => Users
		$this->DB->query('INSERT INTO users2roles (userId, roleId) VALUES (2, 2)');
		
		// Administrators => * * *
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (1, 1)');
		
		// Users => Allowed to view the overview
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 2)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 3)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 4)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 5)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 6)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 7)');
		
		// Users => Allowed to view the demo map
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 8)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 9)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 10)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 11)');
		
		// Users => Allowed to view the demo2 map
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 12)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 13)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 14)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 15)');
		
		// Users => Allowed to view the demo-map map
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 16)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 17)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 18)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 19)');
		
		// Users => Allowed to view the demo-server map
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 20)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 21)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 22)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 23)');
		
		// Users => Allowed to view the demo rotation
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 24)');
		
		// Users => Allowed to view the __automap automap
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 25)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 26)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 27)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 28)');
	}
	
	private function checkUserExists() {
		$RES = $this->DB->query('SELECT COUNT(*) FROM users WHERE name=\''.sqlite_escape_string($this->sUsername).'\'');
		return intval($RES->fetchSingle()) > 0;
	}
	
	private function checkUserAuth() {
		$RES = $this->DB->query('SELECT userId FROM users WHERE name=\''.sqlite_escape_string($this->sUsername).'\' AND password=\''.sqlite_escape_string($this->sPasswordHash).'\'');
		return intval($RES->fetchSingle());
	}
	
	public function passCredentials($aData) {
		if(isset($aData['user'])) {
			$this->sUsername = $aData['user'];
		}
		if(isset($aData['password'])) {
			$this->sPassword = $aData['password'];
		}
		if(isset($aData['passwordHash'])) {
			$this->sPasswordHash = $aData['passwordHash'];
		}
	}
	
	public function getCredentials() {
		return Array('user' => $this->sUsername,
		             'passwordHash' => $this->sPasswordHash,
		             'userId' => $this->iUserId);
	}
	
	public function isAuthenticated() {
		$bReturn = false;

		// Only handle known users
		if($this->sUsername !== '' && $this->checkUserExists()) {
			
			// Try to calculate the passowrd hash only when no hash is known at
			// this time. For example when the user just entered the password
			// for logging in. If the user is already logged in and this is just
			// a session check don't try to rehash the password.
			if($this->sPasswordHash === '') {
				// Compose the password hash for comparing with the stored hash
				$this->sPasswordHash = sha1(AUTH_PASSWORD_SALT.$this->sPassword);
			}
			
			// Check the password hash
			$userId = $this->checkUserAuth();
			if($userId > 0) {
				$this->iUserId = $userId;
				//FIXME: Logging? Successfull authentication
				$bReturn = true;
			} else {
				//FIXME: Logging? Invalid password
				$bReturn = false;
			}
		} else {
			//FIXME: Logging? Invalid user
			$bReturn = false;
		}
		
		return $bReturn;
	}
	
	public function getUser() {
		return $this->sUsername;
	}
	
	public function getUserId() {
		return $this->iUserId;
	}
}
?>
