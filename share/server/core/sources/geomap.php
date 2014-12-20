<?php

class GeomapError extends MapSourceError {}

//
// CSV source file handling
//

function geomap_source_file($p) {
    return cfg('paths', 'geomap') . '/' . $p['source_file'] . '.csv';
}

function geomap_read_csv($p) {
    $locations = array();
    $f = geomap_source_file($p);

    if($p['source_file'] == '')
        throw new GeomapError(l('No location source file given. Terminate rendering geomap.'));

    if(!file_exists($f))
        throw new GeomapError(l('Location source file "[F]" does not exist.', Array('F' => $f)));

    foreach(file($f) AS $line) {
        // skip lines beginning with any of the usual comment characters
        if(preg_match('/^[;#\/]/',$line))
            continue;
        $parts = explode(';', $line);
        $locations[] = array(
            'name'  => $parts[0],
            'alias' => $parts[1],
            'lat'   => (float) $parts[2],
            'long'  => (float) $parts[3],
        );
    }

    return $locations;
}

//
// Backend source handling
//

function geomap_backend_locations($p) {
    global $_BACKEND;
    $hosts = array();
    foreach ($p['backend_id'] AS $backend_id) {
        $_BACKEND->checkBackendExists($backend_id, true);
        $_BACKEND->checkBackendFeature($backend_id, 'getGeomapHosts', true);

        $hosts = array_merge($hosts, $_BACKEND->getBackend($backend_id)->getGeomapHosts($p['filter_group']));
    }
    return $hosts;
}

function geomap_backend_program_start($p) {
    global $_BACKEND;
    $t = null;
    foreach ($p['backend_id'] AS $backend_id) {
        $_BACKEND->checkBackendExists($backend_id, true);
        $_BACKEND->checkBackendFeature($backend_id, 'getProgramStart', true);

        $this_t = $_BACKEND->getBackend($backend_id)->getProgramStart();
        if ($t === null || $this_t > $t)
            $t = $this_t;
    }
    return $t;
}

//
// General source handling code
//

function geomap_get_locations($p) {
    switch($p['source_type']) {
        case 'csv':
            return geomap_read_csv($p);
        break;
        case 'backend':
            return geomap_backend_locations($p);
        break;
        default:
            throw new GeomapError(l('Unhandled source type "[S]"', Array('S' => $p['source_type'])));
        break;
    }
}

//function geomap_backend_cache_file($p) {
//    if(isset($p['filter_group']) && $p['filter_group'] != '') {
//        $fname = '-'.$p['filter_group'];
//    } else {
//        $fname = '';
//    }
//    return cfg('paths', 'var') . '/source-geomap-locations' . $fname . '.cache';
//}

function geomap_source_age($p) {
    switch($p['source_type']) {
        case 'csv':
    	    return filemtime(geomap_source_file($p));
        break;
        case 'backend':
            return geomap_backend_program_start($p);
        break;
        default:
            throw new GeomapError(l('Unhandled source type "[S]"', Array('S' => $p['source_type'])));
        break;
    }
}

function geomap_get_contents($url) {
    try {
        $opts = array(
            'http' => array(
                'timeout'    => cfg('global', 'http_timeout'),
                'user_agent' => 'NagVis '.CONST_VERSION.' geomap',
            )
        );

        $proxy = cfg('global', 'http_proxy');
        if($proxy != null) {
            $opts['http']['proxy'] = $proxy;
            $opts['http']['request_fulluri'] = true;
            $proxy_auth = cfg('global', 'http_proxy_auth');
            if($proxy_auth != null) {
                $opts['http']['header'] = 'Proxy-Authorization: Basic ' . base64_encode("$proxy_auth");
            }
        }
        
        $context = stream_context_create($opts);

        return file_get_contents($url, false, $context);
    } catch(Exception $e) {
        throw new GeomapError(l('Unable to fetch URL "[U]".<br/><br />The geomap needs to be able to fetch '
                                   .'some data from the internet via webservice API. Please take a look '
                                   .'at the docs for more details.<br /><br /><small>[E]</small>',
                                    Array('U' => $url, 'E' => $e->getMessage())));
    }
}

function list_geomap_types() {
    return array(
        'mapnik'     => 'Mapnik',
    );
}

function list_geomap_source_types() {
    return array(
        'csv'     => l('CSV-File'),
        'backend' => l('NagVis Backend'),
    );
}

function list_geomap_source_files() {
    global $CORE;
    return $CORE->getAvailableGeomapSourceFiles();
}

// Register this source as being selectable by the user
global $selectable;
$selectable = true;

// options to be modifiable by the user(url)
global $viewParams;
$viewParams = array(
    'geomap' => array(
        'backend_id',
        'geomap_type',
        'geomap_zoom',
        'geomap_border',
        'source_type',
        'source_file',
        'width',
        'height',
        'iconset',
        'label_show',
        'filter_group',
    )
);

// Config variables to be registered for this source
global $configVars;
$configVars = array(
    'geomap_type' => array(
        'must'       => false,
        'default'    => 'mapnik',
        'match'      => '/^(mapnik)$/i',
        'field_type' => 'dropdown',
        'list'       => 'list_geomap_types',
    ),
    'geomap_zoom' => Array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_INTEGER,
    ),
    'source_type' => array(
        'must'       => false,
        'default'    => 'csv',
        'match'      => MATCH_STRING,
        'field_type' => 'dropdown',
        'list'       => 'list_geomap_source_types',
    ),
    'source_file' => array(
        'must'          => false,
        'default'       => '',
        'match'         => MATCH_STRING_EMPTY,
        'field_type'    => 'dropdown',
        'list'          => 'list_geomap_source_files',
        'depends_on'    => 'source_type',
        'depends_value' => 'csv',
    ),
    'geomap_border' => Array(
        'must'       => false,
        'default'    => 0.25,
        'match'      => MATCH_FLOAT,
    ),
);

// Global config vars not to show for geomaps
$hiddenConfigVars = array(
    'map_image',
);

function geomap_files($params) {
    // The source_file parameter was filtered here in previous versions. Users
    // reported that this is not very useful. So I removed it. Hope it works
    // for most users.
    // FIXME: the following two "unset" statements fix an "array to string conversion" error
    unset ($params['filter_group']);
    unset ($params['sources']);
    unset ($params['backend_id']);

    $image_name  = 'geomap-'.implode('_', array_values($params)).'.png';
    return array(
        $image_name,
        path('sys', '', 'backgrounds').'/'.$image_name,
        cfg('paths', 'var').$image_name.'.data',
    );
}

function process_geomap($MAPCFG, $map_name, &$map_config) {
    $params = $MAPCFG->getSourceParams();
    list($image_name, $image_path, $data_path) = geomap_files($params);

    // Load the list of locations
    $locations = geomap_get_locations($params);

    // This source does not directly honor the existing map configs. It saves
    // the existing config to use it later for modifying some object parameters.
    // The existing map config must not create new objects. The truth about the
    // existing objects comes only from this source.
    $saved_config = $map_config;
    $map_config = array();

    $iconset = $params['iconset'];
    list($icon_w, $icon_h) = iconset_size($iconset);
    
    // Adapt the global section
    $map_config[0] = $saved_config[0];
    $map_config[0]['map_image'] = $image_name.'?'.time().'.png';
    $map_config[0]['iconset']   = $iconset;

    // Now add the objects to the map
    foreach($locations AS $loc) {
        $object_id = $MAPCFG->genObjId($loc['name']);
        $map_config[$object_id] = array(
            'type'      => 'host',
            'host_name' => $loc['name'],
            'iconset'   => $iconset,
            'object_id' => $object_id,
            'alias'     => $loc['alias'],
            'lat'       => $loc['lat'],
            'long'      => $loc['long'],
        );

        if (isset($loc['backend_id'])) {
            $map_config[$object_id]['backend_id'] = array($loc['backend_id']);
        }
    }
    unset($locations);

    // Now apply the filters. Though the map can be scaled by the filtered hosts
    process_filter($MAPCFG, $map_name, $map_config, $params);

    // Terminate empty views
    if(count($map_config) <= 1)
        throw new GeomapError(l('Got empty map after filtering. Terminate rendering geomap.'));

    // Now detect the upper and lower bounds of the locations to display
    // Left/upper and right/bottom
    // north/south
    $min_lat = 90;
    $max_lat = -90;
    // east/west
    $min_long = 180;
    $max_long = -180;
    foreach($map_config AS $obj) {
        if($obj['type'] == 'global')
            continue;

        if($obj['lat'] < $min_lat)
            $min_lat = $obj['lat'];
        if($obj['lat'] > $max_lat)
            $max_lat = $obj['lat'];

        if($obj['long'] < $min_long)
            $min_long = $obj['long'];
        if($obj['long'] > $max_long)
            $max_long = $obj['long'];
    }

    // Fix equal coordinates (Simply add some space on all sides)
    $min_lat  -= $params['geomap_border'];
    $max_lat  += $params['geomap_border'];
    $min_long -= $params['geomap_border'];
    $max_long += $params['geomap_border'];

    // FIXME: Too small min/max? What is the minimum bbox size?

    //echo $min_lat . ' - ' . $max_lat. ' - '. $mid_lat.'\n';
    //echo $min_long . ' - ' . $max_long. ' - ' . $mid_long;

    // Using this API: http://pafciu17.dev.openstreetmap.org/
    $url = cfg('global', 'geomap_server')
          .'?module=map'
          .'&width='.$params['width'].'&height='.$params['height']
          .'&type='.$params['geomap_type'];

    // The geomap zoom seems to be something different than the nagvis zoom. Use
    // the dedicated geomap_zoom parameter
    if(isset($params['geomap_zoom']) && $params['geomap_zoom'] != '') {
        $mid_lat  = ($min_lat + $max_lat) / 2;
        $mid_long = ($min_long + $max_long) / 2;
        $url .= '&zoom='.$params['geomap_zoom']
               .'&center='.$mid_long.','.$mid_lat;
    }
    else {
        $url .= '&bbox='.$min_long.','.$max_lat.','.$max_long.','.$min_lat;
    }
    //file_put_contents('/tmp/123', $url);

    // Fetch the background image when needed
    if(!file_exists($image_path) || geomap_source_age($params) > filemtime($image_path)) {
        // Allow/enable proxy
        $contents = geomap_get_contents($url);
        file_put_contents($image_path, $contents);
    }

    // Fetch the map bounds when needed
    if(!file_exists($data_path) || geomap_source_age($params) > filemtime($data_path)) {
        // Get the lat/long of the image bounds. The api adds a border area to the
        // generated image. This is good since this makes the outer nodes not touch
        // the border of the image. But this makes calculation of the x/y coords
        // problematic. I found a parameter which tells us the long/lat coordinates
        // of the image bounds.
        // http://pafciu17.dev.openstreetmap.org/?module=map&bbox=6.66748,53.7278,14.5533,51.05&width=1500&height=557&type=osmarender&bboxReturnFormat=csv
        // 2.373046875,54.239550531562,18.8525390625,50.499452103968
        $data_url = $url . '&bboxReturnFormat=csv';
        $contents = geomap_get_contents($data_url);

        if(!$contents ||
            (ord($contents[0]) == 137 &&
             ord($contents[1]) == 80 &&
             ord($contents[2]) == 78)) {
            // Got an png image as answer - catch this!
            throw new GeomapError(l('Got invalid response from "[U]". This is mostly caused by an unhandled request.',
                                            array('U' => $data_url)));
        }

        if(!preg_match('/^-?[0-9]+\.?[0-9]*,-?[0-9]+\.?[0-9]*,-?[0-9]+\.?[0-9]*,-?[0-9]+\.?[0-9]*$/i', $contents))
            throw new GeomapError(l('Got invalid data from "[U]": "[C]"', array('U' => $data_url, 'C' => json_encode($contents))));

        file_put_contents($data_path, $contents);
        $parts = explode(',', $contents);
    } else {
        $parts = explode(',', file_get_contents($data_path));
    }

    $img_left  = (float) $parts[0];
    $img_top   = (float) $parts[1];
    $img_right = (float) $parts[2];
    $img_down  = (float) $parts[3];

    $long_diff = $img_right - $img_left;
    $lat_diff  = $img_top   - $img_down;

    $long_para = $params['width'] / $long_diff;
    $lat_para  = $params['height'] / $lat_diff;
    $lat_mult  = $params['height'] / (ProjectF($img_top) - ProjectF($img_down));

    // Now add the coordinates to the map objects
    foreach($map_config AS &$obj) {
        if(!isset($obj['lat']))
            continue;

        // Calculate the lat (y) coords
        $obj['y'] = round((ProjectF($img_top) - ProjectF($obj['lat'])) * $lat_mult - ($icon_h / 2));
        if($obj['y'] < 0)
            $obj['y'] = 0;		
        
        // Calculate the long (x) coords
        $obj['x'] = round(($long_para * ($obj['long'] - $img_left)) - ($icon_w / 2));
        if($obj['x'] < 0)
            $obj['x'] = 0;

        unset($obj['lat']);
        unset($obj['long']);
    }
}

/**
 * Report as changed when
 * a) either the image file or the data file do not exist
 * b) or when the source file is newer than the compare_time
 * c) or when the image/data files are older than the source file
 */
function changed_geomap($MAPCFG, $compare_time) {
    $params = $MAPCFG->getSourceParams();

    list($image_name, $image_path, $data_path) = geomap_files($params);

    // a)
    if(!file_exists($image_path) || !file_exists($data_path))
        return true;

    // b)
    $t = geomap_source_age($params);
    if($t > $compare_time)
        return true;

    // c)
    if($t > filemtime($image_path) || $t > filemtime($data_path))
        return true;

    return false;
}

# calculate lat on Mercator based map
# for details see:
#    http://wiki.openstreetmap.org/wiki/Slippy_map_tilesnames#X_and_Y
# function copied from
#    http://almien.co.uk/OSM/Tools/Coord/source.php

function ProjectF($Lat){
  $Lat = deg2rad($Lat);
  $Y = log(tan($Lat) + (1/cos($Lat)));
  return($Y);
}

?>
