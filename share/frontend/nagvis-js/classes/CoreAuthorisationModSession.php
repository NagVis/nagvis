<?php

class CoreAuthorisationModSession extends CoreAuthorisationModule {
	private $CORE;
	private $SHANDLER;
	
	public function __construct(GlobalCore $CORE, CoreAuthHandler $AUTHENTICATION) {
		$this->CORE = $CORE;
		$this->SHANDLER = new CoreSessionHandler($this->CORE);
	}
	
	public function parsePermissions() {
		if($this->SHANDLER->isSetAndNotEmpty('userPermissions')) {
			return $this->SHANDLER->get('userPermissions');
		} else {
			return false;
		}
	}
}
?>
