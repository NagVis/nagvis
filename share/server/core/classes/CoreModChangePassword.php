<?php
class CoreModChangePassword extends CoreModule {
	protected $CORE;
	protected $FHANDLER;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => REQUIRES_AUTHORISATION,
		                        'change' => REQUIRES_AUTHORISATION);
		
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
					// Check if user is already authenticated
					if(isset($this->AUTHENTICATION) && $this->AUTHENTICATION->isAuthenticated()) {
						$VIEW = new NagVisViewChangePassword($this->CORE);
						$sReturn = json_encode(Array('code' => $VIEW->parse()));
					} else {
						$sReturn = '';
					}
				break;
				case 'change':
					// Check if user is already authenticated
					if(isset($this->AUTHENTICATION) && $this->AUTHENTICATION->isAuthenticated()) {
						$aReturn = $this->handleResponse();
						
						if($aReturn !== false) {
							// Reset the authentication check. Without this the cached result
							// would prevent the authentication check with the given credentials
							$this->AUTHENTICATION->resetAuthCheck();
							
							// Set new passwords in authentication module
							$this->AUTHENTICATION->passNewPassword($aReturn);
							
							// Try to apply the changes
							if($this->AUTHENTICATION->changePassword()) {
								new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The password has been changed.'));
								$sReturn = '';
							} else {
								// Invalid credentials
								$sReturn = $this->msgPasswordNotChanged();
							}
						} else {
							$sReturn = $this->msgInputNotValid();
						}
					} else {
						// When the user is not authenticated redirect to start page (overview)
						Header('Location:'.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					}
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function handleResponse() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('passwordOld')) {
			$bValid = false;
		}
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('passwordNew1')) {
			$bValid = false;
		}
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('passwordNew2')) {
			$bValid = false;
		}
		
		// Check length limits
		if($bValid && $this->FHANDLER->isLongerThan('passwordOld', AUTH_MAX_PASSWORD_LENGTH)) {
			$bValid = false;
		}
		if($bValid && $this->FHANDLER->isLongerThan('passwordNew1', AUTH_MAX_PASSWORD_LENGTH)) {
			$bValid = false;
		}
		if($bValid && $this->FHANDLER->isLongerThan('passwordNew2', AUTH_MAX_PASSWORD_LENGTH)) {
			$bValid = false;
		}
		
		// Check if new passwords are equal
		if($bValid && $this->FHANDLER->get('passwordNew1') !== $this->FHANDLER->get('passwordNew2')) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The two new passwords are not equal.'));
			
			$bValid = false;
		}
		
		// Check if old and new passwords are equal
		if($bValid && $this->FHANDLER->get('passwordOld') === $this->FHANDLER->get('passwordNew1')) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The new and old passwords are equal. Won\'t change anything.'));
			
			$bValid = false;
		}
		
		//@todo Escape vars?
		
	  // Store response data
	  if($bValid === true) {
		  // Return the data
		  return Array(
		               'user' => $this->AUTHENTICATION->getUser(),
		               'password' => $this->FHANDLER->get('passwordOld'),
		               'passwordNew' => $this->FHANDLER->get('passwordNew1'));
		} else {
			return false;
		}
	}
	
	public function msgInputNotValid() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
		return '';
	}
	
	public function msgPasswordNotChanged() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The password could not be changed.'));
		return '';
	}
}

?>
