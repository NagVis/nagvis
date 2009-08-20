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
	public $action;
	public $object;
	public $state;

	public function __construct($id = "", $point = "", $label = "", $address = "",
		$description = "", $action = "", $object = null, $state = State::UNKNOWN)
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

	public static function fromXML($node)
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

	public function toXML($parent)
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
}

?>
