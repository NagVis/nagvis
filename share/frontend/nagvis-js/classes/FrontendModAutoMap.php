<?php
/*****************************************************************************
 *
 * FrontendModAutoMap.php - Module for handling the automap
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
class FrontendModAutoMap extends FrontendModule {
    private $name;
    private $opts;
    private $rotation = '';
    private $rotationStep = '';

    private $viewOpts = Array();

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'AutoMap';
        $this->CORE = $CORE;

        // Parse the view specific options
        $aOpts = Array('show' => MATCH_MAP_NAME,
                       'search' => MATCH_STRING_NO_SPACE_EMPTY,
                       'backend' => MATCH_STRING_NO_SPACE_EMPTY,
                       'root' => MATCH_STRING_NO_SPACE_EMPTY,
                       'childLayers' => MATCH_INTEGER_PRESIGN_EMPTY,
                       'parentLayers' => MATCH_INTEGER_PRESIGN_EMPTY,
                       'renderMode' => MATCH_AUTOMAP_RENDER_MODE,
                       'width' => MATCH_INTEGER_EMPTY,
                       'height' => MATCH_INTEGER_EMPTY,
                       'ignoreHosts' => MATCH_STRING_NO_SPACE_EMPTY,
                       'filterGroup' => MATCH_STRING_EMPTY,
                       'filterByState' => MATCH_STRING_NO_SPACE_EMPTY,
                       'rotation' => MATCH_ROTATION_NAME_EMPTY,
                       'rotationStep' => MATCH_INTEGER_EMPTY,
                       'enableHeader' => MATCH_BOOLEAN_EMPTY,
                       'enableContext' => MATCH_BOOLEAN_EMPTY,
                       'enableHover' => MATCH_BOOLEAN_EMPTY,
                       'perm'          => MATCH_BOOLEAN_EMPTY);

        // There might be a default map when none is given
        $aDefaults = Array('show' => cfg('global', 'startshow'));

        // getCustomOptions fetches and validates the values
        $aVals = $this->getCustomOptions($aOpts, $aDefaults);
        $this->name = $aVals['show'];
        $this->rotation = $aVals['rotation'];
        $this->rotationStep = $aVals['rotationStep'];

        $this->viewOpts['search'] = $aVals['search'];
        $this->viewOpts['enableHeader'] = $aVals['enableHeader'];
        $this->viewOpts['enableContext'] = $aVals['enableContext'];
        $this->viewOpts['enableHover'] = $aVals['enableHover'];

        unset($aVals['show']);
        unset($aVals['search']);
        unset($aVals['rotation']);
        unset($aVals['enableHeader']);
        unset($aVals['enableContext']);
        unset($aVals['enableHover']);

        $this->opts = $aVals;

        // Register valid actions
        $this->aActions = Array(
            'view' => REQUIRES_AUTHORISATION
        );

        // Register valid objects
        $this->aObjects = $this->CORE->getAvailableAutomaps(null, SET_KEYS);

        // Set the requested object for later authorisation
        $this->setObject($this->name);
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'view':
                    // Show the view dialog to the user
                    $sReturn = $this->showViewDialog();
                break;
            }
        }

        return $sReturn;
    }

    private function saveDefaultParams($MAPCFG) {
        $s = '';
        foreach($this->opts AS $key => $val)
            if($key !== 'perm')
                $s .= '&'.$key.'='.$val;

        $MAPCFG->setValue(0, 'default_params', $s);
        $MAPCFG->storeUpdateElement(0);
    }

    private function showViewDialog() {
        global $AUTHORISATION;

        // Initialize backend(s)
        $BACKEND = new CoreBackendMgmt($this->CORE);

        // Initialize map configuration
        $MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
        // Read the map configuration file
        $MAPCFG->readMapConfig();

        // When 'perm' is set save the default_params
        if($this->opts['perm'] === '1' && $AUTHORISATION->isPermitted('AutoMap', 'edit', $this->name))
            $this->saveDefaultParams($MAPCFG);
        unset($this->opts['perm']);

        // Build index template
        $INDEX = new NagVisIndexView($this->CORE);

        // Need to load the custom stylesheet?
        $customStylesheet = $MAPCFG->getValue(0, 'stylesheet');
        if($customStylesheet !== '')
            $INDEX->setCustomStylesheet($this->CORE->getMainCfg()->getPath('sys', 'global', 'styles', $customStylesheet));

        // Header menu enabled/disabled by url?
        if($this->viewOpts['enableHeader'] !== false && $this->viewOpts['enableHeader']) {
            $showHeader = true;
        } elseif($this->viewOpts['enableHeader'] !== false && !$this->viewOpts['enableHeader']) {
            $showHeader = false;
        } else {
            $showHeader = $MAPCFG->getValue(0 ,'header_menu');
        }

        // Need to parse the header menu?
        if($showHeader) {
            // Parse the header menu
            $HEADER = new NagVisHeaderMenu($this->CORE, $this->UHANDLER, $MAPCFG->getValue(0, 'header_template'), $MAPCFG);

            // Put rotation information to header menu
            if($this->rotation != '') {
                $HEADER->setRotationEnabled();
            }

            $INDEX->setHeaderMenu($HEADER->__toString());
        }

        // Initialize map view
        $this->VIEW = new NagVisAutoMapView($this->CORE, $MAPCFG->getName());

        // Set view modificators (Hover, Context toggle)
        $this->VIEW->setViewOpts($this->viewOpts);

        // Render the automap
        $AUTOMAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $this->opts, IS_VIEW);
        $this->VIEW->setContent($AUTOMAP->parseMap());
        $this->VIEW->setAutomapParams($this->opts);

        // Maybe it is needed to handle the requested rotation
        if($this->rotation != '') {
            // Only allow the rotation if the user is permitted to use it
            if($AUTHORISATION->isPermitted('Rotation', 'view', $this->rotation)) {
                $ROTATION = new FrontendRotation($this->CORE, $this->rotation);
                $ROTATION->setStep('automap', $this->name, $this->rotationStep);
                $this->VIEW->setRotation($ROTATION->getRotationProperties());
            }
        }

    $INDEX->setContent($this->VIEW->parse());

        return $INDEX->parse();
    }
}
?>
