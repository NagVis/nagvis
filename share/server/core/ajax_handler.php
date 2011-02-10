<?PHP
/*****************************************************************************
 *
 * ajax_handler.php - Ajax handler for the NagVis frontend
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

// Include global defines
require('../../server/core/defines/global.php');
require('../../server/core/defines/matches.php');

// Include functions
require('../../server/core/functions/autoload.php');
require('../../server/core/functions/debug.php');
require('../../server/core/functions/oldPhpVersionFixes.php');
require('../../server/core/functions/nagvisErrorHandler.php');
require('../../server/core/classes/CoreExceptions.php');

if (PROFILE) profilingStart();

// This defines whether the GlobalMessage prints HTML or ajax error messages
define('CONST_AJAX' , TRUE);

try {
	// Load the core
	$CORE = GlobalCore::getInstance();

	/*
	* Url: Parse the url to know later what module and
	*      action is called. The requested uri is splitted
	*      into elements for later usage.
	*/

	$UHANDLER = new CoreUriHandler($CORE);

	/*
	* Session: Handle the user session
	*/

	$SHANDLER = new CoreSessionHandler();

	/*
	 * Authentication 1: Try to authenticate the user
	 */

	$AUTH = new CoreAuthHandler($CORE, $SHANDLER,
	                   $CORE->getMainCfg()->getValue('global','authmodule'));

	/*
	* Authorisation 1: Collect and save the permissions when the user is logged in
	*                  and nothing other is saved yet
	*/

	if($AUTH->isAuthenticated()) {
		$AUTHORISATION = new CoreAuthorisationHandler($CORE, $AUTH,
		                   $CORE->getMainCfg()->getValue('global', 'authorisationmodule'));
		$AUTHORISATION->parsePermissions();
	} else {
		$AUTHORISATION = null;
	}

	// Make the AA information available to whole NagVis for permission checks
	$CORE->setAA($AUTH, $AUTHORISATION);

	/*
	* Module handling 1: Choose modules
	*/

	// Load the module handler
	$MHANDLER = new CoreModuleHandler($CORE);

	// Register valid modules
	// Unregistered modules can not be accessed
	$MHANDLER->regModule('General');
	$MHANDLER->regModule('Overview');
	$MHANDLER->regModule('Map');
	$MHANDLER->regModule('AutoMap');
	$MHANDLER->regModule('Url');
	$MHANDLER->regModule('ChangePassword');
	$MHANDLER->regModule('Auth');
	$MHANDLER->regModule('Search');
	$MHANDLER->regModule('UserMgmt');
	$MHANDLER->regModule('RoleMgmt');
	$MHANDLER->regModule('MainCfg');
	$MHANDLER->regModule('ManageShapes');
	$MHANDLER->regModule('ManageBackgrounds');
	$MHANDLER->regModule('Multisite');
	$MHANDLER->regModule('User');

	// Load the module
	$MODULE = $MHANDLER->loadModule($UHANDLER->get('mod'));
	if($MODULE == null) {
		new GlobalMessage('ERROR', $CORE->getLang()->getText('The module [MOD] is not known',
		                                      Array('MOD' => htmlentities($UHANDLER->get('mod')))));
	}
	$MODULE->passAuth($AUTH, $AUTHORISATION);
	$MODULE->setAction($UHANDLER->get('act'));
	$MODULE->initObject();

	/*
	* Authorisation 2: Check if the user is permitted to use this module/action
	*                  If not redirect to Msg/401 (Unauthorized) page
	*/

	if($UHANDLER->get('mod') != $CORE->getMainCfg()->getValue('global', 'logonmodule')
	   && ($UHANDLER->get('mod') == 'Auth' || $AUTH->isAuthenticated())) {
			// Only check modules which should have authorisation checks
			// This are all modules excluded some core things
			// Check if the user is permited to access this (module, action, object)
			if($MODULE->actionRequiresAuthorisation())
				$MODULE->isPermitted();
	} else {
		// At the moment the login at ajax_handler is only possible via env auth.
		// Should be enough for the moment
		$MHANDLER = new CoreModuleHandler($CORE);
		$MHANDLER->regModule('LogonEnv');
		$MODULE = $MHANDLER->loadModule('LogonEnv');
		$MODULE->beQuiet();
		$MODULE->setAction('view');

		// Try to auth using the environment auth
		if($MODULE->handleAction() === false) {
			// When not authenticated show error message
			new GlobalMessage('ERROR', $CORE->getLang()->getText('You are not authenticated'),
	                                      null, $CORE->getLang()->getText('Access denied'));
		}
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
		throw new NagVisException($CORE->getLang()->getText('The given action is not valid'));
	}
} catch(NagVisException $e) {
	new GlobalMessage('ERROR', $e->getMessage());
}

echo $sContent;
if (DEBUG&&DEBUGLEVEL&4) debugFinalize();
if (PROFILE) profilingFinalize('core-'.$UHANDLER->get('mod').'-'.$UHANDLER->get('act'));

exit(0);

?>
