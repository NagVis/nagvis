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
	var $MAINCFG;
	var $image;
	
	function GlobalBackground(&$MAINCFG, $image) {
		$this->MAINCFG = &$MAINCFG;
		$this->image = $image;
	}
	
	/**
	 * Gets the name of the image file
	 *
	 * @return	String File Name
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getFileName() {
		return $this->image;
	}
	
	/**
	 * Checks for existing map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkFileExists($printErr) {
		if($this->image != '') {
			if(file_exists($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				if($printErr) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG);
					$FRONTEND->messageToUser('ERROR','backgroundNotExists','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
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
	function checkFileReadable($printErr) {
		if($this->image != '') {
			if($this->checkFileExists($printErr) && is_readable($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				if($printErr) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG);
					$FRONTEND->messageToUser('ERROR','backgroundNotReadable','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
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
	function checkFileWriteable($printErr) {
		if($this->image != '') {
			if($this->checkFileExists($printErr) && is_writable($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				if($printErr) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG);
					$FRONTEND->messageToUser('ERROR','backgroundNotWriteable','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
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
	function checkFolderWriteable($printErr) {
		if(is_writable($this->MAINCFG->getValue('paths', 'map'))) {
			return TRUE;
		} else {
			if($printErr) {
				//Error Box
				$FRONTEND = new GlobalPage($this->MAINCFG);
				$FRONTEND->messageToUser('ERROR','backgroundFolderNotWriteable','PATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
			}
			return FALSE;
		}
	}
}
?>