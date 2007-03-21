<?php
##########################################################################
##     	        NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## draw.php - File to draw the background image 			            ##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence,         	##
## please see attached "LICENCE" file	                                ##
##########################################################################

##########################################################################
## For developer guidlines have a look at http://www.nagvis.org			##
##########################################################################

include("./includes/classes/class.GlobalGraphic.php");
include("./includes/classes/class.GlobalMainCfg.php");
include("./includes/classes/class.GlobalMapCfg.php");
include("./includes/classes/class.GlobalMap.php");
include("./includes/classes/class.GlobalPage.php");
include("./includes/classes/class.GlobalLanguage.php");
require("./includes/classes/class.GlobalBackendMgmt.php");
require("./includes/classes/class.NagVisBackground.php");

include("./includes/classes/class.NagVisMapCfg.php");

$MAINCFG = new GlobalMainCfg('./etc/config.ini.php');

$MAPCFG = new NagVisMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

$BACKEND = new GlobalBackendMgmt($MAINCFG);

$LANG = new GlobalLanguage($MAINCFG,'nagvis:global');

$BACKGROUND = new NagVisBackground($MAINCFG,$MAPCFG,$LANG,$BACKEND);
$BACKGROUND->parseObjects();
$BACKGROUND->parseMap();
?>