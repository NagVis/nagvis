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

function iconset_size($iconset) {
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
