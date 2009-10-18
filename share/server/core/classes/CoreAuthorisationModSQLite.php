<?php

class CoreAuthorisationModSQLite extends CoreAuthorisationModule {
	private $AUTHENTICATION = null;
	private $CORE = null;
	private $DB = null;
	
	public function __construct(GlobalCore $CORE, CoreAuthHandler $AUTHENTICATION) {
		$this->AUTHENTICATION = $AUTHENTICATION;
		$this->CORE = $CORE;
		
		$this->DB = new CoreSQLiteHandler();
		
		// Open sqlite database
		if(!$this->DB->open($this->CORE->MAINCFG->getValue('paths', 'cfg').'auth.db')) {
			// FIXME: Errorhandling
		}
	}
	
	public function parsePermissions() {
		$aPerms = Array();
		
		$sUsername = $this->AUTHENTICATION->getUser();
		
		// Only handle known users
		$userId = $this->checkUserExists($sUsername);
		if($userId > 0) {
		  // Get all the roles of the user
		  $RES = $this->DB->query('SELECT perms.mod AS mod, perms.act AS act, perms.obj AS obj '.
		                          'FROM users2roles '.
		                          'INNER JOIN roles2perms ON roles2perms.roleId = users2roles.roleId '.
		                          'INNER JOIN perms ON perms.permId = roles2perms.permId '.
		                          'WHERE users2roles.userId = \''.sqlite_escape_string($userId).'\'');
		  
			while($data = $this->DB->fetchAssoc($RES)) {
				if(!isset($aPerms[$data['mod']])) {
					$aPerms[$data['mod']] = Array();
				}
				
				if(!isset($aPerms[$data['mod']][$data['act']])) {
					$aPerms[$data['mod']][$data['act']] = Array();
				}
				
				if(!isset($aPerms[$data['mod']][$data['act']][$data['obj']])) {
					$aPerms[$data['mod']][$data['act']][$data['obj']] = Array();
				}
			}
		}
		
		return $aPerms;
	}
	
	private function checkUserExists($sUsername) {
		$RES = $this->DB->query('SELECT userId FROM users WHERE name=\''.sqlite_escape_string($sUsername).'\'');
		return intval($RES->fetchSingle());
	}
}
?>
