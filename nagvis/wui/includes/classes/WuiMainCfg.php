<?php
/*****************************************************************************
 *
 * WuiMainCfg.php - Class for handling the main configuration of NagVis in WUI
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
class WuiMainCfg extends GlobalMainCfg {
	/**
	 * Class Constructor
	 *
	 * @param	String	$configFile			String with path to config file
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiMainCfg($configFile) {
		parent::__construct($configFile);
	}
	
	/**
	 * Gets all informations about an object type
	 *
	 * @param   String  Type to get the informations for
	 * @return  Array   The validConfig array
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	function getValidObjectType($type) {
		return $this->validConfig[$type];
	}
	
	/**
	 * Gets the valid configuration array
	 *
	 * @return	Array The validConfig array
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getValidConfig() {
		return $this->validConfig;
	}
	
	/**
	 * Gets the configuration array
	 *
	 * @return	Array The validConfig array
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getConfig() {
		return $this->config;
	}
	
	/**
	 * Sets a config section in the config array
	 *
	 * @param	String	$sec	Section
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setSection($sec) {
		// Try to append new backends after already defined
		if(preg_match('/^backend_/', $sec)) { 
		    $lastBackendIndex = 0;
		    $i = 0;
		    // Loop all sections to find the last defined backend
		    foreach($this->config AS $type => $vars) {
		        // If the current section is a backend
						if(preg_match('/^backend_/', $type)) { 
		            $lastBackendIndex = $i;
		        }
		        $i++;
		    }
		    
		    if($lastBackendIndex != 0) {
		        // Append the new section after the already defined
		        $slicedBefore = array_slice($this->config, 0, ($lastBackendIndex + 1));
		        $slicedAfter = array_slice($this->config, ($lastBackendIndex + 1));
		        $tmp[$sec] = Array();
		        $this->config = array_merge($slicedBefore,$tmp,$slicedAfter);
		    } else {
		        // If no defined backend found, add it to the EOF
		        $this->config[$sec] = Array();
		    }
	    } else {
	        $this->config[$sec] = Array();
	    }
		
		return TRUE;
	}
	
	/**
	 * Deletes a config section in the config array
	 *
	 * @param	String	$sec	Section
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function delSection($sec) {
		$this->config[$sec] = '';
		unset($this->config[$sec]);
		
		return TRUE;
	}
	
	/**
	 * Writes the config file completly from array $this->configFile
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function writeConfig() {
		// Check for config file write permissions
		if($this->checkNagVisConfigWriteable(1)) {
			$content = '';
			foreach($this->config as $key => &$item) {
				if(is_array($item)) {
					$content .= '['.$key.']'."\n";
					foreach ($item as $key2 => &$item2) {
						if(substr($key2,0,8) == 'comment_') {
							$content .= $item2."\n";
						} else {
							if(is_numeric($item2) || is_bool($item2)) {
								$content .= $key2."=".$item2."\n";
							} else {
								if(is_array($item2) && preg_match('/^rotation_/i', $key) && $key2 == 'maps') {
									$val = '';
									// Check if an element has a label defined
									foreach($item2 AS $intId => $arrStep) {
										$seperator = ',';
										$label = '';
										$step = '';
										
										if($intId == 0) {
											$seperator = '';
										}
										
										if(isset($arrStep['map']) && $arrStep['map'] != '') {
											$step = $arrStep['map'];
										} else {
											$step = '['.$arrStep['url'].']';
										}
										
										if(isset($arrStep['label']) && $arrStep['label'] != '' && $arrStep['label'] != $step) {
											$label = $arrStep['label'].':';
										}
										
										// Save the extracted informations to an array
										$val .= $seperator.$label.$step;
									}
									
									$item2 = $val;
								}
								
								// Don't write the backendid attribute (Is internal)
								if($key2 !== 'backendid') {
									$content .= $key2.'="'.$item2.'"'."\n";
								}
							}
						}
					}
				} elseif(substr($key,0,8) == 'comment_') {
					$content .= $item."\n";
				}
			}
			
			if(!$handle = fopen($this->configFile, 'w+')) {
				$CORE = new GlobalCore($this);
				new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mainCfgNotWriteable'), $CORE->MAINCFG->getValue('paths','htmlbase'));
				return FALSE;
			}
			
			if(!fwrite($handle, $content)) {
				$CORE = new GlobalCore($this);
				new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mainCfgCouldNotWriteMainConfigFile'), $CORE->MAINCFG->getValue('paths','htmlbase'));
				return FALSE;
			}
			
			fclose($handle);
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkNagVisConfigWriteable($printErr) {
		if(is_writeable($this->configFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$CORE = new GlobalCore($this);
				new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mainCfgNotWriteable','MAINCFG~'.$this->configFile), $CORE->MAINCFG->getValue('paths','htmlbase'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable MapCfgFolder
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkMapCfgFolderWriteable($printErr) {
		if(file_exists(substr($this->getValue('paths', 'mapcfg'),0,-1)) && is_writable(substr($this->getValue('paths', 'mapcfg'),0,-1))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$CORE = new GlobalCore($this);
				new GlobalFrontendMessage('ERROR', $CORE->LANG->getText('mapCfgDirNotWriteable','MAPPATH~'.$this->getValue('paths', 'mapcfg')), $CORE->MAINCFG->getValue('paths','htmlbase'));
			}
			return FALSE;
		}
	}
}
?>
