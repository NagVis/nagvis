<?
########################## Global Options #############################

#backed defines the data source to use to get the Nagios states
# - html       -> Parse the Data from the Nagios cgis html output (slow and unrealiable but easy to install)
# - ndomy	   -> Get the Data from a Nagios NDO MySql DB (fast and stable but you need NDO) 
$backend="ndomy";
global,backend

#Include file for default page.
$indexInc="index.nagvis.inc";
includes,index

#Default Iconset. Used for the maps creation.
$defaultIcons="std_medium";
global,defaulticons

#Rotate Maps automatically.
$RotateMaps="0";
global,rotatemaps

#Maps to rotate. Example : array('server','lan','wan')
$maps=array("demo");
global,maps

#Display Header.
$Header="1";
global,displayHeader

#Number of links displayed in one header line.
$headerCount="3";
global,headercount

$Autoupdate_frequency="25";
$check_config="0";
global,checkconfig

#Language for the error boxes (currently "english,"frensh" and "german" are available)
$Language="english";
global,language

#Use GD-Libs to draw backend(0=No / 1=Yes)
#"Line" Objects are only available if set to "1", "0" can be used
#if you have problems with GD or if you need no lines.
$useGDLibs="1";
global,usegdlibs

#Name of the header include file.
$headerInc="header.nagvis.inc";
includes,header

#Local path (full) to NagVis Base Directory.
$Base="/usr/local/nagios/share/nagvis";
paths,base

#Path to NagVis HTTP Base URL.
$HTMLBase="/nagios/nagvis";
paths,htmlbase

####################### Options for the html Backend ############################
#The user Nagvis runs the CGI as (MUST be 'allowed for all services', 'allowed for all hosts')
$CgiUser="nagiosadmin";
backend_html,cgiuser

#Full local path to the directory containing the CGIs.
$CgiPath="/usr/local/nagios/sbin/";
backend_html,cgi

#Path to the CGIs via the Webserver
$HTMLCgiPath="/nagios/cgi-bin";
paths,htmlcgi

#Map refresh time (in seconds).
$RefreshTime="60";
global,refreshtime

#Local path to NagVis etc directory.
$cfgPath=$Base."/etc/";
paths,cfg

#Local path to the folder holding the map config files (*.cfg)
$cfgFolder=$Base."/etc/maps/";
paths,mapcfg

#Local path to the folder holding the maps background image files (*.png)
$mapFolder=$Base."/maps/";
paths,map

#Local path to NagVis iconsets directory.
$iconBaseFolder=$Base."/iconsets/";
paths,icon

#HTTP Path to the NagVis iconsets directory.
$iconHTMLBaseFolder=$HTMLBase."/iconsets/";
paths,htmlicon

#HTTP Path to the Map directory.;
$mapHTMLBaseFolder=$HTMLBase."/maps/";
paths,htmlmap

################## Options for the NDO My Backend ########################
# (NDO is brand new, please edit directly in the head of class.checkstate_ndomy.php)



#####################Internal versioning, dont change #####################
$version="0.9b-workshop2006+";
internal, version
$title="NagVis ".$version;
internal, title

########################## Options for Debbuging ##########################
# For set more Debug-Options, see ./etc/debug.inc.php
# Enable Debugging (0=No / 1=Yes)
$enableDebug="0";
global,enabledebug

#Url to NagVis-Doku
$HTMLBaseDoku="http://luebben-home.de/nagvis-doku/nav.html?nagvis/";
paths,htmldoku

################################# EOF #####################################
#Please make shure that there are NO whitespaces after the ">". ">" MUST be the last char in this file!
?>
