<?php
/*******************************************************************************
 *
 * CoreModule.php - Abstract definition of a core module
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
abstract class CoreModule {
	protected $CORE = null;
	protected $AUTHENTICATION = null;
	protected $AUTHORISATION = null;
	protected $UHANDLER = null;
	
	protected $aActions = Array();
	protected $aObjects = Array();
	protected $sAction = '';
	protected $sObject = '';
	
	public function passAuth($AUTHENTICATION, $AUTHORISATION) {
		$this->AUTHENTICATION = $AUTHENTICATION;
		$this->AUTHORISATION = $AUTHORISATION;
	}
	
	public function offersAction($sAction) {
		if(isset($this->aActions[$sAction])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function setAction($sAction) {
		if($this->offersAction($sAction)) {
			$this->sAction = $sAction;
			return true;
		} else {
			return false;
		}
	}
	
	public function actionRequiresAuthorisation() {
		$bRequiresAuthorisation = false;
		
		if(isset($this->aActions[$this->sAction]) && $this->aActions[$this->sAction] === REQUIRES_AUTHORISATION) {
			$bRequiresAuthorisation = true;
		}
		
		return $bRequiresAuthorisation;
	}
	
	public function offersObject($sObject) {
		if(isset($this->aObjects[$sObject])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function setObject($sObject) {
		if($this->offersObject($sObject)) {
			$this->sObject = $sObject;
			return true;
		} else {
			return false;
		}
	}
	
	public function getObject() {
		return $this->sObject;
	}
	
	public function checkForObjectAuthorisation() {
		$bRet = false;
		
		if($this->sObject !== '') {
			$bRet = true;
		}
		
		return $bRet;
	}
	
	protected function getCustomOptions($aKeys) {
		// Initialize on first call
		if($this->UHANDLER === null) {
			$this->UHANDLER = new CoreUriHandler($this->CORE);
		}
		
		// Load the specific params to the UriHandler
		$this->UHANDLER->parseModSpecificUri($aKeys);
		
		// Now get those params
		$aReturn = Array();
		foreach($aKeys AS $key => $val) {
			$aReturn[$key] = $this->UHANDLER->get($key);
		}
		
		return $aReturn;
	}
	
	abstract public function handleAction();
}
?>