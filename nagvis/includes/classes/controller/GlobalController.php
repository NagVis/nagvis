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
 * The global controller controls the requested actions (map, rotation, etc.)
 * and checks if the variables are valid.
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalController implements GlobalControllerInterface {

	// Contains the set action (map, url, rotation etc...)
	private $action = NULL;

	// Contains the check parameter
	private $checkedParameterName = NULL;

	// Contains if variable is valid (TRUE or FALSE)
	private $isValid = NULL;

	// Contains error message
	private $setMessage = NULL;

	// This array contains possible variables for the automap
	private $automapEnv = array(
									'backend' 		=> '',
									'root'			=> '',
									'maxLayers'		=> '',
									'renderMode'	=> '',
									'width'			=> '',
									'height'		=> '',
									'ignoreHosts'	=> '',
									'filterGroup'	=> '');

	// Contains object which displays the page
	private $displayPage = NULL;

	// Contains object from validator
	private $validator = NULL;

	/**
	 * The constructor checks if the first variable (action) is set and valid.
	 * If nothing is set, the constructor sets to default.
	 *
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __construct() {
		$action = '';
		
		// Get variables
		$httpRequest = new GlobalHttpRequest();
		$this->parameterNames = $httpRequest->getParameterNames();
		
		// Check if isset th first variable (action)
		if(isset($this->parameterNames[0])) {
			$action = $this->parameterNames[0];
		}

		// Check if the first variable (action) is valid
		$this->checkedParameterName = 'action';
		$this->validator = new GlobalValidator('action', $action);
		
		// Is this first variable (action) valid, then set this variable else set to default
		if ($this->validator->isValid()) {
			$this->action = $this->parameterNames[0];
		} else {
			$this->action = 'default';
			$this->setMessage = 'ControllerSetActionToDefault';
		}

		// Switch to set action
		switch ($this->action) {

			// Display index page
			case 'default':
				$this->displayPage = new GlobalControllerIndex();
				$this->checkedParameterName = 'default';
				$this->isValid = TRUE;
			break;

			// Display info page
			case 'info':
				$this->displayPage = new GlobalControllerInfo();
				$this->checkedParameterName = 'info';
				$this->isValid = TRUE;
			break;

			// Display set map
			case 'map':
				// Check variables for the automap
				$this->checkedParameterName = 'map';
				$mapName = $httpRequest->getParameter('map');
				if ($httpRequest->issetParameter('map')) {
					$this->validator = new GlobalValidator('map', $mapName);
					if ($this->validator->isValid()) {
						$this->displayPage = new GlobalControllerMap($mapName);
						$this->isValid = TRUE;
					} else {
						$this->setMessage = $this->validator->getMessage();
						$this->isValid = FALSE;
					}
				} else {
					$this->setMessage = 'controllerNoMapSet';
					$this->isValid = FALSE;
				}
			break;

			// Diplay automap
			case 'automap':
				// Check variables for the automap
				$this->checkedParameterName = 'automap';
				foreach ($this->automapEnv AS $parameterName => $parameterValue) {
					$this->checkedParameterName = $parameterName;
					if ($httpRequest->issetParameter($parameterName)) {
						$parameterValue = $httpRequest->getParameter($parameterName);
						$this->validator = new GlobalValidator($parameterName, $parameterValue);
						if ($this->validator->isValid()) {
							$this->automapEnv[$parameterName] = $parameterValue;
							$this->isValid = TRUE;
						} else {
							$this->setMessage = $this->validator->getMessage();
							$this->isValid = FALSE;
							break;
						}
					} else {
						$this->isValid = TRUE;
					}	
				}

				// If all set variables are valid, then display the automap
				if ($this->isValid) {
					$this->displayPage = new GlobalControllerAutomap($this->automapEnv);
				}
			break;

			// Display rotation pool
			case 'rotation':
				$this->checkedParameterName = 'rotation';
				if ($httpRequest->issetParameter('rotation')) {
					// Check map, when set
					if ($httpRequest->issetParameter('map')) {
						$this->checkedParameterName = 'map';
						$mapName = $httpRequest->getParameter('map');
						$this->validator = new GlobalValidator('map', $mapName);
						if ($this->validator->isValid()) {
							$this->displayPage = new GlobalControllerRotation('map', $mapName);
							$this->isValid = TRUE;
						} else {
							$this->setMessage = $this->validator->getMessage();
							$this->isValid = FALSE;
							break;
						}
					}

					// Check url, when set
					if ($httpRequest->issetParameter('url')) {
						$this->checkedParameterName = 'url';
						$url = $httpRequest->getParameter('url');
						$this->validator = new GlobalValidator('url', $url);
						if ($this->validator->isValid()) {
							$this->displayPage = new GlobalControllerRotation('url', $url);
							$this->isValid = TRUE;
						} else {
							$this->setMessage = $this->validator->getMessage();
							$this->isValid = FALSE;
							break;
						}
					}

					$this->displayPage = new GlobalControllerRotation();
					$this->isValid = TRUE;
				} else {
					$this->setMessage = 'controllerNoRotationpoolSet';
					$this->isValid = FALSE;
				}
			break;

			// Display url
			case 'url':
				if ($httpRequest->issetParameter('url')) {
					$url = $httpRequest->getParameter('url');
					$this->validator = new GlobalValidator('url', $url);
					if ($this->validator->isValid()) {
						$this->displayPage = new GlobalControllerUrl($url);
						$this->isValid = TRUE;
					} else {
						$this->setMessage = $this->validator->getMessage();
						$this->isValid = FALSE;
					}
				} else {
					$this->setMessage = 'controllerNoUrlSet';
					$this->isValid = FALSE;
				}
			break;
		}
	}

	/**
	 * Return set action
	 *
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Return message with error etc.
	 *
	 * @return  string   Message from object
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getMessage() {
		return $this->setMessage;
	}

	/**
	 * Return if object is valid
	 *
	 * @return
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function isValid() {
		return $this->isValid;
	}

	/**
	 * Return name from parameter
	 *
	 * @return  string   Name from parameter
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameterName() {
		return $this->checkedParameterName;
	}
}
?>