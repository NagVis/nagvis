<?php
/*****************************************************************************
 *
 * NagVisHost.php - Class of a Host in NagVis with all necessary information
 *                  which belong to the object handling in NagVis
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
class NagVisHost extends NagiosHost {
	var $CORE;
	var $BACKEND;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 		ID of queried backend
	 * @param		String		Name of the host
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisHost(&$CORE, &$BACKEND, $backend_id, $hostName) {
		$this->CORE = &$CORE;
		
		$this->BACKEND = &$BACKEND;
		$this->type = 'host';
		$this->iconset = 'std_medium';
		parent::NagiosHost($this->CORE, $this->BACKEND, $backend_id, $hostName);
	}
	
	/**
	 * PUBLIC parse()
	 *
	 * Parses the object in HTML format
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parse() {
		return parent::parse();
	}
	
	/**
	 * PUBLIC parseJson()
	 *
	 * Parses the object in json format
	 *
	 * @return	String		JSON code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseJson() {
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
	function parseGraphviz($layer=0, &$arrHostnamesParsed) {
		$strReturn = '';
		
		if(!in_array($this->getName(), $arrHostnamesParsed)) {
			$strReturn .= $this->getType().'_'.$this->getObjectId().' [ ';
			$strReturn .= 'label=<<table border="0">';
			if(isset($this->statusmap_image) && $this->statusmap_image != '') {
				$strReturn .= '<tr><td><img src="'.$this->CORE->MAINCFG->getValue('paths', 'shape').$this->statusmap_image.'"></img></td></tr>';
			}
			$strReturn .= '<tr><td><img src="'.$this->iconPath.$this->icon.'"></img></td></tr>';
			$strReturn .= '<tr><td>'.$this->getName().'</td></tr>';
			$strReturn .= '</table>>, ';
			$strReturn .= 'URL="'.$this->CORE->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/status.cgi?host='.$this->getName().'", ';
			$strReturn .= 'target="'.$this->url_target.'", ';
			$strReturn .= 'tooltip="'.$this->getType().'_'.$this->getObjectId().'",';
			// The root host has to be highlighted, these are the options to do this
			if($layer == 0) {
				$strReturn .= 'shape="egg",';
			}
			$strReturn .= 'layer="'.$layer.'"';
			$strReturn .= ' ];'."\n ";
			
			// Add host to the list of parsed hosts
			$arrHostnamesParsed[] = $this->getName();
			
			foreach($this->getChilds() As $OBJ) {
				if(is_object($OBJ)) {
					$strReturn .= $OBJ->parseGraphviz($layer+1, $arrHostnamesParsed);
					$strReturn .= $this->getType().'_'.$this->getObjectId().' -- '.$OBJ->getType().'_'.$OBJ->getObjectId().' [color=black, decorate=1, style=solid, weight=2 ];'."\n ";
				}
			}
		}
		
		return $strReturn;
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * PRIVATE getUrl()
	 *
	 * Returns the url for the object link
	 *
	 * @return	String	URL
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getUrl() {
		if(isset($this->url) && $this->url != '') {
			$url = parent::getUrl();
		} else {
			$url = $this->CORE->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/status.cgi?host='.$this->host_name;
		}
		return $url;
	}
}
?>
