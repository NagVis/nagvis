<?php
class CoreModAuth extends CoreModule {
	protected $CORE;
	protected $FHANDLER;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('login' => 0,
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
						$aReturn = $this->handleResponse();
						
						if($aReturn !== false) {
							// Reset the authentication check. Without this the cached result
							// would prevent the authentication check with the given credentials
							$this->AUTHENTICATION->resetAuthCheck();
							
							// Set credentials to authenticate
							$this->AUTHENTICATION->passCredentials($aReturn);
							
							// Try to authenticate the user
							if($this->AUTHENTICATION->isAuthenticated()) {
								// Display success with link and refresh in 5 seconds to called page
								$sReturn = json_encode(Array('status' => 'OK',
								                             'message' => $this->CORE->getLang()->getText('You have been authenticated. You will be redirected.'),
								                             'redirectTime' => 1,
								                             'redirectUrl' => $this->CORE->getMainCfg()->getValue('paths', 'htmlbase')));
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
					$this->AUTHENTICATION->logout();
					
					// Redirect to main page
					Header('Location: '.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					exit(0);
				break;
			}
		}
		
		return $sReturn;
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
		if($bValid && $this->FHANDLER->isLongerThan('username', AUTH_MAX_USERNAME_LENGTH)) {
			$bValid = false;
		}
		if($bValid && $this->FHANDLER->isLongerThan('password', AUTH_MAX_PASSWORD_LENGTH)) {
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
	
	public function msgAlreadyLoggedIn() {
		new GlobalMessage('NOTE', $this->CORE->getLang()->getText('You are already logged in. You will be redirected.'), null, null, 1, $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
		
		return '';
	}
	
	public function msgInvalidCredentials() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid credentials.'), null, $this->CORE->getLang()->getText('Authentication failed'), 1, $this->FHANDLER->getReferer());
		
		return '';
	}
}

?>
