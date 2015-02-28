<?php
/*****************************************************************************
 *
 * FrontendModMap.php - Module for handling the maps in NagVis
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
class FrontendModMap extends FrontendModule {
    private $name = '';
    private $search = '';
    private $rotation = '';
    private $rotationStep = '';
    private $perm = '';

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'Map';
        $this->CORE  = $CORE;

        // Parse the view specific options
        $aOpts = Array(
            'show'          => MATCH_MAP_NAME_EMPTY,
            'search'        => MATCH_STRING_NO_SPACE_EMPTY,
            'rotation'      => MATCH_ROTATION_NAME_EMPTY,
            'rotationStep'  => MATCH_INTEGER_EMPTY,
            'perm'          => MATCH_BOOLEAN_EMPTY,
        );

        // There might be a default map when none is given
        $aDefaults = Array('show' => cfg('global', 'startshow'));

        // getCustomOptions fetches and validates the values
        $aVals = $this->getCustomOptions($aOpts, $aDefaults);
        $this->name         = $aVals['show'];
        $this->search       = $aVals['search'];
        $this->rotation     = $aVals['rotation'];
        $this->rotationStep = $aVals['rotationStep'];
        $this->perm         = $aVals['perm'];

        // Register valid actions
        $this->aActions = Array(
            'view' => REQUIRES_AUTHORISATION,
            'edit' => REQUIRES_AUTHORISATION,
        );

        // Register valid objects
        $this->aObjects = $this->CORE->getAvailableMaps(null, SET_KEYS);

        // Set the requested object for later authorisation
        $this->setObject($this->name);
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'edit':
                case 'view':
                    // Show the view dialog to the user
                    $sReturn = $this->showViewDialog();
                break;
            }
        }

        return $sReturn;
    }

    private function showViewDialog() {
        global $AUTHORISATION;
        // Initialize map configuration
        $MAPCFG = new GlobalMapCfg($this->name);

        // Read the map configuration file (Only global section!)
        $MAPCFG->readMapConfig(ONLY_GLOBAL);

        // Get all source parameters
        $opts = $MAPCFG->getSourceParams();

        // Build index template
        $INDEX = new NagVisIndexView($this->CORE);

        // Need to load the custom stylesheet?
        $customStylesheet = $MAPCFG->getValue(0, 'stylesheet');
        if($customStylesheet !== '')
            $INDEX->setCustomStylesheet(path('html', 'global', 'styles', $customStylesheet));

        // Need to parse the header menu by config or url value?
        if(isset($opts['header_menu']) && $opts['header_menu']) {
            // Parse the header menu
            $HEADER = new NagVisHeaderMenu($this->UHANDLER, $MAPCFG->getValue(0 ,'header_template'), $MAPCFG);

            // Put rotation information to header menu
            if($this->rotation != '') {
            	$HEADER->setRotationEnabled();
            }

            $INDEX->setHeaderMenu($HEADER->__toString());
        }

        // Initialize map view
        $this->VIEW = new NagVisMapView($this->CORE, $this->name);
        $this->VIEW->setParams($opts);

        // The user is searching for an object
        $this->VIEW->setSearch($this->search);

        // Enable edit mode for all objects
        if($this->sAction == 'edit')
            $this->VIEW->setEditMode();

        // Maybe it is needed to handle the requested rotation
        if($this->rotation != '') {
            // Only allow the rotation if the user is permitted to use it
            if($AUTHORISATION->isPermitted('Rotation', 'view', $this->rotation)) {
                $ROTATION = new FrontendRotation($this->rotation);
                $ROTATION->setStep('map', $this->name, $this->rotationStep);
                $this->VIEW->setRotation($ROTATION->getRotationProperties());
            }
        }

        $INDEX->setContent($this->VIEW->parse());
        return $INDEX->parse();
    }
}
?>
