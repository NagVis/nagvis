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
class GlobalController {

	private $action = NULL;
	private $parameterNames = NULL;

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
		$validator = new GlobalValidator('action', $this->parameterNames[0]);

		// Set first action
		if ($validator->isValid()) {
			$this->action = $this->parameterNames[0];
		} else {
			$this->action = 'default';
		}

		switch ($this->action) {
			case 'default':
				$displayPage = new GlobalControllerDefault();
				break;

			case 'info':
				$displayPage = new GlobalControllerInfo();
				break;

			case 'map':
				$displayPage = new GlobalControllerMap();
				break;

			case 'automap':
				$displayPage = new GlobalControllerAutomap();
				break;

			case 'rotation':
				$displayPage = new GlobalControllerRotation();
				break;

			case 'url':
				$displayPage = new GlobalControllerUrl();
				break;
		}
	}
}
?>