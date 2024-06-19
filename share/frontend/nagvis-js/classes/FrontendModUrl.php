<?php
/*****************************************************************************
 *
 * FrontendModUrl.php - Module for handling external urls in NagVis
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
class FrontendModUrl extends FrontendModule
{
    private $CORE;
    private $VIEW;
    private $url = '';
    private $rotation = '';
    private $rotationStep = '';

    public function __construct(GlobalCore $CORE)
    {
        $this->sName = 'Url';
        $this->CORE = $CORE;

        // Parse the view specific options
        $aOpts = [
            'show' => MATCH_STRING_URL,
            'rotation' => MATCH_ROTATION_NAME_EMPTY,
            'rotationStep' => MATCH_INTEGER_EMPTY
        ];

        $aVals = $this->getCustomOptions($aOpts);
        $this->url = $aVals['show'];
        $this->rotation = $aVals['rotation'];
        $this->rotationStep = $aVals['rotationStep'];

        // Register valid actions
        $this->aActions = [
            'view' => REQUIRES_AUTHORISATION
        ];
    }

    public function handleAction()
    {
        $sReturn = '';

        if ($this->offersAction($this->sAction)) {
            switch ($this->sAction) {
                case 'view':
                    // Show the view dialog to the user
                    $sReturn = $this->showViewDialog();
                    break;
            }
        }

        return $sReturn;
    }

    private function showViewDialog()
    {
        // Only show when map name given
        if (!isset($this->url) || $this->url == '') {
            throw new NagVisException(l('No url given.'));
        }

        // Build index template
        $INDEX = new NagVisIndexView($this->CORE);

        // Need to parse the header menu?
        if (cfg('index', 'headermenu')) {
            // Parse the header menu
            $HEADER = new NagVisHeaderMenu(cfg('index', 'headertemplate'));

            // Put rotation information to header menu
            if ($this->rotation != '') {
                      $HEADER->setRotationEnabled();
            }

            $INDEX->setHeaderMenu($HEADER->__toString());
        }

        // Initialize map view
        $this->VIEW = new NagVisUrlView($this->CORE, $this->url);

        // Maybe it is needed to handle the requested rotation
        if ($this->rotation != '') {
            $ROTATION = new FrontendRotation($this->rotation);
            $ROTATION->setStep('url', $this->url, $this->rotationStep);
            $this->VIEW->setRotation($ROTATION->getRotationProperties());
        }

        $INDEX->setContent($this->VIEW->parse());
        return $INDEX->parse();
    }
}
