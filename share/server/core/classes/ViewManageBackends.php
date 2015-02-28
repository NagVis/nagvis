<?php
/*****************************************************************************
 *
 * ViewManageBackends.php - View to render manage backends page
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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
class ViewManageBackends {
    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        global $CORE;

        // Initialize template system
        $TMPL = New CoreTemplateSystem($CORE);
        $TMPLSYS = $TMPL->getTmplSys();

        $editableBackends = array_merge(Array('' => ''), $CORE->getDefinedBackends(ONLY_USERCFG));
        $definedBackends  = array_merge(Array('' => ''), $CORE->getDefinedBackends());

        $aData = Array(
            'htmlBase'              => cfg('paths', 'htmlbase'),
            'langSetDefaultBackend' => l('defaultBackend'),
            'langDefaultBackend'    => l('setDefaultBackend'),
            'langSave'              => l('save'),
            'langDelete'            => l('delete'),
            'langAddBackend'        => l('addBackend'),
            'langEditBackend'       => l('editBackend'),
            'langDelBackend'        => l('delBackend'),
            'langSomeNotEditable'   => l('Some backends are not editable by using the web gui. They can only be '
                                        .'configured by modifying the file in the NagVis conf.d directory.'),
            'defaultBackend'        => cfg('defaults', 'backend', true),
            'definedBackends'       => $definedBackends,
            'editableBackends'      => $editableBackends,
            'someNotEditable'       => $editableBackends != $definedBackends,
            'availableBackends'     => array_merge(Array('' => ''), $CORE->getAvailableBackends()),
            'backendAttributes'     => $CORE->getMainCfg()->getValidObjectType('backend'),
            'validMainCfg'          => json_encode($CORE->getMainCfg()->getValidConfig()),
            'lang'                  => $CORE->getJsLang(),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'wuiManageBackends'), $aData);
    }
}
?>
