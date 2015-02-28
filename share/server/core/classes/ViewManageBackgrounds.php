<?php
/*****************************************************************************
 *
 * ViewManageBackgrounds.php - View to render manage backgrounds page
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
class ViewManageBackgrounds {
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

        $aData = Array(
            'htmlBase'             => cfg('paths', 'htmlbase'),
            'htmlImages'           => cfg('paths', 'htmlimages'),
            'langCreateBackground' => l('createBackground'),
            'langBackgroundName'   => l('backgroundName'),
            'langBackgroundColor'  => l('backgroundColor'),
            'langColorSelect'      => l('Color select'), 
            'langBackgroundWidth'  => l('backgroundWidth'),
            'langBackgroundHeight' => l('backgroundHeight'),
            'langCreate'           => l('create'),
            'langUploadBackground' => l('uploadBackground'),
            'langChooseImage'      => l('chooseImage'),
            'langUpload'           => l('upload'),
            'langDeleteBackground' => l('deleteBackground'),
            'langDelete'           => l('delete'),
            'images'               => $CORE->getAvailableBackgroundImages(),
            'lang'                 => $CORE->getJsLang(),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'wuiManageBackgrounds'), $aData);
    }
}
?>
