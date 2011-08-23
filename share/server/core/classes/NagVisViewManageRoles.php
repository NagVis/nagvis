<?php
/*****************************************************************************
 *
 * NagVisViewManageRoles.php - Dialog for managing roles and permissions
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
class NagVisViewManageRoles {
    private $CORE;
    private $AUTHORISATION;

    /**
     * Class Constructor
     *
     * @param 	GlobalCore 	$CORE
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct(CoreAuthorisationHandler $AUTHORISATION) {
        $this->CORE = GlobalCore::getInstance();
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
            'formTargetAdd' => $this->CORE->getMainCfg()->getValue('paths','htmlbase').'/server/core/ajax_handler.php?mod=RoleMgmt&amp;act=doAdd',
            'formTargetEdit' => $this->CORE->getMainCfg()->getValue('paths','htmlbase').'/server/core/ajax_handler.php?mod=RoleMgmt&amp;act=doEdit',
            'formTargetDelete' => $this->CORE->getMainCfg()->getValue('paths','htmlbase').'/server/core/ajax_handler.php?mod=RoleMgmt&amp;act=doDelete',
            'htmlImages' => $this->CORE->getMainCfg()->getValue('paths', 'htmlimages'),
      'maxRolenameLength' => AUTH_MAX_PASSWORD_LENGTH,
      'langRoleAdd' => l('Create Role'),
      'langRoleName' => l('Role Name'),
      'langSelectRole' => l('Select Role'),
      'langSetPermissions' => l('Set Permissions'),
      'langModule' => l('Module'),
      'langAction' => l('Action'),
      'langObject' => l('Object'),
      'langPermitted' => l('Permitted'),
      'langRoleModify' => l('Modify Role'),
      'langRoleDelete' => l('Delete Role'),
      'roles' => $this->AUTHORISATION->getAllRoles(),
      'perms' => $this->AUTHORISATION->getAllVisiblePerms()
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile('default', 'manageRoles'), $aData);
    }
}
?>