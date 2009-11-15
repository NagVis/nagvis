<?php
/*****************************************************************************
 *
 * NagVisViewUserMgmt.php - User management dialog
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
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisViewUserMgmt {
	private $CORE;
	private $AUTHENTICATION;
	private $AUTHORISATION;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(CoreAuthHandler $AUTHENTICATION, CoreAuthorisationHandler $AUTHORISATION) {
		$this->CORE = GlobalCore::getInstance();
		$this->AUTHENTICATION = $AUTHENTICATION;
		$this->AUTHORISATION = $AUTHORISATION;
	}
	
	/**
	 * Parses the information in html format
	 *
	 * @return	String 	String with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parse() {
		// Initialize template system
		$TMPL = New CoreTemplateSystem($this->CORE);
		$TMPLSYS = $TMPL->getTmplSys();
		
		$aData = Array(
			'htmlBase' => $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'),
			'formTargetAdd' => $this->CORE->getMainCfg()->getValue('paths','htmlbase').'/server/core/ajax_handler.php?mod=UserMgmt&amp;act=doAdd',
			'formTargetEdit' => $this->CORE->getMainCfg()->getValue('paths','htmlbase').'/server/core/ajax_handler.php?mod=UserMgmt&amp;act=doEdit',
			'formTargetDelete' => $this->CORE->getMainCfg()->getValue('paths','htmlbase').'/server/core/ajax_handler.php?mod=UserMgmt&amp;act=doDelete',
			'htmlImages' => $this->CORE->getMainCfg()->getValue('paths', 'htmlimages'),
      'maxPasswordLength' => AUTH_MAX_PASSWORD_LENGTH,
      'maxUsernameLength' => AUTH_MAX_USERNAME_LENGTH,
      'langUsername' => $this->CORE->getLang()->getText('Username'),
      'langPassword1' => $this->CORE->getLang()->getText('Password'),
      'langPassword2' => $this->CORE->getLang()->getText('Password Confirm'),
      'langUserAdd' => $this->CORE->getLang()->getText('Create User'),
      'langUserModify' => $this->CORE->getLang()->getText('Modify User'),
      'langUserDelete' => $this->CORE->getLang()->getText('Delete User'),
      'langSelectUser' => $this->CORE->getLang()->getText('Select User'),
      'users' => $this->AUTHENTICATION->getAllUsers(),
      'langManageRoles' => $this->CORE->getLang()->getText('Modify Roles'),
      'langRolesAvailable' => $this->CORE->getLang()->getText('Available Roles'),
      'langRolesSelected' => $this->CORE->getLang()->getText('Selected Roles'),
      'langAdd' => $this->CORE->getLang()->getText('Add'),
      'langRemove' => $this->CORE->getLang()->getText('Remove'),
      'roles' => $this->AUTHORISATION->getAllRoles(),
		);
		
		// Build page based on the template file and the data array
		return $TMPLSYS->get($TMPL->getTmplFile('userMgmt'), $aData);
	}
}
?>
