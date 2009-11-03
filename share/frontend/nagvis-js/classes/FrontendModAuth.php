<?php
class FrontendModAuth extends FrontendModule {
	private $MOD;
	private $AUTH;
	private $GHANDLER;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		$logonModule = $this->CORE->getMainCfg()->getValue('global', 'logonmodule');
		
		$this->aActions = Array('logout' => REQUIRES_AUTHORISATION,
		                        'login' => 0,
		                        'changePassword' => REQUIRES_AUTHORISATION);
		
		$SHANDLER = new CoreSessionHandler($this->CORE->getMainCfg()->getValue('global', 'sesscookiedomain'), 
		                                   $this->CORE->getMainCfg()->getValue('global', 'sesscookiepath'),
		                                   $this->CORE->getMainCfg()->getValue('global', 'sesscookieduration'));
		$this->AUTH = new CoreAuthHandler($this->CORE, $SHANDLER, $this->CORE->getMainCfg()->getValue('global', 'authmodule'));
		
		$this->GHANDLER = new FrontendRequestHandler($_GET);
	}
	
	public function passCredentials($aCredentials) {
		return $this->AUTH->passCredentials($aCredentials);
	}
	
	public function passNewPassword($aCredentials) {
		return $this->AUTH->passNewPassword($aCredentials);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'login':
					if($this->AUTH->isAuthenticated()) {
						// Display success with link and refresh in 5 seconds to called page
						$sReturn = $this->msgAuthenticated();
					} else {
						// Invalid credentials
						$sReturn = $this->msgInvalidCredentials();
					}
				break;
				case 'changePassword':
					if($this->AUTH->changePassword()) {
						// Display success with link and refresh in 5 seconds to called page
						$sReturn = $this->msgPasswordChanged();
					} else {
						// Invalid credentials
						$sReturn = $this->msgPasswordNotChanged();
					}
				break;
				case 'logout':
					$this->AUTH->logout();
					
					// Redirect to main page
					Header('Location: '.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					exit(0);
				break;
			}
		}
		
		return $sReturn;
	}
	
	public function msgAuthenticated() {
		new GlobalMessage('NOTE', $this->CORE->getLang()->getText('You have been authenticated. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->GHANDLER->getReferer())), null, null, 1, $this->GHANDLER->getReferer());
		
		return '';
	}
	
	public function msgInvalidCredentials() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid credentials. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->GHANDLER->getReferer())), null, $this->CORE->getLang()->getText('Authentication failed'), 1, $this->GHANDLER->getReferer());
		
		return '';
	}
	
	public function msgPasswordChanged() {
		new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The password has been changed. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->GHANDLER->getReferer())), null, null, 1, $this->GHANDLER->getReferer());
		
		return '';
	}
	
	public function msgPasswordNotChanged() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The password could not be changed. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->GHANDLER->getReferer())), null, null, 1, $this->GHANDLER->getReferer());
		
		return '';
	}
}
?>
