<?php
/*****************************************************************************
 *
 * NagVisOverviewView.php - Class for handling the map index page
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

class NagVisOverviewView {
    public function __construct($CORE) {
    }

    private function getProperties() {
        $arr = Array();

        $arr['view_type']          = 'overview';
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

        return $arr;
    }

    /**
     * Parses the information for json
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function parse() {
        global $_MAINCFG, $CORE;
        // Initialize template system
        $TMPL    = new FrontendTemplateSystem();
        $TMPLSYS = $TMPL->getTmplSys();
        $USERCFG = new CoreUserCfg();

        $maps      = cfg('index', 'showmaps') == 1 ? $CORE->getListMaps() : array();
        $rotations = cfg('index', 'showrotations') == 1 ? array_keys($CORE->getPermittedRotationPools()) : array();

        $aData = Array(
            'generalProperties' => $_MAINCFG->parseGeneralProperties(),
            'workerProperties'  => $_MAINCFG->parseWorkerProperties(),
            'stateProperties'   => json_encode($_MAINCFG->getStateWeightJS()),
            'userProperties'    => $USERCFG->doGetAsJson(),
            'pageProperties'    => json_encode($this->getProperties()),
            'fileAges'          => json_encode(Array(
                'maincfg' => $_MAINCFG->getConfigFileAge(),
            )),
            'locales'           => json_encode($CORE->getGeneralJSLocales()),
            'rotation_names'    => json_encode($rotations),
            'map_names'         => json_encode($maps),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'overview'), $aData);
    }
}
?>
