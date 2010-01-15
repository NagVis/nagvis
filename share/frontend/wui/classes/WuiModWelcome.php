<?php
class WuiModWelcome extends WuiModule {
	
	public function __construct(WuiCore $CORE) {
		$this->CORE = $CORE;

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
					$sReturn = $this->showWelcomeDialog();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function showWelcomeDialog() {
		// Need to initialize the Uri handler manually cause no parameters are
		// parsed in this module
		$this->initUriHandler();
		
		// Build index template
		$INDEX = new WuiViewIndex($this->CORE);
		$INDEX->setSubtitle('WUI');
		$INDEX->setBackgroundColor('#fff');
		
		// Preflight checks
		if(!$this->CORE->checkPHPMBString(1)) {
			exit;
		}
		
		// Create welcome page
		$this->VIEW = new WuiViewWelcome($this->CORE);
		
		// Need to parse the header menu by config or url value?
    if($this->CORE->getMainCfg()->getValue('wui','headermenu')) {
      // Parse the header menu
      $HEADER = new WuiHeaderMenu($this->CORE, $this->AUTHORISATION, $this->UHANDLER, $this->CORE->getMainCfg()->getValue('wui','headertemplate'));
      $INDEX->setHeaderMenu($HEADER->__toString());
    }
		
		// Print welcome page
    $INDEX->setContent($this->VIEW->parse());

		return $INDEX->parse();
	}
}
?>
