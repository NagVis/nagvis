<?php

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__)) . '/library/');

require_once('Zend/Loader.php');

Zend_Loader::registerAutoload();

/*
fix a bug in IE
http://www.blog.lessrain.com/flash-nasty-xml-load-bug-in-internet-explorer/
http://kb.adobe.com/selfservice/viewContent.do?externalId=kb401472
*/
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// extend Zend_Amf_Server to allow passing exception messages back to Flex
class Custom_Zend_Amf_Server extends Zend_Amf_Server
{
	protected $_production = false;
}

$server = new Custom_Zend_Amf_Server();
$server->setClassMap("Viewpoint", "Viewpoint");
$server->setClassMap("Location", "Location");
$server->setClassMap("Link", "Link");
$server->setClassMap("Host", "Host");
$server->setClassMap("Service", "Service");
echo ($server->handle());

?>
