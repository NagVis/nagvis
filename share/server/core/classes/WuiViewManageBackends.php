<?php
/*****************************************************************************
 *
 * WuiViewManageBackends.php - View to render manage backends page
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
class WuiViewManageBackends {
    private $CORE;
    private $AUTHENTICATION;
    private $AUTHORISATION;

    /**
     * Class Constructor
     *
     * @param 	GlobalCore 	$CORE
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct(CoreAuthHandler $AUTHENTICATION, CoreAuthorisationHandler $AUTHORISATION) {
        $this->CORE = GlobalCore::getInstance();
        $this->AUTHENTICATION = $AUTHENTICATION;
        $this->AUTHORISATION = $AUTHORISATION;
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
            'htmlBase'              => cfg('paths', 'htmlbase'),
            'langSetDefaultBackend' => l('defaultBackend'),
            'langDefaultBackend'    => l('setDefaultBackend'),
            'langSave'              => l('save'),
            'langDelete'            => l('delete'),
            'langAddBackend'        => l('addBackend'),
            'langEditBackend'       => l('editBackend'),
            'langDelBackend'        => l('delBackend'),
            'defaultBackend'        => cfg('defaults', 'backend', true),
            'definedBackends'       => array_merge(Array('' => ''), $this->CORE->getDefinedBackends(ONLY_USERCFG)),
            'availableBackends'     => array_merge(Array('' => ''), $this->CORE->getAvailableBackends()),
            'backendAttributes'     => $this->CORE->getMainCfg()->getValidObjectType('backend'),
            'validMainCfg'          => json_encode($this->CORE->getMainCfg()->getValidConfig()),
            'lang'                  => $this->CORE->getJsLang(),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile('default', 'wuiManageBackends'), $aData);
    }
}
?>
