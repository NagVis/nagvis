<?php

$automap_graphviz_path = '';

/**
 * Checks if a Graphviz binary can be found on the system
 */
function automap_check_graphviz($binary) {
    global $automap_graphviz_path;
    /**
     * Check if the graphviz binaries can be found in the PATH or in the
     * configured path. Prefer the configured path.
     */
    $bFound = false;
    foreach(Array(cfg('automap','graphvizpath').$binary, $binary) AS $path) {
        // Check if dot can be found in path (If it is there $returnCode is 0, if not it is 1)
        exec('which '.$path.' 2>/dev/null', $arrReturn, $exitCode);

        if($exitCode == 0) {
            $automap_graphviz_path = str_replace($binary, '', $arrReturn[0]);
            $bFound = true;
            break;
        }
    }

    if(!$bFound) {
        throw new NagVisException(l('graphvizBinaryNotFound', Array('NAME' => $binary,
                                    'PATHS' => $_SERVER['PATH'].':'.cfg('automap','graphvizpath'))));
    }

    return true;
}

function automap_pos_check_preflight($params) {
    GlobalCore::getInstance()->checkVarFolderWriteable(true);

    // Check all possibly used binaries of graphviz
    automap_check_graphviz('dot');
    automap_check_graphviz('neato');
    automap_check_graphviz('twopi');
    automap_check_graphviz('circo');
    automap_check_graphviz('fdp');
}

function graphviz_config_connector($from_id, $to_id) {
    return '    "'.$from_id.'" -- "'.$to_id.'" [ weight=2 ];'."\n";
}

function graphviz_config_tree(&$params, &$tree, $layer = 0) {
    $str = '';

    $name = $tree['host_name'];
    if (strlen($name) > 14) {
        $name = substr($name, 0, 12) . '...';
    }

    $str .= '    "'.$tree['object_id'].'" [ ';
    $str .= 'label="'.$name.'", ';
    $str .= 'URL="'.$tree['object_id'].'", ';
    $str .= 'tooltip="'.$tree['object_id'].'", ';
    
    $width  = $tree['.width'];
    $height = $tree['.height'];

    // This should be scaled by the choosen iconset
    if($width != 22) {
        $str .= 'width="'.graphviz_px2inch($width).'", ';
    }
    if($height != 22) {
        $str .= 'height="'.graphviz_px2inch($height).'", ';
    }

    // This is the root node
    if($layer == 0) {
        $str .= 'pos="'.graphviz_px2inch($params['width']/2).','.graphviz_px2inch($params['height']/2).'", ';
    }

    // The object has configured x/y coords. Use them.
    // FIXME: This does not work for some reason ...
    if(isset($tree['x']) && isset($tree['y'])) {
        $str .= 'pos="'.graphviz_px2inch($tree['x'] - $width / 2).','.graphviz_px2inch($tree['y'] - $height / 2).'", ';
        $str .= 'pin=true, ';
    }

    // The automap connector hosts could be smaller
    //if($this->automapConnector)
    //	$str .= 'height="'.$this->pxToInch($width/2).'", width="'.$this->pxToInch($width/2).'", ';

    $str .= 'layer="'.$layer.'"';
    $str .= ' ];'."\n";

    foreach($tree['.childs'] AS $child) {
        $str .= graphviz_config_tree($params, $child, $layer + 1);
        $str .= graphviz_config_connector($tree['object_id'], $child['object_id']);
    }

    foreach($tree['.parents'] AS $parent) {
        $str .= graphviz_config_tree($params, $parent, $layer + 1);
        $str .= graphviz_config_connector($tree['object_id'], $parent['object_id']);
    }

    return $str;
}

/**
 * Generates a graphviz configuration string from the object tree.
 * This string is later used to feed graphviz which will then generate
 * the coordinates for the automap.
 */
function graphviz_config(&$params, &$tree) {
    $str  = "graph automap {\n";
    //, ranksep="0.1", nodesep="0.4", ratio=auto, bb="0,0,500,500"
    $str .= '    graph [';
    $str .= 'dpi="72", ';
    $str .= 'margin='.graphviz_px2inch($params['margin']).', ';
    //$str .= 'bgcolor="'.$this->MAPCFG->getValue(0, 'background_color').'", ';
    $str .= 'root="'.$tree['object_id'].'", ';
    $str .= 'rankdir="'.$params['rankdir'].'", ';
    $str .= 'center=true, ';

    /* Directed (dot) only */
    if($params['render_mode'] == 'directed') {
        $str .= 'nodesep="0", ';
        //rankdir: LR,
        //$str .= 'rankdir="LR", ';
        //$str .= 'compound=true, ';
        //$str .= 'concentrate=true, ';
        //$str .= 'constraint=false, ';
    }

    /* Directed (dot) and radial (twopi) only */
    if($params['render_mode'] == 'directed' || $params['render_mode'] == 'radial') {
        $str .= 'ranksep="0.8", ';
    }

    /* All but directed (dot) */
    if($params['render_mode'] != 'directed') {
        //overlap: true,false,scale,scalexy,ortho,orthoxy,orthoyx,compress,ipsep,vpsc
        $str .= 'overlap="'.$params['overlap'].'", ';
    }

    //ratio: expand, auto, fill, compress
    //$str .= 'ratio="auto", ';
    // enforces the size of the drawing area to this value
    $str .= 'size="'.graphviz_px2inch($params['width']).','.graphviz_px2inch($params['height']).'!" ';
    $str .= "];\n";

    /**
     * Default settings for automap nodes
     */
    $str .= '    node [';
    $str .= 'shape="rect", ';
    // Show labels below the image
    $str .= 'labelloc="b", ';
    $str .= 'color="red", ';
    // needs to be included for correct rendering
    $str .= 'image="'.path('sys', 'global', 'icons').'std_medium_ok.png", ';

    // default margin is 0.11,0.055
    //$str .= 'margin="0.05,0.025", ';
    // This may be altered by the single objects depending on the icon size
    // -> must not be set, because it would make ignore the label text size for calculations
    //$str .= 'width="'.graphviz_px2inch(22).'", ';
    //$str .= 'height="'.graphviz_px2inch(22).'", ';
    // Do not use this as this would make the nodes ignore the label sizes
    //$str .= 'fixedsize="true", ';

    $str .= 'fontsize=10';
    $str .= '];'."\n";

    // Create nodes for all hosts
    $str .= graphviz_config_tree($params, $tree);

    $str .= "}\n";

    return $str;
}

/**
 * Renders the imagemap html code for the automap
 */
function graphviz_run($map_name, &$params, $cfg) {
    global $CORE, $automap_graphviz_path;
    /**
     * possible render modes are set by selecting the correct binary:
     *  dot - filter for drawing directed graphs
     *  neato - filter for drawing undirected graphs
     *  twopi - filter for radial layouts of graphs
     *  circo - filter for circular layout of graphs
     *  fdp - filter for drawing undirected graphs
     */
    switch($params['render_mode']) {
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
        case 'undirected3':
            $binary = 'sfdp';
        break;
        default:
            throw new NagVisException(l('Unknown render mode: [MODE]', Array('MODE' => $params['render_mode'])));
        break;
    }

    /**
     * The config can not be forwarded to graphviz binary by echo, this would
     * result in commands too long with big maps. So write the config to a file
     * and let it be read by graphviz binary.
     */
    $dotFile = cfg('paths', 'var').$map_name.'.dot';
    file_put_contents($dotFile, $cfg);
    $CORE->setPerms($dotFile);

    // Parse map
    $cmd = $automap_graphviz_path.$binary
           .' -Tcmapx '.cfg('paths', 'var').$map_name.'.dot 2>&1';

    exec($cmd, $arrMapCode, $returnCode);

    if($returnCode !== 0)
        throw new NagVisException(l('Graphviz call failed ([CODE]): [OUTPUT]<br /><br >Command was: "[CMD]"',
               Array('CODE' => $returnCode, 'OUTPUT' => implode("\n",$arrMapCode), 'CMD' => $cmd)));

    return implode("\n", $arrMapCode);
}

/**
 * Parses the imagemap code to extract the map object coordinates
 */
function graphviz_parse(&$map_config, $imagemap) {
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

    /**
     * This is the new format without annoying url/hostname parsing
     * <map id="automap" name="automap">
     * <area shape="rect" id="node1" href="160d8d" title="160d8d" alt="" coords="267,125,289,147"/>
     * <area shape="rect" id="node2" href="6a6c17" title="6a6c17" alt="" coords="328,76,350,98"/>
     * <area shape="rect" id="node4" href="42c509" title="42c509" alt="" coords="202,178,224,200"/>
     * <area shape="rect" id="node5" href="104b3c" title="104b3c" alt="" coords="125,137,147,159"/>
     * <area shape="rect" id="node13" href="b2811d" title="b2811d" alt="" coords="225,261,247,283"/>
     * <area shape="rect" id="node6" href="204369" title="204369" alt="" coords="132,55,154,77"/>
     * <area shape="rect" id="node8" href="863aae" title="863aae" alt="" coords="57,96,79,118"/>
     * <area shape="rect" id="node10" href="856cad" title="856cad" alt="" coords="55,180,77,202"/>
     * <area shape="rect" id="node14" href="7c1bd4" title="7c1bd4" alt="" coords="167,319,189,341"/>
     * <area shape="rect" id="node16" href="236387" title="236387" alt="" coords="306,274,328,296"/>
     * <area shape="rect" id="node18" href="a01eee" title="a01eee" alt="" coords="248,337,270,359"/>
     * </map>
     */

    // Extract the positions from the html area definitions
    $objCoords = Array();
    foreach(explode("\n", $imagemap) AS $sLine) {
        $sLine = str_replace('&#45;', '-', $sLine);
        // Extract the area objects
        // Only parsing rect/polys at the moment
        if(preg_match('/^<area\sshape="(rect|poly)"\s(?:id="[^"]+"\s)?href="([^"]+)"\stitle="[^"]+"\salt=""\scoords="([^"]+)"\s?\/>$/i', $sLine, $aMatches)) {
            if(isset($aMatches[1]) && isset($aMatches[2]) && isset($aMatches[2])) {
                $type      = trim($aMatches[1]);
                $object_id = trim($aMatches[2]);
                $coords    = trim($aMatches[3]);

                switch($type) {
                    case 'rect':
                        $aCoords = explode(',', $coords);
                        $map_config[$object_id]['x'] = (int) $aCoords[0];
                        $map_config[$object_id]['y'] = (int) $aCoords[1];
                    break;
                    case 'poly':
                        // Get the middle of the polygon and substract the object size
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

                        // Substract the object size
                        $x -= $map_config['.width'];
                        $y -= $map_config['.height'];

                        $map_config[$object_id]['x'] = (int) $x;
                        $map_config[$object_id]['y'] = (int) $y;
                    break;
                }
            }
        }
    }

    // Now apply the coords
    //foreach($map_config AS $object_id => $obj) {
    //    if(isset($aObjCoords[$getName()])) {
    //        $OBJ->setMapCoords($aObjCoords[$OBJ->getName()]);
    //    } else {
    //        $f = cfg('paths', 'var').$this->name.'.imagemap';
    //        file_put_contents($f, $this->mapCode);
    //        throw new NagVisException(l('Got no coordinates for the host "[H]". This might be a parsing issue. '
    //                                    .'Please report this problem to the NagVis team. Include the contents '
    //                                    .'of the file [F] in the bug report.', array('H' => $OBJ->getName(), 'F' => $f)));
    //    }
    //}
}

function process_automap_pos($MAPCFG, $map_name, &$map_config, &$tree, &$params) {
    automap_pos_check_preflight($params);

    $cfg      = graphviz_config($params, $tree);
    $imagemap = graphviz_run($map_name, $params, $cfg);
    graphviz_parse($map_config, $imagemap);
}

// Do the preflight checks
//automap_check_preflight($params);

/**
 * This methods converts pixels to inches. Assuming 72dpi
 */
function graphviz_px2inch($px) {
    return number_format($px / 72, 4, '.', '');
}

?>
