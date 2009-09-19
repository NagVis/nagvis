<?php
class FrontendModInfo extends FrontendModule {
	protected $CORE;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => 0);
		
		$this->FHANDLER = new FrontendRequestHandler($_POST);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					$sReturn = $this->displayDialog();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function displayDialog() {
		$VIEW = new NagVisInfoView($this->CORE);
		return $VIEW->parse();
	}
}
?>
