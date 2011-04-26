<?php
/*****************************************************************************
 *
 * FrontendModLogonMixed.php - Module for handling mixed logins. Uses LogonEnv
 *                             as default and LogonDialog as fallback.
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
class FrontendModLogonMixed extends FrontendModule {
    protected $CORE;

    public function __construct($CORE) {
        $this->sName = 'LogonMixed';
        $this->CORE = $CORE;

        $this->aActions = Array('view' => 0);
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'view':
                    // Register own module handler to handle both logon modules
                    $MHANDLER = new FrontendModuleHandler($this->CORE);
                    $MHANDLER->regModule('LogonEnv');
                    $MHANDLER->regModule('LogonDialog');

                    $MODULE = $MHANDLER->loadModule('LogonEnv');
                    $MODULE->beQuiet();
                    $MODULE->setAction('view');

                    // Try to auth using the environment auth
                    if($MODULE->handleAction() === false) {
                        // Otherwise print the logon dialog
                        $MODULE =  $MHANDLER->loadModule('LogonDialog');
                        $MODULE->setAction('view');
                        $sReturn = $MODULE->handleAction();
                    }
                break;
            }
        }

        return $sReturn;
    }
}

?>