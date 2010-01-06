<?php
/*****************************************************************************
 *
 * NagVisMapView.php - Class for parsing the NagVis maps in nagvis-js frontend
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
class NagVisMapView {
	private $CORE = null;
	private $name = '';
	private $search = '';
	private $aRotation = Array();
	private $aViewOpts = Array();
	
	/**
	 * Class Constructor
	 *
	 * @param    GlobalCore      $CORE
	 * @param    String          $NAME
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(GlobalCore $CORE, $name) {
		$this->CORE = $CORE;
		
		// FIXME: Already validated?
		$this->name = $name;
	}
	
	/**
	 * Set the search value if the user searches for an object
	 *
	 * @param   String    Search string
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setSearch($s) {
		$this->search = $s;
	}
	
	/**
	 * Set the rotation properties if the user wants a rotation
	 *
	 * @param   Array    
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setRotation($a) {
		$this->aRotation = $a;
	}
	
	/**
	 * Set the view modificator options
	 *
	 * @param   Array    
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setViewOpts($a) {
		$this->aViewOpts = $a;
	}
	
	/**
	 * Parses the map and the objects for the nagvis-js frontend
	 *
	 * @return	String 	String with JS Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parse() {
		// Initialize template system
		$TMPL = New FrontendTemplateSystem($this->CORE);
		$TMPLSYS = $TMPL->getTmplSys();
		
		$aData = Array(
				'generalProperties' => $this->CORE->getMainCfg()->parseGeneralProperties(),
				'workerProperties' => $this->CORE->getMainCfg()->parseWorkerProperties(),
				'rotationProperties' => json_encode($this->aRotation),
				'viewProperties' => $this->parseViewProperties(),
				'mapName' => $this->name
			);

    // Build page based on the template file and the data array
    // FIXME: Make template set configurable
    return $TMPLSYS->get($TMPL->getTmplFile('default', 'map'), $aData);
	}
	
	/**
	 * Parses the view specific properties. In most cases this will be user
	 * defined values which maybe given by url or session
	 *
	 * @return  String  JSON array
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function parseViewProperties() {
		$arr = Array();
		
		$arr['search'] = $this->search;
		
		// View specific hover modifier set
		if($this->aViewOpts['enableHover'] !== false) {
			$arr['enableHover'] = $this->aViewOpts['enableHover'];
		}
		
		// View specific context modifier set
		if($this->aViewOpts['enableContext'] !== false) {
			$arr['enableContext'] = $this->aViewOpts['enableContext'];
		}
		
		return json_encode($arr);
	}
}
?>
