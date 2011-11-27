<?php
/*****************************************************************************
 *
 * NagVisMapView.php - Class for parsing the NagVis maps in nagvis-js frontend
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
class NagVisMapView {
    private $CORE      = null;
    private $MAPCFG    = null;
    private $name      = '';
    private $search    = '';
    private $aRotation = Array();
    private $aViewOpts = Array();
    private $editMode  = false;

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
     * Set the rotation properties if the user wants a rotation
     *
     * @param   Array
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setRotation($a) {
        $this->aRotation = $a;
    }

    /**
     * Set the view modificator options
     *
     * @param   Array
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setViewOpts($a) {
        $this->aViewOpts = $a;
    }

    public function setEditMode() {
        $this->editMode = true;
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

        $this->MAPCFG = new NagVisMapCfg($this->CORE, $this->name);
        $this->MAPCFG->readMapConfig(ONLY_GLOBAL);

        $aData = Array(
            'generalProperties'  => $this->CORE->getMainCfg()->parseGeneralProperties(),
            'workerProperties'   => $this->CORE->getMainCfg()->parseWorkerProperties(),
            'rotationProperties' => json_encode($this->aRotation),
            'viewProperties'     => $this->parseViewProperties(),
            'stateProperties'    => json_encode($this->CORE->getMainCfg()->getStateWeight()),
            'userProperties'     => $USERCFG->doGetAsJson(),
            'mapName'            => $this->name,
            'fileAges'           => json_encode(Array(
                'mainCfg'   => $this->CORE->getMainCfg()->getConfigFileAge(),
                $this->name => $this->MAPCFG->getFileModificationTime(),
            )),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'),'map'), $aData);
    }

    /**
     * Parses the view specific properties. In most cases this will be user
     * defined values which maybe given by url or session
     *
     * @return  String  JSON array
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function parseViewProperties() {
        $arr = Array();

        $arr['search']         = $this->search;
        $arr['edit_mode']      = $this->editMode;
        $arr['grid_show']      = intval($this->MAPCFG->getValue(0, 'grid_show'));
        $arr['grid_color']     = $this->MAPCFG->getValue(0, 'grid_color');
        $arr['grid_steps']     = intval($this->MAPCFG->getValue(0, 'grid_steps'));
        $arr['permitted_edit'] = $this->CORE->getAuthorization() !== null && $this->CORE->getAuthorization()->isPermitted('Map', 'edit', $this->name);

        // View specific hover modifier set
        if($this->aViewOpts['enableHover'] !== false) {
            $arr['enableHover'] = $this->aViewOpts['enableHover'];
        }

        // View specific context modifier set
        if($this->aViewOpts['enableContext'] !== false) {
            $arr['enableContext'] = $this->aViewOpts['enableContext'];
        }

        return json_encode($arr);
    }
}
?>
