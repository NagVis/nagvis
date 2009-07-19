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

class Database
{
	/**
	 * @return array of Host
	 */
	public function getHosts()
	{
		$hosts = array();

		$hosts[] = new Host('1', 'firewall', '123.45.67.89');
		$hosts[] = new Host('2', 'storage', '123.45.67.90');
		$hosts[] = new Host('3', 'test', '123.45.67.91');
		$hosts[] = new Host('4', 'foobar', '123.45.67.92');
		$hosts[] = new Host('5', 'kitchen', '23.45.67.89');

		return $hosts;
	}

	/**
	 * @return array of Service
	 */
	public function getServices()
	{
		$services = array();

		$services[] = new Service('1', 'test', 'firewall');
		$services[] = new Service('2', 'ping', 'firewall');
		$services[] = new Service('3', 'ping', 'storage');
		$services[] = new Service('4', 'abcdefg', 'foobar');
		$services[] = new Service('5', 'ping', 'foobar');

		/*
		for ($i = 0; $i < 10000; $i++)
			$services[] = new Service((string)$i, uniqid(), (string)$i);
		*/

		return $services;
	}

	/**
	 * @return array of HostGroup
	 */
	public function getHostGroups()
	{
		$hostgroups = array();

		$hostgroups[] = new HostGroup('1', 'Gothenburg');
		$hostgroups[] = new HostGroup('2', 'Stockholm');
		$hostgroups[] = new HostGroup('3', 'Lviv');

		return $hostgroups;
	}

	/**
	 * @return array of ServiceGroup
	 */
	public function getServiceGroups()
	{
		$servicegroups = array();

		$servicegroups[] = new ServiceGroup('1', 'Gothenburg-Stockholm VPN link');
		$servicegroups[] = new ServiceGroup('2', 'Stockholm-Lviv VPN link');

		return $servicegroups;
	}
}

?>
