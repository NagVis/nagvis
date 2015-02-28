<?php
/*****************************************************************************
 *
 * debug.php - Some functions for debugging
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
	debug('==> Render Time: '.round((microtime_float() - DEBUGSTART), 2).'sec'
             .' Mem peak: '.round(memory_get_peak_usage()/1024/1024, 2).'Mb'
             .' URI: '.$_SERVER['REQUEST_URI']);
}

function log_mem($txt = 'somewhere') {
    if (DEBUG && DEBUGLEVEL & 2)
        debug('mem ['.$txt.']: ' . round(memory_get_usage()/1024/1024, 2) . 'Mb');
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

function profilingStart() {
	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}

function profilingFinalize($pre) {
	include_once "/usr/share/php5-xhprof/xhprof_lib/utils/xhprof_lib.php";
	include_once "/usr/share/php5-xhprof/xhprof_lib/utils/xhprof_runs.php";

	$xhprof_runs = new XHProfRuns_Default();
	$xhprof_runs->save_run(xhprof_disable(), 'nagvis-'.$pre);
}

// Start profiling now when configured to do so
if (PROFILE) profilingStart();
?>
