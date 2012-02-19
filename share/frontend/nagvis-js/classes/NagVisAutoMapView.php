<?php
/*****************************************************************************
 *
 * NagVisAutoMapView.php - Class for parsing the NagVis automaps in nagvis-js
 *                         frontend
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
class NagVisAutoMapView {
    private $CORE = null;

    private $name = '';
    private $search = '';
    private $content = '';
    private $aRotation = Array();
    private $aParams = Array();

    /**
     * Class Constructor
     *
     * @param    GlobalCore      $CORE
     * @param    String          $NAME
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct(GlobalCore $CORE, $name) {
        $this->CORE = $CORE;
        $this->name = $name;
    }

    public function setContent($s) {
        $this->content = $s;
    }

    /**
     * Set the search value if the user searches for an object
     *
     * @param   String    Search string
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setSearch($s) {
        $this->search = $s;
    }

    /**
     * Set the url params
     */
    public function setParams($a) {
        $this->aParams = $a;
    }

    /**
     * Set the rotation properties if the user wants a rotation
     *
     * @param   Array
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setRotation($a) {
        $this->aRotation = $a;
    }

    /**
     * Parses the map and the objects for the nagvis-js frontend
     *
     * @return	String 	String with JS Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        // Initialize template system
        $TMPL    = new FrontendTemplateSystem($this->CORE);
        $TMPLSYS = $TMPL->getTmplSys();
        $USERCFG = new CoreUserCfg();

        $MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
        $MAPCFG->readMapConfig();

        $aData = Array(
                'generalProperties'  => $this->CORE->getMainCfg()->parseGeneralProperties(),
                'workerProperties'   => $this->CORE->getMainCfg()->parseWorkerProperties(),
                'rotationProperties' => json_encode($this->aRotation),
                'viewProperties'     => json_encode(array(
                    'search'        => $this->search,
                    'enableHover'   => $this->aParams['hover_menu'],
                    'enableContext' => $this->aParams['context_menu'],
                    // only take the user supplied coords
                    'params'        => $MAPCFG->getSourceParams(true),
                )),
                'stateProperties'    => json_encode($this->CORE->getMainCfg()->getStateWeight()),
                'userProperties'     => $USERCFG->doGetAsJson(),
                'mapName'            => $this->name,
                'automap'            => $this->content,
                'fileAges'           => json_encode(Array(
                    'maincfg'   => $this->CORE->getMainCfg()->getConfigFileAge(),
                    $this->name => $MAPCFG->getFileModificationTime(),
                )),
            );

    // Build page based on the template file and the data array
    return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'automap'), $aData);
    }
}
?>
