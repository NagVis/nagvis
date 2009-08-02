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

// Include defines
require("./includes/defines/global.php");
require("./includes/defines/matches.php");

// Include functions
require("./includes/functions/autoload.php");
require("./includes/functions/debug.php");
require("./includes/functions/oldPhpVersionFixes.php");
require("./includes/functions/getuser.php");
require("./includes/functions/ajaxErrorHandler.php");

// This defines whether the GlobalFrontendMessage prints HTML or ajax error messages
define('CONST_AJAX' , TRUE);

// Load the core
$CORE = new GlobalCore();

// FIXME: This is a hack; TODO: create a class "AjaxFrontend" which
// handles this user authentication related things and the handling below/getObjectHoverMenu
$CORE->MAINCFG->setRuntimeValue('user', getUser());


// Initialize backends
$BACKEND = new GlobalBackendMgmt($CORE);

// Initialize var
if(!isset($_GET['action'])) {
	$_GET['action'] = '';
}

switch($_GET['action']) {
	case 'getMapState':
		if(!isset($_GET['objName1']) || $_GET['objName1'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterObjName1NotSet');
		} elseif($_GET['map'] == '__automap') {
			echo 'Error: '.$CORE->LANG->getText('automapNotSupportedHere');
		} else {
			// Initialize map configuration
			$MAPCFG = new NagVisMapCfg($CORE, $_GET['map']);
			$MAPCFG->readMapConfig();
			
			$MAP = new NagVisMap($CORE, $MAPCFG, $BACKEND);
			
			$arrReturn = Array('summaryState' => $MAP->MAPOBJ->getSummaryState(),'summaryOutput' => $MAP->MAPOBJ->getSummaryOutput());
			echo json_encode($arrReturn);
		}
	break;
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
			
			$numObjects = count($arrType);
			for($i = 0; $i < $numObjects; $i++) {
				// Get the object configuration
				if(isset($arrMap)) {
					$objConf = getObjConf($arrType[$i], $arrName1[$i], $arrName2[$i], $arrObjId[$i], $arrMap[$i]);
				} else {
					$objConf = getObjConf($arrType[$i], $arrName1[$i], $arrName2[$i], $arrObjId[$i]);
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
						// Initialize map configuration
						$MAPCFG = new NagVisMapCfg($CORE, $arrName1[$i]);
						$MAPCFG->readMapConfig();
						
						$MAP = new NagVisMap($CORE, $MAPCFG, $BACKEND);
						
						$OBJ = $MAP->MAPOBJ;
					break;
					default:
						echo 'Error: '.$CORE->LANG->getText('unknownObject', 'TYPE~'.$type.';MAPNAME~');
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
	case 'getCfgFileAges':
		$aReturn = Array();
		
		if(isset($_GET['f']) && is_array($_GET['f'])) {
			$aFiles = $_GET['f'];
			
			foreach($aFiles AS $sFile) {
				if($sFile == 'mainCfg') {
					$aReturn['mainCfg'] = $CORE->MAINCFG->getConfigFileAge();
				}
			}
		}
		
		if(isset($_GET['m']) && is_array($_GET['m'])) {
			$aMaps = $_GET['m'];
			
			foreach($aMaps AS $sMap) {
				$MAPCFG = new NagVisMapCfg($CORE, $sMap);
				$aReturn[$sMap] = $MAPCFG->getFileModificationTime();
			}
		}
		
		echo json_encode($aReturn);
	break;
	case 'getMapProperties':
		if(!isset($_GET['objName1']) || $_GET['objName1'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterObjName1NotSet');
		} else {
			// Initialize map configuration
			$MAPCFG = new NagVisMapCfg($CORE, $_GET['objName1']);
			$MAPCFG->readMapConfig();
			
			$MAP = new NagVisMap($CORE, $MAPCFG, $BACKEND);
			echo $MAP->parseMapPropertiesJson();
		}
	break;
	case 'getMapObjects':
		if(!isset($_GET['objName1']) || $_GET['objName1'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterObjName1NotSet');
		} else {
			// Initialize map configuration
			$MAPCFG = new NagVisMapCfg($CORE, $_GET['objName1']);
			$MAPCFG->readMapConfig();
			
			$MAP = new NagVisMap($CORE, $MAPCFG, $BACKEND);
			echo $MAP->parseObjectsJson();
		}
	break;
	case 'getHoverTemplate':
		if(!isset($_GET['name']) || $_GET['name'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterNameNotSet');
		} else {
			$arrReturn = Array();
			$arrNames = $_GET['name'];
			
			$numObjects = count($arrNames);
			for($i = 0; $i < $numObjects; $i++) {
				$OBJ = new NagVisHoverMenu($CORE, $arrNames[$i]);
				$arrReturn[] = Array('name' => $arrNames[$i], 'code' => str_replace("\r\n","",str_replace("\n","", $OBJ->__toString())));
			}
			
			echo json_encode($arrReturn);
		}
	break;
	case 'getContextTemplate':
		if(!isset($_GET['name']) || $_GET['name'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterNameNotSet');
		} else {
			$arrReturn = Array();
			$arrNames = $_GET['name'];
			
			$numObjects = count($arrNames);
			for($i = 0; $i < $numObjects; $i++) {
				$OBJ = new NagVisContextMenu($CORE, $arrNames[$i]);
				$arrReturn[] = Array('name' => $arrNames[$i], 'code' => str_replace("\r\n","",str_replace("\n","", $OBJ->__toString())));
			}
			
			echo json_encode($arrReturn);
		}
	break;
	case 'getHoverUrl':
		if(!isset($_GET['url']) || $_GET['url'] == '') {
			echo 'Error: '.$CORE->LANG->getText('parameterUrlNotSet');
		} else {
			$arrReturn = Array();
			$arrUrls = $_GET['url'];
			
			$numObjects = count($arrUrls);
			for($i = 0; $i < $numObjects; $i++) {
				$OBJ = new NagVisHoverUrl($CORE, $arrUrls[$i]);
				$arrReturn[] = Array('url' => $arrUrls[$i], 'code' => $OBJ->__toString());
			}
			
			echo json_encode($arrReturn);
		}
	break;
	default:
		echo 'Error: '.$CORE->LANG->getText('unknownQuery');
	break;
}

function getObjConf($objType, $objName1, $objName2, $objectId, $map = null) {
	global $CORE;
	$objConf = Array();
	/**
	 * There are two ways to get the configuration for an object. When the map
	 * parameter is set the configuration of the object on the map is read.
	 * When the map parameter is not set or empty the configurations from main
	 * configuration file is used.
	 */
	
	if(isset($map) && $map != '' && $map != '__automap') {
		// Get the object configuration from map configuration (object and
		// defaults)
		
		// Initialize map configuration
		$MAPCFG = new NagVisMapCfg($CORE, $map);
		// Read the map configuration file
		$MAPCFG->readMapConfig();
		
		if(is_array($objs = $MAPCFG->getDefinitions($objType))){
			$count = count($objs);
			for($i = 0; $i < $count && count($objConf) >= 0; $i++) {
				if($objs[$i]['object_id'] == $objectId) {
					$objConf = $objs[$i];
					$objConf['id'] = $i;
					
					// Break the loop on first match
					break;
				}
			}
		} else {
			echo 'Error: '.$CORE->LANG->getText('foundNoObjectOfThatTypeOnMap');
		}
		
		if(count($objConf) > 0) {
			// merge with "global" settings
			foreach($MAPCFG->getValidTypeKeys($objType) AS $key) {
				$objConf[$key] = $MAPCFG->getValue($objType, $objConf['id'], $key);
			}
		} else {
			// object not on map
			echo 'Error: '.$CORE->LANG->getText('ObjectNotFoundOnMap');
		}
	} else {
		// Get the object configuration from main configuration defaults by
		// creating a temporary map object
		$objConf['type'] = $objType;
		
		if($objType == 'service') {
			$objConf['host_name'] = $objName1;
			$objConf['service_description'] = $objName2;
		} else {
			$objConf[$objType.'_name'] = $objName1;
		}
		
		// Get settings from main configuration file by generating a temporary 
		// map or from the automap configuration
		if($map == '__automap') {
			$TMPMAPCFG = new NagVisMapCfg($CORE, $map);
			
			// Read the map configuration file
			$TMPMAPCFG->readMapConfig();
		} else {
			$TMPMAPCFG = new NagVisMapCfg($CORE);
		}
		
		// merge with "global" settings
		foreach($TMPMAPCFG->getValidTypeKeys('global') AS $key) {
			if($key != 'type') {
				$objConf[$key] = $TMPMAPCFG->getValue('global', 0, $key);
			}
		}
		
		unset($TMPMAPCFG);
	}
	
	return $objConf;
}
?>
