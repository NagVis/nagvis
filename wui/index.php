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
require("../nagvis/includes/functions/oldPhpVersionFixes.php");

// This defines wether the GlobalFrontendMessage prints HTML or ajax error messages
define('CONST_AJAX' , FALSE);

// Load the core
$CORE = new WuiCore();

// If not set, initialize $_GET['page']
if(!isset($_GET['page'])) {
	$_GET['page'] = '';	
}

// Display the wanted page, if nothing is set, display the map
switch($_GET['page']) {
	case 'edit_config':
		$FRONTEND = new WuiEditMainCfg($CORE);
		$FRONTEND->getForm();
		$FRONTEND->printPage();
	break;
	case 'shape_management':
		$FRONTEND = new WuiShapeManagement($CORE);
		$FRONTEND->getForm();
	break;
	case 'background_management':
		$FRONTEND = new WuiBackgroundManagement($CORE);
		$FRONTEND->getForm();
	break;
	case 'map_management':
		$FRONTEND = new WuiMapManagement($CORE);
		$FRONTEND->getForm();
	break;
	case 'backend_management':
		$FRONTEND = new WuiBackendManagement($CORE);
		$FRONTEND->getForm();
	break;
	case 'addmodify':
		$MAPCFG = new WuiMapCfg($CORE, $_GET['map']);
		$MAPCFG->readMapConfig();
		
		if(!isset($_GET['coords'])) {
			$_GET['coords'] = '';
		}
		if(!isset($_GET['id'])) {
			$_GET['id'] = '';
		}
		
		$FRONTEND = new WuiAddModify($CORE, $MAPCFG, Array('action' => $_GET['action'],
															'type' => $_GET['type'],
															'id' => $_GET['id'],
															'coords' => $_GET['coords']));
		$FRONTEND->getForm();
	break;
	default:
		// Default is the wui map
		
		// Set empty map if none is set
		if(!isset($_GET['map'])) {
			$_GET['map'] = '';	
		}
		
		$MAPCFG = new WuiMapCfg($CORE, $_GET['map']);
		$MAPCFG->readMapConfig();
		
		$FRONTEND = new WuiFrontend($CORE, $MAPCFG);
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
