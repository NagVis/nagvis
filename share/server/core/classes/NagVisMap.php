<?php
/*****************************************************************************
 *
 * NagVisMap.php - Class for parsing the NagVis maps
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
class NagVisMap extends GlobalMap {
    public $MAPOBJ;

    /**
     * Class Constructor
     *
     * @param 	GlobalMainCfg 	$MAINCFG
     * @param 	GlobalMapCfg 	$MAPCFG
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct($CORE, $MAPCFG, $getState = GET_STATE, $bIsView = IS_VIEW) {
        global $_BACKEND;
        parent::__construct($CORE, $MAPCFG);

        if($getState === true) {
            $this->MAPOBJ = new NagVisMapObj($CORE, $_BACKEND, $MAPCFG, $bIsView);
            $this->MAPOBJ->fetchMapObjects();

            if($bIsView === true) {
                $this->MAPOBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
                $_BACKEND->execute();
                $this->MAPOBJ->applyState();
            } else {
                $this->MAPOBJ->queueState(GET_STATE, DONT_GET_SINGLE_MEMBER_STATES);
            }
        }
    }
}
?>
