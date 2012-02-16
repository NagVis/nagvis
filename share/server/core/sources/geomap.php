<?php

function geomap_get_locations($p) {
    $locations = array();
    $f = cfg('paths', 'geomap') . '/' . $p['source_file'] . '.csv';

    if($f == '')
        throw new NagVisException(l('No location source file given. Terminate rendering geomap.'));

    if(!file_exists($f))
        throw new NagVisException(l('Location source file "[F]" does not exist.', Array('F' => $f)));

    foreach(file($f) AS $line) {
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

function geomap_get_contents($url) {
    try {
        $opts = array(
            'http' => array(
                'timeout' => cfg('global', 'http_timeout'),
            )
        );

        $proxy = cfg('global', 'http_proxy');
        if($proxy !== null) {
            $opts['http']['proxy'] = $proxy;
            $opts['http']['request_fulluri'] = true;
        }
        
        $context = stream_context_create($opts);

        return file_get_contents($url, false, $context);
    } catch(Exception $e) {
        throw new NagVisException(l('Unable to fetch URL "[U]".<br/><br />The geomap needs to be able to fetch '
                                   .'some data from the internet via webservice API. Please take a look '
                                   .'at the docs for more details.<br /><br /><small>[E]</small>',
                                    Array('U' => $url, 'E' => $e)));
    }
}

function list_geomap_types() {
    return array(
        'osmarender' => 'Osmarender',
        'mapnik'     => 'Mapnik',
        'cycle '     => 'Cycle',
    );
}

function list_geomap_source_files($CORE) {
    return $CORE->getAvailableGeomapSourceFiles();
}

global $viewParams;
if(!isset($viewParams))
    $viewParams = array();
$viewParams = array_merge($viewParams, array(
    'type' => array(
        'must'    => false,
        'default' => 'osmarender',
        'list'    => 'list_geomap_types',
    ),
    'backend_id' => array(
        'must'    => false,
        'default' => '',
        'list'    => 'listBackendIds',
    ),
    'iconset' => array(
        'must'    => false,
        'list'    => 'listIconsets',
    ),
    'source_file' => array(
        'default' => '',
        'list'    => 'list_geomap_source_files',
    ),
));

function params_geomap($MAPCFG, $map_config) {
    global $viewParams;
    $p = array();

    $p['backend_id']  = isset($_GET['backend_id'])  ? $_GET['backend_id']  : $MAPCFG->getValue(0, 'backend_id');
    $p['width']       = isset($_GET['width'])       ? $_GET['width']       : 1700;
    $p['height']      = isset($_GET['height'])      ? $_GET['height']      : 860;
    $p['zoom']        = isset($_GET['zoom'])        ? $_GET['zoom']        : '';
    $p['type']        = isset($_GET['type'])        ? $_GET['type']        : $viewParams['type']['default'];
    $p['iconset']     = isset($_GET['iconset'])     ? $_GET['iconset']     : $MAPCFG->getValue(0, 'iconset');
    $p['source_file'] = isset($_GET['source_file']) ? $_GET['source_file'] : $viewParams['source_file']['default'];

    // Fetch option array from defaultparams string (extract variable names and values)
    // But don't introduce new vars here. And don't override _GET vars
    $def_params = explode('&', $MAPCFG->getValue(0, 'default_params'));
    unset($def_params[0]);

    foreach($def_params AS $set) {
        $arrSet = explode('=', $set);
        if(isset($p[$arrSet[0]]) && !isset($_GET[$arrSet[0]]))
            $p[$arrSet[0]] = $arrSet[1];
    }

    return $p;
}

function geomap_files($params) {
    $use_params = $params;
    if(isset($use_params['source_file']))
        unset($use_params['source_file']);
    $image_name  = 'geomap-'.implode('_', array_values($use_params)).'.png';
    return array(
        $image_name,
        path('sys', '', 'backgrounds').'/'.$image_name,
        cfg('paths', 'var').$image_name.'.data',
    );
}

function geomap_iconset_size($iconset) {
    global $CORE;
    $fileType = $CORE->getIconsetFiletype($iconset);
    $iconPath      = path('sys',  'global', 'icons').'/'.$iconset.'_ok.'.$fileType;
    $iconPathLocal = path('sys',  'local',  'icons').'/'.$iconset.'_ok.'.$fileType;
    if(file_exists($iconPathLocal))
        return getimagesize($iconPathLocal);
    elseif(file_exists($iconPath))
        return getimagesize($iconPath);
    else
        return array(0, 0);
}

function process_geomap($MAPCFG, $map_name, &$map_config) {
    $params = params_geomap($MAPCFG, $map_config);
    $params = array_merge(params_filter($MAPCFG, $map_config), $params);
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
    list($icon_w, $icon_h) = geomap_iconset_size($iconset);

    // Now add the objects to the map
    foreach($locations AS $loc) {
        $map_config[$loc['name']] = array(
            'type'      => 'host',
            'host_name' => $loc['name'],
            'iconset'   => $iconset,
            'object_id' => $loc['name'],
            'alias'     => $loc['alias'],
            'lat'       => $loc['lat'],
            'long'      => $loc['long'],
        );
    }
    unset($locations);

    // Now apply the filters. Though the map can be scaled by the filtered hosts
    process_filter($MAPCFG, $map_name, $map_config, $params);

    // Terminate empty views
    if(count($map_config) == 0)
        throw new NagVisException(l('Got empty map after filtering. Terminate rendering geomap.'));

    // Now detect the upper and lower bounds of the locations to display
    // Left/upper and right/bottom
    // north/south
    $min_lat = 90;
    $max_lat = -90;
    // east/west
    $min_long = 180;
    $max_long = -180;
    foreach($map_config AS $obj) {
        if($obj['lat'] < $min_lat)
            $min_lat = $obj['lat'];
        if($obj['lat'] > $max_lat)
            $max_lat = $obj['lat'];

        if($obj['long'] < $min_long)
            $min_long = $obj['long'];
        if($obj['long'] > $max_long)
            $max_long = $obj['long'];
    }

    // FIXME: Too small min/max? What is the minimum bbox size?

    $mid_lat  = $min_lat  + ($max_lat - $min_lat) / 2;
    $mid_long = $min_long + ($max_long - $min_long) / 2;

    //echo $min_lat . ' - ' . $max_lat. ' - '. $mid_lat.'\n';
    //echo $min_long . ' - ' . $max_long. ' - ' . $mid_long;

    // Using this API: http://pafciu17.dev.openstreetmap.org/
    $url = 'http://dev.openstreetmap.org/~pafciu17/'
          .'?module=map&bbox='.$min_long.','.$max_lat.','.$max_long.','.$min_lat
          .'&width='.$params['width'].'&height='.$params['height']
          .'&type='.$params['type'];
          //.'&points='.$min_long.','.$max_lat.';'.$max_long.','.$min_lat;
    if($params['zoom'] != '')
        $url .= '&zoom='.$params['zoom'];
    //file_put_contents('/tmp/123', $url);

    // Fetch the background image when needed
    if(!file_exists($image_path)) {
        // Allow/enable proxy
        $contents = geomap_get_contents($url);
        file_put_contents($image_path, $contents);
    }

    // Fetch the map bounds when needed
    if(!file_exists($data_path)) {
        // Get the lat/long of the image bounds. The api adds a border area to the
        // generated image. This is good since this makes the outer nodes not touch
        // the border of the image. But this makes calculation of the x/y coords
        // problematic. I found a parameter which tells us the long/lat coordinates
        // of the image bounds.
        // http://pafciu17.dev.openstreetmap.org/?module=map&bbox=6.66748,53.7278,14.5533,51.05&width=1500&height=557&type=osmarender&bboxReturnFormat=csv
        // 2.373046875,54.239550531562,18.8525390625,50.499452103968
        $data_url = $url . '&bboxReturnFormat=csv';
        $contents = geomap_get_contents($data_url);
        if(!preg_match('/^-?[0-9]+\.?[0-9]*,-?[0-9]+\.?[0-9]*,-?[0-9]+\.?[0-9]*,-?[0-9]+\.?[0-9]*$/i', $contents))
            throw new NagVisException(l('Got invalid data from "[U]"', array('U' => $data_url)));
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
    
    $map_config[0] = $saved_config[0];
    $map_config[0]['map_image'] = $image_name;
    $map_config[0]['iconset']   = $iconset;

    // Now add the coordinates to the map objects
    foreach($map_config AS &$obj) {
        if(!isset($obj['lat']))
            continue;

        // Calculate the lat (y) coords
        $obj['y'] = $params['height'] - ($lat_para * ($obj['lat'] - $img_down)) - ($icon_h / 2);
        
        // Calculate the long (x) coords
        $obj['x'] = ($long_para * ($obj['long'] - $img_left)) - ($icon_w / 2);
        unset($obj['lat']);
        unset($obj['long']);
    }
}

/**
 * Report as changed when the source file is newer than the compare_time
 * or when either the image file or the data file do not exist
 */
function changed_geomap($MAPCFG, $compare_time) {
    $params = params_geomap($MAPCFG, array());

    list($image_name, $image_path, $data_path) = geomap_files($params);
    if(!file_exists($image_path) || !file_exists($data_path))
        return true;

    $t = filemtime(cfg('paths', 'geomap') . '/' . $params['source_file'] . '.csv');
    return $t > $compare_time;
}

?>
