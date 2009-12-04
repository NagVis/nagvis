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
	private $sPasswordnew = '';
	private $sPasswordHash = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
	
		parent::$aFeatures = Array(
			// General functions for authentication
			'passCredentials' => true,
			'getCredentials' => true,
			'isAuthenticated' => true,
			'getUser' => true,
			'getUserId' => true,
			
			// Changing passwords
			'passNewPassword' => true,
			'changePassword' => true,
			'passNewPassword' => true,
			
			// Managing users
			'createUser' => true,
		);
		
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
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (8, \'Map\', \'view\', \'demo\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (9, \'Map\', \'getMapProperties\', \'demo\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (10, \'Map\', \'getMapObjects\', \'demo\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (11, \'Map\', \'getObjectStates\', \'demo\')');
		
		// Access controll: Map module levels for map "demo2"
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (12, \'Map\', \'view\', \'demo2\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (13, \'Map\', \'getMapProperties\', \'demo2\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (14, \'Map\', \'getMapObjects\', \'demo2\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (15, \'Map\', \'getObjectStates\', \'demo2\')');
		
		// Access controll: Map module levels for map "demo-map"
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (16, \'Map\', \'view\', \'demo-map\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (17, \'Map\', \'getMapProperties\', \'demo-map\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (18, \'Map\', \'getMapObjects\', \'demo-map\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (19, \'Map\', \'getObjectStates\', \'demo-map\')');
		
		// Access controll: Map module levels for map "demo-server"
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (20, \'Map\', \'view\', \'demo-server\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (21, \'Map\', \'getMapProperties\', \'demo-server\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (22, \'Map\', \'getMapObjects\', \'demo-server\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (23, \'Map\', \'getObjectStates\', \'demo-server\')');
		
		// Access controll: Rotation module levels for rotation "demo"
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (24, \'Rotation\', \'view\', \'demo\')');
		
		// Access controll: Automap module levels for automap "__automap"
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (25, \'AutoMap\', \'view\', \'__automap\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (26, \'AutoMap\', \'getAutomapProperties\', \'__automap\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (27, \'AutoMap\', \'getAutomapObjects\', \'__automap\')');
		$this->DB->query('INSERT INTO perms (permId, mod, act, obj) VALUES (28, \'AutoMap\', \'getObjectStates\', \'__automap\')');
		
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
		
		// Access assignment: Managers => Allowed to edit/delete all automaps
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 57)');
		$this->DB->query('INSERT INTO roles2perms (roleId, permId) VALUES (2, 58)');
		
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
	
	public function getAllUsers() {
		$aPerms = Array();
		
		// Get all the roles of the user
	  $RES = $this->DB->query('SELECT userId, name FROM users ORDER BY name');
	  while($data = $this->DB->fetchAssoc($RES)) {
	  	$aPerms[] = $data;
	  }
	  
	  return $aPerms;
	}
	
	public function checkUserExists($name) {
		$RES = $this->DB->query('SELECT COUNT(*) FROM users WHERE name=\''.sqlite_escape_string($name).'\'');
		return intval($RES->fetchSingle()) > 0;
	}
	
	private function checkUserAuth($bTrustUsername = AUTH_NOT_TRUST_USERNAME) {
		if($bTrustUsername === AUTH_NOT_TRUST_USERNAME) {
			$query = 'SELECT userId FROM users WHERE name=\''.sqlite_escape_string($this->sUsername).'\' AND password=\''.sqlite_escape_string($this->sPasswordHash).'\'';
		} else {
			$query = 'SELECT userId FROM users WHERE name=\''.sqlite_escape_string($this->sUsername).'\'';
		}
		
		return intval($this->DB->query($query)->fetchSingle());
	}
	
	private function updatePassword() {
		$RES = $this->DB->query('UPDATE users SET password=\''.sqlite_escape_string($this->sPasswordHash).'\' WHERE name=\''.sqlite_escape_string($this->sUsername).'\'');
		return intval($RES->fetchSingle());
	}
	
	private function addUser($user, $hash) {
		$this->DB->query('INSERT INTO users (name,password) VALUES (\''.sqlite_escape_string($user).'\',\''.sqlite_escape_string($hash).'\')');
	}
	
	public function passCredentials($aData) {
		if(isset($aData['user'])) {
			$this->sUsername = $aData['user'];
		}
		if(isset($aData['password'])) {
			$this->sPassword = $aData['password'];
			
			// Remove the password hash when setting a new password
			$this->sPasswordHash = '';
		}
		if(isset($aData['passwordHash'])) {
			$this->sPasswordHash = $aData['passwordHash'];
		}
	}
	
	public function passNewPassword($aData) {
		if(isset($aData['user'])) {
			$this->sUsername = $aData['user'];
		}
		if(isset($aData['password'])) {
			$this->sPassword = $aData['password'];
			
			// Remove the password hash when setting a new password
			$this->sPasswordHash = '';
		}
		if(isset($aData['passwordNew'])) {
			$this->sPasswordNew = $aData['passwordNew'];
		}
	}
	
	public function getCredentials() {
		return Array('user' => $this->sUsername,
		             'passwordHash' => $this->sPasswordHash,
		             'userId' => $this->iUserId);
	}
	
	public function createUser($user, $password) {
		$bReturn = false;
		
		// Compose the password hash
		$hash = $this->createHash($password);
		
		// Create user
		$this->addUser($user, $hash);
		
		// Check result
		if($this->checkUserExists($user)) {
			$bReturn = true;
		} else {
			$bReturn = false;
		}
		
		return $bReturn;
	}
	
	public function changePassword() {
		$bReturn = false;
		
		// Check the authentication with the old password
		if($this->isAuthenticated()) {
			// Set new password to current one
			$this->sPassword = $this->sPasswordNew;
			
			// Compose the new password hash
			$this->sPasswordHash = $this->createHash($this->sPassword);
			
			// Update password
			$this->updatePassword();
			
			$bReturn = true;
		} else {
			//FIXME: Logging? Invalid user
			$bReturn = false;
		}
		
		return $bReturn;
	}
	
	public function isAuthenticated($bTrustUsername = AUTH_NOT_TRUST_USERNAME) {
		$bReturn = false;

		// Only handle known users
		if($this->sUsername !== '' && $this->checkUserExists($this->sUsername)) {
			
			// Try to calculate the passowrd hash only when no hash is known at
			// this time. For example when the user just entered the password
			// for logging in. If the user is already logged in and this is just
			// a session check don't try to rehash the password.
			if($bTrustUsername === AUTH_NOT_TRUST_USERNAME && $this->sPasswordHash === '') {
				// Compose the password hash for comparing with the stored hash
				$this->sPasswordHash = $this->createHash($this->sPassword);
			}
			
			// Check the password hash
			$userId = $this->checkUserAuth($bTrustUsername);
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
	
	private function createHash($password) {
		return sha1(AUTH_PASSWORD_SALT.$password);
	}
}
?>
