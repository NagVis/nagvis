<?php

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
