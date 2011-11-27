<?php
/*****************************************************************************
 *
 * NagVisViewAutomapModifyParams.php - Class for rendering the modify params
 *                                     dialog for automaps
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
class NagVisViewAutomapModifyParams {
    private $CORE;
    private $aOpts;
    private $renderModes = Array(
        '',
        'directed',
        'undirected',
        'radial',
        'circular',
        'undirected2'
    );

    /**
     * Class Constructor
     *
     * @param 	GlobalCore 	$CORE
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct($CORE, $aOpts) {
        $this->CORE = $CORE;

        $this->aOpts = $aOpts;

        $this->gatherParams();
    }

    private function gatherParams() {
        if(isset($this->aOpts['automap']) && $this->aOpts['automap'] != '') {
            // Initialize backend handler
            $BACKEND = new CoreBackendMgmt($this->CORE);

            $MAPCFG = new NagVisAutomapCfg($this->CORE, $this->aOpts['automap']);
            $MAPCFG->readMapConfig();

            $MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $this->aOpts, IS_VIEW);

            $this->aOpts = $MAP->getOptions();

            // Skip unwanted params
            unset($this->aOpts['perm']);
        }
    }

    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        // Initialize template system
        $TMPL = New CoreTemplateSystem($this->CORE);
        $TMPLSYS = $TMPL->getTmplSys();

        $aData = Array(
            'htmlBase'    => cfg('paths','htmlbase'),
            'automapName' => $this->aOpts['automap'],
            'opts'        => $this->aOpts,
            'optValues'   => Array('renderMode'    => $this->renderModes,
                                   // FIXME: root        => List of hosts in the backend
                                   // FIXME: filterGroup => Lists of hostgroups in the backend
                                   'show'          => $this->CORE->getAvailableAutomaps(),
                                   'backend'       => $this->CORE->getDefinedBackends(),
                                   'filterByState' => Array('0', '1'),
                                  ),
            'langApply'       => l('Apply'),
            'langPermanent'   => l('Make Permanent'),
            'permAutomapEdit' => $this->CORE->getAuthorization()->isPermitted('AutoMap', 'edit', $this->aOpts['automap']),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'automapModifyParams'), $aData);
    }
}
?>
