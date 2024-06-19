<?php
/*****************************************************************************
 *
 * NagVisUrlView.php - Class for parsing the NagVis urls in nagvis-js frontend
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
class NagVisUrlView
{
    private $url = '';
    private $content = '';
    private $aRotation = [];

    /**
     * Class Constructor
     *
     * @param GlobalCore $CORE
     * @param string $url
     * @author    Lars Michelsen <lm@larsmichelsen.com>
     */
    public function __construct(GlobalCore $CORE, $url) {
        $this->url = $url;
    }

    /**
     * Set the page content
     *
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function setContent($s) {
        $this->content = $s;
    }

    /**
     * Set the rotation properties if the user wants a rotation
     *
     * @param   array $a
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function setRotation($a) {
        $this->aRotation = $a;
    }

    private function getProperties() {
        return [
            'url'       => $this->url,
            'view_type' => 'url',
        ];
    }

    /**
     * Parses the url and the objects for the nagvis-js frontend
     *
     * @return	string 	String with JS Code
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function parse() {
        global $_MAINCFG;
        // Initialize template system
        $TMPL = New FrontendTemplateSystem();
        $TMPLSYS = $TMPL->getTmplSys();

        $iframe = false;
        $url = $this->url;
        if (strpos($this->url, 'iframe:') === 0) {
            $iframe = true;
            $url = substr($this->url, 7);
        }

        $aData = [
            'generalProperties'  => $_MAINCFG->parseGeneralProperties(),
            'workerProperties'   => $_MAINCFG->parseWorkerProperties(),
            'rotationProperties' => json_encode($this->aRotation),
            'pageProperties'     => json_encode($this->getProperties()),
            'iframe'             => $iframe,
            'url'                => $url,
            'fileAges'           => json_encode([
                'maincfg' => $_MAINCFG->getConfigFileAge(),
            ]),
            'locales'            => json_encode([]),
        ];

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'url'), $aData);
    }
}

