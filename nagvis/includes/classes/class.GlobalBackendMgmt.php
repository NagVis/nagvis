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
	
	/**
	 * Reads all defined Backend-IDs from the MAINCFG
	 *
	 * @return	Array Backend-IDs
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getBackends() {
		$ret = Array();
		foreach($this->MAINCFG->config AS $sec => $var) {
			if(preg_match("/^backend_/i", $sec)) {
				$ret[] = $var['backendid'];
			}
		}
		
		return $ret;
	}
	
	/**
	 * Checks for existing backend file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkBackendExists($backendId,$printErr) {
		if($backendId != '') {
			if(file_exists($this->MAINCFG->getValue('paths','class').'class.GlobalBackend-'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype').'.php')) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:global'));
		            $FRONTEND->messageToUser('ERROR','backendNotExists','BACKENDID~'.$backendId.',BACKENDTYPE~'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype'));
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for an initialized backend
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkBackendInitialized($backendId,$printErr) {
		if($backendId != '') {
			if(isset($this->BACKENDS[$backendId]) && is_object($this->BACKENDS[$backendId])) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:global'));
		            $FRONTEND->messageToUser('ERROR','backendNotInitialized','BACKENDID~'.$backendId.',BACKENDTYPE~'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype'));
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Initializes all backends
	 *
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function initBackends() {
		$aBackends = $this->getBackends();
		
		if(!count($aBackends)) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:global'));
		    $FRONTEND->messageToUser('ERROR','noBackendDefined');
		} else {
			foreach($aBackends AS $backendId) {
				if($this->checkBackendExists($backendId,1)) {
					require_once($this->MAINCFG->getValue('paths','class').'class.GlobalBackend-'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype').'.php');
					$backendClass = "GlobalBackend".$this->MAINCFG->getValue('backend_'.$backendId,'backendtype');
					$this->BACKENDS[$backendId] = new $backendClass($this->MAINCFG,$backendId);
				}
			}
		}
	}
}
?>