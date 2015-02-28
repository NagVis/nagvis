<?php
/*******************************************************************************
 *
 * CoreModSearch.php - Core module to display the map object search dialog
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModSearch extends CoreModule {
    protected $CORE;

    public function __construct($CORE) {
        $this->sName = 'Search';
        $this->CORE = $CORE;

        $this->aActions = Array('view' => REQUIRES_AUTHORISATION);
    }

    public function handleAction() {
        global $AUTH;
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'view':
                    // Check if user is already authenticated
                    if($AUTH->isAuthenticated()) {
                        $VIEW = new NagVisViewSearch($this->CORE);
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
