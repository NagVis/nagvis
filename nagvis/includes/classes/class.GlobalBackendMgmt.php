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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendMgmt::GlobalBackendMgmt($MAINCFG)');
		$this->MAINCFG = &$MAINCFG;
		$this->BACKENDS = Array();
		
		$this->initBackends();
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendMgmt::GlobalBackendMgmt(): 0');
		return 0;
	}
	
	/**
	 * Reads all defined Backend-IDs from the MAINCFG
	 *
	 * @return	Array Backend-IDs
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getBackends() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendMgmt::getBackends()');
		$ret = Array();
		foreach($this->MAINCFG->config AS $sec => $var) {
			if(preg_match('/^backend_/i', $sec)) {
				$ret[] = $var['backendid'];
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendMgmt::getBackends(): Array(...)');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendMgmt::checkBackendExists('.$backendId.','.$printErr.')');
		if($backendId != '') {
			if(file_exists($this->MAINCFG->getValue('paths','class').'class.GlobalBackend-'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype').'.php')) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendMgmt::checkBackendExists(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:global'));
		            $FRONTEND->messageToUser('ERROR','backendNotExists','BACKENDID~'.$backendId.',BACKENDTYPE~'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype'));
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendMgmt::checkBackendExists(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendMgmt::checkBackendExists(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendMgmt::checkBackendInitialized('.$backendId.','.$printErr.')');
		if($backendId != '') {
			if(isset($this->BACKENDS[$backendId]) && is_object($this->BACKENDS[$backendId])) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendMgmt::checkBackendInitialized(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:global'));
		            $FRONTEND->messageToUser('ERROR','backendNotInitialized','BACKENDID~'.$backendId.',BACKENDTYPE~'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype'));
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendMgmt::checkBackendInitialized(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendMgmt::checkBackendInitialized(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Initializes all backends
	 *
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function initBackends() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackendMgmt::initBackends()');
		$aBackends = $this->getBackends();
		
		if(!count($aBackends)) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'backend:global'));
		    $FRONTEND->messageToUser('ERROR','noBackendDefined');
		} else {
			foreach($aBackends AS $backendId) {
				if($this->checkBackendExists($backendId,1)) {
					require_once($this->MAINCFG->getValue('paths','class').'class.GlobalBackend-'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype').'.php');
					$backendClass = 'GlobalBackend'.$this->MAINCFG->getValue('backend_'.$backendId,'backendtype');
					$this->BACKENDS[$backendId] = new $backendClass($this->MAINCFG,$backendId);
				}
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackendMgmt::initBackends()');
	}
}
?>