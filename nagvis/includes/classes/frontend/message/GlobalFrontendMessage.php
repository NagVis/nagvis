<?php
/*****************************************************************************
 *
 * GlobalFronendMessage.php - Class to display a message in the frontend
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: michael_luebben@web.de)
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
 * class GlobalFronendMessage
 *
 * @param   string   $type
 *				Follow type available:
 *					- note 	 -> Blue box for Information
 *					- warning -> Yellow box for warnings messages
 *					- error	 -> Red box for errors messages
 *					- ok		 -> Green box ok messages
 *
 * @param   string   $title	Title for the Box
 * @param   string   $message	Message
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalFrontendMessage {

	private $page;

	function __construct($type, $title, $message) {

		$this->page  = '<html>'."\n";
		$this->page .= '   <head>'."\n";
		$this->page .= '      <meta http-equiv="Content-Type" content="text/html;charset=utf-8">'."\n";
		$this->page .= '      <style type="text/css"><!-- @import url(/nagios/nagvis/nagvis/includes/css/frontendMessage.css);  --></style>'."\n";
		$this->page .= '      <title>'.$title.'</title></head>'."\n";
		$this->page .= '   </head>'."\n";
		$this->page .= '   <body>'."\n";
		$this->page .= '      <div id="shadow">'."\n";
		$this->page .= '         <table class="messageBox" id="'.$type.'MessageBoxBorder" border="1" cellpadding="0" cellspacing="0">'."\n";
		$this->page .= '            <tr>'."\n";
		$this->page .= '               <td class="'.$type.'MessageBoxBackground">'."\n";
		$this->page .= '                  <table cellpadding="0" cellspacing="0" width="100%" height="10">'."\n";
		$this->page .= '                     <tr>'."\n";
		$this->page .= '                        <td  class="'.$type.'MessageBoxBackground">'."\n";
		$this->page .= '                        </td>'."\n";
		$this->page .= '                     </tr>'."\n";
		$this->page .= '                  </table>'."\n";
		$this->page .= '                  <table cellpadding="0" cellspacing="0" width="100%">'."\n";
		$this->page .= '                     <tr>'."\n";
		$this->page .= '                        <td id="'.$type.'MessageHeader" align="center" width="60">'."\n";
		$this->page .= '                           <img src="/nagios/nagvis/nagvis/images/internal/img_'.$type.'.png"/>'."\n";
		$this->page .= '                        </td>'."\n";
		$this->page .= '                        <td class="messageHeader" id="'.$type.'MessageHeader">'."\n";
		$this->page .=                             $title."\n";
		$this->page .= '                        </td>'."\n";
		$this->page .= '                        <td id="'.$type.'MessageHeader" align="center" width="60">'."\n";
		$this->page .= '                           <img src="/nagios/nagvis/nagvis/images/internal/img_'.$type.'.png"/>'."\n";
		$this->page .= '                        </td>'."\n";
		$this->page .= '                     </tr>'."\n";
		$this->page .= '                  </table>'."\n";
		$this->page .= '                  <table cellpadding="0" cellspacing="0" width="100%" height="78%">'."\n";
		$this->page .= '                     <tr>'."\n";
		$this->page .= '                        <td  class="'.$type.'MessageBoxBackground">'."\n";
		$this->page .=                             $message."\n";
		$this->page .= '                        </td>'."\n";
		$this->page .= '                     </tr>'."\n";
		$this->page .= '                  </table>'."\n";
		$this->page .= '               </td>'."\n";
		$this->page .= '            </tr>'."\n";
		$this->page .= '         </table>'."\n";
		$this->page .= '      </div>'."\n";
	}

	function __toString () {

		$this->page .= '   </body>'."\n";
		$this->page .= '</html>'."\n";

		return $this->page;
	}
}
?>