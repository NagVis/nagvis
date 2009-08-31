<?php
/*****************************************************************************
 *
 * GlobalControllerAutomap.php - Global automap controller for nagvis
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
 * class GlobalControllerAutomap
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalControllerAutomap {

	public function __construct($automapEnv) {

		// Load the core
		$CORE = new GlobalCore();

		// Initialize map configuration
		$MAPCFG = new NagVisMapCfg($CORE, '__automap');

		// Read the map configuration file
		$MAPCFG->readMapConfig();

		// Initialize backend(s)
		$BACKEND = new GlobalBackendMgmt($CORE);

		// Initialize the frontend
		$FRONTEND = new NagVisFrontend($CORE, $MAPCFG, $BACKEND);

		// Build the page
		$FRONTEND->addBodyLines($FRONTEND->parseJs('oRotationProperties = '.$FRONTEND->getRotationPropertiesJson(1).';'));
		$FRONTEND->getHeaderMenu($MAPCFG->getValue('global',0 ,'header_menu'), $MAPCFG->getValue('global',0 ,'header_template'));
		$FRONTEND->getAutoMap($automapEnv);

		// Print the page
		$FRONTEND->printPage();
	}
}
?>
