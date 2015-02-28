<?php
/*****************************************************************************
 *
 * FronendMessage.php - Class to render a messagebox in the frontend (WUI
 *                      and regular NagVis frontend)
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

class FrontendMessage {
    private $pathHtmlBase;
    private $type;
    private $message;
    private $title;

    public function __construct($message) {
        $this->message      = $message;

        $this->title        = 'ERROR';
        $this->type         = 'error';
        $this->pathHtmlBase = cfg('paths', 'htmlbase');
    }

    /**
     * Build a message box for the user in HTML format
     *
     * @return  String    Returns the html code of this message
     * @access  private
     * @author  Michael Luebben <michael_luebben@web.de>
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function buildMessage() {
        $content = '';
        $content .= '<link rel="stylesheet" type="text/css" href="'.$this->pathHtmlBase.'/userfiles/templates/default.css" />'."\n";
        $content .= '<div id="messageBoxDiv">'."\n";
        $content .= '   <table id="messageBox" class="'.$this->type.'" height="100%" width="100%" cellpadding="0" cellspacing="0">'."\n";
        $content .= '      <tr>'."\n";
        $content .= '         <td class="'.$this->type.'" colspan="3" height="16">'."\n";
        $content .= '      </tr>'."\n";
        $content .= '      <tr height="32">'."\n";
        $content .= '         <th class="'.$this->type.'" align="center" width="60">'."\n";
        $content .= '           <img src="'.$this->pathHtmlBase.'/frontend/nagvis-js/images/internal/msg_'.$this->type.'.png"/>'."\n";
        $content .= '         </th>'."\n";
        $content .= '         <th class="'.$this->type.'">'.$this->title.'</td>'."\n";
        $content .= '         <th class="'.$this->type.'" align="center" width="60">'."\n";
        $content .= '           <img src="'.$this->pathHtmlBase.'/frontend/nagvis-js/images/internal/msg_'.$this->type.'.png"/>'."\n";
        $content .= '         </th>'."\n";
        $content .= '       </tr>'."\n";
        $content .= '       <tr>'."\n";
        $content .= '         <td class="'.$this->type.'" colspan="3" style="padding-top:16px;">'.$this->message.'</td>'."\n";
        $content .= '       </tr>'."\n";
        $content .= '   </table>'."\n";
        $content .= '</div>'."\n";

        return $content;
    }

    /**
     * Print the message box
     *
     * return   String  HTML Code
     * @access  private
     * @author  Michael Luebben <michael_luebben@web.de>
     */
    public function __toString () {
        return $this->buildMessage();
    }
}
?>
