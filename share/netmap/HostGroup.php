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

class HostGroup
{
	public $name;
	public $alias;

	public function __construct($name = '', $alias = '')
	{
		$this->name = $name;
		$this->alias = $alias;
	}

	public static function fromXML($node)
	{
		return new HostGroup((string)$node['name'],
			(string)$node['alias']);
	}

	public function toXML($parent)
	{
		$node = $parent->addChild('hostgroup');
		$node->addAttribute('name', $this->name);
		@$node->addAttribute('alias', $this->alias);

		return $node;
	}
}

?>
