<?php
/*****************************************************************************
 *
 * rdp.php - Custom action module for connecting to hosts via RDP
 *           This file defines individual config options for this action
 *           and implements the code to generate a *.rdp file for download
 *           or directly connecting to the hosts via RDP.
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

// Config variables to be registered for custom actions of this name
global $configVars;
$configVars = array(
    'domain' => Array(
        'must'     => 1,
        'editable' => 1,
        'default'  => '',
        'match'    => MATCH_STRING_NO_SPACE_EMPTY
    ),
    'username' => Array(
        'must'     => 1,
        'editable' => 1,
        'default'  => '',
        'match'    => MATCH_STRING_NO_SPACE_EMPTY
    ),
);

if (!function_exists('handle_action_rdp')) {
    function handle_action_rdp($MAPCFG, $objId) {
        $host_name = $MAPCFG->getValue($objId, 'host_name');
        $domain    = $MAPCFG->getValue($objId, 'domain');
        $username  = $MAPCFG->getValue($objId, 'username');
    
        // Get the host address! erm ... looks a little complicated...
        global $_BACKEND;
        $backendIds = $MAPCFG->getValue($objId, 'backend_id');
        $OBJ = new NagVisHost($backendIds, $host_name);
        $OBJ->setConfiguration($MAPCFG->getMapObject($objId));
        $OBJ->queueState(GET_STATE, DONT_GET_SINGLE_MEMBER_STATES);
        $_BACKEND->execute();
        $OBJ->applyState();
        $host_address = $OBJ->getStateAttr(ADDRESS);
    
        // Now generate the .rdp file for the user which is then (hopefully) handled
        // correctly by the users browser.
        header('Content-Type: application/rdp; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$host_name.'.rdp');
    
        echo 'full address:s:'.$host_address."\n";
        if($domain)
            echo 'domain:s:'.$domain."\n";
        if($username)
            echo 'username:s:'.$username."\n";
    }
}

?>
