<?php
/*****************************************************************************
 *
 * CoreModUserMgmt.php - Core module for handling the user management tasks
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
class CoreModUserMgmt extends CoreModule {
	protected $CORE;
	protected $FHANDLER;
	
	public function __construct($CORE) {
		$this->sName = 'UserMgmt';
		$this->CORE = $CORE;
		
		$this->aActions = Array('view'         => 'manage',
		                        'getUserRoles' => 'manage',
		                        'getAllRoles'  => 'manage',
		                        'doAdd'        => 'manage',
		                        'doEdit'       => 'manage',
		                        'doDelete'     => 'manage');
		
		$this->FHANDLER = new CoreRequestHandler($_POST);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				// The best place for this would be a FrontendModule but this needs to
				// be in CoreModule cause it is fetched via ajax. The error messages
				// would be printed in HTML format in nagvis-js frontend.
				case 'view':
					$VIEW = new NagVisViewUserMgmt($this->AUTHENTICATION, $this->AUTHORISATION);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'doAdd':
					$aReturn = $this->handleResponseAdd();
					
					if($aReturn !== false) {
						// Try to apply the changes
						if($this->AUTHENTICATION->createUser($aReturn['user'], $aReturn['password'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The user has been created.'));
							$sReturn = '';
						} else {
							// Invalid credentials
							$sReturn = $this->msgUserNotCreated();
						}
					} else {
						$sReturn = $this->msgInputNotValid();
					}
				break;
				case 'getUserRoles':
					// Parse the specific options
					$aVals = $this->getCustomOptions(Array('userId' => MATCH_INTEGER));
					$userId = $aVals['userId'];
					
					// Get current user roles
					$sReturn = json_encode($this->AUTHORISATION->getUserRoles($userId));
				break;
				case 'getAllRoles':
					// Get current permissions of role
					$sReturn = json_encode($this->AUTHORISATION->getAllRoles());
				break;
				case 'doEdit':
					$aReturn = $this->handleResponseEdit();
					
					if($aReturn !== false) {
						if($this->AUTHORISATION->updateUserRoles($aReturn['userId'], $aReturn['roles'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The roles for this user have been updated.'));
							$sReturn = '';
						} else {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('Problem while updating user roles.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'doDelete':
					$aReturn = $this->handleResponseDelete();
					
					if($aReturn !== false) {
						if($this->AUTHORISATION->deleteUser($aReturn['userId'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The user has been deleted.'));
							$sReturn = '';
						} else {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('Problem while deleting user.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
			}
		}
		
		return $sReturn;
	}
		
	private function handleResponseDelete() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('userId'))
			$bValid = false;

		// Regex validation
		if($bValid && !$this->FHANDLER->match('userId', MATCH_INTEGER))
			$bValid = false;
		
		// Parse the specific options
		$userId = intval($this->FHANDLER->get('userId'));
		
		// Don't delete own user
		if($this->AUTHENTICATION->getUserId() == $userId) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('Unable to delete your own user.'));
			
			$bValid = false;
		}
		
		// Store response data
		if($bValid === true)
		  return Array('userId' => $userId);
		else
			return false;
	}
	
	private function handleResponseEdit() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('userId'))
			$bValid = false;
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('rolesSelected'))
			$bValid = false;
		
		// Regex validate
		if($bValid && !$this->FHANDLER->match('userId', MATCH_INTEGER))
			$bValid = false;
		if($bValid && !$this->FHANDLER->match('rolesSelected', MATCH_INTEGER))
			$bValid = false;
		
		// Parse the specific options
		$userId = intval($this->FHANDLER->get('userId'));
		
		// Store response data
		if($bValid === true)
			return Array('userId' => $userId, 'roles' => $this->FHANDLER->get('rolesSelected'));
		else
			return false;
	}
	
	private function handleResponseAdd() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('username'))
			$bValid = false;
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('password1'))
			$bValid = false;
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('password2'))
			$bValid = false;
		
		// Check length limits
		if($bValid && $this->FHANDLER->isLongerThan('username', AUTH_MAX_USERNAME_LENGTH))
			$bValid = false;
		if($bValid && $this->FHANDLER->isLongerThan('password1', AUTH_MAX_PASSWORD_LENGTH))
			$bValid = false;
		if($bValid && $this->FHANDLER->isLongerThan('password2', AUTH_MAX_PASSWORD_LENGTH))
			$bValid = false;
		
		// Regex match
		if($bValid && !$this->FHANDLER->match('username', MATCH_USER_NAME))
			$bValid = false;
		
		// Check if the user already exists
		if($bValid && $this->AUTHENTICATION->checkUserExists($this->FHANDLER->get('username'))) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The username is invalid or does already exist.'));
			$bValid = false;
		}
		
		// Check if new passwords are equal
		if($bValid && $this->FHANDLER->get('password1') !== $this->FHANDLER->get('password2')) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The two passwords are not equal.'));
			$bValid = false;
		}
		
		//@todo Escape vars?
		
		// Store response data
		if($bValid === true)
			return Array('user' => $this->FHANDLER->get('username'),
		               'password' => $this->FHANDLER->get('password1'));
		else
			return false;
	}
	
	public function msgInputNotValid() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
		return '';
	}
	
	public function msgUserNotCreated() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The user could not be created.'));
		return '';
	}
}

?>
