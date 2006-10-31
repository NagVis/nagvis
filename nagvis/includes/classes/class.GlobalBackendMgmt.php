<?php
class GlobalBackendMgmt {
	var $MAINCFG;
	var $BACKENDS;
	
	/**
	* Constructor
	* Initializes all backends
	*
	* @param	config $MAINCFG
	* @author	Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function GlobalBackendMgmt(&$MAINCFG) {
		$this->MAINCFG = &$MAINCFG;
		$this->BACKENDS = Array();
		
		$this->initBackends();
		
		return 0;
	}
	
	function getBackends() {
		$ret = Array();
		foreach($this->MAINCFG->config AS $sec => $var) {
			if(preg_match("/^backend_/i", $sec)) {
				$ret[] = $var['backendid'];
			}
		}
		
		return $ret;
	}
	
	function initBackends() {
		foreach($this->getBackends() AS $backendId) {
			require_once($this->MAINCFG->getValue('paths','class').'class.GlobalBackend-'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype').'.php');
			$this->BACKENDS[$backendId] = new GlobalBackend($this->MAINCFG,$backendId);
		}
	}
}
?>
