<?php
/*****************************************************************************
 *
 * WuiBackground.php - Class for background image handling in WUI
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
class WuiBackground extends GlobalBackground {
	function __construct($CORE, $image) {
		parent::__construct($CORE, $image);
	}
	
	/**                                
	* Uploads a map image
	*
	* @param	Boolean	$printErr
	* @return	Boolean	Is Check Successful?
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function uploadImage($arr, $printErr=1) {
		if(is_uploaded_file($arr['tmp_name'])) {
			$fileName = $arr['name'];
			if(preg_match(MATCH_PNG_GIF_JPG_FILE,$fileName)) {
				if($this->checkFolderWriteable(1)) {
					if(move_uploaded_file($arr['tmp_name'], $this->CORE->getMainCfg()->getValue('paths', 'map').$fileName)) {
						// Change permissions of the map image
						chmod($this->CORE->getMainCfg()->getValue('paths', 'map').$fileName,0666);
						
						return TRUE;
					} else {
						if($printErr) {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('moveUploadedFileFailed'));
						}
						return FALSE;
					}
				} else {
					// No need for error handling here
					return FALSE;
				}
			} else {
				if($printErr) {
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('mustChooseValidImageFormat'));
				}
				return FALSE;
			}
		} else {
			if($printErr) {
				new GlobalMessage('ERROR', $this->CORE->getLang()->getText('fileCouldNotBeUploaded'));
			}
			return FALSE;
		}
	}
}
?>
