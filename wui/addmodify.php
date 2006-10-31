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


require("./includes/classes/class.WuiAddModify.php");

$MAINCFG = new GlobalMainCfg('../nagvis/etc/config.ini.php');
// we set that this is a wui session
$MAINCFG->setRuntimeValue('wui',1);

//$BACKEND = new GlobalBackendMgmt($MAINCFG);

$MAPCFG = new GlobalMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

$FRONTEND = new WuiAddModify($MAINCFG,$MAPCFG,Array('action' => $_GET['action'],
													'type' => $_GET['type'],
													'id' => $_GET['id'],
													'coords' => $_GET['coords']));
$FRONTEND->getForm();
$FRONTEND->printPage();
?>
