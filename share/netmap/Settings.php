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

	public function __construct($googleMapsKey = "", $defaultLocationAction = "", $openLinksInNewWindow = false)
	{
		$this->googleMapsKey = $googleMapsKey;
		$this->defaultLocationAction = $defaultLocationAction;
		$this->openLinksInNewWindow = $openLinksInNewWindow;
	}

	/**
	 * @return array of Settings
	 */
	public function load()
	{
		if (($xml = @simplexml_load_file('settings.xml')) === FALSE)
			throw new Exception('Could not read settings.xml');

		return new Settings((string)$xml['googleMapsKey'],
				(string)$xml['defaultLocationAction'],
				(boolean)$xml['openLinksInNewWindow']);
	}

	/**
	 * @param  Settings $settings
	 * @return Settings
	 */
	public function save($settings)
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>\n<settings/>\n');

		$xml->addAttribute('googleMapsKey', $settings->googleMapsKey);
		$xml->addAttribute('defaultLocationAction', $settings->defaultLocationAction);
		$xml->addAttribute('openLinksInNewWindow', $settings->openLinksInNewWindow);

		if (file_put_contents('settings.xml', $xml->asXML()) !== FALSE)
			return $settings;
		else
			throw new Exception('Could not write settings.xml');
    }
}

?>
