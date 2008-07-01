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

// Start the user session (This is needed by some caching mechanism)
@session_start();

// Set PHP error handling to standard level
error_reporting(E_ALL ^ E_STRICT);

// Include defines
require("./includes/defines/global.php");
require("./includes/defines/matches.php");

// Include functions
require("./includes/functions/debug.php");
require("./includes/functions/oldPhpVersionFixes.php");
require("./includes/functions/getuser.php");

// Include needed global classes
require("./includes/classes/GlobalMainCfg.php");
require("./includes/classes/GlobalMapCfg.php");
require("./includes/classes/GlobalLanguage.php");
require("./includes/classes/GlobalPage.php");
require("./includes/classes/GlobalMap.php");
require("./includes/classes/GlobalBackground.php");
require("./includes/classes/GlobalGraphic.php");
require("./includes/classes/GlobalBackendMgmt.php");

// Include needed nagvis classes
require("./includes/classes/NagVisMapCfg.php");
require("./includes/classes/NagVisMap.php");
require("./includes/classes/NagVisFrontend.php");
require("./includes/classes/NagVisAutoMap.php");

// Include needed nagvis object classes
require("./includes/classes/objects/NagVisObject.php");
require("./includes/classes/objects/NagVisStatefulObject.php");
require("./includes/classes/objects/NagVisStatelessObject.php");
require("./includes/classes/objects/NagiosHost.php");
require("./includes/classes/objects/NagVisHost.php");
require("./includes/classes/objects/NagiosService.php");
require("./includes/classes/objects/NagVisService.php");
require("./includes/classes/objects/NagiosHostgroup.php");
require("./includes/classes/objects/NagVisHostgroup.php");
require("./includes/classes/objects/NagiosServicegroup.php");
require("./includes/classes/objects/NagVisServicegroup.php");
require("./includes/classes/objects/NagVisMapObj.php");
require("./includes/classes/objects/NagVisShape.php");
require("./includes/classes/objects/NagVisTextbox.php");

/**
 * This is a coustom error handling function for submitting PHP errors to the
 * ajax requesting frontend
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function ajaxError($errno, $errstr, $file, $line) {
	// Don't handle E_STRICT errors
	if($errno != 2048) {
		echo "Error: (".$errno.") ".$errstr. " (".$file.":".$line.")";
		die();
	}
}

// Enable coustom error handling
set_error_handler("ajaxError");

// Load the main configuration
$MAINCFG = new GlobalMainCfg(CONST_MAINCFG);

// FIXME: This is a hack; TODO: create a class "AjaxFrontend" which
// handles this user authentication related things and the handling below
$MAINCFG->setRuntimeValue('user', getUser());

// Initialize var
if(!isset($_GET['action'])) {
	$_GET['action'] = '';
}

switch($_GET['action']) {
	case 'getObjectHoverMenu':
		if(!isset($_GET['map']) || $_GET['map'] == '') {
			// FIXME: Error handling
			echo 'Error: Parameter "map" not set';
		} elseif(!isset($_GET['objType']) || $_GET['objType'] == '') {
			// FIXME: Error handling
			echo 'Error: Parameter "objType" not set';
		} elseif(!isset($_GET['objName1']) || $_GET['objName1'] == '') {
			// FIXME: Error handling
			echo 'Error: Parameter "objName1" not set';
		} elseif($_GET['objType'] == 'service' && (!isset($_GET['objName2']) || $_GET['objName2'] == '')) {
			// FIXME: Error handling
			echo 'Error: Parameter "objName2" not set';
		} else  {
			// Initialize map configuration
			$MAPCFG = new NagVisMapCfg($MAINCFG, $_GET['map']);
			// Read the map configuration file
			$MAPCFG->readMapConfig();
			
			// Initialize backend(s)
			$BACKEND = new GlobalBackendMgmt($MAINCFG);
			
			$LANG = new GlobalLanguage($MAINCFG, 'nagvis');
			
			$objConf = Array();
			if($_GET['map'] == '__automap') {
				$objConf['type'] = $_GET['objType'];
				$objConf['host_name'] = $_GET['objName1'];
			} else {
				if(is_array($objs = $MAPCFG->getDefinitions($_GET['objType']))){
					$count = count($objs);
					for($i = 0; $i < $count && count($objConf) >= 0; $i++) {
						if($_GET['objType'] == 'service') {
							if($objs[$i]['host_name'] == $_GET['objName1'] && $objs[$i]['service_description'] == $_GET['objName2']) {
								$objConf = $objs[$i];
								$objConf['id'] = $i;
							}
						} else {
							if($objs[$i][$_GET['objType'].'_name'] == $_GET['objName1']) {
								$objConf = $objs[$i];
								$objConf['id'] = $i;
							}
						}
					}
				} else {
					// FIXME: ERROR handling
					echo "Error: no object of that type on the map";
				}
			}
			
			if(count($objConf) > 0) {
				// merge with "global" settings
				foreach($MAPCFG->validConfig[$_GET['objType']] AS $key => &$values) {
					if((!isset($objConf[$key]) || $objConf[$key] == '') && isset($values['default'])) {
						$objConf[$key] = $values['default'];
					}
				}
				
				switch($_GET['objType']) {
					case 'host':
						$OBJ = new NagVisHost($MAINCFG, $BACKEND, $LANG, $objConf['backend_id'], $objConf['host_name']);
					break;
					case 'service':
						$OBJ = new NagVisService($MAINCFG, $BACKEND, $LANG, $objConf['backend_id'], $objConf['host_name'], $objConf['service_description']);
					break;
					case 'hostgroup':
						$OBJ = new NagVisHostgroup($MAINCFG, $BACKEND, $LANG, $objConf['backend_id'], $objConf['hostgroup_name']);
					break;
					case 'servicegroup':
						$OBJ = new NagVisServicegroup($MAINCFG, $BACKEND, $LANG, $objConf['backend_id'], $objConf['servicegroup_name']);
					break;
					case 'map':
						$SUBMAPCFG = new NagVisMapCfg($MAINCFG, $objConf['map_name']);
						if($SUBMAPCFG->checkMapConfigExists(0)) {
							$SUBMAPCFG->readMapConfig();
						}
						$OBJ = new NagVisMapObj($MAINCFG, $BACKEND, $LANG, $SUBMAPCFG);
						
						if(!$SUBMAPCFG->checkMapConfigExists(0)) {
							$OBJ->summary_state = 'ERROR';
							$OBJ->summary_output = $LANG->getText('mapCfgNotExists', 'MAP~'.$objConf['map_name']);
						}
					break;
					case 'shape':
						$OBJ = new NagVisShape($MAINCFG, $LANG, $objConf['icon']);
					break;
					case 'textbox':
						$OBJ = new NagVisTextbox($MAINCFG, $LANG);
					break;
					default:
						$FRONTEND = new GlobalPage($MAINCFG,Array('languageRoot'=>'global:global'));
						$FRONTEND->messageToUser('ERROR', 'unknownObject', 'TYPE~'.$type.';MAPNAME~'.$this->getName());
					break;
				}
				
				// Apply default configuration to object
				$OBJ->setConfiguration($objConf);
				
				$OBJ->fetchMembers();
				
				$OBJ->fetchState();
				
				$OBJ->fetchIcon();
				
				if(isset($OBJ->hover_url) && $OBJ->hover_url != '') {
					$code = $OBJ->readHoverUrl();
				} else {
					$code = $OBJ->readHoverTemplate();
				}
				
				echo "{ code: ".$code." }";
			} else {
				// FIXME: Errorhandling
				// object not on map
				echo "Error: object not on map";
			}
		}
	break;
	default:
		echo 'Error: Unknown query';
	break;
}
?>
