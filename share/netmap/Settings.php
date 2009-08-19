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

class Settings
{
	public $googleMapsKey;
	public $defaultLocationAction;
	public $openLinksInNewWindow;
	public $htmlbase;
	public $htmlcgi;
	public $hosturl;
	public $hostgroupurl;
	public $serviceurl;
	public $servicegroupurl;
	public $mapurl;

	public function __construct($googleMapsKey = '', $defaultLocationAction = '',
		$openLinksInNewWindow = false, $htmlbase = '', $htmlcgi = '',
		$hosturl = '', $hostgroupurl = '', $serviceurl = '', $servicegroupurl = '', $mapurl = '')
	{
		$this->googleMapsKey = $googleMapsKey;
		$this->defaultLocationAction = $defaultLocationAction;
		$this->openLinksInNewWindow = $openLinksInNewWindow;
		$this->htmlbase = $htmlbase;
		$this->htmlcgi = $htmlcgi;
		$this->hosturl = $hosturl;
		$this->hostgroupurl = $hostgroupurl;
		$this->serviceurl = $serviceurl;
		$this->servicegroupurl = $servicegroupurl;
		$this->mapurl = $mapurl;
	}
}

?>
