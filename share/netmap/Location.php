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

class Location
{
	public $id;
	public $point;
	public $label;
	public $address;
	public $description;
	public $object;

	public function __construct($id = "", $point = "", $label = "", $address = "", $description = "", $object = null)
	{
		$this->id = $id;
		$this->point = $point;
		$this->label = $label;
		$this->address = $address;
		$this->description = $description;
		$this->object = $object;
	}

	/**
	 * @return array of Location
	 */
	public function getAll()
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		$locations = array();
		foreach ($xml->location as $location)
		{
			$object = null;
			$object_type = '';

			/* Note: there should be only one child of location node,
			         but it is required to use foreach with children() */
			foreach ($location->children() as $object_node)
			{
				$object_type = $object_node->getName();
				switch ($object_type)
				{
					case 'host':
						$object = Host::fromXML($object_node);
						break;

					case 'hostgroup':
						$object = HostGroup::fromXML($object_node);
						break;

					case 'service':
						$object = Service::fromXML($object_node);
						break;

					case 'servicegroup':
						$object = ServiceGroup::fromXML($object_node);
						break;

					default:
						throw new Exception('Unknown object type in locations.xml');
				}
			}

			$locations[] = new Location((string)$location['id'],
				(string)$location['point'], (string)$location['label'],
				(string)$location['address'], (string)$location['description'],
				$object, $object_type);
		}

		return $locations;
	}

	/**
	 * @param  string $point
	 * @param  string $label
	 * @param  string $address
	 * @param  string $description
	 * @return Location
	 */
	public function add($point, $label, $address, $description, $object)
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		$node = $xml->addChild('location');
		$id = uniqid('', true);
		$node->addAttribute('id', $id);
		$node->addAttribute('point', $point);
		@$node->addAttribute('label', $label);
		@$node->addAttribute('address', $address);
		@$node->addAttribute('description', $description);
		// Note: @ prevents warnings when attribute value is an empty string

		$object->toXML($node);

		$location = new Location($id, $point, $label, $address, $description, $object);

		if (file_put_contents('locations.xml', $xml->asXML()) !== FALSE)
			return $location;
		else
			throw new Exception('Could not write locations.xml');
    }

	/**
	 * @param  string $id
	 * @param  string $point
	 * @param  string $label
	 * @param  string $address
	 * @param  string $description
	 * @return Location
	 */
	public function edit($id, $point, $label, $address, $description, $object)
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		foreach ($xml->location as $location)
		{
			if ($location['id'] == $id)
			{
				$location['point'] = $point;
				$location['label'] = $label;
				$location['address'] = $address;
				$location['description'] = $description;

				// remove all children
				unset($location->host);
				unset($location->service);
				unset($location->hostgroup);
				unset($location->servicegroup);

				$object->toXML($location);

				$success = true;
				break;
			}
		}

		if (!isset($success))
			throw new Exception('Location does not exist');

		$location = new Location($id, $point, $label, $address, $description, $object);

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

		$index = 0;
		foreach ($xml->location as $location)
		{
			if ($location['id'] == $id)
			{
				// Note: unset($location) won't work thus the need for $index
				unset($xml->location[$index]);
				$success = true;
				break;
			}
			$index++;
		}

		if (!isset($success))
			throw new Exception('Location does not exist');

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

		foreach(Geocode::resolve($address) as $location)
			$locations[] = new Location("", $location['point'], "", $location['address'], "");

		return $locations;
	}

	/**
	 * @param  array $hosts
	 * @return array of Location
	 */
	public function getByFailedHosts($hosts = array())
	{
		$result = array();
		$locations = $this->getAll();

		$all_hosts = array();
		$failed_hosts = array();

		if (($lines = file('hosts.all', FILE_IGNORE_NEW_LINES)) === FALSE)
			throw new Exception('Could not read hosts.all');

		foreach ($lines as $line)
		{
			$fields = explode('|', $line);
			$all_hosts[$fields[0]] = $fields[1];
		}

		if (($lines = file('hosts.failed', FILE_IGNORE_NEW_LINES)) === FALSE)
			throw new Exception('Could not read hosts.failed');

		foreach ($lines as $line)
		{
			$fields = explode('|', $line);
			$failed_hosts[$fields[0]] = false; /* dummy value, may be used in future */
		}

		foreach (array_unique(array_values(array_intersect_key($all, $failed))) as $location_id)
			foreach ($locations as $location)
				if ($location->id == $location_id)
					$result[] = $location;

		return $result;
	}
}

?>
