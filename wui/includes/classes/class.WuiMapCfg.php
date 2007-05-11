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
}
?>
