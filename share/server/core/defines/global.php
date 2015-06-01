<?PHP
/*****************************************************************************
 *
 * global.php - File for global constants and some other standards
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */

// NagVis Version
define('CONST_VERSION', '1.8.5');

// Set PHP error handling to standard level
// Different levels for php versions below 5.1 because PHP 5.1 reports
// some annoying strict messages which are OK for us. From version 5.2
// everything is OK when using E_STRICT.
if(version_compare(PHP_VERSION, '5.2') >= 0)
	error_reporting(E_ALL ^ E_STRICT);
else
	error_reporting(E_ALL);

/**
 * Set the search path for included files
 */
set_include_path(
	get_include_path()
	.PATH_SEPARATOR.'../../server/core/classes'
	.PATH_SEPARATOR.'../../server/core/classes/objects'
	.PATH_SEPARATOR.'../../server/core/ext/php-gettext-1.0.9'
);

// Enable/Disable profiling of NagVis using xhprof.  To make use of this the
// xhprof php module needs to be loaded and the xhprof_lib directory needs
// to be available in /var/www.
define('PROFILE', false);

// enable/disable the debug mode
define('DEBUG', false);

/**
 * For desired debug output add these possible values:
 * 1: function start and end
 * 2: progress information in the functions
 * 4: render time
 */
define('DEBUGLEVEL', 6);

// Path to the debug file
define('DEBUGFILE', '../../../var/nagvis-debug.log');

// It is possible to define a conf.d directory for splitting the main
// configuration in several files. Only the values defined in the CONST_MAINCFG
// file are editable via the web GUI.
//
// The parameters are applied in this direction:
// 1. hardcoded
// 2. CONST_MAINCFG_DIR
// 3. CONST_MAINCFG
//
// The last value wins.
//
// Path to the main configuration file
define('CONST_MAINCFG', '../../../etc/nagvis.ini.php');
define('CONST_MAINCFG_CACHE', '../../../var/nagvis-conf');

// Path to the main configuration conf.d directory
define('CONST_MAINCFG_DIR', '../../../etc/conf.d');

// The directory below the NagVis root which is shared by the webserver
define('HTDOCS_DIR', 'share');

// Needed minimal PHP version
define('CONST_NEEDED_PHP_VERSION', '5.0');

// NagVis session name
define('SESSION_NAME', 'nagvis_session');

// Other basic constants
define('REQUIRES_AUTHORISATION', true);
define('GET_STATE', true);
define('GET_PHYSICAL_PATH', false);
define('DONT_GET_OBJECT_STATE', false);
define('DONT_GET_SINGLE_MEMBER_STATES', false);
define('GET_SINGLE_MEMBER_STATES', true);
define('HANDLE_USERCFG', true);
define('ONLY_USERCFG', true);

define('ONLY_STATE', true);
define('COMPLETE', false);

define('IS_VIEW', true);
define('ONLY_GLOBAL', true);
define('GET_CHILDS', true);
define('SET_KEYS', true);
define('SUMMARY_STATE', true);
define('COUNT_QUERY', true);
define('MEMBER_QUERY', true);
define('HOST_QUERY', true);

// Field definitions - fields in state constructs. There is one
// basic state construct which is used wherever an object is only
// handled as member state. The exteded state construct is used
// for hosts/services which are directly added to a map to get
// more details from those objects
//
// basic state
define('STATE',     0);
define('OUTPUT',    1);
define('ACK',       2);
define('DOWNTIME',  3);
define('STALE',     4);
// extended generic
define('STATE_TYPE',              5);
define('CURRENT_ATTEMPT',         6);
define('MAX_CHECK_ATTEMPTS',      7);
define('LAST_CHECK',              8);
define('NEXT_CHECK',              9);
define('LAST_HARD_STATE_CHANGE', 10);
define('LAST_STATE_CHANGE',      11);
define('PERFDATA',               12);
define('DISPLAY_NAME',           13);
define('ALIAS',                  14);
define('ADDRESS',                15);
define('NOTES',                  16);
define('CHECK_COMMAND',          17);
define('CUSTOM_VARS',            18);
define('DOWNTIME_AUTHOR',        19);
define('DOWNTIME_DATA',          20);
define('DOWNTIME_START',         21);
define('DOWNTIME_END',           22);
// extended service
define('DESCRIPTION',            23);

// Number of fields in extended state structures
define('EXT_STATE_SIZE', 24);

// State definitions - internal numbers representing the states
// hosts
define('UNCHECKED',   14);
define('UNREACHABLE', 12);
define('DOWN',        11);
define('UP',          10);
// services
define('PENDING',      4);
define('UNKNOWN',      3);
define('CRITICAL',     2);
define('WARNING',      1);
define('OK',           0);
// generic
define('ERROR',       -1);

// Maximum length for usernames/passwords
define('AUTH_MAX_PASSWORD_LENGTH', 30);
define('AUTH_MAX_USERNAME_LENGTH', 30);
define('AUTH_MAX_ROLENAME_LENGTH', 30);

// Permission wildcard
define('AUTH_PERMISSION_WILDCARD', '*');

// This is being used when logging in using LogonEnv for trusting the given user
define('AUTH_TRUST_USERNAME', true);
define('AUTH_NOT_TRUST_USERNAME', false);

// Salt for the password hashes
// Note: If you change this you will need to rehash all saved 
//       password hashes
define('AUTH_PASSWORD_SALT', '29d58ead6a65f5c00342ae03cdc6d26565e20954');
?>
