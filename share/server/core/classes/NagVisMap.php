<?php
/*****************************************************************************
 *
 * NagVisMap.php - Class for parsing the NagVis maps
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
class NagVisMap extends GlobalMap {
	private $BACKEND;
	public $MAPOBJ;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $MAPCFG, $BACKEND = null, $getState = GET_STATE, $bIsView = IS_VIEW) {
		parent::__construct($CORE, $MAPCFG);
		
		$this->BACKEND = $BACKEND;
		
		if($getState === true) {
			$this->MAPOBJ = new NagVisMapObj($CORE, $BACKEND, $MAPCFG, $bIsView);
			$this->MAPOBJ->fetchMapObjects();

			if($bIsView === true) {
				$this->MAPOBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
				$this->BACKEND->execute();
				$this->MAPOBJ->applyState();
			} else {
				$this->MAPOBJ->queueState(GET_STATE, DONT_GET_SINGLE_MEMBER_STATES);
			}
		}
	}
	
	/**
	 * Parses the Objects
	 *
	 * @return	String  Json Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseObjectsJson() {
		$arrRet = Array();
		
		// First parse the map object itselfs for having the
		// summary information in the frontend
		$arrRet[] = $this->MAPOBJ->parseJson();
		
		foreach($this->MAPOBJ->getMembers() AS $OBJ) {
			switch(get_class($OBJ)) {
				case 'NagVisHost':
				case 'NagVisService':
				case 'NagVisHostgroup':
				case 'NagVisServicegroup':
				case 'NagVisMapObj':
				case 'NagVisShape':
				case 'NagVisLine':
				case 'NagVisTextbox':
					$arrRet[] = $OBJ->parseJson();
				break;
			}
		}
		
		return json_encode($arrRet);
	}
}
?>
