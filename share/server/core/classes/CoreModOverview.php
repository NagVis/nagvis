<?php
/*******************************************************************************
 *
 * CoreModOverview.php - Core Overview module to handle ajax requests
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModOverview extends CoreModule {
    public function __construct(GlobalCore $CORE) {
        $this->sName = 'Overview';
        $this->CORE = $CORE;

        $this->aActions = Array(
            'getOverviewProperties' => 'view',
            'getOverviewMaps'       => 'view',
            'getOverviewRotations'  => 'view',
            'getObjectStates'       => 'view',
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            $OVERVIEW = new GlobalIndexPage();

            switch($this->sAction) {
                case 'getOverviewProperties':
                    $sReturn = $OVERVIEW->parseIndexPropertiesJson();
                break;
                case 'getOverviewMaps':
                    $sReturn = $OVERVIEW->parseMapsJson('map');
                break;
                case 'getOverviewRotations':
                    $sReturn = $OVERVIEW->parseRotationsJson();
                break;
                case 'getObjectStates':
                    $aOpts = Array(
                        'i' => MATCH_STRING_NO_SPACE,
                        'f' => MATCH_STRING_NO_SPACE_EMPTY,
                    );
                    $aVals = $this->getCustomOptions($aOpts);

                    // Is this request asked to check file ages?
                    if(isset($aVals['f']) && isset($aVals['f'][0])) {
                        $result = $this->checkFilesChanged($aVals['f']);
                        if($result !== null)
                            return $result;
                    }

                    $sReturn = $OVERVIEW->parseMapsJson('list', COMPLETE, $aVals['i']);
                break;
            }
        }

        return $sReturn;
    }
}
?>
