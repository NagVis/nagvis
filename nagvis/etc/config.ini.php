; <?php return 1; ?>
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
defaulticons="std_medium"
; rotate maps (0/1)
rotatemaps=0
; maps to rotate
maps="demo,demo2"
; show header (0/1)
displayheader=1
; options per line in header
headercount=3
; use gdlibs (if set to 0 lines will not work, all other types should work fine)
usegdlibs=1
; refresh time of pages
refreshtime=60

; options for the wui
[wui]
; auto update fequency
autoupdatefreq=25


; path options
[paths]
; absolute physical NagVis path
base="/usr/local/nagios/share/nagvistest/nagvis/"
; absolute physical NagVis cfg path
cfg="/usr/local/nagios/share/nagvistest/nagvis/etc/"
; absolute physical NagVis iconset path
icon="/usr/local/nagios/share/nagvistest/nagvis/iconsets/"
; absolute physical NagVis maps path
map="/usr/local/nagios/share/nagvistest/nagvis/maps/"
; absolute physical NagVis maps cfg path
mapcfg="/usr/local/nagios/share/nagvistest/nagvis/etc/maps/"
; absolute html NagVis path
htmlbase="/org/ti-sysmon/nagios/nagvistest/nagvis"
; absolute html NagVis cgi path
htmlcgi="/org/ti-sysmon/nagios/cgi-bin"
; absolute html NagVis icon path
htmlicon="/org/ti-sysmon/nagios/nagvistest/nagvis/iconsets/"
; absolute html NagVis maps path
htmlmap="/org/ti-sysmon/nagios/nagvistest/nagvis/maps/"
; absolute html NagVis documentation path
htmldoku="http://luebben-home.de/nagvis-doku/nav.html?nagvis/"

; options for the NDO-Backend
[backend_ndo]
; hostname for NDO-db
dbhost="localhost"
; portname for NDO-db
dbport=3306
; database-name for NDO-db
dbname="db_nagios"
; username for NDO-db
dbuser="nagios"
; password for NDO-db
dbpass="g02nd0"
; prefix for tables in NDO-db
dbprefix="ndo_"
; instace-id for tables in NDO-db
dbinstanceid=1
; maximum delay of the NDO Database in Seconds
maxtimewithoutupdate=180


; options for the HTML-Backend
[backend_html]
; with this user, the script reads the CGIs
cgiuser="nagiosadmin"
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
debugstates=0
; debugCheckState on/off (0/1)
debugcheckstate=0
; debugFixIcon on/off (0/1)
debugfixicon=0

; Only internal informations
[internal]
; version of NagVis
version="0.9b3+"
; title of NagVis pages
title="NagVis 0.9b3+ (CVS Snapshot)"

; -------------------------
; EOF
; ------------------------
