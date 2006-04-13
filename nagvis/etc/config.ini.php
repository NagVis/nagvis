; <? return 1; ?>
; the line above is to prevent 
; viewing this file from web.
; DON'T REMOVE IT!

; ----------------------------
; NagVis Configuration File
; ----------------------------

; global options
[global]
; select backend to use (ndomy,html)
; html -> pulls the data from the Nagios Webinterface (slow and unreliable but
;		  easy to use)
; ndomy -> gets the data from a Nagios NDO MySQL Database (fast an reliable 
;          but NDO is needed)
backend="ndomy"
; select language (english,german,...)
language="english"
; default icons
defaultIcons="std_medium"
; rotate maps (0/1)
rotateMaps=0
; maps to rotate
maps=demo,demo2
; show header (0/1)
displayHeader=1
; options per line in header
headerCount=3
; config check on startup
checkConfig=0
; use gdlibs (if set to 0 lines will not work, all other types should work fine)
useGDLibs=1
; refresh time of pages
refreshTime=60

; options for the wui
[wui]
; auto update fequency
autoUpdateFreq=25


; path options
[paths]
; absolute physical NagVis path
base="/usr/local/nagios/share/nagvis/"
; absolute physical NagVis cfg path
cfg="/usr/local/nagios/share/nagvis/etc/"
; absolute physical NagVis iconset path
icon="/usr/local/nagios/share/nagvis/iconsets/"
; absolute physical NagVis maps path
map="/usr/local/nagios/share/nagvis/maps/"
; absolute physical NagVis maps cfg path
mapCfg="/usr/local/nagios/share/nagvis/etc/maps/"
; absolute html NagVis path
htmlBase="/nagios/nagvis"
; absolute html NagVis cgi path
htmlCgi="/nagios/cgi-bin"
; absolute html NagVis icon path
htmlIcon="/nagios/nagvis/iconsets/"
; absolute html NagVis maps path
htmlMap="/nagios/nagvis/maps/"
; absolute html NagVis documentation path
htmlDoku="http://luebben-home.de/nagvis-doku/nav.html?nagvis/"

; options for the NDO-Backend
[backend_ndo]
; hostname for NDO-db
dbHost=localhost
; portname for NDO-db
dbPort=3306
; database-name for NDO-db
dbName=nagios
; username for NDO-db
dbUser=nagios
; password for NDO-db
dbPass=password
; prefix for tables in NDO-db
dbPrefix=ndo_
; instace-id for tables in NDO-db
dbInstanceId=1

; options for the HTML-Backend
[backend_html]
; with this user, the script reads the CGIs
cgiUser="nagiosadmin"
; physical path to the CGIs
cgi="/usr/local/nagios/sbin/"

; usualy not needed
; include options
[includes]
; header include
header="header.nagvis.inc"
; index include
index="index.nagvis.inc"

;debuggin options
[debug]
; debug on/off (0/1)
debug=0
; debugStates on/off (0/1)
debugStates=0
; debugCheckState on/off (0/1)
debugCheckState=0
; debugFixIcon on/off (0/1)
debugFixIcon=0

; Only internal informations
[internal]
; version of NagVis
version="0.9b2"
; title of NagVis pages
title="NagVis 0.9b2"

; -------------------------
; EOF
; -------------------------