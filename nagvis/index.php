<?PHP
##########################################################################
##     	        NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## index.php - Main file to get called by the user. In this file there  ##
##             should be only initialisation of the classes             ##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence,         	##
## please see attached "LICENCE" file	                                ##
##########################################################################

##########################################################################
## Code Format: Vars: wordWordWord                                      ##
##		   	 	Classes: WordWordWord                                   ##
##		     	Objects: WORDWORDWORD                                   ##
## Please use TAB (Tab Size: 4 Spaces) to format the code               ##
##########################################################################

require("./includes/classes/class.GlobalMainCfg.php");
require("./includes/classes/class.GlobalMapCfg.php");
require("./includes/classes/class.GlobalLanguage.php");
require("./includes/classes/class.GlobalPage.php");
require("./includes/classes/class.GlobalMap.php");
require("./includes/classes/class.GlobalGraphic.php");

require("./includes/classes/class.NagVisMap.php");
require("./includes/classes/class.NagVisFrontend.php");

$MAINCFG = new GlobalMainCfg('./etc/config.ini.php','./etc/config.local.ini.php');

$MAPCFG = new GlobalMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

require("./includes/classes/class.GlobalBackend-".$MAINCFG->getValue('global', 'backend').".php");
$BACKEND = new GlobalBackend($MAINCFG);

$FRONTEND = new NagVisFrontend($MAINCFG,$MAPCFG,$BACKEND);

// Build the page
$FRONTEND->getHeaderMenu();
$FRONTEND->getMap();
$FRONTEND->printPage();
?>