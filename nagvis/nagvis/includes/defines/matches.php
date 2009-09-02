<?PHP
/*****************************************************************************
 *
 * matches.php - File for global match constants
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
define('MATCH_ALL', '/^.*$/i');

define('MATCH_STRING', '/^[0-9a-z\s\:\+\[\]\(\)\_\.\,\-\&\?\!\#\@\=\/\\\]+$/i');
define('MATCH_STRING_EMPTY', '/^[0-9a-z\s\:\+\[\]\(\)\_\.\,\-\&\?\!\#\@\=\/\\\]*$/i');
define('MATCH_STRING_NO_SPACE', '/^[0-9a-z\:\+\[\]\(\)\_\.\,\-\&\?\!\#\@\=\/\\\]+$/i');
define('MATCH_STRING_NO_SPACE_EMPTY', '/^[0-9a-z\:\+\[\]\(\)\_\.\,\-\&\?\!\#\@\=\/\\\]*$/i');
define('MATCH_STRING_PATH', '/^[0-9a-z\s\_\.\-\/\\\]+$/i');
define('MATCH_STRING_URL', '/^[0-9a-z\s\:\+\[\]\(\)\=\%\?\&\_\.\-\#\@\=\/\\\]+$/i');
define('MATCH_STRING_URL_EMPTY', '/^[0-9a-z\s\:\+\[\]\(\)\=\%\?\&\_\.\-\#\@\=\/\\\]*$/i');

define('MATCH_INTEGER', '/^[0-9]+$/');
define('MATCH_FLOAT', '/^[0-9]+[\.\,]*[0-9]*$/');
define('MATCH_BOOLEAN', '/^(?:1|0)$/i');

define('MATCH_COLOR', '/^(#?[0-9a-f]{3,6}|transparent)$/i');
define('MATCH_OBJECTTYPE', '/^(?:global|host|service|hostgroup|servicegroup|map|textbox|shape|template)$/i');
define('MATCH_PNGFILE', '/^(.+)\.png$/i');
define('MATCH_PNG_GIF_JPG_FILE', '/^(.+)\.(png|gif|jpg)$/i');
define('MATCH_PNG_GIF_JPG_FILE_OR_NONE', '/^((.+)\.(png|gif|jpg)|none)$/i');
define('MATCH_PNG_GIF_JPG_FILE_OR_URL', '/^((.+)\.(png|gif|jpg)|\[[0-9a-z\s\:\+\[\]\(\)\=\%\?\&\_\.\-\#\@\=\/\\\]+\])$/i');

define('MATCH_VIEW_TYPE', '/^(?:icon|line)$/i');
define('MATCH_VIEW_TYPE_SERVICE', '/^(?:icon|line|gadget)$/i');

define('MATCH_CFG_FILE', '/^(.+)\.cfg$/i');
define('MATCH_HTML_TEMPLATE_FILE', '/^tmpl\.(.+)\.html$/i');
define('MATCH_PHP_FILE', '/^(.+\.php)$/i');
define('MATCH_INTEGER_PRESIGN', '/^[\+\-]?[0-9]+$/');
define('MATCH_ORDER', '/^(?:asc|desc)$/');
define('MATCH_TEXTBOX_WIDTH', '/^([0-9]+|auto)$/');
?>
