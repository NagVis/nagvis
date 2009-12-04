<?php
class FrontendModMap extends FrontendModule {
	private $name = '';
	private $search = '';
	private $rotation = '';
	
	private $viewOpts = Array();
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;

		// Parse the view specific options
		$aOpts = Array('show' => MATCH_MAP_NAME,
		               'search' => MATCH_STRING_NO_SPACE_EMPTY,
		               'rotation' => MATCH_ROTATION_NAME_EMPTY,
		               'enableHeader' => MATCH_BOOLEAN_EMPTY,
		               'enableContext' => MATCH_BOOLEAN_EMPTY,
		               'enableHover' => MATCH_BOOLEAN_EMPTY);
		
		$aVals = $this->getCustomOptions($aOpts);
		$this->name = $aVals['show'];
		$this->search = $aVals['search'];
		$this->rotation = $aVals['rotation'];
		
		$this->viewOpts['enableHeader'] = $aVals['enableHeader'];
		$this->viewOpts['enableContext'] = $aVals['enableContext'];
		$this->viewOpts['enableHover'] = $aVals['enableHover'];
		
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
			$INDEX->setCustomStylesheet($CORE->getMainCfg()->getValue('paths','htmlstyles') . $customStylesheet);
		}
		
		// Header menu enabled/disabled by url?
		if($this->viewOpts['enableHeader'] !== false && $this->viewOpts['enableHeader']) {
			$showHeader = true;
		} elseif($this->viewOpts['enableHeader'] !== false && !$this->viewOpts['enableHeader']) {
			$showHeader = false;
		} else {
			$showHeader = $MAPCFG->getValue('global',0 ,'header_menu');
		}
		
		// Need to parse the header menu by config or url value?
		if($showHeader) {
      // Parse the header menu
      $HEADER = new GlobalHeaderMenu($this->CORE, $this->AUTHORISATION, $MAPCFG->getValue('global',0 ,'header_template'), $MAPCFG);
      
      // Put rotation information to header menu
      if($this->rotation != '') {
      	$HEADER->setRotationEnabled();
      }
      
			$INDEX->setHeaderMenu($HEADER->__toString());
    }

		// Initialize map view
		$this->VIEW = new NagVisMapView($this->CORE, $this->name);
		
		// The user is searching for an object
		$this->VIEW->setSearch($this->search);
		
		// Set view modificators (Hover, Context toggle)
		$this->VIEW->setViewOpts($this->viewOpts);
		
		// Maybe it is needed to handle the requested rotation
		if($this->rotation != '') {
			// Only allow the rotation if the user is permitted to use it
			if($this->AUTHORISATION->isPermitted('Rotation', 'view', $this->rotation)) {
				$ROTATION = new FrontendRotation($this->CORE, $this->rotation);
				$ROTATION->setStep('map', $this->name);
				$this->VIEW->setRotation($ROTATION->getRotationProperties());
			}
		}
		
    //FIXME: Maintenance mode not supported atm
		//$this->VIEW->MAPOBJ->checkMaintenance(1);
    $INDEX->setContent($this->VIEW->parse());

		return $INDEX->parse();
	}
}
?>
