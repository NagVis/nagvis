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
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(&$CORE) {
		$this->CORE = &$CORE;
		
		// Gathersall settings of this rotation
		$this->setRotationOptions();
	}
	
	/**
	 * Gathers all settings of this rotation
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function setRotationOptions() {
		if(isset($_GET['rotation']) && $_GET['rotation'] != '') {
			$this->setPoolName();
			
			// Check wether the pool is defined
			$this->checkPoolExists();
			
			// Set the array of steps
			$this->setSteps();
			
			// Set the current step
			$this->setCurrentStep();
			
			// Set tje next step
			$this->setNextStep();
		}
		
		// Gather step interval
		$this->setStepInterval();
	}
	
	private function setPoolName() {
		$this->strPoolName = $_GET['rotation'];
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
		if(isset($_GET['rotation']) && $_GET['rotation'] != '') {
			$this->intInterval = $this->CORE->MAINCFG->getValue('rotation_'.$this->strPoolName, 'interval');
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
	
	private function setSteps() {
		$steps = $this->CORE->MAINCFG->getValue('rotation_'.$this->strPoolName, 'maps');
		$this->arrSteps = explode(',', str_replace('"','',$steps));
	}
	
	private function checkPoolExists() {
		$steps = $this->CORE->MAINCFG->getValue('rotation_'.$this->strPoolName, 'maps');
		if(!isset($steps) || sizeof($steps) <= 0) {
			// Error Message (Map rotation pool does not exist)
			$FRONTEND = new GlobalPage($this->CORE);
			$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('mapRotationPoolNotExists','ROTATION~'.$this->strPoolName));
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
	public function getCurrentStep() {
		return $this->arrSteps[$this->intCurrentStep];
	}
	
	/**
	 * Gets the Next step to rotate to, if enabled
	 * If Next map is in [ ], it will be an absolute url
	 *
	 * @return	String  URL to rotate to
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getNextStep() {
		$strNextStep = '';
		if(isset($_GET['rotation']) && $_GET['rotation'] != '') {
			$strNextStep = $this->arrSteps[$this->intNextStep];
			
			if(preg_match("/^\[(.+)\]$/", $strNextStep, $arrRet)) {
				$strNextStep = 'index.php?rotation='.$this->strPoolName.'&url='.$arrRet[1];
			} else {
				$strNextStep = 'index.php?rotation='.$this->strPoolName.'&map='.$strNextStep;
			}
		}
		
		return $strNextStep;
	}
}
?>
