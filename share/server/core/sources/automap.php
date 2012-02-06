<?php

function process_add_host(&$mapConfig) {
    // This source does not directly honor the existing map configs. It saves
    // the existing config to use it later for modifying some object parameters.
    // The existing map config must not create new objects. The truth about the
    // existing objects comes only from this source.
    $savedConfig = $mapConfig;
    $mapConfig = array();

    // FIXME: Gather the objects to show in this view
    $mapConfig['aa11bb'] = array(
        'object_id' => 'aa11bb',
        'type'      => 'host', 
        'host_name' => 'localhost',
        'x'         => 50,
        'y'         => 50,
    );

    // Now restore the user defined coordinates
    foreach($savedConfig AS $object_id => $object) {
        if(isset($mapConfig[$object_id])) {
            $mapConfig[$object_id]['x'] = $object['x'];
            $mapConfig[$object_id]['y'] = $object['y'];
        }
    }

    // FIXME: And now use graphviz to gather the rest of the coordinates
}

function changed_add_host($compareTime) {
    // FIXME: Reload on changed parameters or restarted nagios
    return true;
}

?>
