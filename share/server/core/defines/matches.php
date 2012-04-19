<?PHP
/*****************************************************************************
 *
 * matches.php - File for global match constants
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
define('MATCH_ALL', '/^.*$/i');
define('MATCH_REGEX', '/^.*$/i');

// These regex allow unicode matching
define('MATCH_STRING', '/^[0-9a-zа-яё\p{L}\s\:\+\[\]\(\)\_\.\,\-\?\!\#\@\=\/\\\]+$/iu');
define('MATCH_STRING_EMPTY', '/^[0-9a-zа-яё\p{L}\s\:\+\[\]\(\)\_\.\,\-\?\!\#\@\=\/\\\]*$/iu');
define('MATCH_STRING_NO_SPACE', '/^[0-9a-zа-яё\p{L}\:\+\[\]\(\)\_\.\,\-\?\!\#\@\=\/\\\]+$/iu');
define('MATCH_STRING_NO_SPACE_EMPTY', '/^[0-9a-zа-яё\p{L}\:\+\[\]\(\)\_\.\,\-\?\!\#\@\=\/\\\]*$/iu');

define('MATCH_STRING_PATH', '/^[0-9a-z\s\_\.\-\/\\\]+$/i');
define('MATCH_STRING_URL', '/^[0-9a-z\s:;|\+\[\]\(\)\=\%\?\&\_\,\.\-\#\@\=\/\\\]+$/i');
define('MATCH_STRING_URL_EMPTY', '/^[0-9a-z\s:;|\+\[\]\(\)\=\%\?\&\_\,\.\-\#\@\=\/\\\]*$/i');
define('MATCH_GADGET_OPT', '/^[0-9a-z\s\:\+\[\]\(\)\_\.\,\-\&\?\!\#\@\=\/\\\%]+$/i');
define('MATCH_STRING_STYLE', '/^[0-9a-z\:\;\-\+\%\#]*$/i');
define('MATCH_COORDS',       '/^(?:(?:[0-9]+)|([a-z0-9]+(?:%[+-][0-9]+)?))$/');
define('MATCH_COORDS_MULTI', '/^(?:(?:(?:[0-9]+)|([a-z0-9]+(?:%[+-][0-9]+)?))[\.\,]?)+$/');
define('MATCH_COORDS_MULTI_EMPTY', '/^(?:(?:(?:[0-9]+)|([a-z0-9]+(?:%[+-][0-9]+)?))[\.\,]?)*$/');

define('MATCH_INTEGER', '/^[0-9]+$/');
define('MATCH_INTEGER_EMPTY', '/^[0-9]*$/');
define('MATCH_FLOAT', '/^[0-9]+[\.\,]*[0-9]*$/');
define('MATCH_BOOLEAN', '/^(?:1|0)$/i');
define('MATCH_BOOLEAN_EMPTY', '/^(?:1|0)*$/i');

define('MATCH_COLOR', '/^(#?[0-9a-f]{3,6}|transparent)$/i');
define('MATCH_OBJECTTYPE', '/^(?:global|host|service|hostgroup|servicegroup|map|textbox|shape|line|template)$/i');
define('MATCH_OBJECTID', '/^(?:[a-z0-9]+)$/i');
define('MATCH_OBJECTID_EMPTY', '/^(?:[a-z0-9]*)$/i');
define('MATCH_PNGFILE', '/^([^\s]+)\.png$/i');
define('MATCH_PNG_GIF_JPG_FILE', '/^([^\s]+)\.(png|gif|jpg)$/i');
define('MATCH_PNG_GIF_JPG_FILE_OR_URL_NONE', '/^((.+)\.(png|gif|jpg)|\[[0-9a-z\s\:\+\[\]\(\)\=\%\?\&\_\.\-\#\@\=\/\\\]+\]|none)$/i');
define('MATCH_PNG_GIF_JPG_FILE_OR_URL', '/^((.+)\.(png|gif|jpg)|\[[0-9a-z\s\:\+\[\]\(\)\=\%\?\&\_\.\-\#\@\=\/\\\]+\])$/i');
define('MATCH_ROTATION_STEP_TYPES_EMPTY', '/^(?:map|automap|url)?$/');
define('MATCH_LANGUAGE_EMPTY', '/^[a-zA-Z0-9\-_]*$/');
define('MATCH_LANGUAGE_FILE', '/^([^\.].*)/');
define('MATCH_ICONSET', '/^(.+)_ok.(png|gif|jpg)$/');
define('MATCH_BACKEND_FILE', '/^GlobalBackend([^MI].+)\.php$/');
define('MATCH_DOC_DIR', '/^([a-z]{2}_[A-Z]{2})/');
define('MATCH_MAINCFG_FILE', '/^.+\.ini\.php$/i');

define('MATCH_MAP_NAME', '/^[0-9A-Za-z_\-]+$/');
define('MATCH_MAP_NAME_EMPTY', '/^[0-9A-Za-z_\-]*$/');
define('MATCH_ROTATION_NAME', '/^[0-9A-Za-z_\-]+$/');
define('MATCH_ROTATION_NAME_EMPTY', '/^[0-9A-Za-z_\-]*$/');
define('MATCH_BACKGROUND_NAME', '/^[0-9A-Za-z_\-]+$/');
define('MATCH_VIEW_TYPE', '/^(?:icon|line)$/i');
define('MATCH_VIEW_TYPE_SERVICE', '/^(?:icon|line|gadget)$/i');
define('MATCH_VIEW_TYPE_SERVICE_EMPTY', '/^(?:icon|line|gadget)*$/i');
define('MATCH_GET_OBJECT_TYPE', '/^(state|complete|summary)$/');
define('MATCH_GADGET_TYPE', '/^(?:img|html)$/i');
define('MATCH_OBJECT_TYPES', '/^(host|service|hostgroup|servicegroup|map|automap)$/');
define('MATCH_AUTOMAP_RENDER_MODE', '/^(directed|undirected|radial|circular|undirected2)?$/');
define('MATCH_LINE_TYPE', '/^(10|11|12|13|14)?$/');
define('MATCH_LINE_ARROW', '/^(none|forward|back|both)?$/');
define('MATCH_USER_NAME', '/^[0-9A-Za-z_\-\.\@\s]+$/');
define('MATCH_ROLE_NAME', '/^[0-9A-Za-z_\-\.\@\s]+$/');

define('MATCH_URI_PART', '/^[a-zA-Z0-9\-_]*$/');

define('MATCH_CFG_FILE', '/^(.+)\.cfg$/i');
define('MATCH_MP3_FILE', '/^(.+)\.mp3$/i');
define('MATCH_HEADER_TEMPLATE_FILE', '/^(.+)\.header\.html$/i');
define('MATCH_HOVER_TEMPLATE_FILE', '/^(.+)\.hover\.html$/i');
define('MATCH_CONTEXT_TEMPLATE_FILE', '/^(.+)\.context\.html$/i');
define('MATCH_PHP_FILE', '/^(.+\.php)$/i');
define('MATCH_INTEGER_PRESIGN', '/^[\+\-]?[0-9]+$/');
define('MATCH_INTEGER_PRESIGN_EMPTY', '/^[\+\-]?[0-9]*$/');
define('MATCH_ORDER', '/^(?:asc|desc)$/');
define('MATCH_TEXTBOX_WIDTH', '/^([0-9]+|auto)$/');
define('MATCH_TEXTBOX_HEIGHT', '/^([0-9]+|auto)$/');
define('MATCH_WEATHER_COLORS', '/^(?:[0-9]{1,3}(\.[0-9]{1,2})?:#[0-9a-f]{6},?)+$/');
define('MATCH_SOCKET', '/^(unix:[a-zA-Z0-9\-_\.\/]+|tcp:[a-zA-Z0-9\-\.]+:[0-9]{1,5})$/');

define('MATCH_WUI_ADDMODIFY_DO', '/^(add|modify)$/');
?>
