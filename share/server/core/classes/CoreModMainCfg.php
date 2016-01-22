<?php
/*******************************************************************************
 *
 * CoreModMainCfg.php - Core Map module to handle ajax requests
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
 ******************************************************************************/

class CoreModMainCfg extends CoreModule {
    private $name = null;

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'MainCfg';
        $this->CORE = $CORE;

        // Register valid actions
        $this->aActions = Array(
            'edit'              => REQUIRES_AUTHORISATION,
            'manageBackends'    => 'edit',
            'doBackendEdit'     => 'edit',
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'edit':
                    $VIEW = new ViewEditMainCfg();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;

                case 'manageBackends':
                    $VIEW = new ViewManageBackends();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
            }
        }

        return $sReturn;
    }
}
?>
