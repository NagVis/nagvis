<?php
/**
 * This class handles all authorisation tasks and is the glue between the
 * application and the different authorisation modules. It loads the
 * authentication module depending on the configuration. An authentication
 * module handles the information gathered from the webservers vars or the
 * frontend.
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreAuthHandler {
	private $CORE;
	private $SESS;
	private $MOD;
	
	private $sModuleName;
	private $bIsAuthenticated;
	
	public function __construct(GlobalCore $CORE, CoreSessionHandler $SESS, $sModule) {
		$this->CORE = $CORE;
		$this->SESS = $SESS;
		
		$this->sModuleName = $sModule;

		$this->MOD = new $sModule($CORE);
	}
	
	public function getModule() {
		return $this->sModuleName;
	}
	
	public function passCredentials($aData) {
		// Some simple validations
		if($aData !== false) {
			$this->MOD->passCredentials($aData);
		} else {
			//@todo: error handling
		}
	}
	
	private function getCredentials() {
		return $this->MOD->getCredentials();
	}
	
	public function getUser() {
		return $this->MOD->getUser();
	}
	
	public function getUserId() {
		return $this->MOD->getUserId();
	}
	
	public function isAuthenticated() {
		// Don't do these things twice
		if(!isset($this->bIsAuthenticated)) {
			// Ask the module
			$this->bIsAuthenticated = $this->MOD->isAuthenticated();
			
			// Save success to session (only if this is no session auth)
			if($this->bIsAuthenticated === true && $this->sModuleName != 'CoreAuthModSession') {
				$this->SESS->set('authCredentials', $this->getCredentials());
			}
			
			// Remove some maybe old data when not authenticated
			if($this->bIsAuthenticated === false && $this->SESS->isSetAndNotEmpty('authCredentials')) {
				// Remove session data
				$this->logout();
			}
		}
				
		return $this->bIsAuthenticated;
	}
	
	public function logout() {
		// Remove the login information
		$this->SESS->set('authCredentials', false);
		$this->SESS->set('userPermissions', false);
		
		return true;
	}
}
?>
