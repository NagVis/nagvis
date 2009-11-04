<?php
/*****************************************************************************
 *
 * GlobalMessage.php - Handles messages to the user in Frontend, WUI and also
 *                     in the ajax handler
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
 * class GlobalMessage
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalMessage {
	// This array contains allowed types of message boxes
	private $allowedTypes = array(
	  'error',
	  'down',
	  'critical',
	  'up',
	  'ok',
	  'warning',
	  'unknown',
	  'unreachable',
	  'note',
	  'permission');
	
	/**
	 * Class constructor
	 *
	 * @param   strind	$type
	 * @param   string	$message
	 * @param   string	$pathHtmlBase
	 * @param   string	$title
	 * @param   string  $sRedirect
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
 	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($type, $message, $pathHtmlBase = NULL, $title = NULL, $iReloadTime = null, $sReloadUrl = null) {
		// Gather htmlBase path if not provided by method calls
		if($pathHtmlBase === NULL) {
			$pathHtmlBase = GlobalCore::getInstance()->getMainCfg()->getValue('paths', 'htmlbase');
		}
		
		if($title === NULL) {
			$title = $type;
		}
		
		// Remap old types
		if($type == 'info-stop') {
			$type = 'note';
		}
		
		// Check if type allowed else build error box
		if(!in_array($type, $this->allowedTypes)) {
			$this->title = GlobalCore::getInstance()->getLang()->getText('Unknown message type');
			$this->message = GlobalCore::getInstance()->getLang()->getText('The given message type [TYPE] is not known', Array('TYPE' => $type));
		}
		
		// Got all information, now build the message
		// Build an ajax compatible message or a message in HTML format depending on
		// page which is being called
		if(CONST_AJAX) {
			$MSG = new CoreMessage(strtolower($type), $message, $pathHtmlBase, $title);
			
			// Maybe need to reload/redirect
			if($iReloadTime !== null) {
				$MSG->reload($iReloadTime, $sReloadUrl);
			}
			
			print($MSG);
		} else {
			$MSG = new FrontendMessage(strtolower($type), $message, $pathHtmlBase, $title);
			
			// Maybe need to reload/redirect
			if($iReloadTime !== null) {
				$MSG->reload($iReloadTime, $sReloadUrl);
			}
			
			print($MSG);
		}
		
		if($type == 'ERROR' || $type == 'INFO-STOP') {
			exit(1);
		}
	}
}
?>
