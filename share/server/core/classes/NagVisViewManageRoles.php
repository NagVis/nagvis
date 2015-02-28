<?php
/*****************************************************************************
 *
 * NagVisViewManageRoles.php - Dialog for managing roles and permissions
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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
    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        global $CORE, $AUTHORISATION;
        // Initialize template system
        $TMPL = New CoreTemplateSystem($CORE);
        $TMPLSYS = $TMPL->getTmplSys();

        // Delete permissions, which are not needed anymore when opening the
        // "manage roles" dialog. This could be done during usual page
        // processing, but would add overhead which is not really needed.
        $AUTHORISATION->cleanupPermissions();

        $aData = Array(
            'htmlBase' => cfg('paths', 'htmlbase'),
            'formTargetAdd' => cfg('paths','htmlbase').'/server/core/ajax_handler.php?mod=RoleMgmt&amp;act=doAdd',
            'formTargetEdit' => cfg('paths','htmlbase').'/server/core/ajax_handler.php?mod=RoleMgmt&amp;act=doEdit',
            'formTargetDelete' => cfg('paths','htmlbase').'/server/core/ajax_handler.php?mod=RoleMgmt&amp;act=doDelete',
            'htmlImages' => cfg('paths', 'htmlimages'),
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
            'roles' => $AUTHORISATION->getAllRoles(),
            'perms' => $AUTHORISATION->getAllVisiblePerms()
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'manageRoles'), $aData);
    }
}
?>
