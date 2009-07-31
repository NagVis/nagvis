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

class Host
{
	public $name;
	public $address;
	public $alias;

	public function __construct($name = '', $address = '', $alias = '')
	{
		$this->name = $name;
		$this->address = $address;
		$this->alias = $alias;
	}

	public static function fromXML($node)
	{
		return new Host((string)$node['name'],
			(string)$node['address'],
			(string)$node['alias']);
	}

	public function toXML($parent)
	{
		$node = $parent->addChild('host');
		$node->addAttribute('name', $this->name);
		@$node->addAttribute('address', $this->address);
		@$node->addAttribute('alias', $this->alias);

		return $node;
	}
}

?>
