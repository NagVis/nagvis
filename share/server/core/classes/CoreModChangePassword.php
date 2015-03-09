<?php
/*****************************************************************************
 *
 * CoreModChangePassword.php - This module handles the password changes of
 *                             the users when using the internal auth db
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
class CoreModChangePassword extends CoreModule {
    protected $CORE;
    protected $FHANDLER;
    protected $SHANDLER;

    public function __construct($CORE) {
        $this->sName = 'ChangePassword';
        $this->CORE = $CORE;

        $this->aActions = Array(
            'view'   => 'change',
            'change' => REQUIRES_AUTHORISATION
        );

        $this->FHANDLER = new CoreRequestHandler($_POST);
    }

    public function handleAction() {
        global $AUTH;
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                // The best place for this would be a FrontendModule but this needs to
                // be in CoreModule cause it is fetched via ajax. The error messages
                // would be printed in HTML format in nagvis-js frontend.
                case 'view':
                    // Check if user is already authenticated
                    // Change password must be denied when using trusted mode
                    if($AUTH->isAuthenticated() && !$AUTH->authedTrusted()) {
                        $VIEW = new NagVisViewChangePassword($this->CORE);
                        $sReturn = json_encode(Array('code' => $VIEW->parse()));
                    } else {
                        $sReturn = '';
                    }
                break;
                case 'change':
                    // Check if user is already authenticated
                    // Change password must be denied when using trusted mode
                    if($AUTH->isAuthenticated() && !$AUTH->authedTrusted()) {
                        $aReturn = $this->handleResponseChangePassword();

                        if($aReturn !== false) {
                            // Set new passwords in authentication module
                            $AUTH->passNewPassword($aReturn);

                            // Try to apply the changes
                            if($AUTH->changePassword()) {
                                throw new Success(l('The password has been changed.'));
                            } else {
                                // Invalid credentials
                                $sReturn = $this->msgPasswordNotChanged();
                            }
                        } else {
                            $sReturn = $this->msgInputNotValid();
                        }
                    } else {
                        // When the user is not authenticated redirect to start page (overview)
                        Header('Location:'.cfg('paths', 'htmlbase'));
                    }
                break;
            }
        }

        return $sReturn;
    }

    private function handleResponseChangePassword() {
        global $AUTH;
        $bValid = true;
        // Validate the response

        // Check for needed params
        if($bValid && !$this->FHANDLER->isSetAndNotEmpty('passwordOld'))
            $bValid = false;
        if($bValid && !$this->FHANDLER->isSetAndNotEmpty('passwordNew1'))
            $bValid = false;
        if($bValid && !$this->FHANDLER->isSetAndNotEmpty('passwordNew2'))
            $bValid = false;

        // Check length limits
        if($bValid && $this->FHANDLER->isLongerThan('passwordOld', AUTH_MAX_PASSWORD_LENGTH))
            $bValid = false;
        if($bValid && $this->FHANDLER->isLongerThan('passwordNew1', AUTH_MAX_PASSWORD_LENGTH))
            $bValid = false;
        if($bValid && $this->FHANDLER->isLongerThan('passwordNew2', AUTH_MAX_PASSWORD_LENGTH))
            $bValid = false;

        // Check if new passwords are equal
        if($bValid && $this->FHANDLER->get('passwordNew1') !== $this->FHANDLER->get('passwordNew2'))
            throw new NagVisException(l('The two new passwords are not equal.'));

        // Check if old and new passwords are equal
        if($bValid && $this->FHANDLER->get('passwordOld') === $this->FHANDLER->get('passwordNew1'))
            throw new NagVisException(l('The new and old passwords are equal. Won\'t change anything.'));

        //@todo Escape vars?

      // Store response data
      if($bValid === true)
          return Array('user'        => $AUTH->getUser(),
                       'password'    => $this->FHANDLER->get('passwordOld'),
                       'passwordNew' => $this->FHANDLER->get('passwordNew1'));
        else
            return false;
    }

    public function msgInputNotValid() {
        throw new NagVisException(l('You entered invalid information.'));
    }

    public function msgPasswordNotChanged() {
        throw new NagVisException(l('The password could not be changed.'));
    }
}
?>
