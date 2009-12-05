<?php
/*****************************************************************************
 *
 * GlobalBackground.php - Class for global background image handling
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
class GlobalBackground {
	protected $CORE;
	protected $image;
	protected $path;
	protected $webPath;
	protected $type;
	
	/**
	 * Constructor
	 *
	 * @param   config  $MAINCFG
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $image) {
		$this->CORE = $CORE;
		$this->image = $image;
		
		$this->fetchPath();
	}
	
	/**
	 * Gets the name of the image file
	 *
	 * @return	String File Name
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getFileName() {
		return $this->image;
	}
	
	/**
	 * Gets the locationtype of the file
	 *
	 * @return	String File Name
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getFileType() {
		return $this->type;
	}
	
	/**
	 * Fetches the path and saves it on initial load
	 *
	 * @return	String File Name
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function fetchPath() {
		if($this->getFileName() != '' && $this->getFileName() != 'none') {
			// Extract url when used to show an url
			if(preg_match('/^\[(http.*)\]$/', $this->getFileName(), $match) > 0) {
				$this->type = 'url';
				
				$this->path = $match[1];
				$this->webPath = $match[1];
			} else {
				$this->type = 'local';
				
				$this->path = $this->CORE->getMainCfg()->getValue('paths', 'map').$this->getFileName();
				$this->webPath = $this->CORE->getMainCfg()->getValue('paths', 'htmlmap').$this->getFileName();
			}
		} else {
			$this->type = 'none';
			
			$this->path = '';
			$this->webPath = '';
		}
	}
	
	/**
	 * Gets the background file
	 *
	 * @param   Boolean Get web path or alternatively the physical path
	 * @return  String  HTML Path to background file
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getFile($bWebPath = true) {
		if($bWebPath) {
			$sReturn = $this->webPath;
		} else {
			$sReturn = $this->path;
		}
		
		return $sReturn;
	}
	
	/**
	 * Checks for existing map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function checkFileExists($printErr) {
		if($this->image != '') {
			if(file_exists($this->path)) {
				return TRUE;
			} else {
				if($printErr) {
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('backgroundNotExists','IMGPATH~'.$this->CORE->getMainCfg()->getValue('paths', 'map').$this->image));
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function checkFileReadable($printErr) {
		if($this->image != '') {
			if($this->checkFileExists($printErr) && is_readable($this->path)) {
				return TRUE;
			} else {
				if($printErr) {
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('backgroundNotReadable','IMGPATH~'.$this->CORE->getMainCfg()->getValue('paths', 'map').$this->image));
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function checkFileWriteable($printErr) {
		if($this->image != '') {
			if($this->checkFileExists($printErr) && is_writable($this->path)) {
				return TRUE;
			} else {
				if($printErr) {
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('backgroundNotWriteable','IMGPATH~'.$this->CORE->getMainCfg()->getValue('paths', 'map').$this->image));
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable map image folder
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function checkFolderWriteable($printErr) {
		if(is_writable($this->CORE->getMainCfg()->getValue('paths', 'map'))) {
			return TRUE;
		} else {
			if($printErr) {
				new GlobalMessage('ERROR', $this->CORE->getLang()->getText('backgroundFolderNotWriteable','PATH~'.$this->CORE->getMainCfg()->getValue('paths', 'map').$this->image));
			}
			return FALSE;
		}
	}
}
?>
