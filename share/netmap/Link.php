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
	public $id1;
	public $id2;
	public $services;

	public function __construct($id1 = "", $id2 = "", $services = array())
	{
		$this->id1 = $id1;
		$this->id2 = $id2;
		$this->services = $services;
	}

	/**
	 * @return array of Link
	 */
	public function getAll()
	{
		if (($xml = @simplexml_load_file('links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		$links = array();
		foreach ($xml->link as $link)
		{
			$services = array();
			foreach ($link->children() as $service)
				$services[] = array('id' => (string)$service['id'], 'description' => (string)$service['description']);

			$links[] = new Link((string)$link['id1'], (string)$link['id2'], $services);
		}

		return $links;
	}

	/**
	 * @param  string $id1
	 * @param  string $id2
	 * @param  array $services
	 * @return Link
	 */
	public function add($id1, $id2, $services = array())
	{
		if (($xml = @simplexml_load_file('links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		$node = $xml->addChild('link');
		$node->addAttribute('id1', $id1);
		$node->addAttribute('id2', $id2);

		foreach ($services as $service)
		{
			$child = $node->addChildren('service');
			$child->addAttribute('id', $service['id']);
			$child->addAttribute('description', $service['description']);
		}

		$link = new Link($id1, $id2, $services);

		if (file_put_contents('links.xml', $xml->asXML()) !== FALSE)
			return $link;
		else
			throw new Exception('Could not write links.xml');
    }
}

?>
