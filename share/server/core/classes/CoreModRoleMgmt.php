<?php
class CoreModRoleMgmt extends CoreModule {
	protected $CORE;
	protected $FHANDLER;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => REQUIRES_AUTHORISATION,
		                        'getRolePerms' => REQUIRES_AUTHORISATION,
		                        'doAdd' => REQUIRES_AUTHORISATION,
		                        'doEdit' => REQUIRES_AUTHORISATION);
		
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
					$VIEW = new NagVisViewManageRoles($this->AUTHORISATION);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'getRolePerms':
					// Parse the specific options
					$aVals = $this->getCustomOptions(Array('roleId' => MATCH_INTEGER));
					$roleId = $aVals['roleId'];
					
					// Get current permissions of role
					$sReturn = json_encode($this->AUTHORISATION->getRolePerms($roleId));
				break;
				case 'doAdd':
					$aReturn = $this->handleResponseAdd();
					
					if($aReturn !== false) {
						// Try to apply
						if($this->AUTHORISATION->createRole($aReturn['name'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The role has been created.'));
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The user could not be created.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'doEdit':
					$aReturn = $this->handleResponseEdit();
					
					if($aReturn !== false) {
						if($this->AUTHORISATION->updateRolePerms($aReturn['roleId'], $aReturn['perms'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The permissions for this role have been updated.'));
							$sReturn = '';
						} else {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('Problem while updating role permissions.'));
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
	
	private function handleResponseEdit() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('roleId')) {
			$bValid = false;
		}
		
		// Parse the specific options
		// FIXME: validate
		$roleId = intval($this->FHANDLER->get('roleId'));
		
		$aPerms = Array();
		
		// Load perm options
		foreach($this->FHANDLER->getKeys() AS $key) {
			// Only load permission keys
			if(strpos($key, 'perm_') !== false) {
				$aKey = explode('_', $key);
				$permId = $aKey[1];
				
				if($this->FHANDLER->isSetAndNotEmpty($key)) {
					$aPerms[$permId] = true;
				} else {
					$aPerms[$permId] = false;
				}
			}
		}
		
	  // Store response data
	  if($bValid === true) {
		  // Return the data
		  return Array('roleId' => $roleId, 'perms' => $aPerms);
		} else {
			return false;
		}
	}
	
	private function handleResponseAdd() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('name')) {
			$bValid = false;
		}
		
		// Check length limits
		if($bValid && $this->FHANDLER->isLongerThan('name', AUTH_MAX_ROLENAME_LENGTH)) {
			$bValid = false;
		}
		
		// Check if the role already exists
		if($bValid && $this->AUTHORISATION->checkRoleExists($this->FHANDLER->get('name'))) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The rolename is invalid or does already exist.'));
			
			$bValid = false;
		}
		
		//@todo Escape vars?
		
	  // Store response data
	  if($bValid === true) {
		  // Return the data
		  return Array('name' => $this->FHANDLER->get('name'));
		} else {
			return false;
		}
	}
}

?>
