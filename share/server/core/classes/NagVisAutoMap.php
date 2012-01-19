<?php
/*****************************************************************************
 *
 * NagVisAutoMap.php - Class for parsing the NagVis automap
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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

    private $options;
    private $preview;

    private $name;
    private $backend_id;
    private $root;
    private $childLayers;
    private $parentLayers;
    private $width;
    private $height;
    private $renderMode;
    private $ignoreHosts;
    private $filterGroup;
    private $filterByState;
    private $filterByIds = null;

    private $rootObject;
    private $arrMapObjects;
    private $arrHostnames;

    // Array to resolve circular child/parent connections.
    // A host is only parsed once
    private $arrHostnamesParsed = Array();

    // Array to store the connections between hosts. Each line
    // has a starting host and an end-host. The key is built
    // of the names of the both objects. The value is not used.
    private $arrLines = Array();

    private $mapCode;

    private $graphvizPath;
    private $noBinaryFound;

    public function __construct($CORE, $MAPCFG, $BACKEND, $prop, $bIsView = IS_VIEW) {
        $this->BACKEND = $BACKEND;

        $this->arrHostnames = Array();
        $this->arrMapObjects = Array();
        $this->mapCode = '';

        $this->graphvizPath = '';
        $this->noBinaryFound = false;

        parent::__construct($CORE, $MAPCFG);

        $this->name = $this->MAPCFG->getName();


        // Fetch option array from defaultparams string (extract variable
        // names and values)
        $params = explode('&', $this->MAPCFG->getValue(0, 'default_params'));
        unset($params[0]);

        foreach($params AS $set) {
            $arrSet = explode('=',$set);
            // Only load default option when the option has not been set before
            if(!isset($prop[$arrSet[0]]) || $prop[$arrSet[0]] == '') {
                $prop[$arrSet[0]] = $arrSet[1];
            }
        }

        // Set default preview option
        if(!isset($prop['preview']) || $prop['preview'] == '')
            $prop['preview'] = 0;

        $this->preview = $prop['preview'];

        // Do the preflight checks
        $this->checkPreflight();

        if(!isset($prop['backend']) || $prop['backend'] == '')
            $prop['backend'] = $this->MAPCFG->getValue(0, 'backend_id');

        $this->backend_id = $prop['backend'];

        /**
         * This is the name of the root host, user can set this via URL. If no
         * hostname is given NagVis tries to take configured host from main
         * configuration or read the host which has no parent from backend
         */
        if(!isset($prop['root']) || $prop['root'] == '')
            $prop['root'] = $this->getRootHostName();

        /**
         * This sets how many child layers should be displayed. Default value is -1,
         * this means no limitation.
         */
        if(!isset($prop['childLayers']) || $prop['childLayers'] == '')
            $prop['childLayers'] = -1;

        /**
         * This sets how many parent layers should be displayed. Default value is
         * -1, this means no limitation.
         */
        if(!isset($prop['parentLayers']) || $prop['parentLayers'] == '')
            $prop['parentLayers'] = 0;
        if(!isset($prop['renderMode']) || $prop['renderMode'] == '')
            $prop['renderMode'] = 'undirected';
        if(!isset($prop['width']) || $prop['width'] == '')
            $prop['width'] = 1024;
        if(!isset($prop['height']) || $prop['height'] == '')
            $prop['height'] = 786;
        if(!isset($prop['ignoreHosts']) || $prop['ignoreHosts'] == '')
            $prop['ignoreHosts'] = '';
        if(!isset($prop['filterGroup']) || $prop['filterGroup'] == '')
            $prop['filterGroup'] = '';
        if(!isset($prop['filterByState']) || $prop['filterByState'] == '0')
            $prop['filterByState'] = '';

        if(isset($prop['filterByIds']))
            $this->filterByIds = $prop['filterByIds'];

        // Store properties in object
        $this->options = $prop;

        $this->root = $prop['root'];
        $this->childLayers = $prop['childLayers'];
        $this->parentLayers = $prop['parentLayers'];
        $this->ignoreHosts = explode(',', $prop['ignoreHosts']);
        $this->renderMode = $prop['renderMode'];
        $this->width = $prop['width'];
        $this->height = $prop['height'];
        $this->filterGroup = $prop['filterGroup'];
        $this->filterByState = $prop['filterByState'];

        // Get "root" host object
        $this->fetchRootObject($this->root);

        // Get all child object information from backend
        $this->getChildObjectTree();

        // Get all parent object information from backend when needed
        if(isset($this->parentLayers) && $this->parentLayers != 0) {
            // If some parent layers are requested: It should be checked if the used
            // backend supports this
            if($this->BACKEND->checkBackendFeature($this->backend_id, 'getDirectParentNamesByHostName')) {
                $this->getParentObjectTree();
            }
        }

        /**
         * On automap updates not all objects are updated at once. Filter
         * the child tree by the given list of host object ids.
         */
        if($this->filterByIds) {
            $names = $this->MAPCFG->objIdsToNames($this->filterByIds);
            $this->rootObject->filterChilds($names);

            // Filter the parent object tree too when enabled
            if(isset($this->parentLayers) && $this->parentLayers != 0)
                $this->rootObject->filterParents($names);
        }

        /**
         * Here we have all objects within the given layers in the tree - completely unfiltered!
         * Optionally: Apply the filters below
         */

        /**
         * It is possible to filter the object tree by a hostgroup.
         * In this mode a list of hostnames in this group is fetched and the
         * parent/child trees are filtered using this list.
         *
         * Added later: It is possible that a host of the given group is behind a
         * host which is not in the group. These 'connector' hosts need to be added
         * too. Those hosts will be added by default but this can be disabled by
         * config option. This sort of hosts should be visualized in another way.
         */
        if($this->filterGroup != '') {
            $this->filterGroupObject = new NagVisHostgroup($this->CORE, $this->BACKEND, $this->backend_id, $this->filterGroup);
            $this->filterGroupObject->setConfiguration(Array('hover_menu' => 1, 'hover_childs_show' => 1));
            $this->filterGroupObject->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
            $this->BACKEND->execute();
            $this->filterGroupObject->applyState();

            $this->filterObjectTreeByGroup('childs');

            // Filter the parent object tree too when enabled
            if(isset($this->parentLayers) && $this->parentLayers != 0) {
                $this->filterObjectTreeByGroup('parents');
            }
        }

        $this->loadObjectConfigurations();

        // Create MAPOBJ object, form the object tree to map objects and get the
        // state of the objects
        $this->MAPOBJ = new NagVisMapObj($this->CORE, $this->BACKEND, $this->MAPCFG, $bIsView);
        $this->MAPOBJ->objectTreeToMapObjects($this->rootObject);

        $this->MAPOBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
        $this->BACKEND->execute();
        $this->MAPOBJ->applyState();

        if($this->filterByState != '') {
            $this->filterObjectTreeByState('childs');

            // Filter the parent object tree too when enabled
            if(isset($this->parentLayers) && $this->parentLayers != 0)
                $this->filterObjectTreeByState('parents');

            $this->MAPOBJ->clearMembers();
            $this->MAPOBJ->objectTreeToMapObjects($this->rootObject);
        }
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
        //$str .= 'bgcolor="'.$this->MAPCFG->getValue(0, 'background_color').'", ';
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
        $str .= $this->rootObject->parseGraphviz(0, $this->arrHostnamesParsed, $this->arrLines);

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
                    throw new NagVisException(l('unknownRenderMode', Array('MODE' => $this->renderMode)));
                break;
            }

            /**
             * The config can not be forwarded to graphviz binary by echo, this would
             * result in commands too long with big maps. So write the config to a file
             * and let it be read by graphviz binary.
             */
            $dotFile = cfg('paths', 'var').$this->name.'.dot';
            file_put_contents($dotFile, $this->parseGraphvizConfig());
            $this->CORE->setPerms($dotFile);

            // Parse map
            $cmd = $this->graphvizPath.$binary
                   .' -Tcmapx '.cfg('paths', 'var').$this->name.'.dot 2>&1';

            exec($cmd, $arrMapCode, $returnCode);

            if($returnCode !== 0)
                throw new NagVisException(l('Graphviz call failed ([CODE]): [OUTPUT]<br /><br >Command was: "[CMD]"',
                       Array('CODE' => $returnCode, 'OUTPUT' => implode("\n",$arrMapCode), 'CMD' => $cmd)));

            $this->mapCode = implode("\n", $arrMapCode);
        }
    }

    /**
     * PUBLIC createObjectConnectors()
     *
     * Creates line objects between the child/parent objects
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function createObjectConnectors() {
        $lines = Array();
        // FIXME: Handle line style!
        $aConf = Array(
            'view_type'  => 'line',
            'line_type'  => '11',
	    'z'          => 90,
            'line_color' => '#000',
            'line_width' => 1,
        );

        $objIds = $this->MAPCFG->loadObjIds();
        $newObjId = false;

        foreach(array_keys($this->arrLines) AS $lineKey) {
            list($start, $end) = explode('%%', $lineKey);

            $S = $this->arrMapObjects[$start];
            $E = $this->arrMapObjects[$end];


            // Get the image size
            list($sWidth, $sHeight, $sType, $sAttr) = $S->getIconDetails();
            list($eWidth, $eHeight, $eType, $eAttr) = $E->getIconDetails();

            if(!isset($objIds[$lineKey])) {
                $objIds[$lineKey] = $this->MAPCFG->genObjIdAutomap($lineKey);
                $newObjId = true;
            }

            $LINE = new NagVisLine($this->CORE);
            $LINE->setObjectId($objIds[$lineKey]);
            $LINE->setMapCoords(Array('x' => $objIds[$start] . "%+" . ($sWidth  / 2) . ',' . $objIds[$end] . "%+" . ($eWidth  / 2),
                                      'y' => $objIds[$start] . "%+" . ($sHeight / 2) . ',' . $objIds[$end] . "%+" . ($eHeight / 2)));
            $LINE->setConfiguration($aConf);
            $lines[] = $LINE;
        }

        $this->MAPOBJ->addMembers($lines);

        // When some new object ids have been added store them
        if($newObjId)
            $this->MAPCFG->storeObjIds($objIds);
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
         * <map id="automap" name="automap">
         * <area shape="rect" href="/nagios/cgi-bin/status.cgi?host=KASVMNAGIOSMA" target="_self" title="host_0" alt="" coords="664,13,679,28"/>
         * <area shape="rect" href="/nagios/cgi-bin/status.cgi?host=RZ1-024-1-143" target="_self" title="host_1" alt="" coords="664,119,679,135"/>
         *
         * Sometimes the dashes seem to be printed as html entities:
         * <area shape="rect" href="/nagios/cgi&#45;bin/status.cgi?host=RZ1&#45;006&#45;1&#45;130" target="_self" title="host_8" alt="" coords="464,272,470,278"/>
         *
         * In some cases there may be an ID:
         * <area shape="rect" id="node1" href="/nagios/cgi-bin/status.cgi?host=test_router_0" target="_self" title="host_0" alt="" coords="509,378,525,393"/>
         * <area shape="rect" id="node1" href="/icinga/cgi-bin/status.cgi?host=Icinga" target="_self" title="host_0" alt="" coords="698,24,713,40"/>
         *
         * It might happen that there are html entities used in the url
         * <area shape="rect" href="/check_mk/view.py?view_name=host&amp;site=&amp;host=localhost" target="_self" title="host_0" alt="" coords="4,4,20,20"/>
         * <area shape="rect" id="node1" href="/nv16/check_mk/view.py?view_name=host&amp;site=&amp;host=omd-nv16" title="host_013612" alt="" coords="5,5,27,27"/>
         *         
         * And there might also be a space after the coords parameter
         * <area shape="rect" href="/test1/check_mk/view.py?view_name=host&amp;site=&amp;host=test" target="_self" title="host_a94a8f" alt="" coords="5,5,21,21" />
         *
         * Aaaaand also handle centreon param host_name:
         * <area shape="rect" id="node1" href="/centreon/main.php?p=201&amp;o=hd&amp;host_name=localhost" target="_self" title="host_334389" alt="" coords="4,4,20,20"/>
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
            $sLine = str_replace('&#45;', '-', $sLine);
            // Extract the area objects
            // Only parsing rect/polys at the moment
            if(preg_match('/^<area\sshape="(rect|poly)"\s(id="[^"]+"\s)?href=".+[?&;](?:host|host_name)=([^"]+)"(?:\starget="[a-zA-Z-_]*")?\stitle="[^"]+"\salt=""\scoords="([^"]+)"\s?\/>$/i', $sLine, $aMatches)) {
                if(isset($aMatches[1]) && isset($aMatches[2]) && isset($aMatches[3]) && isset($aMatches[4])) {
                    $type = $aMatches[1];
                    $name1 = str_replace('&#45;', '-', trim($aMatches[3]));
                    $coords = trim($aMatches[4]);

                    switch($type) {
                        case 'rect':
                            $aCoords = explode(',', $coords);

                            // FIXME: z-index configurable?
                            // Header menu has z-index 100, this object's label the below+1
                            $aObjCoords[$name1] = Array('x' => $aCoords[0],
                                                        'y' => $aCoords[1],
                                                        'z' => 98);
                        break;
                        case 'poly':
                            // Get the middle of the polygon and substract the object size
                            // FIXME: This is not working at the moment.
                            $x = null;
                            $y = null;
                            $aCoords = explode(' ', $coords);
                            foreach($aCoords AS $coord) {
                                list($newX, $newY) = explode(',', $coord);
                                if($x === null) {
                                    $x = $newX;
                                    $y = $newY;
                                } else {
                                    $x = ($x + $newX) / 2;
                                    $y = ($y + $newY) / 2;
                                }
                            }
                            $x -= 16;
                            $y -= 16;

                            $aObjCoords[$name1] = Array('x' => $x,
                                                        'y' => $y,
                                                        'z' => 98);
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
     * Parses the objects in NagVis map cfg format for export to
   * regular maps
     *
     * @param   String  Name of the target map
     * @return	String  Json Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parseObjectsMapCfg($name) {
        $globalOpts = $this->MAPCFG->getObjectConfiguration();
        $validOpts = Array();
        foreach($this->MAPCFG->getValidTypeKeys('global') AS $key) {
            if(isset($globalOpts[$key])) {
                $validOpts[$key] = $globalOpts[$key];
            }
        }
        unset($validOpts['hover_timeout']);

        $ret = "define global {\n";
        foreach($validOpts AS $key => $val) {
            $ret .= '  '.$key.'='.$val."\n";
        }
        $ret .= "}\n\n";

        foreach($this->MAPOBJ->getMembers() AS $OBJ) {
            $ret .= $OBJ->parseMapCfg($validOpts);
        }

        return $ret;
    }

    /**
     * Creates a classic map from the current automap view
     *
     * @param   String   Name of the target map
     * @return  Boolean  Done?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function toClassicMap($name) {
        if(count($this->CORE->getAvailableMaps('/^'.$name.'.cfg$/')) > 0) {
            if(!$this->CORE->getAuthorization()->isPermitted('Map', 'edit', $name)) {
                throw new NagVisException(l('Unable to export the automap. A map with this name already exists.'));
            }
        }

        // Read position from graphviz and set it on the objects
        $this->setMapObjectPositions();
        $this->createObjectConnectors();

        // Store map configuration
        file_put_contents(cfg('paths', 'mapcfg').$name.'.cfg',
                      $this->parseObjectsMapCfg($name));

        return true;
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

        // And now load the stored object id (or get a new one)
        $objIds = $this->MAPCFG->loadObjIds();
        $newObjId = false;

        // Loop all map objects to load host individual configurations and the uniqe
        // object id
        foreach($this->arrMapObjects AS $OBJ) {
            $name = $OBJ->getName();

            if(!isset($objIds[$name])) {
                $objIds[$name] = $this->MAPCFG->genObjIdAutomap($name);
                $newObjId = true;
            }

            $OBJ->setObjectId($objIds[$name]);

            // Try to find a matching object in the map configuration
            if(isset($aConf[$name])) {
                unset($aConf[$name]['type']);
                unset($aConf[$name]['object_id']);
                unset($aConf[$name]['host_name']);
                $OBJ->setConfiguration($aConf[$name]);
            }
        }

        // When some new object ids have been added store them
        if($newObjId)
            $this->MAPCFG->storeObjIds($objIds);
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

        $this->CORE->checkVarFolderWriteable($printErr);

        // Check all possibly used binaries of graphviz
        if(!$this->checkGraphviz('dot', $printErr) &&
            !$this->checkGraphviz('neato', $printErr) &&
            !$this->checkGraphviz('twopi', $printErr) &&
            !$this->checkGraphviz('circo', $printErr) &&
            !$this->checkGraphviz('fdp', $printErr)) {
            $this->noBinaryFound = true;
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
         * configured path. Prefer the configured path.
         */
        $bFound = false;
        foreach(Array(cfg('automap','graphvizpath').$binary, $binary) AS $path) {
            // Check if dot can be found in path (If it is there $returnCode is 0, if not it is 1)
            exec('which '.$path.' 2>/dev/null', $arrReturn, $exitCode);

            if($exitCode == 0) {
                $this->graphvizPath = str_replace($binary, '', $arrReturn[0]);
                $bFound = true;
                break;
            }
        }

        if(!$bFound) {
            if($printErr)
                throw new NagVisException(l('graphvizBinaryNotFound', Array('NAME' => $binary,
                                        'PATHS' => $_SERVER['PATH'].':'.$this->CORE->getMainCfg()->getvalue('automap','graphvizpath'))));
            return false;
        } else
            return true;
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
     * PRIVATE getChildObjectTree()
     *
     * Get all child objects recursive
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function getChildObjectTree() {
        $this->rootObject->fetchChilds($this->childLayers,
                                       $this->MAPCFG->getObjectConfiguration(),
                                       $this->ignoreHosts,
                                       $this->arrHostnames,
                                       $this->arrMapObjects);
    }

    /**
     * PRIVATE getParentObjectTree()
     *
     * Get all parent objects recursive
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function getParentObjectTree() {
        $this->rootObject->fetchParents($this->parentLayers,
                                        $this->MAPCFG->getObjectConfiguration(),
                                        $this->ignoreHosts,
                                        $this->arrHostnames,
                                        $this->arrMapObjects);
    }

    /**
     * PRIVATE filterObjectTreeByState()
     *
     * Filter the given object tree by state. Only showing objects which
     * have some problem.
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function filterObjectTreeByState($direction) {
        $problemHosts = Array();
        $stateWeight  = $this->CORE->getMainCfg()->getStateWeight();

        foreach($this->arrMapObjects AS $OBJ) {
            if(isset($stateWeight[$OBJ->getSummaryState()])
               && $stateWeight[$OBJ->getSummaryState()][$OBJ->getSubState(SUMMARY_STATE)] > $stateWeight['UP']['normal']) {
                $problemHosts[] = $OBJ->getName();
            }
        }

        if($direction == 'childs')
            $this->rootObject->filterChilds($problemHosts);
        elseif($direction == 'parents')
            $this->rootObject->filterParents($problemHosts);
    }

    /**
     * PRIVATE filterObjectTreeByGroup()
     *
     * Filter the object tree using the given filter group
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function filterObjectTreeByGroup($direction) {
        $hostgroupMembers = Array();
        foreach($this->filterGroupObject->getMembers() AS $OBJ1) {
            $hostgroupMembers[] = $OBJ1->getName();
        }

        if($direction == 'childs')
            $this->rootObject->filterChilds($hostgroupMembers);
        elseif($direction == 'parents')
            $this->rootObject->filterParents($hostgroupMembers);
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
        $defaultRoot = cfg('automap','defaultroot', TRUE);
        if(!isset($defaultRoot) || $defaultRoot == '') {
            try {
                $hostsWithoutParent = $this->BACKEND->getBackend($this->backend_id)->getHostNamesWithNoParent();
            } catch(BackendConnectionProblem $e) {}

            if(isset($hostsWithoutParent) && count($hostsWithoutParent) == 1)
                $defaultRoot = $hostsWithoutParent[0];
        }

        if(!isset($defaultRoot) || $defaultRoot == '') {
            $defaultRoot = cfg('automap','defaultroot');
        }

        // Could not get root host for the automap
        if(!isset($defaultRoot) || $defaultRoot == '') {
            throw new NagVisException(l('couldNotGetRootHostname'));
        } else {
            return $defaultRoot;
        }
    }

    /**
     * Creates a host object by the host name
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function fetchRootObject($hostName) {
        $hostObject = new NagVisHost($this->CORE, $this->BACKEND, $this->backend_id, $hostName);
        $hostObject->setConfiguration($this->MAPCFG->getObjectConfiguration());
        $this->arrMapObjects[$hostName] = $hostObject;
        $this->rootObject = $hostObject;
    }

    /**
     * Returns an array of real applied options of this map
     *
     */
    public function getOptions() {
        return $this->options;
    }
}
?>
