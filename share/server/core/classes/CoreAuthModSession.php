<?php
/*******************************************************************************
 *
 * CoreAuthModFile.php - Authentication module for session based authentication
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
 * Checks if there are authentication information stored in a session
 * If so it tries to reuse the stored information
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreAuthModSession extends CoreAuthModule {
	private $CORE;
	private $SHANDLER;
	private $REALAUTH = null;
	private $iUserId = -1;
	private $sUsername = '';
	private $sPassword = '';
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
			
			// Changing passwordss
			'passNewPassword' => true,
			'changePassword' => true,
			'passNewPassword' => true
		);
		
		$this->SHANDLER = new CoreSessionHandler($this->CORE->getMainCfg()->getValue('global', 'sesscookiedomain'), 
		                                         $this->CORE->getMainCfg()->getValue('global', 'sesscookiepath'),
		                                         $this->CORE->getMainCfg()->getValue('global', 'sesscookieduration'));
	}
	
	private function initRealAuthModule() {
		$this->REALAUTH = new CoreAuthHandler($this->CORE, $this->SHANDLER, $this->CORE->getMainCfg()->getValue('global','authmodule'));
	}
	
	public function passCredentials($aData) {
		if(isset($aData['user'])) {
			$this->sUsername = $aData['user'];
		}
		
		if(isset($aData['password'])) {
			$this->sPassword = $aData['password'];
		}
	}
	
	// The session auth module does not change password itselfs. It calls the real
	// authentication module for performing this change
	public function passNewPassword($aData) {
		// Initialize the real authentication module when needed
		if($this->REALAUTH === null) {
			$this->initRealAuthModule();
		}
		
		// Pass the new information to the real auth module
		return $this->REALAUTH->passNewPassword($aData);
	}
	
	// The session auth module does not change password itselfs. It calls the real
	// authentication module for performing this change
	public function changePassword() {
		// The real authentication module should already have been initialized here
		if($this->REALAUTH === null) {
			return false;
		}
		
		return $this->REALAUTH->changePassword();
	}
	
	public function getCredentials() { return Array(); }
	
	public function isAuthenticated() {
		$aCredentials = null;
		
		// Did the user just try to authenticate? Then passCredentials should be
		// called before to put the credentials here
		if($aCredentials === null && $this->sUsername !== '' && $this->sPassword !== '') {
			$aCredentials = Array('user' => $this->sUsername, 'password' => $this->sPassword);
		}
		
		// Are the sessions options set? Use them for authentication
		if($aCredentials === null && $this->SHANDLER->isSetAndNotEmpty('authCredentials')) {
			$aCredentials = $this->SHANDLER->get('authCredentials');
			
			if(isset($aCredentials['user'])) {
				$this->sUsername = $aCredentials['user'];
			}
			if(isset($aCredentials['passwordHash'])) {
				$this->sPasswordHash = $aCredentials['passwordHash'];
			}
			if(isset($aCredentials['userId'])) {
				$this->iUserId = $aCredentials['userId'];
			}
		}
		
		// If none of the above fit, then break and return false
		if($aCredentials === null) {
			return false;
		}
			
		// Initialize the real auth module when needed
		if($this->REALAUTH === null) {
			$this->initRealAuthModule();
		}
		
		// Validate data
		$this->REALAUTH->passCredentials($aCredentials);
		
		if($this->REALAUTH->isAuthenticated()) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getUser() {
		return $this->sUsername;
	}
	
	public function getUserId() {
		return $this->iUserId;
	}
}

?>
