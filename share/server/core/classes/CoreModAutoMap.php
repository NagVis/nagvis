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
			'getAutomapObjects' => REQUIRES_AUTHORISATION
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
}
?>
