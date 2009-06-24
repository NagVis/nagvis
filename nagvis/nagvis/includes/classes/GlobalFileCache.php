<?php
/*****************************************************************************
 *
 * GlobalFileCache.php - Class for handling caching of config files etc.
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: lars@vertical-visions.de)
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
class GlobalFileCache {
	private $CORE;
	private $file;
	private $cacheFile;
	
	private $fileAge;
	private $cacheFileAge;
	
	/**
	 * Class Constructor
	 *
	 * @param 	Object  Object of GlobalCore
	 * @param 	String  File to check
	 * @param   String  Path to cache file
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $file, $cacheFile) {
		$this->CORE = $CORE;
		$this->file = $file;
		$this->cacheFile = $cacheFile;
		
		if($this->checkFileExists(0)) {
			$this->fileAge = filemtime($this->file);
		}
		
		if($this->checkCacheFileExists(0)) {
			$this->cacheFileAge = filemtime($this->cacheFile);
		}
	}
	
	/**
	 * Reads the cached things from cache and returns them
	 *
	 * @return	Cached things
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getCache() {
		return unserialize(file_get_contents($this->cacheFile));
	}
	
	/**
	 * Writes the things to cache
	 *
	 * @param   Things which should be written to cache
	 * @param   Boolean $printErr
	 * @return  Boolean	Is Successful?
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function writeCache($contents, $printErr=1) {
		// Perform file writeable check only when cache file exists
		// When no cache file exists check if file can be created in directory
		if((!$this->checkCacheFileExists(0) && $this->checkCacheFolderWriteable($printErr)) || ($this->checkCacheFileExists(0) && $this->checkCacheFileWriteable($printErr))) {
			if(($fp = fopen($this->cacheFile, 'w+')) === FALSE){
				if($printErr == 1) {
					new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('cacheFileNotWriteable','FILE~'.$this->cacheFile), $this->CORE->MAINCFG->getValue('paths','htmlbase'));
				}
				return FALSE;
			}
			
			fwrite($fp, serialize($contents));
			fclose($fp);
			
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks if the file has been cached
	 *
	 * @param   Boolean  $printErr
	 * @return  Integer  Unix timestamp of cache creation time or -1 when not cached
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function isCached($printErr=0) {
		// Checks
		// a) Cache file exists
		// b) Cache file older than regular file
		if($this->checkCacheFileExists($printErr) 
			&& $this->getFileAge() <= $this->getCacheFileAge()) {
			return $this->getCacheFileAge();
		} else {
			if($printErr) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('fileNotCached', 'FILE~'.$this->file.',CACHEFILE~'.$this->cacheFile), $this->CORE->MAINCFG->getValue('paths','htmlbase'));
			}
			return -1;
		}
	}
	
	/**
	 * Checks for writeable cache folder
	 *
	 * @param   Boolean  $printErr
	 * @return  Boolean  Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkCacheFolderWriteable($printErr) {
		if(is_writeable(dirname($this->cacheFile))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('cacheFolderNotWriteable', Array('FILE' => $this->cacheFile)), $this->CORE->MAINCFG->getValue('paths','htmlbase'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable cache file
	 *
	 * @param   Boolean  $printErr
	 * @return  Boolean  Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkCacheFileWriteable($printErr) {
		if(is_writeable($this->cacheFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('cacheFileNotWriteable','FILE~'.$this->cacheFile), $this->CORE->MAINCFG->getValue('paths','htmlbase'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for existing cache file
	 *
	 * @param   Boolean  $printErr
	 * @return  Boolean  Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkCacheFileExists($printErr) {
		if(file_exists($this->cacheFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('cacheFileNotExists','FILE~'.$this->cacheFile), $this->CORE->MAINCFG->getValue('paths','htmlbase'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for existing file
	 *
	 * @param   Boolean  $printErr
	 * @return  Boolean  Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkFileExists($printErr) {
		if(file_exists($this->file)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('fileNotExists','FILE~'.$this->file), $this->CORE->MAINCFG->getValue('paths','htmlbase'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Returns the last modification time of the template file
	 *
	 * @return  Integer  Unix Timestamp
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getFileAge() {
		return $this->fileAge;
	}
	
	/**
	 * Returns the last modification time of the cache file
	 *
	 * @return  Integer  Unix Timestamp
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getCacheFileAge() {
		return $this->cacheFileAge;
	}
}
?>
