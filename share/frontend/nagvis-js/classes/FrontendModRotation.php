<?php
/*****************************************************************************
 *
 * FrontendModRotation.php - Module for handling rotations in NagVis
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
class FrontendModRotation extends FrontendModule {
    private $name   = '';
    private $type   = '';
    private $step   = '';
    private $stepId = '';

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'Rotation';
        $this->CORE = $CORE;

        // Parse the view specific options
        $aOpts = Array('show' => MATCH_ROTATION_NAME,
                       'type' => MATCH_ROTATION_STEP_TYPES_EMPTY,
                       'step' => MATCH_STRING_NO_SPACE_EMPTY,
                       'stepId' => MATCH_INTEGER_EMPTY);

        $aVals = $this->getCustomOptions($aOpts);
        $this->name   = $aVals['show'];
        $this->type   = $aVals['type'];
        $this->step   = $aVals['step'];
        $this->stepId = $aVals['stepId'];

        // Register valid actions
        $this->aActions = Array(
            'view' => REQUIRES_AUTHORISATION
        );

        // Register valid objects
        $this->aObjects = $this->CORE->getDefinedRotationPools();

        // Set the requested object for later authorisation
        $this->setObject($this->name);
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'view':
                    // Show the view dialog to the user
                    $sReturn = $this->showViewDialog();
                break;
            }
        }

        return $sReturn;
    }

    private function showViewDialog() {
        // Initialize rotation/refresh
        $ROTATION = new FrontendRotation($this->name);

        // Set the requested step
        if($this->type != '' && $this->step != '')
            $ROTATION->setStep($this->type, $this->step, $this->stepId);

        switch($this->type) {
            case '':
                // If no step given redirect to first step
                header('Location: ' . $ROTATION->getStepUrlById(0));
            break;
            case 'map':
            case 'url':
                header('Location: ' . $ROTATION->getCurrentStepUrl());
            break;
        }
    }
}
?>
