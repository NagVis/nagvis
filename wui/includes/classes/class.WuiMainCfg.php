<?php
/**
 * This Class handles the NagVis configuration file for the wui
 */
class WuiMainCfg extends GlobalMainCfg {
	/**
	 * Class Constructor
	 *
	 * @param	String	$configFile			String with path to config file
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiMainCfg($configFile) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMainCfg::WuiMainCfg($configFile)');
		parent::GlobalMainCfg($configFile);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMainCfg::WuiMainCfg($configFile)');
	}
	
	/**
	 * Gets all defined maps
	 *
	 * @return	Array maps
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getMaps() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMainCfg::getMaps()');
		$files = Array();
		
		if ($handle = opendir($this->getValue('paths', 'mapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".cfg") {
					$files[] = substr($file,0,strlen($file)-4);
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMainCfg::getMaps(): Array(...)');
		return $files;
	}
	
	/**
	 * Sets a config section in the config array
	 *
	 * @param	String	$sec	Section
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function setSection($sec) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMainCfg::setSection($sec)');
		// Try to append new backends after already defined
		if(ereg('^backend_', $sec)) {
		    $lastBackendIndex = 0;
		    $i = 0;
		    // Loop all sections to find the last defined backend
		    foreach($this->config AS $type => $vars) {
		        // If the current section is a backend
		        if(ereg('^backend_', $type)) {
		            $lastBackendIndex = $i;
		        }
		        $i++;
		    }
		    
		    if($lastBackendIndex != 0) {
		        // Append the new section after the already defined
		        $slicedBefore = array_slice($this->config, 0, ($lastBackendIndex + 1));
		        $slicedAfter = array_slice($this->config, ($lastBackendIndex + 1));
		        $tmp[$sec] = Array();
		        $this->config = array_merge($slicedBefore,$tmp,$slicedAfter);
		    } else {
		        // If no defined backend found, add it to the EOF
		        $this->config[$sec] = Array();
		    }
	    } else {
	        $this->config[$sec] = Array();
	    }
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMainCfg::setSection(): TRUE');
		return TRUE;
	}
	
	/**
	 * Deletes a config section in the config array
	 *
	 * @param	String	$sec	Section
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function delSection($sec) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMainCfg::delSection($sec)');
		$this->config[$sec] = '';
		unset($this->config[$sec]);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMainCfg::delSection(): TRUE');
		return TRUE;
	}
}
?>
