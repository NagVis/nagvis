<?php
/*****************************************************************************
 *
 * NagVisMapView.php - Class for parsing the NagVis maps in nagvis-js frontend
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
class NagVisMapView {
    private $MAPCFG    = null;
    private $name      = '';
    private $search    = '';
    private $aRotation = [];
    private $editMode  = false;
    private $aParams   = [];

    public function __construct(GlobalCore $CORE, $name) {
        $this->name = $name;
    }

    /**
     * Set the search value if the user searches for an object
     *
     * @param   string $s Search string
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function setSearch($s) {
        $this->search = $s;
    }

    /**
     * Set the rotation properties if the user wants a rotation
     *
     * @param   array $a
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function setRotation($a) {
        $this->aRotation = $a;
    }

    /**
     * Set the url params
     */
    public function setParams($a) {
        $this->aParams = $a;
    }

    public function setEditMode() {
        $this->editMode = true;
    }

    /**
     * Parses the map and the objects for the nagvis-js frontend
     *
     * @return	string 	String with JS Code
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function parse() {
        global $_MAINCFG, $CORE;
        // Initialize template system
        $TMPL    = new FrontendTemplateSystem();
        $TMPLSYS = $TMPL->getTmplSys();
        $USERCFG = new CoreUserCfg();

        $this->MAPCFG = new GlobalMapCfg($this->name);
        $this->MAPCFG->readMapConfig(ONLY_GLOBAL, true, true, true);

        $aData = [
            'generalProperties'  => $_MAINCFG->parseGeneralProperties(),
            'workerProperties'   => $_MAINCFG->parseWorkerProperties(),
            'rotationProperties' => json_encode($this->aRotation),
            'viewProperties'     => $this->parseViewProperties(),
            'stateProperties'    => json_encode($_MAINCFG->getStateWeightJS()),
            'pageProperties'     => json_encode($this->MAPCFG->getMapProperties()),
            'userProperties'     => $USERCFG->doGetAsJson(),
            'mapName'            => $this->name,
            'zoomFill'           => $this->MAPCFG->getValue(0, 'zoom') == 'fill',
            'fileAges'           => json_encode([
                'maincfg'   => $_MAINCFG->getConfigFileAge(),
                $this->name => $this->MAPCFG->getFileModificationTime(),
            ]),
            'locales'            => json_encode($CORE->getGeneralJSLocales()),
        ];

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'),'map'), $aData);
    }

    /**
     * Parses the view specific properties. In most cases this will be user
     * defined values which maybe given by url or session
     *
     * @return  string  JSON array
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    private function parseViewProperties() {
        global $AUTHORISATION;
        $arr = [];

        $arr['search']                = $this->search;
        $arr['edit_mode']             = $this->editMode;
        $arr['grid_show']             = intval($this->MAPCFG->getValue(0, 'grid_show'));
        $arr['grid_color']            = $this->MAPCFG->getValue(0, 'grid_color');
        $arr['grid_steps']            = intval($this->MAPCFG->getValue(0, 'grid_steps'));
        $arr['event_repeat_interval'] = intval($this->MAPCFG->getValue(0, 'event_repeat_interval'));
        $arr['event_repeat_duration'] = intval($this->MAPCFG->getValue(0, 'event_repeat_duration'));
        $arr['event_on_load']         = intval($this->MAPCFG->getValue(0, 'event_on_load'));
        $arr['permitted_edit']        = $AUTHORISATION !== null && $AUTHORISATION->isPermitted('Map', 'edit', $this->name);
        $arr['permitted_perform']     = $AUTHORISATION !== null && $AUTHORISATION->isPermitted('Action', 'perform', '*');

        // hover_menu & context_menu have to be handled separated from the others
        // It is special for them that the object individual settings have to be
        // treated and only the user specified source params must be applied here
        // (no global, no hardcoded default)
        // FIXME: Recode to use the user_params
        $userParams = $this->MAPCFG->getSourceParams(true);
        if(isset($userParams['hover_menu'])) {
            $arr['hover_menu'] = $userParams['hover_menu'];
        }
        if(isset($userParams['context_menu'])) {
            $arr['context_menu'] = $userParams['context_menu'];
        }

        // This sets the user specific parameters
        $arr['user_params'] = $this->MAPCFG->getSourceParams(true, true);
        // This sets the final source parameters
        $arr['params'] = $this->MAPCFG->getSourceParams();

        return json_encode($arr);
    }
}

