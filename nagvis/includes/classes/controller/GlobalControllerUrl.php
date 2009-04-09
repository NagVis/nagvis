<?php
/*****************************************************************************
 *
 * GlobalControllerDefault.php - Global url controller for nagvis
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
 * class GlobalControllerUrl
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalControllerUrl {

	public function __construct($url) {
		// Load the core
		$CORE = new GlobalCore();

		// Initialize the frontend
		$FRONTEND = new NagVisFrontend($CORE);
		
		$URL = new NagVisUrl($CORE, $url);

		// Build the page
		$FRONTEND->addBodyLines($FRONTEND->parseJs('oRotationProperties = '.$FRONTEND->getRotationPropertiesJson(1).';'));
		$FRONTEND->getHeaderMenu($CORE->MAINCFG->getValue('index','headermenu'), $CORE->MAINCFG->getValue('index', 'headertemplate'));
		$FRONTEND->addBodyLines($FRONTEND->parseJs($URL->parseJson()));
		$FRONTEND->addBodyLines($URL->getContents());

		// Print the page
		$FRONTEND->printPage();
	}
}
?>
