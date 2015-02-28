<?php
/*****************************************************************************
 *
 * CoreModLogonEnv.php - Module for handling logins by environment vars
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

class CoreLogonEnv extends CoreLogonModule {
    public function check($printErr = true) {
        global $AUTH, $CORE;

        // Get environment variable to use
        $envVar = cfg('global', 'logonenvvar');

        // Check if the variable exists and is not empty
        if(!isset($_SERVER[$envVar]) || $_SERVER[$envVar] === '') {
            if($printErr) {
                throw new NagVisException(l('Unable to authenticate user. The environment variable [VAR] is not set or empty.',
                                            Array('VAR' => htmlentities($envVar, ENT_COMPAT, 'UTF-8'))));
            }

            return false;
        }

        // Authenticate the user without providing logon information
        $username = $_SERVER[$envVar];

        // Check if the user exists
        if($this->verifyUserExists($username,
                        cfg('global', 'logonenvcreateuser'),
                        cfg('global', 'logonenvcreaterole'),
                        $printErr) === false) {
            return false;
        }

        $AUTH->setTrustUsername(true);
        $AUTH->setLogoutPossible(false);
        $AUTH->passCredentials(Array('user' => $username));
        return $AUTH->isAuthenticated();
   }
}
?>
