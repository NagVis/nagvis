<?php
/*****************************************************************************
 *
 * CoreLogonDialogHandler.php - This module handles the submission of the
 *                              login form
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
                    if(!isset($data['onetime'])) {
                        // Success: Store in session
                        $AUTH->storeInSession();
                    }
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
        $FHANDLER = new CoreRequestHandler(array_merge($_GET, $_POST));

        // Don't try to auth if one of the vars is missing
        if(!$FHANDLER->issetAndNotEmpty('_username')
           || !$FHANDLER->issetAndNotEmpty('_password'))
            return null;

        if(!$FHANDLER->match('_username', MATCH_USER_NAME)
           || $FHANDLER->isLongerThan('_username', AUTH_MAX_USERNAME_LENGTH))
            throw new FieldInputError('_username', l('Invalid username.'));

        if(!$FHANDLER->issetAndNotEmpty('_password')
           || $FHANDLER->isLongerThan('_password', AUTH_MAX_PASSWORD_LENGTH))
            throw new FieldInputError('_password', l('Invalid password.'));
        
        $a = Array('user'     => $FHANDLER->get('_username'),
                   'password' => $FHANDLER->get('_password'));

        // It is possible to only request onetime access to prevent getting added
        // and authentication cookie
        if(isset($_REQUEST['_onetime'])) {
            $a['onetime'] = true;
        }
    
        // Remove authentication infos. Hide it from the following code
        if(isset($_REQUEST['_username']))
            unset($_REQUEST['_username']);
        if(isset($_REQUEST['_password']))
            unset($_REQUEST['_password']);
        if(isset($_POST['_username']))
            unset($_POST['_username']);
        if(isset($_POST['_password']))
            unset($_POST['_password']);
        if(isset($_GET['_username']))
            unset($_GET['_username']);
        if(isset($_GET['_password']))
            unset($_GET['_password']);

        return $a;
    }
}
?>
