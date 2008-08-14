<?php
/*****************************************************************************
 *
 * NagVisErrorHandling.php - Handles error messages from nagvis
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
 * class NagVisErrorHandling
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class NagVisErrorHandling {

	// Contains core object
	private $CORE;

	// Contains language object
	private $LANG;

	// Contains type of message
	private $type;

	// Contains title for the message box
	private $title;

	// Contains message
	private $message;

	// Contains variables which be change in the message text. Example -> 'TYPE~'.$type.
	private $messageVariables;

	public function __construct($type, $message, $messageVariables,$title=NULL, CORE $CORE=NULL) {

		$this->type = $type;
		$this->title = $title;
		$this->message = $message;
		$this->messageVariables = $messageVariables;

		// Load the core
		if(!is_Object($CORE)) {
			$this->CORE = new GlobalCore();
		} else {
			$this->CORE = $CORE;
		}

		$this->LANG = $this->CORE->LANG;


		// Initialize the frontend
		$FRONTEND = new GlobalPage($this->CORE);
		$FRONTEND->messageToUser($this->LANG->getText($this->type), $this->LANG->getText($message, $messageVariables));
	}
}
?>