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
		// Load map configuration
		$MAPCFG = new WuiMapCfg($this->CORE, '');
		$MAPCFG->readMapConfig();
		
		// Build index template
		$INDEX = new WuiViewIndex($this->CORE);
		$INDEX->setSubtitle('WUI');
		$INDEX->setBackgroundColor('#fff');
		
		// Preflight checks
		if(!$this->CORE->checkPHPMBString(1)) {
			exit;
		}
		
		// FIXME: Header menu?
		
		// Print welcome page
		$this->VIEW = new WuiViewWelcome($this->CORE, $MAPCFG);
    $INDEX->setContent($this->VIEW->parse());

		return $INDEX->parse();
	}
}
?>
