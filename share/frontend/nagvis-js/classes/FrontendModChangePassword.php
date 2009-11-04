<?php
class FrontendModChangePassword extends FrontendModule {
	protected $CORE;
	protected $FHANDLER;
	protected $SHANDLER;
	
	// Maximum length of input in forms
	protected $iInputMaxLength = 15;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => REQUIRES_AUTHORISATION,
		                        'change' => REQUIRES_AUTHORISATION);
		
		$this->FHANDLER = new FrontendRequestHandler($_POST);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					// Check if user is already authenticated
					if(isset($this->AUTHENTICATION) && $this->AUTHENTICATION->isAuthenticated()) {
						$sReturn = $this->displayDialog();
					} else {
						$sReturn = '';
					}
				break;
				case 'change':
					// Check if user is already authenticated
					if(isset($this->AUTHENTICATION) && $this->AUTHENTICATION->isAuthenticated()) {
						$aReturn = $this->handleResponse();
						
						if($aReturn !== false) {
							// Set new passwords in authentication module
							// FIXME: Correct auth module?
							$this->AUTHENTICATION->passNewPassword($aReturn);
							
							// Try to apply the changes
							// FIXME: Correct auth module?
							if($this->AUTHENTICATION->changePassword()) {
								// Display success with link and refresh in 5 seconds to called page
								$sReturn = $this->msgPasswordChanged();
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
	
	private function displayDialog() {
		$VIEW = new NagVisChangePasswordView($this->CORE);
		return json_encode(Array('code' => $VIEW->parse()));
	}
	
	public function msgInputNotValid() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->FHANDLER->getReferer())), null, null, 1, $this->FHANDLER->getReferer());
		
		return '';
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
		if($bValid && $this->FHANDLER->isLongerThan('passwordOld', $this->iInputMaxLength)) {
			$bValid = false;
		}
		if($bValid && $this->FHANDLER->isLongerThan('passwordNew1', $this->iInputMaxLength)) {
			$bValid = false;
		}
		if($bValid && $this->FHANDLER->isLongerThan('passwordNew2', $this->iInputMaxLength)) {
			$bValid = false;
		}
		
		// Check if new passwords are equal
		if($bValid && $this->FHANDLER->get('passwordNew1') !== $this->FHANDLER->get('passwordNew2')) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The two new passwords are not equal.'), null, null, 1, $this->FHANDLER->getReferer());
			
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
	
	public function msgPasswordChanged() {
		new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The password has been changed. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->FHANDLER->getReferer())), null, null, 1, $this->FHANDLER->getReferer());
		
		return '';
	}
	
	public function msgPasswordNotChanged() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The password could not be changed. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->FHANDLER->getReferer())), null, null, 1, $this->FHANDLER->getReferer());
		
		return '';
	}
}

?>
