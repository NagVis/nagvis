<?
##########################################################################
##     	                           NagVis                              ##
##       Autor von NagVis 0.0.3: Jörg Linge <pichfork@ederdrom.de>      ##
##                                                                      ##
##      Umgschrieben von Perl in PHP by Michael Lübben(MiCkEy2002)      ##
##                                                                      ##
##                               Lizenz GPL                             ##
##########################################################################

include("./include/config.inc.php");
include("./include/class.NagVis.php");

// Map festlegen
if(isset($_GET['map']))
   $map = $_GET['map'];
else
   $map = $maps[0];

// Angemeldeten User festlegen
$user = $_SERVER['PHP_AUTH_USER'];

$nagvis = new NagVis();

$nagvis->openSite();
if(!file_exists($cfg_base.$map.".cfg")) //Prüfen ob *.cfg-Datei vorhanden ist!
{
	$nagvis->errorBox("file",$map,$user);
	$nagvis->closeSite();
	$nagvis->printSite();
}
elseif(!file_exists($map_base.$map.".png")) //Prüfen ob die Map vorhanden ist!
{
	$nagvis->errorBox("map",$map,$user);
	$nagvis->closeSite();
	$nagvis->printSite();
}
elseif("user" != "user") //Prüfen ob der User die Berechtigung besitzt die Map zu sehen!
{
	$nagvis->errorBox("permission",$map,$user);
	$nagvis->closeSite();
	$nagvis->printSite();
}
else
{
	$statusLog = $nagvis->readNagVisCfg($map);
	$nagvis->site[] = "<PRE>";
	$nagvis->site[] = print_r($statusLog);
	$nagvis->site[] = "<PRE>";
	$nagvis->closeSite();
	$nagvis->printSite();
	
}

?>