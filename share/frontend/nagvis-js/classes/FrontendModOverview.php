<?php
class FrontendModOverview extends FrontendModule {
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
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
    // Build index template
    $INDEX = new NagVisIndexView($this->CORE);

    //FIXME: Rotation properties not supported atm
    //$FRONTEND->addBodyLines($FRONTEND->parseJs('oRotationProperties = '.$FRONTEND->getRotationPropertiesJson(0).';'));

    // Need to parse the header menu?
    if($this->CORE->getMainCfg()->getValue('index','headermenu')) {
      // Parse the header menu
      $HEADER = new GlobalHeaderMenu($this->CORE, $this->AUTHORISATION, $this->CORE->getMainCfg()->getValue('index', 'headertemplate'), '');
      $INDEX->setHeaderMenu($HEADER->__toString());
    }

    // Initialize map view
    $this->VIEW = new NagVisOverviewView($this->CORE);
    $INDEX->setContent($this->VIEW->parse());

    return $INDEX->parse();
	}
}
?>
