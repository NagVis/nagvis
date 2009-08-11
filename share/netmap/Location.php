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
	const STATE_UNKNOWN = 0;
	const STATE_OK = 1;
	const STATE_WARNING = 2;
	const STATE_ERROR = 3;

	public $id;
	public $point;
	public $label;
	public $address;
	public $description;
	public $object;
	public $state;

	public function __construct($id = "", $point = "", $label = "", $address = "", $description = "", $object = null, $state = self::STATE_UNKNOWN)
	{
		$this->id = $id;
		$this->point = $point;
		$this->label = $label;
		$this->address = $address;
		$this->description = $description;
		$this->object = $object;
		$this->state = $state;
	}

	private function fromXML($node)
	{
		$object = null;
		$object_type = '';

		/* Note: there should be only one child of location node,
				 but it is required to use foreach with children() */
		foreach ($node->children() as $object_node)
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

		return new Location((string)$node['id'],
			(string)$node['point'], (string)$node['label'],
			(string)$node['address'], (string)$node['description'],
			$object);
	}

	private function toXML($parent)
	{
		$node = $parent->addChild('location');
		$node->addAttribute('id', $this->id);
		$node->addAttribute('point', $this->point);
		@$node->addAttribute('label', $this->label);
		@$node->addAttribute('address', $this->address);
		@$node->addAttribute('description', $this->description);

		if (is_object($this->object))
			$this->object->toXML($node);

		return $node;
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
			$locations[] = Location::fromXML($location);

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
		$node = $location->toXML($xml);

		if (file_put_contents('locations.xml', $xml->asXML()) !== FALSE)
			return $location;
		else
			throw new Exception('Could not write locations.xml');
    }

	protected function removeNode(&$xml, $id)
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

		Location::removeNode($xml, $location->id);

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

		Location::removeNode($xml, $id);

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
	 * @return array of Location
	 */
	public function checkAll()
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		$locations = array();
		foreach ($xml->location as $node)
		{
			$location = Location::fromXML($node);
			// TODO: check status of $location->children() here
			$location->state = self::STATE_OK;

			$locations[] = $location;
		}

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
