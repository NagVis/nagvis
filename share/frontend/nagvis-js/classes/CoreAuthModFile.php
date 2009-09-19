<?php

class CoreAuthModFile extends CoreAuthModule {
	private $CORE;
	private $USERCFG;
	
	private $iUserId = -1;
	private $sUsername = '';
	private $sPassword = '';
	private $sPasswordHash = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		$this->USERCFG = new GlobalUserCfg($this->CORE, CONST_USERCFG);
	}
	
	public function passCredentials($aData) {
		if(isset($aData['user'])) {
			$this->sUsername = $aData['user'];
		}
		if(isset($aData['password'])) {
			$this->sPassword = $aData['password'];
		}
		if(isset($aData['passwordHash'])) {
			$this->sPasswordHash = $aData['passwordHash'];
		}
	}
	
	public function getCredentials() {
		return Array('user' => $this->sUsername,
		             'passwordHash' => $this->sPasswordHash,
		             'userId' => $this->iUserId);
	}
	
	public function isAuthenticated() {
		$bReturn = false;
	
		$aUsers = $this->USERCFG->getUsers();

		// Only handle known users with password set and not empty pas sword
		if($this->sUsername !== '' && isset($aUsers[$this->sUsername]) && $this->USERCFG->getValue($this->sUsername, 'password') !== null && $this->USERCFG->getValue($this->sUsername, 'password') !== '') {
			
			// Try to calculate the passowrd hash only when no hash is known at
			// this time. For example when the user just entered the password
			// for logging in. If the user is already logged in and this is just
			// a session check don't try to rehash the password.
			if($this->sPasswordHash === '') {
				// Compose the password hash for comparing with the stored hash
				$this->sPasswordHash = sha1(AUTH_PASSWORD_SALT.$this->sPassword);
			}
			
			// Check the password hash
			if($this->sPasswordHash === $this->USERCFG->getValue($this->sUsername, 'password')) {
				// Set the user id on success
				$this->iUserId = (integer) $this->USERCFG->getValue($this->sUsername, 'userId');
				
				//FIXME: Logging? Successfull authentication
				$bReturn = true;
			} else {
				//FIXME: Logging? Invalid password
				$bReturn = false;
			}
		} else {
			//FIXME: Logging? Invalid user
			$bReturn = false;
		}
		
		return $bReturn;
	}
	
	public function getUser() {
		return $this->sUsername;
	}
	
	public function getUserId() {
		return $this->iUserId;
	}
}
?>
