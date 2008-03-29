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
// Start the user session (This is needed by some caching mechanism)
@session_start();

// Include defines
require("../nagvis/includes/defines/global.php");
require("../nagvis/includes/defines/matches.php");

// Include functions
require("../nagvis/includes/functions/debug.php");

// Include needed global classes
require("../nagvis/includes/classes/GlobalMainCfg.php");
require("../nagvis/includes/classes/GlobalMapCfg.php");
require("../nagvis/includes/classes/GlobalLanguage.php");
require("../nagvis/includes/classes/GlobalPage.php");
require("../nagvis/includes/classes/GlobalBackendMgmt.php");

// Include needed wui specific classes
require("./includes/classes/WuiMainCfg.php");
require("./includes/classes/WuiMapCfg.php");

// Load the main configuration
$MAINCFG = new WuiMainCfg(CONST_MAINCFG);

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
		$BACKEND = new GlobalBackendMgmt($MAINCFG);
		
		// Do some validations
		if(!isset($_GET['backend_id']) || $_GET['backend_id'] == '') {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~backend_id', FALSE);
		} elseif(!isset($_GET['type']) || $_GET['type'] == '') {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~type', FALSE);
		} elseif(!$BACKEND->checkBackendInitialized($_GET['backend_id'], FALSE)) {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'backend:global');
			echo $LANG->getMessageText('backendNotInitialized', 'BACKENDID~'.$_GET['backend_id'], FALSE);
		} elseif(!method_exists($BACKEND->BACKENDS[$_GET['backend_id']],'getObjects')) {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('methodNotSupportedByBackend', 'METHOD~getObjects', FALSE);
		} else {
			// Input looks OK, handle the request...
			
			echo '[ ';
			echo '{ "name": "" }';
			// Read all objects of the requested type from the backend
			foreach($BACKEND->BACKENDS[$_GET['backend_id']]->getObjects($_GET['type'],'','') AS $arr) {
				echo ' ,{ "name": "'.$arr['name1'].'"}';
			}
			echo ']';
		}
	break;
	/*
	 * Get all services in the defined BACKEND of the defined HOST
	 */
	case 'getServices':
		// These values are submited by WUI requests:
		// $_GET['backend_id'], $_GET['host_name']
		
		// Initialize the backend
		$BACKEND = new GlobalBackendMgmt($MAINCFG);
		
		// Do some validations
		if(!isset($_GET['backend_id']) || $_GET['backend_id'] == '') {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~backend_id', FALSE);
		} elseif(!isset($_GET['host_name']) || $_GET['host_name'] == '') {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~host_name', FALSE);
		} elseif(!$BACKEND->checkBackendInitialized($_GET['backend_id'], FALSE)) {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'backend:global');
			echo $LANG->getMessageText('backendNotInitialized', 'BACKENDID~'.$_GET['backend_id'], FALSE);
		} elseif(!method_exists($BACKEND->BACKENDS[$_GET['backend_id']],'getObjects')) {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('methodNotSupportedByBackend', 'METHOD~getObjects', FALSE);
		} else {
			// Input looks OK, handle the request...
			
			echo '[ ';
			$i = 0;
			// Read all services of the given host
			foreach($BACKEND->BACKENDS[$_GET['backend_id']]->getObjects('service',$_GET['host_name'],'') AS $arr) {
				if($i != 0) {
					echo ', ';
				}
				echo '{ "host_name": "'.$arr['name1'].'", "service_description": "'.$arr['name2'].'"}';
				$i++;
			}
			echo ']';
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
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~map', FALSE);
		} elseif(!isset($_GET['mode']) || $_GET['mode'] == '') {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~mode', FALSE);
		} elseif($_GET['mode'] != 'read' && $_GET['mode'] != 'write') {
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('accessModeIsNotValid', 'MODE~'.$_GET['mode'], FALSE);
		} else {
			// Input looks OK, handle the request...
			
			// Initialize map configuration
			$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
			$MAPCFG->readMapConfig();
			
			echo '[ ';
			
			// Read the allowed users for the specified mode
			if($_GET['mode'] == 'read') {
				$arr = $MAPCFG->getValue('global', '0', 'allowed_user');
			} else {
				$arr = $MAPCFG->getValue('global', '0', 'allowed_for_config');
			}
			
			for($i = 0; count($arr) > $i; $i++) {
				if($i > 0) {
					echo ',';	
				}
				echo '\''.$arr[$i].'\' ';
			}
			echo ' ]';
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
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~backend_type', FALSE)."\n";
			echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~backend_id', FALSE);
		} else {
			// Input looks OK, handle the request...
			
			// If the backend_type is not set try to get the backend_type of the given
			// backend
			if($_GET['backend_type'] == '' && $_GET['backend_id'] != '') {
				$_GET['backend_type'] = $MAINCFG->getValue('backend_'.$_GET['backend_id'],'backendtype');
			}
			
			echo '[ ';
			$i = 0;
			
			// Check if the backend_type is set
			if($_GET['backend_type'] != '') {
				// Loop all options for this backend type
				foreach($MAINCFG->validConfig['backend']['options'][$_GET['backend_type']] AS $key => $opt) {
					echo "\t";
					if($i != 0) {
						echo ', ';
					}
					echo '{ '."\n";
					echo "\t\t".'"key": "'.$key.'" '."\n";
					foreach($opt AS $var => $val) {
						echo "\t\t".', "'.$var.'": "'.$val.'" '."\n";
					}
					
					// If the backend_id is given read the currently set value of the
					// backend
					if(isset($_GET['backend_id']) && $_GET['backend_id'] != '' && $MAINCFG->getValue('backend_'.$_GET['backend_id'],$key,TRUE) != '') {
						echo ',  "value": "'.$MAINCFG->getValue('backend_'.$_GET['backend_id'],$key,TRUE).'" ';
					}
					
					echo "\t".' }'."\n";
					$i++;
				}
			} else {
				$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
				echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~backend_type', FALSE);
			}
			echo ' ]';
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
			// Error handling
			$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
			echo $LANG->getMessageText('mustValueNotSet', 'ATTRIBUTE~image', FALSE);
		} else {
			// Input looks OK, handle the request...
			
			echo '[ ';
			$i = 0;
			// Loop all maps
			foreach($MAINCFG->getMaps() AS $var => $val) {
				// Initialize map configuration 
				$MAPCFG = new WuiMapCfg($MAINCFG,$val);
				$MAPCFG->readMapConfig();
				
				// If the map_image is used in the map list the map
				if($MAPCFG->getValue('global', 0,'map_image') == $_GET['image']) {
					if($i != 0) {
						echo ',';	
					}
					echo '"'.$val.'" ';
					$i++;
				}
			}
			echo ' ]';
		}
	break;
	/*
	 * Fallback
	 */
	default:
		$LANG = new GlobalLanguage($MAINCFG,'wui:ajaxHandler');
		echo $LANG->getMessageText('unknownAction', 'ACTION~'.$_GET['action'], FALSE);
	break;
}
?>

	



