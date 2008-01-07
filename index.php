<?php
// This file redirects the request to the frontend index file (nagvis/index.php)
header("Location: ". ((isset($_SERVER["HTTPS"])) ? 'https://': 'http://') . $_SERVER['HTTP_HOST'] 
	. ((isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') ? ':'.$_SERVER['SERVER_PORT']: '')
	. rtrim(dirname($_SERVER['PHP_SELF']), '/\\')
    . "/nagvis/index.php".(($_SERVER["QUERY_STRING"] != '') ? '?':'').$_SERVER["QUERY_STRING"]);
?>