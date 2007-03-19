<?php
#################################################################################
#       Nagvis Web Configurator 												#
#	GPL License																	#
#																				#
#	Web interface to configure Nagvis maps.										#
#																				#
#	Drag & drop, Tooltip and shapes javascript code taken from 					#
#	http://www.walterzorn.com   												#
#################################################################################

require("../nagvis/includes/classes/class.GlobalMainCfg.php");
require("../nagvis/includes/classes/class.GlobalMapCfg.php");
require("../nagvis/includes/classes/class.GlobalLanguage.php");
require("../nagvis/includes/classes/class.GlobalPage.php");
require("../nagvis/includes/classes/class.GlobalForm.php");
require("../nagvis/includes/classes/class.GlobalBackendMgmt.php");


require("./includes/classes/class.WuiMainCfg.php");
require("./includes/classes/class.WuiMapCfg.php");
require("./includes/classes/class.WuiAddModify.php");

$MAINCFG = new WuiMainCfg('../nagvis/etc/config.ini.php');

//$BACKEND = new GlobalBackendMgmt($MAINCFG);

$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

if(!isset($_GET['coords'])) {
	$_GET['coords'] = '';
}
if(!isset($_GET['id'])) {
	$_GET['id'] = '';
}

$FRONTEND = new WuiAddModify($MAINCFG,$MAPCFG,Array('action' => $_GET['action'],
													'type' => $_GET['type'],
													'id' => $_GET['id'],
													'coords' => $_GET['coords']));
$FRONTEND->getForm();
$FRONTEND->printPage();
?>
