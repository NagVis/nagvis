<?php
/*****************************************************************************
 *
 * CoreModAuth.php - This module handles the user login and logout
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
 *****************************************************************************/

/**
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModAuth extends CoreModule {
	protected $CORE;
	protected $FHANDLER;
	
	public function __construct($CORE) {
		$this->sName = 'Auth';
		$this->CORE = $CORE;
		
		$this->aActions = Array('login'  => !REQUIRES_AUTHORISATION,
		                        'logout' => REQUIRES_AUTHORISATION);
		
		$this->FHANDLER = new CoreRequestHandler($_POST);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'login':
					// Check if user is already authenticated
					if(!isset($this->AUTHENTICATION) || !$this->AUTHENTICATION->isAuthenticated()) {
						$aReturn = $this->handleResponseAuth();
						
						if($aReturn !== false) {
							// Reset the authentication check. Without this the cached result
							// would prevent the authentication check with the given credentials
							$this->AUTHENTICATION->resetAuthCheck();
							
							// Set credentials to authenticate
							$this->AUTHENTICATION->passCredentials($aReturn);
							
							// Try to authenticate the user
							if($this->AUTHENTICATION->isAuthenticated()) {
								// Display success with link and refresh in 5 seconds to called page
								// When referer is empty redirect to overview page
								new GlobalMessage('NOTE', $this->CORE->getLang()->getText('You have been authenticated. You will be redirected.'),
								                   null, null, 1, CoreRequestHandler::getReferer($this->CORE->getMainCfg()->getValue('paths', 'htmlbase')));
							} else {
								// Invalid credentials
								// FIXME: Count tries and maybe block somehow
								$sReturn = $this->msgInvalidCredentials();
							}
						} else {
							// FIXME: Count tries and maybe block somehow
							$sReturn = $this->msgInvalidCredentials();
						}
					} else {
						// When the user is already authenticated redirect to start page (overview)
						$sReturn = $this->msgAlreadyLoggedIn();
					}
				break;
				case 'logout':
					if($this->AUTHENTICATION->logout())
						new GlobalMessage('NOTE', $this->CORE->getLang()->getText('You have been logged out. You will be redirected.'),
						                  null, null, 1, $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					else
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('Unable to log you out. Maybe it is not supported by your authentication module.'),
						                  null, null, 1, $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function handleResponseAuth() {
		$attr = Array('username' => MATCH_USER_NAME,
		              'password' => null);
		$this->verifyValuesSet($this->FHANDLER,   $attr);
		$this->verifyValuesMatch($this->FHANDLER, $attr);
		
		// Check length limits
		$bValid = true;
		if($bValid && $this->FHANDLER->isLongerThan('username', AUTH_MAX_USERNAME_LENGTH))
			$bValid = false;
		if($bValid && $this->FHANDLER->isLongerThan('password', AUTH_MAX_PASSWORD_LENGTH))
			$bValid = false;
		
		//@todo Escape vars?
		
		// Store response data
		if($bValid)
		  return Array('user'     => $this->FHANDLER->get('username'),
			             'password' => $this->FHANDLER->get('password'));
		else
			return false;
	}
	
	public function msgAlreadyLoggedIn() {
		new GlobalMessage('NOTE', $this->CORE->getLang()->getText('You are already logged in. You will be redirected.'),
		                  null, null, 1, $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
		return '';
	}
	
	public function msgInvalidCredentials() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid credentials.'),
		                  null, $this->CORE->getLang()->getText('Authentication failed'), 1, CoreRequestHandler::getReferer(''));
		return '';
	}
}

?>
