<?php
/*******************************************************************************
 *
 * CoreModMap.php - Core Map module to handle ajax requests
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

/**
 * @author Lars Michelsen <lm@larsmichelsen.com>
 */
class CoreModMap extends CoreModule {
    private $name = null;

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'Map';
        $this->CORE = $CORE;
        $this->htmlBase = cfg('paths', 'htmlbase');

        // Register valid actions
        $this->aActions = Array(
            'getMapProperties'  => 'view',
            'getMapObjects'     => 'view',
            'getObjectStates'   => 'view',

            'manage'            => REQUIRES_AUTHORISATION,
            'doExportMap'       => 'edit',
            'addModify'         => 'edit',
            'modifyObject'      => 'edit',
            'deleteObject'      => 'edit',
            'toStaticMap'       => 'edit',
            'manageTmpl'        => 'edit',

            // Worldmap related
            'getWorldmapBounds' => 'view',
            'viewToNewMap'      => 'edit',
        );

        // Register valid objects
        $this->aObjects = $this->CORE->getAvailableMaps(null, SET_KEYS);
    }

    public function initObject() {
        switch($this->sAction) {
            // These have the object in GET var "show"
            case 'getMapProperties':
            case 'getMapObjects':
            case 'getObjectStates':
            case 'manageTmpl':
            case 'addModify':
            case 'doExportMap':
            case 'getWorldmapBounds':
                // When e.g. submitting the addModify form the show parameter is a POST variable
                $aVals = $this->getCustomOptions(Array('show' => MATCH_MAP_NAME_EMPTY), array(), true);
                $this->name = $aVals['show'];
            break;
            case 'toStaticMap':
            case 'viewToNewMap':
                $aVals = $this->getCustomOptions(Array('show' => MATCH_MAP_NAME_EMPTY), array(), true);
                $this->name = $aVals['show'];
            break;
            // And those have the objecs in the POST var "map"
            case 'modifyObject':
            case 'deleteObject':
                $FHANDLER = new CoreRequestHandler(array_merge($_GET, $_POST));
                if($FHANDLER->match('map', MATCH_MAP_NAME))
                    $this->name = $FHANDLER->get('map');
                else
                    throw new NagVisException(l('Invalid query. The parameter [NAME] is missing or has an invalid format.',
                                                Array('NAME' => 'map')));
            break;
        }

        // Set the requested object for later authorisation
        $this->setObject($this->name);
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'getMapProperties':
                    $MAPCFG = new GlobalMapCfg($this->name);
                    $MAPCFG->readMapConfig(ONLY_GLOBAL);
                    $sReturn = json_encode($MAPCFG->getMapProperties());
                break;
                case 'getMapObjects':
                    $sReturn = $this->getMapObjects();
                break;
                case 'getObjectStates':
                    $sReturn = $this->getObjectStates();
                break;
                case 'manage':
                    $VIEW = new ViewManageMaps();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
                case 'modifyObject':
                    $sReturn = $this->handleResponse('handleResponseModifyObject', 'doModifyObject',
                                                     null, l('The object could not be modified.'));
                break;
                case 'deleteObject':
                    $aReturn = $this->handleResponseDeleteObject();

                    if($aReturn !== false) {
                        if($this->doDeleteObject($aReturn)) {
                            $sReturn = json_encode(Array('status' => 'OK', 'message' => ''));
                        } else {
                            throw new NagVisException(l('The object could not be deleted.'));
                        }
                    } else {
                        throw new NagVisException(l('You entered invalid information.'));
                    }
                break;
                case 'addModify':
                    $VIEW = new ViewMapAddModify();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
                case 'manageTmpl':
                    $VIEW = new ViewMapManageTmpl();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
                case 'doExportMap':
                    $this->doExportMap($this->name);
                    exit(0);
                    //header('Location:'.$_SERVER['HTTP_REFERER']);
                break;
                case 'toStaticMap':
                    $VIEW = new ViewToStaticMap();
                    $sReturn = json_encode(Array('code' => $VIEW->parse($this->name)));
                break;
                case 'viewToNewMap':
                    $VIEW = new ViewToNewMap();
                    $sReturn = json_encode(Array('code' => $VIEW->parse($this->name)));
                break;
                case 'getWorldmapBounds':
                    $sReturn = $this->getWorldmapBounds();
                break;
            }
        }

        return $sReturn;
    }

    protected function getWorldmapBounds() {
        $MAPCFG = new GlobalMapCfg($this->name);
        $MAPCFG->readMapConfig();
        return json_encode($MAPCFG->handleSources('get_bounds'));
    }

    protected function doExportMap($name) {
        global $CORE;
        if (!$name)
            throw new FieldInputError(null, l('Please choose a map'));

        if (count($CORE->getAvailableMaps('/^'.preg_quote($name).'$/')) == 0)
            throw new FieldInputError(null, l('The given map name is invalid'));

        $MAPCFG = new GlobalMapCfg($name);
        return $MAPCFG->exportMap();
    }

    protected function doDeleteObject($a) {
        // initialize map and read map config
        $MAPCFG = new GlobalMapCfg($a['map']);
        // Ignore map configurations with errors in it.
        // the problems may get resolved by deleting the object
        try {
            $MAPCFG->readMapConfig();
        } catch(MapCfgInvalid $e) {}

        // Give the sources the chance to load the object
        $MAPCFG->handleSources('load_obj', $a['id']);

        if(!$MAPCFG->objExists($a['id']))
            throw new NagVisException(l('The object does not exist.'));

        // first delete element from array
        $MAPCFG->deleteElement($a['id'], true);

        // delete map lock
        if(!$MAPCFG->deleteMapLock())
            throw new NagVisException(l('mapLockNotDeleted'));

        return true;
    }

    protected function handleResponseDeleteObject() {
        $bValid = true;
        // Validate the response

        $FHANDLER = new CoreRequestHandler($_GET);

        // Check for needed params
        if($bValid && !$FHANDLER->isSetAndNotEmpty('map'))
            $bValid = false;
        if($bValid && !$FHANDLER->isSetAndNotEmpty('id'))
            $bValid = false;

        // All fields: Regex check
        if($bValid && !$FHANDLER->match('map', MATCH_MAP_NAME))
            $bValid = false;
        if($bValid && !$FHANDLER->match('id', MATCH_OBJECTID))
            $bValid = false;

        if($bValid)
            $this->verifyMapExists($FHANDLER->get('map'));

        // Store response data
        if($bValid === true) {
            // Return the data
            return Array('map' => $FHANDLER->get('map'),
                         'id' => $FHANDLER->get('id'));
        } else {
            return false;
        }
    }

    protected function doModifyObject($a) {
        $MAPCFG = new GlobalMapCfg($a['map']);
        try {
            $MAPCFG->readMapConfig();
        } catch(MapCfgInvalid $e) {}

        // Give the sources the chance to load the object
        $MAPCFG->handleSources('load_obj', $a['id']);

        if(!$MAPCFG->objExists($a['id']))
            throw new NagVisException(l('The object does not exist.'));

        // set options in the array
        foreach($a['opts'] AS $key => $val) {
            $MAPCFG->setValue($a['id'], $key, $val);
        }

        // write element to file
        $MAPCFG->storeUpdateElement($a['id']);

        // delete map lock
        if(!$MAPCFG->deleteMapLock()) {
            throw new NagVisException(l('mapLockNotDeleted'));
        }

        return json_encode(Array('status' => 'OK', 'message' => ''));
    }

    protected function handleResponseModifyObject() {
        $bValid = true;
        // Validate the response

        // Need to listen to POST and GET
        $aResponse = array_merge($_GET, $_POST);
        // FIXME: Maybe change all to POST
        $FHANDLER = new CoreRequestHandler($aResponse);

        // Check for needed params
        if($bValid && !$FHANDLER->isSetAndNotEmpty('map'))
            $bValid = false;
        if($bValid && !$FHANDLER->isSetAndNotEmpty('id'))
            $bValid = false;

        // All fields: Regex check
        if($bValid && !$FHANDLER->match('map', MATCH_MAP_NAME))
            $bValid = false;
        if($bValid && $FHANDLER->isSetAndNotEmpty('id') && !$FHANDLER->match('id', MATCH_OBJECTID))
            $bValid = false;

        if($bValid)
            $this->verifyMapExists($FHANDLER->get('map'));

        // FIXME: Recode to FHANDLER
        $aOpts = $aResponse;
        // Remove the parameters which are not options of the object
        unset($aOpts['act']);
        unset($aOpts['mod']);
        unset($aOpts['map']);
        unset($aOpts['ref']);
        unset($aOpts['id']);
        unset($aOpts['lang']);

        // Also remove all "helper fields" which begin with a _
        foreach($aOpts AS $key => $val) {
            if(strpos($key, '_') === 0) {
                unset($aOpts[$key]);
            }
        }

        // Store response data
        if($bValid === true) {
            // Return the data
            return Array('map'     => $FHANDLER->get('map'),
                         'id'      => $FHANDLER->get('id'),
                         'refresh' => $FHANDLER->get('ref'),
                         'opts'    => $aOpts);
        } else {
            return false;
        }
    }

    private function getMapObjects() {
        $MAPCFG = new GlobalMapCfg($this->name);
        $MAPCFG->readMapConfig();

        $MAP = new NagVisMap($MAPCFG, GET_STATE, IS_VIEW);
        return $MAP->parseObjectsJson();
    }

    private function getObjectStates() {
        $aOpts = Array(
            'ty' => MATCH_GET_OBJECT_TYPE,
            'i'  => MATCH_STRING_NO_SPACE_EMPTY,
            'f'  => MATCH_STRING_NO_SPACE_EMPTY
        );
        $aVals = $this->getCustomOptions($aOpts, array(), true);

        // Is this request asked to check file ages?
        if(isset($aVals['f']) && isset($aVals['f'][0]) && $aVals['f'] != '') {
            $result = $this->checkFilesChanged($aVals['f']);
            if($result !== null)
                return $result;
        }

        // Initialize map configuration (Needed in getMapObjConf)
        $MAPCFG = new GlobalMapCfg($this->name);
        $MAPCFG->readMapConfig();

        // i might not be set when all map objects should be fetched or when only
        // the summary of the map is called
        if(isset($aVals['i']) && $aVals['i'] != '')
            $MAPCFG->filterMapObjects($aVals['i']);

        $MAP = new NagVisMap($MAPCFG, GET_STATE, IS_VIEW);
        return $MAP->parseObjectsJson($aVals['ty']);
    }

    // Check if the map exists
    private function verifyMapExists($map, $negate = false) {
        if(!$negate) {
            if(count($this->CORE->getAvailableMaps('/^'.$map.'$/')) <= 0) {
                throw new NagVisException(l('The map does not exist.'));
            }
        } else {
            if(count($this->CORE->getAvailableMaps('/^'.$map.'$/')) > 0) {
                throw new NagVisException(l('The map does already exist.'));
            }
        }
    }
}
?>
