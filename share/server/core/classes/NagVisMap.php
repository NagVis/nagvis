<?php
/*****************************************************************************
 *
 * NagVisMap.php - Class for parsing the NagVis maps
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
 *****************************************************************************/

/**
 * @author	Lars Michelsen <lm@larsmichelsen.com>
 */
class NagVisMap {
    public $MAPOBJ = null;
    protected $MAPCFG;

    private $linkedMaps = Array();

    /**
     * Class Constructor
     *
     * @param 	GlobalMainCfg 	$MAINCFG
     * @param 	GlobalMapCfg 	$MAPCFG
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function __construct($MAPCFG, $getState = GET_STATE, $bIsView = IS_VIEW) {
        global $_BACKEND;
        $this->MAPCFG = $MAPCFG;

        if($getState === GET_STATE) {
            $this->MAPOBJ = new NagVisMapObj($MAPCFG, $bIsView);
            // FIXME: needed? $this->MAPOBJ->setConfiguration($this->MAPCFG->getTypeDefaults('global'));
            $objConf = $MAPCFG->getMapObject(0);
            unset($objConf['type']);
            $this->MAPOBJ->setConfiguration($objConf);
            log_mem('postmapinit');
            $this->MAPOBJ->fetchMapObjects();
            log_mem('map ' .$this->MAPCFG->getName(). ' '.count($this->MAPOBJ->getMembers()));
            log_mem('postmapobjects');

            if($bIsView === IS_VIEW) {
                $this->MAPOBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
                $_BACKEND->execute();
                $this->MAPOBJ->applyState();
                log_mem('postmapstate');
            } else {
                $this->MAPOBJ->queueState(GET_STATE, DONT_GET_SINGLE_MEMBER_STATES);
                log_mem('postmapstatequeue');
            }
        }
    }

    /**
     * Parses the objects of the map. Can be called in different modes
     *   complete: first object is the summary of the map and all map objects
     *   summary:  only the summary state of the map
     *   full:     all object infos of all map objects (might be filtered)
     *   state:    the state of all map objects (might be filtered)
     *
     * @param   String  The type of request. Can be complete|summary|state
     * @return	String  Json Code
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function parseObjectsJson($type = 'complete') {
        $arrRet = Array();

        // First parse the map object itselfs for having the
        // summary information in the frontend
        if($type === 'complete' || $type === 'summary')
            $arrRet[] = $this->MAPOBJ->parseJson();

        // In summary mode only return the map object state
        if($type === 'summary')
            return json_encode($arrRet);

        foreach($this->MAPOBJ->getMembers() AS $OBJ) {
            switch(get_class($OBJ)) {
                case 'NagVisHost':
                case 'NagVisService':
                case 'NagVisHostgroup':
                case 'NagVisDynGroup':
                case 'NagVisAggr':
                case 'NagVisServicegroup':
                case 'NagVisMapObj':
                    if($type == 'state') {
                        $arr = $OBJ->getObjectStateInformations();
                        $arr['object_id'] = $OBJ->getObjectId();
                        $arr['icon'] = $OBJ->get('icon');
                        $arrRet[] = $arr;
                    } else {
                        $arrRet[] = $OBJ->parseJson();
                    }
                break;
                default: // Shape, Line, Textbox and others...
                    if ($type == 'complete' || $type == 'full')
                        $arrRet[] = $OBJ->parseJson();
                break;
            }
        }

        return json_encode($arrRet);
    }
}
?>
