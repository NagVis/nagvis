<?php
class FrontendModAutoMap extends FrontendModule {
	private $name;
	private $opts;
	private $rotation = '';
	
	private $viewOpts = Array();
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;

		// Parse the view specific options
		$aOpts = Array('show' => MATCH_MAP_NAME,
		               'backend' => MATCH_STRING_NO_SPACE_EMPTY,
		               'root' => MATCH_STRING_NO_SPACE_EMPTY,
		               'maxLayers' => MATCH_INTEGER_EMPTY,
		               'childLayers' => MATCH_INTEGER_EMPTY,
		               'parentLayers' => MATCH_INTEGER_EMPTY,
		               'renderMode' => MATCH_AUTOMAP_RENDER_MODE,
		               'width' => MATCH_INTEGER_EMPTY,
		               'height' => MATCH_INTEGER_EMPTY,
		               'ignoreHosts' => MATCH_STRING_NO_SPACE_EMPTY,
		               'filterGroup' => MATCH_STRING_NO_SPACE_EMPTY,
		               'rotation' => MATCH_ROTATION_NAME_EMPTY,
		               'enableHeader' => MATCH_BOOLEAN_EMPTY,
		               'enableContext' => MATCH_BOOLEAN_EMPTY,
		               'enableHover' => MATCH_BOOLEAN_EMPTY);
		
		$aVals = $this->getCustomOptions($aOpts);
		$this->name = $aVals['show'];
		$this->rotation = $aVals['rotation'];
		
		$this->viewOpts['enableHeader'] = $aVals['enableHeader'];
		$this->viewOpts['enableContext'] = $aVals['enableContext'];
		$this->viewOpts['enableHover'] = $aVals['enableHover'];
		
		unset($aVals['show']);
		unset($aVals['rotation']);
		unset($aVals['enableHeader']);
		unset($aVals['enableContext']);
		unset($aVals['enableHover']);
		
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
		
		// Need to parse the header menu?
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
		$this->VIEW = new NagVisAutoMapView($this->CORE, $MAPCFG->getName());
		
		// Set view modificators (Hover, Context toggle)
		$this->VIEW->setViewOpts($this->viewOpts);
		
		// Render the automap
		$AUTOMAP = new NagVisAutoMap($this->CORE, $MAPCFG, $BACKEND, $this->opts, IS_VIEW);
		$this->VIEW->setContent($AUTOMAP->parseMap());
		$this->VIEW->setAutomapParams($this->opts);
		
		// Maybe it is needed to handle the requested rotation
		if($this->rotation != '') {
			// Only allow the rotation if the user is permitted to use it
			if($this->AUTHORISATION->isPermitted('Rotation', 'view', $this->rotation)) {
				$ROTATION = new FrontendRotation($this->CORE, $this->rotation);
				$ROTATION->setStep('automap', $this->name);
				$this->VIEW->setRotation($ROTATION->getRotationProperties());
			}
		}
		
    //FIXME: Maintenance mode not supported atm
		//$this->MAP->MAPOBJ->checkMaintenance(1);
		
    $INDEX->setContent($this->VIEW->parse());

		return $INDEX->parse();
	}
}
?>
