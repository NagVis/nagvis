<?php
/*******************************************************************************
 *
 * CoreModAutoMap.php - Core Automap module to handle ajax requests
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
class CoreModAutoMap extends CoreModule {
    private $name = null;
    private $aOpts = null;
    private $opts = null;

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'AutoMap';
        $this->CORE = $CORE;

        $this->aOpts = Array('show' => MATCH_MAP_NAME,
                       'backend' => MATCH_STRING_NO_SPACE_EMPTY,
                       'root' => MATCH_STRING_NO_SPACE_EMPTY,
                       'childLayers' => MATCH_INTEGER_PRESIGN_EMPTY,
                       'parentLayers' => MATCH_INTEGER_PRESIGN_EMPTY,
                       'renderMode' => MATCH_AUTOMAP_RENDER_MODE,
                       'width' => MATCH_INTEGER_EMPTY,
                       'height' => MATCH_INTEGER_EMPTY,
                       'ignoreHosts' => MATCH_STRING_NO_SPACE_EMPTY,
                       'filterGroup' => MATCH_STRING_EMPTY,
                       'filterByState' => MATCH_STRING_NO_SPACE_EMPTY);

        $aVals = $this->getCustomOptions($this->aOpts);
        $this->name = $aVals['show'];
        unset($aVals['show']);
        $this->opts = $aVals;

        // Save the automap name to use
        $this->opts['automap'] = $this->name;
        // Save the preview mode (Enables/Disables printing of errors)
        $this->opts['preview'] = 0;

        // Register valid actions
        $this->aActions = Array(
            'parseAutomap'         => 'view',
            'getAutomapProperties' => 'view',
            'getAutomapObjects'    => 'view',
            'getObjectStates'      => 'view',
            'automapToMap'         => 'edit',
            'modifyParams'         => 'edit',
            'parseMapCfg'          => 'edit',

            'modifyObject'         => 'edit',
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
                case 'parseAutomap':
                case 'parseMapCfg':
                    $sReturn = $this->parseAutomap();
                break;
                case 'getAutomapProperties':
                    $sReturn = $this->getAutomapProperties();
                break;
                case 'getAutomapObjects':
                    $sReturn = $this->getAutomapObjects();
                break;
                case 'getObjectStates':
                    $sReturn = $this->getObjectStates();
                break;
                case 'automapToMap':
                    $VIEW = new NagVisViewAutomapToMap($this->CORE);
          $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
                case 'modifyParams':
                    $VIEW = new NagVisViewAutomapModifyParams($this->CORE, $this->opts);
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
                case 'modifyObject':
                    $refresh = null;
                    $success = null;
                    if(isset($aReturn['refresh']) && $aReturn['refresh'] == 1) {
                        $refresh = 1;
                        $success = l('The object has been modified.');
                    }
                    $sReturn = $this->handleResponse('handleResponseModifyObject', 'doModifyObject',
                                            $success,
                                                                l('The object could not be modified.'),
                                                                $refresh);
                break;
            }
        }

        return $sReturn;
    }

    protected function doModifyObject($a) {
        $MAPCFG = new NagVisAutomapCfg($this->CORE, $a['map']);
        $MAPCFG->readMapConfig();

        // Check if the element exists and maybe create it first
        if(!$MAPCFG->objExists($a['id'])) {
            $a['opts']['host_name'] = $MAPCFG->objIdToName($a['id']);
            $MAPCFG->addElement('host', $a['opts'], true, $a['id']);
        } else {
            foreach($a['opts'] AS $key => $val)
                $MAPCFG->setValue($a['id'], $key, $val);

            // write element to file
            $MAPCFG->storeUpdateElement($a['id']);
        }

        // delete map lock
        if(!$MAPCFG->deleteMapLock())
            throw new NagVisException(l('mapLockNotDeleted'));

        return json_encode(Array('status' => 'OK', 'message' => ''));
    }

    protected function handleResponseModifyObject() {
        $aResponse = array_merge($_GET, $_POST);
        $FHANDLER = new CoreRequestHandler($aResponse);

        $this->verifyValuesSet($FHANDLER,   Array('show', 'id'));
        $this->verifyValuesMatch($FHANDLER, Array('show' => MATCH_MAP_NAME,
                                                  'id'   => MATCH_OBJECTID));

        // Check if the map exists
        if(count($this->CORE->getAvailableAutoMaps('/^'.$FHANDLER->get('show').'$/')) <= 0)
            throw new NagVisException(l('The map does not exist.'));

        $aOpts = $aResponse;
        // Remove the parameters which are not options of the object
        unset($aOpts['act']);
        unset($aOpts['mod']);
        unset($aOpts['show']);
        unset($aOpts['ref']);
        unset($aOpts['id']);
        unset($aOpts['timestamp']);

        // Also remove all "helper fields" which begin with a _
        foreach($aOpts AS $key => $val)
            if(strpos($key, '_') === 0)
                unset($aOpts[$key]);

        return Array('map'     => $FHANDLER->get('show'),
                     'type'    => $FHANDLER->get('type'),
                     'id'      => $FHANDLER->get('id'),
                     'refresh' => $FHANDLER->get('ref'),
                     'opts'    => $aOpts);
    }

    private function parseAutomap() {
        // Initialize backends
        $BACKEND = new CoreBackendMgmt($this->CORE);

        $MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
        $MAPCFG->readMapConfig();

        $MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $this->opts, IS_VIEW);

        if($this->sAction == 'parseAutomap') {
            $MAP->renderMap();
            return json_encode(true);
        } else {
            $FHANDLER = new CoreRequestHandler($_POST);
            if($FHANDLER->match('target', MATCH_MAP_NAME)) {
                $target = $FHANDLER->get('target');

                if($MAP->toClassicMap($target)) {
                    throw new Success(l('The map has been created.'),
                                      null,
                                      1,
                                      cfg('paths','htmlbase').'/frontend/nagvis-js/index.php?mod=Map&show='.$target);
                }  else {
                    throw new NagVisException(l('Unable to create map configuration file.'));
                }
            } else {
                throw new NagVisException(l('Invalid target option given.'));
            }
        }
    }

    private function getAutomapProperties() {
        $MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
        $MAPCFG->readMapConfig(ONLY_GLOBAL);

        $arr = Array();
        $arr['map_name']                 = $MAPCFG->getName();
        $arr['alias']                    = $MAPCFG->getValue(0, 'alias');
        $arr['map_image']                = $MAPCFG->getValue(0, 'map_image');
        $arr['background_usemap']        = '#automap';
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

        return json_encode($arr);
    }

    private function getAutomapObjects() {
        // Initialize backends
        $BACKEND = new CoreBackendMgmt($this->CORE);

        $MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
        $MAPCFG->readMapConfig();

        $MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $this->opts, IS_VIEW);

        // Read position from graphviz and set it on the objects
        $MAP->setMapObjectPositions();
        $MAP->createObjectConnectors();

        return $MAP->parseObjectsJson();
    }

    private function getObjectStates() {
        $arrReturn = Array();

        $aOpts = Array('ty' => MATCH_GET_OBJECT_TYPE,
                       'i'  => MATCH_STRING_NO_SPACE_EMPTY);
        $aVals = $this->getCustomOptions($aOpts);

        // Initialize backends
        $BACKEND = new CoreBackendMgmt($this->CORE);

        // Read the map configuration file
        $MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
        $MAPCFG->readMapConfig();

        // i might not be set when all map objects should be fetched or when only
        // the summary of the map is called
        if($aVals['i'] != '') {
            $MAPCFG->filterMapObjects($aVals['i']);

            // Filter by explicit list of host object ids
            $this->opts['filterByIds'] = $aVals['i'];
        }

        $MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $this->opts, IS_VIEW);
        $MAPOBJ = $MAP->MAPOBJ;
        return $MAP->parseObjectsJson($aVals['ty']);
    }
}
?>
