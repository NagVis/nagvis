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

class NagVisService
{
	private $CORE;

	private function init()
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
	}

	public function getMaps()
	{
		$this->init();
		$maps = $this->CORE->getAvailableMaps();
		return array_values($maps);
	}
}

?>
