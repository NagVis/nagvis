<?php
/*****************************************************************************
 *
 * CoreModChangePassword.php - This module handles the password changes of
 *                             the users when using the internal auth db
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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
 * @author  Lars Michelsen <lm@larsmichelsen.com>
 */
class CoreModChangePassword extends CoreModule {
    public function __construct($CORE) {
        $this->sName = 'ChangePassword';

        $this->aActions = Array(
            'view'   => REQUIRES_AUTHORISATION,
        );
    }

    public function handleAction() {
        global $AUTH;
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'view':
                    // Check if user is already authenticated
                    // Change password must be denied when using trusted mode
                    if($AUTH->isAuthenticated() && !$AUTH->authedTrusted()) {
                        $VIEW = new ViewChangePassword();
                        $sReturn = json_encode(Array('code' => $VIEW->parse()));
                    } else {
                        $sReturn = '';
                    }
                break;
            }
        }

        return $sReturn;
    }
}
?>
