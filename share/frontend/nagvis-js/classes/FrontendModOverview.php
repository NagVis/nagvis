<?php
class FrontendModOverview extends FrontendModule {
	private $rotation = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		// Parse the view specific options
		$aOpts = Array('rotation' => MATCH_ROTATION_NAME_EMPTY);
		$aVals = $this->getCustomOptions($aOpts);
		$this->rotation = $aVals['rotation'];
		
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
		
		// Need to parse the header menu?
		if($this->CORE->getMainCfg()->getValue('index','headermenu')) {
			// Parse the header menu
			$HEADER = new GlobalHeaderMenu($this->CORE, $this->AUTHORISATION, $this->CORE->getMainCfg()->getValue('index', 'headertemplate'), '');
			
			// Put rotation information to header menu
			if($this->rotation != '') {
				$HEADER->setRotationEnabled();
			}
			
			$INDEX->setHeaderMenu($HEADER->__toString());
		}

    // Initialize map view
    $this->VIEW = new NagVisOverviewView($this->CORE);
    
		// Maybe it is needed to handle the requested rotation
		if($this->rotation != '') { 
			$ROTATION = new FrontendRotation($this->CORE, $this->rotation);
			$ROTATION->setStep('overview', '');
			$this->VIEW->setRotation($ROTATION->getRotationProperties());
		}
    
    $INDEX->setContent($this->VIEW->parse());

    return $INDEX->parse();
	}
}
?>
