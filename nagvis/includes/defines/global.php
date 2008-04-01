<?PHP
/*****************************************************************************
 *
 * global.php - File for global constants
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
// enable/disable the debug mode
define('DEBUG', FALSE);

/**
 * For wanted debug output summarize these possible options:
 * 1: function beginning and ending
 * 2: progres informations in the functions
 * 4: render time
 */
define('DEBUGLEVEL', 4);

// Path to the debug file
define('DEBUGFILE', '../var/nagvis-debug.log');

// NagVis Version
define('CONST_VERSION', '1.3rc1');

// Path to the main configuration file
define('CONST_MAINCFG', '../etc/nagvis.ini.php');

// Needed minimal PHP version
define('CONST_NEEDED_PHP_VERSION', '5.0');
?>
