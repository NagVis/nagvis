<?php
/*******************************************************************************
 *
 * CoreModGeneral.php - Core module to handle general ajax requests
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
class CoreModGeneral extends CoreModule {
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array(
			'getCfgFileAges'     => REQUIRES_AUTHORISATION,
			'getStateProperties' => REQUIRES_AUTHORISATION,
			'getHoverTemplate'   => REQUIRES_AUTHORISATION,
			'getContextTemplate' => REQUIRES_AUTHORISATION,
			'getHoverUrl'        => REQUIRES_AUTHORISATION,
			'getObjectStates'    => REQUIRES_AUTHORISATION
		);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'getCfgFileAges':
					$sReturn = $this->getCfgFileAges();
				break;
				case 'getStateProperties':
					$sReturn = $this->getStateProperties();
				break;
				case 'getHoverTemplate':
					$sReturn = $this->getTemplate('hover');
				break;
				case 'getContextTemplate':
					$sReturn = $this->getTemplate('context');
				break;
				case 'getHoverUrl':
					$sReturn = $this->getHoverUrl();
				break;
				case 'getObjectStates':
					$sReturn = $this->getObjectStates();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function getCfgFileAges() {
		$aReturn = Array();
	
		// Parse view specific uri params
		$aKeys = Array('f'  => MATCH_STRING_NO_SPACE, 'm'  => MATCH_MAP_NAME_EMPTY,
		               'am' => MATCH_MAP_NAME_EMPTY);
		$aOpts = $this->getCustomOptions($aKeys);
		
		if(isset($aOpts['f']) && is_array($aOpts['f'])) {
			foreach($aOpts['f'] AS $sFile) {
				if($sFile == 'mainCfg') {
					$aReturn['mainCfg'] = $this->CORE->getMainCfg()->getConfigFileAge();
				}
			}
		}
		
		if(isset($aOpts['m']) && is_array($aOpts['m'])) {
			foreach($aOpts['m'] AS $sMap) {
				$MAPCFG = new NagVisMapCfg($this->CORE, $sMap);
				$aReturn[$sMap] = $MAPCFG->getFileModificationTime();
			}
		}
		
		if(isset($aOpts['am']) && is_array($aOpts['am'])) {
			foreach($aOpts['am'] AS $sAutomap) {
				$MAPCFG = new NagVisAutomapCfg($this->CORE, $sAutomap);
				$aReturn[$sAutomap] = $MAPCFG->getFileModificationTime();
			}
		}
		
		return json_encode($aReturn);
	}
	
	private function getStateProperties() {
		echo json_encode($this->CORE->getMainCfg()->getStateWeight());
	}
	
	private function getTemplate($type) {
		$arrReturn = Array();
		
		// Parse view specific uri params
		$aKeys = Array('name' => MATCH_STRING_NO_SPACE);
		$aOpts = $this->getCustomOptions($aKeys);
		
		foreach($aOpts['name'] AS $sName) {
			if($type == 'hover')
				$OBJ = new NagVisHoverMenu($this->CORE, $sName);
			else
				$OBJ = new NagVisContextMenu($this->CORE, $sName);
			$arrReturn[] = Array('name' => $sName, 'code' => str_replace("\r\n", "", str_replace("\n", "", $OBJ->__toString())));
		}
		
		return json_encode($arrReturn);
	}
	
	private function getHoverUrl() {
		$arrReturn = Array();
		
		// Parse view specific uri params
		$aKeys = Array('url' => MATCH_STRING_URL);
		$aOpts = $this->getCustomOptions($aKeys);
		
		foreach($aOpts['url'] AS $sUrl) {
			$OBJ = new NagVisHoverUrl($this->CORE, $sUrl);
			$arrReturn[] = Array('url' => $sUrl, 'code' => $OBJ->__toString());
		}
		
		return json_encode($arrReturn);
	}
	
	private function getObjectStates() {
		$arrReturn = Array();
		
		$aOpts = Array('ty' => MATCH_GET_OBJECT_TYPE, 't'  => MATCH_OBJECT_TYPES,
		               'n1' => MATCH_STRING,          'n2' => MATCH_STRING_EMPTY,
		               'i'  => MATCH_STRING_NO_SPACE);
		
		$aVals = $this->getCustomOptions($aOpts);
		
		$sType = $aVals['ty'];
		$arrType = $aVals['t'];
		$arrName1 = $aVals['n1'];
		$arrName2 = $aVals['n2'];
		$arrObjId = $aVals['i'];
		
		// Initialize backends
		$BACKEND = new CoreBackendMgmt($this->CORE);
			
		$numObjects = count($arrType);
		$aObjs = Array();
		for($i = 0; $i < $numObjects; $i++) {
			switch($arrType[$i]) {
				case 'map':
					// Skip unpermitted maps
					if($this->CORE->getAuthorization() === null || !$this->CORE->getAuthorization()->isPermitted('Map', 'view', $arrName1[$i]))
						continue 2;
					
					// Initialize map configuration based on map type
					$MAPCFG = new NagVisMapCfg($this->CORE, $arrName1[$i]);
					$MAPCFG->readMapConfig();
					
					$OBJ = new NagVisMapObj($this->CORE, $BACKEND, $MAPCFG, !IS_VIEW);
					$OBJ->fetchMapObjects();
				break;
				case 'automap':
					// Skip unpermitted maps
					if($this->CORE->getAuthorization() === null || !$this->CORE->getAuthorization()->isPermitted('AutoMap', 'view', $arrName1[$i]))
						continue 2;
					
					// Initialize map configuration based on map type
					$MAPCFG = new NagVisAutomapCfg($this->CORE, $arrName1[$i]);
					$MAPCFG->readMapConfig();
					
					$aOpts = Array('backend'     => MATCH_STRING_NO_SPACE_EMPTY,
		                     'root'        => MATCH_STRING_NO_SPACE_EMPTY,
		                     'maxLayers'   => MATCH_INTEGER_EMPTY,
		                     'renderMode'  => MATCH_AUTOMAP_RENDER_MODE,
		                     'width'       => MATCH_INTEGER_EMPTY,
		                     'height'      => MATCH_INTEGER_EMPTY,
		                     'ignoreHosts' => MATCH_STRING_NO_SPACE_EMPTY,
		                     'filterGroup' => MATCH_STRING_NO_SPACE_EMPTY);
		
					$aOpts = $this->getCustomOptions($aOpts);
					
					// Save the automap name to use
					$aOpts['automap'] = $arrName1[$i];
					// Save the preview mode (Enables/Disables printing of errors)
					$aOpts['preview'] = 0;
					
					// FIXME: Maybe this can be a view?! I'm unsure about this...
					$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $aOpts, !IS_VIEW);
					$OBJ = $MAP->MAPOBJ;
				break;
				default:
					new GlobalMessage('ERROR', $CORE->getLang()->getText('Only map and automap states can be fetched using this module/action.'));
				break;
			}
			
			$arr = $MAPCFG->getDefinitions('global');
			$OBJ->setConfiguration($arr[0]);
			
			if($arrType[$i] != 'automap')
				$OBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
			
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
}
?>
