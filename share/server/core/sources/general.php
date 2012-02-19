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

?>
