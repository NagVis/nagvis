<?php
/**
 * Class for NagVis Automap generating
 */
class NagVisAutoMap extends GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	var $BACKEND;
	
	var $backend_id;
	var $root;
	var $maxLayers;
	var $rootObject;
	var $width;
	var $height;
	var $renderMode;

	function NagVisAutoMap(&$MAINCFG, &$LANG, &$BACKEND, $prop) {
		$this->MAINCFG = &$MAINCFG;
		$this->LANG = &$LANG;
		$this->BACKEND = &$BACKEND;
		
		// Create map configuration
		$this->MAPCFG = new NagVisMapCfg($this->MAINCFG, '__automap');
		
		// Do the preflight checks
		$this->checkPreflight();
		
		if(isset($prop['backend']) && $prop['backend'] != '') {
			$this->backend_id = $prop['backend'];
		} else {
			$this->backend_id = $this->MAINCFG->getValue('defaults', 'backend');
		}
		
		/**
		 * This is the name of the root host, user can set this via URL. If no
		 * hostname is given NagVis tries to take configured host from main
		 * configuration or read the host which has no parent from backend
		 */
		if(isset($prop['root']) && $prop['root'] != '') {
			$this->root = $prop['root'];
		}else {
			$this->root = $this->getRootHostName();
		}
		
		/**
		 * This sets how much layers should be displayed. Default value is -1, 
		 * this means no limitation.
		 */
		if(isset($prop['maxLayers']) && $prop['maxLayers'] != '') {
			$this->maxLayers = $prop['maxLayers'];
		} else {
			$this->maxLayers = -1;
		}
		
		/**
		 * The renderMode can be set via URL, if no is given NagVis takes the "tree"
		 * mode
		 */
		if(isset($prop['renderMode']) && $prop['renderMode'] != '') {
			$this->renderMode = $prop['renderMode'];
		} else {
			$this->renderMode = 'undirected';
		}
		
		if(isset($prop['width']) && $prop['width'] != '') {
			$this->width = $prop['width'];
		} else {
			$this->width = 1024;
		}
		
		if(isset($prop['height']) && $prop['height'] != '') {
			$this->height = $prop['height'];
		} else {
			$this->height = 786;
		}
		
		// Get "root" host object
		$this->fetchHostObjectByName($this->root);
		
		// Get all object informations from backend
		$this->getObjectTree();
		
		// Write virtual map configuration depending on the Nagios hosts
		$this->MAPCFG->mapConfig = $this->getMapConfig();
		
		parent::GlobalMap($this->MAINCFG, $this->MAPCFG);
		
		$this->MAPOBJ = new NagVisMapObj($this->MAINCFG, $this->BACKEND, $this->LANG, $this->MAPCFG);
		$this->MAPOBJ->fetchState();
	}
	
	function parseGraphvizConfig() {
		// FIXME
		$str  = 'graph automap { ';
		//, ranksep="0.1", nodesep="0.4", ratio=auto, bb="0,0,500,500"
		$str .= 'graph [ratio="fill", size="'.$this->pxToInch($this->width).','.$this->pxToInch($this->height).'"]; ';
		//$str .= '{ graph [rank=same, bb=""]; ';
		
		// Create nodes for all hosts
		$str .= $this->rootObject->parseGraphviz();
		
		//$str .= '} ';
		$str .= '} ';
		
		//DEBUG: echo $str;
		
		return $str;
	}
	
	function renderMap() {
		/**
		 * possible render modes are set by selecting the correct binary:
		 *  dot - filter for drawing directed graphs
     *  neato - filter for drawing undirected graphs
     *  twopi - filter for radial layouts of graphs
     *  circo - filter for circular layout of graphs
     *  fdp - filter for drawing undirected graphs
		 */
		switch($this->renderMode) {
			case 'directed':
				$binary = 'dot';
			break;
			case 'undirected':
				$binary = 'neato';
			break;
			case 'radial':
				$binary = 'twopi';
			break;
			case 'circular':
				$binary = 'circo';
			break;
			case 'undirected2':
				$binary = 'fdp';
			break;
			default:
				//FIXME: Error handling
			break;
		}
		//system('echo \''.$this->parseGraphvizConfig().'\' | '.$binary.' -Tpng > '.$this->MAINCFG->getValue('paths', 'var').'automap.png');
		//system('echo \''.$this->parseGraphvizConfig().'\' | '.$binary.' -Tsvg | sed "s/'.str_replace('/','\/',$this->MAINCFG->getValue('paths', 'icon')).'/'.str_replace('/','\/',$this->MAINCFG->getValue('paths', 'htmlicon')).'/g" > '.$this->MAINCFG->getValue('paths', 'var').'automap.svg');
		//| sed "s/'.str_replace('/','\/',$this->MAINCFG->getValue('paths', 'icon')).'/'.str_replace('/','\/',$this->MAINCFG->getValue('paths', 'htmlicon')).'/g" > '.$this->MAINCFG->getValue('paths', 'var').'automap.cmapx');
		exec('echo \''.$this->parseGraphvizConfig().'\' | '.$binary.' -Tpng -o \''.$this->MAINCFG->getValue('paths', 'var').'automap.png\' -Tcmapx',$arrMapCode);
		
		return implode("\n", $arrMapCode);
	}
	
	function fixMapCode($arrMapCode) {
		/**
		 * The map coords are absolute but they have to be relative to div element 
		 * with the id "map".
		 *
		 * The cords are given in the following format:
		 * coords="738,61,804,79"
		 * coords="x1,y1,x2,y2"
		 *
		 * The x coords have to be reduced by the height of the header menu.
		 * The height of the header menu can be read via javascript at runtime: 
		 *
		 * document.getElementById('map').offsetTop
		 *
		 * This is not useable cause the coords of the areas have to be changed 
		 * before rendering.
		 *
		 */
		
		return $arrMapCode;
	}
	
	function parseMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseMap()');
		$ret = Array();
		
		// Render the map image and save it, also generate link coords etc
		$mapObjects = $this->renderMap();
		
		// Fix the map code
		$mapObjects = $this->fixMapCode($mapObjects);
		
		// Create HTML code for background image
		$ret = array_merge($ret,$this->getBackground());
		
		// Parse the map with its areas
		$ret[] = $mapObjects;
		
		// Create hover areas for map objects
		$ret[] = $this->getObjects();
		
		// Dynamicaly set favicon
		$ret[] = $this->getFavicon();
		
		// Change title (add map alias and map state)
		// FIXME: This doesn't work here
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisAutoMap::getBackground()');
		
		$src = $this->MAINCFG->getValue('paths', 'htmlvar').'automap.png';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisAutoMap::getBackground(): Array(...)');
		return $this->getBackgroundHtml($src,'','usemap="automap"');
	}
	
	# END Public Methods
	# #####################################################
	
	/**
	 * Do the preflight checks to ensure the automap can be drawn
	 */
	function checkPreflight() {
		$this->checkGd(1);
		$this->checkGraphviz(1);
	}
	
	/**
	 * Checks if the Graphviz binaries can be found on the system
	 *
	 * @return	String	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkGraphviz($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisAutoMap::checkGraphviz('.$printErr.')');
		/* FIXME:
		 * Check if the carphviz binaries can be found in the PATH or in the 
		 * configured path
		 */
		if(TRUE) {
			if($printErr) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('WARNING','gdLibNotFound');
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisAutoMap::checkGraphviz(): FALSE');
			return FALSE;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisAutoMap::checkGraphviz(): TRUE');
			return TRUE;
		}
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
	 * This methods converts pixels to inches
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function pxToInch($px) {
		return round($px/72, 4);
	}
	
	/**
	 * Get all child objects
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getObjectTree() {
		$this->rootObject->fetchChilds($this->maxLayers);
	}
	
	/**
	 * Get root host object by NagVis configuration or by backend.
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getRootHostName() {
		$defaultRoot = $this->MAINCFG->getValue('automap','default_root');
		if(isset($defaultRoot) && $defaultRoot != '') {
			return $defaultRoot;
		} else {
			if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
				$hostsWithoutParent = $this->BACKEND->BACKENDS[$this->backend_id]->getHostNamesWithNoParent();
				if(count($hostsWithoutParent) == 1) {
					return $hostsWithoutParent[0];
				} else {
					//FIXME: ERROR-Handling: Could not get root host for automap
				}
			}
		}
	}
	
	/**
	 * Creates a hos object by the host name
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchHostObjectByName($hostName) {
		$hostObject = new NagVisHost($this->MAINCFG, $this->BACKEND, $this->LANG, $this->backend_id, $hostName);
		$hostObject->fetchState();
		$hostObject->fetchIcon();
		$this->rootObject = $hostObject;
	}
}
?>
