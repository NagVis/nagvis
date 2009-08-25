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

class SettingsService
{
	private function createFile()
	{
		$xml = '<?xml version="1.0" standalone="yes" ?><settings/>';
		if (file_put_contents($config_path . 'settings.xml', $xml) === FALSE)
			throw new Exception('Could not create settings.xml');
	}

	/**
	 * @return array of Settings
	 */
	public function load()
	{
		require_once("../nagvis/includes/defines/global.php");
		require_once("../nagvis/includes/defines/matches.php");
		set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__))
			. PATH_SEPARATOR . '../nagvis/includes/classes/'
			. PATH_SEPARATOR . '../nagvis/includes/classes/'
			. PATH_SEPARATOR . '../nagvis/includes/classes/validator/'
			. PATH_SEPARATOR . '../nagvis/includes/classes/frontend/');
		require_once("../nagvis/includes/functions/oldPhpVersionFixes.php");
		require_once("../nagvis/includes/functions/getuser.php");

		$CORE = new GlobalCore();
		$CORE->MAINCFG->setRuntimeValue('user', getUser());

		if (($htmlbase = $CORE->MAINCFG->getValue('paths', 'htmlbase')) === false)
			throw new Exception('Error in NagVis configuration');
		if (($htmlcgi = $CORE->MAINCFG->getValue('paths', 'htmlcgi')) === false)
			throw new Exception('Error in NagVis configuration');
		if (($hosturl = $CORE->MAINCFG->getValue('defaults', 'hosturl')) === false)
			throw new Exception('Error in NagVis configuration');
		if (($hostgroupurl = $CORE->MAINCFG->getValue('defaults', 'hostgroupurl')) === false)
			throw new Exception('Error in NagVis configuration');
		if (($serviceurl = $CORE->MAINCFG->getValue('defaults', 'serviceurl')) === false)
			throw new Exception('Error in NagVis configuration');
		if (($servicegroupurl = $CORE->MAINCFG->getValue('defaults', 'servicegroupurl')) === false)
			throw new Exception('Error in NagVis configuration');
		if (($mapurl = $CORE->MAINCFG->getValue('defaults', 'mapurl')) === false)
			throw new Exception('Error in NagVis configuration');

		if (!file_exists($config_path . 'settings.xml'))
			self::createFile();

		if (($xml = @simplexml_load_file($config_path . 'settings.xml')) === FALSE)
			throw new Exception('Could not read settings.xml');

		return new Settings((string)$xml['googleMapsKey'],
				(string)$xml['defaultLocationAction'],
				(boolean)$xml['openLinksInNewWindow'],
				$htmlbase, $htmlcgi,
				$hosturl, $hostgroupurl, $serviceurl, $servicegroupurl, $mapurl);
	}

	/**
	 * @param  Settings $settings
	 * @return Settings
	 */
	public function save($settings)
	{
		$xml = new SimpleXMLElement("<?xml version=\"1.0\" standalone=\"yes\"?>\n<settings/>\n");

		@$xml->addAttribute('googleMapsKey', $settings->googleMapsKey);
		@$xml->addAttribute('defaultLocationAction', $settings->defaultLocationAction);
		@$xml->addAttribute('openLinksInNewWindow', $settings->openLinksInNewWindow);

		if (file_put_contents($config_path . 'settings.xml', $xml->asXML()) !== FALSE)
			return $settings;
		else
			throw new Exception('Could not write settings.xml');
    }
}

?>
