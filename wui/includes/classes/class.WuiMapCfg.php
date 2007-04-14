<?php
/**
 * This Class handles the NagVis configuration file
 *
 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
 */
class WuiMapCfg extends GlobalMapCfg {
	var $name;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG	
	 * @param	String			$name		Name of the map
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function WuiMapCfg(&$MAINCFG,$name='') {
		$this->MAINCFG = &$MAINCFG;
		$this->name	= $name;
		
		$this->getMap();
		parent::GlobalMapCfg($MAINCFG,$this->name);
	}
	
	/**
	 * Reads which map should be displayed, primary use
	 * the map defined in the url, if there is no map
	 * in url, use first entry of "maps" defined in 
	 * the NagVis main config
	 *
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getMap() {
		// check the $this->name string for security reasons (its the ONLY value we get directly from external...)
		// Allow ONLY Characters, Numbers, - and _ inside the Name of a Map
		$this->name = preg_replace("/[^a-zA-Z0-9_-]/",'',$this->name);
	}
}
?>
