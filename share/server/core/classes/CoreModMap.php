<?php
/*******************************************************************************
 *
 * CoreModMap.php - Core Map module to handle ajax requests
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

            // WUI specific actions
            'manage'            => REQUIRES_AUTHORISATION,
            'doExportMap'       => 'edit',

            'addModify'         => 'edit',
            'modifyObject'      => 'edit',
            'createObject'      => 'edit',
            'deleteObject'      => 'edit',
            'toStaticMap'       => 'edit',

            'manageTmpl'        => 'edit',
            'getTmplOpts'       => 'edit',
            'doTmplAdd'         => 'edit',
            'doTmplModify'      => 'edit',
            'doTmplDelete'      => 'edit',
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
            case 'getTmplOpts':
            case 'addModify':
            case 'doExportMap':
                $aVals = $this->getCustomOptions(Array('show' => MATCH_MAP_NAME_EMPTY));
                $this->name = $aVals['show'];
            break;
            case 'toStaticMap':
                $aVals = $this->getCustomOptions(Array('show' => MATCH_MAP_NAME_EMPTY), array(), true);
                $this->name = $aVals['show'];
            break;
            // And those have the objecs in the POST var "map"
            case 'createObject':
            case 'modifyObject':
            case 'deleteObject':
            case 'doTmplAdd':
            case 'doTmplModify':
            case 'doTmplDelete':
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
                    $sReturn = $this->getMapProperties();
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
                    $aOpts = Array('show' => MATCH_MAP_NAME);
                    $aVals = $this->getCustomOptions($aOpts);

                    $VIEW = new ViewMapManageTmpl();
                    $VIEW->setOpts($aVals);
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
                case 'getTmplOpts':
                    $aOpts = Array('show' => MATCH_MAP_NAME,
                                   'name' => MATCH_STRING_NO_SPACE);
                    $aVals = $this->getCustomOptions($aOpts);

                    // Read map config but don't resolve templates and don't use the cache
                    $MAPCFG = new GlobalMapCfg($aVals['show']);
                    $MAPCFG->readMapConfig(0, false, false);

                    $aTmp = $MAPCFG->getDefinitions('template');
                    $aTmp = $aTmp[$MAPCFG->getTemplateIdByName($aVals['name'])];
                    unset($aTmp['type']);
                    unset($aTmp['object_id']);

                    $sReturn = json_encode(Array('opts' => $aTmp));
                break;
                case 'doTmplAdd':
                    $this->handleResponse('handleResponseDoTmplAdd', 'doTmplAdd',
                                            l('The object has been added.'),
                                                                l('The object could not be added.'),
                                                                1);
                break;
                case 'doTmplModify':
                    $this->handleResponse('handleResponseDoTmplModify', 'doTmplModify',
                                            l('The object has been modified.'),
                                                                l('The object could not be modified.'),
                                                                1);
                break;
                case 'doTmplDelete':
                    $this->handleResponse('handleResponseDoTmplDelete', 'doTmplDelete',
                                            l('The template has been deleted.'),
                                                                l('The template could not be deleted.'),
                                                                1);
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
            }
        }

        return $sReturn;
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

    protected function doTmplModify($a) {
        $MAPCFG = new GlobalMapCfg($a['map']);
        $MAPCFG->readMapConfig(0, false, false);

        $id = $MAPCFG->getTemplateIdByName($a['opts']['name']);

        // set options in the array
        foreach($a['opts'] AS $key => $val) {
            $MAPCFG->setValue('template', $id, $key, $val);
        }

        $MAPCFG->writeElement('template', $id);

        // delete map lock
        if(!$MAPCFG->deleteMapLock()) {
            throw new NagVisException(l('mapLockNotDeleted'));
        }

        return true;
    }

    protected function handleResponseDoTmplModify() {
        $bValid = true;
        // Validate the response

        $FHANDLER = new CoreRequestHandler($_POST);

        // Check for needed params
        if($bValid && !$FHANDLER->isSetAndNotEmpty('map'))
            $bValid = false;
        if($bValid && !$FHANDLER->isSetAndNotEmpty('name'))
            $bValid = false;

        // All fields: Regex check
        if($bValid && !$FHANDLER->match('map', MATCH_MAP_NAME))
            $bValid = false;
        if($bValid && !$FHANDLER->match('name', MATCH_STRING_NO_SPACE))
            $bValid = false;

        if($bValid)
            $this->verifyMapExists($FHANDLER->get('map'));

        // Check if the template already exists
        // Read map config but don't resolve templates and don't use the cache
        $MAPCFG = new GlobalMapCfg($FHANDLER->get('map'));
        $MAPCFG->readMapConfig(0, false, false);
        if($bValid && count($MAPCFG->getTemplateNames('/^'.$FHANDLER->get('name').'$/')) <= 0) {
            throw new NagVisException(l('A template with this name does not exist.'));

            $bValid = false;
        }
        $MAPCFG = null;

        // FIXME: Recode to FHANDLER
        $aOpts = $_POST;

        // Remove the parameters which are not options of the object
        unset($aOpts['submit']);
        unset($aOpts['map']);

        // Transform the array to key => value form
        $opts = Array('name' => $FHANDLER->get('name'));
        foreach($aOpts AS $key => $a) {
            if(substr($key, 0, 3) === 'opt' && isset($a['name']) && isset($a['value'])) {
                $opts[$a['name']] = $a['value'];
            }
        }

        // Store response data
        if($bValid === true) {
            // Return the data
            return Array('map' => $FHANDLER->get('map'),
                         'opts' => $opts);
        } else {
            return false;
        }
    }

    protected function doTmplDelete($a) {
        // Read map config but don't resolve templates and don't use the cache
        $MAPCFG = new GlobalMapCfg($a['map']);
        $MAPCFG->readMapConfig(0, false, false);

        $id = $MAPCFG->getTemplateIdByName($a['name']);

        // first delete element from array
        $MAPCFG->deleteElement($id, true);

        // delete map lock
        if(!$MAPCFG->deleteMapLock()) {
            throw new NagVisException(l('mapLockNotDeleted'));
        }

        return true;
    }

    protected function handleResponseDoTmplDelete() {
        $bValid = true;
        // Validate the response

        $FHANDLER = new CoreRequestHandler($_POST);

        // Check for needed params
        if($bValid && !$FHANDLER->isSetAndNotEmpty('map'))
            $bValid = false;
        if($bValid && !$FHANDLER->isSetAndNotEmpty('name'))
            $bValid = false;

        // All fields: Regex check
        if($bValid && !$FHANDLER->match('map', MATCH_MAP_NAME))
            $bValid = false;
        if($bValid && !$FHANDLER->match('name', MATCH_STRING_NO_SPACE))
            $bValid = false;

        if($bValid)
            $this->verifyMapExists($FHANDLER->get('map'));

        // Check if the template already exists
        // Read map config but don't resolve templates and don't use the cache
        $MAPCFG = new GlobalMapCfg($FHANDLER->get('map'));
        $MAPCFG->readMapConfig(0, false, false);
        if($bValid && count($MAPCFG->getTemplateNames('/^'.$FHANDLER->get('name').'$/')) <= 0)
            throw new NagVisException(l('The template does not exist.'));
        $MAPCFG = null;

        // Store response data
        if($bValid === true) {
            // Return the data
            return Array('map' => $FHANDLER->get('map'),
                         'name' => $FHANDLER->get('name'));
        } else {
            return false;
        }
    }

    protected function doTmplAdd($a) {
        $MAPCFG = new GlobalMapCfg($a['map']);
        $MAPCFG->readMapConfig(0, false, false);

        // append a new object definition to the map configuration
        $MAPCFG->addElement('template', $a['opts'], true);

        // delete map lock
        if(!$MAPCFG->deleteMapLock())
            throw new NagVisException(l('mapLockNotDeleted'));

        return true;
    }

    protected function handleResponseDoTmplAdd() {
        $bValid = true;
        // Validate the response

        $FHANDLER = new CoreRequestHandler($_POST);

        // Check for needed params
        if($bValid && !$FHANDLER->isSetAndNotEmpty('map'))
            $bValid = false;
        if($bValid && !$FHANDLER->isSetAndNotEmpty('name'))
            $bValid = false;

        // All fields: Regex check
        if($bValid && !$FHANDLER->match('map', MATCH_MAP_NAME))
            $bValid = false;
        if($bValid && !$FHANDLER->match('name', MATCH_STRING_NO_SPACE))
            $bValid = false;

        if($bValid)
            $this->verifyMapExists($FHANDLER->get('map'));

        // Check if the template already exists
        // Read map config but don't resolve templates and don't use the cache
        $MAPCFG = new GlobalMapCfg($FHANDLER->get('map'));
        $MAPCFG->readMapConfig(0, false, false);
        if($bValid && count($MAPCFG->getTemplateNames('/^'.$FHANDLER->get('name').'$/')) > 0)
            throw new NagVisException(l('A template with this name already exists.'));
        $MAPCFG = null;

        // FIXME: Recode to FHANDLER
        $aOpts = $_POST;

        // Remove the parameters which are not options of the object
        unset($aOpts['submit']);
        unset($aOpts['map']);
        unset($aOpts['name']);

        // Transform the array to key => value form
        $opts = Array('name' => $FHANDLER->get('name'));
        foreach($aOpts AS $key => $a) {
            if(substr($key, 0, 3) === 'opt' && isset($a['name']) && isset($a['value'])) {
                $opts[$a['name']] = $a['value'];
            }
        }

        // Store response data
        if($bValid === true) {
            // Return the data
            return Array('map' => $FHANDLER->get('map'),
                         'opts' => $opts);
        } else {
            return false;
        }
    }

    protected function doDeleteObject($a) {
        // initialize map and read map config
        $MAPCFG = new GlobalMapCfg($a['map']);
        // Ignore map configurations with errors in it.
        // the problems may get resolved by deleting the object
        try {
            $MAPCFG->readMapConfig();
        } catch(MapCfgInvalid $e) {}

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

    private function getMapProperties() {
        $MAPCFG = new GlobalMapCfg($this->name);
        $MAPCFG->readMapConfig(ONLY_GLOBAL);

        $arr = Array();
        $arr['map_name']                 = $MAPCFG->getName();
        $arr['alias']                    = $MAPCFG->getValue(0, 'alias');
        $arr['background_image']         = $MAPCFG->BACKGROUND->getFile();
        $arr['background_color']         = $MAPCFG->getValue(0, 'background_color');
        $arr['favicon_image']            = cfg('paths', 'htmlimages').'internal/favicon.png';
        $arr['page_title']               = $MAPCFG->getValue(0, 'alias').' ([SUMMARY_STATE]) :: '.cfg('internal', 'title');
        $arr['event_background']         = $MAPCFG->getValue(0, 'event_background');
        $arr['event_highlight']          = $MAPCFG->getValue(0, 'event_highlight');
        $arr['event_highlight_interval'] = $MAPCFG->getValue(0, 'event_highlight_interval');
        $arr['event_highlight_duration'] = $MAPCFG->getValue(0, 'event_highlight_duration');
        $arr['event_log']                = $MAPCFG->getValue(0, 'event_log');
        $arr['event_log_level']          = $MAPCFG->getValue(0, 'event_log_level');
        $arr['event_log_events']         = $MAPCFG->getValue(0, 'event_log_events');
        $arr['event_log_height']         = $MAPCFG->getValue(0, 'event_log_height');
        $arr['event_log_hidden']         = $MAPCFG->getValue(0, 'event_log_hidden');
        $arr['event_scroll']             = $MAPCFG->getValue(0, 'event_scroll');
        $arr['event_sound']              = $MAPCFG->getValue(0, 'event_sound');
        $arr['in_maintenance']           = $MAPCFG->getValue(0, 'in_maintenance');
        $arr['sources']                  = $MAPCFG->getValue(0, 'sources');

        return json_encode($arr);
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
        $aVals = $this->getCustomOptions($aOpts);

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
        if($aVals['i'] != '')
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
