<?php
/*****************************************************************************
 *
 * CoreRotation.php - Class represents all rotations in NagVis
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
class CoreRotation {
    private $sPoolName = NULL;

    private $arrSteps = Array();
    private $intInterval = NULL;

    private $intCurrentStep = NULL;
    private $intNextStep = NULL;
    private $strNextStep = NULL;

    public function __construct($sPoolName) {
        global $CORE, $AUTHORISATION;
        $this->sPoolName = $sPoolName;

        // Check wether the pool is defined
        if(!$this->checkPoolExists()) {
            throw new NagVisException(l('mapRotationPoolNotExists',
                                      Array('ROTATION' => htmlentities($this->sPoolName, ENT_COMPAT, 'UTF-8'))));
        }

        // Trigger the autorization backend to create new rotation permissions when needed
        // FIXME: maybe not the best place for that. But there is better central place to
        //        trigger thath
        foreach($CORE->getDefinedRotationPools() AS $name) {
            $AUTHORISATION->createPermission('Rotation', $name);
        }

        // Read the array of steps from configuration
        $this->gatherSteps();

        /* Sample structure in $this->arrSteps
         Array(
          [0] => Array
          (
            [label] => demo
            [map] => demo
            [url] =>
            [target] =>
            )
         )
        */

        // Form the steps in urls
        $this->createStepUrls();

        // Gather step interval
        $this->gatherStepInterval();
    }

    /**
     * PUBLIC stepExists()
     *
     * Checks if the state of given type and identifier exists
     *
     * @param   String    Type of the step (map,url)
     * @param   String    Step identifier map name, url, ...
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function stepExists($type, $step) {
        $bRet = false;

        // Loop all steps and check if this step exists
        foreach($this->arrSteps AS $intId => $arrStep) {
            if(isset($arrStep[$type]) && $arrStep[$type] === $step) {
                $bRet = true;
                break;
            }
        }

        return $bRet;
    }

    /**
     * PUBLIC setStep()
     *
     * Sets the current step
     *
     * @param   String    Type of the step (map,url)
     * @param   String    Step identifier map name, url, ...
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setStep($sType, $sStep, $iStepId = '') {
        // First check if the step exists
        if($this->stepExists($sType, $sStep)) {
            if($iStepId != '')
                $this->intCurrentStep = (int) $iStepId;
            else
                // Get position of current step in the array
                if($sStep !== '') {
                    foreach($this->arrSteps AS $iIndex => $arrStep) {
                        if(isset($arrStep[$sType]) && $arrStep[$sType] === $sStep) {
                            $this->intCurrentStep = $iIndex;
                            break;
                        }
                    }
                } else {
                    $this->intCurrentStep = 0;
                }

            // Set the next step after setting the current step
            $this->setNextStep();
        } else {
            throw new NagVisException(l('The requested step [STEP] of type [TYPE] does not exist in the rotation pool [ROTATION]',
                                      Array('ROTATION' => htmlentities($this->sPoolName, ENT_COMPAT, 'UTF-8'),
                                            'STEP'     => htmlentities($sStep, ENT_COMPAT, 'UTF-8'),
                                            'TYPE'     => htmlentities($sType, ENT_COMPAT, 'UTF-8'))));
        }
    }

    /**
     * Sets the next step to take
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function setNextStep() {
        if($this->intCurrentStep === FALSE || ($this->intCurrentStep + 1) >= sizeof($this->arrSteps)) {
            // if end of array reached, go to the beginning...
            $this->intNextStep = 0;
        } else {
            $this->intNextStep = $this->intCurrentStep + 1;
        }
    }

    /**
     * Sets the step intervall
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function gatherStepInterval() {
        if($this->sPoolName !== '') {
            $this->intInterval = cfg('rotation_'.$this->sPoolName, 'interval');
        } else {
            $this->intInterval = cfg('rotation', 'interval');
        }
    }

    /**
     * Sets the urls of each step in this pool
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function createStepUrls() {
        $htmlBase = cfg('paths', 'htmlbase');
        foreach($this->arrSteps AS $intId => $arrStep) {
            if(isset($arrStep['url']) && $arrStep['url'] != '') {
                $this->arrSteps[$intId]['target'] = $htmlBase.'/frontend/nagvis-js/index.php?mod=Url&act=view&show='.$arrStep['url'].'&rotation='.$this->sPoolName.'&rotationStep='.$intId;
            } else {
                $this->arrSteps[$intId]['target'] = $htmlBase.'/frontend/nagvis-js/index.php?mod=Map&act=view&show='.$arrStep['map'].'&rotation='.$this->sPoolName.'&rotationStep='.$intId;
            }
        }
    }

    /**
     * Sets the steps which are defined in this pool
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function gatherSteps() {
        $this->arrSteps = cfg('rotation_'.$this->sPoolName, 'maps');
    }

    /**
     * Checks if the specified rotation pool exists
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkPoolExists() {
        global $CORE;
        $pools = $CORE->getDefinedRotationPools();

        if(isset($pools[$this->sPoolName])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the next time to refresh or rotate in seconds
     *
     * @return	Integer		Returns The next rotation time in seconds
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getStepInterval() {
        return $this->intInterval;
    }

    /**
     * Gets the Next step to rotate to, if enabled
     * If Next map is in [ ], it will be an absolute url
     *
     * @return	String  URL to rotate to
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getCurrentStepLabel() {
        return $this->arrSteps[$this->intCurrentStep]['label'];
    }

    /**
     * Gets the Next step to rotate to, if enabled
     * If Next map is in [ ], it will be an absolute url
     *
     * @return	String  URL to rotate to
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getCurrentStepUrl() {
        return $this->arrSteps[$this->intCurrentStep]['target'];
    }

    /**
     * Gets the Next step to rotate to, if enabled
     * If Next map is in [ ], it will be an absolute url
     *
     * @return	String  URL to rotate to
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getNextStepUrl() {
        return $this->arrSteps[$this->intNextStep]['target'];
    }

    /**
     * Gets the name of the pool
     *
     * @return	Integer
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getPoolName() {
        return $this->sPoolName;
    }

    /**
     * Gets the url of a specific step
     *
     * @return	Integer
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getStepUrlById($intId) {
        return $this->arrSteps[$intId]['target'];
    }

    /**
     * Gets the label of a specific step
     *
     * @return	Integer
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getStepLabelById($intId) {
        return $this->arrSteps[$intId]['label'];
    }

    /**
     * Gets the number of steps
     *
     * @return	Integer
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getNumSteps() {
        return sizeof($this->arrSteps);
    }

    /**
     * PUBLIC getRotationPropertiesJson
     *
     * Gets the rotation properties for the current view as array
     *
     * @return  Array    Rotation properties
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getRotationProperties() {
        $arr = Array();

        if($this->sPoolName !== '') {
            $arr['rotationEnabled'] = 1;
            $arr['nextStepUrl'] = $this->getNextStepUrl();
            $arr['nextStepTime'] = $this->getStepInterval();
        } else {
            $arr['rotationEnabled'] = 0;
        }

        return $arr;
    }
}
?>
