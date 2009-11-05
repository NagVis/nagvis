<?php

class FrontendTemplateSystem {
	private $TMPL;
	private $CORE;
	
	public function __construct($CORE) {
		// Load Dwoo. It is used as external library
		require_once('ext/dwoo-1.1.0/dwooAutoload.php');

		$this->CORE = $CORE;
		$this->TMPL = new Dwoo($this->CORE->getMainCfg()->getValue('paths','var').'tmpl/compile', $this->CORE->getMainCfg()->getValue('paths','var').'tmpl/cache');
	}
	
	public function getTmplSys() {
		return $this->TMPL;
	}
	
	public function getTmplFile($sTmpl) {
		return new Dwoo_Template_File($this->CORE->getMainCfg()->getValue('paths','pagetemplate').'default.'.$sTmpl.'.html');
	}
}

?>
