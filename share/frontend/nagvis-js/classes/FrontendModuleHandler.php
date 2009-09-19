<?php
class FrontendModuleHandler extends CoreModuleHandler {
	public function __construct($CORE) {
		parent::__construct($CORE);
		
		$this->sPrefix = 'FrontendMod';
	}
}

?>
