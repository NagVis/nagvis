<?php

function geomap_get_locations() {
    $locations = array();
    // FIXME: File config?
    $f = '/tmp/edklatlng.csv';
    if(!file_exists($f))
        throw new NagVisException(l('Locations file "[F]" does not exist.', Array('F' => $f)));
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

function process_geomap(&$mapConfig) {
    // This source does not directly honor the existing map configs. It saves
    // the existing config to use it later for modifying some object parameters.
    // The existing map config must not create new objects. The truth about the
    // existing objects comes only from this source.
    $savedConfig = $mapConfig;
    $mapConfig = array();

    // Load the list of locations
    $locations = geomap_get_locations();

    // Now detect the upper and lower bounds of the locations to display
    // Left/upper and right/bottom
    // north/south
    $min_lat = 90;
    $max_lat = -90;
    // east/west
    $min_long = 180;
    $max_long = -180;
    foreach($locations AS $loc) {
        if($loc['lat'] < $min_lat)
            $min_lat = $loc['lat'];
        elseif($loc['lat'] > $max_lat)
            $max_lat = $loc['lat'];

        if($loc['long'] < $min_long)
            $min_long = $loc['long'];
        if($loc['long'] > $max_long)
            $max_long = $loc['long'];
    }

    $mid_lat  = $min_lat  + ($max_lat - $min_lat) / 2;
    $mid_long = $min_long + ($max_long - $min_long) / 2;

    //echo $min_lat . ' - ' . $max_lat. ' - '. $mid_lat.'\n';
    //echo $min_long . ' - ' . $max_long. ' - ' . $mid_long;

    // FIXME: Need the map name
    // FIXME: Screen resolution?
    if(isset($_GET['width'])) {
        $width = $_GET['width'];
    } else {
        $width  = 1700;
    }

    if(isset($_GET['height'])) {
        $height = $_GET['height'];
    } else {
        $height = 860;
    }

    //if(isset($_GET['zoom'])) {
    //    $zoom = $_GET['zoom'];
    //} else {
    //    $zoom = 8;
    //}

    if(isset($_GET['type'])) {
        $type = $_GET['type'];
    } else {
        $type = 'osmarender';
    }

    // FIXME: Iconset - gather automatically?
    $iconset = 'std_dot';
    $icon_w  = 6;
    $icon_h  = 6;

    $params = array($min_long, $max_lat, $max_long, $min_lat, $width, $height, $type); //, $zoom);
    $image_name  = 'geomap-'.implode('-', $params).'.png';
    $image_path  = path('sys', '', 'backgrounds').'/'.$image_name;
    $data_path   = cfg('paths', 'var').$image_name.'.data';

    // Using this API: http://pafciu17.dev.openstreetmap.org/
    $url = 'http://dev.openstreetmap.org/~pafciu17/'
          .'?module=map&bbox='.$min_long.','.$max_lat.','.$max_long.','.$min_lat
          .'&width='.$width.'&height='.$height.'&type='.$type; //&zoom='.$zoom;
          //.'&points='.$min_long.','.$max_lat.';'.$max_long.','.$min_lat;
    //file_put_contents('/tmp/123', $url);

    // Fetch the background image when needed
    if(!file_exists($image_path)) {
        // Allow/enable proxy
        $contents = file_get_contents($url);
        file_put_contents($image_path, $contents);
    }

    // Fetch the map bounds when needed
    if(!file_exists($data_path)) {
        // Get the lat/long of the image bounds. The api adds a border area to the
        // generated image. This is good since this makes the outer nodes not touch
        // the border of the image. But this makes calculation of the x/y coords
        // problematic. I found a parameter which tells us the long/lat coordinates
        // of the image bounds.
        $contents = file_get_contents($url . '&bboxReturnFormat=csv');
        file_put_contents($data_path, $contents);
        // FIXME: Write x/y factors to the file
        $parts = explode(',', $contents);
    } else {
        $parts = explode(',', file_get_contents($data_path));
    }

    $img_left  = (float) $parts[0];
    $img_top   = (float) $parts[1];
    $img_right = (float) $parts[2];
    $img_down  = (float) $parts[3];
    // FIXME: Save this to a tempfile

    $long_diff = $img_right - $img_left;
    $lat_diff  = $img_top   - $img_down;

    $long_para = $width / $long_diff;
    $lat_para  = $height / $lat_diff;
    
    $mapConfig[0] = array(
        'type'      => 'global',
        'map_image' => $image_name,
        'iconset'   => $iconset,
    );

    // Now add the objects to the map
    foreach($locations AS $loc) {
        // Calculate the lat (y) coords
        $y = $height - ($lat_para * ($loc['lat'] - $img_down)) - ($icon_h / 2);
        
        // Calculate the long (x) coords
        $x = $long_para * ($loc['long'] - $img_left) - ($icon_w / 2);

        $mapConfig[$loc['name']] = array(
            'type'      => 'host',
            'host_name' => $loc['name'],
            'iconset'   => $iconset,
            'object_id' => $loc['name'],
            'alias'     => $loc['alias'],
            'x'         => $x,
            'y'         => $y,
        );
    }
}

function changed_geomap($compare_time) {
    // This has changed when the source file is newer than the compare time
    $t = filemtime('/tmp/edklatlng.csv');
    return $t > $compare_time;
}

?>
