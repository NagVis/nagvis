<?php
class FrontendModAutoMap extends FrontendModule {
	private $name;
	private $opts;
	private $rotation = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;

		// Parse the view specific options
		$aOpts = Array('show' => MATCH_MAP_NAME,
		               'backend' => MATCH_STRING_NO_SPACE_EMPTY,
		               'root' => MATCH_STRING_NO_SPACE_EMPTY,
		               'maxLayers' => MATCH_INTEGER_EMPTY,
		               'renderMode' => MATCH_AUTOMAP_RENDER_MODE,
		               'width' => MATCH_INTEGER_EMPTY,
		               'height' => MATCH_INTEGER_EMPTY,
		               'ignoreHosts' => MATCH_STRING_NO_SPACE_EMPTY,
		               'filterGroup' => MATCH_STRING_NO_SPACE_EMPTY,
		               'rotation' => MATCH_ROTATION_NAME_EMPTY);
		
		$aVals = $this->getCustomOptions($aOpts);
		$this->name = $aVals['show'];
		$this->rotation = $aVals['rotation'];
		unset($aVals['show']);
		unset($aVals['rotation']);
		$this->opts = $aVals;
		
		// Register valid actions
		$this->aActions = Array(
			'view' => REQUIRES_AUTHORISATION
		);
		
		// Register valid objects
		$this->aObjects = $this->CORE->getAvailableAutomaps();
		
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
		// Initialize backend(s)
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
    // Initialize map configuration
    $MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
    // Read the map configuration file
    $MAPCFG->readMapConfig();

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
		$this->VIEW = new NagVisAutoMapView($this->CORE, $MAPCFG->getName());
		
		// Render the automap
		$AUTOMAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $this->opts);
		$this->VIEW->setContent($AUTOMAP->parseMap());
		
		// Maybe it is needed to handle the requested rotation
		if($this->rotation != '') { 
			$ROTATION = new FrontendRotation($this->CORE, $this->rotation);
			$ROTATION->setStep('automap', $this->name);
			$this->VIEW->setRotation($ROTATION->getRotationProperties());
		}
		
    //FIXME: Maintenance mode not supported atm
		//$this->MAP->MAPOBJ->checkMaintenance(1);
		
    $INDEX->setContent($this->VIEW->parse());

		return $INDEX->parse();
	}
}
?>
