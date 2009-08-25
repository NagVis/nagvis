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
 
// instruct PHP not to send any cache control headers (see note 'HTTPS on IE')
ini_set('session.cache_limiter', '');

// manually setup the no-cache headers that don't break IE
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__)) . '/library/');

require_once('Zend/Loader.php');

Zend_Loader::registerAutoload();

// extend Zend_Amf_Server to allow passing exception messages back to Flex
class Custom_Zend_Amf_Server extends Zend_Amf_Server
{
	protected $_production = false;
}

define('NAGVIS_PATH', realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../..') . '/');
define('INCLUDE_PATH', NAGVIS_PATH . 'share/nagvis/includes/');
define('CONFIG_PATH', NAGVIS_PATH . 'etc/geomap/');

$server = new Custom_Zend_Amf_Server();
$server->setClassMap("Settings", "Settings");
$server->setClassMap("Viewpoint", "Viewpoint");
$server->setClassMap("Location", "Location");
$server->setClassMap("Link", "Link");
$server->setClassMap("Host", "Host");
$server->setClassMap("Service", "Service");
$server->setClassMap("HostGroup", "HostGroup");
$server->setClassMap("ServiceGroup", "ServiceGroup");
echo ($server->handle());


/*******
 * Notes
 *******
 * HTTPS on IE
 *
 * IE bug prevents data loading via https if the server uses a no-cache header.
 * See http://kb2.adobe.com/cps/000/fdc7b5c.html for details.
 *
 * Tests show that the main problem provides 'Pragma: no-cache' header, while
 * 'Cache-Control:' header may be used.
 *
 * On session start PHP automatically sends different no-cache headers depending 
 * on the value of session.cache_limiter configuration setting.
 * See http://php.net/manual/en/function.session-cache-limiter.php for details.
 *
 * See also these posts:
 *	http://www.gmrweb.net/2005/08/18/flash-remoting-https-internet-explorer/
 *	http://faindu.wordpress.com/2008/04/18/ie7-ssl-xml-flex-error-2032-stream-error/		
 *******/
?>
