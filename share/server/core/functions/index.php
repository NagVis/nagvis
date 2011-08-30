<?php
/*******************************************************************************
 *
 * index.php - This file is included by the single index files in NagVis to
 *             consolidate equal code
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
 ******************************************************************************/

/*
* Url: Parse the url to know later what module and
*      action is called. The requested uri is splitted
*      into elements for later usage.
*/

$UHANDLER = new CoreUriHandler();

/*
* Session: Handle the user session
*/

$SHANDLER = new CoreSessionHandler();

/*
 * Authentication: Try to authenticate the user
 */

$AUTH = new CoreAuthHandler($CORE, $SHANDLER, cfg('global','authmodule'));

/*
* Authorisation 1: Collect and save the permissions when the user is logged in
*                  and nothing other is saved yet
*/

if($AUTH->isAuthenticated()) {
    $AUTHORISATION = new CoreAuthorisationHandler($CORE, $AUTH, cfg('global', 'authorisationmodule'));
    $AUTHORISATION->parsePermissions();
} else
    $AUTHORISATION = null;

// Make the AA information available to whole NagVis for permission checks
$CORE->setAA($AUTH, $AUTHORISATION);

// Re-set the language to handle the user individual language
$_LANG->setLanguage(HANDLE_USERCFG);

/*
* Module handling 1: Choose modules
*/

// Register valid modules
// Unregistered modules can not be accessed
foreach($_modules AS $mod)
    $MHANDLER->regModule($mod);

// Load the module
$MODULE = $MHANDLER->loadModule($UHANDLER->get('mod'));
if($MODULE == null)
    throw new NagVisException(l('The module [MOD] is not known', Array('MOD' => htmlentities($UHANDLER->get('mod')))));
$MODULE->passAuth($AUTH, $AUTHORISATION);
$MODULE->setAction($UHANDLER->get('act'));
$MODULE->initObject();

/*
* Authorisation 2: Check if the user is permitted to use this module/action
*                  If not redirect to Msg/401 (Unauthorized) page
*/

// Only proceed with authenticated users
if($UHANDLER->get('mod') != cfg('global', 'logonmodule')
   && ($AUTH->isAuthenticated() || (CONST_AJAX && $UHANDLER->get('mod') == 'Auth'))) {
    // Only check modules which should have authorisation checks
    // This are all modules excluded some core things
    // Check if the user is permited to access this (module, action, object)
    if($MODULE->actionRequiresAuthorisation())
        $MODULE->isPermitted();
} elseif(CONST_AJAX) {
    // At the moment the login at ajax_handler is only possible via env auth.
    // Should be enough for the moment
    $MHANDLER = new CoreModuleHandler($CORE);
    $MHANDLER->regModule('LogonEnv');
    $MODULE = $MHANDLER->loadModule('LogonEnv');
    $MODULE->beQuiet();
    $MODULE->setAction('view');
    
    // Try to auth using the environment auth
    // When not authenticated show error message
    if($MODULE->handleAction() === false)
        throw new NagVisException(l('You are not authenticated'), null, l('Access denied'));
} else {
    // When not authenticated redirect to logon dialog
    $MODULE = $MHANDLER->loadModule(cfg('global', 'logonmodule'));
    $UHANDLER->set('act', 'view');
}

/*
* Module handling 2: Render the modules when permitted
*                    otherwise handle other pages
*/

// Handle regular action when everything is ok
// When no matching module or action is found show the 404 error
if($MODULE !== false && $MODULE->offersAction($UHANDLER->get('act'))) {
    $MODULE->setAction($UHANDLER->get('act'));

    // Handle the given action in the module
    $sContent = $MODULE->handleAction();
} else {
    // Create instance of msg module
    throw new NagVisException(l('The given action is not valid'));
}

echo $sContent;
if (DEBUG&&DEBUGLEVEL&4) debugFinalize();
if (PROFILE) profilingFinalize($_name.'-'.$UHANDLER->get('mod').'-'.$UHANDLER->get('act'));

exit(0);

?>
