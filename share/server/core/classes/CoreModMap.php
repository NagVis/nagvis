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
		
		$aOpts = Array('show' => MATCH_MAP_NAME);
		$aVals = $this->getCustomOptions($aOpts);
		$this->name = $aVals['show'];
		
		// Register valid actions
		$this->aActions = Array(
			'getMapProperties' => REQUIRES_AUTHORISATION,
			'getMapObjects' => REQUIRES_AUTHORISATION,
			'getObjectStates' => REQUIRES_AUTHORISATION,
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
					$sReturn = $this->getMapProperties();
				break;
				case 'getMapObjects':
					$sReturn = $this->getMapObjects();
				break;
				case 'getObjectStates':
					$sReturn = $this->getObjectStates();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function getMapProperties() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$MAPCFG = new NagVisMapCfg($this->CORE, $this->name);
		$MAPCFG->readMapConfig();
		
		$MAP = new NagVisMap($this->CORE, $MAPCFG, $BACKEND);
		return $MAP->parseMapPropertiesJson();
	}
	
	private function getMapObjects() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$MAPCFG = new NagVisMapCfg($this->CORE, $this->name);
		$MAPCFG->readMapConfig();
		
		$MAP = new NagVisMap($this->CORE, $MAPCFG, $BACKEND);
		return $MAP->parseObjectsJson();
	}
	
	private function getObjectStates() {
		$arrReturn = Array();
		
		$aOpts = Array('ty' => MATCH_GET_OBJECT_TYPE,
		               't' => MATCH_OBJECT_TYPES,
		               'n1' => MATCH_STRING_NO_SPACE,
		               'n2' => MATCH_STRING_NO_SPACE_EMPTY,
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
					
					$MAP = new NagVisMap($this->CORE, $MAPCFG, $BACKEND);
					
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
					$params = explode('&', $this->CORE->MAINCFG->getValue('automap','defaultparams'));
					unset($params[0]);
					foreach($params AS &$set) {
						$arrSet = explode('=',$set);
						$opts[$arrSet[0]] = $arrSet[1];
					}
					// Save the automap name to use
					$opts['automap'] = $arrName1[$i];
					// Save the preview mode
					$opts['preview'] = 1;
					
					$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $opts);
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
			echo 'Error: '.$this->CORE->LANG->getText('foundNoObjectOfThatTypeOnMap');
		}
		
		if(count($objConf) > 0) {
			// merge with "global" settings
			foreach($MAPCFG->getValidTypeKeys($objType) AS $key) {
				$objConf[$key] = $MAPCFG->getValue($objType, $objConf['id'], $key);
			}
		} else {
			// object not on map
			echo 'Error: '.$this->CORE->LANG->getText('ObjectNotFoundOnMap');
		}
		
		return $objConf;
	}
}
?>
