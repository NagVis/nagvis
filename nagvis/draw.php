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

@session_start();

require("./includes/defines/global.php");
require("./includes/defines/matches.php");

require("./includes/functions/debug.php");

require("./includes/classes/GlobalGraphic.php");
require("./includes/classes/GlobalMainCfg.php");
require("./includes/classes/GlobalMapCfg.php");
require("./includes/classes/GlobalMap.php");
require("./includes/classes/GlobalPage.php");
require("./includes/classes/GlobalBackground.php");
require("./includes/classes/GlobalLanguage.php");
require("./includes/classes/GlobalBackendMgmt.php");

require("./includes/classes/NagVisMap.php");
require("./includes/classes/NagVisBackground.php");
require("./includes/classes/NagVisMapCfg.php");

require("./includes/classes/objects/NagVisObject.php");
require("./includes/classes/objects/NagVisStatefulObject.php");
require("./includes/classes/objects/NagVisStatelessObject.php");
require("./includes/classes/objects/NagiosHost.php");
require("./includes/classes/objects/NagVisHost.php");
require("./includes/classes/objects/NagiosService.php");
require("./includes/classes/objects/NagVisService.php");
require("./includes/classes/objects/NagiosHostgroup.php");
require("./includes/classes/objects/NagVisHostgroup.php");
require("./includes/classes/objects/NagiosServicegroup.php");
require("./includes/classes/objects/NagVisServicegroup.php");
require("./includes/classes/objects/NagVisMapObj.php");
require("./includes/classes/objects/NagVisShape.php");
require("./includes/classes/objects/NagVisTextbox.php");

$MAINCFG = new GlobalMainCfg(CONST_MAINCFG);

$MAPCFG = new NagVisMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

$BACKEND = new GlobalBackendMgmt($MAINCFG);

$LANG = new GlobalLanguage($MAINCFG,'nagvis:global');

$BACKGROUND = new NagVisBackground($MAINCFG,$MAPCFG,$LANG,$BACKEND);
$BACKGROUND->parseObjects();
$BACKGROUND->parseMap();
?>
