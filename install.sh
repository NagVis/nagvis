#!/bin/bash
###############################################################################
#
# install.sh - Installs/Updates NagVis
#
# Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
#
# Development:
#  Wolfgang Nieder
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
INSTALLER_VERSION="0.1.7"
# Default action
INSTALLER_ACTION="install"
# Be quiet? (Enable/Disable confirmations)
INSTALLER_QUIET=0
# Should the installer change config options when possible?
INSTALLER_CONFIG_MOD="n"

# Default Nagios path
NAGIOS_PATH="/usr/local/nagios"
# Default Path to Graphviz binaries
GRAPHVIZ_PATH="/usr/local/bin"
# Default Path to NagVis base
NAGVIS_PATH=""
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
[ -z "$NEED_PHP_VERSION" ] && NEED_PHP_VERSION="5.0"

NEED_PHP_MODULES="gd mysql mbstring gettext session xml"
NEED_GV_MOD="dot neato twopi circo fdp"
NEED_GV_VERSION=2.14

LINE_SIZE=78
GREP_INCOMPLETE=0

# Function definitions
###############################################################################

# Print usage
usage() {
cat <<EOD
Usage: $0 [OPTIONS]
Installs or updates NagVis on your system.

Parameters:
  -n <PATH>   Path to Nagios directory. The default value is $NAGIOS_PATH
  -b <PATH>   Path to graphviz binaries. The default value is $GRAPHVIZ_PATH
  -p <PATH>   Path to NagVis base directory. The default value is $NAGIOS_PATH/share/nagvis
  -u <USER>   User who runs the webserver
  -g <GROUP>  Group who runs the webserver
  -c [y|n]    Update configuration files when possible?
  -q          Quiet mode. The installer won't ask for confirmation of what to do.
              This can be useful for automatic or scripted deployment.
              WARNING: Only use this if you know what you are doing
  -v          Version information
  -h          This message

EOD
}

# Print version information
version() {
cat <<EOD
NagVis installer, version $INSTALLER_VERSION
Copyright (C) 2004-2009 NagVis Project

License: GNU General Public License version 2

Development:
- Wolfgang Nieder
- Lars Michelsen <lars@vertical-visions.de>

EOD
}

# Print line
line() {
	DASHES="--------------------------------------------------------------------------------------------------------------"
	SIZE2=`expr $LINE_SIZE - 4`
	if [ -z "$1" ]; then
		printf "+%${LINE_SIZE}.${LINE_SIZE}s+\n" $DASHES
	else
		if [ -z "$2" ]; then
			printf "+--- %s\n" "$1"
		else
			printf "+--- %${SIZE2}.${SIZE2}s+\n" "$1 $DASHES"
		fi
	fi
}
# Print text
text() {
	SIZE2=`expr $LINE_SIZE - 3`
	if [ -z "$1" ]; then
		printf "%s%${LINE_SIZE}s%s\n" "|" "" "|"
	else
		if [ -z "$2" ]; then
			printf "%s\n" "$1"
		else
			printf "%-${LINE_SIZE}.${LINE_SIZE}s %s\n" "$1" "$2"
		fi
	fi
}

# Ask user for confirmation
confirm() {
	echo -n "| $1 [$2]: "
	read ANS
	ANS=`echo $ANS | tr "jy" "YY"`
	[ -z $ANS ] && ANS="Y"
	if [ "$ANS" != "Y" ]; then
		text
		text "| Installer aborted, exiting..." "|"
		line ""
		exit 1
	fi
}

# Print welcome message
welcome() {
cat <<EOD
+------------------------------------------------------------------------------+
| Welcome to NagVis Installer $INSTALLER_VERSION                                            |
+------------------------------------------------------------------------------+
| This script is built to facilitate the NagVis installation and update        |
| procedure for you. The installer has been tested on the following systems:   |
| - Debian Etch (4.0)                                                          |
| - Ubuntu Hardy (8.04)                                                        |
| - SuSE Linux Enterprise Server 10                                            |
|                                                                              |
| Similar distributions to the ones mentioned above should work as well.       |
| That (hopefully) includes RedHat, Fedora, CentOS, OpenSuSE                   |
|                                                                              |
| If you experience any problems using these or other distributions, please    |
| report that to the NagVis team.                                              |
+------------------------------------------------------------------------------+
EOD
if [ $INSTALLER_QUIET -ne 1 ]; then
	confirm "Do you want to proceed?" "y"
fi
}

# Print module state, exit if necessary
log() {
	SIZE=`expr $LINE_SIZE - 8` 
	if [ -z "$2" ]; then
		printf "%-${SIZE}s %s\n" "| $1" "MISSING |"
		exit 1
	elif [ "$2" = "needed" ]; then
		echo "$1 needed"
		exit 1
	elif [ "$2" = "warning" ]; then
		printf "%-${LINE_SIZE}s |\n" "| $1"
	else	
		printf "%-${SIZE}s %s\n" "| $1" "  found |"
	fi
}
 
# Check Apache PHP module
check_apache_php() {
	DIR=$1
	[ ! -d $DIR ] && return
	
	# The apache user/group are defined by env vars in Ubuntu, set them here
	[ -f $DIR/envvars ] && source $DIR/envvars
	
	MODPHP=`find $DIR -type f -exec grep -ie "mod_php.*\.so" -e "libphp.*\.so" {} \; | tr -s " " | cut -d" " -f3 | uniq`
	HTML_PATH=`find $DIR -type f -exec grep -i "^Alias" {} \; | cut -d" " -f2 | grep -i "/nagios[/]\?$"  | uniq` 
	HTML_ANZ=`find $DIR -type f -exec grep -i "^Alias" {} \; | cut -d" " -f2 | grep -i "/nagios[/]\?$"  | wc -l` 
	
	# Only try to detect user when not set or empty
	if [ -z "$WEB_USER" ]; then
		WEB_USER=`find $DIR -type f -exec grep -i "^User" {} \; | cut -d" " -f2 | uniq`
		VAR=`echo $WEB_USER | grep "$" >/dev/null 2>&1`
		[ $? -eq 0 ] && WEB_USER=`eval "echo $WEB_USER"`
	fi

	# Only try to detect group when not set or empty
	if [ -z "$WEB_GROUP" ]; then
		WEB_GROUP=`find $DIR -type f -exec grep -i "^Group" {} \; | cut -d" " -f2 | uniq`
		VAR=`echo $WEB_GROUP | grep "$" >/dev/null 2>&1`
		[ $? -eq 0 ] && WEB_GROUP=`eval "echo $WEB_GROUP"`
	fi
}

# Check Graphviz version by installed system package
check_graphviz_version() {
	if [ "${PKG##/*/}" = "dpkg" ]; then
		GRAPHVIZ_VER=`$PKG -l "graphviz" | grep "graphviz" | grep ii | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
	else
		GRAPHVIZ_VER=`$PKG -qa "graphviz" | sed "s/graphviz-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
	fi

	if [ -z "$GRAPHVIZ_VER" ]; then
		log "WARNING: The Graphviz package was not found." "warning"
		log "         This may not be a problem if you installed it from source" "warning"
	else 
		log "Graphviz $GRAPHVIZ_VER" $GRAPHVIZ_VER
		if [ `echo "$1 $GRAPHVIZ_VER" | awk '{if ($1 > $2) print $1; else print $2}'` = $1 ]; then
			log "|  Error: Version >= $1" "needed"
		fi
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
			log "|  Error: Version >= $2" "needed"
		fi
	done
}

# Check PHP Version
check_php_version() {
	if [ "${PKG##/*/}" = "dpkg" ]; then
		PHP_VER=`$PKG -l "php[0-9]" 2>/dev/null | grep "php" | grep ii | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
	else
		PHP_VER=`$PKG -qa "php[0-9]" | sed "s/php[0-9]\-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
	fi
	PHP=`which php`
	if [ -z "$PHP_VER" ]; then
		if [ -s "$PHP" -a -x "$PHP" ]; then
			PHP_VER=`$PHP -v | head -1 | sed -e "s/PHP \([0-9\]\+\.[0-9\]\+\).*/\1/"`
		fi
	fi
	log "PHP $PHP_VER" $PHP_VER
	
	if [ `echo "$1 $PHP_VER" | awk '{if ($1 > $2) print $1; else print $2}'` = $1 ]; then
		log "|  Error: Version >= $1" "needed"
	fi
}

# Check PHP modules
check_php_modules() {
	for MOD in $1
	do
		if [ "${PKG##/*/}" = "dpkg" ]; then
			MOD_VER=`$PKG -l "php[0-9]-$MOD" 2>/dev/null | grep "php" | grep "ii" | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
		else
			MOD_VER=`$PKG -qa "php[0-9]?-$MOD" | sed "s/php[0-9]?\-$MOD-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
		fi

		# maybe compiled in module
		if [ -s "$PHP" -a -x "$PHP" ]; then
			TMP=`$PHP -m | grep -i "^$MOD$"`
			[ -z "$MOD_VER" -a -n "$TMP" ]&&MOD_VER="compiled_in"
		fi
		
		if [ -z "$TMP" ]; then
			TMP=`find /etc/ -type f -name "php?" -exec grep -ie "$MOD" {} \; | cut -d"=" -f2`
		fi
		
		# RedHat: /etc/php.d/...
		if [ -z "$TMP" -a -d /etc/php.d/ ]; then
			TMP=`grep -ie "$MOD" /etc/php.d/* | cut -d"=" -f2`
		fi
		
		# Ubuntu: /etc/php5/conf.d
		if [ -z "$TMP" -a -d /etc/php5/conf.d ]; then
			TMP=`grep -ie "extension=$MOD" /etc/php5/conf.d/* | sed 's/.*=//;s/\.so*//'`
		fi
		
		log "  Module: $MOD $MOD_VER" $TMP

		if [ -n "$MOD_VER" ]; then
			if [ `echo "$2 $MOD_VER" | awk '{if ($1 > $2) print $1; else print $2}'` = $2 ]; then
				log "  WARNING: Module $MOD not found." "warning"
				log "           This may not be a problem. You can ignore this if your php" "warning"
				log "           was compiled with the module included" "warning"
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
	else
		if [ "$2" != "" ]; then
			echo "$2"
		fi
	fi
}

copy() {
	GLOB_IGNORE="$1"
	[ -n "$LINE" ] && line "$LINE"
	if [ -f "$NAGVIS_PATH_OLD/$2" ]; then
		cp -p $NAGVIS_PATH_OLD/$2 $NAGVIS_PATH/$2
		chk_rc "|  Error copying file $3" "| done"
	fi
	if [ -d "$NAGVIS_PATH_OLD/$2" -a ! -d "$3" ]; then
		ANZ=`find $NAGVIS_PATH_OLD/$2 -type f | wc -l`
		if [ $ANZ -gt 0 ]; then
			cp -pr $NAGVIS_PATH_OLD/$2/* $NAGVIS_PATH/$2
			chk_rc "|  Error copying $3" "| done"
		fi
	fi
	if [ -d "$3" ]; then
		cp -pr $2 $3
		chk_rc "|  Error copying $2 to $3" "| done"
	fi
	GLOB_IGNORE=""
	LINE=""
}

# Main program starting
###############################################################################

# Process command line options
if [ $# -gt 0 ]; then
	while getopts "n:p:u:b:g:c:hqv" options; do
		case $options in
			n)
				NAGIOS_PATH=$OPTARG
			;;
			b)
				GRAPHVIZ_PATH=$OPTARG
			;;
			p)
				NAGVIS_PATH=$OPTARG
			;;
			u)
				WEB_USER=$OPTARG
			;;
			g)
				WEB_GROUP=$OPTARG
			;;
			q)
				INSTALLER_QUIET=1
			;;
			c)
				INSTALLER_CONFIG_MOD=$OPTARG
			;;
			h)
				usage
				exit 0
			;;
			v)
				version
				exit 0
			;;
			*)
				echo "Error: Unknown option."
				usage
				exit 1
			;;
		esac
	done
fi

# Print welcome message
welcome

# Start gathering information
line ""
text "| Starting installation of NagVis $NAGVIS_VER" "|"
line ""
text 
line "Checking for packet manager" "+"
PKG=`which rpm`
[ -u $PKG ] && PKG=`which dpkg`
if [ -u $PKG ]; then
	log "No packet manager (rpm/dpkg) found. Aborting..."
	exit 1
fi
log "Using packet manager $PKG" $PKG

# checking grep option as non-Linux might not support "-r"
grep -r INSTALLER_VERSION install.sh >/dev/null 2>&1
if [ $? -ne 0 ]; then
	GREP_INCOMPLETE=1
	log "grep doesn't support option -r" "warning"
fi

text
line "Checking paths" "+"

# Get Nagios path
if [ $INSTALLER_QUIET -ne 1 ]; then
  echo -n "| Please enter the path to Nagios base directory [$NAGIOS_PATH]: "
  read APATH
  if [ ! -z $APATH ]; then
    NAGIOS_PATH=$APATH
  fi
fi

# Check Nagios path
if [ -d $NAGIOS_PATH ]; then
	log "Nagios path $NAGIOS_PATH" "found"
else
	echo "| Nagios home $NAGIOS_PATH is missing. Aborting..."
	exit 1
fi

# Set default NagVis path
[ -z $NAGVIS_PATH ] && NAGVIS_PATH=${NAGIOS_PATH%/}/share/nagvis

# Get NagVis path
if [ $INSTALLER_QUIET -ne 1 ]; then
	echo -n "| Please enter the path to NagVis base [$NAGVIS_PATH]: "
	read ABASE
	if [ ! -z $ABASE ]; then
		NAGVIS_PATH=$ABASE
	fi
fi

text
line "Checking prerequisites" "+"

# Check Nagios version
NAGIOS_BIN="$NAGIOS_PATH/bin/nagios"

if [ -f $NAGIOS_BIN ]; then
	NAGIOS=`$NAGIOS_PATH/bin/nagios --version | grep Nagios 2>&1`
	log "$NAGIOS" $NAGIOS
else
	log "Nagios binary $NAGIOS_BIN"
fi
NAGVER=`echo $NAGIOS | cut -d" " -f2 | cut -c1,1`

# Check NDO
NDO=`$NAGIOS_PATH/bin/ndo2db-${NAGVER}x --version | grep -i "^NDO2DB" 2>/dev/null`
# maybe somebody removed version information
[ -z "$NDO" ]&&NDO=`$NAGIOS_PATH/bin/ndo2db --version | grep -i "^NDO2DB" 2>/dev/null`
log "$NDO" $NDO

# Check PHP Version
check_php_version $NEED_PHP_VERSION

# Check PHP Modules
check_php_modules "$NEED_PHP_MODULES" "$NEED_PHP_VERSION"

# Check Apache PHP Module
check_apache_php "/etc/apache2/"
check_apache_php "/etc/apache/"
check_apache_php "/etc/http/"
check_apache_php "/etc/httpd/"
log "  Apache mod_php" $MODPHP
if [ $HTML_ANZ -gt 1 ]; then
	log "more than one alias found" "warning"
	echo $HTML_PATH
fi

# Check Graphviz
check_graphviz_version $NEED_GV_VERSION

# Check Graphviz Modules
check_graphviz_modules "$NEED_GV_MOD" $NEED_GV_VERSION

text
line "Trying to detect Apache settings" "+"

HTML_PATH=${HTML_PATH%/}

if [ $INSTALLER_QUIET -ne 1 ]; then
	echo -n "| Please enter the name of the web-server user [$WEB_USER]: "
	read AUSR
	if [ ! -z $AUSR ]; then
		WEB_USER=$AUSR
	fi
fi

if [ $INSTALLER_QUIET -ne 1 ]; then
	echo -n "| Please enter the name of the web-server group [$WEB_GROUP]: "
	read AGRP
	if [ ! -z $AGRP ]; then
		WEB_GROUP=$AGRP
	fi
fi

if [ ! `getent passwd | cut -d':' -f1 | grep "^$WEB_USER"` = "$WEB_USER" ]; then
	echo "|  Error: User $WEB_USER not found."
	exit 1
fi

if [ ! `getent group | cut -d':' -f1 | grep "^$WEB_GROUP"` = "$WEB_GROUP" ]; then
	echo "|  Error: Group $WEB_GROUP not found."
	exit 1
fi

text
line "Checking for existing NagVis" "+"

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

if [ "$INSTALLER_ACTION" = "update" ]; then
	if [ $INSTALLER_QUIET -ne 1 ]; then
		echo -n "| Do you want the installer to update your config files when possible [$INSTALLER_CONFIG_MOD]?: "
		read AMOD
		if [ ! -z $AMOD ]; then
			INSTALLER_CONFIG_MOD=$AMOD
		fi
	fi
fi

text
line ""
text "| Summary" "|"
line ""
text "| NagVis home will be:           $NAGVIS_PATH" "|"
text "| Owner of NagVis files will be: $WEB_USER" "|"
text "| Group of NagVis files will be: $WEB_GROUP" "|"
text
text "| Installation mode:             $INSTALLER_ACTION" "|"
if [ "$INSTALLER_ACTION" = "update" ]; then
	text "| Old version:                   $NAGVIS_VER_OLD" "|"
	text "| New version:                   $NAGVIS_VER" "|"
	text "| Backup directory:              $NAGVIS_PATH_OLD" "|"
	text
	text "| Note: The current NagVis directory will be moved to the backup directory." "|"
	if [ ! "$NAGVIS_VER_OLD" = "UNKNOWN" ]; then
		text "|       Your configuration files will be copied." "|"
		if [ "$INSTALLER_CONFIG_MOD" = "y" ]; then
			text "|       The configuration files will be updated if possible." "|"
		else
			text "|       The configuration files will NOT be updated. Please check the " "|"
			text "|       changelog for any changes which affect your configuration files." "|"
		fi
	else
		text
		text "|          !!! UPDATE FROM VERSION \"$NAGVIS_VER_OLD\" IS NOT SUPPORTED !!!" "|"
		text "|       You have to move your custom files manually from backup directory." "|"
	fi
fi
text

if [ $INSTALLER_QUIET -ne 1 ]; then
	confirm "Do you really want to continue?" "y"
fi

line ""
text "| Starting installation" "|"
line ""

if [ "$INSTALLER_ACTION" = "update" ]; then
	line "Moving old NagVis to $NAGVIS_PATH_OLD..."
	mv $NAGVIS_PATH $NAGVIS_PATH_OLD
	chk_rc "|  Error moving old NagVis $NAGVIS_PATH_OLD" "| done"
fi

if [ ! -d $NAGVIS_PATH ]; then
	line "Creating directory $NAGVIS_PATH..."
	mkdir -p $NAGVIS_PATH
	chk_rc "|  Error creating directory $NAGVIS_PATH" "| done"
fi

LINE="Copying files to $NAGVIS_PATH..."
copy "install.sh" '*' "$NAGVIS_PATH"

if [ "$INSTALLER_ACTION" = "update" -a "$NAGVIS_VER_OLD" != "UNKNOWN" ]; then
	LINE="Restoring main configuration file..."
	copy "" "$NAGVIS_CONF" "main configuration file"
	
	LINE="Restoring custom map configuration files..."
	copy "demo.cfg:demo2.cfg" "etc/maps" "map configuration files"
	
	LINE="Restoring custom map images..."
	copy "nagvis-demo.png" "nagvis/images/maps" "map image files"
	
	LINE="Restoring custom iconsets..."
	copy "20x20.png:configerror_*.png:error.png:std_*.png" "nagvis/images/iconsets" "iconset files"
	
	LINE="Restoring custom shapes..."
	copy "" "nagvis/images/shapes" "shapes"
	
	LINE="Restoring custom header templates..."
	copy "tmpl.default*" "nagvis/templates/header" "header templates"
	
	LINE="Restoring custom hover templates..."
	copy "tmpl.default*" "nagvis/templates/hover" "hover templates"
	
	LINE="Restoring custom header template images..."
	copy "tmpl.default*" "nagvis/images/templates/header" "header template images"

	LINE="Restoring custom hover template images..."
	copy "tmpl.default*" "nagvis/images/templates/hover" "hover template images"
fi

# Do some update tasks (Changing options, notify about deprecated options)
if [ "$INSTALLER_ACTION" = "update" -a "$NAGVIS_VER_OLD" != "UNKNOWN" ]; then
	line "Handling changed/removed options..."
	if [ "x`echo $NAGVIS_VER_OLD | grep '1.3'`" != "x" ]; then
		text "| Update from 1.3.x" "|"
		text
		line "Applying changes to main configuration file..."
		text "| oops, sorry, not implemented yet" "|"
		chk_rc "| Error" "| done"
		line "Applying changes to map configuration files..."
		text "| oops, sorry, not implemented yet" "|"
		chk_rc "| Error" "| done"
	else
		text "| Update from unhandled version $NAGVIS_VER_OLD" "|"
		text
		text "| HINT: Please check the changelog or the documentation for changes which" "|"
		text "|       affect your configuration files" "|"
	fi
fi

line "Setting permissions..." "+"
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
echo "| done"

if [ ! -f $NAGVIS_PATH/$NAGVIS_CONF ]; then
	line "Creating main configuration file..."
	cp -p $NAGVIS_PATH/${NAGVIS_CONF}-sample $NAGVIS_PATH/$NAGVIS_CONF
	chk_rc "|  Error copying sample configuration" "| done"
fi

text
line
text "| Installation complete" "|"
text
text "| You can savely remove this source directory." "|"
text
text "| What to do next?" "|"
text "| - Read the documentation" "|"
text "| - Maybe you want to edit the main configuration file?" "|"
text "|   Its location is: $NAGVIS_PATH/$NAGVIS_CONF" "|"
text "| - Configure NagVis via browser" "|"
text "|   <http://localhost${HTML_PATH}/nagvis/config.php>" "|"
line
exit 0
