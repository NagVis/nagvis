<?PHP
/*****************************************************************************
 *
 * index.php - Main page of NagVis
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de
 *                                                  , michael_luebben@web.de)
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

// Include defines
require('./includes/defines/global.php');
require('./includes/defines/matches.php');

// Include functions
require('./includes/functions/autoload.php');
require('./includes/functions/debug.php');
require('./includes/functions/oldPhpVersionFixes.php');

$controller = new GlobalController();

if (!$controller->isValid()) {
	// FIXME: No GlobalCore object here - that's a problem!
	new GlobalFrontendMessage('ERROR', $controller->getMessage());
}

// ONLY FOR DEBUGGING
//-----------------------------------------------------------------------------------------------
print "Status        : <b>".(($controller->isValid()) ? 'TRUE' : 'FALSE')."</b><br />";
print "Action        : ".$controller->getAction()."<br />";
print "Parametername : ".$controller->getParameterName()."<br />";
print "Message       : ".$controller->getMessage();
//-----------------------------------------------------------------------------------------------
?>
