<?php
/*****************************************************************************
 *
 * FrontendTemplateSystem.php - Implements the template parsing in NagVis
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

/**
 * @author	Lars Michelsen <lm@larsmichelsen.com>
 */
class FrontendTemplateSystem
{
    private $TMPL;

    public function __construct()
    {
        // Load Dwoo. It is used as external library
        require_once(cfg('paths', 'base')
            . HTDOCS_DIR . '/frontend/nagvis-js/ext/dwoo-1.1.0/dwooAutoload.php');

        $this->TMPL = new Dwoo(cfg('paths', 'var')
            . 'tmpl/compile', cfg('paths', 'var') . 'tmpl/cache');
    }

    public function getTmplSys()
    {
        return $this->TMPL;
    }

    public function getTmplFile($sTheme, $sTmpl)
    {
        $F = new Dwoo_Template_File(path('sys', '', 'templates', $sTheme . '.' . $sTmpl . '.html'));
        // Would rather set this to null to make the template system not call chmod at all to use
        // the system default, but this does not work because makeDirectory() uses 0777 in case
        // it is set to null.
        // Would rather set 640 for the files, but there is only a single chmod value configurable
        // which is used for directories AND files. So I have to use 750.
        $F->setChmod(0750);
        return $F;
    }
}


