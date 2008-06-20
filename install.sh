#!/bin/bash
###############################################################################
#
# install.sh - Installs/Updates NagVis
#
# Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
#
# Developement:
#  Wolfgang
#  Lars Michelsen <lars@vertical-visions.de>
#
# License:
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License version 2 as
# published by the Free Software Foundation.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
#
###############################################################################

# Some initializations
###############################################################################

# Installer version
INSTALLER_VERSION="0.1"
# Default action
INSTALLER_ACTION="install"

# Default Nagios path
NAGIOS_PATH="/usr/local/nagios"
# Default Path to Graphviz binaries
GRAPHVIZ_PATH="/usr/local/bin"
# Version of NagVis to be installed
NAGVIS_VER=`cat nagvis/includes/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`
# Version of old NagVis (If update; Will be detected)
NAGVIS_VER_OLD=""
# Relative path to the NagVis configuration file
NAGVIS_CONF="etc/nagvis.ini.php"
# Default nagios share webserver path
HTML_PATH="/nagios"
# Saving current timestamp for backup when updating
DATE=`date +%s`
# Default webserver user
WEB_USER=""
# Default webserver group
WEB_GROUP=""

# Version prerequisites
NEED_PHP_VERSION=`cat nagvis/includes/defines/global.php | grep CONST_NEEDED_PHP_VERSION | awk -F"'" '{ print $4 }'`
# TODO: mbstring
NEED_PHP_MODULES="gd mysql"
NEED_GV_MOD="dot neato twopi circo fdp"
NEED_GV_VERSION=2.14

# Function definitions
###############################################################################

# Print usage
usage() {
cat <<EOD
Usage: $0 [OPTIONS]
Installs or updates NagVis on your system.

Parameters:
  -n <PATH>   Path to Nagios directory. The default value is /usr/local/nagios
  -g <PATH>   Path to graphviz binaries. The default value is /usr/local
  -u <USER>   User which runs the webserver
  -g <GROUP>  Group which runs the webserver
  -h          This message

EOD
}

# Ask user for confirm
confirm() {
	echo -n "| $1 [$2]: "
	read ANS
	ANS=`echo $ANS | tr "jy" "YY"`
	[ -z $ANS ] && ANS="Y"
	if [ "$ANS" != "Y" ]; then
		echo "|"
		echo "| Installer aborted, exiting..."
		echo "+------------------------------------------------------------------------------+"
		exit 1
	fi
}

# Print welcome message
welcome() {
cat <<EOD
+------------------------------------------------------------------------------+
| Welcome to NagVis Installer $INSTALLER_VERSION                                              |
+------------------------------------------------------------------------------+
| This program is built to facilitate the NagVis installation and update       |
| procedure for you. The installer has been tested on the following systems:   |
| - Debian Etch (4.0)                                                          |
| - Ubuntu Hardy (8.04)                                                        |
| - SuSE Linux Enterprise Server 10                                            |
|                                                                              |
| When you experience some problems using this on another distribution, please |
| report that to the NagVis team.                                              |
+------------------------------------------------------------------------------+
EOD
confirm "Do you want to proceed?" "y"
}

# Print module state, exit if necessary
log() {
	if [ -z "$2" ]; then
		printf "%-71s %s\n" "| $1" "MISSING"
		exit 1
	else
		printf "%-73s %s\n" "| $1" "found"
	fi
}
 
# Check Apache PHP module
check_apache_php() {
	DIR=$1
	[ ! -d $DIR ] && return
	MODPHP=`grep -rie "mod_php.*\.so" -e "libphp.*\.so" $DIR | tr -s " " | cut -d" " -f3 | uniq`
	USER=`grep -ri "^User" $DIR | cut -d" " -f2 | uniq`
	GROUP=`grep -ri "^Group" $DIR | cut -d" " -f2 | uniq`
	HTML_PATH=`grep -ri "^Alias" $DIR | grep -i "/nagios" | cut -d" " -f2 | uniq` 
}

# Check Graphviz version by installed system package
check_graphviz_version() {
  if [ "${PKG##/*/}" = "dpkg" ]; then
    GRAPHVIZ_VER=`$PKG -l "graphviz" | grep "graphviz" | grep ii | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
  else
    GRAPHVIZ_VER=`$PKG -qa "graphviz" | sed "s/graphviz-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
  fi

  log "Graphviz $GRAPHVIZ_VER" $GRAPHVIZ_VER

  if [ `echo "$1 $GRAPHVIZ_VER" | awk '{if ($1 > $2) print $1; else print $2}'` = $1 ]; then
    echo "|  Error: Version >= $1 needed"
    exit 1
  fi
}

# Check Graphviz Modules
check_graphviz_modules() {
	for MOD in $1
	do
		TMP=`which $MOD`
		[ -z "$TMP" ] && TMP=`which $GRAPHVIZ_PATH/$MOD`
		GV_MOD_VER=`$MOD -V 2>&1`
		GV_MOD_VER=${GV_MOD_VER#*version }
		GV_MOD_VER=${GV_MOD_VER% (*}
		
		log "  Graphviz Module $MOD $GV_MOD_VER" $TMP
		
		if [ `echo "$2 $GV_MOD_VER" | awk '{if ($1 > $2) print $1; else print $2}'` = $2 ]; then
			echo "|  Error: Version >= $2 needed"
			exit 1
		fi
done
}

# Check PHP Version
check_php_version() {
	if [ "${PKG##/*/}" = "dpkg" ]; then
		PHP_VER=`$PKG -l "php[0-9]" | grep "php" | grep ii | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
	else
		PHP_VER=`$PKG -qa "php[0-9]" | sed "s/php[0-9]\-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
	fi

	log "PHP $PHP_VER" $PHP_VER
	
	if [ `echo "$1 $PHP_VER" | awk '{if ($1 > $2) print $1; else print $2}'` = $1 ]; then
		echo "|  Error: Version >= $1 needed"
		exit 1
	fi
}

# Check PHP modules
check_php_modules() {
	for MOD in $1
	do
		if [ "${PKG##/*/}" = "dpkg" ]; then
			MOD_VER=`$PKG -l "php[0-9]-$MOD" | grep "php" | grep "ii" | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
		else
			MOD_VER=`$PKG -qa "php[0-9]-$MOD" | sed "s/php[0-9]\-$MOD-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
		fi
		
		TMP=`grep -rie "$MOD" /etc/php? | cut -d"=" -f2`
    log "  Module: $MOD $MOD_VER" $MOD

		if [ -n $MOD_VER ]; then
			if [ `echo "$2 $MOD_VER" | awk '{if ($1 > $2) print $1; else print $2}'` = $2 ]; then
				echo "|  Error: Version >= $2 needed"
				exit 1
			fi
		fi
	done
}

# Check return code
chk_rc() {
	RC=$?
	if [ $RC -ne 0 ]; then
		echo $* Return Code: $RC
		exit 1
	fi
}

# Main program starting
###############################################################################

# Process command line options
while [ $# -gt 0 ]; do
  case $1 in
    -n)
      NAGIOS_PATH=$2
      shift 2
      ;;
    -v)
      GRAPHVIZ_PATH=$2
      shift 2
      ;;
    -u)
      WEB_USER=$2
      shift 2
      ;;
    -g)
      WEB_GROUP=$2
      shift 2
      ;;
    -h|--help)
      usage;
      exit 0
      ;;
    --)
      shift
      break
      ;;
    *)
      echo "Error: Unknown option: $1"
      usage;
      exit 1
      ;;
  esac
done

# Print welcome message
welcome

# Start gathering informations
echo "+------------------------------------------------------------------------------+"
echo "| Starting installation of NagVis $NAGVIS_VER"
echo "+------------------------------------------------------------------------------+"
echo "|"
echo "+--- Checking for packet manager ----------------------------------------------+"
PKG=`which rpm`
[ -u $PKG ] && PKG=`which dpkg`
log "Packet manager $PKG" $PKG

if [ -z "$NAGIOS_PATH" ]; then
	echo -n "| Please enter the Nagios base dir [$NAGIOS_PATH]: "
	read ADST
	[ ! -z $ADST ] && NAGIOS_PATH=$ADST
fi

echo "|"
echo "+--- Checking prerequisites ---------------------------------------------------+"

# Check Nagios
if [ -d $NAGIOS_PATH ]; then NAGIOS=`$NAGIOS_PATH/bin/nagios --version | grep Nagios 2>&1`
	log "$NAGIOS" $NAGIOS
else
	echo "| Nagios home $NAGIOS_PATH is missing. Aborting..."
	exit 1
fi
NAGVER=`echo $NAGIOS | cut -d" " -f2 | cut -c1,1`

# Check NDO
NDO=`$NAGIOS_PATH/bin/ndo2db-${NAGVER}x --version | grep -i "^NDO2DB" 2>/dev/null`
log "$NDO" $NDO

# Check PHP Version
check_php_version $NEED_PHP_VERSION

# Check PHP Modules
check_php_modules "$NEED_PHP_MODULES" "$NEED_PHP_VERSION"

# Check Apache PHP Module
check_apache_php "/etc/apache2/"
check_apache_php "/etc/apache/"
check_apache_php "/etc/http/"
log "  Apache mod_php" $MODPHP

# Check Graphviz
check_graphviz_version $NEED_GV_VERSION

# Check Graphviz Modules
check_graphviz_modules "$NEED_GV_MOD" $NEED_GV_VERSION

echo "|"
echo "+--- Trying to detect Apache settings -----------------------------------------+"

HTML_PATH=${HTML_PATH%/}
WEB_USER=${WEB_USER:-$USER}
WEB_GROUP=${GRP:-$GROUP}
echo -n "| Please enter the name of the web-server user [$WEB_USER]: "
read AUSR
if [ ! -z $AUSR ]; then
	WEB_USER=$AUSR
q
fi
echo -n "| Please enter the name of the web-server group [$WEB_GROUP]: "
read AGRP
if [ ! -z $AGRP ]; then
	WEB_GROUP=$AGRP
fi

if [ ! `getent passwd | cut -d':' -f1 | grep $WEB_USER` = "$WEB_USER" ]; then
	echo "|  Error: User $WEB_USER not found."
	exit 1
fi

if [ ! `getent group | cut -d':' -f1 | grep $WEB_GROUP` = "$WEB_GROUP" ]; then
  echo "|  Error: Group $WEB_GROUP not found."
  exit 1
fi

echo "|"
echo "+--- Checking for existing NagVis ---------------------------------------------+"

NAGVIS_PATH=${NAGIOS_PATH%/}/share/nagvis
if [ -d $NAGVIS_PATH ]; then
	INSTALLER_ACTION="update"
	
	if [ -e $NAGVIS_PATH/nagvis/includes/defines/global.php ]; then
		NAGVIS_VER_OLD=`cat $NAGVIS_PATH/nagvis/includes/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`
	else
		NAGVIS_VER_OLD="UNKNOWN"
	fi
	
	NAGVIS_PATH_OLD=$NAGVIS_PATH.old-$DATE

	log "NagVis $NAGVIS_VER_OLD" $NAGVIS_VER_OLD
fi

echo "|"
echo "+------------------------------------------------------------------------------+"
echo "| Summary"
echo "+------------------------------------------------------------------------------+"
echo "| NagVis home will be:           $NAGVIS_PATH"
echo "| Owner of NagVis files will be: $WEB_USER"
echo "| Group of NagVis files will be: $WEB_GROUP"
echo "| "
echo "| Installation mode:             $INSTALLER_ACTION"
if [ "$INSTALLER_ACTION" = "update" ]; then
  echo "| Old version:                   $NAGVIS_VER_OLD"
  echo "| New version:                   $NAGVIS_VER"
	echo "| Backup directory:              $NAGVIS_PATH_OLD"
	echo "| "
	echo "| Note: The current NagVis directory will be moved to the backup directory."
	if [ ! "$NAGVIS_VER_OLD" = "UNKNOWN" ]; then
		echo "|       Your configuration files will be migrated."
	else
		echo "|"
		echo "|          !!! UPDATE FROM VERSION \"$NAGVIS_VER_OLD\" IS NOT SUPPORTED !!!"
		echo "|       You have to move your custom files manually from backup directory."
	fi
fi
echo "|"

confirm "Do you really want to continue?" "y"

echo "+------------------------------------------------------------------------------+"
echo "| Starting installation"
echo "+------------------------------------------------------------------------------+"

if [ "$INSTALLER_ACTION" = "update" ]; then
	echo "+--- Moving old NagVis to  $NAGVIS_PATH_OLD"
	mv $NAGVIS_PATH $NAGVIS_PATH_OLD
	chk_rc "| Error moving old NagVis $NAGVIS_PATH_OLD"
fi

if [ ! -d $NAGVIS_PATH ]; then
	echo "+--- Creating directory $NAGVIS_PATH"
	mkdir -p $NAGVIS_PATH
	chk_rc "| Error creating directory $NAGVIS_PATH"
fi


echo "+--- Copying files to $NAGVIS_PATH"
GLOBIGNORE="install.sh"
cp -pr * $NAGVIS_PATH
GLOBIGNORE=""
chk_rc "| Error copying files to $NAGVIS_PATH"

if [ "$INSTALLER_ACTION" = "update" -a ! "$NAGVIS_VER_OLD" = "UNKNOWN" ]; then
  echo "+--- Restoring main configuration file"
	cp -p $NAGVIS_PATH_OLD/$NAGVIS_CONF $NAGVIS_PATH/$NAGVIS_CONF
	chk_rc "| Error copying main configuration file"
	
	echo "+--- Restoring custom map configuration files"
	GLOBIGNORE="demo.cfg:demo2.cfg"
	cp -p $NAGVIS_PATH_OLD/etc/maps/* $NAGVIS_PATH/etc/maps
	GLOBIGNORE=""
	chk_rc "| Error copying map configuration files"
	
	echo "+--- Restoring custom map images"
	GLOBIGNORE="nagvis-demo.png"
	cp -p $NAGVIS_PATH_OLD/nagvis/images/maps/* $NAGVIS_PATH/nagvis/images/maps
	GLOBIGNORE=""
	chk_rc "| Error copying map image files"
	
	echo "+--- Restoring custom iconsets"
	
	GLOBIGNORE="20x20.png:configerror_*.png:error.png:std_*.png"
	cp -p $NAGVIS_PATH_OLD/nagvis/images/iconsets/* $NAGVIS_PATH/nagvis/images/iconsets
	GLOBIGNORE=""
	chk_rc "| Error copying iconset files"
	
	echo "+--- Restoring custom shapes"
	cp -p $NAGVIS_PATH_OLD/nagvis/images/shapes/* $NAGVIS_PATH/nagvis/images/shapes
	chk_rc "| Error copying shapes"
	
	echo "+--- Restoring custom templates (header, hover)"
	GLOBIGNORE="tmpl.default*"
	cp -p $NAGVIS_PATH_OLD/nagvis/templates/header/* $NAGVIS_PATH/nagvis/templates/header
	chk_rc "| Error copying header templates"
	cp -p $NAGVIS_PATH_OLD/nagvis/templates/hover/* $NAGVIS_PATH/nagvis/templates/hover
	GLOBIGNORE=""
	chk_rc "| Error copying hover templates"
	
	echo "+--- Restoring custom template images (header, hover)"
	GLOBIGNORE="tmpl.default*"
	cp -p $NAGVIS_PATH_OLD/nagvis/images/templates/header/* $NAGVIS_PATH/nagvis/images/templates/header
	GLOBIGNORE=""
	chk_rc "| Error copying header template images"
	
	GLOBIGNORE="tmpl.default*"
  cp -p $NAGVIS_PATH_OLD/nagvis/images/templates/hover/* $NAGVIS_PATH/nagvis/images/templates/hover
	GLOBIGNORE=""
	chk_rc "| Error copying hover template images"
fi

echo "+--- Setting permissions"
chown $WEB_USER:$WEB_GROUP $NAGVIS_PATH -R
chmod 664 $NAGVIS_PATH/$NAGVIS_CONF-sample
chmod 775 $NAGVIS_PATH/nagvis/images/maps
chmod 664 $NAGVIS_PATH/nagvis/images/maps/*
chmod 775 $NAGVIS_PATH/etc/maps
chmod 664 $NAGVIS_PATH/etc/maps/*
if [ -d $NAGVIS_PATH/nagvis/var ]; then
	chmod 775 $NAGVIS_PATH/nagvis/var
	chmod 664 $NAGVIS_PATH/nagvis/var/*
fi

if [ ! -f $NAGVIS_PATH/$NAGVIS_CONF ]; then
	echo "+--- Creating main configuration file"
	cp -p $NAGVIS_PATH/${NAGVIS_CONF}-sample $NAGVIS_PATH/$NAGVIS_CONF
	chk_rc "| Error copying sample configuration"
fi

echo "|"
echo "+------------------------------------------------------------------------------+"
printf "%-78s %s|\n" "| Installation complete"
echo "+------------------------------------------------------------------------------+"
printf "%-78s %s|\n" "| You can savely remove this source directory."
printf "%-78s %s|\n" "|"
printf "%-78s %s|\n" "| What to do next?"
printf "%-78s %s|\n" "| - Read the documentation"
printf "%-78s %s|\n" "| - Maybe you want to edit $NAGVIS_PATH/$NAGVIS_CONF"
printf "%-78s %s|\n" "| - Configure NagVis by Browser <http://localhost${HTML_PATH}/nagvis/config.php>"
echo "+------------------------------------------------------------------------------+"
exit 0
