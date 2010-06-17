<?php
/*****************************************************************************
 *
 * ajax_handler.php - Handler for Ajax request of WUI
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
require("../../server/core/functions/oldPhpVersionFixes.php");
require('../../server/core/classes/CoreExceptions.php');

// This defines wether the GlobalMessage prints HTML or ajax error messages
define('CONST_AJAX' , TRUE);

// Initialize the core
$CORE = WuiCore::getInstance();

// Initialize var
if(!isset($_GET['action'])) {
	$_GET['action'] = '';
}

// Now do the requested action
switch($_GET['action']) {
	/*
	 * Get all objects in defined BACKEND if the defined TYPE
	 * Get all services in the defined BACKEND of the defined HOST
	 */
	case 'getObjects':
	case 'getServices':
		// These values are submited by WUI requests:
		// $_GET['backend_id'], $_GET['type']
		
		// Initialize the backend
		$BACKEND = new CoreBackendMgmt($CORE);
		
		// Do some validations
		if(!isset($_GET['backend_id']) || $_GET['backend_id'] == '')
			new GlobalMessage('ERROR', $CORE->getLang()->getText('mustValueNotSet1', 'ATTRIBUTE~backend_id'));

		if($_GET['action'] === 'getObjects' && (!isset($_GET['type']) || $_GET['type'] == ''))
			new GlobalMessage('ERROR', $CORE->getLang()->getText('mustValueNotSet1', 'ATTRIBUTE~type'));

		// Special validation for getServices
		if($_GET['action'] === 'getServices' && (!isset($_GET['host_name']) || $_GET['host_name'] == ''))
			new GlobalMessage('ERROR', $CORE->getLang()->getText('mustValueNotSet1', 'ATTRIBUTE~host_name'));

		try {
			if(!method_exists($BACKEND->getBackend($_GET['backend_id']),'getObjects')) {
				new GlobalMessage('ERROR', $CORE->getLang()->getText('methodNotSupportedByBackend', 'METHOD~getObjects'));
			}
		} catch(BackendConnectionProblem $e) {
			new GlobalMessage('ERROR', $CORE->getLang()->getText('Connection Problem (Backend: [BACKENDID]): [MSG]',
				  																						Array('BACKENDID' => $_GET['backend_id'], 'MSG' => $e->getMessage())));
			exit();
		}

		// Input looks OK, handle the request...
		
		$name1 = ($_GET['action'] === 'getServices' ? $_GET['host_name'] : '');
		$type =  ($_GET['action'] === 'getObjects'  ? $_GET['type']      : 'service');
		
		// Initialize an empty list
		if($_GET['action'] === 'getObjects')
			$aRet = Array(Array('name' => ''));
		else
			$aRet = Array(Array('host_name' => '', 'service_description' => ''));
		
		// Read all objects of the requested type from the backend
		try {
			$objs = $BACKEND->getBackend($_GET['backend_id'])->getObjects($type, $name1, '');
			foreach($objs AS $obj) {
				if($_GET['action'] === 'getObjects')
					$aRet[] = Array('name' => $obj['name1']);
				else
					$aRet[] = Array('host_name'           => $obj['name1'],
					                'service_description' => $obj['name2']);
			}
		} catch(BackendConnectionProblem $e) {}
		
		echo json_encode($aRet);
	break;
	/*
	 * Gets values for the backend options of a defined backend. Needed when
	 * editing existing backends
	 */
	case 'getBackendOptions':
		// These values are submited by WUI requests:
		// ($_GET['backend_id'])
		
		// Do some validations
		if(!isset($_GET['backend_id'])) {
			new GlobalMessage('ERROR', $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~backend_id'));
		} else {
			// Input looks OK, handle the request...
			$aRet = Array();
			
			$backendType = $CORE->getMainCfg()->getValue('backend_'.$_GET['backend_id'],'backendtype');
			
			// Loop all options for this backend type
			$aBackendOpts = $CORE->getMainCfg()->getValidObjectType('backend');
			
			// Merge global backend options with type specific options
			$aOpts = $aBackendOpts['options'][$backendType];
			
			foreach($aBackendOpts AS $sKey => $aOpt) {
				if($sKey !== 'backendid' && $sKey !== 'options') {
					$aOpts[$sKey] = $aOpt;
				}
			} 
			
			foreach($aOpts AS $key => $aOpt) {
				if($CORE->getMainCfg()->getValue('backend_'.$_GET['backend_id'], $key, TRUE) !== FALSE) {
					$aRet[$key] = $CORE->getMainCfg()->getValue('backend_'.$_GET['backend_id'], $key, TRUE);
				} else {
					$aRet[$key] = '';
				}
			}
			
			echo json_encode($aRet);
		}
	break;
	/* Returns the formular contents for the WUI popup windows
	 */
	case 'getFormContents':
		switch($_GET['form']) {
			case 'manageBackends':
				$FRONTEND = new WuiBackendManagement($CORE);
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
		new GlobalMessage('ERROR', $CORE->getLang()->getText('unknownAction', 'ACTION~'.$_GET['action']));
	break;
}
?>

	



