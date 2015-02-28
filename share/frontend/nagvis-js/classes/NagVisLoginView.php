<?php
/*****************************************************************************
 *
 * NagVisLoginView.php - Class for handling the login page
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
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisLoginView {
    public function __construct($CORE) {
    }

    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        global $LOGIN_MSG, $_MAINCFG;
        // Initialize template system
        $TMPL = New FrontendTemplateSystem();
        $TMPLSYS = $TMPL->getTmplSys();

        $target = CoreRequestHandler::getRequestUri('');

        // Add the language to the target url when the user requested a specific language
        if(isset($_GET['lang']) && $_GET['lang'] != '' && strpos($target, 'lang=') === false) {
            if(strpos($target, '?') === false) {
                $target .= '?lang='.$_GET['lang'];
            } else {
                $target .= '&lang='.$_GET['lang'];
            }
        }

        $aData = Array(
            'generalProperties' => $_MAINCFG->parseGeneralProperties(),
            'locales'           => json_encode(Array()),
            'pageTitle' => cfg('internal', 'title') . ' &rsaquo; Log In',
            'htmlBase' => cfg('paths', 'htmlbase'),
            'htmlJs' => cfg('paths', 'htmljs'),
            'htmlCss' => cfg('paths', 'htmlcss'),
            'formTarget' => $target,
            'htmlTemplates' => path('html', 'global', 'templates'),
            'htmlImages' => cfg('paths', 'htmlimages'),
            'maxPasswordLength' => AUTH_MAX_PASSWORD_LENGTH,
            'maxUsernameLength' => AUTH_MAX_USERNAME_LENGTH,
            'langName' => l('Name'),
            'langPassword' => l('Password'),
            'langLogin' => l('Login'),
            'langTitleCookiesDisabled' => l('Cookies disabled'),
            'langTextCookiesDisabled' => l('NagVis is unable to set a cookie in your browser. Please enable cookies for at least the NagVis host.'),
            'loginMsg' => isset($LOGIN_MSG)  && $LOGIN_MSG !== null ? $LOGIN_MSG->msg : '',
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'login'), $aData);
    }
}
?>
