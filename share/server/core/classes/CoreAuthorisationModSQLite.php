<?php
/*******************************************************************************
 *
 * CoreAuthorisationModSQLite.php - Authorsiation module based on SQLite
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
class CoreAuthorisationModSQLite extends CoreAuthorisationModule {
	private $AUTHENTICATION = null;
	private $CORE = null;
	private $DB = null;
	
	public function __construct(GlobalCore $CORE, CoreAuthHandler $AUTHENTICATION) {
		$this->AUTHENTICATION = $AUTHENTICATION;
		$this->CORE = $CORE;
		
		$this->DB = new CoreSQLiteHandler();
		
		// Open sqlite database
		if(!$this->DB->open($this->CORE->getMainCfg()->getValue('paths', 'cfg').'auth.db')) {
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
