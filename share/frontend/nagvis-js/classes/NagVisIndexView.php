<?php
/*****************************************************************************
 *
 * NagVisIndexView.php - Class for parsing the NagVis index in nagvis-js
 *                       frontend
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
class NagVisIndexView {
    private $sSubtitle = '';
    private $sCustomStylesheet = '';
    private $sHeaderMenu = '';
    private $sContent = '';

    public function __construct(GlobalCore $CORE) {
    }

    public function setSubtitle($s) {
        $this->sSubtitle = ' &rsaquo; ' . $s;
    }

    public function setCustomStylesheet($s) {
        $this->sCustomStylesheet = $s;
    }

    public function setHeaderMenu($s) {
        $this->sHeaderMenu = $s;
    }

    public function setContent($s) {
        $this->sContent = $s;
    }

    /**
     * Parses the map and the objects for the nagvis-js frontend
     *
     * @return	String 	String with JS Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        // Initialize template system
        $TMPL = New FrontendTemplateSystem();
        $TMPLSYS = $TMPL->getTmplSys();

        $aData = Array(
            'pageTitle'        => cfg('internal', 'title') . $this->sSubtitle,
            'htmlBase'         => cfg('paths', 'htmlbase'),
            'htmlJs'           => cfg('paths', 'htmljs'),
            'htmlCss'          => cfg('paths', 'htmlcss'),
            'htmlTemplates'    => path('html', 'global', 'templates'),
            'bUseCompressedJs' => $this->checkJsCompressed(),
            'customStylesheet' => $this->sCustomStylesheet,
            'headerMenu'       => $this->sHeaderMenu,
            'content'          => $this->sContent
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'index'), $aData);
    }

    /**
     * Checks if the compressed javascript file exists
     *
     * @return	Boolean
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkJsCompressed() {
        return file_exists(cfg('paths', 'js').'NagVisCompressed.js');
    }
}
?>
