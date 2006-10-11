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
require("../nagvis/includes/classes/class.GlobalLanguage.php");
require("../nagvis/includes/classes/class.GlobalPage.php");
require("../nagvis/includes/classes/class.GlobalForm.php");

require("./includes/classes/class.WuiMapManagement.php");

$MAINCFG = new GlobalMainCfg('../nagvis/etc/config.ini.php');
// we set that this is a wui session
$MAINCFG->setRuntimeValue('wui',1);

$FRONTEND = new WuiMapManagement($MAINCFG);
$FRONTEND->getForm();
$FRONTEND->printPage();
?>