<?PHP
/*****************************************************************************
 *
 * ajax_handler.php - Ajax handler for the NagVis frontend
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

// Include global defines
require('../../server/core/defines/global.php');
require('../../server/core/defines/matches.php');

// Include functions
require('../../server/core/functions/autoload.php');
require('../../server/core/functions/debug.php');
require("../../server/core/functions/getuser.php");
require('../../server/core/functions/oldPhpVersionFixes.php');
require('../../server/core/functions/ajaxErrorHandler.php');

// This defines whether the GlobalMessage prints HTML or ajax error messages
define('CONST_AJAX' , TRUE);

// Load the core
$CORE = new GlobalCore();

/*
 * Url: Parse the url to know later what module and
 *      action is called. The requested uri is splitted
 *      into elements for later usage.
 */

$UHANDLER = new CoreUriHandler($CORE);

/*
 * Session: Handle the user session
 */

$SHANDLER = new CoreSessionHandler($CORE);

/*
 * Authentication 1: First try to use an existing session
 *                   If that fails use the configured login method
 */

$AUTH = new CoreAuthHandler($CORE, $SHANDLER, 'CoreAuthModSession');

/*
 * Authorisation 1: Collect and save the permissions when the user is logged in
 *                  and nothing other is saved yet
 */

if($AUTH->isAuthenticated()) {
	// First try to get information from session
	$AUTHORISATION = new CoreAuthorisationHandler($CORE, $AUTH, 'CoreAuthorisationModSession');
	$aPerms = $AUTHORISATION->parsePermissions();

	// When no information in session get permission and write to session
	if($aPerms === false) {
		$AUTHORISATION = new CoreAuthorisationHandler($CORE, $AUTH, $CORE->MAINCFG->getValue('global', 'authorisationmodule'));

		// Save credentials to seession
 		$SHANDLER->set('userPermissions', $AUTHORISATION->parsePermissions());
	}
} else {
	$AUTHORISATION = null;
}

/*
 * Module handling 1: Choose modules
 */

// Load the module handler
$MHANDLER = new CoreModuleHandler($CORE);

// Register valid modules
// Unregistered modules can not be accessed
$MHANDLER->regModule('General');
$MHANDLER->regModule('Overview');
$MHANDLER->regModule('Map');
$MHANDLER->regModule('AutoMap');

// Load the module
$MODULE = $MHANDLER->loadModule($UHANDLER->get('mod'));
if($MODULE == null) {
	new GlobalMessage('ERROR', $CORE->LANG->getText('unknownModule', Array('module' => $UHANDLER->get('mod'))));
}
$MODULE->passAuth($AUTH, $AUTHORISATION);
$MODULE->setAction($UHANDLER->get('act'));

/*
 * Authorisation 2: Check if the user is permitted to use this module/action
 *                  If not redirect to Msg/401 (Unauthorized) page
 */

// Only check modules which should have authorisation checks
// This are all modules excluded some core things
if($MODULE->actionRequiresAuthorisation()) {
	// Only proceed with authenticated users
	if($AUTH->isAuthenticated()) {
		// Check if the user is permited to this action in the module
		if(!isset($AUTHORISATION) || !$AUTHORISATION->isPermitted($UHANDLER->get('mod'), $UHANDLER->get('act'))) {
			new GlobalMessage('ERROR', $CORE->LANG->getText('notPermitted'));
		}
	} else {
		// When not authenticated redirect to logon dialog
		$MODULE = $MHANDLER->loadModule('LogonDialog');
		$UHANDLER->set('act', 'view');
	}
}

/*
 * Module handling 2: Render the modules when permitted
 *                    otherwise handle other pages
 */

// Handle regular action when everything is ok
// When no matching module or action is found show the 404 error
if($MODULE !== false && $MODULE->offersAction($UHANDLER->get('act'))) {
	$MODULE->setAction($UHANDLER->get('act'));

	// Handle the given action in the module
	$sContent = $MODULE->handleAction();
} else {
	// Create instance of msg module
	new GlobalMessage('ERROR', $CORE->LANG->getText('actionNotValid'));
}

echo $sContent;
if (DEBUG&&DEBUGLEVEL&4) debugFinalize();
exit(1);

/*// Initialize backends
$BACKEND = new GlobalBackendMgmt($CORE);

switch($_GET['action']) {
	case 'getObjectStates':
		if(!isset($_GET['n1']) || $_GET['n1'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterObjName1NotSet');
		} elseif(!isset($_GET['ty']) || $_GET['ty'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterTypeNotSet');
		} elseif(!isset($_GET['n2']) || $_GET['n2'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterObjName2NotSet');
		} elseif(!isset($_GET['t']) || $_GET['t'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterObjTypeNotSet');
		} elseif(!isset($_GET['i']) || $_GET['i'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterObjIdNotSet');
		} else {
			$arrReturn = Array();
						
			$sType = $_GET['ty'];
			$arrName1 = $_GET['n1'];
			$arrName2 = $_GET['n2'];
			$arrType = $_GET['t'];
			$arrObjId = $_GET['i'];
			
			if(isset($_GET['m'])) {
				$arrMap = $_GET['m'];
			}
			
			if(isset($_GET['am'])) {
				$arrAutoMap = $_GET['am'];
			}
			
			$numObjects = count($arrType);
			for($i = 0; $i < $numObjects; $i++) {
				// Get the object configuration
				if(isset($arrMap)) {
					$objConf = getMapObjConf($arrType[$i], $arrName1[$i], $arrName2[$i], $arrObjId[$i], 'map', $arrMap[$i]);
				} elseif(isset($arrAutoMap)) {
					$objConf = getAutoMapObjConf($arrType[$i], $arrName1[$i], $arrName2[$i], $arrAutoMap[$i]);
					
					// The object id needs to be set here to identify the object in the response
					$objConf['object_id'] = $arrObjId[$i];
				} else {
					$objConf = getObjConf($arrType[$i], $arrName1[$i], $arrName2[$i]);
					$objConf['object_id'] = $arrObjId[$i];
				}
				
				switch($arrType[$i]) {
					case 'host':
						$OBJ = new NagVisHost($CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i]);
					break;
					case 'service':
						$OBJ = new NagVisService($CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i], $arrName2[$i]);
					break;
					case 'hostgroup':
						$OBJ = new NagVisHostgroup($CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i]);
					break;
					case 'servicegroup':
						$OBJ = new NagVisServicegroup($CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i]);
					break;
					case 'map':
						// Initialize map configuration based on map type
						$MAPCFG = new NagVisMapCfg($CORE, $arrName1[$i]);
						$MAPCFG->readMapConfig();
						
						$MAP = new NagVisMap($CORE, $MAPCFG, $BACKEND);
						
						$OBJ = $MAP->MAPOBJ;
					break;
					case 'automap':
						// Initialize map configuration based on map type
						$MAPCFG = new NagVisAutomapCfg($CORE, $arrName1[$i]);
						$MAPCFG->readMapConfig();
						
						// FIXME: Maybe should be recoded?
						// FIXME: What about the options given in URL when calling the map?
						$opts = Array();
						// Fetch option array from defaultparams string (extract variable
						// names and values)
						$params = explode('&', $CORE->MAINCFG->getValue('automap','defaultparams'));
						unset($params[0]);
						foreach($params AS &$set) {
							$arrSet = explode('=',$set);
							$opts[$arrSet[0]] = $arrSet[1];
						}
						// Save the automap name to use
						$opts['automap'] = $arrName1[$i];
						// Save the preview mode
						$opts['preview'] = 1;
						
						$MAP = new NagVisAutoMap($CORE, $MAPCFG, $BACKEND, $opts);
						$OBJ = $MAP->MAPOBJ;
					break;
					default:
						echo 'Error: '.$CORE->LANG->getText('unknownObject', Array('TYPE' => $arrType[$i], 'MAPNAME' => ''));
					break;
				}
				
				// Apply default configuration to object
				$OBJ->setConfiguration($objConf);
				
				// These things are already done by NagVisMap and NagVisAutoMap classes
				// for the NagVisMapObj objects. Does not need to be done a second time.
				if(get_class($OBJ) != 'NagVisMapObj') {
					$OBJ->fetchMembers();
					$OBJ->fetchState();
				}
				
				$OBJ->fetchIcon();
				
				switch($sType) {
					case 'state':
						$arr = $OBJ->getObjectStateInformations();
					break;
					case 'complete':
						$arr = $OBJ->parseJson();
					break;
				}
				
				$arr['object_id'] = $OBJ->getObjectId();
				$arr['icon'] = $OBJ->get('icon');
				
				$arrReturn[] = $arr;
			}
			
			echo json_encode($arrReturn);
		}
	break;
	default:
		echo 'Error: '.$CORE->LANG->getText('unknownQuery');
	break;
}*/

function getAutoMapObjConf($objType, $objName1, $objName2, $map) {
	global $CORE;
	$objConf = Array();
	
	$MAPCFG = new NagVisAutomapCfg($CORE, $map);
			
	// Read the map configuration file
	$MAPCFG->readMapConfig();
	
	$objConf = $MAPCFG->getObjectConfiguration();
	
	// backend_id is filtered in getObjectConfiguration(). Set it manually
	$objConf['backend_id'] = $MAPCFG->getValue('global', 0, 'backend_id');
		
	return $objConf;
}
?>
