<?php
/*****************************************************************************
 *
 * CoreLogonMixed.php - Module for handling mixed logins. Uses LogonEnv
 *                             as default and LogonDialog as fallback.
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

class CoreLogonMixed {
    public function check() {
        // Try to auth using the environment auth
        $ENV= new CoreLogonEnv();
        if($ENV->check(false) === true) {
            return true;
        }

        // Check if there were some auth data submitted
        $DIALOG = new CoreLogonDialogHandler();
        return $DIALOG->check(false);
    }
}

?>
