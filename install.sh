#!/bin/bash
###############################################################################
#
# install.sh - Installs/Updates NagVis
#
# Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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

# Some initialisations
###############################################################################

# Default action
INSTALLER_ACTION="install"
# Be quiet? (Enable/Disable confirmations)
INSTALLER_QUIET=0
# Should the installer change config options when possible?
INSTALLER_CONFIG_MOD="y"
# files to ignore/delete
IGNORE_DEMO=""
# backends to use
NAGVIS_BACKENDS="mklivestatus,ndo2db,ido2db,merlinmy"
# data source
SOURCE=nagios
# skip checks
RC=0
FORCE=0
UNDO=0
ACONF="y"
REMOVE="n"
LOG=install.log
CALL="$0"
NAGVIS_PATH_PARAM_SET=0
NAGVIS_PATH_OLD_PARAM_SET=0

# Default Path to Graphviz binaries
GRAPHVIZ_PATH="/usr/local/bin"
# Version of NagVis to be installed
NAGVIS_VER=$(cat share/server/core/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }')
INSTALLER_VERSION=$NAGVIS_VER
# Version of old NagVis (will be detected if update)
NAGVIS_VER_OLD=""
# Relative path to the NagVis configuration file
NAGVIS_CONF="etc/nagvis.ini.php"
# Relative path to the NagVis SQLite auth database
NAGVIS_AUTH_DB="etc/auth.db"
# File for saving the old removed map permissions
AUTH_BACKUP="etc/auth-backup"
# Default nagios web conf
HTML_SAMPLE="etc/apache2-nagvis.conf-sample"
# Default nagios web conf
HTML_CONF="nagvis.conf"
# Saving current timestamp for backup when updating
DATE=$(date +%Y-%m-%d_%H:%M:%S)
# Web path to the NagVis base directory
HTML_PATH=""
# Path to webserver conf
WEB_PATH=""
# Default webserver user
WEB_USER=""
# Default webserver group
WEB_GROUP=""

# Version prerequisites
NEED_PHP_VERSION=$(cat share/server/core/defines/global.php | grep CONST_NEEDED_PHP_VERSION | awk -F"'" '{ print $4 }')
[ -z "$NEED_PHP_VERSION" ] && NEED_PHP_VERSION="5.0"

NEED_PHP_MODULES="gd mbstring gettext session xml pdo"
NEED_GV_MOD="dot neato twopi circo fdp"
NEED_GV_VERSION=2.14
NEED_SQLITE_VERSION=3.0

LINE_SIZE=78
GREP_INCOMPLETE=0

# Function definitions
###############################################################################

. install_lib

# format version string
fmt_version() {
    V=${1//a/.0.0}
    V=${V//b/.0.2}
    V=${V//rc/.0.4}
    if [ ${#V} -eq 3 ]; then
        V=${V}.0.60
    fi
    NV=""
    for S in "${V//./ }"; do
        NV="$NV$(printf %02d $S)"
    done
    while [ ${#NV} -lt 8 ]; do
        NV=${NV}0
    done
    echo $NV
}

# Print usage
usage() {
cat <<EOD
NagVis Installer $INSTALLER_VERSION
Installs or updates NagVis on your system.

Usage: $0 [OPTIONS]

General Parameters:
  -s <SOURCE>   Data source, defaults to Nagios, may be Icinga
  -n <PATH>     Path to Nagios/Icinga base directory (\$BASE)
                Default value: $NAGIOS_PATH
  -b <PATH>     Path to graphviz binaries ($NEED_GV_MOD)
                Default value: $GRAPHVIZ_PATH
  -p <PATH>     Path to NagVis base directory to install to
                Default value: $NAGVIS_PATH
  -O <PATH>     Path to the old NagVis base directory to update from.
                You only need to set this if it is different than the new NagVis base
                directory given in "-p". This may be useful when updating from 1.4
                to 1.6 where the paths to NagVis changed.
                Default value: $NAGVIS_PATH
  -W <PATH>     Web path to the NagVis base directory
                Default: $HTML_PATH 
  -u <USER>     User who runs the webserver
  -g <GROUP>    Group who runs the webserver
  -w <PATH>     Path to the webserver config files

  -i <BACKENDs> Comma separated list of backends to use:
                  Available backends: mklivestatus, ndo2db, ido2db, merlinmy

Backend specific parameters:

  ndo2db, ido2db:
  -m <BINARY>   Full path to the NDO/IDO module
                Default value: \$BASE/bin/ndo2db

  mklivestatus:
  -l <SOCKET>   MKLivestatus socket. Has to be in the following format:
                  TCP Socket:  tcp:<ip>:<port>
                  Unix Socket: unix:<full-path>
                Default value: unix:\$BASE/var/rw/live
    
Flag parameters:
  -o            Omit demo files
  -a [y|n]      Install the config file for the web server (y/n)
  -r            When performing an update of an existing NagVis installation the old
                NagVis directory will be saved in a backup directory. When you know
                what you are doing you can tell the installer to remove this backup 
                directory after a successful installation.
  -q            Quiet mode. The installer won't ask for confirmation of what to do.
                The installer will use the hard coded options or the values given
                by command line parameters.
                This can be useful for automatic or scripted deployment
                WARNING: Only use this if you know what you are doing
  -F            This is the force mode. Specifying this flag will call the installer
                skip all validity checks and install NagVis with the given options
                WARNING: Only use this if you know what you are doing
  -c [y|n]      Update configuration files when possible? Parses all existing
                configuration files, checks for deprecated and missing options and
                fixes known problems. This option has only effects when updating
                mechanisms have been added to this installer.

  -v            Version information
  -h            This message

EOD
}

# Print version information
version() {
cat <<EOD
NagVis installer, version $INSTALLER_VERSION
Copyright (C) 2004-2011 NagVis Project (Contact: info@nagvis.org)

License: GNU General Public License version 2

Development:
- Wolfgang Nieder
- Lars Michelsen <lars@vertical-visions.de>

EOD
}

# Print line
line() {
  [ $INSTALLER_QUIET -eq 1 ] && return
    DASHES="--------------------------------------------------------------------------------------------------------------"
    SIZE2=`expr $LINE_SIZE - 4`
    if [ -z "$1" ]; then
        OUT=`printf "+%${LINE_SIZE}.${LINE_SIZE}s+\n" $DASHES`
    else
        if [ -z "$2" ]; then
            OUT=`printf "+--- %s\n" "$1"`
        else
            OUT=`printf "+--- %${SIZE2}.${SIZE2}s+\n" "$1 $DASHES"`
        fi
    fi
  echo "$OUT"
}
# Print text
text() {
  [ $INSTALLER_QUIET -eq 1 ] && return
    SIZE2=`expr $LINE_SIZE - 3`
    if [ -z "$1" ]; then
        OUT=`printf "%s%${LINE_SIZE}s%s\n" "|" "" "|"`
    else
        if [ -z "$2" ]; then
            OUT=`printf "%s\n" "$1"`
        else
            OUT=`printf "%-${LINE_SIZE}.${LINE_SIZE}s %s\n" "$1" "$2"`
        fi
    fi
    echo "$OUT"
}

# Ask user for confirmation
confirm() {
    echo -n "| $1 [$2]: "
    read ANS
    [ -z $ANS ] && ANS=$2
    ANS=`echo $ANS | tr "jyos" "YYYY" | cut -c 1,1`
    [ "$ANS" != "Y" ]&&ANS="N"  
}

# Ask user for confirmation
check_confirm() {
    ANS=`echo $ANS | tr "jyos" "yyyy" | cut -c 1,1`
    if [ "$ANS" = "y" ]; then
        return 0
    else
        return 0
    fi
}

# Check Nagios path
check_nagios_path() {
    if [ -d $NAGIOS_PATH ]; then
        log "  $SOURCE path $NAGIOS_PATH" "found"
        return 0
    else
        log "  $SOURCE path $NAGIOS_PATH" ""
        return 1
    fi
}

check_web_user() {
    if [ "`getent passwd $WEB_USER | cut -d':' -f1`" = "$WEB_USER" ]; then
        return 0
    else
        echo "|  Error: User $WEB_USER not found."
        return 1
    fi
}

check_web_group() {
    if [ "`getent group $WEB_GROUP | cut -d':' -f1`" = "$WEB_GROUP" ]; then
        return 0
    else
        echo "|  Error: Group $WEB_GROUP not found."
        exit 1
    fi
}

ask_user() {
    VAR=$1
    DEFAULT=$2
    MANDATORY=$3
    VERIFY_FUNC=$4
    TEXT=$5
    RETURN=1

    while true; do
        if [ $INSTALLER_QUIET -ne 1 ]; then
            echo -n "| $TEXT [$DEFAULT]: "
            read OPT
            
            if [ ! -z $OPT ]; then
                eval "${VAR}=$OPT"
            else
                eval "${VAR}=$DEFAULT"
            fi
        fi

        if [ $MANDATORY -eq 1 -a "${!VAR}" != "" ] || [ $MANDATORY -eq 0 ]; then
            if [ "$VERIFY_FUNC" != "" ]; then
                ${VERIFY_FUNC}
                if [ $? = 0 ]; then
                    RETURN=0
                fi
                
                # In quiet mode break in all cases
                if [ $INSTALLER_QUIET -eq 1 -o $RETURN = 0 ]; then
                    break
                fi
            else
                RETURN=0
                break
            fi
        else
            break
        fi
    done

    return $RETURN
}

# Print welcome message
welcome() {
  [ $INSTALLER_QUIET -eq 1 ] && return
cat <<EOD
+------------------------------------------------------------------------------+
| $(printf "Welcome to NagVis Installer %-48s" $INSTALLER_VERSION) |
+------------------------------------------------------------------------------+
| This script is built to facilitate the NagVis installation and update        |
| procedure for you. The installer has been tested on the following systems:   |
| - Debian, since Etch (4.0)                                                   |
| - Ubuntu, since Hardy (8.04)                                                 |
| - SuSE Linux Enterprise Server 10 and 11                                     |
|                                                                              |
| Similar distributions to the ones mentioned above should work as well.       |
| That (hopefully) includes RedHat, Fedora, CentOS, OpenSuSE                   |
|                                                                              |
| If you experience any problems using these or other distributions, please    |
| report that to the NagVis team.                                              |
+------------------------------------------------------------------------------+
EOD
    ask_user "ANS" "y" 1 "check_confirm" \
             "Do you want to proceed?"

    if [ "$ANS" != "y" ]; then
        text
        text "| Installer aborted, exiting..." "|"
        line ""
        exit 1
    fi
}

# Print module state, exit if necessary
log() {
  [ $INSTALLER_QUIET -eq 1 ] && return
    SIZE=`expr $LINE_SIZE - 8` 
    if [ -z "$2" ]; then
        OUT=`printf "%-${SIZE}s %s\n" "| $1" "MISSING |"`
    elif [ "$2" = "needed" ]; then
        OUT="$1 needed"
    elif [ "$2" = "warning" ]; then
        OUT=`printf "%-${LINE_SIZE}s |\n" "| $1"`
    elif [ "$2" = "done" ]; then
        OUT=`printf "%-${SIZE}s %s\n" "| $1" "  done  |"`
    elif [ "$2" = "no_ex" ]; then
        SIZE=`expr $LINE_SIZE - 16` 
        OUT=`printf "%-${SIZE}s %s\n" "| $1" " not executable |"`
    else    
        OUT=`printf "%-${SIZE}s %s\n" "| $1" "  found |"`
    fi
    echo "$OUT"
}

# Tries to detect the Nagios path using the running Nagios process
# Will overwrite the NAGIOS_PATH when found some Nagios running
detect_nagios_path() {
    IFS=$'\n'
    init_id=1
    os_type=`uname -s`
    case $os_type in
        SunOS) # In Solaris Zone, there's no init process (id = 1) but zsched (random id)
            init_id=$(ps -ef | grep " zsched$" | grep -v grep  | awk '{ print $2 }' | head -1)
        ;;
    esac
    for N_PROC in `
    case $os_type in
        SunOS) /bin/ps -ef -o pid,ppid,user,args ;;
        *) ps ax -o pid,ppid,user,command ;;
    esac | grep "bin/$SOURCE" | grep -v grep`; do
        IFS=" "
        #  2138     1 nagios   /d/nagvis-dev/nagios/bin/nagios -d /d/nagvis-dev/nagios/etc/nagios.cfg
        N_PID=`expr "$N_PROC" : ' *\([0-9]*\)'`
        N_PPID=`expr "$N_PROC" : ' *[0-9]* *\([0-9]*\)'`
        N_USR=`expr "$N_PROC" : ' *[0-9]* *[0-9]* *\([^ ]*\)'`
        N_CMD=`expr "$N_PROC" : ' *[0-9]* *[0-9]* *[^ ]* *\(.*\)'`
        
        echo "$N_CMD" | grep -i " -d" >/dev/null
        if [[ $? -eq 0 && $N_PPID -eq $init_id ]]; then
            N_BIN=${N_CMD%% *}
            NAGIOS_PATH=${N_BIN%%/bin/$SOURCE}
            NAGIOS_PATH=${NAGIOS_PATH%/}
        fi
    done
    IFS=" "
}

# Tries to detect the correct path to the livestatus socket locally
detect_livestatus_socket() {
    if [ -S "/var/run/nagios/rw/live" ]; then
        LIVESTATUS_SOCK="unix:/var/run/nagios/rw/live"
    else
        LIVESTATUS_SOCK="unix:$NAGIOS_PATH/var/rw/live"
    fi
}
 
# Check Backend module prerequisites
check_backend() {
    # Ask to configure the backends during update
    if [ $INSTALLER_ACTION = "update" ]; then
        confirm "Do you want to update the backend configuration?" "n"
        if [ "$ANS" = "N" ]; then
            return
        fi
    fi

    BACKENDS=""
    text "| Checking Backends. (Available: $NAGVIS_BACKENDS)" "|"
    if [ $INSTALLER_QUIET -ne 1 ]; then
        if [ -z "$NAGVIS_BACKEND" ]; then
            ASK=`echo $NAGVIS_BACKENDS | sed 's/,/ /g'`
            for i in $ASK; do
                DEFAULT="n"

                if [ "$i" = "mklivestatus" ]; then
                    DEFAULT="y"
                fi

                confirm "Do you want to use backend $i?" $DEFAULT
                if [ "$ANS" = "Y" ]; then
                    BACKENDS=$BACKENDS,$i
                fi
            done
            NAGVIS_BACKEND=$BACKENDS
        fi
        NAGVIS_BACKEND=${NAGVIS_BACKEND#,}
    fi

    echo $NAGVIS_BACKEND | grep -i "MKLIVESTATUS" >/dev/null
    if [ $? -eq 0 ]; then
        [ -z "$LIVESTATUS_SOCK" ] && detect_livestatus_socket
        
        # Check if the livestatus socket is available
        # when not using a tcp socket
        if [[ "$LIVESTATUS_SOCK" =~ unix:* ]]; then
            if [ -S ${LIVESTATUS_SOCK#unix:} ]; then
                log "  Livestatus Socket (${LIVESTATUS_SOCK#unix:})" "found"
            elif [ $INSTALLER_QUIET -ne 1 ]; then
                # Loop until we got what we want in interactive mode
                while [[ ! "$LIVESTATUS_SOCK" =~ tcp:* && ! -S ${LIVESTATUS_SOCK#unix:} ]]; do
                    log "  Livestatus Socket (${LIVESTATUS_SOCK#unix:})" ""
                    text "| Valid socket formats are: tcp:127.0.0.1:7668 or unix:/path/to/live" "|"
                    
                    echo -n "| Please enter your MKLivestatus socket: "
                    read APATH
                    if [ ! -z $APATH ]; then
                        LIVESTATUS_SOCK=$APATH
                        
                        if [[ ! "$LIVESTATUS_SOCK" =~ unix:* && ! "$LIVESTATUS_SOCK" =~ tcp:* ]]; then
                            text "| Invalid socket format. Take a look above for valid formats." "|"
                        elif [[ ! "$LIVESTATUS_SOCK" =~ unix:* ]]; then
                            text "| Unable to check TCP-Sockets, hope you entered the correct socket." "|"
                        fi
                    fi
                done
            else
                log "  Livestatus Socket (${LIVESTATUS_SOCK#unix:})" ""
            fi

            CALL="$CALL -l \"$LIVESTATUS_SOCK\""
        else
            text "| Unable to check TCP-Sockets, hope you entered the correct socket." "|"
        fi
        
        # Check if php socket module is available
        check_php_modules "sockets" "$NEED_PHP_VERSION"
        
        if [ "$BACKENDS" = "" ]; then
            BACKENDS="mklivestatus"
        else
            BACKENDS="${BACKENDS},mklivestatus"
        fi
    fi
    
    echo $NAGVIS_BACKEND | grep -i "NDO2DB" >/dev/null
    if [ $? -eq 0 ]; then
        # Check ndo2db binary with version suffix
        [ -z "$NDO_MOD" ]&&NDO_MOD="$NAGIOS_PATH/bin/ndo2db-3x"
        NDO=`$NDO_MOD --version 2>/dev/null | grep -i "^NDO2DB"`

        # Check ndo2db binary without version suffix
        if [ -z "$NDO" ]; then
            NDO_MOD="$NAGIOS_PATH/bin/ndo2db"
            NDO=`$NDO_MOD --version 2>/dev/null | grep -i "^NDO2DB"`
        fi
        
        [ -z "$NDO" ]&&NDO_MOD="NDO Module ndo2db"
        log "  $NDO_MOD (ndo2db)" $NDO
        
        # Check if php mysql module is available
        check_php_modules "mysql" "$NEED_PHP_VERSION"
        
        if [ "$BACKENDS" = "" ]; then
            BACKENDS="ndo2db"
        else
            BACKENDS="${BACKENDS},ndo2db"
        fi
    fi
    
    echo $NAGVIS_BACKEND | grep -i "IDO2DB" >/dev/null
    if [ $? -eq 0 ]; then
        # Check IDO
        if [ -z "$NDO" ]; then
            NDO_MOD="$NAGIOS_PATH/bin/ido2db"
        fi
        NDO=`$NDO_MOD --version 2>/dev/null | grep -i "^IDO2DB"`
        [ -z "$NDO" ]&&NDO_MOD="IDO Module ido2db"
        log "  $NDO_MOD (ido2db)" $NDO
        # Check if php mysql module is available
        check_php_modules "mysql" "$NEED_PHP_VERSION"
        
        if [ "$BACKENDS" = "" ]; then
            BACKENDS="ido2db"
        else
            BACKENDS="${BACKENDS},ido2db"
        fi
    fi

    # Check merlin prerequisites if necessary
    echo $NAGVIS_BACKEND | grep -i "MERLINMY" >/dev/null
    if [ $? -eq 0 ]; then
        #text "|   *** Sorry, no checks yet for merlin" "|"
        
        # Check if php mysql module is available
        check_php_modules "mysql" "$NEED_PHP_VERSION"
        
        if [ "$BACKENDS" = "" ]; then
            BACKENDS="merlinmy"
        else
            BACKENDS="${BACKENDS},merlinmy"
        fi
    fi
    
    
    if [ -z "$BACKENDS" ]; then
        log "NO (valid) backend(s) specified"
    fi
    
    BACKENDS=${BACKENDS#,}
    [ ! -z "$BACKENDS" ] && CALL="$CALL -b $NAGVIS_BACKEND"
}

# Check Apache PHP module
check_apache_php() {
    DIR=$1
    [ ! -d $DIR ] && return
    WEB_PATH=${DIR%%/}
    [ -d $DIR/conf.d ]&&WEB_PATH=$WEB_PATH/conf.d
    
    # The apache user/group are defined by env vars in Ubuntu, set them here
    [ -f $DIR/envvars ] && source $DIR/envvars
    
    MODPHP=`find $DIR -type f -exec grep -ie "mod_php.*\.so" -e "libphp.*\.so" {} \; | tr -s " " | cut -d" " -f3 | uniq`
    
    # Only try to detect user when not set or empty
    if [ -z "$WEB_USER" ]; then
        WEB_USER=`find $DIR -type f -name "*conf" -exec grep -i "^User" {} \; | grep -vi "UserDir" | cut -d" " -f2 | uniq`
        VAR=`echo $WEB_USER | grep "$" >/dev/null 2>&1`
        [ $? -eq 0 ] && WEB_USER=`eval "echo $WEB_USER"`
    fi

    # Only try to detect group when not set or empty
    if [ -z "$WEB_GROUP" ]; then
        WEB_GROUP=`find $DIR -type f -name "*.conf" -exec grep -i "^Group" {} \; | cut -d" " -f2 | uniq`
        VAR=`echo $WEB_GROUP | grep "$" >/dev/null 2>&1`
        [ $? -eq 0 ] && WEB_GROUP=`eval "echo $WEB_GROUP"`
    fi
}

# Check Graphviz version by installed system package
check_graphviz_version() {
    if [ "${PKG##/*/}" = "dpkg" ]; then
        GRAPHVIZ_VER=`$PKG -l "graphviz" | grep "graphviz" | grep ii | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
    elif [ "${PKG##/*/}" = "rpm" ]; then
        GRAPHVIZ_VER=`$PKG -qa "graphviz" | sed "s/graphviz-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
    elif [ "${PKG##/*/}" = "pkginfo" ]; then
        GRAPHVIZ_VER=`$PKG -l "SMCgviz" | grep VERSION | awk '{print $2}'`
    else
        GRAPHVIZ_VER=`$PKG list installed "graphviz" | grep "installed" | awk -F' ' '{ print $2 }' | sed "s/-.*$//" | cut -d"." -f1,2`
    fi

   GRAPHVIZ_FMT=`fmt_version $GRAPHVIZ_VER` 
    if [ -z "$GRAPHVIZ_VER" ]; then
        log "WARNING: The Graphviz package was not found." "warning"
        log "         This may not be a problem if you installed it from source" "warning"
    else 
        log "Graphviz $GRAPHVIZ_VER" $GRAPHVIZ_VER
        if [ $GRAPHVIZ_FMT -lt $GRAPHVIZ_REQ ]; then
            log "|  Error: Version >= $1" "needed"
        fi
    fi
}

# Check Graphviz Modules
check_graphviz_modules() {
    for MOD in $1
    do
        TMP=`which $MOD 2>/dev/null`
        [ -z "$TMP" ] && TMP=`which $GRAPHVIZ_PATH/$MOD 2>/dev/null`
        if [ -s "$TMP" ]; then
            GV_MOD_VER=`$MOD -V 2>&1`
            GV_MOD_VER=${GV_MOD_VER#*version }
            GV_MOD_VER=${GV_MOD_VER% (*}
        fi
        
        log "  Graphviz Module $MOD $GV_MOD_VER" $TMP
        if [ -n "$GV_MOD_VER" ]; then
            GV_MOD_FMT=`fmt_version $GV_MOD_VER` 
        
            if [ $GV_MOD_FMT -lt $GRAPHVIZ_REQ ]; then
                log "|  Error: Version >= $2" "needed"
            fi
        fi
    done
}

# Check PHP Version
check_php_version() {
    if [ "${PKG##/*/}" = "dpkg" ]; then
        PHP_VER=`$PKG -l "php[0-9]" 2>/dev/null | grep "php" | grep ii | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
    elif [ "${PKG##/*/}" = "rpm" ]; then
        PHP_VER=`$PKG -qa "php[0-9]" | sed "s/php[0-9]\-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
    else
        PHP_VER=`$PKG list installed "php[0-9]" 2>/dev/null | grep "installed" | awk -F' ' '{ print $2 }' | sed "s/-.*$//" | cut -d"." -f1,2`
    fi
    PHP=`which php 2>/dev/null`
    if [ -z "$PHP_VER" ]; then
        if [ -s "$PHP" -a -x "$PHP" ]; then
            PHP_VER=`$PHP -v | head -1 | sed -e "s/PHP \([0-9\]\+\.[0-9\]\+\).*/\1/"`
        fi
    fi
    log "PHP $PHP_VER" $PHP_VER
    if [ -n "$PHP_VER" ]; then
        PHP_FMT=`fmt_version $PHP_VER` 
        PHP_REQ=`fmt_version $NEED_PHP_VERSION` 
    
        if [ $PHP_FMT -lt $PHP_REQ ]; then
            log "|  Error: Version >= $1" "needed"
        fi
    fi
}

# Check PHP modules
check_php_modules() {
    for MOD in $1
    do
        TMP=""
        if [ "${PKG##/*/}" = "dpkg" ]; then
            MOD_VER=`$PKG -l "php[0-9]-$MOD" 2>/dev/null | grep "php" | grep "ii" | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
        elif [ "${PKG##/*/}" = "rpm" ]; then
            MOD_VER=`$PKG -qa "php[0-9]?-$MOD" | sed "s/php[0-9]?\-$MOD-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
        else
            MOD_VER=`$PKG list installed "php[0-9]-$MOD" 2>/dev/null | grep "php" | grep "installed" | awk -F' ' '{ print $2 }' | sed "s/-.*$//" | cut -d"." -f1,2`
        fi

        # maybe compiled in module
        if [ -s "$PHP" -a -x "$PHP" ]; then
            TMP=`$PHP -m 2>&1 | grep -i "^$MOD$"`
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
        
        log "  PHP Module: $MOD $MOD_VER" $TMP

        if [ -n "$MOD_VER" ]; then
            if [ `echo "$2 $MOD_VER" | awk '{if ($1 > $2) print $1; else print $2}'` = $2 ]; then
                log "  WARNING: Module $MOD not found." "warning"
                log "           This may not be a problem. You can ignore this if your php" "warning"
                log "           was compiled with the module included" "warning"
            fi
        fi
    done
}

# Check SQLite version
check_sqlite_version() {
    if [ "${PKG##/*/}" = "dpkg" ]; then
        SQLITE_VER=`$PKG -l "sqlite3" | grep "sqlite" | grep ii | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
    elif [ "${PKG##/*/}" = "rpm" ]; then
        SQLITE_VER=`$PKG -qa "sqlite" | sed "s/sqlite-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
    else
        SQLITE_VER=`$PKG list installed "sqlite" | grep "installed" | awk -F' ' '{ print $2 }' | sed "s/-.*$//" | cut -d"." -f1,2`
    fi

   SQLITE_FMT=`fmt_version $SQLITE_VER` 
    if [ -z "$SQLITE_VER" ]; then
        log "WARNING: The SQLite package was not found." "warning"
        log "         This may not be a problem if you installed it from source" "warning"
    else 
        log "SQLite $SQLITE_VER" $SQLITE_VER
        if [ $SQLITE_FMT -lt $SQLITE_REQ ]; then
            log "|  Error: Version >= $1" "needed"
        fi
    fi
}

# Check return code
chk_rc() {
    LRC=$?
    if [ $LRC -ne 0 ]; then
        echo $* Return Code: $LRC
        if [ $UNDO -eq 1 ]; then
            ANS="n"
            ask_user "ANS" "y" 1 "check_confirm" \
                     "Do you want to revert to old NagVis version?"

            if [ "$ANS" = "y" ]; then
                text "| Trying to revert to old NagVis version" "|"
                text "| Renaming $NAGVIS_PATH to ${NAGVIS_PATH}_broken" "|"
                [ -d $NAGVIS_PATH ]&& mv $NAGVIS_PATH ${NAGVIS_PATH}_broken
                text "| Renaming $NAGVIS_PATH_BACKUP to $NAGVIS_PATH_OLD" "|"
                [ -d $NAGVIS_PATH_BACKUP ]&& mv $NAGVIS_PATH_BACKUP $NAGVIS_PATH_OLD
            fi
        fi
        exit 1
    else
        if [ "$2" != "" ]; then
            echo "$2"
        fi
    fi
}

rename_template_files() {
    DONE=""
    # 1: source directory
    SOURCE=$1
    # 2: target directory
    TARGET=$2
    # 3: template type
    TYPE=$3

    [ -n "$LINE" ] && DONE=`log "$LINE" done`

    FILES=`find $NAGVIS_PATH/$SOURCE -type f -printf "%f\n"`
    IFS=$'\n'
    for FILE in $FILES; do
        IFS=" "
        FILE_NEW=`echo "$FILE" | sed 's/tmpl\.//g' | sed "s/\.html/\.$TYPE\.html/g" | sed "s/\.css/\.$TYPE\.css/g"`
    
    cp -p "$NAGVIS_PATH/$SOURCE/$FILE" "$NAGVIS_PATH/$TARGET/$FILE_NEW"
    chk_rc "|  Error renaming $TYPE template file ($SOURCE/$FILE to $TARGET/$FILE_NEW)" "$DONE"
    done
    IFS=" "
}

copy_dir_xpath() {
    DONE=""
    # 1: Exclude pattern
    # 2: Old dir
    # 3: New dir
    
  [ -n "$LINE" ] && DONE=`log "$LINE" done` 
  # Get files and directories to copy. This takes only the elements in the
  # given directory.
  # FILES=`find $NAGVIS_PATH_BACKUP/$2 -mindepth 1 -maxdepth 1`
  FILES=`find $NAGVIS_PATH_BACKUP/$2/* -prune 2> /dev/null`

  # Maybe exclude some files
  if [ "$1" != "" ]; then
    FILES=`echo "$FILES" | grep -vE $1`
  fi

  if [ "$FILES" != "" ]; then
    cp -pr `echo "$FILES" | xargs` $NAGVIS_PATH/$3
    chk_rc "|  Error copying dir $2 to $3" "$DONE"
  fi
}

restore() {
    copy $NAGVIS_PATH_BACKUP/$1 $NAGVIS_PATH/$1 "$2" "$3"
}

copy() {
    DONE=""
    
    # DEBUG: [ -n "$LINE" ] && line "$LINE"
    [ -n "$LINE" ] && DONE=`log "$LINE" done` 
    [ -z "$3" ] && WHAT=$1 || WHAT=$3

    # When trying to copy a file/dir which does not exist just skip it
    if [[ "$1" != */ && ! -e "$1" ]]; then
        return
    fi
    
    FILTER=
    if [ "$4" != "" ]; then
        for F in $4; do
            FILTER=$FILTER\ --exclude\ $F
        done
    fi

    rsync -aq -f "- .gitignore" $FILTER $1 $2
    chk_rc "|  Error copying $WHAT" "$DONE"
    
    LINE=""
    DONE=""
}

set_perm() {
    if [ -d "$2" -o -f "$2" -o "${2#${2%?}}" = "*" ]; then
        # Don't do anything when called with globbing and directory is empty
        if [[ "${2#${2%?}}" = "*" && "`ls -1 "${2%*\*}"`" = "" ]]; then
            return 0
        else
            DONE=`log "$2" done` 
            chmod $1 $2
            chk_rc "| Error setting permissions for $2" "$DONE"
        fi
    fi
}

makedir() {
    if [ ! -d $1 ]; then
        DONE=`log "Creating directory $1..." done` 
        mkdir -p $1
        chk_rc "|  Error creating directory $1" "$DONE"
    fi
}

# Main program starting
###############################################################################

# More (re)initialisations

# Version info
NAGVIS_TAG=`fmt_version "$NAGVIS_VER"`
[ -z "$NAGVIS_TAG" ]&&NAGVIS_TAG=01000000

if [ $NAGVIS_TAG -lt 01050000 ]; then
    echo "Error: This installer version only installs NagVis 1.5x or newer"
    exit 1
fi

S=`echo $* | grep -i "\-s icinga"`
[ $? -eq 0 ]&&SOURCE=icinga
# Default hardcoded Nagios path
NAGIOS_PATH="/usr/local/$SOURCE"

# Try to detect the Nagios path with some magic
detect_nagios_path

# Default hardcoded NagVis base
NAGVIS_PATH="${NAGIOS_PATH%%nagios}"
NAGVIS_PATH="${NAGVIS_PATH%/}/nagvis"
NAGVIS_PATH_OLD=$NAGVIS_PATH

# Default nagios share webserver path
HTML_PATH="/nagvis"

# Process command line options
if [ $# -gt 0 ]; then
    while getopts "p:n:m:l:w:W:u:b:g:c:i:s:O:a:ohqvFr" options $OPTS; do
        case $options in
            n)
                NAGIOS_PATH=${OPTARG%/}
            ;;
            m)
                NDO_MOD=$OPTARG
            ;;
            l)
                LIVESTATUS_SOCK=$OPTARG
            ;;
            b)
                GRAPHVIZ_PATH=$OPTARG
            ;;
            p)
                NAGVIS_PATH=${OPTARG%/}
                NAGVIS_PATH_PARAM_SET=1

                if [ $NAGVIS_PATH_OLD_PARAM_SET -eq 0 ]; then
                    NAGVIS_PATH_OLD=$NAGVIS_PATH
                fi
            ;;
            O)
                NAGVIS_PATH_OLD=${OPTARG%/}
                NAGVIS_PATH_OLD_PARAM_SET=1
            ;;
            w)
                WEB_PATH=${OPTARG%/}
            ;;
            W)
                HTML_PATH=$OPTARG
            ;;
            u)
                WEB_USER=$OPTARG
            ;;
            g)
                WEB_GROUP=$OPTARG
            ;;
            i)
                NAGVIS_BACKEND=$OPTARG
            ;;
            o)
                IGNORE_DEMO="demo*cfg demo*png demo*ini.php demo-*.csv"
            ;;
            q)
                INSTALLER_QUIET=1
            ;;
            c)
                INSTALLER_CONFIG_MOD=$OPTARG
            ;;
            s)
                SOURCE=`echo $OPTARG | tr [A-Z] [a-z]` 
            ;;
            a)
                ACONF="$OPTARG"
            ;;
            F)
                FORCE=1
            ;;
            r)
                REMOVE="y"
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

if [ $FORCE -eq 1 ]; then
    if [ -z "$WEB_USER" -o -z "$WEB:GROUP" -o -z "$WEB_PATH" ]; then
        echo "ERROR: Using '-F' you also have to specify '-u ...', '-g ...' and '-w ...'"
        exit 1
    fi
fi

find_bin()
{
    bin=$1
    case `uname -s` in
        SunOS) which $bin | grep -v "^no $bin in" ;;
        *) which $bin 2> /dev/null ;;
    esac
}

{
# Print welcome message
welcome

# Start gathering information
line ""
text "| Starting installation of NagVis $NAGVIS_VER" "|"
line ""
[ -f /etc/issue ]&&OS=`grep -v "^\s*$" /etc/issue | sed 's/\\\.*//' | head -1` 
[ -n "$OS" ]&&text "| OS  : $OS" "|"
text
line "Checking for tools" "+"
WHICH=`whereis which | awk '{print $2}'` 
if [ -z $WHICH ]; then
    log "'which' not found (maybe package missing). Aborting..."
    exit 1
fi
PKG=`find_bin dpkg`
[ -z "$PKG" ] && PKG=`find_bin rpm`
[ -z "$PKG" ] && PKG=`find_bin yum`
[ -z "$PKG" ] && PKG=`find_bin pkginfo`
if [ -z "$PKG" ]; then
    log "No packet manager (rpm/dpkg/yum/pkginfo) found. Aborting..."
    exit 1
fi
log "Using packet manager $PKG" $PKG
SED=`which sed` 

if ! which rsync >/dev/null 2>&1; then
    log "rsync is not installed. Aborting..."
    exit 1
fi

# checking grep option as non-Linux might not support "-r"
grep -r INSTALLER_VERSION install.sh >/dev/null 2>&1
if [ $? -ne 0 ]; then
    GREP_INCOMPLETE=1
    log "grep doesn't support option -r" "warning"
fi

text
line "Checking paths" "+"

if [ $FORCE -eq 0 ]; then
    # Get Nagios/Icinga path
    ask_user "NAGIOS_PATH" "$NAGIOS_PATH" 1 "check_nagios_path" \
           "Please enter the path to the $SOURCE base directory"
    [ $RC != 1 ] && RC=$?
    NAGIOS_PATH=${NAGIOS_PATH%/}
    CALL="$CALL -n $NAGIOS_PATH"

    # Get NagVis path
    TMP=$NAGVIS_PATH
    ask_user "NAGVIS_PATH" "$NAGVIS_PATH" 1 "" \
           "Please enter the path to NagVis base"
    NAGVIS_PATH=${NAGVIS_PATH%/}
    [ $RC != 1 ] && RC=$?
    
    # Also update old path when it was equal to the new directory or empty before
    [ "$NAGVIS_PATH_OLD" = "" -o "$NAGVIS_PATH_OLD" = "$TMP" ] && NAGVIS_PATH_OLD=$NAGVIS_PATH
    
    CALL="$CALL -p $NAGVIS_PATH"

    # Maybe the user wants to update from NagVis 1.4x. The paths
    # have changed there. So try to get the old nagvis dir in nagios/share
    # path. When there is some, ask the user to update that installation.
    if [ ! -d "$NAGVIS_PATH_OLD" -a -d ${NAGIOS_PATH%/}/share/nagvis -a "$NAGVIS_PATH" != "${NAGIOS_PATH%/}/share/nagvis" ]; then
        # Found nagvis in nagios/share and this run wants to install NagVis somewhere else
        NAGVIS_PATH_OLD="${NAGIOS_PATH%/}/share/nagvis"

        if [ $INSTALLER_QUIET -ne 1 ]; then
            text "| The installer will install NagVis to $NAGVIS_PATH. But the installer found" "|"
            text "| another NagVis installation at $NAGVIS_PATH_OLD." "|"
            
            ANS="n"
            ask_user "ANS" "y" 1 "check_confirm" \
                     "Do you want to update that installation?"

            if [ "$ANS" != "y" ]; then
                text "| Okay, not performing an update with changing paths." "|"
                text "|" "|"
                NAGVIS_PATH_OLD=$NAGVIS_PATH
            fi
        fi
        CALL="$CALL -O $NAGVIS_PATH_OLD"
    fi
    
fi

# When the old directory exists this is an update run
if [ -d "$NAGVIS_PATH_OLD" ]; then
    INSTALLER_ACTION="update"
fi

text
line "Checking prerequisites" "+"

# Set Nagios binary when not set yet
[ -f "$NAGIOS_PATH/bin/icinga" ]&&SOURCE=icinga
[ -f "$NAGIOS_PATH/bin/nagios" ]&&SOURCE=nagios

if [ $FORCE -eq 0 ]; then
    # Check PHP Version
    check_php_version $NEED_PHP_VERSION

    # Check PHP Modules
    check_php_modules "$NEED_PHP_MODULES" "$NEED_PHP_VERSION"

    # Check Apache PHP Module
    check_apache_php "/etc/apache2/"
    check_apache_php "/etc/apache/"
    check_apache_php "/etc/http/"
    check_apache_php "/etc/httpd/"
    check_apache_php "/usr/local/etc/apache2/"  # FreeBSD
    log "  Apache mod_php" $MODPHP

    # Check Backend prerequisites
    check_backend

    # Check Graphviz
    GRAPHVIZ_REQ=`fmt_version $NEED_GV_VERSION` 
    check_graphviz_version $NEED_GV_VERSION

    # Check Graphviz Modules
    check_graphviz_modules "$NEED_GV_MOD" $NEED_GV_VERSION

    # Check SQLite
    SQLITE_REQ=`fmt_version $NEED_SQLITE_VERSION` 
    check_sqlite_version $NEED_SQLITE_VERSION

    if [ $RC -ne 0 ]; then
        text
        line "Errors found during check of prerequisites. Aborting..."
        exit 1
    fi

    text
    line "Trying to detect Apache settings" "+"

    HTML_PATH=${HTML_PATH%/}

    ask_user "HTML_PATH" "$HTML_PATH" 1 "" \
           "Please enter the web path to NagVis"
    HTML_PATH=${HTML_PATH%/}
    [ $RC != 1 ] && RC=$?

    ask_user "WEB_USER" "$WEB_USER" 1 "check_web_user" \
           "Please enter the name of the web-server user"
    [ $RC != 1 ] && RC=$?

    ask_user "WEB_GROUP" "$WEB_GROUP" 1 "check_web_group" \
           "Please enter the name of the web-server group"
    [ $RC != 1 ] && RC=$?
    
    ask_user "ACONF" "$ACONF" 1 "check_confirm" \
       "create Apache config file"

    CALL="$CALL -u $WEB_USER -g $WEB_GROUP -w $WEB_PATH -a $ACONF"
fi

text
line "Checking for existing NagVis" "+"

if [ -d $NAGVIS_PATH_OLD ]; then
    if [ -e $NAGVIS_PATH_OLD/nagvis/includes/defines/global.php ]; then
        NAGVIS_VER_OLD=`cat $NAGVIS_PATH_OLD/nagvis/includes/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`
    elif [ -e $NAGVIS_PATH_OLD/share/nagvis/includes/defines/global.php ]; then
        NAGVIS_VER_OLD=`cat $NAGVIS_PATH_OLD/share/nagvis/includes/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`
    elif [ -e $NAGVIS_PATH_OLD/share/server/core/defines/global.php ]; then
        NAGVIS_VER_OLD=`cat $NAGVIS_PATH_OLD/share/server/core/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`
    else
        NAGVIS_VER_OLD="UNKNOWN"
    fi
    
    # Generate the version tag for old version
    if [ "$NAGVIS_VER_OLD" != "UNKNOWN" ]; then
        NAGVIS_TAG_OLD=`fmt_version "$NAGVIS_VER_OLD"`
    else
        NAGVIS_TAG_OLD=01000000
    fi
    
    NAGVIS_PATH_BACKUP=$NAGVIS_PATH_OLD.old-$DATE

    log "NagVis $NAGVIS_VER_OLD" $NAGVIS_VER_OLD
fi

if [ "$INSTALLER_ACTION" = "update" ]; then
    if [ $INSTALLER_QUIET -ne 1 ]; then
        ask_user "INSTALLER_CONFIG_MOD" "$INSTALLER_CONFIG_MOD" 1 "check_confirm" \
                     "Do you want the installer to update your config files when possible?"
        ask_user "REMOVE" "$REMOVE" 1 "check_confirm" \
                     "Remove backup directory after successful installation?"
    fi
fi

text
line ""
text "| Summary" "|"
line ""
text "| NagVis home will be:           $NAGVIS_PATH" "|"
text "| Owner of NagVis files will be: $WEB_USER" "|"
text "| Group of NagVis files will be: $WEB_GROUP" "|"
text "| Path to Apache config dir is:  $WEB_PATH" "|"
if [ "$ACONF" = "y" -o "$ACONF" = "Y" ]; then
    text "| Apache config will be created: yes" "|"
else
    text "| Apache config will be created: NO" "|"
fi
text
if [ "$IGNORE_DEMO" != "" ]; then
    text "| demo files will NOT be copied" "|"
    text
fi
text "| Installation mode:             $INSTALLER_ACTION" "|"
if [ "$INSTALLER_ACTION" = "update" ]; then
    if [ $NAGVIS_PATH != $NAGVIS_PATH_OLD ]; then
        text "| Old NagVis home:               $NAGVIS_PATH_OLD" "|"
    fi
    text "| Old version:                   $NAGVIS_VER_OLD" "|"
    text "| New version:                   $NAGVIS_VER" "|"
    text "| Backup directory:              $NAGVIS_PATH_BACKUP" "|"
    text
    text "| Note: The current NagVis directory will be moved to the backup directory." "|"
    if [ "$REMOVE" = "y" ]; then
        text "|       The backup directory will be removed after successful installation. " "|"
    else
        text "|       The backup directory will be NOT removed after successful installation. " "|"
    fi
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
    if [ "$ANS" != "Y" ]; then
        text
        text "| Installer aborted, exiting..." "|"
        line ""
        exit 1
    fi
fi

line ""
text "| Starting installation" "|"
line ""

if [ "$INSTALLER_ACTION" = "update" ]; then
    DONE=`log "Moving old NagVis to $NAGVIS_PATH_BACKUP.." done` 
    mv $NAGVIS_PATH_OLD $NAGVIS_PATH_BACKUP
    chk_rc "|  Error moving old NagVis $NAGVIS_PATH_BACKUP" "$DONE"
fi

# in case of errors switch to old NagVis directory
UNDO=1

# Create base path
makedir "$NAGVIS_PATH"

# Create non shared var directory when not exists
makedir "$NAGVIS_PATH/var"
makedir "$NAGVIS_PATH/var/tmpl/cache"
makedir "$NAGVIS_PATH/var/tmpl/compile"
# Create shared var directory when not exists
makedir "$NAGVIS_PATH/share/var"
# Copy all desired files
LINE="Copying files to $NAGVIS_PATH..."
copy "share" "$NAGVIS_PATH"
copy "etc" "$NAGVIS_PATH"
makedir "$NAGVIS_PATH/etc/conf.d"
makedir "$NAGVIS_PATH/etc/profiles"
copy "README" "$NAGVIS_PATH"
copy "LICENCE" "$NAGVIS_PATH"
copy "docs" "$NAGVIS_PATH/share/" "" "*/cleanup_new_notes.sh"
cmp_js $NAGVIS_PATH/share/frontend/nagvis-js/js

# Remove demo maps if desired
if [ "$IGNORE_DEMO" != "" ]; then
    for i in $IGNORE_DEMO;
    do
        DONE=`log "Removing file(s) $i" done` 
        find $NAGVIS_PATH -name "$i" -exec rm {} \;
        chk_rc "|  Error removing $i" "$DONE"
    done    
fi

# Create main configuration file from sample when no file exists
if [ -f $NAGVIS_PATH/${NAGVIS_CONF}-sample ]; then
    NAGVIS_CFG=$NAGVIS_PATH/$NAGVIS_CONF
    DONE=`log "Creating main configuration file..." done` 
    if [ -f $NAGVIS_CFG ]; then
        text "| *** $NAGVIS_CFG will NOT be overwritten !" "|"
        NAGVIS_CFG=$NAGVIS_PATH/$NAGVIS_CONF.inst
        text "| *** creating $NAGVIS_CFG instead" "|"
    fi
    cp -p $NAGVIS_PATH/${NAGVIS_CONF}-sample $NAGVIS_CFG
    chk_rc "|  Error copying sample configuration" "$DONE"

    # add sesscookiepath
    grep ";sesscookiepath=\"$HTML_PATH\"" $NAGVIS_CFG >/dev/null
    if [ $? -eq 1 ]; then
        DONE=`log "adding sesscookie=$HTML_PATH" done` 
        $SED -i "s#;\(sesscookiepath\)=\(.*\)#;\1=\2\n\1=\"$HTML_PATH\"#g" $NAGVIS_CFG
        chk_rc "|  Error adding sesscookiepath" "$DONE"
    fi

    # add NagVis base
    grep ";base=\"$NAGVIS_PATH/\"" $NAGVIS_CFG >/dev/null
    if [ $? -eq 1 ]; then
        DONE=`log "adding base=\"$NAGVIS_PATH\"" done` 
        $SED -i "s#;\(base\)=\(.*\)#;\1=\2\n\1=\"$NAGVIS_PATH/\"#g" $NAGVIS_CFG
        chk_rc "|  Error adding base path" "$DONE"
    fi

    # add htmlbase
    grep ";htmlbase=\"$HTML_PATH\"" $NAGVIS_CFG >/dev/null
    if [ $? -eq 1 ]; then
        DONE=`log "adding htmlbase=\"$HTML_PATH\"" done` 
        $SED -i "s#;\(htmlbase\)=\(.*\)#;\1=\2\n\1=\"$HTML_PATH\"#g" $NAGVIS_CFG
        chk_rc "|  Error adding htmlbase" "$DONE"
    fi

    # add htmlcgi
    grep ";htmlcgi=\"/$SOURCE/cgi-bin\"" $NAGVIS_CFG >/dev/null
    if [ $? -eq 1 ]; then
        DONE=`log "adding htmlcgi=/$SOURCE/cgi-bin" done` 
        $SED -i "s#;\(htmlcgi\)=\(.*\)#;\1=\2\n\1=\"/$SOURCE/cgi-bin\"#g" $NAGVIS_CFG
        chk_rc "|  Error adding htmlcgi" "$DONE"
    fi

    # add dbname
    grep ";dbname=\"$SOURCE\"" $NAGVIS_CFG >/dev/null
    if [ $? -eq 1 ]; then
        DONE=`log "adding dbname=$SOURCE" done` 
        $SED -i "s#;\(dbname\)=\(\"nagios\"\)#;\1=\2\n\1=\"$SOURCE\"#g" $NAGVIS_CFG
        chk_rc "|  Error adding dbname" "$DONE"
    fi

    # add dbprefix
    grep ";dbprefix=\"${SOURCE}_\"" $NAGVIS_CFG >/dev/null
    if [ $? -eq 1 ]; then
        DONE=`log "adding dbprefix=${SOURCE}_" done` 
        $SED -i "s#;\(dbprefix\)=\(\"nagios_\"\)#;\1=\2\n\1=\"${SOURCE}_\"#g" $NAGVIS_CFG
        chk_rc "|  Error adding dbprefix" "$DONE"
    fi

    # Set the new default backend_id. Don't need to treat livestatus here because
  # it is the hardcoded default value in NagVis.
    echo $NAGVIS_BACKEND | grep "merlinmy" >/dev/null
    [ $? -eq 0 ]&&NEWBACK="merlinmy_1"
    echo $NAGVIS_BACKEND | grep "ido2db" >/dev/null
    [ $? -eq 0 ]&&NEWBACK="ndomy_1"
    echo $NAGVIS_BACKEND | grep "ndo2db" >/dev/null
    [ $? -eq 0 ]&&NEWBACK="ndomy_1"
  if [ ! -z "$NEWBACK" ]; then
        DONE=`log "setting backend to $NEWBACK" done` 
        $SED -i "s#;\(backend\)=\(.*\)#;\1=\2\n\1=\"$NEWBACK\"#g" $NAGVIS_CFG
        chk_rc "|  Error setting backend" "$DONE"
    fi

    # Add livestatus backend when configured to use MKLivestatus
    if [ ! -z "$LIVESTATUS_SOCK" ]; then
        DONE=`log "  Adding MKLivestatus Backend..." done`
        $SED -i 's#;socket="unix:/usr/local/nagios/var/rw/live"#socket="'"$LIVESTATUS_SOCK"'"#g' $NAGVIS_CFG
        chk_rc "|  Error adding MKLivstatus Backend" "$DONE"
    fi

    # Add the webservers group to use it with chgrp calls in NagVis
    DONE=`log "  Adding webserver group to file_group..." done`
    $SED -i 's#;file_group=""#file_group="'"$WEB_GROUP"'"#g' $NAGVIS_CFG
    chk_rc "|  Error adding file_group" "$DONE"
fi

# Create apache configuration file from sample when no file exists
if [ -f $NAGVIS_PATH/$HTML_SAMPLE ]; then
    if [ "$ACONF" = "n" -o "$ACONF" = "N" ]; then
        text "| *** creation of $WEB_PATH/$HTML_CONF will be SKIPPED !" "|"
    else
        CHG='s/^//'
        if [ -s $WEB_PATH/$HTML_CONF ]; then
            text "| *** $WEB_PATH/$HTML_CONF will NOT be overwritten !" "|"
            HTML_CONF="$HTML_CONF.$DATE"
            text "| *** creating $WEB_PATH/$HTML_CONF instead (commented out config)" "|"
            CHG='s/^/#new /'
        fi
        DONE=`log "Creating web configuration file..." done`

        # Replace macros in sample configuration file
        cat $NAGVIS_PATH/$HTML_SAMPLE | $SED "s#@NAGIOS_PATH@#$NAGIOS_PATH#g;s#@NAGVIS_PATH@#$NAGVIS_PATH/share#g;s#@NAGVIS_WEB@#$HTML_PATH#g;$CHG" > $WEB_PATH/$HTML_CONF
        chk_rc "|  Error creating web configuration" "$DONE"
        DONE=`log "Setting permissions for web configuration file..." done`
        chown $WEB_USER:$WEB_GROUP $WEB_PATH/$HTML_CONF
        chk_rc "|  Error setting web conf permissions" "$DONE"
    fi
fi

text
if [ "$INSTALLER_ACTION" = "update" -a "$NAGVIS_VER_OLD" != "UNKNOWN" ]; then
    NAGVIS_DIR="share/nagvis"
    USERFILES_DIR="share/userfiles"

    if [ $NAGVIS_TAG_OLD -ge 01050000 ]; then
        LINE="Restoring main configuration file(s)..."
        restore "$NAGVIS_CONF" "main configuration file" ""
        restore "etc/nagvis-site.ini.php" "site main configuration file" ""
    
        LINE="Restoring custom map configuration files..."
        restore "etc/maps/" "map configuration files" "/demo*.cfg"
    
        LINE="Restoring custom geomap source files..."
        restore "etc/geomap/" "geomap source files" "/demo*.csv"
    
        LINE="Restoring user configuration files..."
        if [ -d $NAGVIS_PATH_BACKUP/etc/profiles ]; then
            restore "etc/profiles/" "user configuration files" ""
        fi

        if [ -d $NAGVIS_PATH_BACKUP/etc/conf.d ]; then
            LINE="Restoring conf.d/ configuration files..."
            restore "etc/conf.d/" "conf.d configuration files" ""
        fi
    
        LINE="Restoring custom map images..."
        restore "$USERFILES_DIR/images/maps/" "map image files" "/demo*.png"
    
        LINE="Restoring custom gadget images..."
        restore "$USERFILES_DIR/images/gadgets/" "gadget image files" ""
    
        LINE="Restoring custom iconsets..."
        restore "$USERFILES_DIR/images/iconsets/" "iconset files" "/20x20.png /std_*_*.png /demo_*.png"
    
        LINE="Restoring custom shapes..."
        restore "$USERFILES_DIR/images/shapes/" "shapes" "*demo*png /std_*"
        
        LINE="Restoring custom templates..."
        restore "$USERFILES_DIR/templates/" "templates" "/default.*"
        
        LINE="Restoring custom template images..."
        restore "$USERFILES_DIR/images/templates/" "template images" "/default.*"

        LINE="Restoring custom gadgets..."
        restore "$USERFILES_DIR/gadgets/" "gadgets" "/gadgets_core.php /std_*.php"

        if [ -d $NAGVIS_PATH_BACKUP/$USERFILES_DIR/scripts/ ]; then
            LINE="Restoring custom scripts..."
            restore "$USERFILES_DIR/scripts/" "scripts" "/std_*.php"
        fi
        
        LINE="Restoring auth database file..."
        restore "$NAGVIS_AUTH_DB" "auth database file" ""
        restore "$AUTH_BACKUP" "auth backup file" ""

        LINE="Restoring custom stylesheets..."
        restore "$USERFILES_DIR/styles/" "stylesheets" ""
    else
        # This is a cross version update. For example from 1.4x to 1.5x
        LINE="Restoring main configuration file..."
        restore "$NAGVIS_CONF" "main configuration file" ""
    
        LINE="Restoring custom map configuration files..."
        copy_dir_xpath "\/(demo\.cfg|demo2\.cfg|demo-server\.cfg|demo-map\.cfg)$" "etc/maps" "etc/maps" "map configuration files"
    
        LINE="Restoring custom map images..."
        copy_dir_xpath "\/nagvis-demo\.png$" "nagvis/images/maps" "$USERFILES_DIR/images/maps" "map image files"
    
        LINE="Restoring custom gadget images..."
        copy_dir_xpath "" "nagvis/images/gadgets" "$USERFILES_DIR/images/gadgets" "gadget image files"
    
        LINE="Restoring custom iconsets..."
        copy_dir_xpath "\/(20x20\.png|std_(big|medium|small)\.png|demo_.+\.png)$" "nagvis/images/iconsets" "$USERFILES_DIR/images/iconsets" "iconset files"
    
        LINE="Restoring custom shapes..."
        copy_dir_xpath "" "nagvis/images/shapes" "$USERFILES_DIR/images/shapes" "shapes"
        
        LINE="Restoring custom templates..."
        copy_dir_xpath  "\/tmpl\.default.+$" "nagvis/templates" "$USERFILES_DIR/templates" "hover templates"
        LINE="Renaming custom hover templates"
        rename_template_files "$USERFILES_DIR/templates/hover" "$USERFILES_DIR/templates" "hover"
        LINE="Renaming custom context templates"
        rename_template_files "$USERFILES_DIR/templates/context" "$USERFILES_DIR/templates" "context"
        
        LINE="Restoring custom gadgets..."
        copy_dir_xpath "\/(gadgets_core\.php|std_.+\.php)$" "nagvis/gadgets" "$USERFILES_DIR/gadgets" "gadgets"

        text "|" "|"
        text "| IMPORTANT: When upgrading from previous 1.5.0 to 1.5.x version you need" "|"
        text "|            to migrate eventually custom templates by hand because the " "|"
        text "|            template format has totally changed. The template images are" "|"
        text "|            unhandled too." "|"
    fi
fi
text

# Do some update tasks (Changing options, notify about deprecated options)
if [ "$INSTALLER_ACTION" = "update" -a "$NAGVIS_VER_OLD" != "UNKNOWN" -a "$INSTALLER_CONFIG_MOD" = "y" ]; then
    line 
    text "| Handling changed/removed options" "|"
    line

    # Only perform the actions below for NagVis 1.5.x or newer installations
    if [ $NAGVIS_TAG -ge 01050000 ]; then
        DONE=`log "Removing allowedforconfig option from main config..." done`
        sed -i '/^allowedforconfig=/d' $NAGVIS_PATH/etc/nagvis.ini.php
        chk_rc "| Error" "$DONE"

        DONE=`log "Removing autoupdatefreq option from main config..." done`
        sed -i '/^autoupdatefreq=/d' $NAGVIS_PATH/etc/nagvis.ini.php
        chk_rc "| Error" "$DONE"

        DONE=`log "Removing htmlwuijs option from main config..." done`
        sed -i '/^htmlwuijs=/d' $NAGVIS_PATH/etc/nagvis.ini.php
        chk_rc "| Error" "$DONE"

        DONE=`log "Removing wuijs option from main config..." done`
        sed -i '/^wuijs=/d' $NAGVIS_PATH/etc/nagvis.ini.php
        chk_rc "| Error" "$DONE"

        DONE=`log "Removing showautomaps option from main config..." done`
        sed -i '/^showautomaps=/d' $NAGVIS_PATH/etc/nagvis.ini.php
        chk_rc "| Error" "$DONE"

        # Remove base and htmlbase path from cross path updated main
        # configuration file
        if [ "$NAGVIS_PATH_OLD" != "$NAGVIS_PATH" ]; then
            DONE=`log "Uncommenting base path during cross-path update..." done`
            sed -i 's/^base=\(.*\)$/;base=\1/g' $NAGVIS_PATH/etc/nagvis.ini.php
            chk_rc "| Error" "$DONE"

            DONE=`log "Uncommenting htmlbase path during cross-path update..." done`
            sed -i 's/^htmlbase=\(.*\)$/;htmlbase=\1/g' $NAGVIS_PATH/etc/nagvis.ini.php
            chk_rc "| Error" "$DONE"

            DONE=`log "Uncommenting sesscookiepath during cross-path update..." done`
            sed -i 's/^sesscookiepath=\(.*\)$/;sesscookiepath=\1/g' $NAGVIS_PATH/etc/nagvis.ini.php
            chk_rc "| Error" "$DONE"
            
        fi
        
        DONE=`log "Removing usegdlibs option from main config..." done`
        sed -i '/^usegdlibs=/d' $NAGVIS_PATH/etc/nagvis.ini.php
        chk_rc "| Error" "$DONE"
        
        DONE=`log "Removing displayheader option from main config..." done`
        sed -i '/^displayheader=/d' $NAGVIS_PATH/etc/nagvis.ini.php
        chk_rc "| Error" "$DONE"
        
        DONE=`log "Removing hovertimeout option from main config..." done`
        sed -i '/^hovertimeout=/d' $NAGVIS_PATH/etc/nagvis.ini.php
        chk_rc "| Error" "$DONE"
        
        DONE=`log "Removing allowed_for_config option from map configs..." done`
        grep -r '^allowed_for_config=' $NAGVIS_PATH/etc/maps/*.cfg >> $NAGVIS_PATH/$AUTH_BACKUP
        sed -i '/^allowed_for_config=/d' $NAGVIS_PATH/etc/maps/*.cfg
        chk_rc "| Error" "$DONE"
        
        DONE=`log "Removing allowed_user from map configs..." done`
        grep -r '^allowed_user=' $NAGVIS_PATH/etc/maps/*.cfg >> $NAGVIS_PATH/$AUTH_BACKUP
        sed -i '/^allowed_user=/d' $NAGVIS_PATH/etc/maps/*.cfg
        chk_rc "| Error" "$DONE"

        DONE=`log "Removing hover_timeout from map configs..." done`
        sed -i '/^hover_timeout=/d' $NAGVIS_PATH/etc/maps/*.cfg
        chk_rc "| Error" "$DONE"

        DONE=`log "Removing usegdlibs from map configs..." done`
        sed -i '/^usegdlibs=/d' $NAGVIS_PATH/etc/maps/*.cfg
        chk_rc "| Error" "$DONE"

        line
    fi
    
    # Maybe this is useful in the future? => Updates for special versions
    #if [ $NAGVIS_TAG_OLD -ge 01030000 ] && [ $NAGVIS_TAG_OLD -lt 01050000 ]; then
    #   text "| Version specific changes from 1.3.x or 1.4.x " "|"
    #   text
    #   line "Applying changes to main configuration file..."
    #   text "| oops, no changes yet" "|"
    #   chk_rc "| Error" "| done"
    #   line "Applying changes to map configuration files..."
    #   text "| oops, no changes yet" "|"
    #   chk_rc "| Error" "| done"
    #fi
    
    text "| HINT: Please check the changelog or the documentation for changes which" "|"
    text "|       affect your configuration files" "|"
fi
text

line "Setting permissions..." "+"
chown -R $WEB_USER:$WEB_GROUP $NAGVIS_PATH
[ -f "$NAGVIS_PATH/$NAGVIS_CONF-sample" ]&&set_perm 664 "$NAGVIS_PATH/$NAGVIS_CONF-sample"
set_perm 775 "$NAGVIS_PATH/etc"
set_perm 775 "$NAGVIS_PATH/etc/maps"
set_perm 664 "$NAGVIS_PATH/etc/maps/*"
set_perm 775 "$NAGVIS_PATH/etc/geomap"
set_perm 664 "$NAGVIS_PATH/etc/geomap/*"
set_perm 775 "$NAGVIS_PATH/etc/profiles"
set_perm 664 "$NAGVIS_PATH/etc/profiles/*"
set_perm 775 "$NAGVIS_PATH/share/userfiles/images/maps"
set_perm 664 "$NAGVIS_PATH/share/userfiles/images/maps/*"
set_perm 775 "$NAGVIS_PATH/share/userfiles/images/shapes"
set_perm 664 "$NAGVIS_PATH/share/userfiles/images/shapes/*"
set_perm 775 "$NAGVIS_PATH/var"
set_perm 664 "$NAGVIS_PATH/var/*"
set_perm 775 "$NAGVIS_PATH/var/tmpl"
set_perm 775 "$NAGVIS_PATH/var/tmpl/cache"
set_perm 775 "$NAGVIS_PATH/var/tmpl/compile"
set_perm 775 "$NAGVIS_PATH/share/var"
set_perm 664 "$NAGVIS_PATH/share/var/*"
text

if [ "$INSTALLER_ACTION" = "update" -a "$REMOVE" = "y" ]; then
    DONE=`log "Removing backup directory" done`
    rm -rf $NAGVIS_PATH_BACKUP
    chk_rc "|  Error removing directory user configuration" "$DONE"
fi

line
text "| Installation complete" "|"
text
text "| You can safely remove this source directory." "|"
text
text "| For later update/upgrade you may use this command to have a faster update:" "|"
text "| $CALL"
text
if [ "$INSTALLER_ACTION" = "update" ] && [ $NAGVIS_TAG_OLD -lt 01050000 ]; then
text "| 1.4 to 1.6x upgrade: The map permissions have ben reset. Old permissions" "|"
text "| have been backed up in nagvis/etc/auth-backup file. You need to migrate" "|"
text "| these permissions manually using using the new user/role management GUI." "|"
text
fi
text "| What to do next?" "|"
text "| - Read the documentation" "|"
text "| - Maybe you want to edit the main configuration file?" "|"
text "|   Its location is: $NAGVIS_PATH/$NAGVIS_CONF" "|"
text "| - Configure NagVis via browser" "|"
text "|   <http://localhost${HTML_PATH}/config.php>" "|"
text "| - Initial admin credentials:" "|"
text "|     Username: admin" "|"
text "|     Password: admin" "|"
line
} 2>&1 | tee $LOG

exit 0
