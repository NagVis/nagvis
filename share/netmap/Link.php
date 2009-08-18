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

class Link
{
	const STATE_UNKNOWN = 0;
	const STATE_OK = 1;
	const STATE_WARNING = 2;
	const STATE_ERROR = 3;

	public $id1;
	public $id2;
	public $description;
	public $action;
	public $object;
	public $state;

	public function __construct($id1 = "", $id2 = "", $description = "",
		$action = "", $object = null, $state = self::STATE_UNKNOWN)
	{
		$this->id1 = $id1;
		$this->id2 = $id2;
		$this->description = $description;
		$this->action = $action;
		$this->object = $object;
		$this->state = $state;
	}

	private function fromXML($node)
	{
		$object = null;
		$object_type = '';

		/* Note: there should be only one child of link node,
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
					throw new Exception('Unknown object type in links.xml');
			}
		}

		return new Link((string)$node['id1'], (string)$node['id2'],
			(string)$node['description'], (string)$node['action'], $object);
	}

	private function toXML($parent)
	{
		$node = $parent->addChild('link');
		$node->addAttribute('id1', $this->id1);
		$node->addAttribute('id2', $this->id2);
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
					throw new Exception('Unknown object type in links.xml');
			}
		else
			$this->state = self::STATE_UNKNOWN;
	}

	/**
	 * @param  boolean $problemonly
	 * @return array of Link
	 */
	public function getAll($problemonly = false)
	{
		if (($xml = @simplexml_load_file('links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		$links = array();
		foreach ($xml->link as $node)
		{
			$link = Link::fromXML($node);

			$link->updateState();

			if (!$problemonly || $link->state != self::STATE_OK)
				$links[] = $link;
		}

		return $links;
	}

	/**
	 * @param  object $link
	 * @return Link
	 */
	public function add($link)
	{
		if (($xml = @simplexml_load_file('links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		$link->updateState();
		$node = $link->toXML($xml);

		if (file_put_contents('links.xml', $xml->asXML()) !== FALSE)
			return $link;
		else
			throw new Exception('Could not write links.xml');
    }

	private function removeNode(&$xml, $id1, $id2)
	{
		$index = 0;
		foreach ($xml->location as $node)
		{
			if ($node['id1'] == $id1 && $node['id2'] == $id2)
			{
				// Note: unset($node) won't work thus the need for $index
				unset($xml->location[$index]);
				$success = true;
				break;
			}
			$index++;
		}
		if (!isset($success))
			throw new Exception('Link does not exist');
	}

	/**
	 * @param  object $link
	 * @return Link
	 */
	public function edit($link)
	{
		if (($xml = @simplexml_load_file('links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		$link->updateState();

		Location::removeNode($xml, $link->id1, $link->id2);

		$link->toXML($xml);

		if (file_put_contents('links.xml', $xml->asXML()) !== FALSE)
			return $link;
		else
			throw new Exception('Could not write links.xml');
    }

	/**
	 * @param  string $id1
	 * @param  string $id2
	 * @return string
	 */
	public function remove($id1, $id2)
	{
		if (($xml = @simplexml_load_file('links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		Location::removeNode($xml, $id1, $id2);

		if (file_put_contents('links.xml', $xml->asXML()) !== FALSE)
			return true;
		else
			throw new Exception('Could not write links.xml');
    }
}

?>
