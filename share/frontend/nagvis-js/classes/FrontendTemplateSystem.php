<?php
/*****************************************************************************
 *
 * FrontendTemplateSystem.php - Implements the template parsing in NagVis
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
class FrontendTemplateSystem {
    private $TMPL;

    public function __construct() {
        // Load Dwoo. It is used as external library
        require_once(cfg('paths','base')
                     .HTDOCS_DIR.'/frontend/nagvis-js/ext/dwoo-1.1.0/dwooAutoload.php');

        $this->TMPL = new Dwoo(cfg('paths','var')
                               .'tmpl/compile', cfg('paths','var').'tmpl/cache');
    }

    public function getTmplSys() {
        return $this->TMPL;
    }

    public function getTmplFile($sTheme, $sTmpl) {
        return new Dwoo_Template_File(path('sys', '', 'templates', $sTheme.'.'.$sTmpl.'.html'));
    }
}

?>
