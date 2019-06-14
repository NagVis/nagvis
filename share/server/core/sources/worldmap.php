<?php

class WorldmapError extends MapSourceError {}

define('MATCH_WORLDMAP_ZOOM', '/^1?[0-9]$/');

// Register this source as being selectable by the user
global $selectable;
$selectable = true;

// options to be modifiable by the user(url)
global $viewParams;
$viewParams = array(
    'worldmap' => array(
        'backend_id',
        'worldmap_center',
        'worldmap_zoom',
    )
);

// Config variables to be registered for this source
global $configVars;
$configVars = array(
    /*** GLOBAL OPTIONS ***/
    'worldmap_center' => array(
        'must'      => true,
        'default'   => '50.86837814203458,10.21728515625',
        'match'     => MATCH_LATLONG,
    ),
    'worldmap_zoom' => array(
        'must'      => true,
        'default'   => 6,
        'match'     => MATCH_WORLDMAP_ZOOM,
    ),

    /*** OBJECT OPTIONS ***/
    'min_zoom' => array(
        'must'      => false,
        'default'   => 2,
        'match'     => MATCH_WORLDMAP_ZOOM,
    ),
    'max_zoom' => array(
        'must'      => false,
        'default'   => 18,
        'match'     => MATCH_WORLDMAP_ZOOM,
    ),
);

// Assign config variables to specific object types
global $configVarMap;
$configVarMap = array(
    'global' => array(
        'worldmap' => array(
            'worldmap_center' => null,
            'worldmap_zoom'   => null,
        ),
    ),
);

// Assign these options to all map objects (except global)
foreach (getMapObjectTypes() AS $type) {
    $configVarMap[$type] = array(
        'worldmap' => array(
            'min_zoom' => null,
            'max_zoom' => null,
        ),
    );
}

// Global config vars not to show for worldmaps
$hiddenConfigVars = array(
    'zoom',
    'zoombar',
);

// Alter some global vars with automap specific things
$updateConfigVars = array(
    'iconset' => array(
        'default' => 'std_geo',
    ),
    'icon_size' => array(
        'default' => array(24),
    ),
);

// The worldmap database object
$DB = null;

function worldmap_init_schema() {
    global $DB, $CORE;
    // Create initial db scheme if needed
    if (!$DB->tableExist('objects')) {
        $DB->query('CREATE TABLE objects '
                 .'(object_id VARCHAR(20),'
                 .' lat REAL,'
                 .' lng REAL,'
                 .' lat2 REAL,' // needed for line objects
                 .' lng2 REAL,'
                 .' object TEXT,'
                 .' PRIMARY KEY(object_id))');
        $DB->query('CREATE INDEX latlng ON objects (lat,long)');
        $DB->query('CREATE INDEX latlng2 ON objects (lat2,long2)');
        $DB->createVersionTable();

        // Install demo data
        worldmap_db_update_object('273924', 53.5749514424993, 10.0405490398407, array(
            "x"         => "53.57495144249931",
            "y"         => "10.040549039840698",
            "type"      => "map",
            "map_name"  => "demo-ham-racks",
            "object_id" => "273924"
        ));
        worldmap_db_update_object('0df2d3', 48.1125317248817, 11.6794109344482, array(
            "x"              => "48.11253172488166",
            "y"              => "11.67941093444824",
            "type"           => "hostgroup",
            "hostgroup_name" => "muc",
            "object_id"      => "0df2d3"
        ));
        worldmap_db_update_object('ebbf59', 50.9391761712781, 6.95863723754883, array(
            "x"              => "50.93917617127812",
            "y"              => "6.958637237548828",
            "type"           => "hostgroup",
            "hostgroup_name" => "cgn",
            "object_id"      => "ebbf59"
        ));
    }
    //else {
    //    // Maybe an update is needed
    //    $DB->updateDb();

    //    // Only apply the new version when this is the real release or newer
    //    // (While development the version string remains on the old value)
    //    //if($CORE->versionToTag(CONST_VERSION) >= 1060100)
    //        $DB->updateDbVersion();
    //}
}

function worldmap_init_db() {
    global $DB;
    if ($DB !== null)
        return; // only init once
    $DB = new CorePDOHandler();
    if (!$DB->open('sqlite', array('filename' => cfg('paths', 'cfg').'worldmap.db'), null, null))
        throw new NagVisException(l('Unable to open worldmap database ([DB]): [MSG]',
            Array('DB' => $DB->dsn,
                  'MSG' => json_encode($DB->error()))));

    worldmap_init_schema();
}

function worldmap_get_objects_by_bounds($sw_lng, $sw_lat, $ne_lng, $ne_lat) {
    global $DB;
    worldmap_init_db();

    $q = 'SELECT lat, lng, lat2, lng2, object FROM objects WHERE'
        .'((:sw_lat < :ne_lat AND lat BETWEEN :sw_lat AND :ne_lat)'
        .' OR (:ne_lat < :sw_lat AND lat BETWEEN :ne_lat AND :sw_lat)'
        .'AND '
        .'(:sw_lng < :ne_lng AND lng BETWEEN :sw_lng AND :ne_lng)'
        .' OR (:ne_lng < :sw_lng AND lng BETWEEN :ne_lng AND :sw_lng))'
        .'OR '
        .'((:sw_lat < :ne_lat AND lat2 BETWEEN :sw_lat AND :ne_lat)'
        .' OR (:ne_lat < :sw_lat AND lat2 BETWEEN :ne_lat AND :sw_lat)'
        .'AND '
        .'(:sw_lng < :ne_lng AND lng2 BETWEEN :sw_lng AND :ne_lng)'
        .' OR (:ne_lng < :sw_lng AND lng2 BETWEEN :ne_lng AND :sw_lng))';

    $RES = $DB->query($q, array('sw_lng' => $sw_lng, 'sw_lat' => $sw_lat, 'ne_lng' => $ne_lng, 'ne_lat' => $ne_lat));
    $objects = array();
    $referenced = array();
    while ($data = $RES->fetch()) {
        $obj = json_decode($data['object'], true);
        $objects[$obj['object_id']] = $obj;

        // check all coordinates for relative coords
        $coords = array($data['lat'], $data['lng'], $data['lat2'], $data['lng2']);
        foreach ($coords as $coord) {
            if (strpos($coord, '%') !== false) {
                $referenced[substr($coord, 0, 6)] = null;
            }
        }
    }

    // When an object has relative coordinates also fetch the referenced object
    if ($referenced) {
        $keys = array_unique(array_keys($referenced));
        $count = count($keys);
        $oids = array();
        $filter = array();
        for ($i = 1; $i <= $count; $i++) {
            $id = ":o$i";
            $oids[] = $id;
            $filter[$id] = $keys[$i - 1];
        }
        $q = 'SELECT object FROM objects WHERE object_id IN ('.implode(', ', $oids).')';
        $RES = $DB->query($q, $filter);
        while ($data = $RES->fetch()) {
            $obj = json_decode($data['object'], true);
            $objects[$obj['object_id']] = $obj;
        }
    }

    return $objects;
}

// Worldmap internal helper function to add an object to the worldmap
function worldmap_db_update_object($obj_id, $lat, $lng, $obj,
                                   $lat2 = null, $lng2 = null, $insert = true) {
    global $DB;
    worldmap_init_db();

    if ($insert) {
        $q = 'INSERT INTO objects (object_id, lat, lng, lat2, lng2, object)'
            .' VALUES (:obj_id, :lat, :lng, :lat2, :lng2, :object)';
    }
    else {
        $q = 'UPDATE objects SET '
            .' lat=:lat,'
            .' lng=:lng,'
            .' lat2=:lat2,'
            .' lng2=:lng2,'
            .' object=:object '
            .'WHERE object_id=:obj_id';
    }

    if ($DB->query($q, array(
        'obj_id' => $obj_id,
        'lat' => $lat,
        'lng' => $lng,
        'lat2' => $lat2,
        'lng2' => $lng2,
        'object' => json_encode($obj))))
        return true;
    else
        throw new WorldmapError(l('Failed to add object: [E]: [Q]', array(
            'E' => json_encode($DB->error()), 'Q' => $q)));
}

function worldmap_update_object($MAPCFG, $map_name, &$map_config, $obj_id, $insert = true) {
    $obj = $map_config[$obj_id];

    if ($obj['type'] == 'global')
        return false; // adding global section (during map creation)

    // disable creating new objects during "view to new map" action
    if (val($_GET, 'act', null) == 'viewToNewMap')
        return true;

    $lat  = $obj['x'];
    $lng  = $obj['y'];
    $lat2 = null;
    $lng2 = null;

    // Handle lines and so on
    if ($MAPCFG->getValue($obj_id, 'view_type') == 'line' || $obj['type'] == 'line') {
        $x = explode(',', $obj['x']);
        $y = explode(',', $obj['y']);
        $lat  = $x[0];
        $lng  = $y[0];
        $lat2 = $x[count($x)-1];
        $lng2 = $y[count($y)-1];
    }

    return worldmap_db_update_object($obj_id, $lat, $lng, $obj, $lat2, $lng2, $insert);
}

//
// The following functions are used directly by NagVis
//

// Set the needed values for maps currently being created
function init_map_worldmap($MAPCFG, $map_name, &$map_config) {
    global $configVars;
    $MAPCFG->setValue(0, "worldmap_center", $configVars["worldmap_center"]["default"]);
    $MAPCFG->setValue(0, "worldmap_zoom", $configVars["worldmap_zoom"]["default"]);
}

// Returns the minimum bounds needed to be able to display all objects
function get_bounds_worldmap($MAPCFG, $map_name, &$map_config) {
    global $DB;
    worldmap_init_db();

    $q = 'SELECT min(lat) as min_lat, min(lng) as min_lng, '
        .'max(lat) as max_lat, max(lng) as max_lng '
        .'FROM objects';
    $b = $DB->query($q)->fetch();
    return array(array($b['min_lat'], $b['min_lng']),
                 array($b['max_lat'], $b['max_lng']));
}

function load_obj_worldmap($MAPCFG, $map_name, &$map_config, $obj_id) {
    global $DB;
    worldmap_init_db();

    if (isset($map_config[$obj_id]))
        return true; // already loaded

    $q = 'SELECT object FROM objects WHERE object_id=:obj_id';
    $b = $DB->query($q, array('obj_id' => $obj_id))->fetch();
    if ($b)
        $map_config[$obj_id] = json_decode($b['object'], true);
}

function del_obj_worldmap($MAPCFG, $map_name, &$map_config, $obj_id) {
    global $DB;
    worldmap_init_db();

    $q = 'DELETE FROM objects WHERE object_id=:obj_id';
    if ($DB->query($q, array('obj_id' => $obj_id)))
        return true;
    else
        throw new WorldmapError(l('Failed to delete object: [E]: [Q]', array(
            'E' => json_encode($DB->error()), 'Q' => $q)));
}

function update_obj_worldmap($MAPCFG, $map_name, &$map_config, $obj_id) {
    return worldmap_update_object($MAPCFG, $map_name, $map_config, $obj_id, false);
}

function add_obj_worldmap($MAPCFG, $map_name, &$map_config, $obj_id) {
    return worldmap_update_object($MAPCFG, $map_name, $map_config, $obj_id);
}

function process_worldmap($MAPCFG, $map_name, &$map_config) {
    $bbox = val($_GET, 'bbox', null);
    if ($bbox === null)
        return false; // do nothing

    $params = $MAPCFG->getSourceParams();
    $zoom = (int)$params['worldmap_zoom'];

    list($sw_lng, $sw_lat, $ne_lng, $ne_lat) = explode(',', $bbox);
    foreach (worldmap_get_objects_by_bounds($sw_lng, $sw_lat, $ne_lng, $ne_lat) as $object_id => $obj) {
        // Now, when the object has a maximum / minimum zoom configured,
        // hide it depending on the zoom
        $min_zoom = isset($obj['min_zoom']) ? (int)$obj['min_zoom'] : $MAPCFG->getDefaultValue('host', 'min_zoom');
        $max_zoom = isset($obj['max_zoom']) ? (int)$obj['max_zoom'] : $MAPCFG->getDefaultValue('host', 'max_zoom');

        if ($min_zoom <= $max_zoom && ($zoom < $min_zoom || $zoom > $max_zoom))
            continue;

        $map_config[$object_id] = $obj;
    }
    return true;
}

function changed_worldmap($MAPCFG, $compare_time) {
    $db_path = cfg('paths', 'cfg').'worldmap.db';
    return !file_exists($db_path) || filemtime($db_path) > $compare_time;
}

?>
