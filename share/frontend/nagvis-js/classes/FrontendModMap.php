<?php
class FrontendModMap extends FrontendModule {
	private $name = '';
	private $search = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;

		// Parse the view specific options
		$aOpts = Array('show' => MATCH_MAP_NAME,
		               'search' => MATCH_STRING_NO_SPACE_EMPTY);
		
		$aVals = $this->getCustomOptions($aOpts);
		$this->name = $aVals['show'];
		$this->search = $aVals['search'];
		
		// Register valid actions
		$this->aActions = Array(
			'view' => REQUIRES_AUTHORISATION
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
				case 'view':
					// Show the view dialog to the user
					$sReturn = $this->showViewDialog();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function showViewDialog() {
    // Initialize map configuration
    $MAPCFG = new NagVisMapCfg($this->CORE, $this->name);
    // Read the map configuration file (Only global section!)
    $MAPCFG->readMapConfig(1);

		// Build index template
		$INDEX = new NagVisIndexView($this->CORE);
		
		// Need to load the custom stylesheet?
		$customStylesheet = $MAPCFG->getValue('global',0, 'stylesheet');
		if($customStylesheet !== '') {
			$INDEX->setCustomStylesheet($CORE->MAINCFG->getValue('paths','htmlstyles') . $customStylesheet);
		}
		
		// Need to parse the header menu?
		if($MAPCFG->getValue('global',0 ,'header_menu')) {
      // Parse the header menu
      $HEADER = new GlobalHeaderMenu($this->CORE, $MAPCFG->getValue('global',0 ,'header_template'), $MAPCFG);
			$INDEX->setHeaderMenu($HEADER->__toString());
    }

		// Initialize map view
		$this->MAP = new NagVisMapView($this->CORE, $this->name);

		// The user is searching for an object
		$this->MAP->setSearch($this->search);
		
    //FIXME: Maintenance mode not supported atm
		//$this->MAP->MAPOBJ->checkMaintenance(1);
    $INDEX->setContent($this->MAP->parse());

		return $INDEX->parse();

		//FIXME: Rotation properties not supported atm
    //$FRONTEND->addBodyLines($FRONTEND->parseJs('oRotationProperties = '.$FRONTEND->getRotationPropertiesJson(0).';'));
	}
}
?>
