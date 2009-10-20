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
	
	private $name;
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
	public function __construct($CORE, $MAPCFG, $BACKEND, $prop) {
		$this->BACKEND = $BACKEND;
		
		$this->arrHostnames = Array();
		$this->arrMapObjects = Array();
		$this->arrHostnamesParsed = Array();
		$this->mapCode = '';
		
		$this->noBinaryFound = FALSE;
		
		parent::__construct($CORE, $MAPCFG);

		$this->name = $this->MAPCFG->getName();

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
		$this->getChildObjectTree();
		
		if($this->filterGroup != '') {
			$this->filterGroupObject = new NagiosHostgroup($this->CORE, $this->BACKEND, $this->backend_id, $this->filterGroup);
			$this->filterGroupObject->fetchMemberHostObjects();
			
			$this->filterChildObjectTreeByGroup();
		}
		
		$this->loadObjectConfigurations();
		
		// Create MAPOBJ object, form the object tree to map objects and get the
		// state of the objects
		$this->MAPOBJ = new NagVisMapObj($this->CORE, $this->BACKEND, $this->MAPCFG);
		$this->MAPOBJ->objectTreeToMapObjects($this->rootObject);
		$this->MAPOBJ->fetchState();
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
		$arr['alias'] = $this->MAPCFG->getValue('global', 0, 'alias');
		$arr['background_image'] = $this->getBackgroundJson();
		$arr['background_usemap'] = '#automap';
		$arr['background_color'] = $this->MAPCFG->getValue('global', 0, 'background_color');
		$arr['favicon_image'] = $this->getFavicon();
		$arr['page_title'] = $this->MAPCFG->getValue('global', 0, 'alias').' ('.$this->MAPOBJ->getSummaryState().') :: '.$this->CORE->MAINCFG->getValue('internal', 'title');
		$arr['event_background'] = $this->MAPCFG->getValue('global', 0, 'event_background');
		$arr['event_highlight'] = $this->MAPCFG->getValue('global', 0, 'event_highlight');
		$arr['event_highlight_interval'] = $this->MAPCFG->getValue('global', 0, 'event_highlight_interval');
		$arr['event_highlight_duration'] = $this->MAPCFG->getValue('global', 0, 'event_highlight_duration');
		$arr['event_log'] = $this->MAPCFG->getValue('global', 0, 'event_log');
		$arr['event_log_level'] = $this->MAPCFG->getValue('global', 0, 'event_log_level');
		$arr['event_log_height'] = $this->MAPCFG->getValue('global', 0, 'event_log_height');
		$arr['event_log_hidden'] = $this->MAPCFG->getValue('global', 0, 'event_log_hidden');
		$arr['event_scroll'] = $this->MAPCFG->getValue('global', 0, 'event_scroll');
		$arr['event_sound'] = $this->MAPCFG->getValue('global', 0, 'event_sound');
		
		return json_encode($arr);
	}
	
	/**
	 * Gets the path to the background of the map
	 *
	 * @return	String  Javascript code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getBackgroundJson() {
		return $this->CORE->MAINCFG->getValue('paths', 'htmlsharedvar').$this->name.'.png?'.mt_rand(0,10000);
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
		$str .= 'root="'.$this->rootObject->getType().'_'.$this->rootObject->getObjectId().'", ';
		
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
		$str .= 'shape="rect", ';
		$str .= 'color="white", ';
		// This may be altered by the single objects depending on the icon size
		$str .= 'width="'.$this->pxToInch(16).'", ';
		$str .= 'height="'.$this->pxToInch(16).'", ';
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
					new GlobalMessage('ERROR', $this->CORE->LANG->getText('unknownRenderMode','MODE~'.$this->renderMode));
				break;
			}
			
			/**
			 * The config can not be forwarded to graphviz binary by echo, this would
			 * result in commands too long with big maps. So write the config to a file
			 * and let it be read by graphviz binary.
			 */
			$fh = fopen($this->CORE->MAINCFG->getValue('paths', 'var').$this->name.'.dot','w');
			fwrite($fh, $this->parseGraphvizConfig());
			fclose($fh);
			
			// Parse map
			exec($this->CORE->MAINCFG->getValue('automap','graphvizpath').$binary.' -Tpng -o \''.$this->CORE->MAINCFG->getValue('paths', 'sharedvar').$this->name.'.png\' -Tcmapx '.$this->CORE->MAINCFG->getValue('paths', 'var').$this->name.'.dot', $arrMapCode);
			
			$this->mapCode = implode("\n", $arrMapCode);
		}
	}
	
	/**
	 * PUBLIC setMapObjectPositions()
	 *
	 * Reads the rendered positions from the map code and
	 * sets it to the map objects
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setMapObjectPositions() {
		// Render the map image and save it, also generate link coords etc. 
		// when not done yet
		if($this->mapCode == '') {
			$this->renderMap();
		}
		
		/*
		 * Sample code to parse:
		 * <map id="automap" name="automap">
		 * <area shape="poly" href="/nagios/cgi-bin/status.cgi?host=dev.nagvis.org" target="_self" title="host_662" alt="" coords="425,394 443,392 458,388 468,381 471,373 469,364 463,356 454,348 442,342 430,339 418,338 405,340 393,345 383,352 375,360 370,368 371,377 378,384 390,390 407,394"/>
		 * <area shape="rect" href="/nagios/cgi-bin/status.cgi?host=exchange.nagvis.org" target="_self" title="host_11" alt="" coords="742,294,834,334"/>
		 * <area shape="rect" href="/nagios/cgi-bin/status.cgi?host=www.nagvis.com" target="_self" title="host_184" alt="" coords="249,667,325,707"/>
		 * <area shape="rect" href="/nagios/cgi-bin/status.cgi?host=www.nagvis.org" target="_self" title="host_231" alt="" coords="151,78,225,118"/>
		 * </map>
		 *
		 * Coord description:
		 * For a rectangle, you map the top left and bottom right corners. All 
		 * coordinates are listed as x,y (over,up). So, for upper left corner 
		 * 0,0 and lower right corner 10,15 you would type: 0,0,10,15.
		 *
		 */
		
		// Extract the positions from the html area definitions
		$aMapCode = explode("\n", $this->mapCode);
		$aObjCoords = Array();
		foreach($aMapCode AS $sLine) {
			// Extract the area objects
			// Only parsing rect/polys at the moment
			if(preg_match('/^<area\sshape="(rect|poly)"\shref="\/nagios\/cgi-bin\/status\.cgi\?host=([^"]+)"\starget="_self"\stitle="[^"]+"\salt=""\scoords="([^"]+)"\/>$/i', $sLine, $aMatches)) {
				if(isset($aMatches[1]) && isset($aMatches[2]) && isset($aMatches[3])) {
					$type = $aMatches[1];
					$name1 = trim($aMatches[2]);
					$coords = trim($aMatches[3]);
					
					switch($type) {
						case 'rect':
							$aCoords = explode(',', $coords);
							
							// FIXME: z-index configurable?
							$aObjCoords[$name1] = Array('x' => $aCoords[0], 'y' => $aCoords[1], 'z' => 101);
						break;
						case 'poly':
							//$aCoords = explode(',', $coords);
						
							//$aObjCoords[$name1] = Array('x' => $aCoords[0], 'y' => $aCoords[1], 'z' => 101);
						break;
					}
				}
			}
		}
		
		// Now apply the coords
		foreach($this->MAPOBJ->getMembers() AS $OBJ) {
			if(isset($aObjCoords[$OBJ->getName()])) {
				$OBJ->setMapCoords($aObjCoords[$OBJ->getName()]);
			}
		}
	}
	
	/**
	 * Parses the Automap HTML code
	 *
	 * @return	String HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseMap() {
		// Render the map image and save it, also generate link coords etc
		$this->renderMap();
		
		return $this->mapCode;
	}
	
	/**
	 * Parses the Objects
	 *
	 * @return	String  Json Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseObjectsJson() {
		$arrRet = Array();
		
		$i = 0;
		foreach($this->MAPOBJ->getMembers() AS $OBJ) {
			switch(get_class($OBJ)) {
				case 'NagVisHost':
				case 'NagVisService':
				case 'NagVisHostgroup':
				case 'NagVisServicegroup':
				case 'NagVisMapObj':
				case 'NagVisShape':
				case 'NagVisTextbox':
					$arrRet[] = $OBJ->parseJson();
				break;
			}
			$i++;
		}
		
		return json_encode($arrRet);
	}
	
	# END Public Methods
	# #####################################################
	
	private function loadObjectConfigurations() {
		// Load the hosts from mapcfg into the aConf array
		$aConf = Array();
		$aHosts = $this->MAPCFG->getDefinitions('host');
		foreach($aHosts AS $aHost) {
			$aConf[$aHost['host_name']] = $aHost;
		}
		
		// #22 FIXME: Need to add some code here to make "sub automap" links possible
		// If a host matching the current hostname ist available in the automap configuration
		// load all settings here
		
		// Loop all map object
		foreach($this->arrMapObjects AS $OBJ) {
			// Try to find a matching object in the map configuration
			if(isset($aConf[$OBJ->getName()])) {
				unset($aConf[$OBJ->getName()]['type']);
				unset($aConf[$OBJ->getName()]['object_id']);
				unset($aConf[$OBJ->getName()]['host_name']);
				$OBJ->setConfiguration($aConf[$OBJ->getName()]);
			}
		}
	}
	
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
				new GlobalMessage('ERROR', $this->CORE->LANG->getText('graphvizBinaryNotFound','NAME~'.$binary.',PATHS~'.$_SERVER['PATH'].':'.$this->CORE->MAINCFG->getvalue('automap','graphvizpath')));
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
		if($this->MAPOBJ->getSummaryInDowntime()) {
			$favicon = 'downtime';
		} elseif($this->MAPOBJ->getSummaryAcknowledgement()) {
			$favicon = 'ack';
		} else {
			$favicon = strtolower($this->MAPOBJ->getSummaryState());
		}
		
		if(file_exists($this->CORE->MAINCFG->getValue('paths', 'images').'internal/favicon_'.$favicon.'.png')) {
			$favicon = $this->CORE->MAINCFG->getValue('paths', 'htmlimages').'internal/favicon_'.$favicon.'.png';
		} else {
			$favicon = $this->CORE->MAINCFG->getValue('paths', 'htmlimages').'internal/favicon.png';
		}
		return $favicon;
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
	private function getChildObjectTree() {
		$this->rootObject->fetchChilds($this->maxLayers, $this->MAPCFG->getObjectConfiguration(), $this->ignoreHosts, $this->arrHostnames, $this->arrMapObjects);
	}
	
	/**
	 * Filter the object tree using the given filter group
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function filterChildObjectTreeByGroup() {
		$hostgroupMembers = Array();
		foreach($this->filterGroupObject->getMembers() AS $OBJ1) {
			$hostgroupMembers[] = $OBJ1->getName();
		}
		
		$this->rootObject->filterChilds($hostgroupMembers);
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
			new GlobalMessage('ERROR', $this->CORE->LANG->getText('couldNotGetRootHostname'));
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
		$hostObject->setConfiguration($this->MAPCFG->getObjectConfiguration());
		$hostObject->setObjectId(0);
		$this->rootObject = $hostObject;
	}
}
?>
