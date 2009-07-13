<?php

/*****************************************************************************
 *
 * Copyright (C) 2009 NagVis Project
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
