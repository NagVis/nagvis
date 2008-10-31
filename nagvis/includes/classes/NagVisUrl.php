<?php
/*****************************************************************************
 *
 * NagVisUrl.php - This class handles urls which should be shown in NagVis
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisUrl {
	private $CORE;
	
	private $strUrl;
	private $strContents;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(&$CORE, $strUrl) {
		$this->CORE = &$CORE;
		
		$this->strUrl = $strUrl;
		$this->strContents = '';
	}
	
	public function fetchContents() {
		$this->strContents = file_get_contents($this->strUrl);
	}
	
	public function getContents() {
		if($this->strContents == '') {
			$this->fetchContents();
		}
		
		return $this->strContents;
	}

	/**
   * Parses some option and initializes the worker
   *
   * @return  String  String with Html Code
   * @author  Lars Michelsen <lars@vertical-visions.de>
   */
  function parseJson() {
    $ret = '';
    $ret .= 'var oGeneralProperties='.$this->CORE->MAINCFG->parseGeneralProperties().';'."\n";
    $ret .= 'var oWorkerProperties='.$this->CORE->MAINCFG->parseWorkerProperties().';'."\n";
    //$ret .= 'var oFileAges='.$this->parseFileAges().';'."\n";

    // Kick of the worker
    $ret .= 'addLoadEvent(runWorker(0, \'url\'));';

    return $ret;
  }
}
?>
