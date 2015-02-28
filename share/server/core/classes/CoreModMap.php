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
            case 'toStaticMap':
            case 'doExportMap':
                $aVals = $this->getCustomOptions(Array('show' => MATCH_MAP_NAME_EMPTY));
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
                    $aOpts = Array(
                        'show'      => MATCH_MAP_NAME,
                        'clone_id'  => MATCH_OBJECTID_EMPTY,
                        'submit'    => MATCH_STRING_EMPTY,
                        'update'    => MATCH_INTEGER_EMPTY,
                        'mode'      => MATCH_STRING_EMPTY,
                        'perm'      => MATCH_BOOLEAN_EMPTY,
                        'perm_user' => MATCH_BOOLEAN_EMPTY,
                    );
                    $aVals = $this->getCustomOptions($aOpts, Array(), true);
                    list($attrs, $attrsFiltered) = $this->filterMapAttrs($this->getAllOptions($aOpts));

                    // mode is set to view_params if only the "view parameters" dialog is handled in this request.
                    // This dialog has less options and is primary saved for the user and not for all users in the
                    // map configuration
                    $mode = isset($aVals['mode']) ? $aVals['mode'] : null;
                    // Tells the handleAddModify handler to store the options permanent
                    if(isset($aVals['perm']) && $aVals['perm'] == '1') {
                        $perm = 1;
                    } elseif(isset($aVals['perm_user']) && $aVals['perm_user'] == '1') {
                        $perm = 2;
                    } else {
                        $perm = null;
                    }

                    if($mode == 'view_params' && !isset($aVals['show']))
                        $map_name = null;
                    else
                        $map_name = $aVals['show'];

                    $VIEW = new ViewMapAddModify($map_name, $mode);
                    $VIEW->setAttrs($attrs);

                    // This tells the following handling when the page only needs to be repainted
                    $update = isset($aVals['update']) && $aVals['update'] == '1';
                    $cloneId = isset($aVals['clone_id']) ? $aVals['clone_id'] : null;

                    $err     = null;
                    $success = null;
                    // Don't handle submit actions when the 'update' POST attribute is set
                    if(isset($aVals['submit']) && $aVals['submit'] != '' && !$update) {
                        // The form has been submitted.
                        try {
                            $success = $this->handleAddModify($mode, $perm, $map_name, $attrs, $attrsFiltered);
                        } catch(FieldInputError $e) {
                            $err = $e;
                        }
                    }

                    $sReturn = json_encode(Array('code' => $VIEW->parse($update, $err, $success, $cloneId)));
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
                    if(isset($_POST['target'])) {
                        // Is called on form submission
                        $this->toStaticMap();
                    } else {
                        $VIEW = new NagVisViewToStaticMap($this->CORE);
                        $sReturn = json_encode(Array('code' => $VIEW->parse()));
                    }
                break;
            }
        }

        return $sReturn;
    }

    /**
     * Converts maps using sources to static maps
     */
    private function toStaticMap() {
        $FHANDLER = new CoreRequestHandler($_POST);
        if(!$FHANDLER->match('target', MATCH_MAP_NAME)) {
            throw new NagVisException(l('Invalid target option given.'));
        }

        $target = $FHANDLER->get('target');
        // "true" negates the check
        $this->verifyMapExists($target, true);

        // Read the old config
        $this->verifyMapExists($this->name);
        $MAPCFG = new GlobalMapCfg($this->name);
        $MAPCFG->readMapConfig();

        // Create a new map config
        $NEW = new GlobalMapCfg($target);
        $NEW->createMapConfig();
        foreach($MAPCFG->getMapObjects() AS $object_id => $cfg) {
            // Remove "sources" from the global section. Cause this makes the maps dynamic
            if($cfg['type'] == 'global') {
                unset($cfg['sources']);
            }
            $NEW->addElement($cfg['type'], $cfg, $perm = true, $object_id);
        }

        throw new Success(
            l('The map has been created.'),
            null,
            1,
            cfg('paths','htmlbase').'/frontend/nagvis-js/index.php?mod=Map&show='.$target
        );
    }


    // Filter the attributes using the helper fields
    // Each attribute can have the toggle_* field set. If present
    // use it's value to filter out the attributes
    private function filterMapAttrs($attrs) {
        $ret = array();
        $filtered = array();
        foreach($attrs AS $attr => $val) {
            if(substr($attr, 0, 7) == 'toggle_' || $attr == '_t' || $attr == 'lang' || $attr == 'update')
                continue;

            if(isset($attrs['toggle_'.$attr]) && $attrs['toggle_'.$attr] !== 'on') {
                $filtered[$attr] = null;
            } else {
                $ret[$attr] = $val;
            }
        }
        return array($ret, $filtered);
    }

    // Validate and process addModify form submissions
    protected function handleAddModify($mode, $perm, $map, $attrs, $attrsFiltered) {
        if ($mode != 'view_params')
            $this->verifyMapExists($map);
        $MAPCFG = new GlobalMapCfg($map);

        try {
            $MAPCFG->skipSourceErrors();
            $MAPCFG->readMapConfig();
        } catch(MapCfgInvalid $e) {}

        // Modification/Creation?
        // The object_id is known on modification. When it is not known 'type' is set
        // to create new objects
        if(isset($attrs['object_id']) && $attrs['object_id'] != '') {
            // Modify an existing object

            $type  = $MAPCFG->getValue($attrs['object_id'], 'type');
            $objId = $attrs['object_id'];

            // The handler has been called in "view_params" mode. In this case the user has
            // less options and the options to
            // 1. modify these parameters only for the current open view
            // 2. Save the changes for himselfs
            // 3. Save the changes to the map config (-> Use default code below)
            if($mode == 'view_params' && $perm != 1 && $perm != 2) {
                // This is the 1. case -> redirect the user to a well formated url
                $params = array_merge($attrs, $attrsFiltered);
                unset($params['object_id']);
                return array(0, $params, '');
            }

            if($mode == 'view_params' && $perm == 2) {
                // This is the 2. case -> saving the options only for the user
                $USERCFG = new CoreUserCfg();
                $params = $attrs;
                unset($params['object_id']);
                $USERCFG->doSet(array(
                    'params-' . $map => $params,
                ));
                return array(0, '', '');
            }

            if(!$MAPCFG->objExists($objId))
                throw new NagVisException(l('The object does not exist.'));

            $this->validateAttributes($MAPCFG, $MAPCFG->getValidObjectType($type), $attrs);

            // Update the map configuration   
            if($mode == 'view_params') {
                // Only modify/add the given attributes. Don't remove any
                // set options in the array
                foreach($attrs as $key => $val)
                    $MAPCFG->setValue($attrs['object_id'], $key, $val);
                $MAPCFG->storeUpdateElement($attrs['object_id']);
            } else {
                // add/modify case: Rewrite whole object with the given attributes
                $MAPCFG->updateElement($objId, $attrs, true);
            }

            $t = $type == 'global' ? l('map configuration') : $type;
            $successMsg = array(2, '', l('The [TYPE] has been modified. Reloading in 2 seconds.',
                                                               Array('TYPE' => $t)));
        } else {
            // Create the new object
            $type  = $attrs['type'];

            $this->validateAttributes($MAPCFG, $MAPCFG->getValidObjectType($type), $attrs);

            // append a new object definition to the map configuration
            $MAPCFG->addElement($type, $attrs, true);

            $successMsg = array(2, '', l('The [TYPE] has been added. Reloading in 2 seconds.',
                                                            Array('TYPE' => $type)));
        }

        // delete map lock
        if(!$MAPCFG->deleteMapLock())
            throw new NagVisException(l('mapLockNotDeleted'));

        // On success, always scroll to top of page
        $successMsg[2] .= '<script type="text/javascript">document.body.scrollTop = document.documentElement.scrollTop = 0;</script>';

        return $successMsg;
    }

    private function validateAttributes($MAPCFG, $attrDefs, $attrs) {
        // Are some must values missing?
        foreach($attrDefs as $propname => $prop) {
            if(isset($prop['must']) && $prop['must'] == '1') {
                // In case of "source" options only validate the ones which belong
                // to currently enabled sources
                if(isset($prop['source_param']) && !in_array($prop['source_param'], $MAPCFG->getValue(0, 'sources')))
                    continue;
                
                if (!isset($attrs[$propname]) || $attrs[$propname] == '')
                    throw new FieldInputError($propname, l('The attribute needs to be set.'));
            }
        }

        // FIXME: Are all given attrs valid ones?
        foreach($attrs AS $key => $val) {
            if(!isset($attrDefs[$key]))
                throw new FieldInputError($key, l('The attribute is unknown.'));
            if(isset($attrDefs[$key]['deprecated']) && $attrDefs[$key]['deprecated'] === true)
                throw new FieldInputError($key, l('The attribute is deprecated.'));

            // The object has a match regex, it can be checked
            // -> In case of array attributes validate the single parts
            if(isset($attrDefs[$key]['match'])) {
                $array = isset($attrDefs[$key]['array']) && $attrDefs[$key]['array'];
                if(!$array)
                    $v = array($val);
                else
                    $v = explode(',', $val);

                foreach($v as $part) {
                    if(!preg_match($attrDefs[$key]['match'], $part)) {
                        throw new FieldInputError($key, l('The attribute has the wrong format (Regex: [MATCH]).',
                            Array('MATCH' => $attrDefs[$key]['match'])));
                    }
                }
            }
        }
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
