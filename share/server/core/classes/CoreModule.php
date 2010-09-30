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
	protected $sName = '';
	protected $sAction = '';
	protected $sObject = null;
	
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
		if(!$this->offersAction($sAction))
			return false;

		$this->sAction = $sAction;
		return true;
	}
	
	/**
	 * Tells wether the requested action requires the users autorisation
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function actionRequiresAuthorisation() {
		return isset($this->aActions[$this->sAction]) && $this->aActions[$this->sAction] !== !REQUIRES_AUTHORISATION;
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
		if(!$this->offersObject($sObject))
			return false;

		$this->sObject = $sObject;
		return true;
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
	 * Checks if the user is permitted to perform the requested action
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function isPermitted() {
		$authorized = true;
		if(!isset($this->AUTHORISATION))
			$authorized = false;

		// Maybe the requested action is summarized by some other
		$action = !is_bool($this->aActions[$this->sAction]) ? $this->aActions[$this->sAction] : $this->sAction;

		if(!$this->AUTHORISATION->isPermitted($this->sName, $action, $this->sObject))
			$authorized = false;

		if(!$authorized)
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You are not permitted to access this page'),
			                                                   null, $CORE->getLang()->getText('Access denied'));
	}
	
	/**
	 * Initializes the URI handler object
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function initUriHandler() {
		$this->UHANDLER = new CoreUriHandler($this->CORE);
	}
	
	/**
	 * Reads a list of custom variables from the request
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function getCustomOptions($aKeys, $aDefaults = Array()) {
		// Initialize on first call
		if($this->UHANDLER === null)
			$this->initUriHandler();
		
		// Load the specific params to the UriHandler
		$this->UHANDLER->parseModSpecificUri($aKeys, $aDefaults);
		
		// Now get those params
		$aReturn = Array();
		foreach($aKeys AS $key => $val)
			$aReturn[$key] = $this->UHANDLER->get($key);
		
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

	/**
	 * Helper function to handle default form responses
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function handleResponse($validationHandler, $action, $successMsg, $failMessage, $reload = null, $redirectUrl = null) {
		$aReturn = $this->{$validationHandler}();

		$type = 'NOTE';
		$msg = '';
		if($aReturn !== false)
			if($this->{$action}($aReturn))
				$msg = $successMsg;
			else {
				$type = 'ERROR';
				$msg = $failMessage;
			}
		else {
			$type = 'ERROR';
			$msg = $this->CORE->getLang()->getText('You entered invalid information.');
		}
		new GlobalMessage($type, $msg, null, null, $reload, $redirectUrl);
	}

	/**
	 * Checks if the listed values are set. Otherwise it raises and error message
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function verifyValuesSet($HANDLER, $list) {
		foreach($list AS $key)
			if(!$HANDLER->isSetAndNotEmpty($key))
				new GlobalMessage('ERROR', $this->CORE->getLang()->getText('mustValueNotSet',
			                                                      Array('ATTRIBUTE' => $key)));
	}
}
?>
