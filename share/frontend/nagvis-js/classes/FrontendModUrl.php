<?php
class FrontendModUrl extends FrontendModule {
	private $url = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;

		// Parse the view specific options
		$aOpts = Array('show' => MATCH_STRING_URL);
		
		$aVals = $this->getCustomOptions($aOpts);
		$this->url = $aVals['show'];
		
		// Register valid actions
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
	
	private function showViewDialog() {
		// Only show when map name given
		if(!isset($this->url) || $this->url == '') {
			//FIXME: Error handling
			echo "No url given";
			exit(1);
		}
		
		// Build index template
		$INDEX = new NagVisIndexView($this->CORE);
		
		// Need to parse the header menu?
		if($this->CORE->MAINCFG->getValue('index','headermenu')) {
      // Parse the header menu
      $HEADER = new GlobalHeaderMenu($this->CORE, $this->CORE->MAINCFG->getValue('index', 'headertemplate'));
			$INDEX->setHeaderMenu($HEADER->__toString());
    }
		
		// Initialize map view
		$this->VIEW = new NagVisUrlView($this->CORE, $this->url);
    $INDEX->setContent($this->VIEW->parse());

		return $INDEX->parse();

		//FIXME: Rotation properties not supported atm
    //$FRONTEND->addBodyLines($FRONTEND->parseJs('oRotationProperties = '.$FRONTEND->getRotationPropertiesJson(0).';'));
	}
}
?>
