<?php
/*****************************************************************************
 *
 * CoreModAuth.php - This module handles the user login and logout
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
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModAuth extends CoreModule {
    protected $CORE;
    protected $FHANDLER;

    public function __construct($CORE) {
        $this->sName = 'Auth';
        $this->CORE = $CORE;

        $this->aActions = Array('login'  => !REQUIRES_AUTHORISATION,
                                'logout' => REQUIRES_AUTHORISATION);

        $this->FHANDLER = new CoreRequestHandler($_POST);
    }

    public function check($printErr) {

    }

    public function handleAction() {
        global $AUTH;
        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'logout':
                    if($AUTH->logout())
                        throw new Success(l('You have been logged out. You will be redirected.'),
                                          l('Logged out'),
                                          1, cfg('paths', 'htmlbase'));
                    else
                        throw new NagVisException(l('Unable to log you out. Maybe it is not supported by your authentication module.'),
                                          null, 1, cfg('paths', 'htmlbase'));
                break;
            }
        }
    }

    private function handleResponseAuth() {
        $attr = Array('username' => MATCH_USER_NAME,
                      'password' => null);
        $this->verifyValuesSet($this->FHANDLER,   $attr);
        $this->verifyValuesMatch($this->FHANDLER, $attr);

        // Check length limits
        $bValid = true;
        if($bValid && $this->FHANDLER->isLongerThan('username', AUTH_MAX_USERNAME_LENGTH))
            $bValid = false;
        if($bValid && $this->FHANDLER->isLongerThan('password', AUTH_MAX_PASSWORD_LENGTH))
            $bValid = false;

        //@todo Escape vars?

        // Store response data
        if($bValid)
          return Array('user'     => $this->FHANDLER->get('username'),
                       'password' => $this->FHANDLER->get('password'));
        else
            return false;
    }

    public function msgAlreadyLoggedIn() {
        throw new NagVisException(l('You are already logged in. You will be redirected.'),
                          null, 1, cfg('paths', 'htmlbase'));
        return '';
    }

    public function msgInvalidCredentials() {
        throw new NagVisException(l('You entered invalid credentials.'),
                                  l('Authentication failed'),
                                  1, CoreRequestHandler::getReferer(''));
        return '';
    }
}

?>
