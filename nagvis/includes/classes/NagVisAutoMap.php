<?php
/**
 * Class for NagVis Automap generating
 */
class NagVisAutoMap extends GlobalMap {
	var $MAINCFG;
	var $LANG;
	var $BACKEND;
	
	var $backendId;
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
		
		if(isset($prop['backendId']) && $prop['backendId'] != '') {
			$this->backendId = $prop['backendId'];
		} else {
			$this->backendId = $this->MAINCFG->getValue('defaults', 'backend');
		}
		
		if(isset($prop['root']) && $prop['root'] != '') {
			$this->root = $prop['root'];
		}else {
			$this->root = $this->getRootHostName();
		}
		
		if(isset($prop['maxLayers']) && $prop['maxLayers'] != '') {
			$this->maxLayers = $prop['maxLayers'];
		} else {
			$this->maxLayers = 0;
		}
		
		if(isset($prop['renderMode']) && $prop['renderMode'] != '') {
			$this->renderMode = $prop['renderMode'];
		} else {
			$this->renderMode = 'tree';
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
		$this->rootObject = $this->getHostObjectByName($this->root);
		
		// Get all object informations from backend
		$this->getObjectTree($this->rootObject);
		
		// Get status for all objects
		$this->getObjectState($this->rootObject);
		
		// Get the icon for that objects
		$this->getObjectIcons($this->rootObject);
		
		parent::GlobalMap($MAINCFG,$MAPCFG);
	}
	
	function parseGraphvizConfig() {
		// FIXME
	}
	
	/**
	 * This method calcs the coords in NagVis, we consider to source this out to graphviz
	 * deprecated
	 */
	function getObjectCoords(&$parentObject, $currentLayer=0, $parentLayerWidth=0) {
		// Get number of direct childs
		$numChilds = $parentObject->getNumChilds();
		
		// This is the Root of all evil"
		if($currentLayer == 0) {
			switch($this->renderMode) {
				case 'circle':
					$parentObject->setMapCoords($this->getCenterKoords());
				break;
				case 'tree':
					$parentObject->setMapCoords($this->getTopCenterKoords());
					$parentLayerWidth = $this->width;
				break;
				case 'dirtree':
					$parentObject->setMapCoords($this->getTopLeftKoords());
					$parentLayerWidth = $this->height;
				break;
			}
		}
		
		$i = 1;
		foreach($parentObject->getChilds() AS $childObject) {
			switch($this->renderMode) {
				case 'circle':
					# Calculate Coords by number of childs and layer
					//360/$numChilds => wieviel Grad bekommt ein icon
					//nummerdesChilds*icongrad=anfang des bereichs
					//DickeEinesLayers*$layer => anfang des bereichs
					//DickeEinesLayers*($layer+1) => ende des bereichs
					//(Anfang - Ende) * 0.5 => mitte des bereichs => Position des Icons
					//$parentObject->getX()
					//$parentObject->getY()
					//$parentObject->getZ()
				break;
				case 'tree':
					# Calculate Coords by number of childs and layer
					//y=parent-pos + icon-height + spacer
					$y = $parentObject->getY() + 22 + 20;
					//map-width/numChilds => space-for-one-icon/2 * id-of-child => x-of icon
					//(nr*breite/anzahl)-(groesse*0.5)
					$x = ($i*$parentLayerWidth/$numChilds)-($parentLayerWidth/$numChilds*0.5);
					
					$childObject->setMapCoords(Array('x' => $x, 'y' => $y, 'z' => 0));
				break;
				case 'dirtree':
					# Calculate Coords by number of childs and layer
					//y=parent-pos + icon-height + spacer
					$x = $parentObject->getX() + 22 + 20;
					//map-width/numChilds => space-for-one-icon/2 * id-of-child => x-of icon
					//(nr*breite/anzahl)-(groesse*0.5)
					$y = $parentObject->getY()+(22*$i);
					
					$childObject->setMapCoords(Array('x' => $x, 'y' => $y, 'z' => 0));
				break;
			}
			
			$this->getObjectCoords($childObject,$currentLayer + 1, $parentLayerWidth / $numChilds);
			$i++;
		}
	}
	
	function parseMap() {
		$ret = Array();
		$ret = array_merge($ret,$this->parseObjects($this->rootObject));
		# Switch: $renderMode
			# Case: circle
				
			# End Case
			# Case: tree
				
			# End Case
		# End Switch
		return $ret;
	}
	
	/* FROM NagVisMap class
	function parseMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseMap()');
		$ret = Array();
		$ret[] = '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>';
		$ret[] = '<div class="map">';
		$ret = array_merge($ret,$this->getBackground());
		$ret = array_merge($ret,$this->parseObjects());
		// Dynamicaly set favicon
		$ret[] = $this->getFavicon();
		// Change title (add map alias and map state)
		$ret[] = '<script type="text/javascript" language="JavaScript">document.title=\''.$this->MAPCFG->getValue('global', 0, 'alias').' ('.$this->getMapState($this->getMapObjects(1,1)).') :: \'+document.title;</script>';
		$ret[] = '</div>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseMap(): Array(...)');
		return $ret;
	} */
	
	# END Public Methods
	# #####################################################
	
	function getObjectIcons(&$parentObject) {
		$parentObject->getIcon();
		
		foreach($parentObject->getChilds() AS $childObject) {
			$this->getObjectIcons($childObject);
		}
	}
	
	function getObjectState(&$parentObject) {
		$parentObject->getState();
		
		foreach($parentObject->getChilds() AS $childObject) {
			$this->getObjectState($childObject);
		}
	}
	
	function getBackground() {
		
	}
	
	function parseObjects(&$parentObject) {
		$ret = Array();
		$ret[] = $parentObject->parse();
		
		foreach($parentObject->getChilds() AS $childObject) {
			$ret = array_merge($ret,$this->parseObjects($childObject));
		}
	
		return $ret;
	}
	
	function getMapState() {
		
	}
	
	function getMapObjects() {
		
	}
	
	function wrapState() {
		
	}
	
	function getCenterKoords() {
		return Array('x'=> $this->width * 0.5, 'y'=> $this->height * 0.5, 'z'=> 0);
	}
	
	function getTopCenterKoords() {
		return Array('x'=> $this->width * 0.5, 'y'=> 100, 'z'=> 0);
	}
	function getTopLeftKoords() {
		return Array('x'=> 100, 'y'=> 100, 'z'=> 0);
	}
	
	function getRootHostName() {
		$defaultRoot = $this->MAINCFG->getValue('automap','default_root');
		if(isset($defaultRoot) && $defaultRoot != '') {
			return $defaultRoot;
		} else {
			# FIXME: Get from Backend
			if($this->BACKEND->checkBackendInitialized($this->backendId, TRUE)) {
				$hostsWithoutParent = $this->BACKEND->BACKENDS[$this->backendId]->getHostNamesWithNoParent();
				if(count($hostsWithoutParent) == 1) {
					return $hostsWithoutParent[0];
				} else {
					# ERROR: Could not get root host for automap
				}
			}
		}
	}
	
	function getObjectTree(&$parentObject, $currentLayer=0) {
		# Add all direct childs to the current parent object
		$parentObject->getDirectChildObjects();
		
		# Recursive until $maxLayersLeft=0 or the end of the Nagios world  || ($this->maxLayers == 0 && $parentObject->getNumChilds() == 0)
		if(($this->maxLayers != 0 && $this->maxLayers >= $currentLayer) || $this->maxLayers == 0) {
			# Loop all direct childs to fetch their childs, ... 
			foreach($parentObject->getChilds() AS $childObject) {
				$this->getObjectTree($childObject,$currentLayer + 1);
			}
		}
	}

	function getHostObjectByName($hostName) {
		$hostObject = new NagVisHost($this->MAINCFG, $this->BACKEND, $this->backendId, $hostName);
		return $hostObject;
	}
}
?>
