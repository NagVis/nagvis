<?php
/*****************************************************************************
 *
 * index.php - Main page of the WUI
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */

// Include defines
require("../nagvis/includes/defines/global.php");
require("../nagvis/includes/defines/matches.php");

// Include functions
require("../nagvis/includes/functions/autoload.php");
require("../nagvis/includes/functions/debug.php");
require("../nagvis/includes/functions/getuser.php");
require("../nagvis/includes/functions/oldPhpVersionFixes.php");

// This defines wether the GlobalFrontendMessage prints HTML or ajax error messages
define('CONST_AJAX' , FALSE);

// Load the core
$CORE = new WuiCore();

// Set empty map if none is set
if(!isset($_GET['map'])) {
	$_GET['map'] = '';	
}

$MAPCFG = new WuiMapCfg($CORE, $_GET['map']);
$MAPCFG->readMapConfig();

$FRONTEND = new WuiFrontend($CORE, $MAPCFG);
$FRONTEND->checkPreflight();
$FRONTEND->getMap();

if($_GET['map'] != '') {
	// Do preflight checks (before printing the map)
	if(!$MAPCFG->checkMapConfigWriteable(1)) {
		exit;
	}
	if(!$MAPCFG->checkMapLocked(1)) {
		// Lock the map for the defined time
		$MAPCFG->writeMapLock();
	}
}

// print the HTML page
$FRONTEND->printPage();

?>
