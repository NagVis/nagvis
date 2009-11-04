<?php
class FrontendModLogonDialog extends FrontendModule {
	protected $CORE;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => 0);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					// Check if user is already authenticated
					if(!isset($this->AUTHENTICATION) || !$this->AUTHENTICATION->isAuthenticated()) {
						$VIEW = new NagVisLoginView($this->CORE);
						$sReturn = $VIEW->parse();
					} else {
						// When the user is already authenticated redirect to start page (overview)
						Header('Location:'.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					}
				break;
			}
		}
		
		return $sReturn;
	}
}

?>
