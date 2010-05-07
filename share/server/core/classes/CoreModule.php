<?php
/*******************************************************************************
 *
 * CoreModule.php - Abstract definition of a core module
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
	
	/**
	 * Tells if the module offers the requested action
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function offersAction($sAction) {
		return isset($this->aActions[$sAction]);
	}
	
	/**
	 * Stores the requested action in the module
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setAction($sAction) {
		if($this->offersAction($sAction)) {
			$this->sAction = $sAction;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Tells wether the requested action requires the users autorisation
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function actionRequiresAuthorisation() {
		return isset($this->aActions[$this->sAction]) && $this->aActions[$this->sAction] === REQUIRES_AUTHORISATION;
	}
	
	/**
	 * Tells wether the requested object is available
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function offersObject($sObject) {
		return isset($this->aObjects[$sObject]);
	}
	
	/**
	 * Stores the requested object name in the module
	 * when it is supported
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setObject($sObject) {
		if($this->offersObject($sObject)) {
			$this->sObject = $sObject;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 *  Returns the object string
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getObject() {
		return $this->sObject;
	}
	
	/**
	 * Checks if the users autorisation for this object should be checked.
	 * This does not perform the permission check!
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkForObjectAuthorisation() {
		return $this->sObject !== '';
	}
	
	protected function initUriHandler() {
		$this->UHANDLER = new CoreUriHandler($this->CORE);
	}
	
	protected function getCustomOptions($aKeys) {
		// Initialize on first call
		if($this->UHANDLER === null) {
			$this->initUriHandler();
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

	/**
	 * Is a dummy at this place. Some special modules like
	 * CoreModMap have no general way of fetching the called
	 * "object" because the value might come in different vars
	 * when using different actions. So these special modules
	 * can implement that by overriding this method.
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function initObject() {}

	
	/**
	 * This method needs to be implemented by each module
	 * to handle the user called action
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	abstract public function handleAction();
}
?>
