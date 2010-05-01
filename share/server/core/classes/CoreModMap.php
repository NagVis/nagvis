<?php
/*******************************************************************************
 *
 * CoreModMap.php - Core Map module to handle ajax requests
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModMap extends CoreModule {
	private $name = null;
	private $MAPCFG = null;
	
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
			'addModify' => REQUIRES_AUTHORISATION,
			'modifyObject' => REQUIRES_AUTHORISATION,
			'createObject' => REQUIRES_AUTHORISATION,
			'deleteObject' => REQUIRES_AUTHORISATION,
			'manageTmpl' => REQUIRES_AUTHORISATION,
			'getTmplOpts' => REQUIRES_AUTHORISATION,
			'doTmplAdd' => REQUIRES_AUTHORISATION,
			'doTmplModify' => REQUIRES_AUTHORISATION,
			'doTmplDelete' => REQUIRES_AUTHORISATION,
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
							                  $this->CORE->getMainCfg()->getValue('paths','htmlbase').'/frontend/wui/index.php?mod=Map&act=edit&show='.$aReturn['map_name']);
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
						if($this->doModifyObject($aReturn)) {
							if(isset($aReturn['refresh']) && $aReturn['refresh'] == 1) {
								new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The object has been modified.'),
							                  null,
							                  null,
							                  1);
							  $sReturn = '';
							} else {
								$sReturn = json_encode(Array('status' => 'OK', 'message' => ''));
							}
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
						if($this->doDeleteObject($aReturn)) {
							$sReturn = json_encode(Array('status' => 'OK', 'message' => ''));
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The object could not be deleted.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'addModify':
					$aOpts = Array('show' => MATCH_MAP_NAME,
					               'do' => MATCH_WUI_ADDMODIFY_DO,
					               'type' => MATCH_OBJECTTYPE,
					               'id' => MATCH_INTEGER_EMPTY,
					               'viewType' => MATCH_VIEW_TYPE_SERVICE_EMPTY,
					               'coords' => MATCH_STRING_NO_SPACE_EMPTY,
					               'clone' => MATCH_INTEGER_EMPTY);
					$aVals = $this->getCustomOptions($aOpts);
					
					// Initialize unset optional attributes
					if(!isset($aVals['coords'])) {
						$aVals['coords'] = '';
					}
					
					if(!isset($aVals['id'])) {
						$aVals['id'] = '';
					}
					
					if(!isset($aVals['viewType'])) {
						$aVals['viewType'] = '';
					}
					
					if(!isset($aVals['clone'])) {
						$aVals['clone'] = '';
					}
					
					$VIEW = new WuiViewMapAddModify($this->AUTHENTICATION, $this->AUTHORISATION);
					$VIEW->setOpts($aVals);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'manageTmpl':
					$aOpts = Array('map' => MATCH_MAP_NAME);
					$aVals = $this->getCustomOptions($aOpts);
					
					$VIEW = new WuiViewMapManageTmpl($this->AUTHENTICATION, $this->AUTHORISATION);
					$VIEW->setOpts($aVals);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'getTmplOpts':
					$aOpts = Array('map' => MATCH_MAP_NAME,
					               'name' => MATCH_STRING_NO_SPACE);
					$aVals = $this->getCustomOptions($aOpts);
					
					// Read map config but don't resolve templates and don't use the cache
					$MAPCFG = new WuiMapCfg($this->CORE, $aVals['map']);
					$MAPCFG->readMapConfig(0, false, false);
					
					$aTmp = $MAPCFG->getDefinitions('template');
					$aTmp = $aTmp[$MAPCFG->getTemplateIdByName($aVals['name'])];
					unset($aTmp['type']);
					unset($aTmp['object_id']);
					
					$sReturn = json_encode(Array('opts' => $aTmp));
				break;
				case 'doTmplAdd':
					$aReturn = $this->handleResponseDoTmplAdd();
					
					if($aReturn !== false) {
						if($this->doTmplAdd($aReturn)) {
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
				case 'doTmplModify':
					$aReturn = $this->handleResponseDoTmplModify();
					
					if($aReturn !== false) {
						if($this->doTmplModify($aReturn)) {
							// FIXME: Would be nice to have the object adding without reload of the page
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The object has been modified.'),
							                  null,
							                  null,
							                  1);
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The object could not be modified.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'doTmplDelete':
					$aReturn = $this->handleResponseDoTmplDelete();
					
					if($aReturn !== false) {
						// Try to delete the template
						if($this->doTmplDelete($aReturn)) {
							// FIXME: Would be nice to have the object adding without reload of the page
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The template has been deleted.'),
							                  null,
							                  null,
							                  1);
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The template could not be deleted.'));
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
	
	private function doTmplModify($a) {
		$MAPCFG = new WuiMapCfg($this->CORE, $a['map']);
		$MAPCFG->readMapConfig(0, false, false);
		
		$id = $MAPCFG->getTemplateIdByName($a['opts']['name']);
		
		// set options in the array
		foreach($a['opts'] AS $key => $val) {
			$MAPCFG->setValue('template', $id, $key, $val);
		}
		
		$MAPCFG->writeElement('template', $id);
		
		// delete map lock
		if(!$MAPCFG->deleteMapLock()) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('mapLockNotDeleted'));
		}
		
		return true;
	}
	
	private function handleResponseDoTmplModify() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_POST);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('name')) {
			$bValid = false;
		}
		
		//FIXME: All fields: Regex check
		
		// Check if the map exists
		if($bValid && count($this->CORE->getAvailableMaps('/^'.$FHANDLER->get('map').'$/')) <= 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map does not exist.'));
			
			$bValid = false;
		}
		
		// Check if the template already exists
		// Read map config but don't resolve templates and don't use the cache
		$MAPCFG = new WuiMapCfg($this->CORE, $FHANDLER->get('map'));
		$MAPCFG->readMapConfig(0, false, false);
		if($bValid && count($MAPCFG->getTemplateNames('/^'.$FHANDLER->get('name').'$/')) <= 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('A template with this name does not exist.'));
			
			$bValid = false;
		}
		$MAPCFG = null;
		
		// FIXME: Recode to FHANDLER
		$aOpts = $_POST;
		
		// Remove the parameters which are not options of the object
		unset($aOpts['submit']);
		unset($aOpts['map']);
		
		// Transform the array to key => value form
		$opts = Array('name' => $FHANDLER->get('name'));
		foreach($aOpts AS $key => $a) {
			if(substr($key, 0, 3) === 'opt' && isset($a['name']) && isset($a['value'])) {
				$opts[$a['name']] = $a['value'];
			}
		}
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('map' => $FHANDLER->get('map'),
			             'opts' => $opts);
		} else {
			return false;
		}
	}
	private function doTmplDelete($a) {
		// Read map config but don't resolve templates and don't use the cache
		$MAPCFG = new WuiMapCfg($this->CORE, $a['map']);
		$MAPCFG->readMapConfig(0, false, false);
		
		$id = $MAPCFG->getTemplateIdByName($a['name']);
		
		// first delete element from array
		$MAPCFG->deleteElement('template', $id);
		// then write new array to file
		$MAPCFG->writeElement('template', $id);
		
		// delete map lock
		if(!$MAPCFG->deleteMapLock()) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('mapLockNotDeleted'));
		}
			
		return true;
	}
	
	private function handleResponseDoTmplDelete() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_POST);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('name')) {
			$bValid = false;
		}
		
		//FIXME: All fields: Regex check
		
		// Check if the map exists
		if($bValid && count($this->CORE->getAvailableMaps('/^'.$FHANDLER->get('map').'$/')) <= 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map does not exist.'));
			
			$bValid = false;
		}
		
		// Check if the template already exists
		// Read map config but don't resolve templates and don't use the cache
		$MAPCFG = new WuiMapCfg($this->CORE, $FHANDLER->get('map'));
		$MAPCFG->readMapConfig(0, false, false);
		if($bValid && count($MAPCFG->getTemplateNames('/^'.$FHANDLER->get('name').'$/')) <= 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The template does not exist.'));
			
			$bValid = false;
		}
		$MAPCFG = null;
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('map' => $FHANDLER->get('map'),
			             'name' => $FHANDLER->get('name'));
		} else {
			return false;
		}
	}
	
	private function doTmplAdd($a) {
		$MAPCFG = new WuiMapCfg($this->CORE, $a['map']);
		$MAPCFG->readMapConfig(0, false, false);
		
		// append a new object definition to the map configuration
		$elementId = $MAPCFG->addElement('template', $a['opts']);
		$MAPCFG->writeElement('template', $elementId);
		
		// delete map lock
		if(!$MAPCFG->deleteMapLock()) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('mapLockNotDeleted'));
		}
		
		return true;
	}
	
	private function handleResponseDoTmplAdd() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_POST);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('name')) {
			$bValid = false;
		}
		
		//FIXME: All fields: Regex check
		
		// Check if the map exists
		if($bValid && count($this->CORE->getAvailableMaps('/^'.$FHANDLER->get('map').'$/')) <= 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The map does not exist.'));
			
			$bValid = false;
		}
		
		// Check if the template already exists
		// Read map config but don't resolve templates and don't use the cache
		$MAPCFG = new WuiMapCfg($this->CORE, $FHANDLER->get('map'));
		$MAPCFG->readMapConfig(0, false, false);
		if($bValid && count($MAPCFG->getTemplateNames('/^'.$FHANDLER->get('name').'$/')) > 0) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('A template with this name already exists.'));
			
			$bValid = false;
		}
		$MAPCFG = null;
		
		// FIXME: Recode to FHANDLER
		$aOpts = $_POST;
		
		// Remove the parameters which are not options of the object
		unset($aOpts['submit']);
		unset($aOpts['map']);
		unset($aOpts['name']);
		
		// Transform the array to key => value form
		$opts = Array('name' => $FHANDLER->get('name'));
		foreach($aOpts AS $key => $a) {
			if(substr($key, 0, 3) === 'opt' && isset($a['name']) && isset($a['value'])) {
				$opts[$a['name']] = $a['value'];
			}
		}
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('map' => $FHANDLER->get('map'),
			             'opts' => $opts);
		} else {
			return false;
		}
	}
	
	private function doDeleteObject($a) {
		// initialize map and read map config
		$MAPCFG = new WuiMapCfg($this->CORE, $a['map']);
		// Ignore map configurations with errors in it.
		// the problems may get resolved by deleting the object
		try {
			$MAPCFG->readMapConfig();
		} catch(MapCfgInvalid $e) {}
		
		// first delete element from array
		$MAPCFG->deleteElement($a['type'], $a['id']);
		// then write new array to file
		$MAPCFG->writeElement($a['type'], $a['id']);
		
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
		try {
			$MAPCFG->readMapConfig();
		} catch(MapCfgInvalid $e) {}
		
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
		unset($aOpts['ref']);
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
			             'refresh' => $FHANDLER->get('ref'),
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
		try {
			$MAPCFG->readMapConfig();
		} catch(MapCfgInvalidObject $e) {}
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
		               'n1' => MATCH_STRING,
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
		
		// Initialize map configuration (Needed in getMapObjConf)
		$this->MAPCFG = new NagVisMapCfg($this->CORE, $this->name);
		$this->MAPCFG->readMapConfig();
		
		$numObjects = count($arrType);
		$aObjs = Array();
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
			
					$OBJ = new NagVisMapObj($this->CORE, $BACKEND, $MAPCFG, !IS_VIEW);
					$OBJ->fetchMapObjects();
				break;
				case 'automap':
					// Initialize map configuration based on map type
					$MAPCFG = new NagVisAutomapCfg($this->CORE, $arrName1[$i]);
					$MAPCFG->readMapConfig();
					
					$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, Array('automap' => $arrName1[$i], 'preview' => 1), !IS_VIEW);
					$OBJ = $MAP->MAPOBJ;
				break;
				default:
					echo 'Error: '.$CORE->getLang()->getText('unknownObject', Array('TYPE' => $arrType[$i], 'MAPNAME' => ''));
				break;
			}
			
			// Apply default configuration to object
			$OBJ->setConfiguration($objConf);
			
			if($arrType[$i] != 'automap') {
				$OBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
			}
			
			$aObjs[] = $OBJ;
		}
		
		// Now after all objects are queued execute them and then apply the states
		$BACKEND->execute();
		
		foreach($aObjs AS $OBJ) {
			$OBJ->applyState();
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
		
		if(is_array($objs = $this->MAPCFG->getDefinitions($objType))){
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
			$typeDefs = $this->MAPCFG->getTypeDefaults($objType);
			// merge with "global" settings
			foreach($typeDefs AS $key => $default) {
				if(!isset($objConf[$key])) {
					$objConf[$key] = $default;
				}
			}
		} else {
			// object not on map
			echo 'Error: '.$this->CORE->getLang()->getText('ObjectNotFoundOnMap');
		}
		
		return $objConf;
	}
}
?>
