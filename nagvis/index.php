<?
##########################################################################
##     	        NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## index.php - Main file to get called by the user. In this file there  ##
##             should be only "output code", all calculations should    ##
##             be done in the classes!                                  ##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence,         	##
## please see attached "LICENCE" file	                                ##
##########################################################################

##########################################################################
## Code Format: Vars: wordWordWord                                      ##
##		   	 Classes: wordwordword                                      ##
##		     Objects: WORDWORDWORD                                      ##
## Please use TAB (Tab Size: 4 Spaces) to format the code               ##
##########################################################################

require("./includes/classes/class.NagVisConfig.php");
require("./includes/classes/class.MapCfg.php");
require("./includes/classes/class.Graphic.php");
require("./includes/classes/class.ReadFiles.php");
require("./includes/classes/class.Common.php");
require("./includes/classes/class.NagVis.php");
require("./includes/classes/class.CheckIt.php");
include("./includes/classes/class.Debug.php");

$MAINCFG = new MainNagVisCfg('./etc/config.ini.php');
$MAPCFG = new MapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();
$CHECKIT = new checkit($MAINCFG,$MAPCFG);
$DEBUG = new debug($MAINCFG);

$FRONTEND = new frontend($MAINCFG,$MAPCFG);

$READFILE = new readFile($MAINCFG);

// check-stuff
if(!$CHECKIT->check_user(1)) {
	exit;
}
if(!$CHECKIT->check_gd(1)) {
	exit;	
}
if(!$CHECKIT->check_permissions($MAPCFG->getValue('global', 0,'allowed_user'),1)) {
	exit;
}
if(!$MAPCFG->checkMapImageReadable(1)) {
	exit;
}
if(!$CHECKIT->check_langfile(1)) {
	exit;
}
$MAINCFG->setRuntimeValue('rotateUrl',$CHECKIT->check_rotate());

require("./includes/classes/class.CheckState_".$MAINCFG->getValue('global', 'backend').".php");
$BACKEND = new backend($MAINCFG);

$FRONTEND->openSite();

// Create Header-Menu, when enabled
if ($MAINCFG->getValue('global', 'displayheader') == "1") {
	$Menu = $READFILE->readMenu();
	$FRONTEND->makeHeaderMenu($Menu);
}

// Print background of the map
$FRONTEND->printMap();

// Let's get all the objects of this map and parse it to the page
$FRONTEND->getMapObjects($MAPCFG,$FRONTEND,$BACKEND,$DEBUG,1);

// Print debug informations
$FRONTEND->debug($debug);

$FRONTEND->closeSite();
$FRONTEND->printSite();
?>
