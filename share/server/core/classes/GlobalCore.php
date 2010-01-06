<?php
/*****************************************************************************
 *
 * GlobalCore.php - The core of NagVis pages
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
 * class GlobalCore
 *
 * @author  Lars Michelsen <lars@vertical-visions.de
 */
class GlobalCore {
	protected static $MAINCFG = null;
	protected static $LANG = null;
	protected static $AUTHENTICATION = null;
	protected static $AUTHORIZATION = null;
	
	private static $instance = null;
	protected $iconsetTypeCache = Array();

	/**
	 * Deny construct
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	private function __construct() {}
	
	/**
	 * Deny clone
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	private function __clone() {}
	
	/**
	 * Getter function to initialize MAINCFG
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getMainCfg() {
		if(self::$MAINCFG === null) {
			// Initialize main configuration when not set yet
			self::$MAINCFG = new GlobalMainCfg(CONST_MAINCFG);
			self::$MAINCFG->init();
		}
		
		return self::$MAINCFG;
	}
	
	/**
	 * Getter function to initialize LANG
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getLang() {
		if(self::$LANG === null) {
			// Initialize language when not set yet
			self::$LANG = new GlobalLanguage();
		}
		
		return self::$LANG;
	}
	
	/**
	 * Setter for AA
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function setAA(CoreAuthHandler $A1, CoreAuthorisationHandler $A2 = null) {
		self::$AUTHENTICATION = $A1;
		self::$AUTHORIZATION = $A2;
	}
	
	/**
	 * Getter function for AUTHORIZATION
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getAuthorization() {
		return self::$AUTHORIZATION;
	}
	
	/**
	 * Getter function for AUTHENTICATION
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getAuthentication() {
		return self::$AUTHENTICATION;
	}
	
	/**
	 * Static method for getting the instance
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/* Here are some methods defined which get used all over NagVis and have
	 * no other special place where they could be located */
  
	/**
	 * Check if GD-Libs installed, when GD-Libs are enabled
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkGd($printErr) {
	if(!extension_loaded('gd')) {
		if($printErr) {
			new GlobalMessage('WARNING', self::getLang()->getText('gdLibNotFound'));
		}
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	/**
	 * Reads all defined Backend-ids from the main configuration
	 *
	 * @return	Array Backend-IDs
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	public function getDefinedBackends() {
		$ret = Array();
		foreach(self::getMainCfg()->getSections() AS $name) {
			if(preg_match('/^backend_/i', $name)) {
				$ret[] = self::getMainCfg()->getValue($name, 'backendid');
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
	public function getDefinedRotationPools() {
		$ret = Array();
		foreach(self::getMainCfg()->getSections() AS $name) {
			if(preg_match('/^rotation_/i', $name)) {
				$id = self::getMainCfg()->getValue($name, 'rotationid');
				$ret[$id] = $id;
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
		
		if ($handle = opendir(self::getMainCfg()->getValue('paths', 'language'))) {
 			while (false !== ($file = readdir($handle))) {
				if(!preg_match('/^\./', $file)) {
					$files[] = $file;
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
		return $files;
	}
	
	/**
	 * Reads all languages which are available in NagVis and
	 * are enabled by the configuration
	 *
	 * @return	Array list
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableAndEnabledLanguages() {
		$aRet = Array();
		
		foreach($this->getAvailableLanguages() AS $val) {
			if(in_array($val, self::getMainCfg()->getValue('global', 'language_available'))) {
				$aRet[] = $val;
			}
		}
		
		return $aRet;
	}
	
	/**
	 * Reads all available backends
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableBackends() {
		$files = Array();
		
		if ($handle = opendir(self::getMainCfg()->getValue('paths', 'class'))) {
 			while (false !== ($file = readdir($handle))) {
 				if(preg_match('/^GlobalBackend([^MI].+)\.php$/', $file, $arrRet)) {
					$files[] = $arrRet[1];
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
		return $files;
	}
	
	/**
	 * Reads all hover templates
	 *
	 * @return	Array hover templates
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	public function getAvailableHoverTemplates() {
		$files = Array();
		
		if($handle = opendir(self::getMainCfg()->getValue('paths', 'templates'))) {
 			while (false !== ($file = readdir($handle))) {
				if(preg_match(MATCH_HOVER_TEMPLATE_FILE, $file, $arrRet)) {
					$files[] = $arrRet[1];
				}
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
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
		
		if ($handle = opendir(self::getMainCfg()->getValue('paths', 'templates'))) {
 			while (false !== ($file = readdir($handle))) {
				if(preg_match(MATCH_HEADER_TEMPLATE_FILE, $file, $arrRet)) {
					$files[] = $arrRet[1];
				}
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
		return $files;
	}
	
	/**
	 * Reads all header templates
	 *
	 * @return	Array list
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableContextTemplates() {
		$files = Array();
		
		if ($handle = opendir(self::getMainCfg()->getValue('paths', 'templates'))) {
 			while (false !== ($file = readdir($handle))) {
				if(preg_match(MATCH_CONTEXT_TEMPLATE_FILE, $file, $arrRet)) {
					$files[] = $arrRet[1];
				}
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
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
		
		if ($handle = opendir(self::getMainCfg()->getValue('paths', 'shape'))) {
 			while (false !== ($file = readdir($handle))) {
				if(preg_match(MATCH_PNG_GIF_JPG_FILE, $file, $arrRet)) {
					$files[] = $arrRet[0];
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
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
		
		if($handle = opendir(self::getMainCfg()->getValue('paths', 'icon'))) {
 			while (false !== ($file = readdir($handle))) {
				if(preg_match('/^(.+)_ok.(png|gif|jpg)$/', $file, $arrRet)) {
					$files[] = $arrRet[1];
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
		return $files;
	}
	
	/**
	 * Returns the filetype of an iconset
	 *
	 * @param   String  Iconset name
	 * @return	String  Iconset file type
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getIconsetFiletype($iconset) {
		$type = '';
		
		if(isset($this->iconsetTypeCache[$iconset])) {
			$type = $this->iconsetTypeCache[$iconset];
		} else {
			if($handle = opendir(self::getMainCfg()->getValue('paths', 'icon'))) {
				while (false !== ($file = readdir($handle))) {
					// First filter all files with _ok, it is faster than regex all
					if(strpos($file, '_ok') !== FALSE) {
						if(preg_match('/^'.$iconset.'_ok.(png|gif|jpg)$/', $file, $arrRet)) {
							$type = $arrRet[1];
						}
					}
				}
			}
			closedir($handle);
		}
		
		// Catch error when iconset filetype could not be fetched
		if($type === '') {
			new GlobalMessage('ERROR', self::getLang()->getText('iconsetFiletypeUnknown', Array('ICONSET' => $iconset)));
		}
		
		$this->iconsetTypeCache[$iconset] = $type;
		return $type;
	}
	
	/**
	 * Reads all automaps in automapcfg path
	 *
	 * @param   String  Regex to match the map name
	 * @return	Array   Array of maps
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableAutomaps($strMatch = NULL) {
		$files = Array();
		
		if($handle = opendir(self::getMainCfg()->getValue('paths', 'automapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if(preg_match(MATCH_CFG_FILE, $file, $arrRet)) {
					if($strMatch == NULL || ($strMatch != NULL && preg_match($strMatch, $arrRet[1]))) {
							$files[$arrRet[1]] = $arrRet[1];
					}
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
		return $files;
	}
	
	/**
	 * Reads all maps in mapcfg path
	 *
	 * @param   String  Regex to match the map name
	 * @return	Array   Array of maps
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableMaps($strMatch = NULL) {
		$files = Array();
		
		if ($handle = opendir(self::getMainCfg()->getValue('paths', 'mapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if(preg_match(MATCH_CFG_FILE, $file, $arrRet)) {
					if($strMatch == NULL || ($strMatch != NULL && preg_match($strMatch, $arrRet[1]))) {
							$files[$arrRet[1]] = $arrRet[1];
					}
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
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
		
		if($handle = opendir(self::getMainCfg()->getValue('paths', 'map'))) {
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
	
	/**
	 * Reads all available gadgets
	 *
	 * @return	Array   Array of gadgets
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAvailableGadgets() {
		$files = Array();
		
		if ($handle = opendir(self::getMainCfg()->getValue('paths', 'gadget'))) {
 			while (false !== ($file = readdir($handle))) {
				if($file !== 'gadgets_core.php') {
					if(preg_match(MATCH_PHP_FILE, $file, $arrRet)) {
						$files[] = $arrRet[1];
					}
				}
			}
			
			if ($files) {
				natcasesort($files);
			}
			
			closedir($handle);
		}
		
		return $files;
	}
	
	/**
	 * This method checks if the given map is a automap
	 * This is quite hackish but have no better option at the moment
	 *
	 * @param   String      Name of the map
	 * @return	Boolean		Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkMapIsAutomap($sMap) {
		$aAutomaps = $this->getAvailableAutomaps();
		
		if(in_array($sMap, $aAutomaps)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Checks for existing var folder
	 *
	 * @param		Boolean 	$printErr
	 * @return	Boolean		Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkVarFolderExists($printErr) {
		if(file_exists(substr(self::getMainCfg()->getValue('paths', 'var'),0,-1))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalMessage('ERROR', self::getLang()->getText('varFolderNotExists','PATH~'.self::getMainCfg()->getValue('paths', 'var')));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable VarFolder
	 *
	 * @param		Boolean 	$printErr
	 * @return	Boolean		Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkVarFolderWriteable($printErr) {
		if($this->checkVarFolderExists($printErr) && is_writable(substr(self::getMainCfg()->getValue('paths', 'var'),0,-1)) && @file_exists(self::getMainCfg()->getValue('paths', 'var').'.')) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalMessage('ERROR', self::getLang()->getText('varFolderNotWriteable','PATH~'.self::getMainCfg()->getValue('paths', 'var')));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for existing shared var folder
	 *
	 * @param		Boolean 	$printErr
	 * @return	Boolean		Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkSharedVarFolderExists($printErr) {
		if(file_exists(substr(self::getMainCfg()->getValue('paths', 'sharedvar'),0,-1))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalMessage('ERROR', self::getLang()->getText('The shared var folder [PATH] does not exist', Array('PATH' => self::getMainCfg()->getValue('paths', 'sharedvar'))));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable shared var folder
	 *
	 * @param		Boolean 	$printErr
	 * @return	Boolean		Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkSharedVarFolderWriteable($printErr) {
		if($this->checkSharedVarFolderExists($printErr) && is_writable(substr(self::getMainCfg()->getValue('paths', 'sharedvar'),0,-1)) && @file_exists(self::getMainCfg()->getValue('paths', 'sharedvar').'.')) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalMessage('ERROR', self::getLang()->getText('The shared var folder [PATH] is not writeable', Array('PATH' => self::getMainCfg()->getValue('paths', 'sharedvar'))));
			}
			return FALSE;
		}
	}
}
?>
