<?php
/*****************************************************************************
 *
 * GlobalControllerRotation.php - Global rotation controller for nagvis
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
 * class GlobalControllerRotation
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalControllerRotation {

	public function __construct($type = NULL, $mapName = NULL) {
		// Load the core
		$CORE = new GlobalCore();
		
		// Initialize rotation/refresh
		$ROTATION = new NagVisRotation($CORE);
		
		// If no step given redirect to first step
		if(!$type) {
			// Redirect to next page
			header('Location: '.$ROTATION->getNextStepUrl());
		}
		
		if ($type == 'map') {
			new GlobalControllerMap($mapName);
		} elseif ($type == 'url') {
			new GlobalControllerUrl($mapName);
		}
	}
}