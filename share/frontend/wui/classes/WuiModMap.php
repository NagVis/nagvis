<?php
class WuiModMap extends WuiModule {
	private $name = '';
	
	public function __construct(WuiCore $CORE) {
		$this->sName = 'Map';
		$this->CORE = $CORE;

		// Parse the view specific options
		$aOpts = Array('show' => MATCH_MAP_NAME);
		
		$aVals = $this->getCustomOptions($aOpts);
		$this->name = $aVals['show'];
		
		// Register valid actions
		$this->aActions = Array(
			'edit' => REQUIRES_AUTHORISATION,
		);
		
		// Register valid objects
		$this->aObjects = $this->CORE->getAvailableMaps(null, SET_KEYS);
		
		// Set the requested object for later authorisation
		$this->setObject($this->name);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'edit':
					// Show the view dialog to the user
					$sReturn = $this->showEditDialog();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function showEditDialog() {
		// Load map configuration
		$MAPCFG = new WuiMapCfg($this->CORE, $this->name);

		try {
			$MAPCFG->readMapConfig();
		} catch(MapCfgInvalidObject $e) {}
		
		// Build index template
		$INDEX = new WuiViewIndex($this->CORE);
		$INDEX->setSubtitle('WUI');
		$INDEX->setBackgroundColor($MAPCFG->getValue('global', 0, 'background_color'));
		
		// Need to load the custom stylesheet?
		$customStylesheet = $MAPCFG->getValue('global', 0, 'stylesheet');
		if($customStylesheet !== '') {
			$INDEX->setCustomStylesheet($this->CORE->getMainCfg()->getValue('paths','htmlstyles') . $customStylesheet);
		}
		
		$MAP = new WuiMap(WuiCore::getInstance(), $MAPCFG);
		
		// Preflight checks
		if(!$this->CORE->checkPHPMBString(1))
			exit;
		if(!$MAPCFG->checkMapConfigWriteable(1))
			exit;
		// Lock the map for the defined time if its free
		if(!$MAPCFG->checkMapLocked())
			$MAPCFG->writeMapLock();
		
    // Need to parse the header menu by config or url value?
    if($this->CORE->getMainCfg()->getValue('wui','headermenu')) {
      // Parse the header menu
      $HEADER = new WuiHeaderMenu($this->CORE, $this->AUTHORISATION, $this->UHANDLER, $this->CORE->getMainCfg()->getValue('wui','headertemplate'), $MAPCFG);
      $INDEX->setHeaderMenu($HEADER->__toString());
    }
		
		// Map view
		$VIEW = new WuiViewMap(WuiCore::getInstance(), $MAP);
		$INDEX->setContent($VIEW->parse());

		return $INDEX->parse();
	}
}
?>
