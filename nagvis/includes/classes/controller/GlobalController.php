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
	private $parameterValue = NULL;
	private $message = NULL;
	private $isValid = NULL;

	private $automapEnv = array(
									'backend' 		=> '',
									'root'			=> '',
									'maxLayers'		=> '',
									'renderMode'	=> '',
									'width'			=> '',
									'height'		=> '',
									'ignoreHosts'	=> '',
									'filterGroup'	=> '');

	/**
	 * Constructor
	 *
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __construct() {
		$action = '';
		
		// Get variables
		$httpRequest = new GlobalHttpRequest();
		$this->parameterNames = $httpRequest->getParameterNames();
		
		// Check if variable action is valid
		if(isset($this->parameterNames[0])) {
			$action = $this->parameterNames[0];
		}
		
		$actionValidator = new GlobalValidator('action', $action);
		
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
				if ($httpRequest->issetParameter('map')) {
					$mapName = $httpRequest->getParameter('map');
					$validator = new GlobalValidator('map', $mapName);
					if ($validator->isValid()) {
						$displayPage = new GlobalControllerMap($mapName);
						$this->isValid = TRUE;
					} else {
						$this->message = $validator->getMessage();
						$this->isValid = FALSE;
					}
				} else {
					$this->message = 'noMapSet';
					$this->isValid = FALSE;
				}
			break;

			case 'automap':
				// Check varibles
				foreach ($this->automapEnv AS $parameterName => $parameterValue) {
					if ($httpRequest->issetParameter($parameterName)) {
						$parameterValue = $httpRequest->getParameter($parameterName);
						$validator = new GlobalValidator($parameterName, $parameterValue);
						if ($validator->isValid()) {
							$this->automapEnv[$parameterName] = $parameterValue;
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
				if ($httpRequest->issetParameter('rotation')) {
					// Check map, when set
					if ($httpRequest->issetParameter('map')) {
						$mapName = $httpRequest->getParameter('map');
						$validator = new GlobalValidator('map', $mapName);
						if ($validator->isValid()) {
							$displayPage = new GlobalControllerRotation('map', $mapName);
							$this->isValid = TRUE;
						} else {
							$this->message = $validator->getMessage();
							$this->isValid = FALSE;
							break;
						}
					}

					// Check url, when set
					if ($httpRequest->issetParameter('url')) {
						$url = $httpRequest->getParameter('url');
						$validator = new GlobalValidator('url', $url);
						if ($validator->isValid()) {
							$displayPage = new GlobalControllerRotation('url', $url);
							$this->isValid = TRUE;
						} else {
							$this->message = $validator->getMessage();
							$this->isValid = FALSE;
							break;
						}
					}

					$displayPage = new GlobalControllerRotation('map');
					$this->isValid = TRUE;
				} else {
					$this->message = 'noRotationpoolSet';
					$this->isValid = FALSE;
				}
			break;

			case 'url':
				if ($httpRequest->issetParameter('url')) {
					$url = $httpRequest->getParameter('url');
					$validator = new GlobalValidator('url', $url);
					if ($validator->isValid()) {
						$displayPage = new GlobalControllerUrl($url);
						$this->isValid = TRUE;
					} else {
						$this->message = $validator->getMessage();
						$this->isValid = FALSE;
					}
				} else {
					$this->message = 'noUrlSet';
					$this->isValid = FALSE;
				}
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