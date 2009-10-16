<?php
class FrontendModAutoMap extends FrontendModule {
	private $CORE;
	private $name;
	private $opts;
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;

		$UHANDLER = new CoreUriHandler($this->CORE); 
		$this->name = $UHANDLER->get('show');

		$this->getAutomapOptions($UHANDLER);
		
		$this->aActions = Array(
			'view' => REQUIRES_AUTHORISATION
		);
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

	private function getAutomapOptions(CoreUriHandler $UHANDLER) {
    // Parse view specific uri params
		$aKeys = Array(
		  'backend'     => '',
		  'root'      => '',
		  'maxLayers'   => '',
		  'renderMode'  => '',
		  'width'     => '',
		  'height'    => '',
		  'ignoreHosts' => '',
		  'filterGroup' => '');

		// Load the specific params to the UriHandler
		$UHANDLER->parseModSpecificUri($aKeys);

		// Now get those params
		foreach($aKeys AS $key => $val) {
			$this->opts[$key] = $UHANDLER->get($key);
		}
	}
	
	private function showViewDialog() {
		// Only show when map name given
		if(!isset($this->name) || $this->name == '') {
			//FIXME: Error handling
			echo "No map given";
			exit(1);
		}
		
		// Initialize backend(s)
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
    // Initialize map configuration
    $MAPCFG = new NagVisAutomapCfg($this->CORE, $this->name);
    // Read the map configuration file
    $MAPCFG->readMapConfig();

		// Build index template
		$INDEX = new NagVisIndexView($this->CORE);

		//FIXME: Rotation properties not supported atm
    //$FRONTEND->addBodyLines($FRONTEND->parseJs('oRotationProperties = '.$FRONTEND->getRotationPropertiesJson(0).';'));
		
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
		
    //FIXME: Maintenance mode not supported atm
		//$this->MAP->MAPOBJ->checkMaintenance(1);
		
    $INDEX->setContent($this->VIEW->parse());

		return $INDEX->parse();
	}
}
?>
