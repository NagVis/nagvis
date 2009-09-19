<?php

/**
 * Checks if there are authentication information stored in a session
 * If so it tries to reuse the stored information
 */
class CoreAuthModSession extends CoreAuthModule {
	private $CORE;
	private $SHANDLER;
	private $iUserId = -1;
	private $sUsername = '';
	private $sPasswordHash = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		$this->SHANDLER = new CoreSessionHandler($CORE);
	}
	
	public function passCredentials($aData) {}
	
	public function getCredentials() { return Array(); }
	
	public function isAuthenticated() {
		// Are the options set?
		if($this->SHANDLER->isSetAndNotEmpty('authCredentials')) {
			$aCredentials = $this->SHANDLER->get('authCredentials');
			
			if(isset($aCredentials['user'])) {
				$this->sUsername = $aCredentials['user'];
			}
			if(isset($aCredentials['passwordHash'])) {
				$this->sPasswordHash = $aCredentials['passwordHash'];
			}
			if(isset($aCredentials['userId'])) {
				$this->iUserId = $aCredentials['userId'];
			}
			
			// Validate data
			$AUTH = new CoreAuthHandler($this->CORE, $this->SHANDLER, $this->CORE->MAINCFG->getValue('global','authmodule'));
			$AUTH->passCredentials($aCredentials);
			
			if($AUTH->isAuthenticated()) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function getUser() {
		return $this->sUsername;
	}
	
	public function getUserId() {
		return $this->iUserId;
	}
}

?>
