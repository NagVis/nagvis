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
// Start the user session (This is needed by some caching mechanism)
@session_start();

// Include defines
require("../nagvis/includes/defines/global.php");
require("../nagvis/includes/defines/matches.php");

// Include functions
require("../nagvis/includes/functions/debug.php");
require("../nagvis/includes/functions/json.php");

// Include needed global classes
require("../nagvis/includes/classes/GlobalMainCfg.php");
require("../nagvis/includes/classes/GlobalMapCfg.php");
require("../nagvis/includes/classes/GlobalLanguage.php");
require("../nagvis/includes/classes/GlobalPage.php");
require("../nagvis/includes/classes/GlobalMap.php");
require("../nagvis/includes/classes/GlobalBackground.php");
require("../nagvis/includes/classes/GlobalGraphic.php");

// Include needed wui specific classes
require("./includes/classes/WuiMainCfg.php");
require("./includes/classes/WuiMapCfg.php");

// Load the main configuration
$MAINCFG = new WuiMainCfg(CONST_MAINCFG);

// If not set, initialize $_GET['page']
if(!isset($_GET['page'])) {
	$_GET['page'] = '';	
}

// Display the wanted page, if nothing is set, display the map
switch($_GET['page']) {
	case 'edit_config':
		// Include page specific global/wui classes
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiEditMainCfg.php");
		
		$FRONTEND = new WuiEditMainCfg($MAINCFG);
		$FRONTEND->getForm();
		$FRONTEND->printPage();
	break;
	case 'shape_management':
		// Include page specific global/wui classes
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiShapeManagement.php");
		
		$FRONTEND = new WuiShapeManagement($MAINCFG);
		$FRONTEND->getForm();
	break;
	case 'background_management':
		// Include page specific global/wui classes
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiBackgroundManagement.php");
		
		$FRONTEND = new WuiBackgroundManagement($MAINCFG);
		$FRONTEND->getForm();
	break;
	case 'map_management':
		// Include page specific global/wui classes
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiMapManagement.php");
		
		$FRONTEND = new WuiMapManagement($MAINCFG);
		$FRONTEND->getForm();
	break;
	case 'backend_management':
		// Include page specific global/wui classes
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiBackendManagement.php");
		
		$FRONTEND = new WuiBackendManagement($MAINCFG);
		$FRONTEND->getForm();
	break;
	case 'addmodify':
		// Include page specific global/wui classes
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiAddModify.php");
		
		$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
		$MAPCFG->readMapConfig();
		
		if(!isset($_GET['coords'])) {
			$_GET['coords'] = '';
		}
		if(!isset($_GET['id'])) {
			$_GET['id'] = '';
		}
		
		$FRONTEND = new WuiAddModify($MAINCFG,$MAPCFG,Array('action' => $_GET['action'],
															'type' => $_GET['type'],
															'id' => $_GET['id'],
															'coords' => $_GET['coords']));
		$FRONTEND->getForm();
	break;
	default:
		// Default is the wui map
		
		// Include page specific global/wui classes
		require("./includes/classes/WuiFrontend.php");
		require("./includes/classes/WuiMap.php");
		
		// Set empty map if none is set
		if(!isset($_GET['map'])) {
			$_GET['map'] = '';	
		}
		
		$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
		$MAPCFG->readMapConfig();
		
		$FRONTEND = new WuiFrontend($MAINCFG,$MAPCFG);
		$FRONTEND->checkPreflight();
		$FRONTEND->getMap();
		$FRONTEND->getMessages();
		
		if($_GET['map'] != '') {
			// Do preflight checks (before printing the map)
			if(!$MAPCFG->checkMapConfigWriteable(1)) {
				exit;
			}
			if(!$MAPCFG->BACKGROUND->checkFileExists(1)) {
				exit;
			}
			if(!$MAPCFG->BACKGROUND->checkFileReadable(1)) {
				exit;
			}
			if(!$MAPCFG->checkMapLocked(1)) {
				// Lock the map for the defined time
				$MAPCFG->writeMapLock();
			}
		}
	break;
}
		
// print the HTML page
$FRONTEND->printPage();
?>
