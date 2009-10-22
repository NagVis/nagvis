<?php
class FrontendModAuth extends FrontendModule {
	private $MOD;
	private $AUTH;
	private $GHANDLER;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		$logonModule = $this->CORE->MAINCFG->getValue('global', 'logonmodule');
		
		$this->aActions = Array('logout' => 0, 'login' => 0);
		
		$SHANDLER = new CoreSessionHandler($this->CORE->MAINCFG->getValue('global', 'sesscookiedomain'), 
		                                   $this->CORE->MAINCFG->getValue('global', 'sesscookiepath'),
		                                   $this->CORE->MAINCFG->getValue('global', 'sesscookieduration'));
		$this->AUTH = new CoreAuthHandler($this->CORE, $SHANDLER, $this->CORE->MAINCFG->getValue('global', 'authmodule'));
		
		$this->GHANDLER = new FrontendRequestHandler($_GET);
	}
	
	public function passCredentials($aCredentials) {
		return $this->AUTH->passCredentials($aCredentials);
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
				case 'logout':
					$this->AUTH->logout();
					
					// Redirect to main page
					Header('Location: '.$this->CORE->MAINCFG->getValue('paths', 'htmlbase'));
					exit(0);
				break;
			}
		}
		
		return $sReturn;
	}
	
	public function msgAuthenticated() {
		new GlobalMessage('NOTE', $this->CORE->LANG->getText('You have been authenticated. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->GHANDLER->getReferer())), null, null, 1, $this->GHANDLER->getReferer());
		
		return '';
	}
	
	public function msgInvalidCredentials() {
		new GlobalMessage('ERROR', $this->CORE->LANG->getText('You entered invalid credentials. You will be <a href="[refererUrl]">redirected</a>.', Array('refererUrl' => $this->GHANDLER->getReferer())), null, $this->CORE->LANG->getText('Authentication failed'), 1, $this->GHANDLER->getReferer());
		
		return '';
	}
}
?>
