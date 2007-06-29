<?PHP
/**
 * Here are defined some Regex matches for value validation
 * in main and map configuration files
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */

define('MATCH_ALL', '/^.*$/i');

define('MATCH_STRING', '/^[0-9a-z\s\:\[\]\(\)\_\.\,\-\?\!\/\\\]+$/i');
define('MATCH_STRING_NO_SPACE', '/^[0-9a-z\:\[\]\(\)\_\.\,\-\?\!\/\\\]+$/i');
define('MATCH_STRING_PATH', '/^[0-9a-z\s\_\.\-\/\\\]+$/i');
define('MATCH_STRING_URL', '/^[0-9a-z\s\:\[\]\(\)\=\?\&\_\.\-\/\\\]+$/i');

define('MATCH_INTEGER', '/^[0-9]+$/');
define('MATCH_BOOLEAN', '/^(1|0|true|false)$/i');

define('MATCH_COLOR', '/^(#?[0-9a-f]{3,6}|transparent)$/i');
define('MATCH_OBJECTTYPE', '/^(global|host|service|hostgroup|servicegroup|map|textbox|shape)$/i');
define('MATCH_PNGFILE', '/^.+\.png$/i');
define('MATCH_INTEGER_PRESIGN', '/^[\+\-]?[0-9]+$/');
?>
