<?PHP
/*****************************************************************************
 *
 * std_lq.php - Example helper script to query a requested livestatus backend
 *              with a custom livestatus query.
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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
 * You have to call this script with at least two parameters:
 *
 *   backend_id - The id of the backend to use as configured in NagVis
 *   query      - The livestatus query to execute (newlines must be given as \\n)
 *
 * Optional parameters:
 *
 *   type       - Customize the way the resulting data is displayed
 *
 * Example calls to this script:
 *
 * Query a single cell as simple string:
 * std_lq.php?backend_id=live_1&query=GET hosts\\nLimit: 1\\nColumns: host_name\\n&type=cell
 *
 * Query several columns from a single dataset. Resulting values are displayed separated by ", ":
 * std_lq.php?backend_id=live_1&query=GET hosts\\nLimit: 1\\nColumns: host_name address state\\n&type=row
 *
 * Query one column from several datasets. Each found cell is displayed in a separate line:
 * std_lq.php?backend_id=live_1&query=GET hosts\\nLimit: 2\\nColumns: host_name\\n&type=column
 * 
 * Query several columns from several datasets and display them as table:
 * std_lq.php?backend_id=live_1&query=GET hosts\\nLimit: 2\\nColumns: host_name state address\\n&type=list
 */

// Modify the default allowed query type match regex. By default only GET requests are allowed.
$queryTypes  = 'GET';
// Modify the matchig regex for the allowed tables
$queryTables = '([a-z]+)';
// Adds the username of the authed user to the livestatus query to filter the objects to query
// according to the equal named nagios contact. This is enabled by default.
// If you trust your users and do not want to limit the results with the users permissions 
// you can set this to false. But be aware: Everyone who can access this script through the 
// browser is able to fetch all information from the configured livestatus backends.
$setAuthUser = true;

/*** *** *** *** *** *** *** END OF CONFIGURATION *** *** *** *** *** *** ***/

if(file_exists('../../server/core/defines/global.php')) {
    $_nv_core_dir = '../../server/core';
} else {
    // handle OMD local/ hierarchy
    $_path_parts = explode('/', dirname($_SERVER["SCRIPT_FILENAME"]));
    if($_path_parts[count($_path_parts) - 6] == 'local') {
        $_nv_core_dir = join(array_slice(explode('/' ,dirname($_SERVER["SCRIPT_FILENAME"])), 0, -6), '/').'/share/nagvis/htdocs/server/core';
    } else {
        echo 'ERROR: Unable to detect nagvis core dir';
        exit(1);
    }
}

// Include global defines
require($_nv_core_dir.'/defines/global.php');
require($_nv_core_dir.'/defines/matches.php');

// Include functions
require($_nv_core_dir.'/functions/autoload.php');
require($_nv_core_dir.'/functions/debug.php');
require($_nv_core_dir.'/functions/oldPhpVersionFixes.php');
require($_nv_core_dir.'/classes/CoreExceptions.php');
require($_nv_core_dir.'/functions/nagvisErrorHandler.php');

define('CONST_AJAX', true);

try {
    require($_nv_core_dir.'/functions/core.php');

    // Authenticate the user
    $SHANDLER = new CoreSessionHandler();
    $AUTH = new CoreAuthHandler();
    if(!($AUTH->sessionAuthPresent() && $AUTH->isAuthenticatedSession())) {
        // ...otherwise try to auth the user
        // Logon Module?
        // -> Received data to check the auth? Then check auth!
        // -> Save to session if logon module told to do so!
        $logonModule = 'Core' . cfg('global', 'logonmodule');
        $logonModule = $logonModule == 'CoreLogonDialog' ? 'CoreLogonDialogHandler' : $logonModule;
        $MODULE = new $logonModule(GlobalCore::getInstance());
        $ret = $MODULE->check();
    }

    if(!$AUTH->isAuthenticated()) {
        throw new NagVisException('Not authenticated.');
    }

    $username = $AUTH->getUser();

    if(!isset($_GET['backend_id'])) {
        throw new UserInputError('The parameter "backend_id" is missing.');
    }

    if(!isset($_GET['query'])) {
        throw new UserInputError('The parameter "query" is missing.');
    }

    if(!isset($_GET['type'])) {
        $type = 'raw';
    } else {
        $type = $_GET['type'];
    }

    $backendId = $_GET['backend_id'];
    $query     = str_replace('\\\\n', "\n", $_GET['query']);

    if($setAuthUser) {
        $query .= 'AuthUser: '.$username."\n";
    }

    // Validate the query
    if(!preg_match("/^".$queryTypes."\s".$queryTables."\n/", $query)) {
        throw new UserInputError('Invalid livestatus query.');
    }

    $B = $_BACKEND->getBackend($backendId);

    switch($type) {
        case 'cell':
            // Display the string of the single result cell
            $result = $B->query('column', $query);
            if(!isset($result[0])) {
                throw new UserInputError('Got empty response');
            }

            echo $result[0];
        break;

        case 'column':
            // Display one cell per row
            $result = $B->query($type, $query);
            if(!isset($result[0])) {
                throw new UserInputError('Got empty response');
            }

            echo implode('<br>', $result);
        break;

        case 'row':
            // Display items comma separated
            $result = $B->query($type, $query);
            if(!isset($result[0])) {
                throw new UserInputError('Got empty response');
            }

            echo implode(', ', $result);
        break;

        case 'list':
            // Display a table which formats the result in a readable way
            $result = $B->query($type, $query);
            if(!isset($result[0])) {
                throw new UserInputError('Got empty response');
            }

            echo '<table>';
            foreach($result AS $line) {
                echo '<tr>';
                foreach($line AS $cell) {
                    echo '<td>'.$cell.'</td>';
                }
                echo '</tr>';
            };
            echo '</table>';
        break;

        default:
            echo json_encode($B->query($type, $query));
        break;
    }

    exit(0);
} catch(NagVisException $e) {
    echo 'Exception (std_lq.php): ' .$e->getMessage();
}
exit(1);

?>
