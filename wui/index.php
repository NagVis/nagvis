<?php
##########################################################################
##      NagVis WUI - Addon to edit the configuration in the browser     ##
##########################################################################
## index.php - Main file to get called by the user			            ##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence,         	##
## please see attached "LICENCE" file	                                ##
##########################################################################

##########################################################################
## For developer guidlines have a look at http://www.nagvis.org			##
##########################################################################

include("../nagvis/includes/classes/class.GlobalMainCfg.php");
include("../nagvis/includes/classes/class.GlobalMapCfg.php");
include("../nagvis/includes/classes/class.GlobalLanguage.php");
include("../nagvis/includes/classes/class.GlobalPage.php");
include("../nagvis/includes/classes/class.GlobalMap.php");
include("../nagvis/includes/classes/class.GlobalGraphic.php");

include("./includes/classes/class.WuiMainCfg.php");
include("./includes/classes/class.WuiMapCfg.php");
include("./includes/classes/class.WuiFrontend.php");
include("./includes/classes/class.WuiMap.php");


$MAINCFG = new WuiMainCfg('../nagvis/etc/config.ini.php');

if(!isset($_GET['map'])) {
	$_GET['map'] = '';	
}

$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

$FRONTEND = new WuiFrontend($MAINCFG,$MAPCFG);
$FRONTEND->getMap();
$FRONTEND->getMessages();

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