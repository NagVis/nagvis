<?php
/*****************************************************************************
 *
 * NagVisAutoMap.php - Class for parsing the NagVis automap
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
class NagVisAutoMap extends GlobalMap {
	public $MAPOBJ;
	private $BACKEND;
	
	private $preview;
	
	private $backend_id;
	private $root;
	private $maxLayers;
	private $width;
	private $height;
	private $renderMode;
	private $ignoreHosts;
	private $filterGroup;
	
	private $rootObject;
	private $arrMapObjects;
	private $arrHostnames;
	
	private $arrHostnamesParsed;
	
	private $mapCode;
	
	private $noBinaryFound;
	
	/**
	 * Automap constructor
	 *
	 * @param		MAINCFG		Object of NagVisMainCfg
	 * @param		LANG			Object of GlobalLanguage
	 * @param		BACKEND		Object of GlobalBackendMgmt
	 * @return	String 		Graphviz configuration
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $BACKEND, $prop) {
		$this->BACKEND = $BACKEND;
		
		$this->arrHostnames = Array();
		$this->arrMapObjects = Array();
		$this->arrHostnamesParsed = Array();
		$this->mapCode = '';
		
		$this->noBinaryFound = FALSE;
		
		// Create map configuration
		$MAPCFG = new NagVisMapCfg($CORE, '__automap');
		$MAPCFG->readMapConfig();
		
		parent::__construct($CORE, $MAPCFG);

		// Set the preview option
		if(isset($prop['preview']) && $prop['preview'] != '') {
			$this->preview = $prop['preview'];
		} else {
			$this->preview = 0;
		}
		
		// Do the preflight checks
		$this->checkPreflight();
		
		if(isset($prop['backend']) && $prop['backend'] != '') {
			$this->backend_id = $prop['backend'];
		} else {
			$this->backend_id = $this->CORE->MAINCFG->getValue('defaults', 'backend');
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
		 * This sets how many layers should be displayed. Default value is -1, 
		 * this means no limitation.
		 */
		if(isset($prop['maxLayers']) && $prop['maxLayers'] != '') {
			$this->maxLayers = $prop['maxLayers'];
		} else {
			$this->maxLayers = -1;
		}
		
		/**
		 * The renderMode can be set via URL, if none is given NagVis takes the "tree"
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
		
		if(isset($prop['ignoreHosts']) && $prop['ignoreHosts'] != '') {
			$this->ignoreHosts = explode(',', $prop['ignoreHosts']);
		} else {
			$this->ignoreHosts = Array();
		}
		
		if(isset($prop['filterGroup']) && $prop['filterGroup'] != '') {
			$this->filterGroup = $prop['filterGroup'];
		} else {
			$this->filterGroup = '';
		}
		
		// Get "root" host object
		$this->fetchHostObjectByName($this->root);
		
		// Get all object information from backend
		$this->getObjectTree();
		
		if($this->filterGroup != '') {
			$this->filterGroupObject = new NagiosHostgroup($this->CORE, $this->BACKEND, $this->backend_id, $this->filterGroup);
			$this->filterGroupObject->fetchMemberHostObjects();
			
			$this->filterObjectTreeByGroup();
		}
		
		// Create MAPOBJ object, form the object tree to map objects and get the
		// state of the objects
		$this->MAPOBJ = new NagVisMapObj($this->CORE, $this->BACKEND, $this->MAPCFG);
		$this->MAPOBJ->objectTreeToMapObjects($this->rootObject);
		$this->MAPOBJ->fetchState();
	}
	
	/**
	 * Parses the Map and the Objects
	 *
	 * @return	String 	String with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseMapJson() {
		$ret = '';
		$ret .= 'oGeneralProperties='.$this->CORE->MAINCFG->parseGeneralProperties().';'."\n";
		$ret .= 'oWorkerProperties='.$this->CORE->MAINCFG->parseWorkerProperties().';'."\n";
		
		// Kick of the worker
		$ret .= 'addDOMLoadEvent(function(){runWorker(0, \'automap\')});';
		
		return $ret;
	}
	
	/**
	 * Parses the Map and the Object options in json format
	 *
	 * @return	String 	String with JSON Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseMapPropertiesJson() {
		$arr = Array();
		$arr['map_name'] = $this->MAPCFG->getName();
		
		return json_encode($arr);
	}
	
	/**
	 * Parses the graphviz config of the automap
	 *
	 * @return	String 		Graphviz configuration
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function parseGraphvizConfig() {
		
		/**
		 * Graph definition
		 */
		$str  = 'graph automap { ';
		//, ranksep="0.1", nodesep="0.4", ratio=auto, bb="0,0,500,500"
		$str .= 'graph [';
		$str .= 'dpi="72", ';
		//ratio: expand, auto, fill, compress
		$str .= 'ratio="fill", ';
		$str .= 'root="'.$this->rootObject->getType().'_'.$this->rootObject->getName().'", ';
		
		/* Directed (dot) only */
		if($this->renderMode == 'directed') {
			$str .= 'nodesep="0", ';
			//rankdir: LR,
			//$str .= 'rankdir="LR", ';
			//$str .= 'compound=true, ';
			//$str .= 'concentrate=true, ';
			//$str .= 'constraint=false, ';
		}
		
		/* Directed (dot) and radial (twopi) only */
		if($this->renderMode == 'directed' || $this->renderMode == 'radial') {
			$str .= 'ranksep="0.8", ';
		}
		
		/* Only for circular (circo) mode */
		if($this->renderMode == 'circular') {
			//$str .= 'mindist="1.0", ';
		}
		
		/* All but directed (dot) */
		if($this->renderMode != 'directed') {
			//overlap: true,false,scale,scalexy,ortho,orthoxy,orthoyx,compress,ipsep,vpsc
			//$str .= 'overlap="ipsep", ';
		}
		
		$str .= 'size="'.$this->pxToInch($this->width).','.$this->pxToInch($this->height).'"]; '."\n";
		
		/**
		 * Default settings for automap nodes
		 */
		$str .= 'node [';
		// default margin is 0.11,0.055
		$str .= 'margin="0.0,0.0", ';
		$str .= 'ratio="auto", ';
		$str .= 'shape="none", ';
		$str .= 'fontcolor=black, fontsize=10';
		$str .= '];'."\n ";
		
		// Create nodes for all hosts
		$str .= $this->rootObject->parseGraphviz(0, $this->arrHostnamesParsed);
		
		$str .= '} ';
		
		return $str;
	}
	
	/**
	 * Renders the map image, saves it to var/ directory and creates the map and
	 * areas for the links
	 *
	 * @return	Array		HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function renderMap() {
		// This is only usable when this is preview mode (printErr = 0). This checks
		// if there is no binary on this system. When there is none, the map is not
		// being rendered
		if(!$this->noBinaryFound) {
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
					new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('unknownRenderMode','MODE~'.$this->renderMode));
				break;
			}
			
			/**
			 * The config can not be forwarded to graphviz binary by echo, this would
			 * result in commands too long with big maps. So write the config to a file
			 * and let it be read by graphviz binary.
			 */
			$fh = fopen($this->CORE->MAINCFG->getValue('paths', 'var').'automap.dot','w');
			fwrite($fh, $this->parseGraphvizConfig());
			fclose($fh);
			
			// Parse map
			exec($this->CORE->MAINCFG->getValue('automap','graphvizpath').$binary.' -Tpng -o \''.$this->CORE->MAINCFG->getValue('paths', 'var').'automap.png\' -Tcmapx '.$this->CORE->MAINCFG->getValue('paths', 'var').'automap.dot', $arrMapCode);
			
			$this->mapCode = implode("\n", $arrMapCode);
		}
	}
	
	/**
	 * Replaces some unwanted things from graphviz html code
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fixMapCode() {
		/**
		 * The hover menu can't be rendered in graphviz config. The information
		 * which is needed here is rendered like this title="<host_name>".
		 *
		 * The best idea I have for this: Extract the hostname and replace
		 * title="<hostname>" with the hover menu code.
		 */
		
		foreach($this->MAPOBJ->getMembers() AS $OBJ) {
			$this->mapCode = str_replace('title="'.$OBJ->getType().'_'.$OBJ->getObjectId().'"', $OBJ->getHoverMenu(), $this->mapCode);
		}
	}
	
	/**
	 * Parses the Automap HTML code
	 *
	 * @return	String HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseMap() {
		$ret = '';
		
		// Render the map image and save it, also generate link coords etc
		$this->renderMap();
		
		// Fix the map code
		$this->fixMapCode();
		
		// Create HTML code for background image
		$ret = $this->getBackground();
		
		// Parse the map with its areas
		$ret .= $this->mapCode;
		
		// Dynamically set favicon
		$ret .= $this->getFavicon();
		
		// Change title (add map alias and map state)
		$ret .= '<script type="text/javascript">var htmlBase=\''.$this->CORE->MAINCFG->getValue('paths', 'htmlbase').'\'; var mapName=\''.$this->MAPCFG->getName().'\'; document.title=\''.$this->MAPCFG->getValue('global', 0, 'alias').' ('.$this->MAPOBJ->getSummaryState().') :: \'+document.title;</script>';
		
		return $ret;
	}
	
	/**
	 * Gets the background of the map
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getBackground() {
		// Append random number to prevent caching
		$src = $this->CORE->MAINCFG->getValue('paths', 'htmlvar').'automap.png?'.mt_rand(0,10000);
		
		return $this->getBackgroundHtml($src,'','usemap="#automap"');
	}
	
	# END Public Methods
	# #####################################################
	
	/**
	 * Do the preflight checks to ensure the automap can be drawn
	 */
	private function checkPreflight() {
		// If this is a preview for the index page do not print errors
		if($this->preview) {
			$printErr = 0;
		} else {
			$printErr = 1;
		}
		
		// The GD-Libs are used by graphviz
    // Graphviz does not use the php5-gd which is checked by the
    // following call
		//$this->CORE->checkGd($printErr);
		
		$this->CORE->checkVarFolderWriteable($printErr);
		
		// Check all possibly used binaries of graphviz
		if(!$this->checkGraphviz('dot', $printErr) &&
			!$this->checkGraphviz('neato', $printErr) &&
			!$this->checkGraphviz('twopi', $printErr) &&
			!$this->checkGraphviz('circo', $printErr) &&
			!$this->checkGraphviz('fdp', $printErr)) {
			$this->noBinaryFound = TRUE;
		}
	}
	
	/**
	 * Checks if the Graphviz binaries can be found on the system
	 *
	 * @param		String	Filename of the binary
	 * @param		Bool		Print error message?
	 * @return	String	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkGraphviz($binary, $printErr) {
		/**
		 * Check if the graphviz binaries can be found in the PATH or in the 
		 * configured path
		 */
		// Check if dot can be found in path (If it is there $returnCode is 0, if not it is 1)
		exec('which '.$binary, $arrReturn, $returnCode1);
		
		if(!$returnCode1) {
			$this->CORE->MAINCFG->setValue('automap','graphvizpath',str_replace($binary,'',$arrReturn[0]));
		}
		
		exec('which '.$this->CORE->MAINCFG->getValue('automap','graphvizpath').$binary, $arrReturn, $returnCode2);
		
		if(!$returnCode2) {
			$this->CORE->MAINCFG->setValue('automap','graphvizpath',str_replace($binary,'',$arrReturn[0]));
		}
		
		if($returnCode1 & $returnCode2) {
			if($printErr) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('graphvizBinaryNotFound','NAME~'.$binary.',PATHS~'.$_SERVER['PATH'].':'.$this->CORE->MAINCFG->getvalue('automap','graphvizpath')));
			}
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	/**
	 * Gets the favicon of the page representation the state of the map
	 *
	 * @return	String	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getFavicon() {
		if(file_exists($this->CORE->MAINCFG->getValue('paths','htmlbase').'/nagvis/images/internal/favicon_'.strtolower($this->MAPOBJ->getSummaryState()).'.png')) {
			$favicon = $this->CORE->MAINCFG->getValue('paths','htmlbase').'/nagvis/images/internal/favicon_'.strtolower($this->MAPOBJ->getSummaryState()).'.png';
		} else {
			$favicon = $this->CORE->MAINCFG->getValue('paths','htmlbase').'/nagvis/images/internal/favicon.png';
		}
		return '<script type="text/javascript">favicon.change(\''.$favicon.'\'); </script>';
	}
	
	/**
	 * This methods converts pixels to inches
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function pxToInch($px) {
		return number_format($px/72, 4, '.','');
	}
	
	/**
	 * Get all child objects
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getObjectTree() {
		$this->rootObject->fetchChilds($this->maxLayers, $this->getObjectConfiguration(), $this->ignoreHosts, $this->arrHostnames, $this->arrMapObjects);
	}
	
	/**
	 * Filter the object tree using the given filter group
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function filterObjectTreeByGroup() {
		$hostgroupMembers = Array();
		foreach($this->filterGroupObject->getMembers() AS $OBJ1) {
			$hostgroupMembers[] = $OBJ1->getName();
		}
		
		$this->rootObject->filterChilds($hostgroupMembers);
	}
	
	/**
	 * Gets the configuration of the objects using the global configuration
	 *
	 * @return	Array		Object configuration
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function &getObjectConfiguration() {
		$objConf = Array();
		
		// Get object configuration from __automap configuration
		foreach($this->MAPCFG->getValidTypeKeys('host') AS $key) {
			if($key != 'type' && $key != 'backend_id' && $key != 'host_name' & $key != 'object_id') {
				$objConf[$key] = $this->MAPCFG->getValue('global', 0, $key);
			}
		}
		
		return $objConf;
	}
	
	/**
	 * Get root host object by NagVis configuration or by backend.
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getRootHostName() {
		/**
		 * NagVis tries to take configured host from main
		 * configuration or read the host which has no parent from backend
		 * when the root cannot be fetched via backend it reads the default
		 * value for the defaultroot
		 */
		$defaultRoot = $this->CORE->MAINCFG->getValue('automap','defaultroot', TRUE);
		if(!isset($defaultRoot) || $defaultRoot == '') {
			if($this->BACKEND->checkBackendInitialized($this->backend_id, TRUE)) {
				$hostsWithoutParent = $this->BACKEND->BACKENDS[$this->backend_id]->getHostNamesWithNoParent();
				if(count($hostsWithoutParent) == 1) {
					$defaultRoot = $hostsWithoutParent[0];
				}
			}
		}
		
		if(!isset($defaultRoot) || $defaultRoot == '') {
			$defaultRoot = $this->CORE->MAINCFG->getValue('automap','defaultroot');
		}
		
		// Could not get root host for the automap
		if(!isset($defaultRoot) || $defaultRoot == '') {
			new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('couldNotGetRootHostname'));
		} else {
			return $defaultRoot;
		}
	}
	
	/**
	 * Creates a host object by the host name
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchHostObjectByName($hostName) {
		$hostObject = new NagVisHost($this->CORE, $this->BACKEND, $this->backend_id, $hostName);
		$hostObject->fetchMembers();
		$hostObject->setConfiguration($this->getObjectConfiguration());
		$this->rootObject = $hostObject;
	}
}
?>
