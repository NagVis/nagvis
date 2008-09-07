<?php
/*****************************************************************************
 *
 * GlobalIndexPage.php - Class for handling the map index page
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
class GlobalIndexPage {
	private $CORE;
	private $BACKEND;
	
	private $htmlBase;
	
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @param 	GlobalBackendMgmt	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(&$CORE, &$BACKEND) {
		$this->CORE = &$CORE;
		$this->BACKEND = &$BACKEND;
		
		$this->htmlBase = $this->CORE->MAINCFG->getValue('paths','htmlbase');
	}
	
	/**
	 * Displays the automatic index page of all maps
	 *
	 * @return	Array   HTML Code of Index Page
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parse() {
			$ret = '';
			
			$ret .= '<script type="text/javascript" language="JavaScript">var htmlBase=\''.$this->htmlBase.'\'; var mapName=\'\';</script>';
			$ret .= $this->getMapIndex();
			$ret .= $this->getRotationPoolIndex();
			
			return $ret;
	}
	
	/**
	 * Returns a HTML table with the NagVis rotation pools if there are rotations
	 *
	 * @return	Array   HTML Code of Index Page
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getRotationPoolIndex() {
		$ret = '';
		
		// Only display the rotation list when enabled
		if($this->CORE->MAINCFG->getValue('index','showrotations') == 1) {
			$aRotationPools = $this->CORE->getDefinedRotationPools();
			if(count($aRotationPools) > 0) {
				$ret .= '<table class="infobox">';
				$ret .= '<tr><th colspan="2">'.$this->CORE->LANG->getText('rotationPools').'</th></tr>';
				foreach($aRotationPools AS $poolName) {
					$ROTATION = new NagVisRotation($this->CORE, $poolName);
					
					// Form the onClick action
					$onClick = 'location.href=\''.$ROTATION->getNextStepUrl().'\';';
					
					// Form the HTML code for the rotation cell
					$ret .= '<tr>';
					$ret .= '<td rowspan="'.$ROTATION->getNumSteps().'" onMouseOut="this.style.cursor=\'auto\';this.bgColor=\'\';return nd();" onMouseOver="this.style.cursor=\'pointer\';this.bgColor=\'#ffffff\';" onClick="'.$onClick.'">';
					$ret .= '<h2>'.$poolName.'</h2><br />';
					$ret .= '</td>';
					
					// Parse the code for the step list
					foreach($ROTATION->getSteps() AS $intId => $arrStep) {
						if($intId != 0) {
							$ret .= '<tr>';
						}
						$onClick = 'location.href=\''.$ROTATION->getStepUrlById($intId).'\';';
						$ret .= '<td width="250" onMouseOut="this.style.cursor=\'auto\';this.bgColor=\'\';return nd();" onMouseOver="this.style.cursor=\'pointer\';this.bgColor=\'#ffffff\';" onClick="'.$onClick.'">';
						$ret .= $ROTATION->getStepLabelById($intId).'</td>';
						$ret .= '</tr>';
					}
				}
				$ret .= '</table>';
			}
		}
		
		return $ret;
	}
	
	/**
	 * Returns a HTML table with the NagVis maps
	 *
	 * @return	Array   HTML Code of Index Page
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getMapIndex() {
		$ret = '';
		
		$intCellsPerRow = $this->CORE->MAINCFG->getValue('index','cellsperrow');
		
		$ret .= '<table>';
		$ret .= '<tr><th colspan="'.$intCellsPerRow.'">'.$this->CORE->LANG->getText('mapIndex').'</th></tr><tr>';
		$i = 1;
		foreach($this->CORE->getAvailableMaps() AS $mapName) {
			$MAPCFG = new NagVisMapCfg($this->CORE, $mapName);
			if(!$MAPCFG->readMapConfig()) {
				// Skip this map when config problem
				continue;
			}
			
			if($MAPCFG->getValue('global',0, 'show_in_lists') == 1 && ($mapName != '__automap' || ($mapName == '__automap' && $this->CORE->MAINCFG->getValue('automap', 'showinlists')))) {
				if($mapName == '__automap') {
					$opts = Array();
					
					// Fetch option array from defaultparams string (extract variable 
					// names and values)
					$params = explode('&', $this->CORE->MAINCFG->getValue('automap','defaultparams'));
					unset($params[0]);
					
					foreach($params AS &$set) {
						$arrSet = explode('=',$set);
						$opts[$arrSet[0]] = $arrSet[1];
					}
					
					$opts['preview'] = 1;
					
					$MAP = new NagVisAutoMap($this->CORE, $this->BACKEND, $opts);
					// If there is no automap image on first load of the index page,
					// render the image
					$MAP->renderMap();
				} else {
					$MAP = new NagVisMap($this->CORE, $MAPCFG, $this->BACKEND);
				}
				
				// Apply default configuration to object
				$objConf = Array();
				foreach($MAPCFG->validConfig['map'] AS $key => &$values) {
					if((!isset($objConf[$key]) || $objConf[$key] == '') && isset($values['default'])) {
						$objConf[$key] = $values['default'];
					}
				}
				$MAP->MAPOBJ->setConfiguration($objConf);
				
				// Get the icon of the map
				$MAP->MAPOBJ->fetchIcon();
				
				// Check if the user is permited to view this map
				if($MAP->MAPOBJ->checkPermissions($MAPCFG->getValue('global',0, 'allowed_user'),FALSE)) {
					if($MAP->MAPOBJ->checkMaintenance(0)) {
						$class = '';
						
						if($mapName == '__automap') {
							$onClick = 'location.href=\''.$this->htmlBase.'/index.php?automap=1'.$this->CORE->MAINCFG->getValue('automap','defaultparams').'\';';
						} else {
							$onClick = 'location.href=\''.$this->htmlBase.'/index.php?map='.$mapName.'\';';
						}
						
						$summaryOutput = $MAP->MAPOBJ->getSummaryOutput();
					} else {
						$class = 'class="disabled"';
						
						$onClick = 'alert(\''.$this->LANG->getText('mapInMaintenance').'\');';
						$summaryOutput = $this->LANG->getText('mapInMaintenance');
					}
					
					// If this is the automap display the last rendered image
					if($mapName == '__automap') {
						$imgPath = $this->CORE->MAINCFG->getValue('paths','var').'automap.png';
						$imgPathHtml = $this->CORE->MAINCFG->getValue('paths','htmlvar').'automap.png';
					} else {
						$imgPath = $this->CORE->MAINCFG->getValue('paths','map').$MAPCFG->BACKGROUND->getFileName();
						$imgPathHtml = $this->CORE->MAINCFG->getValue('paths','htmlmap').$MAPCFG->BACKGROUND->getFileName();
					}
					
					// Now form the cell with it's contents
					$MAP->MAPOBJ->replaceMacros();
					$ret .= '<td '.$class.' style="width:200px;height:200px;" '.$MAP->MAPOBJ->getHoverMenu().' onClick="'.$onClick.'">';
					$ret .= '<img align="right" src="'.$MAP->MAPOBJ->iconHtmlPath.$MAP->MAPOBJ->icon.'" />';
					$ret .= '<h2>'.$MAPCFG->getValue('global', '0', 'alias').'</h2><br />';
					if($MAPCFG->getValue('global', 0,'usegdlibs') == '1' && $MAP->checkGd(1)) {
						$ret .= '<img style="width:200px;height:150px;" src="'.$this->createThumbnail($imgPath, $mapName).'" /><br />';
					} else {
						$ret .= '<img style="width:200px;height:150px;" src="'.$imgPathHtml.'" /><br />';
					}
					$ret .= '</td>';
					if($i % $intCellsPerRow == 0) {
							$ret .= '</tr><tr>';
					}
					$i++;
				}
			}
		}
		// Fill table with empty cells if there are not enough maps to get the line filled
		if(($i - 1) % $intCellsPerRow != 0) {
				for($a=0;$a < ($intCellsPerRow - (($i - 1) % $intCellsPerRow));$a++) {
						$ret .= '<td>&nbsp;</td>';
				}
		}
		$ret .= '</tr>';
		$ret .= '</table>';
		
		return $ret;
	}
	
	/**
	 * Creates thumbnail images for the index map
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createThumbnail($imgPath, $mapName) {
		if($this->CORE->checkVarFolderWriteable(TRUE) && $this->checkImageExists($imgPath, TRUE)) {
			// 0: width, 1:height, 2:type
			$imgSize = getimagesize($imgPath);
			$strFileType = '';
			
			switch($imgSize[2]) {
				case 1:
					$image = imagecreatefromgif($imgPath);
					$strFileType = 'gif';
				break;
				case 2:
					$image = imagecreatefromjpeg($imgPath);
					$strFileType = 'jpg';
				break;
				case 3:
					$image = imagecreatefrompng($imgPath);
					$strFileType = 'png';
				break;
				default:
					$FRONTEND = new GlobalPage($this->CORE);
					$FRONTEND->messageToUser('ERROR', $this->LANG->getText('onlyPngOrJpgImages'));
				break;
			}
			
			$pathThumbImage = $this->CORE->MAINCFG->getValue('paths','var').$mapName.'-thumb.'.$strFileType;
			
			// Size of source images
			$bgWidth = $imgSize[0];
			$bgHeight = $imgSize[1];
			
			// Target size
			$thumbResWidth = 200;
			$thumbResHeight = 150;
			
			if($bgWidth > $bgHeight) {
				// Calculate size
				$thumbWidth = $thumbResWidth;
				$thumbHeight = $bgHeight / ($bgWidth / $thumbWidth);
				
				// Calculate offset
				$thumbX = 0;
				$thumbY = ($thumbResHeight - $thumbHeight) / 2;
			} elseif($bgHeight > $bgWidth) {
				// Calculate size
				$thumbHeight = $thumbResHeight;
				$thumbWidth = $bgWidth / ($bgHeight / $thumbResHeight);
				
				// Calculate offset
				$thumbX = ($thumbResWidth - $thumbWidth) / 2;
				$thumbY = 0;
			} else {
				// Calculate size
				if($thumbResWidth > $thumbResHeight) {
						$thumbHeight = $thumbResHeight;
						$thumbWidth = $thumbResHeight;
				} elseif($thumbResHeight > $thumbResWidth) {
						$thumbHeight = $thumbResWidth;
						$thumbWidth = $thumbResWidth;
				} else {
						$thumbHeight = $thumbResHeight;
						$thumbWidth = $thumbResHeight;
				}
				
				// Calculate offset
				$thumbX = ($thumbResWidth - $thumbWidth) / 2;
				$thumbY = ($thumbResHeight - $thumbHeight) / 2;
			}
			
			$thumb = imagecreatetruecolor($thumbResWidth, $thumbResHeight); 
			
			imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 254));
			imagecolortransparent($thumb, imagecolorallocate($thumb, 255, 255, 254));
			
			imagecopyresampled($thumb, $image, $thumbX, $thumbY, 0, 0, $thumbWidth, $thumbHeight, $bgWidth, $bgHeight);
			
			switch($imgSize[2]) {
				case 1:
					imagegif($thumb, $pathThumbImage);
				break;
				case 2:
					imagejpeg($thumb, $pathThumbImage);
				break;
				case 3:
					imagepng($thumb, $pathThumbImage);
				break;
				default:
					$FRONTEND = new GlobalPage($this->CORE);
					$FRONTEND->messageToUser('ERROR', $this->LANG->getText('onlyPngOrJpgImages'));
				break;
			}
			
			return $this->CORE->MAINCFG->getValue('paths','htmlvar').$mapName.'-thumb.'.$strFileType;
		} else {
			return '';
		}
	}
	
	/**
	 * Checks Image exists
	 *
	 * @param 	String	$imgPath
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkImageExists($imgPath, $printErr) {
		if(file_exists($imgPath)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->CORE);
				$FRONTEND->messageToUser('WARNING', $this->CORE->LANG->getText('imageNotExists','FILE~'.$imgPath));
			}
			return FALSE;
		}
	}
}
?>
