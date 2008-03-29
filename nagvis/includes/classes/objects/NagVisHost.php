<?php
/*****************************************************************************
 *
 * NagVisHost.php - Class of a Host in NagVis with all necessary informations
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
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 	ID of queried backend
	 * @param		String		Name of the host
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisHost(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostName) {
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->type = 'host';
		$this->iconset = 'std_medium';
		parent::NagiosHost($this->MAINCFG, $this->BACKEND, $this->LANG, $backend_id, $hostName);
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
	 * PUBLIC parseGraphviz()
	 *
	 * Parses the object in graphviz configuration format
	 *
	 * @param	Integer		Number of the current Layer
	 * @param	Array		Array of hostnames which are already parsed
	 * @return	String		graphviz configuration code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseGraphviz($layer=0, &$arrHostnamesParsed) {
		$strReturn = '';
		
		if(!in_array($this->getName(), $arrHostnamesParsed)) {
			$strReturn .= $this->getType().'_'.$this->getObjectId().' [ ';
			$strReturn .= 'label=<<table border="0">';
			$strReturn .= '<tr><td><img src="'.$this->iconPath.$this->icon.'"></img></td></tr>';
			$strReturn .= '<tr><td>'.$this->getName().'</td></tr>';
			$strReturn .= '</table>>, ';
			$strReturn .= 'URL="'.$this->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/status.cgi?host='.$this->getName().'", ';
			$strReturn .= 'target="'.$this->url_target.'", ';
			$strReturn .= 'tooltip="'.$this->getType().'_'.$this->getObjectId().'",';
			// The root host has to be highlighted, this are the options to do this
			if($layer == 0) {
				$strReturn .= 'shape="egg",';
			}
			$strReturn .= 'layer="'.$layer.'"';
			$strReturn .= ' ];'."\n ";
			
			// Add host to the List of parsed hosts
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
	 * Fetches the icon for the object depending on the summary state
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchIcon() {
		// Set the paths of this icons
		$this->iconPath = $this->MAINCFG->getValue('paths', 'icon');
		$this->iconHtmlPath = $this->MAINCFG->getValue('paths', 'htmlicon');
		
		if($this->getSummaryState() != '') {
			$stateLow = strtolower($this->getSummaryState());
			
			switch($stateLow) {
				case 'unknown':
				case 'unreachable':
				case 'down':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_ack.png';
					} elseif($this->getSummaryInDowntime() == 1) {
						$icon = $this->iconset.'_downtime.png';
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.png';
					}
				break;
				case 'critical':
				case 'warning':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_sack.png';
					} elseif($this->getSummaryInDowntime() == 1) {
						$icon = $this->iconset.'_sdowntime.png';
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.png';
					}
				break;
				case 'up':
				case 'ok':
					$icon = $this->iconset.'_up.png';
				break;
				case 'pending':
					$icon = $this->iconset.'_'.$stateLow.'.png';
				break;
				default:
					$icon = $this->iconset.'_error.png';
				break;
			}
			
			//Checks whether the needed file exists
			if(@file_exists($this->MAINCFG->getValue('paths', 'icon').$icon)) {
				$this->icon = $icon;
			} else {
				$this->icon = $this->iconset.'_error.png';
			}
		} else {
			$this->icon = $this->iconset.'_error.png';
		}
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		
		if(isset($this->url) && $this->url != '') {
			$link = parent::createLink();
		} else {
			$link = '<a href="'.$this->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/status.cgi?host='.$this->host_name.'" target="'.$this->url_target.'">';
		}
		return $link;
	}
}
?>
