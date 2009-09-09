<?php
/*****************************************************************************
 *
 * NagVisOverviewView.php - Class for handling the map index page
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
class NagVisOverviewView {
	private $CORE;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE) {
		$this->CORE = $CORE;
	}
	
	/**
	 * Parses the information for json
	 *
	 * @return	String 	String with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parse() {
		$ret = '';
		$ret .= 'oGeneralProperties='.$this->CORE->MAINCFG->parseGeneralProperties().';'."\n";
		$ret .= 'oWorkerProperties='.$this->CORE->MAINCFG->parseWorkerProperties().';'."\n";
		$ret .= 'aMaps=Array();'."\n";
		$ret .= 'aRotations=Array();'."\n";
		
		// Kick of the worker
		$ret .= 'addDOMLoadEvent(function(){runWorker(0, \'overview\')});';
		
		return $ret;
	}
}
?>
