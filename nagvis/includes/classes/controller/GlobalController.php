<?php
/*****************************************************************************
 *
 * GlobalController.php - Global controller for NagVis
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: michael_luebben@web.de)
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
 * class GlobalController
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalController implements GlobalControllerInterface {

	private $action = NULL;
	private $parameterNames = NULL;
	private $parameterName = NULL;
	private $parameterValue = NULL;
	private $message = NULL;
	private $isValid = NULL;

	private $mapName = NULL;

	private $automapEnv = array(
									'backend' 		=> '',
									'root'			=> '',
									'maxLayers'		=> '',
									'renderMode'	=> '',
									'width'			=> '',
									'height'			=> '',
									'ignoreHosts'	=> '',
									'filterGroup'	=> '');

	/**
	 * Constructor
	 *
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __construct() {

		// Get variables
		$httpRequest = new GlobalHttpRequest();
		$this->parameterNames = $httpRequest->getParameterNames();

		// Check if variable action is valid
		$actionValidator = new GlobalValidator('action', $this->parameterNames[0]);

		// Set first action
		if ($actionValidator->isValid()) {
			$this->action = $this->parameterNames[0];
		} else {
			$this->action = 'default';
			$this->message = 'setActionToDefault';
		}

		switch ($this->action) {
			case 'default':
				$displayPage = new GlobalControllerDefault();
				$this->isValid = TRUE;
				break;

			case 'info':
				$displayPage = new GlobalControllerInfo();
				$this->isValid = TRUE;
				break;

			case 'map':
				$this->parameterName = 'map';
				if ($httpRequest->issetParameter($this->parameterName)) {
					$this->mapName = $httpRequest->getParameter($this->parameterName);
					$mapValidator = new GlobalValidator($this->parameterName, $this->mapName);
					if ($mapValidator->isValid()) {
						$displayPage = new GlobalControllerMap($this->mapName);
						$this->isValid = TRUE;
					} else {
						$this->message = $mapValidator->getMessage();
						$this->isValid = FALSE;
					}
				} else {
					$this->message = 'noMapSet';
					$this->isValid = FALSE;
				}
				break;

			case 'automap':
				// Check varibles
				foreach ($this->automapEnv as $this->parameterName => $parameterValue) {
					if ($httpRequest->issetParameter($this->parameterName)) {
						$this->parameterValue = $httpRequest->getParameter($this->parameterName);
						$validator = new GlobalValidator($this->parameterName, $this->parameterValue);
						if ($validator->isValid()) {
							$this->automapEnv[$this->parameterName] = $this->parameterValue;
							$this->isValid = TRUE;
						} else {
							$this->message = $validator->getMessage();
							$this->isValid = FALSE;
							break;
						}
					} else {
						$this->isValid = TRUE;
					}	
				}

				if ($this->isValid) {
					$displayPage = new GlobalControllerAutomap($this->automapEnv);
				}
				break;

			case 'rotation':
				$displayPage = new GlobalControllerRotation();
				break;

			case 'url':
				$displayPage = new GlobalControllerUrl();
				break;
		}
	}

	/**
	 * Get set action
	 *
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Return message
	 *
	 * @return  string   Message from object
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Check if object is valid
	 *
	 * @return
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function isValid() {
		return $this->isValid;
	}

	/**
	 * Get name from parameter
	 *
	 * @return  string   Name from parameter
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameterName() {
		return $this->parameterName;
	}
}
?>