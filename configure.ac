#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ(2.59)
AC_INIT(nagvis, 1.2, nagvis-devel@lists.sourceforge.net)

AC_PREFIX_DEFAULT(/usr/local/nagvis)

PKG_NAME=nagvis
PKG_VERSION="1.2"
PKG_HOME_URL="http://www.nagvis.org"
PKG_REL_DATE="19-06-2007-06"

# Change some defaults
test "$prefix" = NONE && prefix=/usr/local/nagvis

PRE=$prefix
BASE_DIR=$prefix
AC_SUBST(BASE_DIR)
SHARE_DIR="$PRE/share"
AC_SUBST(SHARE_DIR)
CFG_DIR="$PRE/etc"
AC_SUBST(CFG_DIR)
CFG_FILE="$CFG_DIR/nagvis.ini"
AC_SUBST(CFG_FILE)
VAR_DIR="$PRE/var"
AC_SUBST(VAR_DIR)

# Figure out how to invoke "install" and what install options to use.

AC_PROG_INSTALL
AC_SUBST(INSTALL)

# Checks for programs.
AC_PROG_CC
AC_PROG_MAKE_SET
AC_PATH_PROG([STRIP],[strip],[true])

AC_PATH_PROG(CP,cp)

# Optional: Individual Nagios user/group
#AC_ARG_WITH(nagios_user,AC_HELP_STRING([--with-nagios-user=<user>], [sets user name to run nagios]),nagios_user=$withval,nagios_user=nagios)
#AC_ARG_WITH(nagios_group,AC_HELP_STRING([--with-nagios-group=<grp>], [sets group name to run nagios]),nagios_grp=$withval,nagios_grp=nagios)
#AC_SUBST(nagios_user)
#AC_SUBST(nagios_grp)
#INSTALL_OPTS="-o $nagios_user -g $nagios_grp"
#AC_SUBST(INSTALL_OPTS)
# Optional: Individual webserver user/group
AC_ARG_WITH(web-user,AC_HELP_STRING([--with-web-user=<user>], [sets user name the webserver is started with (Default: wwwrun)]),webserver_user=$withval,webserver_user=wwwrun)
AC_ARG_WITH(web-group,AC_HELP_STRING([--with-web-group=<grp>], [sets group name the webserver is started with (Default: www)]),webserver_grp=$withval,webserver_grp=www)
AC_SUBST(webserver_user)
AC_SUBST(webserver_grp)
INSTALL_OPTS="-o $webserver_user -g $webserver_grp"
AC_SUBST(INSTALL_OPTS)

# Optional: SHARE-URL
AC_ARG_WITH(htmurl,AC_HELP_STRING([--with-htmurl=<local-url>], [sets URL for public html (Default: /nagvis)]),SHARE_URL=$withval,SHARE_URL=/nagvis)
AC_SUBST(SHARE_URL)

# Optional: Nagios CGI-URL
AC_ARG_WITH(cgiurl,AC_HELP_STRING([--with-cgiurl=<local-url>], [sets URL for nagios cgi (Default: /nagios/cgi-bin)]),CGI_URL=$withval,CGI_URL=/nagios/cgi-bin)
AC_SUBST(CGI_URL)

# Optional: Without WUI
AC_ARG_WITH(wui,AC_HELP_STRING([--without-wui], [don't use the WUI (Configuration Utility)]),WUI=no,WUI=yes)
AC_SUBST(WUI)

# Optional: Debug Level
AC_ARG_WITH(debug-level,AC_HELP_STRING([--with-debug-level=<level>], [Enable debuging in NagVis (1: function beginning and ending, 2: progres informations in the functions, 4: render time) (Default: 0)]),DEBUG_LEVEL=$withval,DEBUG_LEVEL=0)
AC_SUBST(DEBUG_LEVEL)
if test $DEBUG_LEVEL -gt 1 ;then
DEBUG=1
AC_SUBST(DEBUG)
else
DEBUG=0
AC_SUBST(DEBUG)
fi
AC_SUBST(DEBUG_LEVEL)
# Optional: Debug-Log
AC_ARG_WITH(debug-log,AC_HELP_STRING([--with-debug-log=<logfile>], [path to the debug logfile (Default: $VAR_DIR/nagvis-debug.log)]),DEBUG_LOG=$withval,DEBUG_LOG=$VAR_DIR/nagvis-debug.log)
AC_SUBST(DEBUG_LOG)

# Check if this is an update (check for existing share folder)
AC_CHECK_FILE($SHARE_DIR,[NAGVIS_UPDATE="true"],[NAGVIS_UPDATE="false"])
# Check if this is an update (check for existing config.ini) 
AC_CHECK_FILE($CFG_FILE,[NAGVIS_UPDATE="true"],[NAGVIS_UPDATE="false"])
AC_SUBST(NAGVIS_UPDATE)

# Config files to create to
AC_CONFIG_FILES(Makefile etc/nagvis.ini-sample etc/nagvis-httpd.conf-sample share/nagvis/Makefile share/nagvis/index.php share/nagvis/draw.php share/nagvis/includes/defines/global.php share/wui/Makefile share/wui/index.php share/wui/wui.function.inc.php share/wui/ajax_handler.php)
AC_OUTPUT()

# Output after configure run
echo ""
AC_MSG_RESULT([*** Configuration summary for $PKG_NAME $PKG_VERSION $PKG_REL_DATE ***:])
echo ""
echo " General Options:"
echo " -------------------------"
AC_MSG_RESULT([     Webserver user/group:  $webserver_user,$webserver_grp])
AC_MSG_RESULT()
AC_MSG_RESULT([        Install \${prefix}:  $prefix])
AC_MSG_RESULT([               Config Dir:  $CFG_DIR])
AC_MSG_RESULT([                 HTML Dir:  $SHARE_DIR])
AC_MSG_RESULT([                 HTML URL:  $SHARE_URL])
AC_MSG_RESULT([              Install WUI:  $WUI])
if test "x$NAGVIS_UPDATE" = "xtrue"; then
AC_MSG_RESULT()
AC_MSG_RESULT([                Is Update:  Please backup your installation before "make install"!])
fi
if test "x$DEBUG" = "x1" ;then
AC_MSG_RESULT()
AC_MSG_RESULT([            Debug Enabled:  WARNING: Debug functions are enabled!])
AC_MSG_RESULT([                            The debug log will grow fast.])
AC_MSG_RESULT([              Debug Level:  $DEBUG_LEVEL])
AC_MSG_RESULT([                Debug Log:  $DEBUG_LOG])
fi
echo " -------------------------"
echo ""
echo "Review the options above for accuracy.  If they look okay,"
echo "type 'make all' to compile."
echo ""
