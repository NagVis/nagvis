<?php
/**
 * Class for printing the map
 * Should be used by ALL pages of NagVis and NagVisWui
 */
class GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	
	var $linkedMaps = Array();
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalMap(&$MAINCFG,&$MAPCFG) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::GlobalMap($MAINCFG,$MAPCFG)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::GlobalMap()');
	}
	
	/**
	 * Check if GD-Libs installed, when GD-Libs are enabled
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkGd($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::checkGd('.$printErr.')');
		if($this->MAPCFG->getValue('global', 0, 'usegdlibs') == '1') {
			if(!extension_loaded('gd')) {
				if($printErr) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
					$FRONTEND->messageToUser('WARNING','gdLibNotFound');
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkGd(): FALSE');
				return FALSE;
			} else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkGd(): TRUE');
				return TRUE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkGd(): TRUE');
			return TRUE;
		}
	}
	
	/**
	 * Gets the background html code of the map
	 *
	 * @param	String	$src	html path
	 * @param	String	$style  css parameters
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackgroundHtml($src, $style='', $attr='') {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getBackgroundHtml('.$src.','.$style.')');
		if (DEBUG&&DEBUGLEVEL&1) debug('Stop method GlobalMap::getBackgroundHtml(HTML)');
		return Array('<img id="background" src="'.$src.'" style="z-index:0;'.$style.'" alt="" '.$attr.'>');
	}
}
?>
