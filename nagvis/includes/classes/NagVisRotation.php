<?php
/*****************************************************************************
 *
 * NagVisRotation.php - Class represents all rotations in NagVis
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
class NagVisRotation {
	private $CORE;
	
	private $strPoolName = NULL;
	
	private $arrSteps = Array();
	private $intInterval = NULL;
	
	private $intCurrentStep = NULL;
	private $intNextStep = NULL;
	private $strNextStep = NULL;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @param   String  Name of the rotation pool
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $strPoolName = NULL) {
		$this->CORE = $CORE;
		
		if($strPoolName == NULL && isset($_GET['rotation']) && $_GET['rotation'] != '') {
			$strPoolName = $_GET['rotation'];
		}
		
		// Gathersall settings of this rotation
		$this->setRotationOptions($strPoolName);
	}
	
	/**
	 * Gathers all settings of this rotation
	 *
	 * @param   String  Name of the rotation pool
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function setRotationOptions($strPoolName) {
		if($strPoolName != NULL) {
			$this->setPoolName($strPoolName);
			
			// Check wether the pool is defined
			$this->checkPoolExists();
			
			// Set the array of steps
			$this->setSteps();
			
			// Form the steps in urls
			$this->setStepUrls();
			
			// Set the current step
			$this->setCurrentStep();
			
			// Set the next step
			$this->setNextStep();
			
		}
		
		// Gather step interval
		$this->setStepInterval();
	}
	
	/**
	 * Sets the name of this rotation pool
	 *
	 * @param   String  Name of the rotation pool
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function setPoolName($strPoolName) {
		$this->strPoolName = $strPoolName;
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
	private function setStepInterval() {
		if($this->getPoolName() != '') {
			$this->intInterval = $this->CORE->MAINCFG->getValue('rotation_'.$this->getPoolName(), 'interval');
		} else {
			$this->intInterval = $this->CORE->MAINCFG->getValue('rotation', 'interval');
		}
	}
	
	/**
	 * Sets the current step
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function setCurrentStep() {
		$strCurrentStep = '';
		$strType = '';
		
		if(isset($_GET['url']) && $_GET['url'] != '') {
			$strCurrentStep = $_GET['url'];
			$strType = 'url';
		} elseif(isset($_GET['map']) && $_GET['map'] != '') {
			$strCurrentStep = $_GET['map'];
			$strType = 'map';
		}
		
		// Set position of actual map in the array
		if($strCurrentStep != '') {
			foreach($this->arrSteps AS $intId => $arrStep) {
				if($strCurrentStep == $arrStep[$strType]) {
					$this->intCurrentStep = $intId;
					continue;
				}
			}
		} else {
			$this->intCurrentStep = 0;
		}
	}
	
	/**
	 * Sets the urls of each step in this pool
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function setStepUrls() {
		foreach ($this->arrSteps AS $intId => $arrStep) {
			if(isset($arrStep['url']) && $arrStep['url'] != '') {
				$this->arrSteps[$intId]['target'] = 'index.php?rotation='.$this->getPoolName().'&url='.$arrStep['url'];
			} else {
				$this->arrSteps[$intId]['target'] = 'index.php?rotation='.$this->getPoolName().'&map='.$arrStep['map'];
			}
		}
	}
	
	/**
	 * Sets the steps which are defined in this pool
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function setSteps() {
		$this->arrSteps = $this->CORE->MAINCFG->getValue('rotation_'.$this->getPoolName(), 'maps');
	}
	
	/**
	 * Checks if the specified rotation pool exists
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkPoolExists() {
		if(array_search($this->strPoolName, $this->CORE->getDefinedRotationPools()) === FALSE) {
			// Error Message (Map rotation pool does not exist)
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('mapRotationPoolNotExists','ROTATION~'.$this->getPoolName()));
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
		return $this->strPoolName;
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
	 * Gets the array of steps
	 *
	 * @return	Integer
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSteps() {
		return $this->arrSteps;
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
}
?>
