<?php
/*****************************************************************************
 *
 * GlobalControllerinfo.php - Global controller for info page
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
 * class GlobalControllerInfo
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalControllerInfo {

	public function __construct() {

		// Load the main configuration
		$MAINCFG = new GlobalMainCfg(CONST_MAINCFG);

		// Initialize the frontend
		$FRONTEND = new NagVisFrontend($MAINCFG);

		// Build the page
		$FRONTEND->addBodyLines($FRONTEND->getRefresh());
		$FRONTEND->getHeaderMenu();
		$FRONTEND->addBodyLines(new NagVisInfoPage($MAINCFG));
		$FRONTEND->getMessages();

		// Print the page
		$FRONTEND->printPage();
	}
}
?>
