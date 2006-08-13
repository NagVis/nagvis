<?php
#################################################################################
#       Nagvis Web Configurator 						#
#	GPL License								#
#										#
#										#
#	Web interface to configure Nagvis maps.					#
#										#
#	Drag & drop, Tooltip and shapes javascript code taken from 		#
#	http://www.walterzorn.com   						#
#										#
#################################################################################

include("../nagvis/includes/classes/class.GlobalMainCfg.php");
include("../nagvis/includes/classes/class.GlobalMapCfg.php");
include("../nagvis/includes/classes/class.GlobalLanguage.php");
include("../nagvis/includes/classes/class.GlobalPage.php");
include("../nagvis/includes/classes/class.GlobalMap.php");
include("../nagvis/includes/classes/class.GlobalGraphic.php");

include("./includes/classes/class.WuiFrontend.php");
include("./includes/classes/class.WuiMap.php");


$MAINCFG = new GlobalMainCfg('../nagvis/etc/config.ini.php','../nagvis/etc/config.local.ini.php');
// FIXME: i don't want to set this in future - we set that this is a wui session
$MAINCFG->setRuntimeValue('wui',1);

$MAPCFG = new GlobalMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

$FRONTEND = new WuiFrontend($MAINCFG,$MAPCFG,$BACKEND);
$FRONTEND->getMap();

if($_GET['map'] != '') {
	if(!$MAPCFG->checkMapConfigWriteable(1)) {
		exit;
	}
	if(!$MAPCFG->checkMapImageReadable(1)) {
		exit;
	}
}

# we print in the HTML page all the code we just computed
$FRONTEND->printPage();
?>