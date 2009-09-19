<?php
/**
 * Class for handlin the PHP sessions. The sessions are used to store
 * information between loading different pages.
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreSessionHandler {
	private $CORE;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$sDomain = $this->CORE->MAINCFG->getValue('global', 'sesscookiedomain');
		$sPath = $this->CORE->MAINCFG->getValue('global', 'sesscookiepath');
		$iDuration = $this->CORE->MAINCFG->getValue('global', 'sesscookieduration');
		
		// Set the session name (used in params/cookie names)
		session_name(SESSION_NAME);
		
		// Set custom params for the session cookie
		session_set_cookie_params($iDuration, $sPath, $sDomain);
		
		// Start a session for the user when not started yet
		if(!isset($_SESSION)) {
			session_start();
		}
		
		// Reset the expiration time upon page load
		if(isset($_COOKIE[SESSION_NAME])) {
			setcookie(SESSION_NAME, $_COOKIE[SESSION_NAME], time() + $iDuration, $sPath, $sDomain);
		}
	}
	
	public function isSetAndNotEmpty($sKey) {
		if(isset($_SESSION[$sKey]) && $_SESSION[$sKey] != '') {
			return true;
		} else {
			return false;
		}
	}
	
	public function get($sKey) {
		if(isset($_SESSION[$sKey])) {
			return $_SESSION[$sKey];
		} else {
			return false;
		}
	}
	
	public function set($sKey, $sVal) {
		if(isset($_SESSION[$sKey])) {
			$sOld = $_SESSION[$sKey];
		} else {
			$sOld = false;
		}
		
		if($sVal == false) {
			unset($_SESSION[$sKey]);
		} else {
			$_SESSION[$sKey] = $sVal;
		}
		
		return $sOld;
	}
}

?>
