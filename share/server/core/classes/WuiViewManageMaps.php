<?php
/*****************************************************************************
 *
 * WuiViewManageMaps.php - View to render manage maps page
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
class WuiViewManageMaps {
    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        global $CORE;

        // Initialize template system
        $TMPL = new CoreTemplateSystem($CORE);
        $TMPLSYS = $TMPL->getTmplSys();

        $aData = Array(
            'htmlBase'              => cfg('paths', 'htmlbase'),
            'langCreateMap'         => l('createMap'),
            'langCreate'            => l('create'),
            'langRenameMap'         => l('renameMap'),
            'langRename'            => l('rename'),
            'langDeleteMap'         => l('deleteMap'),
            'langDelete'            => l('delete'),
            'langExportMap'         => l('exportMap'),
            'langExport'            => l('export'),
            'langImportMap'         => l('importMap'),
            'langImport'            => l('import'),
            'langChooseMap'         => l('chooseMap'),
            'langChooseMapFile'     => l('chooseMapFile'),
            'langNewMapName'        => l('newMapName'),
            'langMapName'           => l('mapName'),
            'langMapIconset'        => l('mapIconset'),
            'langBackground'        => l('background'),
            'availableBackgrounds'  => $CORE->getAvailableBackgroundImages(),
            'availableIconsets'     => $CORE->getAvailableIconsets(),
            'availableMaps'         => $CORE->getAvailableMaps(),
	    'lang'                  => $CORE->getJsLang(),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'wuiManageMaps'), $aData);
    }
}
?>
