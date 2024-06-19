<?PHP
/*****************************************************************************
 *
 * matches.php - File for global match constants
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

/**
 * @author	Lars Michelsen <lm@larsmichelsen.com>
 */
const MATCH_ALL = '/^.*$/mi';
const MATCH_NOT_EMPTY = '/^.+$/';
const MATCH_REGEX = '/^.*$/i';

// These regex allow unicode matching
const MATCH_STRING = '/^[0-9a-zа-яё\p{L}\s:+[\]()_.,\-\*?!#@=\/\\\]+$/iu';
const MATCH_STRING_EMPTY = '/^[0-9a-zа-яё\p{L}\s:+[\]()_.,\-\*?!#@=\/\\\\*]*$/iu';
const MATCH_STRING_NO_SPACE = '/^[0-9a-zа-яё\p{L}:+[\]()_.,\-\*?!#@=\/\\\\*]+$/iu';
const MATCH_STRING_NO_SPACE_EMPTY = '/^[0-9a-zа-яё\p{L}:+[\]()_.,\-\*?!#@=\/\\\]*$/iu';
const MATCH_CONDITION = '/^[0-9a-zа-яё\p{L}\s_\-~=]*$/iu';

const MATCH_STRING_PATH = '/^[0-9a-z\s_.\-\/\\\]+$/i';
const MATCH_STRING_URL = '/^[0-9a-z\s:;|+[\]()=%?&_,.\-#@=\/\\\~\{\}]+$/i';
const MATCH_STRING_URL_EMPTY = '/^[0-9a-z\s:;|+[\]()=%?&_,.\-#@=\/\\\~]*$/i';
const MATCH_GADGET_OPT = '/^[0-9a-z\s:+[\]()_.,\-&?!#@=\/\\\%]+$/i';
const MATCH_STRING_STYLE = '/^[0-9a-z:;\-+%#(),.]*$/i';
const MATCH_COORDS = '/^(?:(?:[0-9]+)|([a-z0-9]+(?:%[+-][0-9]+)?))$/';
const MATCH_COORDS_MULTI = '/^(?:(?:(?:[0-9]+)|(?:-?[0-9]+(?:.[0-9]+)?)|([a-z0-9]+(?:%[+-][0-9]+)?)),?)+$/';
const MATCH_COORDS_MULTI_EMPTY = '/^(?:(?:(?:[0-9]+)|([a-z0-9]+(?:%[+-][0-9]+)?))[.,]?)*$/';
const MATCH_COORD_SIMPLE = '/^[0-9]+$/';

const MATCH_INTEGER = '/^[0-9]+$/';
const MATCH_INTEGER_EMPTY = '/^[0-9]*$/';
const MATCH_FLOAT = '/^[0-9]+[.,]*[0-9]*$/';
const MATCH_FLOAT_EMPTY = '/^([0-9]+[.,]?[0-9]*)*$/';
const MATCH_BOOLEAN = '/^(?:1|0)$/i';
const MATCH_BOOLEAN_EMPTY = '/^(?:1|0)*$/i';
const MATCH_LATLONG = '/^-?[0-9]+(.[0-9]+),-?[0-9]+(.[0-9]+)?$/';

const MATCH_COLOR = '/^(#?[0-9a-f]{3,8}|transparent)$/i';
const MATCH_OBJECTTYPE = '/^(?:global|host|service|dyngroup|aggr|hostgroup|servicegroup|map|textbox|shape|line|template|container)$/i';
const MATCH_OBJECTID = '/^(?:[a-z0-9]+)$/i';
const MATCH_OBJECTID_EMPTY = '/^(?:[a-z0-9]*)$/i';
const MATCH_PNGFILE = '/^([^\s]+)\.png$/iu';
const MATCH_PNG_GIF_JPG_FILE = '/^([^\s]+)\.(png|gif|jpg)$/iu';
const MATCH_PNG_GIF_JPG_FILE_OR_URL_NONE = '/^((.+)\.(png|gif|jpg)|\[[0-9a-z\s:+[\]()=%?&_.\-#@=\/\\\]+\]|none)$/iu';
const MATCH_PNG_GIF_JPG_FILE_OR_URL = '/^((.+)\.(png|gif|jpg)|\[[0-9a-z\s:+[\]()=%?&_.\-#@=\/\\\]+\])$/iu';
const MATCH_ROTATION_STEP_TYPES_EMPTY = '/^(?:map|url)?$/';
const MATCH_LANGUAGE_EMPTY = '/^[a-zA-Z0-9\-_]*$/';
const MATCH_LANGUAGE_FILE = '/^([^.].*)/';
const MATCH_ICONSET = '/^(.+)_ok.(png|gif|jpg)$/u';
const MATCH_BACKEND_FILE = '/^GlobalBackend([^MI].+)\.php$/';
const MATCH_BACKEND_ID = '/^[0-9a-z._-]*$/iu';
const MATCH_DOC_DIR = '/^([a-z]{2}_[A-Z]{2})/';
const MATCH_MAINCFG_FILE = '/^.+\.ini\.php$/i';

const MATCH_SERVICE_DESCRIPTION = '/^[0-9a-zа-яё\p{L}\s:+[\]()_.,\'\-\*?!#@=\/\\\]+$/iu';
const MATCH_MAP_NAME = '/^[0-9A-Za-z_\-]+$/';
const MATCH_MAP_NAME_EMPTY = '/^[0-9A-Za-z_-]*$/';
const MATCH_ROTATION_NAME = '/^[0-9A-Za-z_-]+$/';
const MATCH_ROTATION_NAME_EMPTY = '/^[0-9A-Za-z_-]*$/';
const MATCH_BACKGROUND_NAME = '/^[0-9A-Za-z_-]+$/';
const MATCH_VIEW_TYPE = '/^(?:icon|line)$/i';
const MATCH_VIEW_TYPE_OBJ = '/^(?:icon|line|gadget)$/i';
const MATCH_VIEW_TYPE_CONTAINER = '/^(?:inline|iframe)$/i';
const MATCH_GET_OBJECT_TYPE = '/^(state|full|complete|summary)$/';
const MATCH_GADGET_TYPE = '/^(?:img|html)$/i';
const MATCH_OBJECT_TYPES = '/^(host|service|hostgroup|servicegroup|dyngroup|aggr|map)$/';
const MATCH_AUTOMAP_RENDER_MODE = '/^(directed|undirected|radial|circular|undirected2|undirected3)?$/';
const MATCH_AUTOMAP_RANKDIR = '/^(TB|LR|BT|RL)?$/';
const MATCH_AUTOMAP_OVERLAP = '/^(true|false|scale|scalexy|ortho|orthoxy|orthoyx|compress|ipsep|vpsc|prism)?$/';
const MATCH_AUTOMAP_BUSINESS_IMPACT = '/^(0_development|1_testing|2_standard|3_production|4_top_production|5_business_critical)?$/';
const MATCH_AUTOMAP_HOSTNAME = '/^[0-9a-zа-яё\p{L}:+[\]()_.,\-?!#@=\/\\\<>]+$/iu';
const MATCH_LINE_TYPE = '/^(10|11|12|13|14|15)?$/';
const MATCH_LINE_ARROW = '/^(none|forward|back|both)?$/';
const MATCH_USER_NAME = '/^[0-9A-Za-z_\-.@\s]+$/';
const MATCH_ROLE_NAME = '/^[0-9A-Za-z_\-.@\s]+$/';
const MATCH_DYN_GROUP_TYPES = '/^(?:host|service)$/';
const MATCH_DYN_OBJECT_TYPES = '/^(?:host|service|hostgroup|servicegroup)$/';
const MATCH_LIVESTATUS_FILTER = '/^(?:Filter: .*\\\n)+$/i';
const MATCH_ZOOM_FACTOR = '/^(?:[0-9]+|fill)$/';

const MATCH_URI_PART = '/^[a-zA-Z0-9_-]*$/';

const MATCH_CFG_FILE = '/^(.+)\.cfg$/u';
const MATCH_CSV_FILE = '/^(.+)\.csv$/iu';
const MATCH_MP3_FILE = '/^(.+)\.mp3$/iu';
const MATCH_HEADER_TEMPLATE_FILE = '/^(.+)\.header\.html$/iu';
const MATCH_HOVER_TEMPLATE_FILE = '/^(.+)\.hover\.html$/iu';
const MATCH_CONTEXT_TEMPLATE_FILE = '/^(.+)\.context\.html$/iu';
const MATCH_PHP_FILE = '/^(.+\.php)$/iu';
const MATCH_SOURCE_FILE = '/^(.+)\.php$/iu';
const MATCH_INTEGER_PRESIGN = '/^[+-]?[0-9]+$/';
const MATCH_INTEGER_PRESIGN_EMPTY = '/^[+-]?[0-9]*$/';
const MATCH_LABEL_X = '/^([+-]?[0-9]+|center)$/';
const MATCH_LABEL_Y = '/^([+-]?[0-9]+|bottom)$/';
const MATCH_ORDER = '/^(?:asc|desc)$/';
const MATCH_TEXTBOX_WIDTH = '/^([0-9]+|auto)$/';
const MATCH_TEXTBOX_HEIGHT = '/^([0-9]+|auto)$/';
const MATCH_WEATHER_COLORS = '/^(?:[0-9]{1,4}(\.[0-9]{1,2})?:#[0-9a-f]{6},?)+$/';
const MATCH_SOCKET = '/^(unix:[a-zA-Z0-9\-_.\/]+|tcp(-tls)?:[a-zA-Z0-9.-]+:[0-9]{1,5})$/';

const MATCH_WUI_ADDMODIFY_DO = '/^(add|modify)$/';
