<?PHP
##########################################################################
##              NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## index.php - Main file to get called by the user                      ##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence,         	##
## please see attached "LICENCE" file                                   ##
##########################################################################

##########################################################################
## For developer guidlines have a look at http://www.nagvis.org         ##
##########################################################################

@session_start();

require("./includes/defines/global.php");
require("./includes/defines/matches.php");

require("./includes/functions/debug.php");

require("./includes/classes/GlobalMainCfg.php");
require("./includes/classes/GlobalMapCfg.php");
require("./includes/classes/GlobalLanguage.php");
require("./includes/classes/GlobalPage.php");
require("./includes/classes/GlobalMap.php");
require("./includes/classes/GlobalBackground.php");
require("./includes/classes/GlobalGraphic.php");
require("./includes/classes/GlobalBackendMgmt.php");

require("./includes/classes/NagVisMapCfg.php");
require("./includes/classes/NagVisMap.php");
require("./includes/classes/NagVisFrontend.php");

require("./includes/classes/NagVisAutoMap.php");

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

if(!isset($_GET['map'])) {
	$_GET['map'] = '';
}

$MAPCFG = new NagVisMapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();

$BACKEND = new GlobalBackendMgmt($MAINCFG);

$FRONTEND = new NagVisFrontend($MAINCFG,$MAPCFG,$BACKEND);

if(isset($_GET['map']) && $_GET['map'] != '') {
	// Build the page
	$FRONTEND->addBodyLines($FRONTEND->getRefresh());
	$FRONTEND->getHeaderMenu();
	$FRONTEND->getMap();
	$FRONTEND->getMessages();
} elseif(isset($_GET['url'])) {
	$FRONTEND->addBodyLines($FRONTEND->getRefresh());
	$FRONTEND->getHeaderMenu();
	$arrFile = file($_GET['url']);
	$FRONTEND->addBodyLines($arrFile);
} elseif(isset($_GET['automap']) && $_GET['automap'] != '') {
	if(!isset($_GET['backend'])) {
		$_GET['backend'] = '';
	}
	if(!isset($_GET['root'])) {
		$_GET['root'] = '';
	}
	if(!isset($_GET['maxLayers'])) {
		$_GET['maxLayers'] = '';
	}
	if(!isset($_GET['renderMode'])) {
		$_GET['renderMode'] = '';
	}
	if(!isset($_GET['width'])) {
		$_GET['width'] = '';
	}
	if(!isset($_GET['height'])) {
		$_GET['height'] = '';
	}
	
	$FRONTEND->addBodyLines($FRONTEND->getRefresh());
	$FRONTEND->getHeaderMenu();
	$FRONTEND->getAutoMap(Array('backend' => $_GET['backend'], 'root' => $_GET['root'], 'maxLayers' => $_GET['maxLayers'], 'renderMode' => $_GET['renderMode'], 'width' => $_GET['width'], 'height' => $_GET['height']));
	$FRONTEND->getMessages();
} elseif(isset($_GET['rotation']) && $_GET['rotation'] != '' && (!isset($_GET['url']) || $_GET['url'] == '') && (!isset($_GET['map']) || $_GET['map'] == '')) {
	header('Location: '.$FRONTEND->getNextRotationUrl());
} elseif(isset($_GET['info'])) {
	$FRONTEND->getInstInformations();
} else {
	// Build the page
	$FRONTEND->getHeaderMenu();
	$FRONTEND->addBodyLines($FRONTEND->getIndexPage());
	$FRONTEND->getMessages();
}

$FRONTEND->printPage();
?>
