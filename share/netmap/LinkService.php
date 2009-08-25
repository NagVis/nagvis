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

class LinkService
{
	private function validate($link)
	{
		if ($link->id == '' || $link->id1 == '' || $link->id2 == '')
			throw new Exception('Attempt to create an invalid object of Link class');
	}

	private function updateState(&$link)
	{
		$db = new NagiosService();

		if (isset($link->object))
			switch (get_class($link->object))
			{
				case 'Host':
					$link->state = $db->getHostState($link->object);
					break;

				case 'HostGroup':
					$link->state = $db->getHostGroupState($link->object);
					break;

				case 'Service':
					$link->state = $db->getServiceState($link->object);
					break;

				case 'ServiceGroup':
					$link->state = $db->getServiceGroupState($link->object);
					break;

				default:
					throw new Exception('Unknown object type in links.xml');
			}
		else
			$link->state = State::UNKNOWN;
	}

	private function createFile()
	{
		$xml = '<?xml version="1.0" standalone="yes" ?><links/>';
		if (file_put_contents(CONFIG_PATH . 'links.xml', $xml) === FALSE)
			throw new Exception('Could not create links.xml');
	}

	/**
	 * @param  boolean $problemonly
	 * @return array of Link
	 */
	public function getAll($problemonly = false)
	{
		if (!file_exists(CONFIG_PATH . 'links.xml'))
			self::createFile();

		if (($xml = @simplexml_load_file(CONFIG_PATH . 'links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		$links = array();
		foreach ($xml->link as $node)
		{
			$link = Link::fromXML($node);

			self::validate($link);

			self::updateState($link);

			if (!$problemonly || $link->state != State::OK)
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
		$link->id = uniqid('', true);
		self::validate($link);

		if (($xml = @simplexml_load_file(CONFIG_PATH . 'links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		self::updateState($link);
		$node = $link->toXML($xml);

		if (file_put_contents(CONFIG_PATH . 'links.xml', $xml->asXML()) !== FALSE)
			return $link;
		else
			throw new Exception('Could not write links.xml');
    }

	private function removeNode(&$xml, $id)
	{
		$index = 0;
		foreach ($xml->link as $node)
		{
			if ($node['id'] == $id)
			{
				// Note: unset($node) won't work thus the need for $index
				unset($xml->link[$index]);
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
		self::validate($link);

		if (($xml = @simplexml_load_file(CONFIG_PATH . 'links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		self::updateState($link);

		self::removeNode($xml, $link->id);

		$link->toXML($xml);

		if (file_put_contents(CONFIG_PATH . 'links.xml', $xml->asXML()) !== FALSE)
			return $link;
		else
			throw new Exception('Could not write links.xml');
    }

	/**
	 * @param  string $id
	 * @return string
	 */
	public function remove($id)
	{
		if (($xml = @simplexml_load_file(CONFIG_PATH . 'links.xml')) === FALSE)
			throw new Exception('Could not read links.xml');

		self::removeNode($xml, $id);

		if (file_put_contents(CONFIG_PATH . 'links.xml', $xml->asXML()) !== FALSE)
			return $id;
		else
			throw new Exception('Could not write links.xml');
    }
}

?>
