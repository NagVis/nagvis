<?php

// options to be modyfiable by the user(url)
global $viewParams;
$viewParams = [
    '*' => [
        'sources',

        'header_menu',
        'hover_menu',
        'context_menu',
        'zoombar',

        'zoom',
    ]
];

// Config variables to be registered for all sources, only options
// which are not already available as map paramters need to be
// registered here
global $configVars;
$configVars = [
    'width' => [
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_INTEGER_EMPTY,
        'field_type' => 'dimension',
    ],
    'height' => [
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_INTEGER_EMPTY,
        'field_type' => 'dimension',
    ],
];

// Assign config variables to specific object types
global $configVarMap;
$configVarMap = [
    'global' => [
        'appearance' => [
            'width'  => null,
            'height' => null,
        ],
    ],
];

function iconset_size($iconset) {
    global $CORE;
    $fileType = $CORE->getIconsetFiletype($iconset);
    $iconPath      = path('sys',  'global', 'icons') . '/' . $iconset . '_ok.' . $fileType;
    $iconPathLocal = path('sys',  'local',  'icons') . '/' . $iconset . '_ok.' . $fileType;
    if(file_exists($iconPathLocal)) {
        return getimagesize($iconPathLocal);
    }
    elseif(file_exists($iconPath)) {
        return getimagesize($iconPath);
    } else {
        return [0, 0];
    }
}

function shape_size($icon) {
    $iconPath      = path('sys',  'global', 'shapes') . '/' . $icon;
    $iconPathLocal = path('sys',  'local',  'shapes') . '/' . $icon;
    if(file_exists($iconPathLocal)) {
        return getimagesize($iconPathLocal);
    }
    elseif(file_exists($iconPath)) {
        return getimagesize($iconPath);
    } else {
        return [0, 0];
    }
}


