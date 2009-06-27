<?php

class Host
{
	public $id;
	public $name;
	public $address;
	public $selected;

	public function __construct($id, $name, $address, $selected = false)
	{
		$this->id = $id;
		$this->name = $name;
		$this->address = $address;
		$this->selected = $selected;
	}
}

?>
