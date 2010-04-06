<?php
/*******************************************************************************
 *
 * CoreAuthHandler.php - Handler for authentication modules
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
 * This class handles all authorisation tasks and is the glue between the
 * application and the different authorisation modules. It loads the
 * authentication module depending on the configuration. An authentication
 * module handles the information gathered from the webservers vars or the
 * frontend.
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreAuthHandler {
	private $CORE;
	private $SESS;
	private $MOD;
	
	private $sModuleName;
	private $bIsAuthenticated = null;
	
	public function __construct(GlobalCore $CORE, CoreSessionHandler $SESS, $sModule) {
		$this->CORE = $CORE;
		$this->SESS = $SESS;
		
		$this->sModuleName = $sModule;

		$this->MOD = new $sModule($CORE);
	}
	
	public function checkFeature($name) {
		$a = $this->MOD->getSupportedFeatures();
		
		if(isset($a[$name])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getModule() {
		return $this->sModuleName;
	}
	
	public function passCredentials($aData) {
		// Some simple validations
		if($aData !== false) {
			$this->MOD->passCredentials($aData);
		} else {
			//FIXME: error handling
		}
	}
	
	public function passNewPassword($aData) {
		// FIXME: First check if the auth module supports this mechanism
		
		// Some simple validations
		if($aData !== false) {
			$this->MOD->passNewPassword($aData);
		} else {
			//FIXME: error handling
		}
	}
	
	private function getCredentials() {
		return $this->MOD->getCredentials();
	}
	
	public function getUser() {
		return $this->MOD->getUser();
	}
	
	public function getUserId() {
		return $this->MOD->getUserId();
	}
	
	public function getAllUsers() {
		// FIXME: First check if the auth module supports this mechanism
		
		// Ask the module
		return $this->MOD->getAllUsers();
	}
	
	public function checkUserExists($name) {
		if($name !== '' && $this->MOD->checkUserExists($name)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function createUser($username, $password) {
		// FIXME: First check if the auth module supports this mechanism
		
		// Ask the module
		return $this->MOD->createUser($username, $password);
	}
	
	public function changePassword() {
		// FIXME: First check if the auth module supports this mechanism
		
		// Ask the module
		$bChanged = $this->MOD->changePassword();
		
		// Save success to session (only if this is no session auth)
		if($bChanged === true && $this->sModuleName != 'CoreAuthModSession') {
			$this->SESS->set('authCredentials', $this->getCredentials());
		}
		
		return $bChanged;
	}
	
	public function resetAuthCheck() {
		$this->bIsAuthenticated = null;
	}
	
	public function isAuthenticated($bTrustUsername = AUTH_NOT_TRUST_USERNAME) {
		// Don't do these things twice
		if($this->bIsAuthenticated === null) {
			$ALOG = new CoreLog($this->CORE->getMainCfg()->getValue('paths', 'var').'nagvis-audit.log',
			                    $this->CORE->getMainCfg()->getValue('global', 'dateformat'));
			
			// When the user authenticated in trust mode read it here and override
			// the value handed over with the function call.
			// The isAuthentication() function will then only check if the user exists.
			if($this->SESS->isSetAndNotEmpty('authTrusted')) {
				$bAlreadyAuthed = true;
				$bTrustUsername = AUTH_TRUST_USERNAME;
			} else {
				$bAlreadyAuthed = false;	
			}
			
			// Ask the module
			$this->bIsAuthenticated = $this->MOD->isAuthenticated($bTrustUsername);
			
			// Save success to session (only if this is no session auth)
			if($this->bIsAuthenticated === true && $this->sModuleName != 'CoreAuthModSession') {
				$this->SESS->set('authCredentials', $this->getCredentials());
				
				// Save that the user authenticated in trust mode
				if($bTrustUsername === AUTH_TRUST_USERNAME) {
					$this->SESS->set('authTrusted', AUTH_TRUST_USERNAME);
				}

				if(!$bAlreadyAuthed)
					$ALOG->l('User logged in ('.$this->getUser().' / '.$this->getUserId().'): '.$this->sModuleName);
			}

			if($this->bIsAuthenticated === false && $this->sModuleName != 'CoreAuthModSession') {
				$ALOG->l('User login failed ('.$this->getUser().' / '.$this->getUserId().'): '.$this->sModuleName);
			}
			
			// Remove some maybe old data when not authenticated
			if($this->bIsAuthenticated === false && $this->SESS->isSetAndNotEmpty('authCredentials')) {
				// Remove session data
				$this->logout();
			}
		}
				
		return $this->bIsAuthenticated;
	}

	public function logoutSupported() {
		return ! $this->SESS->isSetAndNotEmpty('authTrusted');
	}
	
	public function logout() {
		if($this->logoutSupported()) {
			$ALOG = new CoreLog($this->CORE->getMainCfg()->getValue('paths', 'var').'nagvis-audit.log',
			                    $this->CORE->getMainCfg()->getValue('global', 'dateformat'));
			$ALOG->l('User logged out ('.$this->getUser().' / '.$this->getUserId().'): '.$this->sModuleName);
			
			// Remove the login information
			$this->SESS->set('authCredentials', false);
			$this->SESS->set('userPermissions', false);
			
			return true;
		} else {
			return false;
		}
	}
}
?>
