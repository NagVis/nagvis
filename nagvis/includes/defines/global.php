<?PHP
/**
 * Here are defined some constants for use in NagVis and WUI
 *
 * @author      Lars Michelsen <lars@vertical-visions.de>
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
define('CONST_VERSION', '1.3b2');

// Path to the main configuration file
define('CONST_MAINCFG', '../etc/nagvis.ini.php');
?>
