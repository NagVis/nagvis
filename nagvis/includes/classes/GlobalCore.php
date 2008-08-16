<?php
/*****************************************************************************
 *
 * GlobalCore.php - The core of NagVis pages
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: michael_luebben@web.de)
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
 * class GlobalCore
 *
 * @author  Lars Michelsen <lars@vertical-visions.de
 */
class GlobalCore {
	public $MAINCFG;
	public $LANG;

	/**
	 * Class Constructor
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(&$MAINCFG = NULL, &$LANG = NULL) {
		if($MAINCFG == NULL) {
			// Load the main configuration
			$this->MAINCFG = new GlobalMainCfg(CONST_MAINCFG);
		} else {
			$this->MAINCFG = &$MAINCFG;
		}
		
		if($LANG == NULL) {
			// Initialize language
			$this->LANG = new GlobalLanguage($this->MAINCFG);
		} else {
			$this->LANG = &$LANG;
		}
	}
	
	/* Here are some methods defined which get used all over NagVis and have
	 * no other special place where they could be located */
	
	/**
	 * Reads all defined Backend-ids from the main configuration
	 *
	 * @return	Array Backend-IDs
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	public function getDefinedBackends() {
		$ret = Array();
		foreach($this->MAINCFG->config AS $sec => $var) {
			if(preg_match("/^backend_/i", $sec)) {
				$ret[] = $var['backendid'];
			}
		}
		
		return $ret;
	}
	
	/**
	 * Gets all rotation pools
	 *
	 * @return	Array pools
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function getDefinedRotationPools() {
		$ret = Array();
		
		foreach($this->MAINCFG->config AS $sec => &$var) {
			if(preg_match('/^rotation_/i', $sec)) {
				$ret[] = $var['rotationid'];
			}
		}
		
		return $ret;
	}
	
	/**
	 * Reads all languages
	 *
	 * @return	Array list
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableLanguages() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'language'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match('/.xml$/', $file)) {
					$files[] = str_replace('wui_','',str_replace('.xml','',$file));
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all aviable backends
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableBackends() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'class'))) {
 			while (false !== ($file = readdir($handle))) {
 				if ($file != "." && $file != ".." && preg_match('/^class.GlobalBackend-(.+).php/', $file, $arrRet)) {
					$files[] = $arrRet[1];
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all hover templates in hovertemplate path
	 *
	 * @return	Array hover templates
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	public function getAvailableHoverTemplates() {
		$files = Array();
		
		if($handle = opendir($this->MAINCFG->getValue('paths', 'hovertemplate'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != '.' && $file != '..' && preg_match(MATCH_HTML_TEMPLATE_FILE, $file, $arrRet)) {
					$files[] = $arrRet[1];
				}
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all header templates
	 *
	 * @return	Array list
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableHeaderTemplates() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'headertemplate'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != '.' && $file != '..' && preg_match(MATCH_HTML_TEMPLATE_FILE, $file, $arrRet)) {
					$files[] = $arrRet[1];
				}
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all PNG images in shape path
	 *
	 * @return	Array Shapes
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableShapes() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'shape'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match(MATCH_PNG_GIF_JPG_FILE, $file, $arrRet)) {
					$files[] = $arrRet[1];
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all iconsets in icon path
	 *
	 * @return	Array iconsets
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableIconsets() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'icon'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match('/(.+)_ok.(png|gif|jpg)$/', $file, $arrRet)) {
					$files[] = $arrRet[1];
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all maps in mapcfg path
	 *
	 * @return	Array maps
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	public function getAvailableMaps() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'mapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match(MATCH_CFG_FILE, $file, $arrRet)) {
					$files[] = $arrRet[1];
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all map images in map path
	 *
	 * @return	Array map images
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	public function getAvailableBackgroundImages() {
		$files = Array();
		
		if($handle = opendir($this->MAINCFG->getValue('paths', 'map'))) {
 			while(false !== ($file = readdir($handle))) {
				if(preg_match(MATCH_PNG_GIF_JPG_FILE, $file)) {
					$files[] = $file;
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
}
?>
