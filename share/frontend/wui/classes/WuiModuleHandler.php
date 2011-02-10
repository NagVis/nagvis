<?php
class WuiModuleHandler extends CoreModuleHandler {
	public function __construct() {
		parent::__construct(WuiCore::getInstance());
		
		$this->sPrefix = 'WuiMod';
	}

	public function loadModule($s) {
		// Redirect unhandled modules to welcome page
		if($s !== 'Map' && $s !== WuiCore::getInstance()->getMainCfg()->getValue('global', 'logonmodule'))
			$s = 'Welcome';
		return parent::loadModule($s);
	}
}

?>
