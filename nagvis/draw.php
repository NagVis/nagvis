<?php
/*****************************************************************************
 *
 * draw.php - Renders the background image of maps
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


// Start the user session (This is needed by some caching mechanism)
@session_start();

// Include defines
require("./includes/defines/global.php");
require("./includes/defines/matches.php");

// Include defines
require("./includes/functions/debug.php");
require("./includes/functions/oldPhpVersionFixes.php");

// Include needed global classes
require("./includes/classes/GlobalGraphic.php");
require("./includes/classes/GlobalMainCfg.php");
require("./includes/classes/GlobalMapCfg.php");
require("./includes/classes/GlobalMap.php");
require("./includes/classes/GlobalPage.php");
require("./includes/classes/GlobalBackground.php");
require("./includes/classes/GlobalLanguage.php");
require("./includes/classes/GlobalBackendMgmt.php");

// Include needed frontend classes
require("./includes/classes/NagVisMap.php");
require("./includes/classes/NagVisBackground.php");
require("./includes/classes/NagVisMapCfg.php");

// Include needed object classes
require("./includes/classes/objects/NagVisObject.php");
require("./includes/classes/objects/NagVisStatefulObject.php");
require("./includes/classes/objects/NagVisStatelessObject.php");
require("./includes/classes/objects/NagiosHost.php");
require("./includes/classes/objects/NagVisHost.php");
require("./includes/classes/objects/NagiosService.php");
require("./includes/classes/objects/NagVisService.php");
require("./includes/classes/objects/NagiosHostgroup.php");
require("./includes/classes/objects/NagVisHostgroup.php");
require("./includes/classes/objects/NagiosServicegroup.php");
require("./includes/classes/objects/NagVisServicegroup.php");
require("./includes/classes/objects/NagVisMapObj.php");
require("./includes/classes/objects/NagVisShape.php");
require("./includes/classes/objects/NagVisTextbox.php");

// Load the main configuration
$MAINCFG = new GlobalMainCfg(CONST_MAINCFG);

// Initialize the map configuration
$MAPCFG = new NagVisMapCfg($MAINCFG,$_GET['map']);
// Read the map configuration file
$MAPCFG->readMapConfig();

// Initialize the backend(s)
$BACKEND = new GlobalBackendMgmt($MAINCFG);

// Initialize the language handling
$LANG = new GlobalLanguage($MAINCFG,'nagvis:global');

// Initialize the background image
$BACKGROUND = new NagVisBackground($MAINCFG,$MAPCFG,$LANG,$BACKEND);
$BACKGROUND->parseObjects();
$BACKGROUND->parseMap();
?>
