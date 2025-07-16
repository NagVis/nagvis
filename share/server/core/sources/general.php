<?php

// options to be modyfiable by the user(url)
global $viewParams;
$viewParams = array(
    '*' => array(
        'sources',

        'header_menu',
        'hover_menu',
        'context_menu',
        'zoombar',

        'zoom',
    )
);

// Config variables to be registered for all sources, only options
// which are not already available as map paramters need to be
// registered here
global $configVars;
$configVars = array(
    'width' => array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_INTEGER_EMPTY,
        'field_type' => 'dimension',
    ),
    'height' => array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_INTEGER_EMPTY,
        'field_type' => 'dimension',
    ),
);

// Assign config variables to specific object types
global $configVarMap;
$configVarMap = array(
    'global' => array(
        'appearance' => array(
            'width'  => null,
            'height' => null,
        ),
    ),
);

function iconset_size($iconset) {
    global $CORE;
    $fileType = $CORE->getIconsetFiletype($iconset);
    $iconPath      = path('sys',  'global', 'icons').'/'.$iconset.'_ok.'.$fileType;
    $iconPathLocal = path('sys',  'local',  'icons').'/'.$iconset.'_ok.'.$fileType;
    if(file_exists($iconPathLocal)) {
        if($fileType == "svg") {
            return svg_size($iconPathLocal);
        }
        else {
            return getimagesize($iconPathLocal);
        }
    }
    elseif(file_exists($iconPath)){
        if($fileType == "svg") {
            return svg_size($iconPath);
        }
        else {
            return getimagesize($iconPath);
        }
    }
    else
        return array(0, 0);
}

function svg_size($filepath) {
    if (!file_exists($filepath)) {
        return array(0, 0);
    }

    $doc = new DOMDocument();
    $doc->load($filepath);

    $svg = $doc->getElementsByTagName('svg')->item(0);
    $width = $svg->getAttribute('width');
    $height = $svg->getAttribute('height');

    // Fallback to viewBox
    if (empty($width) || empty($height)) {
        $viewBox = $svg->getAttribute('viewBox');
        $parts = preg_split('/[\s,]+/', $viewBox);
        if (count($parts) === 4) {
            $width = $parts[2];
            $height = $parts[3];
        }
    }

    return array(floatval($width), floatval($height));
}

function shape_size($icon) {
    $iconPath      = path('sys',  'global', 'shapes').'/'.$icon;
    $iconPathLocal = path('sys',  'local',  'shapes').'/'.$icon;
    if(file_exists($iconPathLocal))
        return getimagesize($iconPathLocal);
    elseif(file_exists($iconPath))
        return getimagesize($iconPath);
    else
        return array(0, 0);
}

?>
