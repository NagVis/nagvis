<?PHP
/*****************************************************************************
 *
 * global.php - File for global constants and some other standards
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

// NagVis Version
const CONST_VERSION = '1.9.42';

// Set PHP error handling to standard level
// Different levels for php versions below 5.1 because PHP 5.1 reports
// some annoying strict messages which are OK for us. From version 5.2
// everything is OK when using E_STRICT.
if (version_compare(PHP_VERSION, '5.2') >= 0) {
    error_reporting(E_ALL ^ E_STRICT);
} else {
    error_reporting(E_ALL);
}

/**
 * Set the search path for included files
 */
set_include_path(
    get_include_path()
     . PATH_SEPARATOR . '../../server/core/classes'
     . PATH_SEPARATOR . '../../server/core/classes/objects'
     . PATH_SEPARATOR . '../../server/core/ext/php-gettext-1.0.12'
);

// Enable/Disable profiling of NagVis using xhprof.  To make use of this the
// xhprof php module needs to be loaded and the xhprof_lib directory needs
// to be available in /var/www.
const PROFILE = false;

// enable/disable the debug mode
const DEBUG = false;

/**
 * For desired debug output add these possible values:
 * 1: function start and end
 * 2: progress information in the functions
 * 4: render time
 */
const DEBUGLEVEL = 6;

// Path to the debug file
const DEBUGFILE = '../../../var/nagvis-debug.log';

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
const CONST_MAINCFG = '../../../etc/nagvis.ini.php';
const CONST_MAINCFG_CACHE = '../../../var/nagvis-conf';

// Path to the main configuration conf.d directory
const CONST_MAINCFG_DIR = '../../../etc/conf.d';

// The directory below the NagVis root which is shared by the webserver
const HTDOCS_DIR = 'share';

// Needed minimal PHP version
const CONST_NEEDED_PHP_VERSION = '5.0';

// NagVis session name
const SESSION_NAME = 'nagvis_session';

// Other basic constants
const REQUIRES_AUTHORISATION = true;
const GET_STATE = true;
const GET_PHYSICAL_PATH = false;
const DONT_GET_OBJECT_STATE = false;
const DONT_GET_SINGLE_MEMBER_STATES = false;
const GET_SINGLE_MEMBER_STATES = true;
const HANDLE_USERCFG = true;
const ONLY_USERCFG = true;

const ONLY_STATE = true;
const COMPLETE = false;

const IS_VIEW = true;
const ONLY_GLOBAL = true;
const GET_CHILDS = true;
const SET_KEYS = true;
const SUMMARY_STATE = true;
const COUNT_QUERY = true;
const MEMBER_QUERY = true;
const HOST_QUERY = true;

// Field definitions - fields in state constructs. There is one
// basic state construct which is used wherever an object is only
// handled as member state. The exteded state construct is used
// for hosts/services which are directly added to a map to get
// more details from those objects
//
// basic state
const STATE = 0;
const OUTPUT = 1;
const ACK = 2;
const DOWNTIME = 3;
const STALE = 4;
// extended generic
const STATE_TYPE = 5;
const CURRENT_ATTEMPT = 6;
const MAX_CHECK_ATTEMPTS = 7;
const LAST_CHECK = 8;
const NEXT_CHECK = 9;
const LAST_HARD_STATE_CHANGE = 10;
const LAST_STATE_CHANGE = 11;
const PERFDATA = 12;
const DISPLAY_NAME = 13;
const ALIAS = 14;
const ADDRESS = 15;
const NOTES = 16;
const CHECK_COMMAND = 17;
const CUSTOM_VARS = 18;
const DOWNTIME_AUTHOR = 19;
const DOWNTIME_DATA = 20;
const DOWNTIME_START = 21;
const DOWNTIME_END = 22;
// extended service
const DESCRIPTION = 23;

// Number of fields in extended state structures
const EXT_STATE_SIZE = 24;

// State definitions - internal numbers representing the states
// hosts
const UNCHECKED = 14;
const UNREACHABLE = 12;
const DOWN = 11;
const UP = 10;
// services
const PENDING = 4;
const UNKNOWN = 3;
const CRITICAL = 2;
const WARNING = 1;
const OK = 0;
// generic
const ERROR = -1;

// Maximum length for usernames/passwords
const AUTH_MAX_PASSWORD_LENGTH = 30;
const AUTH_MAX_USERNAME_LENGTH = 30;
const AUTH_MAX_ROLENAME_LENGTH = 30;

// Permission wildcard
const AUTH_PERMISSION_WILDCARD = '*';

// This is being used when logging in using LogonEnv for trusting the given user
const AUTH_TRUST_USERNAME = true;
const AUTH_NOT_TRUST_USERNAME = false;

// Salt for the password hashes
// Note: If you change this you will need to rehash all saved
//       password hashes
const AUTH_PASSWORD_SALT = '29d58ead6a65f5c00342ae03cdc6d26565e20954';
