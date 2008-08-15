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
		parent::GlobalMainCfg($configFile);
	}
	
	/**
	 * Gets all defined maps
	 *
	 * @return	Array maps
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMaps() {
		$files = Array();
		
		if($fh = opendir($this->getValue('paths', 'mapcfg'))) {
 			while(FALSE !== ($file = readdir($fh))) {
				// only handle *.cfg files
				if(ereg('\.cfg$',$file)) {
					$files[] = substr($file, 0, strlen($file) - 4);
				}				
			}
			
			if(count($files) > 1) {
				natcasesort($files);
			}
		}
		closedir($fh);
		
		return $files;
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
	 * Sets a config section in the config array
	 *
	 * @param	String	$sec	Section
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setSection($sec) {
		// Try to append new backends after already defined
		if(ereg('^backend_', $sec)) {
		    $lastBackendIndex = 0;
		    $i = 0;
		    // Loop all sections to find the last defined backend
		    foreach($this->config AS $type => $vars) {
		        // If the current section is a backend
		        if(ereg('^backend_', $type)) {
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
			foreach($this->config as $key => &$item) {
				if(is_array($item)) {
					$content .= '['.$key.']'."\n";
					foreach ($item as $key2 => &$item2) {
						if(substr($key2,0,8) == 'comment_') {
							$content .= $item2."\n";
						} else {
							if(is_numeric($item2) || is_bool($item2))
								$content .= $key2."=".$item2."\n";
							else
							$content .= $key2.'="'.$item2.'"'."\n";
						}
					}
				} elseif(substr($key,0,8) == 'comment_') {
					$content .= $item."\n";
				} else {
					if(is_numeric($item) || is_bool($item))
						$content .= $key.'='.$item."\n";
					else
						$content .= $key.'="'.$item.'"'."\n";
				}
			}
			
			if(!$handle = fopen($this->configFile, 'w+')) {
				$FRONTEND = new GlobalPage($this);
				$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('mainCfgNotWriteable'));
				return FALSE;
			}
			
			if(!fwrite($handle, $content)) {
				$FRONTEND = new GlobalPage($this);
				$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('mainCfgCouldNotWriteMainConfigFile'));
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
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('mainCfgNotWriteable','MAINCFG~'.$this->configFile));
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
				$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('mapCfgDirNotWriteable','MAPPATH~'.$this->getValue('paths', 'mapcfg')));
			}
			return FALSE;
		}
	}
}
?>
