<?php

class Service
{
	public $id;
	public $description;
	public $host;
	public $selected;

	public function __construct($id, $description, $host, $selected = false)
	{
		$this->id = $id;
		$this->description= $description;
		$this->host = $host;
		$this->selected = $selected;
	}
}

?>
