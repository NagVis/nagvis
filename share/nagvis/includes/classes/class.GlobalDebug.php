<?php
define('CONST_DEBUGSTART',microtime_float());

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
