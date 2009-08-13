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
	public $action;
	public $object;
	public $state;

	public function __construct($id = "", $point = "", $label = "", $address = "", $description = "", $action = "", $object = null, $state = self::STATE_UNKNOWN)
	{
		$this->id = $id;
		$this->point = $point;
		$this->label = $label;
		$this->address = $address;
		$this->description = $description;
		$this->action = $action;
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

		return new Location((string)$node['id'], (string)$node['point'],
			(string)$node['label'], (string)$node['address'],
			(string)$node['description'], (string)$node['action'], $object);
	}

	private function toXML($parent)
	{
		$node = $parent->addChild('location');
		$node->addAttribute('id', $this->id);
		$node->addAttribute('point', $this->point);
		@$node->addAttribute('label', $this->label);
		@$node->addAttribute('address', $this->address);
		@$node->addAttribute('description', $this->description);
		@$node->addAttribute('action', $this->action);

		if (is_object($this->object))
			$this->object->toXML($node);

		return $node;
	}

	private function updateState()
	{
		$db = new Database();

		if (isset($this->object))
			switch (get_class($this->object))
			{
				case 'Host':
					$this->state = $db->getHostState($this->object);
					break;

				case 'HostGroup':
					$this->state = $db->getHostGroupState($this->object);
					break;

				case 'Service':
					$this->state = $db->getServiceState($this->object);
					break;

				case 'ServiceGroup':
					$this->state = $db->getServiceGroupState($this->object);
					break;

				default:
					throw new Exception('Unknown object type in locations.xml');
			}
		else
			$this->state = self::STATE_UNKNOWN;
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

			$location->updateState();

			if (!$problemonly || $location->state != self::STATE_OK)
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
		$location->updateState();
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

		$location->updateState();

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
			$locations[] = new Location("", $location['point'], "", $location['address'], "", "");

		return $locations;
	}
}

?>
