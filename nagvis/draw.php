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

require("./includes/defines/global.php");
require("./includes/defines/matches.php");

require("./includes/classes/class.GlobalDebug.php");
require("./includes/classes/class.GlobalGraphic.php");
require("./includes/classes/class.GlobalMainCfg.php");
require("./includes/classes/class.GlobalMapCfg.php");
require("./includes/classes/class.GlobalMap.php");
require("./includes/classes/class.GlobalPage.php");
require("./includes/classes/class.GlobalLanguage.php");
require("./includes/classes/class.GlobalBackendMgmt.php");

require("./includes/classes/class.NagVisMap.php");
require("./includes/classes/class.NagVisBackground.php");
require("./includes/classes/class.NagVisMapCfg.php");

$MAINCFG = new GlobalMainCfg(CONST_MAINCFG);

$MAPCFG = new NagVisMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

$BACKEND = new GlobalBackendMgmt($MAINCFG);

$LANG = new GlobalLanguage($MAINCFG,'nagvis:global');

$BACKGROUND = new NagVisBackground($MAINCFG,$MAPCFG,$LANG,$BACKEND);
$BACKGROUND->parseObjects();
$BACKGROUND->parseMap();
?>
