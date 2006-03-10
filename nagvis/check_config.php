<?php
include("./includes/classes/class.NagVisConfig.php");
include("./includes/classes/class.CheckIt.php");

$MAINCFG = new MainNagVisCfg('./etc/config.ini.php');
$checkconfig = new checkit($MAINCFG);

$checkconfig->check_dummy();

?>
