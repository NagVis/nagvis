<?php
/** 
 * Class for printing the map
 * Should be used by ALL pages of NagVis and NagVisWui
 */
class GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	
	var $linkedMaps = Array();
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalMap(&$MAINCFG,&$MAPCFG) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::GlobalMap($MAINCFG,$MAPCFG)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::GlobalMap()');
	}
	
	/**
	 * Check if GD-Libs installed, when GD-Libs are enabled.
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkGd($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::checkGd('.$printErr.')');
		if($this->MAPCFG->getValue('global', 0, 'usegdlibs') == '1') {
        	if(!extension_loaded('gd')) {
        		if($printErr) {
	                $FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','gdLibNotFound');
	            }
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkGd(): FALSE');
	            return FALSE;
            } else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkGd(): TRUE');
            	return TRUE;
        	}
        } else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkGd(): TRUE');
            return TRUE;
        }
	}
	
	/**
	 * Gets the background of the map
	 *
	 * @param	String	$type	Type of Background (gd/img)
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getBackground($type='gd') {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getBackground('.$type.')');
		$style = '';
		if($this->MAPCFG->getName() != '') {
			if($this->MAPCFG->getValue('global', 0,'usegdlibs') == '1' && $type == 'gd' && $this->checkGd(0)) {
				$src = './draw.php?map='.$this->MAPCFG->getName();
			} else {
				$src = $this->MAINCFG->getValue('paths', 'htmlmap').$this->MAPCFG->getImage();
			}
		} else {
			$src = './images/internal/wuilogo.png';
			$style = 'width:800px; height:600px;';
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getBackground(): Array(...)');
		return Array('<img id="background" src="'.$src.'" style="z-index:0;'.$style.'" alt="">');
	}
	
	/**
	 * Searches the icon for an object
	 *
	 * @param	Array	$obj	Array with object properties
	 * @return	String	Name of the icon
	 * @author Michael Luebben <michael_luebben@web.de>
	 * @author Lars Michelsen <lars@vertical-visions.de>
     */
	function getIcon(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getIcon(&$obj)');
		$stateLow = strtolower($obj['state']);
		
		switch($obj['type']) {
			case 'map':
				switch($stateLow) {
					case 'ok':
					case 'warning':
					case 'critical':
					case 'unknown':
					case 'ack':		
						$icon = $obj['iconset'].'_'.$stateLow.'.png';
					break;
					default:
						$icon = $obj['iconset'].'_error.png';
					break;
				}
			break;
			case 'host':
			case 'hostgroup':
				switch($stateLow) {
					case 'down':
					case 'unknown':
					case 'critical':
					case 'unreachable':
					case 'warning':
					case 'ack':
					case 'up':
						$icon = $obj['iconset'].'_'.$stateLow.'.png';
					break;
					default:
						$icon = $obj['iconset'].'_error.png';
					break;
				}
			break;
			case 'service':
			case 'servicegroup':
				switch($stateLow) {
					case 'critical':
					case 'warning':
					case 'unknown':
					case 'ok':
						$icon = $obj['iconset'].'_'.$stateLow.'.png';
					break;
					case 'ack':
						$icon = $obj['iconset'].'_s'.$stateLow.'.png';
					break;
					default:	
						$icon = $obj['iconset'].'_error.png';
					break;
				}
			break;
			default:
					$icon = $obj['iconset'].'_error.png';
			break;
		}
		
		//replaced: if(file_exists($this->MAINCFG->getValue('paths', 'icon').$icon)) {
		if(@fclose(@fopen($this->MAINCFG->getValue('paths', 'icon').$icon,'r'))) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getIcon(): '.$icon);
			return $icon;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getIcon(): '.$obj['iconset'].'_error.png');
			return $obj['iconset'].'_error.png';
		}
	}
	
	/**
	 * Create a position for a icon on the map
	 *
	 * @param	Array	Array with object properties
	 * @return	Array	Array with object properties
	 * @author	Michael Luebben <michael_luebben@web.de>
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fixIcon(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::fixIcon(&$obj)');
		if(!isset($obj['path']) || $obj['path'] == '') {
			$imgPath = $obj['icon'];
		} else {
			$imgPath = $obj['path'].$obj['icon'];
		}
		
		if(!file_exists($imgPath)) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		    $FRONTEND->messageToUser('WARNING','iconNotExists','IMGPATH~'.$imgPath);
		    
			$obj['path'] = $this->MAINCFG->getValue('paths', 'icon');
			$obj['htmlPath'] = $this->MAINCFG->getValue('paths', 'htmlicon');
			$obj['icon'] = '20x20.gif';
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::fixIcon(): Array(...)');
		return $obj;
	}
	
	/**
	 * Gets the paths to the icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	Array with object informations
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIconPaths(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getIconPaths(&$obj)');
		if($obj['type'] == 'shape') {
			if(preg_match('/^\[(.*)\]$/',$obj['icon'],$match) > 0) {
				$obj['path'] = '';
				$obj['htmlPath'] = '';
			} else {
				$obj['path'] = $this->MAINCFG->getValue('paths', 'shape');
				$obj['htmlPath'] = $this->MAINCFG->getValue('paths', 'htmlshape');
			}
		} else {
			$obj['path'] = $this->MAINCFG->getValue('paths', 'icon');
			$obj['htmlPath'] = $this->MAINCFG->getValue('paths', 'htmlicon');
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getIconPaths(): Array(...)');
		return $obj;
	}
	
	/**
	 * Checks for valid Permissions
	 *
	 * @param 	String 	$allowed	
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkPermissions(&$allowed,$printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::checkPermissions(Array(...),'.$printErr.')');
		if(isset($allowed) && !in_array('EVERYONE', $allowed) && !in_array($this->MAINCFG->getRuntimeValue('user'),$allowed)) {
        	if($printErr) {
        		$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		        $FRONTEND->messageToUser('ERROR','permissionDenied','USER~'.$this->MAINCFG->getRuntimeValue('user'));
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkPermissions(): FALSE');
			return FALSE;
        } else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkPermissions(): TRUE');
        	return TRUE;
    	}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::checkPermissions(): TRUE');
		return TRUE;
	}
}