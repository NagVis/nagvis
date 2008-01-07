<?php
/**
 * Class for handling the background images in WUI
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
class WuiBackground extends GlobalBackground {
	function WuiBackground(&$MAINCFG, $image) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackground::WuiBackground(&$MAINCFG)');
		parent::GlobalBackground($MAINCFG, $image);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackground::WuiBackground()');
	}
	
	/**
	* Deletes the map image
	*
	* @param	Boolean	$printErr
	* @return	Boolean	Is Check Successful?
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function deleteImage($printErr=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackground::deleteImage('.$printErr.')');
		if($this->checkFolderWriteable($printErr) && $this->checkFileWriteable($printErr)) {
			if(unlink($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackground::deleteImage(): TRUE');
				return TRUE;
			} else {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('ERROR','couldNotDeleteMapImage','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackground::deleteImage(): FALSE');
				return FALSE;
			}
		}
	}
	
	/**
	* Uploads a map image
	*
	* @param	Boolean	$printErr
	* @return	Boolean	Is Check Successful?
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function uploadImage($arr, $printErr=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackground::uploadImage(Array(...))');
		if(is_uploaded_file($arr['tmp_name'])) {
			$fileName = $arr['name'];
			if(preg_match('/\.png/i',$fileName)) {
				if($this->checkFolderWriteable(1)) {
					if(move_uploaded_file($arr['tmp_name'], $this->MAINCFG->getValue('paths', 'map').$fileName)) {
						// Change permissions of the map image
						chmod($this->MAINCFG->getValue('paths', 'map').$fileName,0666);
						
						if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackground::uploadImage(): TRUE');
						return TRUE;
					} else {
						// Error handling
						$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:backgroundManagement'));
						$FRONTEND->messageToUser('ERROR','moveUploadedFileFailed');
						return FALSE;
					}
				} else {
					// No need for error handling here
					return FALSE;
				}
			} else {
				// Error handling
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:backgroundManagement'));
				$FRONTEND->messageToUser('ERROR','mustBePngFile');
				return FALSE;
			}
		} else {
			// Error handling
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:backgroundManagement'));
			$FRONTEND->messageToUser('ERROR','fileCouldNotBeUploaded');
			return FALSE;
		}
	}
	
	/**
	* Creates a simple map image
	*
	* @param	Boolean	$printErr
	* @return	Boolean	Is Check Successful?
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function createImage($color, $width, $height) {
		if(!$this->checkFileExists(0)) {
			if($this->checkFolderWriteable(1)) {
				$image = imagecreatetruecolor($width, $height);
				
				// get rgb color from hexcode
				$color = str_replace('#','',$color);
				$int = hexdec($color);
				$r = 0xFF & ($int >> 0x10);
				$g = 0xFF & ($int >> 0x8);
				$b = 0xFF & $int;
				
				$bgColor = imagecolorallocate($image, $r, $g, $b);
				imagefill($image, 0, 0, $bgColor);
				imagepng($image,$this->MAINCFG->getValue('paths', 'map').$this->image);
				imagedestroy($image);
				
				return TRUE;
			} else {
				// No need for error handling here
				return FALSE;
			}
		} else {
			// Error handling
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:backgroundManagement'));
			$FRONTEND->messageToUser('ERROR','imageAlreadyExists','IMAGE~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
			return FALSE;
		}
	}
}
?>