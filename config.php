<?php
header("Location: http://" . $_SERVER['HTTP_HOST'] 
	. rtrim(dirname($_SERVER['PHP_SELF']), '/\\')
    . "/wui/index.php".(($_SERVER["QUERY_STRING"] != '') ? '?':'').$_SERVER["QUERY_STRING"]);
?>