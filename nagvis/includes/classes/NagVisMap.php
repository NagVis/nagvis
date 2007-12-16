<?php
/**
 * Class for printing the map in NagVis
 */
class NagVisMap extends GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	var $GRAPHIC;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisMap(&$MAINCFG,&$MAPCFG,&$LANG,&$BACKEND,$getState=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::NagVisMap($MAINCFG,$MAPCFG,$LANG,$BACKEND)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->LANG = &$LANG;
		$this->BACKEND = &$BACKEND;
		
		$this->GRAPHIC = new GlobalGraphic();
		
		parent::GlobalMap($MAINCFG,$MAPCFG);
		$this->MAPOBJ = new NagVisMapObj($this->MAINCFG, $this->BACKEND, $this->LANG, $this->MAPCFG);
		
		if($getState) {
			$this->MAPOBJ->fetchState();
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::NagVisMap()');
	}
	
	/**
	 * Parses the Map and the Objects
	 *
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseMap()');
		$ret = Array();
		$ret = array_merge($ret,$this->getBackground());
		$ret = array_merge($ret,$this->parseObjects());
		// Dynamicaly set favicon
		$ret[] = $this->getFavicon();
		// Change title (add map alias and map state)
		$ret[] = '<script type="text/javascript" language="JavaScript">document.title=\''.$this->MAPCFG->getValue('global', 0, 'alias').' ('.$this->MAPOBJ->getSummaryState().') :: \'+document.title;</script>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseMap(): Array(...)');
		return $ret;
	}
	
	/**
	 * Gets the background of the map
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackground() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getBackground()');
		
		if($this->MAPCFG->getValue('global', 0,'usegdlibs') == '1' && $this->checkGd(1)) {
			$src = './draw.php?map='.$this->MAPCFG->getName();
		} else {
			$src = $this->MAINCFG->getValue('paths', 'htmlmap').$this->MAPCFG->BACKGROUND->getFileName();
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getBackground(): Array(...)');
		return $this->getBackgroundHtml($src);
	}
	
	/**
	 * Gets the favicon of the page representation the state of the map
	 *
	 * @return	String	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getFavicon() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getFavicon()');
		if(file_exists('./images/internal/favicon_'.strtolower($this->MAPOBJ->getSummaryState()).'.png')) {
			$favicon = './images/internal/favicon_'.strtolower($this->MAPOBJ->getSummaryState()).'.png';
		} else {
			$favicon = './images/internal/favicon.png';
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getFavicon()');
		return '<script type="text/javascript" language="JavaScript">favicon.change(\''.$favicon.'\'); </script>';
	}
	
	/**
	 * Parses the Objects
	 *
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseObjects() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseObjects()');
		$ret = Array();
		foreach($this->MAPOBJ->getMapObjects() AS $OBJ) {
			switch(get_class($OBJ)) {
				case 'NagVisHost':
				case 'NagVisService':
				case 'NagVisHostgroup':
				case 'NagVisServicegroup':
				case 'NagVisMapObj':
				case 'NagVisShape':
				case 'NagVisTextbox':
					$ret[] = $OBJ->parse();
				break;
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::parseObjects(): Array(...)');
		return $ret;
	}
}
?>