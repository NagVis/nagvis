<?php
/*******************************************************************************
 *
 * CoreAuthModFile.php - Authentication module for session based authentication
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
 * Checks if there are authentication information stored in a session
 * If so it tries to reuse the stored information
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreAuthModSession extends CoreAuthModule {
	private $CORE;
	private $SHANDLER;
	private $iUserId = -1;
	private $sUsername = '';
	private $sPasswordHash = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		$this->SHANDLER = new CoreSessionHandler($this->CORE->getMainCfg()->getValue('global', 'sesscookiedomain'), 
		                                         $this->CORE->getMainCfg()->getValue('global', 'sesscookiepath'),
		                                         $this->CORE->getMainCfg()->getValue('global', 'sesscookieduration'));
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
			$AUTH = new CoreAuthHandler($this->CORE, $this->SHANDLER, $this->CORE->getMainCfg()->getValue('global','authmodule'));
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
