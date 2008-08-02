<?php
/*****************************************************************************
 *
 * GlobalBackendMgmt.php - class for handling all backends
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalBackendMgmt {
	var $CORE;
	
	var $BACKENDS;
	
	/**
	 * Constructor
	 * Initializes all backends
	 *
	 * @param	config $MAINCFG
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalBackendMgmt(&$CORE) {
		$this->CORE = &$CORE;
		
		$this->BACKENDS = Array();
		
		$this->initBackends();
		
		return 0;
	}
	
	/**
	 * Reads all defined Backend-IDs from the MAINCFG
	 *
	 * @return	Array Backend-IDs
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackends() {
		$ret = Array();
		foreach($this->CORE->MAINCFG->config AS $sec => &$var) {
			if(preg_match('/^backend_/i', $sec)) {
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
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkBackendExists($backendId, $printErr) {
		if(isset($backendId) && $backendId != '') {
			if(file_exists($this->CORE->MAINCFG->getValue('paths','class').'GlobalBackend-'.$this->CORE->MAINCFG->getValue('backend_'.$backendId,'backendtype').'.php')) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->CORE);
					$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('backendNotExists','BACKENDID~'.$backendId.',BACKENDTYPE~'.$this->CORE->MAINCFG->getValue('backend_'.$backendId,'backendtype')));
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
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkBackendInitialized($backendId, $printErr) {
		if(isset($backendId) && $backendId != '') {
			if(isset($this->BACKENDS[$backendId]) && is_object($this->BACKENDS[$backendId])) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->CORE);
					$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('backendNotInitialized','BACKENDID~'.$backendId.',BACKENDTYPE~'.$this->CORE->MAINCFG->getValue('backend_'.$backendId,'backendtype')));
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
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function initBackends() {
		$aBackends = $this->getBackends();
		
		if(!count($aBackends)) {
			$FRONTEND = new GlobalPage($this->CORE);
		    $FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('noBackendDefined'));
		} else {
			foreach($aBackends AS &$backendId) {
				if($this->checkBackendExists($backendId, 1)) {
					require($this->CORE->MAINCFG->getValue('paths','class').'GlobalBackend-'.$this->CORE->MAINCFG->getValue('backend_'.$backendId,'backendtype').'.php');
					
					$backendClass = 'GlobalBackend'.$this->CORE->MAINCFG->getValue('backend_'.$backendId,'backendtype');
					$this->BACKENDS[$backendId] = new $backendClass($this->CORE,$backendId);
				}
			}
		}
	}
}
?>
