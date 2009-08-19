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

class LocationService
{
	private function updateState(&$location)
	{
		$db = new NagiosService();

		if (isset($location->object))
			switch (get_class($location->object))
			{
				case 'Host':
					$location->state = $db->getHostState($location->object);
					break;

				case 'HostGroup':
					$location->state = $db->getHostGroupState($location->object);
					break;

				case 'Service':
					$location->state = $db->getServiceState($location->object);
					break;

				case 'ServiceGroup':
					$location->state = $db->getServiceGroupState($location->object);
					break;

				default:
					throw new Exception('Unknown object type in locations.xml');
			}
		else
			$location->state = Location::STATE_UNKNOWN;
	}

	/**
	 * @param  boolean $problemonly
	 * @return array of Location
	 */
	public function getAll($problemonly = false)
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		$locations = array();
		foreach ($xml->location as $node)
		{
			$location = Location::fromXML($node);

			self::updateState($location);

			if (!$problemonly || $location->state != Location::STATE_OK)
				$locations[] = $location;
		}

		return $locations;
	}

	/**
	 * @param  object $location
	 * @return Location
	 */
	public function add($location)
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		$location->id = uniqid('', true);
		self::updateState($location);
		$node = $location->toXML($xml);

		if (file_put_contents('locations.xml', $xml->asXML()) !== FALSE)
			return $location;
		else
			throw new Exception('Could not write locations.xml');
    }

	private function removeNode(&$xml, $id)
	{
		$index = 0;
		foreach ($xml->location as $node)
		{
			if ($node['id'] == $id)
			{
				// Note: unset($node) won't work thus the need for $index
				unset($xml->location[$index]);
				$success = true;
				break;
			}
			$index++;
		}
		if (!isset($success))
			throw new Exception('Location does not exist');
	}

	/**
	 * @param  object $location
	 * @return Location
	 */
	public function edit($location)
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		self::updateState($location);

		self::removeNode($xml, $location->id);

		$location->toXML($xml);

		if (file_put_contents('locations.xml', $xml->asXML()) !== FALSE)
			return $location;
		else
			throw new Exception('Could not write locations.xml');
    }

	/**
	 * @param  string $id
	 * @return string
	 */
	public function remove($id)
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		self::removeNode($xml, $id);

		if (file_put_contents('locations.xml', $xml->asXML()) !== FALSE)
			return $id;
		else
			throw new Exception('Could not write locations.xml');
    }

	/**
	 * @param  string $address
	 * @return Location
	 */
	public function find($address)
	{
		$locations = array();

		foreach(GeocodeService::resolve($address) as $location)
			$locations[] = new Location("", $location['point'], "", $location['address'], "", "");

		return $locations;
	}
}

?>
