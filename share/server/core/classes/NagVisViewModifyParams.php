<?php
/*****************************************************************************
 *
 * NagVisViewModifyParams.php - Class for rendering the modify params
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
class NagVisViewModifyParams {
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
        unset($this->opts['_t']);
        if(!isset($this->opts['width']))
            $this->opts['width'] = '';
        if(!isset($this->opts['height']))
            $this->opts['height'] = '';

        $this->optValues = array();
    }

    public function setValues($arr) {
        $this->optValues = $arr;
    }

    public function addOpts($arr) {
        foreach($arr AS $key => $val) {
            if(!isset($this->opts[$key])) {
                $this->opts[$key] = $val;
            }
        }
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

        $aData = Array(
	    'mod'         => $_GET['mod'],
            'act'         => $_GET['act'],
            'show'        => $_GET['show'],
            'htmlBase'    => cfg('paths', 'htmlbase'),
            'opts'        => $this->opts,
            'optValues'   => Array(
            ),
            'langApply'       => l('Apply'),
            'langPermanent'   => l('Make Permanent'),
            'permAutomapEdit' => $CORE->getAuthorization()->isPermitted($_GET['mod'], 'edit', $_GET['show']),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'viewModifyParams'), $aData);
    }
}
?>
