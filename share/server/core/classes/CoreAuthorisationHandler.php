<?php
/**
 * This class handles all authorisation tasks and is the glue between the
 * application and the different authorisation modules.
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreAuthorisationHandler {
	private $sModuleName = '';
	private $aPermissions = Array();
	
	private $CORE;
	private $MOD;
	
	public function __construct(GlobalCore $CORE, CoreAuthHandler $AUTH, $sModule) {
		$this->sModuleName = $sModule;
		
		$this->CORE = $CORE;
		$this->MOD = new $sModule($this->CORE, $AUTH);
	}
	
	public function getModule() {
		return $this->sModuleName;
	}
	
	public function parsePermissions() {
		$this->aPermissions = $this->MOD->parsePermissions();
		
		return $this->aPermissions;
	}
	
	public function isPermitted($sModule, $sAction) {
		// Checks if the users has the permission for the action
		if(
		   // User is permitted to access the module by explicit permission
		   (isset($this->aPermissions[$sModule]) &&
		   // User is permitted to do this action explicit or has a wildcard for all actions of this module
		   (isset($this->aPermissions[$sModule][$sAction]) || isset($this->aPermissions[$sModule][AUTH_PERMISSION_WILDCARD])))
		   ||
		   // Or check if the user is permitted to access the module by wildcard permission
		   (isset($this->aPermissions[AUTH_PERMISSION_WILDCARD]) &&
		   // User is permitted to do this action by wildcard or has a wildcard for all actions of all modules
		   (isset($this->aPermissions[AUTH_PERMISSION_WILDCARD][$sAction]) || isset($this->aPermissions[AUTH_PERMISSION_WILDCARD][AUTH_PERMISSION_WILDCARD])))
		  ) {
			return true;
		} else {
			//FIXME: Logging!
			return false;
		}
	}
}
?>
