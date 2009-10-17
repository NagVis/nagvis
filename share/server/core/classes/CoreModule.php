<?php
abstract class CoreModule {
	protected $CORE = null;
	protected $AUTHENTICATION = null;
	protected $AUTHORISATION = null;
	protected $UHANDLER = null;
	
	protected $aActions = Array();
	protected $sAction = '';
	protected $bRequiresAuthorisation;
	
	public function passAuth($AUTHENTICATION, $AUTHORISATION) {
		$this->AUTHENTICATION = $AUTHENTICATION;
		$this->AUTHORISATION = $AUTHORISATION;
	}
	
	public function offersAction($sAction) {
		if(isset($this->aActions[$sAction])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function setAction($sAction) {
		if($this->offersAction($sAction)) {
			$this->sAction = $sAction;
			return true;
		} else {
			return false;
		}
	}
	
	public function actionRequiresAuthorisation() {
		if(isset($this->aActions[$this->sAction]) && $this->aActions[$this->sAction] === REQUIRES_AUTHORISATION) {
			$this->bRequiresAuthorisation = true;
		} else {
			$this->bRequiresAuthorisation = false;
		}
		
		return $this->bRequiresAuthorisation;
	}
	
	protected function getCustomOptions($aKeys) {
		// Initialize on first call
		if($this->UHANDLER === null) {
			$this->UHANDLER = new CoreUriHandler($this->CORE);
		}
		
		// Load the specific params to the UriHandler
		$this->UHANDLER->parseModSpecificUri($aKeys);
		
		// Now get those params
		$aReturn = Array();
		foreach($aKeys AS $key => $val) {
			$aReturn[$key] = $this->UHANDLER->get($key);
		}
		
		return $aReturn;
	}
	
	abstract public function handleAction();
}
?>