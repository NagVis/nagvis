<?php
/*******************************************************************************
 *
 * CoreModOverview.php - Core Overview module to handle ajax requests
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
class CoreModOverview extends CoreModule {
    private $htmlBase;

    public function __construct(GlobalCore $CORE) {
        $this->htmlBase = cfg('paths','htmlbase');
        $this->sName = 'Overview';

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
            switch($this->sAction) {
                case 'getOverviewProperties':
                    $sReturn = $this->parseIndexPropertiesJson();
                break;
                case 'getOverviewRotations':
                    $sReturn = $this->parseRotationsJson();
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

                    $sReturn = $this->parseMapsJson(COMPLETE, $aVals['i']);
                break;
            }
        }

        return $sReturn;
    }

    private function parseMapJson($objectId, $mapName, $what) {
        global $AUTHORISATION;
        // Check if the user is permitted to view this
        if(!$AUTHORISATION->isPermitted('Map', 'view', $mapName))
            return null;

        // If the parameter filterUser is set, filter the maps by the username
        // given in this parameter. This is a mechanism to be authed as generic
        // user but see the maps of another user. This feature is disabled by
        // default but could be enabled if you need it.
        if(cfg('global', 'user_filtering') && isset($_GET['filterUser']) && $_GET['filterUser'] != '') {
            $AUTHORISATION2 = new CoreAuthorisationHandler();
            $AUTHORISATION2->parsePermissions($_GET['filterUser']);
            if(!$AUTHORISATION2->isPermitted('Map', 'view', $mapName))
                return null;

            // Switch the auth cookie to this user
            global $SHANDLER;
            $SHANDLER->aquire();
            $SHANDLER->set('authCredentials', array('user' => $_GET['filterUser'], 'password' => ''));
            $SHANDLER->set('authTrusted',     true);
            $SHANDLER->commit();
        }

        $map = Array('object_id' => $objectId);

        $MAPCFG = new GlobalMapCfg($mapName);
        $MAPCFG->checkMapConfigExists(true);
        $MAPCFG->readMapConfig();

        // Only perform this check with a valid config
        if($MAPCFG->getValue(0, 'show_in_lists') != 1)
            return null;

        $objConf = $MAPCFG->getTypeDefaults('global');

        $MAP = new NagVisMap($MAPCFG, GET_STATE, !IS_VIEW);

        // Apply default configuration to object
        $objConf = array_merge($objConf, $this->getMapDefaultOpts($mapName, $MAPCFG->getAlias()));

        $MAP->MAPOBJ->setConfiguration($objConf);

        if($MAP->MAPOBJ->checkMaintenance(0)) {
            $map['overview_url']    = $this->htmlBase.'/index.php?mod=Map&act=view&show='.$mapName;
            $map['overview_class']  = '';
        } else {
            $map['overview_class']  = 'disabled';
            $map['overview_url']    = 'javascript:alert(\''.l('The map is in maintenance mode. Please be patient.').'\');';
            $map['summary_output']  = l('The map is in maintenance mode. Please be patient.');

            $MAP->MAPOBJ->clearMembers();
            $MAP->MAPOBJ->setSummaryState('UNKNOWN');
            $MAP->MAPOBJ->fetchIcon();
        }

        if(cfg('index','showmapthumbs') == 1)
            $map['overview_image'] = $this->renderMapThumb($MAPCFG);

        return array($MAP->MAPOBJ, $map);
    }

    /**
     * Parses the maps and maps for the overview page
     * Then it is called for a list of maps to return the current state
     * for the listed objects.
     *
     * @return	String  Json Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     * FIXME: More cleanups, compacting and extraction of single parts
     */
    public function parseMapsJson($what = COMPLETE, $objects = Array()) {
        global $_BACKEND, $CORE;
        $mapList = $objects;

        $aMaps = Array();
        $aObjs = Array();
        log_mem('pre');
        foreach($mapList AS $objectId) {
            $a = explode('-', $objectId, 2);
            if(!isset($a[1]))
                continue;
            $mapName = $a[1];
            // list mode: Skip processing when this type of object should not be shown
            if(cfg('index', 'showmaps') != 1)
                continue;

            try {
                $ret = $this->parseMapJson($objectId, $mapName, $what);
                if($ret === null) {
                    // Skip maps which shal not be shown to the user
                    continue;
                }
            } catch(Exception $e) {
                $aMaps[] = $this->mapError($mapName, $e->getMessage());
                continue;
            }

            $aObjs[] = $ret;
            log_mem('post '. $mapName);
        }

        // Now fetch and apply data from backend
        $_BACKEND->execute();
        foreach($aObjs AS $aObj) {
            $aObj[0]->applyState();
            $aObj[0]->fetchIcon();

            if($what === ONLY_STATE)
                $aMaps[] = array_merge($aObj[0]->getObjectStateInformations(), $aObj[1]);
            else
                $aMaps[] = array_merge($aObj[0]->parseJson(), $aObj[1]);
        }
        log_mem('post backend');
        return json_encode($aMaps);
    }

    private function getMapDefaultOpts($name, $alias) {
        return Array(
          'type'              => 'map',
          'map_name'          => $name,
          'object_id'         => 'map-'.$name,
          // Enable the hover menu in all cases - maybe make it configurable
          'hover_menu'        => 1,
          'hover_childs_show' => 1,
          'hover_template'    => 'default',
          // Enforce std_medium iconset - don't use map default iconset
          'iconset'           => 'std_medium',
          'alias'             => $alias
        );
    }

    private function mapError($name, $msg) {
        $map = $this->getMapDefaultOpts($name, $name);
        $map['name']            = $map['map_name'];
        unset($map['map_name']);
        $map['state']           = 'ERROR';
        $map['summary_state']   = 'ERROR';
        $map['icon']            = 'std_medium_error.png';
        $map['members']         = Array();
        $map['num_members']     = 0;
        $map['overview_class']  = 'error';
        $map['overview_url']    = 'javascript:alert(\''.$msg.'\');';
        $map['summary_output']  = l('Map Error: [ERR]', Array('ERR' => $msg));
        return $map;
    }

    private function renderMapThumb($MAPCFG) {
        global $CORE;
        $imgPath     = $MAPCFG->BACKGROUND->getFile(GET_PHYSICAL_PATH);

        // Check if
        // a) PHP supports gd
        // b) The image is a local one
        // c) The image exists
        // When one is not OK, then use the large map image
        if(!$CORE->checkGd(0) || !$MAPCFG->BACKGROUND->getFileType() == 'local' || !file_exists($imgPath))
            return $MAPCFG->BACKGROUND->getFile();

        $sThumbFile     = $MAPCFG->getName() . '-thumb.' . $this->getFileType($imgPath);
        $sThumbPath     = cfg('paths', 'sharedvar') . $sThumbFile;
        $sThumbPathHtml = cfg('paths', 'htmlsharedvar') . $sThumbFile;

        // Only create a new thumb when there is no cached one
        $FCACHE = new GlobalFileCache($imgPath, $sThumbPath);
        if($FCACHE->isCached() === -1)
            $image = $this->createThumbnail($imgPath, $sThumbPath);

        return $sThumbPathHtml;
    }

    /**
     * Parses the rotations for the overview page
     *
     * @return	String  Json Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parseRotationsJson() {
        global $AUTHORISATION, $CORE;
        // Only display the rotation list when enabled
        if(cfg('index','showrotations') != 1)
            return json_encode(Array());

        $aRotations = Array();
        foreach($CORE->getPermittedRotationPools() AS $poolName) {
            $ROTATION = new CoreRotation($poolName);
            $iNum = $ROTATION->getNumSteps();
            $aSteps = Array();
            for($i = 0; $i < $iNum; $i++) {
                $aSteps[] = Array('name' => $ROTATION->getStepLabelById($i),
                                  'url'  => $ROTATION->getStepUrlById($i));
            }

            $aRotations[] = Array('name'      => $poolName,
                                  'url'       => $ROTATION->getStepUrlById(0),
                                  'num_steps' => $ROTATION->getNumSteps(),
                                  'steps'     => $aSteps);
        }

        return json_encode($aRotations);
    }

    /**
     * Parses the overview page options in json format
     *
     * @return	String 	String with JSON Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parseIndexPropertiesJson() {
        $arr = Array();

        $arr['cellsperrow']        = (int) cfg('index', 'cellsperrow');
        $arr['showmaps']           = (int) cfg('index', 'showmaps');
        $arr['showgeomap']         = (int) cfg('index', 'showgeomap');
        $arr['showmapthumbs']      = (int) cfg('index', 'showmapthumbs');
        $arr['showrotations']      = (int) cfg('index', 'showrotations');

        $arr['page_title']         = cfg('internal', 'title');
        $arr['favicon_image']      = cfg('paths', 'htmlimages').'internal/favicon.png';
        $arr['background_color']   = cfg('index','backgroundcolor');

        $arr['lang_mapIndex']      = l('mapIndex');
        $arr['lang_rotationPools'] = l('rotationPools');

        $arr['event_log']          = (int) cfg('defaults', 'eventlog');
        $arr['event_log_level']    = cfg('defaults', 'eventloglevel');
        $arr['event_log_events']   = (int) cfg('defaults', 'eventlogevents');
        $arr['event_log_height']   = (int) cfg('defaults', 'eventlogheight');
        $arr['event_log_hidden']   = (int) cfg('defaults', 'eventloghidden');

        return json_encode($arr);
    }

    /**
     * Returns the filetype
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getFileType($imgPath) {
        $imgSize = getimagesize($imgPath);
        switch($imgSize[2]) {
            case 1:
                $strFileType = 'gif';
            break;
            case 2:
                $strFileType = 'jpg';
            break;
            case 3:
                $strFileType = 'png';
            break;
            default:
                $strFileType = '';
            break;
        }

        return $strFileType;
    }

    /**
     * Creates thumbnail images for the index map
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function createThumbnail($imgPath, $thumbPath) {
        global $CORE;
        if($CORE->checkVarFolderWriteable(TRUE) && $CORE->checkExisting($imgPath, TRUE)) {
            // 0: width, 1:height, 2:type
            $imgSize = getimagesize($imgPath);
            $strFileType = '';

            switch($imgSize[2]) {
                case 1:
                    $image = imagecreatefromgif($imgPath);
                    $strFileType = 'gif';
                break;
                case 2:
                    $image = imagecreatefromjpeg($imgPath);
                    $strFileType = 'jpg';
                break;
                case 3:
                    $image = imagecreatefrompng($imgPath);
                    $strFileType = 'png';
                break;
                default:
                    throw new NagVisException(l('onlyPngOrJpgImages'));
                break;
            }

            // Size of source images
            list($bgWidth, $bgHeight) = $imgSize;

            // Target size
            $thumbResWidth = 200;
            $thumbResHeight = 150;

            if($bgWidth > $bgHeight) {
                // Calculate size
                $thumbWidth = $thumbResWidth;
                $thumbHeight = $bgHeight / ($bgWidth / $thumbWidth);

                // Calculate offset
                $thumbX = 0;
                $thumbY = ($thumbResHeight - $thumbHeight) / 2;
            } elseif($bgHeight > $bgWidth) {
                // Calculate size
                $thumbHeight = $thumbResHeight;
                $thumbWidth = $bgWidth / ($bgHeight / $thumbResHeight);

                // Calculate offset
                $thumbX = ($thumbResWidth - $thumbWidth) / 2;
                $thumbY = 0;
            } else {
                // Calculate size
                if($thumbResWidth > $thumbResHeight) {
                        $thumbHeight = $thumbResHeight;
                        $thumbWidth = $thumbResHeight;
                } elseif($thumbResHeight > $thumbResWidth) {
                        $thumbHeight = $thumbResWidth;
                        $thumbWidth = $thumbResWidth;
                } else {
                        $thumbHeight = $thumbResHeight;
                        $thumbWidth = $thumbResHeight;
                }

                // Calculate offset
                $thumbX = ($thumbResWidth - $thumbWidth) / 2;
                $thumbY = ($thumbResHeight - $thumbHeight) / 2;
            }

            $thumb = imagecreatetruecolor($thumbResWidth, $thumbResHeight);

            imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 254));
            imagecolortransparent($thumb, imagecolorallocate($thumb, 255, 255, 254));

            imagecopyresampled($thumb, $image, $thumbX, $thumbY, 0, 0, $thumbWidth, $thumbHeight, $bgWidth, $bgHeight);

            switch($imgSize[2]) {
                case 1:
                    imagegif($thumb, $thumbPath);
                break;
                case 2:
                    imagejpeg($thumb, $thumbPath);
                break;
                case 3:
                    imagepng($thumb, $thumbPath);
                break;
                default:
                    throw new NagVisException(l('onlyPngOrJpgImages'));
                break;
            }

            return $thumbPath;
        } else {
            return '';
        }
    }
}
?>
