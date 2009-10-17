<?php
class CoreModAutoMap extends CoreModule {
	private $name = null;
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		$aOpts = Array('show' => MATCH_MAP_NAME);
		$aVals = $this->getCustomOptions($aOpts);
		$this->name = $aVals['show'];
		
		$this->aActions = Array(
			'parseAutomap' => REQUIRES_AUTHORISATION,
			'getAutomapProperties' => REQUIRES_AUTHORISATION,
			'getAutomapObjects' => REQUIRES_AUTHORISATION,
			'getObjectStates' => REQUIRES_AUTHORISATION
		);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'parseAutomap':
					$sReturn = $this->parseAutomap();
				break;
				case 'getAutomapProperties':
					$sReturn = $this->getAutomapProperties();
				break;
				case 'getAutomapObjects':
					$sReturn = $this->getAutomapObjects();
				break;
				case 'getObjectStates':
					$sReturn = $this->getObjectStates();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function parseAutomap() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$MAPCFG = new NagVisAutomapCfg($CORE, $this->name);
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
		$opts['automap'] = $this->name;
		// Save the preview mode
		$opts['preview'] = 1;
		
		$MAP = new NagVisAutoMap($CORE, $MAPCFG, $BACKEND, $opts);
		$MAP->renderMap();
		
		return json_encode(true);
	}
	
	private function getAutomapProperties() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
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
		$opts['automap'] = $this->name;
		// Save the preview mode
		$opts['preview'] = 1;
		
		$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $opts);
		return $MAP->parseMapPropertiesJson();
	}
	
	private function getAutomapObjects() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
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
		$opts['automap'] = $this->name;
		// Save the preview mode
		$opts['preview'] = 1;
		
		$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $opts);
		// Fetch the state
		$MAP->MAPOBJ->fetchState();
		// Read position from graphviz and set it on the objects
		$MAP->setMapObjectPositions();

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
			$objConf = $this->getObjConf($arrType[$i], $arrName1[$i], $arrName2[$i]);
			
			// The object id needs to be set here to identify the object in the response
			$objConf['object_id'] = $arrObjId[$i];
			
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
					echo 'Error: '.$this->CORE->LANG->getText('unknownObject', Array('TYPE' => $arrType[$i], 'MAPNAME' => ''));
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

	private function getObjConf($objType, $objName1, $objName2) {
		$objConf = Array();
		
		$MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
				
		// Read the map configuration file
		$MAPCFG->readMapConfig();
		
		$objConf = $MAPCFG->getObjectConfiguration();
		
		// backend_id is filtered in getObjectConfiguration(). Set it manually
		$objConf['backend_id'] = $MAPCFG->getValue('global', 0, 'backend_id');
			
		return $objConf;
	}
}
?>
