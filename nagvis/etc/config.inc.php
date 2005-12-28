<?
#state_class defines the parsing of the status data
# - parse       -> cgi-/html-parsing    (status.cgi)
# - dbpg        -> nagios-db parsing    (postgres)
# - dbmy        -> nagios-db parsing    (mysql)
# - xml         -> xml-parsing          (xml.cgi)
$StateClass="html";

#The user Nagvis runs the CGI as (MUST be 'allowed for all services', 'allowed for all hosts')
$CgiUser="nagiosadmin";

#Language for the error boxes (currently "english,"frensh" and "german" are available)
$Language="english";

#Local path (full) to NagVis Base Directory.
$Base="/usr/local/nagios/share/nagvis";

#Path to NagVis HTTP Base URL.
$HTMLBase="/nagios/nagvis";

#Full local path to the directory containing the CGIs.
$CgiPath="/usr/local/nagios/sbin/";

#Path to the CGIs via the Webserver
$HTMLCgiPath="/nagios/cgi-bin";

#Map refresh time (in seconds).
$RefreshTime="60";

#Local path to NagVis etc directory.
$cfgPath=$Base."/etc/";

#Local path to the folder holding the map config files (*.cfg)
$cfgFolder=$Base."/etc/maps/";

#Local path to the folder holding the maps background image files (*.png)
$mapFolder=$Base."/maps/";

#Local path to NagVis iconsets directory.
$iconBaseFolder=$Base."/iconsets/";

#HTTP Path to the NagVis iconsets directory.
$iconHTMLBaseFolder=$HTMLBase."/iconsets/";

#Include file for default page.
$indexInc="index.nagvis.inc";

#Default Iconset. Used for the maps creation.
$defaultIcons="std_medium";

#Rotate Maps automatically.
$RotateMaps="0";

#Maps to rotate. Example : array('server','lan','wan')
$maps=array("demo");

#Display Header.
$Header="1";

#Number of links displayed in one header line.
$headerCount="3";

$Autoupdate_frequency="25";
$check_config="0";

#Name of the header include file.
$headerInc="header.nagvis.inc";

#Internal versioning, dont change
$version="0.9b1+";
$title="NagVis ".$version;
#Please make shure that there are NO whitespaces after the ">". > MUST be the last char in this file!
?>
