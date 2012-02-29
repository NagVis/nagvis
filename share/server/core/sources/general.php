<?php

// options to be modyfiable by the user(url)
global $viewParams;
$viewParams = array(
    '*' => array(
        'backend_id',
        'iconset',

        'width',
        'height',

        'header_menu',
        'hover_menu',
        'context_menu',
    )
);

// Config variables to be registered for this source
global $configVars;
$configVars = array(
    'width' => array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_INTEGER_EMPTY
    ),
    'height' => array(
        'must'       => false,
        'default'    => '',
        'match'      => MATCH_INTEGER_EMPTY
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

?>
