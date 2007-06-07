<?php
/**
 * This Class handles the NagVis configuration file
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
class WuiMapCfg extends GlobalMapCfg {
	var $name;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG	
	 * @param	String			$name		Name of the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiMapCfg(&$MAINCFG,$name='') {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapCfg::WuiMapCfg(&$MAINCFG,'.$name.')');
		$this->MAINCFG = &$MAINCFG;
		$this->name	= $name;
		
		$this->getMap();
		parent::GlobalMapCfg($MAINCFG,$this->name);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapCfg::WuiMapCfg()');
	}
	
	/**
	 * Reads which map should be displayed, primary use
	 * the map defined in the url, if there is no map
	 * in url, use first entry of "maps" defined in 
	 * the NagVis main config
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function getMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapCfg::getMap()');
		// check the $this->name string for security reasons (its the ONLY value we get directly from external...)
		// Allow ONLY Characters, Numbers, - and _ inside the Name of a Map
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapCfg::getMap()');
		$this->name = preg_replace("/[^a-zA-Z0-9_-]/",'',$this->name);
	}
	
	/**
	 * Reads the configuration file of the map and 
	 * sends it as download to the client.
	 *
	 * @return	Boolean   Only returns FALSE if something went wrong
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function exportMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapCfg::exportMap()');
		if($this->checkMapConfigReadable(1)) {
			$mapPath = $this->MAINCFG->getValue('paths', 'mapcfg').$this->getName().'.cfg';
			
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.$this->getName().'.cfg');
			header('Content-Length: '.filesize($mapPath));
			
			if(readfile($mapPath)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapCfg::exportMap(): exit()');
				exit;
			} else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapCfg::exportMap(): FALSE');
				return FALSE;	
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapCfg::exportMap(): FALSE');
			return FALSE;	
		}
	}
	
	/**
	 * Gets lockfile informations
	 *
	 * @param	Boolean $ignoreLock
	 * @param	Boolean $printErr
	 * @return	Array/Boolean   Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    function checkMapLocked($ignoreLock=0,$printErr=1) {
	    if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapLocked('.$ignoreLock.','.$printErr.')');
        // read lockfile
        $lockdata = $this->readMapLock();
        if(is_array($lockdata)) {
            // check if the lock is older than 5 Minutes and don't ignore lock
            if($lockdata['time'] > time() - 360) {
                if($ignoreLock == 0) {
                    // the lock should be ignored
    				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLocked(): FALSE');
                    return FALSE;
                } else {
                    // there is a lock and it should be recognized
                    // check if this is the lock of the current user (Happens e.g. by pressing F5)
                    if($this->MAINCFG->getRuntimeValue('user') == $lockdata['user'] && $_SERVER['REMOTE_ADDR'] == $lockdata['ip']) {
                        // refresh the lock (write a new lock)
                        $this->writeMapLock();
                        // it's locked by the current user, so it's not locked for him
                        return FALSE;
                    }
                    if($printErr == 1) {
                        $LANG = new GlobalLanguage($this->MAINCFG,'wui:global');
                        
                        // message the user that there is a lock by another user, the user can decide wether he want's to override it or not
                        print '<script>if(!confirm(\''.$LANG->getMessageText('mapLocked','MAP~'.$this->name.',TIME~'.date('d.m.Y H:i',$lockdata['time']).',USER~'.$lockdata['user'].',IP~'.$lockdata['ip']).'\',\'\')) { history.back(); }</script>';
    				}
    				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLocked(): TRUE');
    				return TRUE;
                }
            } else {
                // delete lockfile & continue
                // try to delete map lock, if nothing to delete its OK
                $this->deleteMapLock();
    			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLocked(): FALSE');
                return FALSE;
            }
        } else {
            // no valid informations in lock or no lock there
            // try to delete map lock, if nothing to delete its OK
            $this->deleteMapLock();
    		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLocked(): FALSE');
            return FALSE;
        }
    }
    
	
	/**
	 * Reads the contents of the lockfile
	 *
	 * @return	Array/Boolean   Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function readMapLock() {
	    if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::readMapLock()');
	    if($this->checkMapLockReadable(0)) {
	        $fileContent = file($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.lock');
	        // only recognize the first line, explode it by :
	        $arrContent = explode(':',$fileContent[0]);
	        // if there are more elements in the array it is OK
	        if(count($arrContent) > 0) {
	            return Array('time' => $arrContent[0], 'user' => $arrContent[1], 'ip' => $arrContent[2]);
	        } else {
	            if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::readMapLock(): FALSE');
	            return FALSE;
	        }
        } else {
	        if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::readMapLock(): FALSE');
	        return FALSE;
	    }
	}
	
	/**
	 * Writes the lockfile for a map
	 *
	 * @return	Boolean     Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function writeMapLock() {
	    if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::writeMapLock()');
	    // open file for writing and insert the needed informations
	 	$fp = fopen($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.lock','w');
	 	fwrite($fp,time().':'.$this->MAINCFG->getRuntimeValue('user').':'.$_SERVER['REMOTE_ADDR']);
	 	fclose($fp);
	 	if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::writeMapLock(): TRUE');
	}
	
	/**
	 * Deletes the lockfile for a map
	 *
	 * @return	Boolean     Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function deleteMapLock() {
	    if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::deleteMapLock()');
	    if($this->checkMapLockReadable(0)) {
	        if(unlink($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.lock')) {
	            // map lock deleted => OK
	            if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::deleteMapLock(): TRUE');
	            return TRUE;
	        } else {
	            if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::deleteMapLock(): FALSE');
	            return FALSE;
	        }
        } else {
            // no map lock to delete => OK
	        if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::deleteMapLock(): TRUE');
	        return TRUE;   
	    }
	}
	
	/**
	 * Checks for existing lockfile
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkMapLockExists($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapLockExists('.$printErr.')');
		if($this->name != '') {
			if(file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.lock')) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLockExists(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:global'));
		            $FRONTEND->messageToUser('ERROR','mapLockNotExists','MAP~'.$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.lock');
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLockExists(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLockExists(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable lockfile
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkMapLockReadable($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapLockReadable('.$printErr.')');
		if($this->name != '') {
			if($this->checkMapLockExists($printErr) && is_readable($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.lock')) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLockReadable(): TRUE');
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:global'));
		            $FRONTEND->messageToUser('ERROR','mapLockNotReadable','MAP='.$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.lock');
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLockReadable(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapLockReadable(): FALSE');
			return FALSE;
		}
	}
}
?>
