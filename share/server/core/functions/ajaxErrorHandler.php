<?PHP
/*****************************************************************************
 *
 * ajax_error_handler.php - Ajax error handler for the NagVis ajax handlers
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
 * This is a custom error handling function for submitting PHP errors to the
 * ajax requesting frontend
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function ajaxError($errno, $errstr, $file, $line) {
	// Don't handle E_STRICT errors
	if($errno != 2048) {
		echo "Error: (".$errno.") ".$errstr. " (".$file.":".$line.")";
		die();
	}
}

// Enable custom error handling
set_error_handler("ajaxError");

?>
