<?php
class CoreModSearch extends CoreModule {
	protected $CORE;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => REQUIRES_AUTHORISATION);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					// Check if user is already authenticated
					if(isset($this->AUTHENTICATION) && $this->AUTHENTICATION->isAuthenticated()) {
						$VIEW = new NagVisViewSearch($this->CORE);
						$sReturn = json_encode(Array('code' => $VIEW->parse()));
					} else {
						$sReturn = '';
					}
				break;
			}
		}
		
		return $sReturn;
	}
}

?>
