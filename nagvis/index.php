<?PHP
##########################################################################
##     	        NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## index.php - Main file to get called by the user			            ##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence,         	##
## please see attached "LICENCE" file	                                ##
##########################################################################

##########################################################################
## For developer guidlines have a look at http://www.nagvis.org			##
##########################################################################

require("./includes/classes/class.GlobalDebug.php");
require("./includes/classes/class.GlobalMainCfg.php");
require("./includes/classes/class.GlobalMapCfg.php");
require("./includes/classes/class.GlobalLanguage.php");
require("./includes/classes/class.GlobalPage.php");
require("./includes/classes/class.GlobalMap.php");
require("./includes/classes/class.GlobalGraphic.php");
require("./includes/classes/class.GlobalBackendMgmt.php");

require("./includes/classes/class.NagVisMapCfg.php");
require("./includes/classes/class.NagVisMap.php");
require("./includes/classes/class.NagVisFrontend.php");

$MAINCFG = new GlobalMainCfg('./etc/config.ini.php');

if(!isset($_GET['map'])) {
	$_GET['map'] = '';
}

$MAPCFG = new NagVisMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

$BACKEND = new GlobalBackendMgmt($MAINCFG);

$FRONTEND = new NagVisFrontend($MAINCFG,$MAPCFG,$BACKEND);

if(!isset($_GET['url']) && !isset($_GET['info'])) {
    // Build the page
	$FRONTEND->getHeaderMenu();
	$FRONTEND->getMap();
	$FRONTEND->getMessages();
	
	$FRONTEND->printPage();
} elseif(isset($_GET['url'])) {
    $arrFile = file($_GET['url']);
    $FRONTEND->addBodyLines($arrFile);
} elseif(isset($_GET['info'])) {
	$FRONTEND->getInstInformations();
	$FRONTEND->printPage();
}

?>