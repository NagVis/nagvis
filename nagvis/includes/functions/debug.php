<?php
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
