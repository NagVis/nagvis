<?php

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
