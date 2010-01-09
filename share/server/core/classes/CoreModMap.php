<?php
/*******************************************************************************
 *
 * CoreModMap.php - Core Map module to handle ajax requests
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModMap extends CoreModule {
	private $name = null;
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		// Register valid actions
		$this->aActions = Array(
			'getMapProperties' => REQUIRES_AUTHORISATION,
			'getMapObjects' => REQUIRES_AUTHORISATION,
			'getObjectStates' => REQUIRES_AUTHORISATION,
			// WUI specific actions
			'doAdd' => REQUIRES_AUTHORISATION,
			'doRename' => REQUIRES_AUTHORISATION,
			'doDelete' => REQUIRES_AUTHORISATION,
			'modifyObject' => REQUIRES_AUTHORISATION,
			'createObject' => REQUIRES_AUTHORISATION,
			'deleteObject' => REQUIRES_AUTHORISATION,
		);
		
		// Register valid objects
		$this->aObjects = $this->CORE->getAvailableMaps();
		
		// Set the requested object for later authorisation
		$this->setObject($this->name);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'getMapProperties':
					$aOpts = Array('show' => MATCH_MAP_NAME);
					$aVals = $this->getCustomOptions($aOpts);
					$this->name = $aVals['show'];
					
					$sReturn = $this->getMapProperties();
				break;
				case 'getMapObjects':
					$aOpts = Array('show' => MATCH_MAP_NAME);
					$aVals = $this->getCustomOptions($aOpts);
					$this->name = $aVals['show'];
					
					$sReturn = $this->getMapObjects();
				break;
				case 'getObjectStates':
					$aOpts = Array('show' => MATCH_MAP_NAME);
					$aVals = $this->getCustomOptions($aOpts);
					$this->name = $aVals['show'];
					
					$sReturn = $this->getObjectStates();
				break;
				case 'doAdd':
					$aReturn = $this->handleResponseAdd();
					
					if($aReturn !== false) {
						// Try to create the map
						if($this->doAdd($aReturn)) {
							new GlobalMessage('NOTE', 
							                  $this->CORE->getLang()->getText('The map has been created.'),
							                  null,
							                  null,
							                  1,
							                  $this->CORE->getMainCfg()->getValue('paths','htmlbase').'/frontend/wui/index.php?mod=Map&act=edit&show='.$aReturn['map_new_name']);
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map could not be created.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'doRename':
					$aReturn = $this->handleResponseRename();
					
					if($aReturn !== false) {
						// Try to create the map
						if($this->doRename($aReturn)) {
							// if renamed map is open, redirect to new name
							if($aReturn['map'] == 'undefined' || $aReturn['map'] == '' || $aReturn['map'] == $aReturn['map_name']) {
								$map = $aReturn['map_new_name'];
							} else {
								$map = $aReturn['map'];
							}
							
							new GlobalMessage('NOTE', 
							                  $this->CORE->getLang()->getText('The map has been renamed.'),
							                  null,
							                  null,
							                  1,
							                  $this->CORE->getMainCfg()->getValue('paths','htmlbase').'/frontend/wui/index.php?mod=Map&act=edit&show='.$map);
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map could not be renamed.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'doDelete':
					$aReturn = $this->handleResponseDelete();
					
					if($aReturn !== false) {
						// Try to create the map
						if($this->doDelete($aReturn)) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The map has been deleted.'));
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map could not be deleted.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'createObject':
					$aReturn = $this->handleResponseCreateObject();
					
					if($aReturn !== false) {
						// Try to create the map
						if($this->doCreateObject($aReturn)) {
							// FIXME: Would be nice to have the object adding without reload of the page
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The object has been added.'),
							                  null,
							                  null,
							                  1);
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The object could not be added.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'modifyObject':
					$aReturn = $this->handleResponseModifyObject();
					
					if($aReturn !== false) {
						// Try to create the map
						if($this->doModifyObject($aReturn)) {
							// FIXME: Would be nice to have the object adding without reload of the page
							//new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The object has been modified.'),
							//                  null,
							//                  null,
							//                  1);
							// FIXME: Recode to GlobalMessage. But the particular callers like
							//        suppress the success messages
							$sReturn = json_encode(Array('status' => 'OK', 'message' => ''));
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The object could not be modified.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'deleteObject':
					$aReturn = $this->handleResponseDeleteObject();
					
					if($aReturn !== false) {
						// Try to create the map
						if($this->doDeleteObject($aReturn)) {
							// FIXME: Recode to GlobalMessage. But the particular callers like
							//        suppress the success messages
							$sReturn = json_encode(Array('status' => 'OK', 'message' => ''));
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The object could not be modified.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function doDeleteObject($a) {
		// initialize map and read map config
		$MAPCFG = new WuiMapCfg($this->CORE, $a['map']);
		$MAPCFG->readMapConfig();
		
		// first delete element from array
		$MAPCFG->deleteElement($a['type'],$a['id']);
		// then write new array to file
		$MAPCFG->writeElement($a['type'],$a['id']);
		
		// delete map lock
		if(!$MAPCFG->deleteMapLock()) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('mapLockNotDeleted'));
		}
			
		return true;
	}
	
	private function handleResponseDeleteObject() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_GET);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('type')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('id')) {
			$bValid = false;
		}
		
		//FIXME: All fields: Regex check
		
		// Check if the map exists
		if($bValid && count($this->CORE->getAvailableMaps('/^'.$FHANDLER->get('map').'$/')) <= 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map does not exist.'));
			
			$bValid = false;
		}
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('map' => $FHANDLER->get('map'),
			             'type' => $FHANDLER->get('type'),
			             'id' => $FHANDLER->get('id'));
		} else {
			return false;
		}
	}
	
	private function doModifyObject($a) {
		$MAPCFG = new WuiMapCfg($this->CORE, $a['map']);
		$MAPCFG->readMapConfig();
		
		// set options in the array
		foreach($a['opts'] AS $key => $val) {
			$MAPCFG->setValue($a['type'], $a['id'], $key, $val);
		}
		
		// write element to file
		$MAPCFG->writeElement($a['type'], $a['id']);
		
		// delete map lock
		if(!$MAPCFG->deleteMapLock()) {
			new GlobalMessage('ERROR', $CORE->getLang()->getText('mapLockNotDeleted'));
		}
			
		return true;
	}
	
	private function handleResponseModifyObject() {
		$bValid = true;
		// Validate the response
		
		// Need to listen to POST and GET
		$aResponse = array_merge($_GET, $_POST);
		// FIXME: Maybe change all to POST
		$FHANDLER = new CoreRequestHandler($aResponse);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('type')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('id')) {
			$bValid = false;
		}
		
		//FIXME: All fields: Regex check
		
		// Check if the map exists
		if($bValid && count($this->CORE->getAvailableMaps('/^'.$FHANDLER->get('map').'$/')) <= 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map does not exist.'));
			
			$bValid = false;
		}
		
		// FIXME: Recode to FHANDLER
		$aOpts = $aResponse;
		// Remove the parameters which are not options of the object
		unset($aOpts['act']);
		unset($aOpts['mod']);
		unset($aOpts['map']);
		unset($aOpts['type']);
		unset($aOpts['id']);
		unset($aOpts['timestamp']);
		
		// Also remove all "helper fields" which begin with a _
		foreach($aOpts AS $key => $val) {
			if(strpos($key, '_') === 0) {
				unset($aOpts[$key]);
			}
		}
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('map' => $FHANDLER->get('map'),
			             'type' => $FHANDLER->get('type'),
			             'id' => $FHANDLER->get('id'),
			             'opts' => $aOpts);
		} else {
			return false;
		}
	}
	
	private function doCreateObject($a) {
		$MAPCFG = new WuiMapCfg($this->CORE, $a['map']);
		$MAPCFG->readMapConfig();
		
		// append a new object definition to the map configuration
		$elementId = $MAPCFG->addElement($a['type'], $a['opts']);
		$MAPCFG->writeElement($a['type'], $elementId);
		
		// delete map lock
		if(!$MAPCFG->deleteMapLock()) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('mapLockNotDeleted'));
		}
		
		return true;
	}
	
	private function handleResponseCreateObject() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_POST);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('type')) {
			$bValid = false;
		}
		
		//FIXME: All fields: Regex check
		
		// Check if the map exists
		if($bValid && count($this->CORE->getAvailableMaps('/^'.$FHANDLER->get('map').'$/')) <= 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map does not exist.'));
			
			$bValid = false;
		}
		
		// FIXME: Recode to FHANDLER
		$aOpts = $_POST;
		// Remove the parameters which are not options of the object
		unset($aOpts['map']);
		unset($aOpts['type']);
		
		// Also remove all "helper fields" which begin with a _
		foreach($aOpts AS $key => $val) {
			if(strpos($key, '_') === 0) {
				unset($aOpts[$key]);
			}
		}
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('map' => $FHANDLER->get('map'),
			             'type' => $FHANDLER->get('type'),
			             'opts' => $aOpts);
		} else {
			return false;
		}
	}
	
	private function doDelete($a) {
		$MAPCFG = new WuiMapCfg($this->CORE, $a['map_name']);
		$MAPCFG->readMapConfig();
		$MAPCFG->deleteMapConfig();
		
		return true;
	}
	
	private function handleResponseDelete() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_POST);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map_name')) {
			$bValid = false;
		}
		
		//FIXME: All fields: Regex check
		
		// Check if the map exists
		if($bValid && count($this->CORE->getAvailableMaps('/^'.$FHANDLER->get('map_name').'$/')) <= 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map does not exist.'));
			
			$bValid = false;
		}
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('map_name' => $FHANDLER->get('map_name'));
		} else {
			return false;
		}
	}
	
	private function doRename($a) {
		$files = Array();
		
		// loop all map configs to replace mapname in all map configs
		foreach($this->CORE->getAvailableMaps() as $mapName) {
			$MAPCFG1 = new WuiMapCfg($this->CORE, $mapName);
			$MAPCFG1->readMapConfig();
			
			$i = 0;
			// loop definitions of type map
			foreach($MAPCFG1->getDefinitions('map') AS $key => $obj) {
				// check if old map name is linked...
				if($obj['map_name'] == $_POST['map_name']) {
					$MAPCFG1->setValue('map', $i, 'map_name', $a['map_new_name']);
					$MAPCFG1->writeElement('map',$i);
				}
				$i++;
			}
		}
		
		// rename config file
		rename($this->CORE->getMainCfg()->getValue('paths', 'mapcfg').$a['map_name'].'.cfg',
		       $this->CORE->getMainCfg()->getValue('paths', 'mapcfg').$a['map_new_name'].'.cfg');
		
		return true;
	}
	
	private function handleResponseRename() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_POST);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map_name')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map_new_name')) {
			$bValid = false;
		}
		
		//FIXME: All fields: Regex check
		
		// Check if the new map already exists
		if($bValid && count($this->CORE->getAvailableMaps('/^'.$FHANDLER->get('map_new_name').'$/')) > 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The new mapname does already exist.'));
			
			$bValid = false;
		}
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array(
		               'map_new_name' => $FHANDLER->get('map_new_name'),
		               'map_name' => $FHANDLER->get('map_name'),
		               'map' => $FHANDLER->get('map'));
		} else {
			return false;
		}
	}
	
	private function doAdd($a) {
		$MAPCFG = new WuiMapCfg($this->CORE, $a['map_name']);
		if(!$MAPCFG->createMapConfig()) {
			return false;
		}
		
		$MAPCFG->addElement('global', $a);
		return $MAPCFG->writeElement('global','0');
	}
	
	private function handleResponseAdd() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_POST);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map_name')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('allowed_users')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('allowed_for_config')) {
			$bValid = false;
		}
		
		//FIXME: All fields: Regex check
		
		// Check if the map already exists
		if($bValid && count($this->CORE->getAvailableMaps('/^'.$FHANDLER->get('map_name').'$/')) > 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The mapname does already exist.'));
			
			$bValid = false;
		}
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array(
		               'map_name' => $FHANDLER->get('map_name'),
		               'allowed_user' => $FHANDLER->get('allowed_users'),
		               'allowed_for_config' => $FHANDLER->get('allowed_for_config'),
		               'iconset' => $FHANDLER->get('map_iconset'),
		               'map_image' => $FHANDLER->get('map_image'));
		} else {
			return false;
		}
	}
	
	private function getMapProperties() {
		$MAPCFG = new NagVisMapCfg($this->CORE, $this->name);
		$MAPCFG->readMapConfig();
		
		$arr = Array();
		$arr['map_name'] = $MAPCFG->getName();
		$arr['alias'] = $MAPCFG->getValue('global', 0, 'alias');
		$arr['background_image'] = $MAPCFG->BACKGROUND->getFile();
		$arr['background_color'] = $MAPCFG->getValue('global', 0, 'background_color');
		$arr['favicon_image'] = $this->CORE->getMainCfg()->getValue('paths', 'htmlimages').'internal/favicon.png';
		$arr['page_title'] = $MAPCFG->getValue('global', 0, 'alias').' ([SUMMARY_STATE]) :: '.$this->CORE->getMainCfg()->getValue('internal', 'title');
		$arr['event_background'] = $MAPCFG->getValue('global', 0, 'event_background');
		$arr['event_highlight'] = $MAPCFG->getValue('global', 0, 'event_highlight');
		$arr['event_highlight_interval'] = $MAPCFG->getValue('global', 0, 'event_highlight_interval');
		$arr['event_highlight_duration'] = $MAPCFG->getValue('global', 0, 'event_highlight_duration');
		$arr['event_log'] = $MAPCFG->getValue('global', 0, 'event_log');
		$arr['event_log_level'] = $MAPCFG->getValue('global', 0, 'event_log_level');
		$arr['event_log_height'] = $MAPCFG->getValue('global', 0, 'event_log_height');
		$arr['event_log_hidden'] = $MAPCFG->getValue('global', 0, 'event_log_hidden');
		$arr['event_scroll'] = $MAPCFG->getValue('global', 0, 'event_scroll');
		$arr['event_sound'] = $MAPCFG->getValue('global', 0, 'event_sound');
		
		return json_encode($arr);
	}
	
	private function getMapObjects() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$MAPCFG = new NagVisMapCfg($this->CORE, $this->name);
		$MAPCFG->readMapConfig();
		
		$MAP = new NagVisMap($this->CORE, $MAPCFG, $BACKEND, GET_STATE, IS_VIEW);
		return $MAP->parseObjectsJson();
	}
	
	private function getObjectStates() {
		$arrReturn = Array();
		
		$aOpts = Array('ty' => MATCH_GET_OBJECT_TYPE,
		               't' => MATCH_OBJECT_TYPES,
		               'n1' => MATCH_STRING_NO_SPACE,
		               'n2' => MATCH_STRING_EMPTY,
		               'i' => MATCH_STRING_NO_SPACE);
		
		$aVals = $this->getCustomOptions($aOpts);
		
		$sType = $aVals['ty'];
		$arrType = $aVals['t'];
		$arrName1 = $aVals['n1'];
		$arrName2 = $aVals['n2'];
		$arrObjId = $aVals['i'];
		
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$numObjects = count($arrType);
		for($i = 0; $i < $numObjects; $i++) {
			// Get the object configuration
			$objConf = $this->getMapObjConf($arrType[$i], $arrName1[$i], $arrName2[$i], $arrObjId[$i]);
			
			switch($arrType[$i]) {
				case 'host':
					$OBJ = new NagVisHost($this->CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i]);
				break;
				case 'service':
					$OBJ = new NagVisService($this->CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i], $arrName2[$i]);
				break;
				case 'hostgroup':
					$OBJ = new NagVisHostgroup($this->CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i]);
				break;
				case 'servicegroup':
					$OBJ = new NagVisServicegroup($this->CORE, $BACKEND, $objConf['backend_id'], $arrName1[$i]);
				break;
				case 'map':
					// Initialize map configuration based on map type
					$MAPCFG = new NagVisMapCfg($this->CORE, $arrName1[$i]);
					$MAPCFG->readMapConfig();
					
					$MAP = new NagVisMap($this->CORE, $MAPCFG, $BACKEND, GET_STATE, !IS_VIEW);
					
					$OBJ = $MAP->MAPOBJ;
				break;
				case 'automap':
					// Initialize map configuration based on map type
					$MAPCFG = new NagVisAutomapCfg($this->CORE, $arrName1[$i]);
					$MAPCFG->readMapConfig();
					
					// FIXME: Maybe should be recoded?
					// FIXME: What about the options given in URL when calling the map?
					$opts = Array();
					// Fetch option array from defaultparams string (extract variable
					// names and values)
					$params = explode('&', $this->CORE->getMainCfg()->getValue('automap','defaultparams'));
					unset($params[0]);
					foreach($params AS &$set) {
						$arrSet = explode('=',$set);
						$opts[$arrSet[0]] = $arrSet[1];
					}
					// Save the automap name to use
					$opts['automap'] = $arrName1[$i];
					// Save the preview mode
					$opts['preview'] = 1;
					
					$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $opts, !IS_VIEW);
					$OBJ = $MAP->MAPOBJ;
				break;
				default:
					echo 'Error: '.$CORE->getLang()->getText('unknownObject', Array('TYPE' => $arrType[$i], 'MAPNAME' => ''));
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
		
		return json_encode($arrReturn);
	}
	
	private function getMapObjConf($objType, $objName1, $objName2, $objectId) {
		$objConf = Array();
		
		// Get the object configuration from map configuration (object and
		// defaults)
		
		// Initialize map configuration
		$MAPCFG = new NagVisMapCfg($this->CORE, $this->name);
		
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
			echo 'Error: '.$this->CORE->getLang()->getText('foundNoObjectOfThatTypeOnMap');
		}
		
		if(count($objConf) > 0) {
			// merge with "global" settings
			foreach($MAPCFG->getValidTypeKeys($objType) AS $key) {
				$objConf[$key] = $MAPCFG->getValue($objType, $objConf['id'], $key);
			}
		} else {
			// object not on map
			echo 'Error: '.$this->CORE->getLang()->getText('ObjectNotFoundOnMap');
		}
		
		return $objConf;
	}
}
?>
