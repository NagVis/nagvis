<?php
/*****************************************************************************
 *
 * index.php - Main page of the WUI
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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

// Include global defines
require('../../server/core/defines/global.php');
require('../../server/core/defines/matches.php');

// Include wui related defines
require('defines/wui.php');

// Include functions
require("../../server/core/functions/autoload.php");
require("../../server/core/functions/debug.php");
require("../../server/core/functions/getuser.php");
require("../../server/core/functions/oldPhpVersionFixes.php");

// This defines wether the GlobalMessage prints HTML or ajax error messages
define('CONST_AJAX' , FALSE);

// Load the core
// Initialize the core
$CORE = WuiCore::getInstance();

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
