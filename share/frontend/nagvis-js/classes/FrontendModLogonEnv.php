<?php
class FrontendModLogonEnv extends FrontendModule {
	protected $CORE;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => 0);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					// Check if user is already authenticated
					if(!isset($this->AUTHENTICATION) || !$this->AUTHENTICATION->isAuthenticated()) {
						
						// Get environment variable to use
						$sEnvVar = $this->CORE->getMainCfg()->getValue('global', 'logonenvvar');
						
						// Check if the variable exists and is not empty
						if(isset($_SERVER[$sEnvVar]) && $_SERVER[$sEnvVar] !== '') {
							$sUser = $_SERVER[$sEnvVar];
							
							// Get the authentication instance from the core
							$this->AUTHENTICATION = $this->CORE->getAuthentication();
							
							// Check if the user exists
							if(!$this->AUTHENTICATION->checkUserExists($sUser)) {
								if(settype($this->CORE->getMainCfg()->getValue('global', 'logonenvcreateuser'), 'boolean')) {
									// Create user when not existing yet
									// Important to add a random password here. When someone
									// changes the logon mechanism to e.g. LogonDialog it
									// would be possible to logon with a hardcoded password
									$this->AUTHENTICATION->createUser($sUser, (time() * rand(1, 10)));
								} else {
									new GlobalMessage('ERROR', $this->CORE->getLang()->getText('Unable to authenticate user. User does not exist.'));
								}
							}
							
							// Authenticate the user without providing logon information
							
							// Reset the authentication check. Without this the cached result
							// would prevent the authentication check with the given credentials
							$this->AUTHENTICATION->resetAuthCheck();
							
							// Set credentials to authenticate
							// Use dummy password - with empty or unset password the session
							// auth module would not authenticate the user
							$this->AUTHENTICATION->passCredentials(Array('user' => $sUser, 'password' => '.'));
							
							// Try to authenticate the user
							if($this->AUTHENTICATION->isAuthenticated(AUTH_TRUST_USERNAME)) {
								// Display success with link and refresh in 5 seconds to called page
								$FHANDLER = new CoreRequestHandler($_POST);
								
								// Redirect without message to the user
								//new GlobalMessage('NOTE', $this->CORE->getLang()->getText('You have been authenticated. You will be redirected.'), null, null, 1, $FHANDLER->getReferer());
								$ref = $FHANDLER->getRequestUri();
								if($ref === '') {
									$ref = $this->CORE->getMainCfg()->getValue('paths', 'htmlbase');
								}
								
								header('Location:'.$ref);
							} else {
								// Invalid credentials
								// FIXME: Count tries and maybe block somehow
								$FHANDLER = new CoreRequestHandler($_POST);
								new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid credentials.'), null, $this->CORE->getLang()->getText('Authentication failed'), 10, $FHANDLER->getReferer());
							}
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('Unable to authenticate user. The environment variable [VAR] is not set or empty.', Array('VAR' => htmlentities($sEnvVar))));
						}
					} else {
						// When the user is already authenticated redirect to start page (overview)
						header('Location:'.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					}
				break;
			}
		}
		
		return $sReturn;
	}
}

?>