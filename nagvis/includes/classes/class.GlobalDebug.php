<?php
define('DEBUG',FALSE);
define('DEBUGFILE','/tmp/nagvis.debug');

function debug($msg) {
	$fh=fopen(DEBUGFILE,"a");
	fwrite($fh,utf8_encode(microtime_float().' '.$msg."\n"));
	fclose($fh);
}

function microtime_float() {
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}
?>