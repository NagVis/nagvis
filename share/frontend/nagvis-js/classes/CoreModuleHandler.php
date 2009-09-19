<?php
class CoreModuleHandler {
	protected $CORE;
	protected $aRegistered;
	protected $sPrefix;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aRegistered = Array();
		$this->sPrefix = 'AjaxMod';
	}
	
	public function loadModule($sModule) {
		// Check if module class is registered
		if(isset($this->aRegistered[$this->sPrefix.$sModule]) && $this->aRegistered[$this->sPrefix.$sModule] === 'active') {
			$className = $this->sPrefix.$sModule;
			
			// create instance of module
			$MOD = new $className($this->CORE);
			
			// return instance
			return $MOD;
		} else {
			// Error handling
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('moduleNotRegistered'));
			return null;
		}
	}
	
	public function regModule($sModule) {
		// Check if module class exists
    if(class_exists($this->sPrefix.$sModule)) {
			// Register the module at the module handler
			$this->aRegistered[$this->sPrefix.$sModule] = 'active';
			
			return true;
		} else {
			// Error handling
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('moduleClassNotExists'));
			return false;
		}
	}
}

?>
