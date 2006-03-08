<?php
include("./includes/classes/class.NagVisConfig.php");
include("./includes/classes/class.CheckIt.php");

$CONFIG = new MainNagVisCfg('./etc/config.ini');
$checkconfig = new checkit($CONFIG);

$checkconfig->check_dummy();

?>
