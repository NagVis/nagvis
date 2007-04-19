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
		parent::GlobalMainCfg($configFile);
	}
	
	/**
	 * Gets all defined maps
	 *
	 * @return	Array maps
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getMaps() {
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
		$this->config[$sec] = Array();
		
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
		$this->config[$sec] = '';
		unset($this->config[$sec]);
		
		return TRUE;
	}
}
?>
