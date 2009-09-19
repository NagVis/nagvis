<?php
abstract class FrontendModule {
	protected $AUTHENTICATION;
	protected $AUTHORISATION;
	
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
	
	abstract public function handleAction();
}
?>