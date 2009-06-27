<?php

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
}

?>
