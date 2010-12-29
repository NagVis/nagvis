<?php
/*****************************************************************************
 *
 * NagVisHost.php - Class of a Host in NagVis with all necessary information
 *                  which belong to the object handling in NagVis
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
class NagVisHost extends NagiosHost {
	protected static $langType = null;
	protected static $langSelf = null;
	protected static $langChild = null;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class CoreBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 		ID of queried backend
	 * @param		String		Name of the host
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $BACKEND, $backend_id, $hostName) {
		$this->type = 'host';
		$this->iconset = 'std_medium';
		parent::__construct($CORE, $BACKEND, $backend_id, $hostName);
	}
	
	/**
	 * PUBLIC parseJson()
	 *
	 * Parses the object in json format
	 *
	 * @return	String		JSON code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseJson() {
		return parent::parseJson();
	}
	
	/**
	 * PUBLIC parseGraphviz()
	 *
	 * Parses the object in graphviz configuration format
	 *
	 * @param	Integer		Number of the current Layer
	 * @param	Array			Array of hostnames which are already parsed
	 * @return	String		graphviz configuration code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseGraphviz($layer=0, &$arrHostnamesParsed) {
		$strReturn = '';
		
		if(!in_array($this->getName(), $arrHostnamesParsed)) {
			if($this->icon == '')
				$this->fetchIcon();
			
			// Get the image size
			list($width, $height, $type, $attr) = getimagesize($this->CORE->getMainCfg()->getValue('paths', 'icon').$this->icon);
			
			$strReturn .= $this->getType().'_'.$this->getObjectId().' [ ';
			$strReturn .= 'label="", ';
			$strReturn .= 'URL="'.str_replace(array('[htmlcgi]', '[host_name]'),
				array($this->CORE->getMainCfg()->getValue('backend_'.$this->backend_id, 'htmlcgi'), $this->getName()),
				$this->CORE->getMainCfg()->getValue('defaults', 'hosturl')).'", ';
			$strReturn .= 'target="'.$this->url_target.'", ';
			$strReturn .= 'tooltip="'.$this->getType().'_'.$this->getObjectId().'",';
			
			// The root host has to be highlighted, these are the options to do this
			/*if($layer == 0) {
				$strReturn .= 'shape="egg",';
			}*/
			
			// This should be scaled by the choosen iconset
			if($width != 16) {
				$strReturn .= 'width="'.$this->pxToInch($width).'", ';
			}
			if($height != 16) {
				$strReturn .= 'height="'.$this->pxToInch($height).'", ';
			}
			
			$strReturn .= 'layer="'.$layer.'"';
			$strReturn .= ' ];'."\n ";
			
			// Add host to the list of parsed hosts
			$arrHostnamesParsed[] = $this->getName();
			
			foreach($this->getChildsAndParents() As $OBJ) {
				if(is_object($OBJ)) {
					$strReturn .= $OBJ->parseGraphviz($layer+1, $arrHostnamesParsed);

					// Add the line to visualize the direction
					$strReturn .= $this->getType().'_'.$this->getObjectId().' -- '.$OBJ->getType().'_'.$OBJ->getObjectId().' [';
					$strReturn .= 'color=black, ';
					$strReturn .= 'decorate=1, ';
					$strReturn .= 'style=solid, ';
					if(isset($this->line_arrow) && $this->line_arrow != 'none')
						$strReturn .= 'dir='.$this->line_arrow.', ';
					$strReturn .= 'weight=2 ';
					$strReturn .= ' ];'."\n ";
				}
			}
		}
		
		return $strReturn;
	}
	
	/**
	 * This methods converts pixels to inches
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function pxToInch($px) {
		return number_format($px/72, 4, '.','');
	}
	
	# End public methods
	# #########################################################################
}
?>
