<?php
/*****************************************************************************
 *
 * NagVisService.php - Class of a Service in NagVis with all necessary 
 *                  information which belong to the object handling in NagVis
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
class NagVisService extends NagiosService {
	protected $gadget_url;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 		ID of queried backend
	 * @param		String		Name of the host
	 * @param		String		Service description
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $BACKEND, $backend_id, $hostName, $serviceDescription) {
		$this->type = 'service';
		$this->iconset = 'std_medium';
		
		parent::__construct($CORE, $BACKEND, $backend_id, $hostName, $serviceDescription);
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PROTECTED parseGadgetUrl()
	 *
	 * Sets the path of gadget_url. The method adds htmlgadgets path when relative
	 * path or will remove [] when full url given
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function parseGadgetUrl() {
		if(preg_match('/^\[(.*)\]$/',$this->gadget_url,$match) > 0) {
			$this->gadget_url = $match[1];
		} else {
			$this->gadget_url = $this->CORE->MAINCFG->getValue('paths', 'htmlgadgets').$this->gadget_url;
		}
	}
}
?>
