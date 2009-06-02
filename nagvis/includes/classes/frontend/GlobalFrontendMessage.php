<?php
/*****************************************************************************
 *
 * GlobalFrontendMessage.php - Handles error messages in NagVis and WUI frontend
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
 * class GlobalFrontendMessage
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalFrontendMessage {
	/**
	 * The constructor atm only wrapper for GlobalFrontendMessageBox
	 *
	 * @param   strind	$type
	 * @param   string	$message
	 * @param   string	$pathHtmlBase
	 * @param   string	$title
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __construct($type, $message, $pathHtmlBase = NULL, $title = NULL) {
		if($pathHtmlBase === NULL) {
			$CORE = new GlobalCore();
			$pathHtmlBase = $CORE->MAINCFG->getValue('paths', 'htmlbase');
		}
		
		// New method
		print(new GlobalFrontendMessageBox($type, $message, $pathHtmlBase, $title));
		
		if($type == 'ERROR' || $type == 'INFO-STOP') {
			exit(1);
		}
	}
}
?>