<?php
/*****************************************************************************
 *
 * debug.php - Some functions for debugging
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
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
 
// Save the start time of NagVis
define('DEBUGSTART',microtime_float());


/**
 * Writes the debug output to the debug file
 *
 * @param		String		Debug message
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function debug($msg) {
	$fh = fopen(DEBUGFILE, 'a');
	fwrite($fh, utf8_encode(microtime_float().' '.$msg."\n"));
	fclose($fh);
}

/**
 * Writes the render time and the called URI to the debug file
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function debugFinalize() {
	debug('###########################################################');
	debug('Render Time: '.(microtime_float()-DEBUGSTART).' URI: '.$_SERVER['REQUEST_URI']);
	debug('###########################################################');
}

/**
 * Returns the current time in microtime as float
 *
 * @return	Float		Microtime
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function microtime_float() {
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}
?>
