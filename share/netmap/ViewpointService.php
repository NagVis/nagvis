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

class ViewpointService
{
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
