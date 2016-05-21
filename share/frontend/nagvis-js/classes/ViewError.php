<?php
/*****************************************************************************
 *
 * ViewError.php - Renders an error page
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

class ViewError {
    private function errorPage($e) {
        global $_MAINCFG;
        ob_start();
        $USERCFG = new CoreUserCfg();

        js('oGeneralProperties = '.$_MAINCFG->parseGeneralProperties().';'.N
          .'oUserProperties = '.$USERCFG->doGetAsJson().';'.N);

        echo '<div id="page">';
        js('frontendMessage({'.N
          .'    "type"    : "error",'.N
          .'    "closable": false,'.N
          .'    "title"   : "'.l('Error').'",'.N
          .'    "message" : "'.htmlentities($e->getMessage(), ENT_COMPAT, 'UTF-8').'"'.N
          .'});');
        echo '</div>';

        return ob_get_clean();
    }

    public function parse($e, $MAPCFG = null) {
        global $CORE;

        $INDEX = new NagVisIndexView($CORE);
        $HEADER = new NagVisHeaderMenu(cfg('index', 'headertemplate'), $MAPCFG);
        $INDEX->setHeaderMenu($HEADER->__toString());

        $INDEX->setContent($this->errorPage($e));

        return $INDEX->parse();
    }

    public function parseWithMap($e, $map_name) {
        $MAPCFG = new GlobalMapCfg($map_name);
        try {
            $MAPCFG->readMapConfig(ONLY_GLOBAL);
        } catch(MapCfgInvalid $e) {}
        return $this->parse($e, $MAPCFG);
    }
}
?>
