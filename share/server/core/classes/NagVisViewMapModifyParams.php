<?php
/*****************************************************************************
 *
 * NagVisViewMapModifyParams.php - Class for rendering the modify params
 *                                 dialog
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
class NagVisViewMapModifyParams {
    private $opts = array();

    /**
     * Class Constructor
     *
     * @param 	GlobalCore 	$CORE
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct() {
	$this->opts = $_GET;
        unset($this->opts['mod']);
        unset($this->opts['act']);
        unset($this->opts['show']);
        unset($this->opts['search']);
        unset($this->opts['rotation']);
        unset($this->opts['rotationStep']);
        unset($this->opts['enableHeader']);
        unset($this->opts['enableContext']);
        unset($this->opts['enableHover']);
    }

    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        $CORE = GlobalCore::getInstance();

        // Initialize template system
        $TMPL = New CoreTemplateSystem($CORE);
        $TMPLSYS = $TMPL->getTmplSys();

        $map_name = $_GET['show'];

        $aData = Array(
	    'type'        => 'map',
	    'mod'         => 'Map',
            'htmlBase'    => cfg('paths', 'htmlbase'),
            'mapName'     => $map_name,
            'opts'        => $this->opts,
            'optValues'   => Array(
                'renderMode'    => $this->renderModes,
                // FIXME: root        => List of hosts in the backend
                // FIXME: filterGroup => Lists of hostgroups in the backend
                'show'          => $CORE->getAvailableAutomaps(),
                'backend'       => $CORE->getDefinedBackends(),
                'filterByState' => Array('0', '1'),
            ),
            'langApply'       => l('Apply'),
            'langPermanent'   => l('Make Permanent'),
            'permAutomapEdit' => $CORE->getAuthorization()->isPermitted('Map', 'edit', $map_name),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'automapModifyParams'), $aData);
    }
}
?>
