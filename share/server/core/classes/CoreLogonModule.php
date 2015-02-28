<?php
/*****************************************************************************
 *
 * CoreLogonModule.php - Implements some common used code which is used by
 *                       several different logon modules.
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

class CoreLogonModule {
    // Create user when not existing yet
    // Important to add a random password here. When someone
    // changes the logon mechanism to e.g. LogonDialog it
    // would be possible to logon with a hardcoded password
    protected function createUser($username, $role) {
        global $AUTH;
        $AUTH->createUser($username, (time() * rand(1, 10)));
        if($role !== '') {
            $A = new CoreAuthorisationHandler();
            $A->parsePermissions();
            $A->updateUserRoles($A->getUserId($username), Array($A->getRoleId($role)));
        }
    }

    protected function verifyUserExists($username, $createUser, $createRole, $printErr) {
        global $AUTH;
        if(!$AUTH->checkUserExists($username)) {
            settype($createUser, 'boolean');
            if($createUser === true) {
                $this->createUser($username, $createRole);
            } else {
                if($printErr) {
                    throw new NagVisException(l('Unable to authenticate user. User does not exist.'));
                }
                return false;
            }
        }
        return true;
    }
}
?>
