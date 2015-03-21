<?php
/*****************************************************************************
 *
 * NagVisOverviewView.php - Class for handling the map index page
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
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisOverviewView {
    public function __construct($CORE) {
    }

    /**
     * Parses the information for json
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        global $_MAINCFG, $CORE;
        // Initialize template system
        $TMPL    = new FrontendTemplateSystem();
        $TMPLSYS = $TMPL->getTmplSys();
        $USERCFG = new CoreUserCfg();

        $maps      = cfg('index', 'showmaps') == 1 ? $CORE->getListMaps() : array();
        $rotations = cfg('index', 'showrotations') == 1 ? array_keys($CORE->getPermittedRotationPools()) : array();

        $aData = Array(
            'generalProperties' => $_MAINCFG->parseGeneralProperties(),
            'workerProperties'  => $_MAINCFG->parseWorkerProperties(),
            'stateProperties'   => json_encode($_MAINCFG->getStateWeightJS()),
            'userProperties'    => $USERCFG->doGetAsJson(),
            'fileAges'          => json_encode(Array(
                'maincfg' => $_MAINCFG->getConfigFileAge(),
            )),
            'locales'           => json_encode(Array(
                // FIXME: Duplicated definitions in NagVisMapView.php and NagVisOverviewView.php
                'more items...' => l('more items...'),
                'Create Object' => l('Create Object'),
            )),
            'rotation_names'    => json_encode($rotations),
            'map_names'         => json_encode($maps),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'overview'), $aData);
    }
}
?>
