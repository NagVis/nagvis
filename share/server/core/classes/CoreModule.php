<?php
abstract class CoreModule {
	protected $CORE = null;
	protected $AUTHENTICATION = null;
	protected $AUTHORISATION = null;
	protected $UHANDLER = null;
	
	protected $aActions = Array();
	protected $aObjects = Array();
	protected $sAction = '';
	protected $sObject = '';
	
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
		$bRequiresAuthorisation = false;
		
		if(isset($this->aActions[$this->sAction]) && $this->aActions[$this->sAction] === REQUIRES_AUTHORISATION) {
			$bRequiresAuthorisation = true;
		}
		
		return $bRequiresAuthorisation;
	}
	
	public function offersObject($sObject) {
		if(isset($this->aObjects[$sObject])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function setObject($sObject) {
		if($this->offersObject($sObject)) {
			$this->sObject = $sObject;
			return true;
		} else {
			return false;
		}
	}
	
	public function getObject() {
		return $this->sObject;
	}
	
	public function checkForObjectAuthorisation() {
		$bRet = false;
		
		if($this->sObject !== '') {
			$bRet = true;
		}
		
		return $bRet;
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