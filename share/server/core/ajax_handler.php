<?PHP
/*****************************************************************************
 *
 * ajax_handler.php - Ajax handler for the NagVis frontend
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

// Include global defines
require('../../server/core/defines/global.php');
require('../../server/core/defines/matches.php');

// Include functions
require('../../server/core/classes/CoreExceptions.php');
require('../../server/core/functions/autoload.php');

if (PROFILE) {
    profilingStart();
}

const CONST_AJAX = true;
header('Content-Type: application/json');

try {
    require('../../server/core/functions/core.php');
    $MHANDLER = new CoreModuleHandler();
    $_name    = 'core';
    $_modules = [
        'General',
        'Overview',
        'Map',
        'Url',
        'ChangePassword',
        'Auth',
        'Search',
        'UserMgmt',
        'RoleMgmt',
        'MainCfg',
        'ManageShapes',
        'ManageBackgrounds',
        'Multisite',
        'User',
        'Action',
    ];
    require('../../server/core/functions/index.php');
    exit(0);
} catch(NagVisException $e) {
    echo $e;
} catch(NagVisErrorException $e) {
    echo json_encode([
        'type'    => 'error',
        'message' => (string)$e,
        'title'   => l('Error: PHP Error'),
    ]);
} catch(Exception $e) {
    echo json_encode([
        'type'    => 'error',
        'message' => $e->getMessage(),
        'title'   => l('Error: Unhandled Exception'),
    ]);
}
exit(1);


