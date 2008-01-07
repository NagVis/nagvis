<?php
// This file redirects the request to the WUI index file (wui/index.php)
header("Location: ".((isset($_SERVER["HTTPS"])) ? 'https://': 'http://') . $_SERVER['HTTP_HOST'] 
	. ((isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') ? ':'.$_SERVER['SERVER_PORT']: '')
	. rtrim(dirname($_SERVER['PHP_SELF']), '/\\')
    . "/wui/index.php".(($_SERVER["QUERY_STRING"] != '') ? '?':'').$_SERVER["QUERY_STRING"]);
?>