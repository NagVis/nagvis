<?php
/**
 * Class for handling the background image
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalBackground {
	var $MAINCFG;
	var $image;
	
	function GlobalBackground(&$MAINCFG, $image) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackground::GlobalBackground(&$MAINCFG)');
		$this->MAINCFG = &$MAINCFG;
		$this->image = $image;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackground::GlobalBackground()');
	}
	
	/**
	 * Gets the name of the image file
	 *
	 * @return	String File Name
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getFileName() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalBackground::getFileName()');
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalBackground::getFileName()');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapImageExists('.$printErr.')');
		if($this->image != '') {
			//if(file_exists($this->MAINCFG->getValue('paths', 'map').$this->image)) {
			if(@fclose(@fopen($this->MAINCFG->getValue('paths', 'map').$this->image, 'r'))) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageExists(): TRUE');
				return TRUE;
			} else {
				if($printErr) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:global'));
					$FRONTEND->messageToUser('ERROR','backgroundNotExists','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageExists(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageExists(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapImageReadable('.$printErr.')');
		if($this->image != '') {
			if($this->checkFileExists($printErr) && is_readable($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageReadable(): TRUE');
				return TRUE;
			} else {
				if($printErr) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:global'));
					$FRONTEND->messageToUser('ERROR','backgroundNotReadable','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageReadable(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageReadable(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapImageWriteable('.$printErr.')');
		if($this->image != '') {
			if($this->checkFileExists($printErr) && is_writable($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageWriteable(): TRUE');
				return TRUE;
			} else {
				if($printErr) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:global'));
					$FRONTEND->messageToUser('ERROR','backgroundNotWriteable','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				}
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageWriteable(): FALSE');
				return FALSE;
			}
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageWriteable(): FALSE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMapCfg::checkMapImageWriteable('.$printErr.')');
		if(is_writable($this->MAINCFG->getValue('paths', 'map'))) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageWriteable(): TRUE');
			return TRUE;
		} else {
			if($printErr) {
				//Error Box
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'wui:global'));
				$FRONTEND->messageToUser('ERROR','backgroundFolderNotWriteable','PATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMapCfg::checkMapImageWriteable(): FALSE');
			return FALSE;
		}
	}
}
?>