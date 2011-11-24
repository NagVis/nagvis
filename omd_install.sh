#!/bin/bash
# omd_install.sh - Installs NagVis to the local/ path of OMD sites
#
# Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
#
# Development:
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

echo "+--------------------------------------------------------------------+"
echo "| This script installs NagVis into the local/ path of your OMD site. |"
echo "| The installation in the local/ path will then override the default |"
echo "| NagVis installation included with OMD.                             |"
echo "|                                                                    |"
echo "| When installed in local/ path NagVis will use the config and user  |"
echo "| files of the site.                                                 |"
echo "|                                                                    |"
echo "| RECOMMENDED ONLY FOR TESTING                                       |"
echo "+--------------------------------------------------------------------+"

if [ -z "$OMD_SITE" ] || [ -z "$OMD_ROOT" ]; then
    echo "ERROR: You are not running this inside an OMD site."
    echo "       Check out http://omdistro.org/ for easy installable and manageable Nagios."
    exit 1
fi

echo -n "Do you really want to continue? [y] "
read OPT
if [ ! -z "$OPT" ] &&  [ $OPT != "y" ]; then
    echo "Terminated by user."
    exit 1
fi

CWD="$(cd "$(dirname "$0")" && pwd)"

echo "Installing..."

# 1.5 had the userfiles dir in $OMD_ROOT/var/nagvis/userfiles
# Test if this is the first installation of NagVis 1.6x in this
# site. If so: Copy the contents of the old userfiles dir to the
# new location
if [ ! -d $OMD_ROOT/local/share/nagvis/htdocs/userfiles ]; then
    mkdir -p $OMD_ROOT/local/share/nagvis/htdocs/userfiles
    cp -r $OMD_ROOT/var/nagvis/userfiles/* $OMD_ROOT/local/share/nagvis/htdocs/userfiles
fi

mkdir -p $OMD_ROOT/var/nagvis/profiles
mkdir -p $OMD_ROOT/local/share/nagvis/htdocs
cp -r $CWD/share/* $OMD_ROOT/local/share/nagvis/htdocs
cp -r $CWD/docs $OMD_ROOT/local/share/nagvis/htdocs/

# Update "old" (1.5) userfiles dir
if [ -d $OMD_ROOT/var/nagvis/userfiles ]; then
    cp -r $CWD/share/userfiles/* $OMD_ROOT/var/nagvis/userfiles
fi

# Handle the old and new omd specific config file paths
OMD_CFG=$OMD_ROOT/etc/nagvis/conf.d/omd.ini.php
if [ ! -d $OMD_ROOT/etc/nagvis/conf.d ]; then
    mkdir $OMD_ROOT/etc/nagvis/conf.d
    if [ -f $OMD_ROOT/etc/nagvis/nagvis-omd.ini.php ]; then
        mv $OMD_ROOT/etc/nagvis/nagvis-omd.ini.php $OMD_ROOT/etc/nagvis/conf.d/omd.ini.php
        ln -s $OMD_ROOT/etc/nagvis/conf.d/omd.ini.php $OMD_ROOT/etc/nagvis/nagvis-omd.ini.php
    fi
fi

# Backup the omd.ini.php on first time using omd_install.sh
if ! grep omd_install.sh $OMD_CFG >/dev/null 2>&1; then
    cp $OMD_CFG $OMD_CFG.bak
fi

# Update omd specific nagvis.ini.php file
cat > $OMD_CFG <<EOF
; <?php return 1; ?>
; -----------------------------------------------------------------
; Don't touch this file. It is under control of OMD. Modifying this
; file might break the update mechanism of OMD.
;
; If you want to customize your NagVis configuration please use the
; etc/nagvis/nagvis.ini.php file.
;
; Tainted by omd_install.sh for installation to local/
; -----------------------------------------------------------------

[global]
sesscookiepath="/$OMD_SITE/nagvis"

[paths]
base="$OMD_ROOT/local/share/nagvis/"
cfg="$OMD_ROOT/etc/nagvis/"
mapcfg="$OMD_ROOT/etc/nagvis/maps/"
automapcfg="$OMD_ROOT/etc/nagvis/automaps/"
var="$OMD_ROOT/tmp/nagvis/"
sharedvar="$OMD_ROOT/tmp/nagvis/share/"
profiles="$OMD_ROOT/var/nagvis/profiles/"
htmlbase="/$OMD_SITE/nagvis"
htmlcgi="/$OMD_SITE/nagios/cgi-bin"

[defaults]
backend="$OMD_SITE"

[backend_$OMD_SITE]
backendtype="mklivestatus"
socket="unix:$OMD_ROOT/tmp/run/live"
EOF

# Backup the agvis.conf on first time using omd_install.sh
if ! grep omd_install.sh $OMD_ROOT/etc/apache/conf.d/nagvis.conf >/dev/null 2>&1; then
    cp $OMD_ROOT/etc/apache/conf.d/nagvis.conf $OMD_ROOT/etc/apache/conf.d/nagvis.conf.bak
fi

cat > $OMD_ROOT/etc/apache/conf.d/nagvis.conf <<EOF
# NagVis Apache2 configuration file for use in OMD
#
# This file has been created by omd_install.sh which installs NagVis into
# the local hierarchy of an OMD site.
# #############################################################################

Alias /$OMD_SITE/nagvis/var "$OMD_ROOT/tmp/nagvis/share"
Alias /$OMD_SITE/nagvis "$OMD_ROOT/local/share/nagvis/htdocs"

<Directory "$OMD_ROOT/tmp/nagvis/share">
  Options FollowSymLinks
  AllowOverride None
</Directory>

<Directory "$OMD_ROOT/local/share/nagvis/htdocs">
  Options FollowSymLinks
  AllowOverride None

  # With installed and enabled mod_rewrite there are several redirections
  # available to fix deprecated and/or wrong urls. None of those rules is
  # mandatory to get NagVis working.
  <IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /$OMD_SITE/nagvis

    # Use mod_rewrite for old url redirection even if there are php files which
    # redirect the queries itselfs. In some cases the mod_rewrite redirect
    # is better than the php redirect.
    #RewriteCond %{REQUEST_URI} ^/$OMD_SITE/nagvis(/config\.php|/index\.php|/|)(\?.*|)$
    #RewriteRule ^(index\.php|)(\?.*|)$ /$OMD_SITE/nagvis/frontend/nagvis-js/\$1\$2 [R=301,L]

    # Redirect old regular map links
    RewriteCond %{REQUEST_URI} ^/$OMD_SITE/nagvis/frontend/(nagvis-js|wui)
    RewriteCond %{QUERY_STRING} map=(.*)
    RewriteRule ^(.*)$ /$OMD_SITE/nagvis/frontend/nagvis-js/index.php?mod=Map&act=view&show=%1 [R=301,L]

    # Without map= param
    RewriteCond %{REQUEST_URI} ^/$OMD_SITE/nagvis/frontend(/wui)?/?(index.php)?$
    RewriteRule ^(.*)$ /$OMD_SITE/nagvis/frontend/nagvis-js/index.php [R=301,L]

    # Redirect old rotation calls
    RewriteCond %{REQUEST_URI} ^/$OMD_SITE/nagvis/frontend/nagvis-js
    RewriteCond %{QUERY_STRING} !mod
    RewriteCond %{QUERY_STRING} rotation=(.*)
    RewriteRule ^(.*)$ /$OMD_SITE/nagvis/frontend/nagvis-js/index.php?mod=Rotation&act=view&show=%1 [R=301,L]
  </IfModule>
</Directory>
EOF

patch -s $OMD_ROOT/local/share/nagvis/htdocs/server/core/defines/global.php <<EOF
--- nagvis-1.5.3/share/server/core/defines/global.php.orig      2010-10-02 19:43:58.000000000 +0200
+++ nagvis-1.5.3/share/server/core/defines/global.php   2010-10-02 19:47:00.000000000 +0200
@@ -64,7 +64,7 @@
 define('DEBUGLEVEL', 6);

 // Path to the debug file
-define('DEBUGFILE', '../../../var/nagvis-debug.log');
+define('DEBUGFILE', join(array_slice(explode('/' ,dirname(\$_SERVER["SCRIPT_FILENAME"])), 0, -6), '/').'/tmp/nagvis/nagvis-debug.log');

 // It is possible to define a second main configuration file
 // to pre-define some options in a file the user may not be
@@ -78,14 +78,14 @@
 // The last value wins.
 //
 // Path to the main configuration file
-define('CONST_MAINCFG', '../../../etc/nagvis.ini.php');
-define('CONST_MAINCFG_CACHE', '../../../var/nagvis-conf');
+define('CONST_MAINCFG', join(array_slice(explode('/' ,dirname(\$_SERVER["SCRIPT_FILENAME"])), 0, -6), '/').'/etc/nagvis/nagvis.ini.php');
+define('CONST_MAINCFG_CACHE', join(array_slice(explode('/' ,dirname(\$_SERVER["SCRIPT_FILENAME"])), 0, -6), '/').'/tmp/nagvis/nagvis-conf');
 
 // Path to the main configuration conf.d directory
-define('CONST_MAINCFG_DIR', '../../../etc/conf.d');
+define('CONST_MAINCFG_DIR', join(array_slice(explode('/' ,dirname(\$_SERVER["SCRIPT_FILENAME"])), 0, -6), '/').'/etc/nagvis/conf.d');
 
 // The directory below the NagVis root which is shared by the webserver
-define('HTDOCS_DIR', 'share');
+define('HTDOCS_DIR', 'htdocs');
 
 // Needed minimal PHP version
 define('CONST_NEEDED_PHP_VERSION', '5.0');
EOF

# Cleanup temporary files
find $OMD_ROOT/tmp/nagvis -type f -exec rm {} \;

omd reload apache

echo "            ...done."

exit 0
