<?php
/*******************************************************************************
 *
 * CoreAuthHandler.php - Handler for authentication modules
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
	private $bVerifyAuth      = false;
	
	public function __construct(GlobalCore $CORE, CoreSessionHandler $SESS, $sModule) {
		$this->CORE = $CORE;
		$this->SESS = $SESS;
		
		$this->sModuleName = $sModule;
		$this->MOD = new $sModule($CORE);
	}
	
	public function checkFeature($name) {
		$a = $this->MOD->getSupportedFeatures();
		return isset($a[$name]) && $a[$name];
	}
	
	public function getModule() {
		return $this->sModuleName;
	}
	
	public function passCredentials($aData) {
		// Some simple validations
		if($aData !== false) {
			$this->bVerifyAuth = true;
			$this->MOD->passCredentials($aData);
		} else {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('Data has an invalid format'));
		}
	}
	
	public function passNewPassword($aData) {
		// FIXME: First check if the auth module supports this mechanism
		
		// Some simple validations
		if($aData !== false) {
			$this->MOD->passNewPassword($aData);
		} else {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('Data has an invalid format'));
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
		return $name !== '' && $this->MOD->checkUserExists($name);
	}
	
	public function createUser($username, $password) {
		// FIXME: First check if the auth module supports this mechanism
		
		// Ask the module
		return $this->MOD->createUser($username, $password);
	}

	// Did the user authenticate using trusted auth?
	public function authedTrusted() {
		return $this->SESS->isSetAndNotEmpty('authTrusted');
	}

	public function getLogonModule() {
		return $this->SESS->get('logonModule');
	}
	
	public function changePassword() {
		// FIXME: First check if the auth module supports this mechanism
		
		// Ask the module
		$bChanged = $this->MOD->changePassword();
		
		// Save success to session
		if($bChanged === true)
			$this->SESS->set('authCredentials', $this->getCredentials());
		
		return $bChanged;
	}

	public function resetPassword($uid, $pw) {
		if(!$this->checkFeature('resetPassword'))
			throw new CoreAuthModNoSupport();
		return $this->MOD->resetPassword($uid, $pw);
	}
	
	public function resetAuthCheck() {
		$this->bIsAuthenticated = null;
	}

	private function verifyAuth($bTrustUsername) {
		if((bool) $this->CORE->getMainCfg()->getValue('global', 'audit_log') === true)
			$ALOG = new CoreLog($this->CORE->getMainCfg()->getValue('paths', 'var').'nagvis-audit.log',
		                      $this->CORE->getMainCfg()->getValue('global', 'dateformat'));
		else
			$ALOG = null;

		$bAlreadyAuthed = $this->SESS->isSetAndNotEmpty('authCredentials');

		// Remove logins which were performed with different logon modules
		if($bAlreadyAuthed && $this->SESS->get('logonModule') != $this->sModuleName)
			$this->logout();
		
		// When the user authenticated in trust mode read it here and override
		// the value handed over with the function call.
		// The isAuthentication() function will then only check if the user exists.
		if($this->authedTrusted())
			$bTrustUsername = AUTH_TRUST_USERNAME;
		
		// Ask the module
		$isAuthenticated = $this->MOD->isAuthenticated($bTrustUsername);
		
		// Save success to session (only if this is no session auth)
		if($isAuthenticated === true) {
			$this->SESS->set('logonModule',     $this->sModuleName);
			$this->SESS->set('authCredentials', $this->getCredentials());
			
			// Save that the user authenticated in trust mode
			if($bTrustUsername === AUTH_TRUST_USERNAME)
				$this->SESS->set('authTrusted', AUTH_TRUST_USERNAME);

			if($ALOG !== null && !$bAlreadyAuthed)
				$ALOG->l('User logged in ('.$this->getUser().' / '.$this->getUserId().'): '.$this->sModuleName);
		}

		if($ALOG !== null && $isAuthenticated === false)
			$ALOG->l('User login failed ('.$this->getUser().' / '.$this->getUserId().'): '.$this->sModuleName);
		
		// Remove some maybe old data when not authenticated
		if($isAuthenticated === false && $this->SESS->isSetAndNotEmpty('authCredentials'))
			$this->logout();

		return $isAuthenticated;
	}
	
	public function isAuthenticated($bTrustUsername = AUTH_NOT_TRUST_USERNAME) {
		// Use auth cache if available
		if($this->bIsAuthenticated !== null)
			return $this->bIsAuthenticated;

		// No auth request, so fetch information from SESSION
		if($this->bVerifyAuth === false) {
			$aCredentials = $this->SESS->get('authCredentials');

			if($aCredentials === null)
				return false;
			else
				$this->MOD->passCredentials($aCredentials);
		}

		// The user loggs in with this call
		$this->bIsAuthenticated = $this->verifyAuth($bTrustUsername);
		return $this->bIsAuthenticated;
	}

	public function logoutSupported() {
		return !$this->authedTrusted();
	}
	
	public function logout() {
		if(!$this->logoutSupported())
			return false;

		if((bool) $this->CORE->getMainCfg()->getValue('global', 'audit_log') === true) {
			$ALOG = new CoreLog($this->CORE->getMainCfg()->getValue('paths', 'var').'nagvis-audit.log',
		                      $this->CORE->getMainCfg()->getValue('global', 'dateformat'));
			$ALOG->l('User logged out ('.$this->getUser().' / '.$this->getUserId().'): '.$this->sModuleName);
		}
		
		// Remove the login information
		$this->SESS->set('authCredentials', false);
		$this->SESS->set('userPermissions', false);
		$this->SESS->set('logonModule',     false);
		
		return true;
	}
}
?>
