<?php
/*****************************************************************************
 *
 * GlobalBackground.php - Class for global background image handling
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
class GlobalBackground {
	protected $CORE;
	protected $image;
	
	/**
	 * Constructor
	 *
	 * @param   config  $MAINCFG
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $image) {
		$this->CORE = $CORE;
		$this->image = $image;
	}
	
	/**
	 * Gets the name of the image file
	 *
	 * @return	String File Name
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getFileName() {
		return $this->image;
	}
	
	/**
	 * Gets the background file
	 *
	 * @return  String  HTML Path to background file
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getFile() {
		$sReturn = '';
		if($this->getFileName() != '' && $this->getFileName() != 'none') {
			$sReturn = $this->CORE->getMainCfg()->getValue('paths', 'htmlmap').$this->getFileName();
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
			if(file_exists($this->CORE->getMainCfg()->getValue('paths', 'map').$this->image)) {
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
			if($this->checkFileExists($printErr) && is_readable($this->CORE->getMainCfg()->getValue('paths', 'map').$this->image)) {
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
			if($this->checkFileExists($printErr) && is_writable($this->CORE->getMainCfg()->getValue('paths', 'map').$this->image)) {
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
