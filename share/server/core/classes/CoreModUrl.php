<?php
/*******************************************************************************
 *
 * CoreModUrl.php - Core module to handle ajax requests for urls
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
class CoreModUrl extends CoreModule {
    private $url = null;

    public function __construct(GlobalCore $CORE) {
        $this->CORE = $CORE;

        $aOpts = Array('show' => MATCH_STRING_URL);
        $aVals = $this->getCustomOptions($aOpts);
        $this->url = $aVals['show'];

        // Register valid actions
        $this->aActions = Array(
            'getContents'   => 'view',
            'getProperties' => 'view',
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'getContents':
                    $sReturn = $this->getContents();
                break;
                case 'getProperties':
                    $sReturn = $this->getProperties();
                break;
            }
        }

        return $sReturn;
    }

    private function getContents() {
        $URL = new NagVisUrl($this->CORE, $this->url);
        return json_encode(Array('content' => $URL->getContents()));
    }

    private function getProperties() {
        $URL = new NagVisUrl($this->CORE, $this->url);
        return $URL->parsePropertiesJson();
    }
}
?>
