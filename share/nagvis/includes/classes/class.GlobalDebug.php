<?php
define('DEBUG',FALSE);
/**
 * For wanted debug output summarize these possible options:
 * 1: function beginning and ending
 * 2: progres informations in the functions
 * 4: render time
 */
define('DEBUGLEVEL',7);
define('DEBUGFILE','/tmp/nagvis.debug');
define('DEBUGSTART',microtime_float());

function debug($msg) {
		$fh=fopen(DEBUGFILE,'a');
		fwrite($fh,utf8_encode(microtime_float().' '.$msg."\n"));
		fclose($fh);
}

function debugFinalize() {
	debug('###########################################################');
	debug('Render Time: '.(microtime_float()-DEBUGSTART));
	debug('###########################################################');
}

function microtime_float() {
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}
?>
