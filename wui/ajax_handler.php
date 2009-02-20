<?php
/*****************************************************************************
 *
 * ajax_handler.php - Handler for Ajax request of WUI
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
require("../nagvis/includes/functions/ajaxErrorHandler.php");

// Include needed WUI specific functions
//FIXME: Remove this ...
require('./includes/functions/form_handler.php');

// This defines wether the GlobalFrontendMessage prints HTML or ajax error messages
define('CONST_AJAX' , TRUE);

// Load the core
$CORE = new WuiCore();

// Initialize var
if(!isset($_GET['action'])) {
	$_GET['action'] = '';
}

// Now do the requested action
switch($_GET['action']) {
	/*
	 * Get all objects in defined BACKEND if the defined TYPE
	 */
	case 'getObjects':
		// These values are submited by WUI requests:
		// $_GET['backend_id'], $_GET['type']
		
		// Initialize the backend
		$BACKEND = new GlobalBackendMgmt($CORE);
		
		// Do some validations
		if(!isset($_GET['backend_id']) || $_GET['backend_id'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~ackend_id'));
		} elseif(!isset($_GET['type']) || $_GET['type'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~type'));
		} elseif(!$BACKEND->checkBackendInitialized($_GET['backend_id'], FALSE)) {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('backendNotInitialized', 'BACKENDID~'.$_GET['backend_id']));
		} elseif(!method_exists($BACKEND->BACKENDS[$_GET['backend_id']],'getObjects')) {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('methodNotSupportedByBackend', 'METHOD~getObjects'));
		} else {
			// Input looks OK, handle the request...
			
			$aRet = Array(Array('name' => ''));
			// Read all objects of the requested type from the backend
			foreach($BACKEND->BACKENDS[$_GET['backend_id']]->getObjects($_GET['type'],'','') AS $arr) {
				$aRet[] = Array('name' => $arr['name1']);
			}
			
			echo json_encode($aRet);
		}
	break;
	/*
	 * Get all services in the defined BACKEND of the defined HOST
	 */
	case 'getServices':
		// These values are submited by WUI requests:
		// $_GET['backend_id'], $_GET['host_name']
		
		// Initialize the backend
		$BACKEND = new GlobalBackendMgmt($CORE);
		
		// Do some validations
		if(!isset($_GET['backend_id']) || $_GET['backend_id'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~ackend_id'));
		} elseif(!isset($_GET['host_name']) || $_GET['host_name'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~host_name'));
		} elseif(!$BACKEND->checkBackendInitialized($_GET['backend_id'], FALSE)) {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('backendNotInitialized', 'BACKENDID~'.$_GET['backend_id']));
		} elseif(!method_exists($BACKEND->BACKENDS[$_GET['backend_id']],'getObjects')) {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('methodNotSupportedByBackend', 'METHOD~getObjects'));
		} else {
			// Input looks OK, handle the request...
			
			$aRet = Array();
			// Read all services of the given host
			foreach($BACKEND->BACKENDS[$_GET['backend_id']]->getObjects('service',$_GET['host_name'],'') AS $arr) {
				$aRet[] = Array('host_name' => $arr['name1'], 'service_description' => $arr['name2']);
			}
			
			echo json_encode($aRet);
		}
	break;
	/*
	 * Get all users which are allowed to access the MAP in the defined MODE
	 */
	case 'getAllowedUsers':
		// These values are submited by WUI requests:
		// $_GET['map'], $_GET['mode']
		
		// Do some validations
		if(!isset($_GET['map']) || $_GET['map'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map'));
		} elseif(!isset($_GET['mode']) || $_GET['mode'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~mode'));
		} elseif($_GET['mode'] != 'read' && $_GET['mode'] != 'write') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('accessModeIsNotValid', 'MODE~'.$_GET['mode']));
		} else {
			// Input looks OK, handle the request...
			
			// Initialize map configuration
			$MAPCFG = new WuiMapCfg($CORE, $_GET['map']);
			$MAPCFG->readMapConfig();
			
			// Read the allowed users for the specified mode
			if($_GET['mode'] == 'read') {
				$arr = $MAPCFG->getValue('global', '0', 'allowed_user');
			} else {
				$arr = $MAPCFG->getValue('global', '0', 'allowed_for_config');
			}
			
			$aRet = Array();
			for($i = 0; count($arr) > $i; $i++) {
				$aRet[] = $arr[$i];
			}
			
			echo json_encode($aRet);
		}
	break;
	/*
	 * Get all options which are setable in the backends of the given TYPE. If the
	 * TYPE is not set the TYPE can be read by given BACKEND-ID. If the BACKEND-ID
	 * is set it returns also the currently set values of each option.
	 */
	case 'getBackendOptions':
		// These values are submited by WUI requests:
		// $_GET['backend_type'], ($_GET['backend_id'])
		
		// Do some validations
		if((!isset($_GET['backend_type']) || $_GET['backend_type'] == '') && (!isset($_GET['backend_id']) || $_GET['backend_id'] == '')) {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backend_id"/"backend_type'));
		} else {
			// Input looks OK, handle the request...
			
			// If the backend_type is not set try to get the backend_type of the given
			// backend
			if($_GET['backend_type'] == '' && $_GET['backend_id'] != '') {
				$_GET['backend_type'] = $CORE->MAINCFG->getValue('backend_'.$_GET['backend_id'],'backendtype');
			}
			
			$aRet = Array();
			// Check if the backend_type is set
			if($_GET['backend_type'] != '') {
				// Loop all options for this backend type
				$a = $CORE->MAINCFG->getValidObjectType('backend');
				foreach($a['options'][$_GET['backend_type']] AS $key => $opt) {
					$a = Array('key' => $key);
					
					foreach($opt AS $var => $val) {
						$a[$var] = $val;
					}
					
					// If the backend_id is given read the currently set value of the
					// backend
					if(isset($_GET['backend_id']) && $_GET['backend_id'] != '' && $CORE->MAINCFG->getValue('backend_'.$_GET['backend_id'],$key,TRUE) != '') {
						$a['value'] = $CORE->MAINCFG->getValue('backend_'.$_GET['backend_id'],$key,TRUE);
					}
					
					$aRet[] = $a;
				}
			} else {
				new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backend_type'));
			}
			
			echo json_encode($aRet);
		}
	break;
	/*
	 * Checks if the given IMAGE is used by any map
	 */
	case 'getMapImageInUse';
		// These values are submited by WUI requests:
		// $_GET['image']
		
		// Do some validations
		if(!isset($_GET['image']) || $_GET['image'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~image'));
		} else {
			// Input looks OK, handle the request...
			
			$aMaps = Array();
			// Loop all maps
			foreach($CORE->getAvailableMaps() AS $var => $val) {
				// Initialize map configuration 
				$MAPCFG = new WuiMapCfg($CORE, $val);
				$MAPCFG->readMapConfig();
				
				// If the map_image is used in the map list the map
				if($MAPCFG->getValue('global', 0,'map_image') == $_GET['image']) {
					$aMaps[] = $val;
				}
			}
			
			echo json_encode($aMaps);
		}
	break;
	/* This is the new ajax way changing attributes (Not using "properties" hacks)
	 * Modify an object of the given TYPE with the given ID on the given MAP
	 */
	case 'modifyMapObject':
		if(!isset($_GET['map']) || $_GET['map'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map'));
		} elseif(!isset($_GET['type']) || $_GET['type'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~type'));
		} else {
			$status = 'OK';
			$message = '';
			
			$MAPCFG = new WuiMapCfg($CORE, $_GET['map']);
			$MAPCFG->readMapConfig();
			
			$aOpts = $_GET;
			// Remove the parameters which are not options of the object
			unset($aOpts['action']);
			unset($aOpts['map']);
			unset($aOpts['id']);
			unset($aOpts['type']);
			unset($aOpts['timestamp']);
			
			// set options in the array
			foreach($aOpts AS $key => $val) {
				$MAPCFG->setValue($_GET['type'], $_GET['id'], $key, $val);
			}
			
			// write element to file
			$MAPCFG->writeElement($_GET['type'],$_GET['id']);
			
			// do the backup
			backup($CORE->MAINCFG, $_GET['map']);
			
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
				new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mapLockNotDeleted'));
			}
			
			echo json_encode(Array('status' => $status, 'message' => $message));
		}
	break;
	/* This is the new ajax way delete objects
	 * Delete an object of the given TYPE with the given ID from the given MAP
	 */
	case 'deleteMapObject':
		if(!isset($_GET['map']) || $_GET['map'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map'));
		} elseif(!isset($_GET['type']) || $_GET['type'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~type'));
		} elseif(!isset($_GET['id']) || $_GET['id'] == '') {
			new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~id'));
		} else {
			$status = 'OK';
			$message = '';
			
			// initialize map and read map config
			$MAPCFG = new WuiMapCfg($CORE, $_GET['map']);
			$MAPCFG->readMapConfig();
			
			// first delete element from array
			$MAPCFG->deleteElement($_GET['type'],$_GET['id']);
			// then write new array to file
			$MAPCFG->writeElement($_GET['type'],$_GET['id']);
					
			// do the backup
			backup($CORE->MAINCFG,$_GET['map']);
			
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
				new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mapLockNotDeleted'));
			}
			
			echo json_encode(Array('status' => $status, 'message' => $message));
		}
	break;
	/* Returns the formular contents for the WUI popup windows
	 */
	case 'getFormContents':
		switch($_GET['form']) {
			case 'addmodify':
				$MAPCFG = new WuiMapCfg($CORE, $_GET['map']);
				$MAPCFG->readMapConfig();
				
				if(!isset($_GET['coords'])) {
					$_GET['coords'] = '';
				}
				
				if(!isset($_GET['id'])) {
					$_GET['id'] = '';
				}
				
				if(!isset($_GET['viewType'])) {
					$_GET['viewType'] = '';
				}
				
				$FRONTEND = new WuiAddModify($CORE, $MAPCFG, Array('action' => $_GET['do'],
																	'type' => $_GET['type'],
																	'id' => $_GET['id'],
																	'coords' => $_GET['coords'],
																	'viewType' => $_GET['viewType']));
			break;
			case 'editMainCfg':
				$FRONTEND = new WuiEditMainCfg($CORE);
				$FRONTEND->getForm();
			break;
			case 'manageBackends':
				$FRONTEND = new WuiBackendManagement($CORE);
				$FRONTEND->getForm();
			break;
			case 'manageBackgrounds':
				$FRONTEND = new WuiBackgroundManagement($CORE);
				$FRONTEND->getForm();
			break;
			case 'manageShapes':
				$FRONTEND = new WuiShapeManagement($CORE);
				$FRONTEND->getForm();
			break;
			case 'manageMaps':
				$FRONTEND = new WuiMapManagement($CORE);
				$FRONTEND->getForm();
			break;
		}
		
		echo json_encode(Array('code' => $FRONTEND->getForm()));
	break;
	/*
	 * Fallback
	 */
	default:
		new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('unknownAction', 'ACTION~'.$_GET['action']));
	break;
}
?>

	



