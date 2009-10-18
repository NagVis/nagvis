<?php
/*******************************************************************************
 *
 * CoreAuthorisationModFile.php - Authorsiation module based on simple files
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
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreAuthorisationModFile extends CoreAuthorisationModule {
	private $AUTHENTICATION;
	private $CORE;
	private $USERCFG;
	
	public function __construct(GlobalCore $CORE, CoreAuthHandler $AUTHENTICATION) {
		$this->AUTHENTICATION = $AUTHENTICATION;
		$this->CORE = $CORE;
		
		$this->USERCFG = new GlobalUserCfg($this->CORE, CONST_USERCFG);
	}
	
	public function parsePermissions() {
		$aPerms = Array();
		
		$sUsername = $this->AUTHENTICATION->getUser();
		$aUsers = $this->USERCFG->getUsers();
		
		// Only handle known users
		if(
		   // User is valid
		   isset($aUsers[$sUsername])
		   // User has roles
		   && $this->USERCFG->getValue($sUsername, 'roles') !== null && is_array($this->USERCFG->getValue($sUsername, 'roles'))
		  ) {
		  
		  // Get all the roles of the user
			$aRoles = $this->USERCFG->getValue($sUsername, 'roles');
			
			// Get all the permissions of the available roles
			// FIXME: Authorisation parsing
			//$aRolePerms = $this->CONF->get('authorisationRoles');
			
			// Loop all users roles to merge the permissions
			/*foreach($aRoles AS $sRole) {
				if(isset($aRolePerms[$sRole]) && is_array($aRolePerms[$sRole])) {
					$aPerms = array_merge_recursive($aPerms, $aRolePerms[$sRole]);
				}
			}*/
			$aPerms['*']['*'] = '';
		}
		
		return $aPerms;
	}
}
?>
