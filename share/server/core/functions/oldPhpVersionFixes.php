<?php
/*****************************************************************************
 *
 * oldPhpVersionFixes.php - This implements some functions which are present
 *                          in newer PHP versions but are already needed by
 *                          NagVis PHP code
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

/**
 * This implements the function date_default_timezone_set() which is needed
 * since PHP 5.1 by all PHP date functions
 *
 * @author 	Lars Michelsen <lm@larsmichelsen.com>
 */
if(!function_exists('date_default_timezone_set')) {
	function date_default_timezone_set($timezone_identifier) {
		putenv("TZ=".$timezone_identifier);
		return TRUE;
	}
}

// To prevent the annoying and in most cases useless message:
//
// Error: (0) date() [function.date]: It is not safe to rely on the system's
// timezone settings. You are *required* to use the date.timezone setting or
// the date_default_timezone_set() function. In case you used any of those
// methods and you are still getting this warning, you most likely misspelled
// the timezone identifier. We selected 'Europe/Berlin' for 'CEST/2.0/DST' instead
//
// Workaround the problem by setting the systems timezone as PHP default
// timezone. Don't let PHP know about that hack - it would cry again ;-).
if(function_exists("date_default_timezone_get"))
    date_default_timezone_set(@date_default_timezone_get());

?>
