<?php
/*****************************************************************************
 *
 * FronendMessage.php - Class to render a messagebox in the frontend (WUI
 *                      and regular NagVis frontend)
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
 * class FrontendMessage
 *
 * @param   string   $type
 *			Follow type available:
 *				Message type for services:
 *					- ok		 		-> Green box for ok messages
 *					- warning 		-> Yellow box for warning messages
 *					- unknown 		-> orange box for unknown messages
 *					- critical	 	-> Red box for error messages
 *
 *				Message type for hosts:
 *					- up		 		-> Green box for up messages
 *					- down	 		-> Red box for error messages
 *					- unknown 		-> orange box for unknown messages
 *					- unreachable 	-> orange box for unknown messages
 *
 *				Message type for another messages:
 *					- note 	 		-> Blue box for Information
 *					- error	 		-> Red box for error messages
 *					- permission	-> Orange box for permission messages
 *
 * @param   string   $title	Title for the Box
 * @param   string   $message	Message
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
class FrontendMessage {
	// Contains path to the html base
	private $pathHtmlBase;

	//This variables contains information which will be used to build the message box
	private $type;
	private $message;
	private $title;
	private $reloadTime = null;
	private $reloadUrl = null;

	/**
	 * The contructor checks the type and builds the message box
	 *
	 * @param   strind	$type
	 * @param   string	$message
	 * @param   string	$pathHtmlBase
	 * @param   string	$title
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($type, $message, $pathHtmlBase, $title) {
		$this->type = $type;
		$this->message = $message;
		$this->pathHtmlBase = $pathHtmlBase;
		$this->title = $title;
	}
	
	/**
	 * Sets the redirect/reload information for the message box
	 *
	 * @access  public
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function reload($time, $url) {
		$this->reloadTime = $time;
		$this->reloadUrl = $url;
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
		
		// Have to reload or redirect?
		if($this->reloadTime !== null) {
			if($this->reloadUrl === null) {
				$content .= '<meta http-equiv="refresh" content="'.$this->reloadTime.'">'."\n";
			} else {
				$content .= '<meta http-equiv="refresh" content="'.$this->reloadTime.'; URL='.$this->reloadUrl.'">'."\n";
			}
		}
		
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
