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
	
	private $strPoolName;
	
	private $arrSteps;
	private $intInterval;
	
	private $intCurrentStep;
	private $intNextStep;
	private $strNextStep;
	
	private $arrStepUrls;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(&$CORE, $strPoolName = NULL) {
		$this->CORE = &$CORE;
		
		if($strPoolName == NULL && isset($_GET['rotation']) && $_GET['rotation'] != '') {
			$strPoolName = $_GET['rotation'];
		}
		
		// Gathersall settings of this rotation
		$this->setRotationOptions($strPoolName);
	}
	
	/**
	 * Gathers all settings of this rotation
	 *
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
	
	private function setPoolName($strPoolName) {
		$this->strPoolName = $strPoolName;
	}
	
	private function setNextStep() {
		if($this->intCurrentStep === FALSE || ($this->intCurrentStep + 1) >= sizeof($this->arrSteps)) {
			// if end of array reached, go to the beginning...
			$this->intNextStep = 0;
		} else {
			$this->intNextStep = $this->intCurrentStep + 1;
		}
	}
	
	private function setStepInterval() {
		if($this->getPoolName() != '') {
			$this->intInterval = $this->CORE->MAINCFG->getValue('rotation_'.$this->getPoolName(), 'interval');
		} else {
			$this->intInterval = $this->CORE->MAINCFG->getValue('rotation', 'interval');
		}
	}
	
	private function setCurrentStep() {
		$strCurrentStep = '';
		
		if(isset($_GET['url']) && $_GET['url'] != '') {
			$strCurrentStep = '['.$_GET['url'].']';
		} elseif(isset($_GET['map']) && $_GET['map'] != '') {
			$strCurrentStep = $_GET['map'];
		}
		
		// Set position of actual map in the array
		$this->intCurrentStep = array_search($strCurrentStep, $this->arrSteps);
	}
	
	private function setStepUrls() {
		$this->arrStepUrls = $this->arrSteps;
		
		foreach ($this->arrStepUrls AS $id => $step) {
			if(preg_match("/^\[(.+)\]$/", $step, $arrRet)) {
				$this->arrStepUrls[$id] = 'index.php?rotation='.$this->getPoolName().'&url='.$arrRet[1];
			} else {
				$this->arrStepUrls[$id] = 'index.php?rotation='.$this->getPoolName().'&map='.$step;
			}
		}
	}
	
	private function setSteps() {
		$steps = $this->CORE->MAINCFG->getValue('rotation_'.$this->getPoolName(), 'maps');
		$this->arrSteps = explode(',', str_replace('"','',$steps));
	}
	
	private function checkPoolExists() {
		$steps = $this->CORE->MAINCFG->getValue('rotation_'.$this->getPoolName(), 'maps');
		
		if(!isset($steps) || sizeof($steps) <= 0) {
			// Error Message (Map rotation pool does not exist)
			$FRONTEND = new GlobalPage($this->CORE);
			$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('mapRotationPoolNotExists','ROTATION~'.$this->getPoolName()));
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
	public function getCurrentStepUrl() {
		return $this->arrStepUrls[$this->intCurrentStep];
	}
	
	/**
	 * Gets the Next step to rotate to, if enabled
	 * If Next map is in [ ], it will be an absolute url
	 *
	 * @return	String  URL to rotate to
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getNextStepUrl() {
		return $this->arrStepUrls[$this->intNextStep];
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
	public function getStepUrlById($id) {
		return $this->arrStepUrls[$id];
	}
	
	/**
	 * Gets the name of a specific step
	 *
	 * @return	Integer
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getStepById($id) {
		return $this->arrSteps[$id];
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
