<?php
/*******************************************************************************
 *
 * CoreSessionHandler.php - Class to handle PHP session data
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 ******************************************************************************/

/**
 * Class for handlin the PHP sessions. The sessions are used to store
 * information between loading different pages.
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreSessionHandler {
		
	public function __construct() {
		$sDomain = GlobalCore::getInstance()->getMainCfg()->getValue('global', 'sesscookiedomain');
		$sPath = GlobalCore::getInstance()->getMainCfg()->getValue('global', 'sesscookiepath');
		$iDuration = GlobalCore::getInstance()->getMainCfg()->getValue('global', 'sesscookieduration');
		
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
