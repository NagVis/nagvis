<?php
/*****************************************************************************
 *
 * GlobalControllerIndex.php - Index Controller for NagVis
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
 * class GlobalControllerDefault
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalControllerIndex {
	public function __construct() {
		// Load the core
		$CORE = new GlobalCore();
		
		// Initialize backend(s)
		$BACKEND = new GlobalBackendMgmt($CORE);
		
		// Initialize the frontend
		$FRONTEND = new NagVisFrontend($CORE, '', $BACKEND, '');
		
		// Build the page
		$FRONTEND->addBodyLines($FRONTEND->parseJs('oRotationProperties = '.$FRONTEND->getRotationPropertiesJson(0).';'));
		$FRONTEND->getHeaderMenu($CORE->MAINCFG->getValue('index','headermenu'), $CORE->MAINCFG->getValue('index', 'headertemplate'));
		$FRONTEND->getIndexPage();
		
		// Print the page
		$FRONTEND->printPage();
	}
}
?>
