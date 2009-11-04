<?php
class FrontendModLogonDialog extends FrontendModule {
	protected $CORE;
	protected $FHANDLER;
	protected $SHANDLER;
	
	// Maximum length of input in forms
	protected $iInputMaxLength = 12;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => 0,
		                        'login' => 0,
		                        'logout' => REQUIRES_AUTHORISATION);
		
		$this->FHANDLER = new FrontendRequestHandler($_POST);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					// Check if user is already authenticated
					if(!isset($this->AUTHENTICATION) || !$this->AUTHENTICATION->isAuthenticated()) {
						$sReturn = $this->displayDialog();
					} else {
						// When the user is already authenticated redirect to start page (overview)
						Header('Location:'.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					}
				break;
				case 'login':
					// Check if user is already authenticated
					if(!isset($this->AUTHENTICATION) || !$this->AUTHENTICATION->isAuthenticated()) {
						$aReturn = $this->handleResponse();
						
						if($aReturn !== false) {
							// Reset the authentication check. Without this the cached result
							// would prevent the authentication check with the given credentials
							$this->AUTHENTICATION->resetAuthCheck();
							
							// Set credentials to authenticate
							// FIXME: Correct auth module?
							$this->AUTHENTICATION->passCredentials($aReturn);
							
							// Try to authenticate the user
							// FIXME: Correct auth module?
							if($this->AUTHENTICATION->isAuthenticated()) {
								// Display success with link and refresh in 5 seconds to called page
								$sReturn = $this->msgAuthenticated();
							} else {
								// Invalid credentials
								$sReturn = $this->msgInvalidCredentials();
							}
						} else {
							$sReturn = $this->msgInvalidCredentials();
						}
					} else {
						// When the user is already authenticated redirect to start page (overview)
						Header('Location:'.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					}
				break;
				case 'logout':
					// FIXME: Correct auth module?
					$this->AUTHENTICATION->logout();
					
					// Redirect to main page
					Header('Location: '.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					exit(0);
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function displayDialog() {
		$VIEW = new NagVisLoginView($this->CORE);
		return $VIEW->parse();
	}
	
	private function handleResponse() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('username')) {
			$bValid = false;
		}
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('password')) {
			$bValid = false;
		}
		
		// Check length limits
		if($bValid && $this->FHANDLER->isLongerThan('username', $this->iInputMaxLength)) {
			$bValid = false;
		}
		if($bValid && $this->FHANDLER->isLongerThan('password', $this->iInputMaxLength)) {
			$bValid = false;
		}
		
		//@todo Escape vars?
		
	  // Store response data
	  if($bValid === true) {
	  	$sUsername = $this->FHANDLER->get('username');
	  	$sPassword = $this->FHANDLER->get('password');
		  
		  // Return the data
		  return Array('user' => $sUsername, 'password' => $sPassword);
		} else {
			return false;
		}
	}
	
	public function msgAuthenticated() {
		new GlobalMessage('NOTE', $this->CORE->getLang()->getText('You have been authenticated. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->FHANDLER->getReferer())), null, null, 1, $this->FHANDLER->getReferer());
		
		return '';
	}
	
	public function msgInvalidCredentials() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid credentials. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->FHANDLER->getReferer())), null, $this->CORE->getLang()->getText('Authentication failed'), 1, $this->FHANDLER->getReferer());
		
		return '';
	}
}

?>
