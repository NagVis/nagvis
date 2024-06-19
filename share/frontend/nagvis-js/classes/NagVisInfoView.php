<?php
/*****************************************************************************
 *
 * NagVisInfoView.php - Class for handling the rendering of the support
 *                      information page
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
class NagVisInfoView
{
    public function __construct($CORE) {}

    /**
     * Parses the information in html format
     *
     * @return	string 	String with Html Code
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function parse()
    {
        global $AUTH, $AUTHORISATION;
        // Initialize template system
        $TMPL = New FrontendTemplateSystem();
        $TMPLSYS = $TMPL->getTmplSys();

        $userName  = $AUTH->getUser();
        $userId    = $AUTH->getUserId();
        $userRoles = $AUTHORISATION->getUserRoles($userId);
        $userPerms = $AUTHORISATION->parsePermissions();

        $aData = [
            'pageTitle'      => cfg('internal', 'title') . ' &rsaquo; ' . l('supportInfo'),
            'htmlBase'       => cfg('paths', 'htmlbase'),
            'htmlTemplates'  => path('html', 'global', 'templates'),
            'nagvisVersion'  => CONST_VERSION,
            'phpVersion'     => PHP_VERSION,
            'mysqlVersion'   => shell_exec('mysql --version'),
            'os'             => shell_exec('uname -a'),
            'serverSoftware' => $_SERVER['SERVER_SOFTWARE'],
            'scriptFilename' => $_SERVER['SCRIPT_FILENAME'],
            'scriptName' => $_SERVER['SCRIPT_NAME'],
            'requestTime' => $_SERVER['REQUEST_TIME'] . ' (gmdate(): ' . gmdate('r', $_SERVER['REQUEST_TIME']) . ')',
            'phpErrorReporting' => ini_get('error_reporting'),
            'phpSafeMode' => (ini_get('safe_mode')?"yes":"no"),
            'phpMaxExecTime' => ini_get('max_execution_time'),
            'phpMemoryLimit' => ini_get('memory_limit'),
            'phpLoadedExtensions' => implode(", ", get_loaded_extensions()),
            'userAgent' => $_SERVER['HTTP_USER_AGENT'],
            // Auth details
            'logonModule'         => cfg('global', 'logonmodule'),
            'authModule'          => cfg('global', 'authmodule'),
            'authorisationModule' => cfg('global', 'authorisationmodule'),
            'logonEnvVar'         => cfg('global', 'logonenvvar'),
            'logonEnvVal'         => (
                                        isset($_SERVER[cfg('global', 'logonenvvar')])
                                            ? $_SERVER[cfg('global', 'logonenvvar')]
                                            : ''
                                    ),
            'logonEnvCreateUser'  => cfg('global', 'logonenvcreateuser'),
            'logonEnvCreateRole'  => cfg('global', 'logonenvcreaterole'),
            'loggedIn'            => $userName . ' (' . $userId . ')',
            'userRoles'           => json_encode($userRoles),
            'userPerms'           => json_encode($userPerms),
            'userAuthModule'      => $AUTH->getAuthModule(),
            'userLogonModule'     => $AUTH->getLogonModule(),
            'userAuthTrusted'     => ($AUTH->authedTrusted() ? l("yes") : l("no")),
            'compatJsonEncode'    => (l("yes")),
            'compatJsonDecode'    => (l("yes")),
        ];

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'info'), $aData);
    }
}
