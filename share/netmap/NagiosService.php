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
		require_once(INCLUDE_PATH . 'defines/global.php');
		require_once(INCLUDE_PATH . 'defines/matches.php');
		set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__))
			. PATH_SEPARATOR . INCLUDE_PATH . 'classes/'
			. PATH_SEPARATOR . INCLUDE_PATH . 'classes/validator/'
			. PATH_SEPARATOR . INCLUDE_PATH . 'classes/frontend/');
		require_once(INCLUDE_PATH . 'functions/oldPhpVersionFixes.php');


		$this->CORE = new GlobalCore();
		$this->CORE->getMainCfg()->setRuntimeValue('user', getUser());
		$this->BACKEND = new CoreBackendMgmt($this->CORE);

		if (($backendId = $this->CORE->getMainCfg()->getValue('defaults', 'backend')) === false)
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
		if (($data = $this->backend->getHostgroupState($hostgroup->name, 0)) === false)
			return State::ERROR;

		$state = State::UNKNOWN;
		foreach ($data as $host)
		{
			switch ($host['state'])
			{
				case 'PENDING':
					$host_state = State::UNKNOWN;
					break;

				case 'UP':
					$host_state = State::OK;
					break;

				case 'DOWN':
					$host_state = State::ERROR;
					break;

				case 'UNREACHABLE':
					$host_state = State::WARNING;
					break;

				case 'UNKNOWN':
				default:
					$host_state = State::UNKNOWN;
			}

			$state = max($state, $host_state);
		}

		return $state;
	}

	/**
	 * @return integer
	 */
	public function getServiceGroupState($servicegroup)
	{
		if (($data = $this->backend->getServicegroupState($servicegroup->name, 0)) === false)
			return State::ERROR;

		$state = State::UNKNOWN;
		foreach ($data as $service)
		{
			switch ($service['state'])
			{
				case 'PENDING':
					$service_state = State::UNKNOWN;
					break;

				case 'OK':
					$service_state = State::OK;
					break;

				case 'WARNING':
					$service_state = State::WARNING;
					break;

				case 'CRITICAL':
					$service_state = State::ERROR;
					break;

				case 'UNKNOWN':
				default:
					$service_state = State::UNKNOWN;
			}

			$state = max($state, $service_state);
		}

		return $state;
	}
}

?>
