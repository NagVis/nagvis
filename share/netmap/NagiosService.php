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

class NagiosService
{
	private $CORE;
	private $BACKEND;
	private $backend;

	public function __construct()
	{
		require_once("../nagvis/includes/defines/global.php");
		require_once("../nagvis/includes/defines/matches.php");
		set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__))
			. PATH_SEPARATOR . '../nagvis/includes/classes/'
			. PATH_SEPARATOR . '../nagvis/includes/classes/validator/'
			. PATH_SEPARATOR . '../nagvis/includes/classes/frontend/');
		require_once("../nagvis/includes/functions/oldPhpVersionFixes.php");
		require_once("../nagvis/includes/functions/getuser.php");

		$this->CORE = new GlobalCore();
		$this->CORE->MAINCFG->setRuntimeValue('user', getUser());
		$this->BACKEND = new GlobalBackendMgmt($this->CORE);

		if (($backendId = $this->CORE->MAINCFG->getValue('defaults', 'backend')) === false)
			throw new Exception('Default backend is not set');

		if (!$this->BACKEND->checkBackendInitialized($backendId, 0))
			throw new Exception('Backend ' . $backendId . ' could not be initialized');

		$this->backend = $this->BACKEND->BACKENDS[$backendId];
	}

	/**
	 * @return array of Host
	 */
	public function getHosts()
	{
		$hosts = array();

		$objects = $this->backend->getObjectsEx('host');
		foreach ($objects as $object)
			$hosts[] = new Host($object['name'], $object['address'], $object['alias']);

		return $hosts;
	}

	/**
	 * @return array of Service
	 */
	public function getServices()
	{
		$services = array();

		$objects = $this->backend->getObjectsEx('service');
		foreach ($objects as $object)
			$services[] = new Service($object['host'], $object['description']);

		return $services;
	}

	/**
	 * @return array of HostGroup
	 */
	public function getHostGroups()
	{
		$hostgroups = array();

		$objects = $this->backend->getObjectsEx('hostgroup');
		foreach ($objects as $object)
			$hostgroups[] = new HostGroup($object['name'], $object['alias']);

		return $hostgroups;
	}

	/**
	 * @return array of ServiceGroup
	 */
	public function getServiceGroups()
	{
		$servicegroups = array();

		$objects = $this->backend->getObjectsEx('servicegroup');
		foreach ($objects as $object)
			$servicegroups[] = new ServiceGroup($object['name'], $object['alias']);

		return $servicegroups;
	}

	/**
	 * @return integer
	 */
	public function getHostState($host)
	{
		$data = $this->backend->getHostState($host->name, 0);

		switch ($data['state'])
		{
			case 'ERROR':
				return State::ERROR;

			case 'PENDING':
				return State::UNKNOWN;

			case 'UP':
				return State::OK;

			case 'DOWN':
				return State::ERROR;

			case 'UNREACHABLE':
				return State::WARNING;

			case 'UNKNOWN':
			default:
				return State::UNKNOWN;
		}
	}

	/**
	 * @return integer
	 */
	public function getServiceState($service)
	{
		$data = $this->backend->getServiceState($service->host, $service->description, 0);

		switch ($data['state'])
		{
			case 'ERROR':
				return State::ERROR;

			case 'PENDING':
				return State::UNKNOWN;

			case 'OK':
				return State::OK;

			case 'WARNING':
				return State::WARNING;

			case 'CRITICAL':
				return State::ERROR;

			case 'UNKNOWN':
			default:
				return State::UNKNOWN;
		}
	}

	/**
	 * @return integer
	 */
	public function getHostGroupState($hostgroup)
	{
		$hosts = $this->backend->getHostsByHostgroupName($hostgroup->name);
		$state = State::UNKNOWN;

		foreach ($hosts as $host)
			$state = max($state, $this->getHostState(new Host($host)));

		return $state;
	}

	/**
	 * @return integer
	 */
	public function getServiceGroupState($servicegroup)
	{
		$services = $this->backend->getServicesByServicegroupName($servicegroup->name);
		$state = State::UNKNOWN;

		foreach ($services as $service)
			$state = max($state, $this->getServiceState(new Service($service['host_name'], $service['service_description'])));

		return $state;
	}
}

?>
