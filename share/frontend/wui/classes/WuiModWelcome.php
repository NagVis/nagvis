<?php
class WuiModWelcome extends WuiModule {
	
	public function __construct(WuiCore $CORE) {
		$this->CORE = $CORE;

		// Register valid actions
		$this->aActions = Array(
			'show' => REQUIRES_AUTHORISATION
		);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'show':
					// Show the view dialog to the user
					$sReturn = $this->showWelcomeDialog();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function showWelcomeDialog() {
		// Build index template
		$INDEX = new WuiViewIndex($this->CORE);
		$INDEX->setSubtitle('WUI');
		$INDEX->setBackgroundColor($MAPCFG->getValue('global',0, 'background_color'));
		
		// Need to load the custom stylesheet?
		$customStylesheet = $MAPCFG->getValue('global',0, 'stylesheet');
		if($customStylesheet !== '') {
			$INDEX->setCustomStylesheet($CORE->getMainCfg()->getValue('paths','htmlstyles') . $customStylesheet);
		}
		
		// FIXME: Header menu?
		
		$MAP = new WuiMap(WuiCore::getInstance(), $MAPCFG);
		$INDEX->setContent($MAP->parseMap());
		
		/*$FRONTEND = new WuiFrontend($this->CORE, $MAPCFG);
		$FRONTEND->checkPreflight();
		$FRONTEND->getMap();
		
		if($_GET['map'] != '') {
			// Do preflight checks (before printing the map)
			if(!$MAPCFG->checkMapConfigWriteable(1)) {
				exit;
			}
			if(!$MAPCFG->checkMapLocked(1)) {
				// Lock the map for the defined time
				$MAPCFG->writeMapLock();
			}
		}
		
		// print the HTML page
		$FRONTEND->printPage();*/
		
		// Initialize map view
		$this->VIEW = new WuiViewMap($this->CORE, '');
		
    //FIXME: Maintenance mode not supported atm
		//$this->VIEW->MAPOBJ->checkMaintenance(1);
		
		//FIXME: Map locking
		/*if(!$MAPCFG->checkMapLocked(1)) {
				// Lock the map for the defined time
				$MAPCFG->writeMapLock();
			}*/
		
    $INDEX->setContent($this->VIEW->parse());

		return $INDEX->parse();
	}
}
?>
