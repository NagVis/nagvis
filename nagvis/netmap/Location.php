<?php

class Location
{
	public $id;
	public $point;
	public $label;
	public $address;
	public $description;
	public $hosts;

	public function __construct($id = "", $point = "", $label = "", $address = "", $description = "", $hosts = array())
	{
		$this->id = $id;
		$this->point = $point;
		$this->label = $label;
		$this->address = $address;
		$this->description = $description;
		$this->hosts = $hosts;
	}

	/**
	 * @return array of Location
	 */
	public function getAll()
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		$locations = array();
		foreach ($xml->location as $location)
		{
			$hosts = array();
			foreach ($location->children() as $host)
				$hosts[] = array('id' => (string)$host['id'], 'name' => (string)$host['name']);

			$locations[] = new Location((string)$location['id'],
				(string)$location['point'], (string)$location['label'],
				(string)$location['address'], (string)$location['description'],
				$hosts);
		}

		return $locations;
	}

	/**
	 * @param  string $point
	 * @param  string $label
	 * @param  string $address
	 * @param  string $description
	 * @return Location
	 */
	public function add($point, $label, $address, $description)
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		$node = $xml->addChild('location');
		$id = uniqid('', true);
		$node->addAttribute('id', $id);
		$node->addAttribute('point', $point);
		@$node->addAttribute('label', $label);
		@$node->addAttribute('address', $address);
		@$node->addAttribute('description', $description);
		// Note: @ prevents warnings when attribute value is an empty string

		$location = new Location($id, $point, $label, $address, $description);

		if (file_put_contents('locations.xml', $xml->asXML()) !== FALSE)
			return $location;
		else
			throw new Exception('Could not write locations.xml');
    }

	/**
	 * @param  string $id
	 * @param  string $point
	 * @param  string $label
	 * @param  string $address
	 * @param  string $description
	 * @return Location
	 */
	public function edit($id, $point, $label, $address, $description)
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		foreach ($xml->location as $location)
		{
			if ($location['id'] == $id)
			{
				$location['point'] = $point;
				$location['label'] = $label;
				$location['address'] = $address;
				$location['description'] = $description;
				$success = true;
				break;
			}
		}

		if (!isset($success))
			throw new Exception('Location does not exist');

		$location = new Location($id, $point, $label, $address, $description);

		if (file_put_contents('locations.xml', $xml->asXML()) !== FALSE)
			return $location;
		else
			throw new Exception('Could not write locations.xml');
    }

	/**
	 * @param  string $id
	 * @return string
	 */
	public function remove($id)
	{
		if (($xml = @simplexml_load_file('locations.xml')) === FALSE)
			throw new Exception('Could not read locations.xml');

		$index = 0;
		foreach ($xml->location as $location)
		{
			if ($location['id'] == $id)
			{
				// Note: unset($location) won't work thus the need for $index
				unset($xml->location[$index]);
				$success = true;
				break;
			}
			$index++;
		}

		if (!isset($success))
			throw new Exception('Location does not exist');

		if (file_put_contents('locations.xml', $xml->asXML()) !== FALSE)
			return $id;
		else
			throw new Exception('Could not write locations.xml');
    }

	/**
	 * @param  string $address
	 * @return Location
	 */
	public function find($address)
	{
		$locations = array();

		foreach(Geocode::resolve($address) as $location)
			$locations[] = new Location("", $location['point'], "", $location['address'], "");

		return $locations;
	}
}

?>
