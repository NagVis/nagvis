<?php

class Viewpoint
{
	public $label;
	public $center;
	public $zoom;

	public function __construct($label = "", $center = "", $zoom = 0)
	{
		$this->label = $label;
		$this->center = $center;
		$this->zoom = $zoom;
	}

	/**
	 * @return array of Viewpoint
	 */
	public function getAll()
	{
		if (($xml = @simplexml_load_file('viewpoints.xml')) === FALSE)
			throw new Exception('Could not read viewpoints.xml');

		$viewpoints = array();
		foreach ($xml->viewpoint as $viewpoint)
			$viewpoints[] = new Viewpoint((string)$viewpoint['label'],
				(string)$viewpoint['center'], (integer)$viewpoint['zoom']);

		return $viewpoints;
	}

	/**
	 * @param  string $description
	 * @param  string $coordinates
	 * @param  integer $zoom
	 * @return Viewpoint
	 */
	public function add($label, $center, $zoom)
	{
		if (($xml = @simplexml_load_file('viewpoints.xml')) === FALSE)
			throw new Exception('Could not read viewpoints.xml');

		$node = $xml->addChild('viewpoint');
		$node->addAttribute('label', $label);
		$node->addAttribute('center', $center);
		$node->addAttribute('zoom', $zoom);

		$viewpoint = new Viewpoint($label, $center, $zoom);

		if (file_put_contents('viewpoints.xml', $xml->asXML()) !== FALSE)
			return $viewpoint;
		else
			throw new Exception('Could not write viewpoints.xml');
    }
}

?>
