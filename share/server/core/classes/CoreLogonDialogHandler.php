<?php
/*****************************************************************************
 *
 * CoreLogonDialogHandler.php - This module handles the submission of the
 *                              login form
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

class CoreLogonDialogHandler {
    public function check($printErr = true) {
        global $AUTH;

        $err = null;
        try {
            $data = $this->handleResponseAuth();
            if($data !== null) {
                // Set credentials to authenticate
                $AUTH->setTrustUsername(false);
                $AUTH->setLogoutPossible(true);
                $AUTH->passCredentials($data);

                // Try to authenticate the user
                $result = $AUTH->isAuthenticated();
                if($result === true) {
                    // Success: Store in session
                    $AUTH->storeInSession();
                    return true;
                } else {
                    throw new FieldInputError(null, l('Authentication failed.'));
                }
            }
        } catch(FieldInputError $e) {
            $err = $e;
        }

        // Failed!
        if(!CONST_AJAX) {
            return array('LogonDialog', 'view', $err);
        } else {
            throw new NagVisException(l('You are not authenticated'), null, l('Access denied'));
        }
    }

    private function handleResponseAuth() {
        $attr = Array('username' => MATCH_USER_NAME,
                      'password' => null);

        $FHANDLER = new CoreRequestHandler($_POST);

        if(!$FHANDLER->issetAndNotEmpty('username') && !$FHANDLER->issetAndNotEmpty('password'))
            return null;

        if(!$FHANDLER->match('username', MATCH_USER_NAME)
           || $FHANDLER->isLongerThan('username', AUTH_MAX_USERNAME_LENGTH))
            throw new FieldInputError('username', l('Invalid username.'));

        if(!$FHANDLER->issetAndNotEmpty('password')
           || $FHANDLER->isLongerThan('password', AUTH_MAX_PASSWORD_LENGTH))
            throw new FieldInputError('password', l('Invalid password.'));

        return Array('user'     => $FHANDLER->get('username'),
                     'password' => $FHANDLER->get('password'));
    }
}
?>
