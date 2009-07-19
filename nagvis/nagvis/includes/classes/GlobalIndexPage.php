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
	public function __construct($CORE, $BACKEND) {
		$this->CORE = $CORE;
		$this->BACKEND = $BACKEND;
		
		$this->htmlBase = $this->CORE->MAINCFG->getValue('paths','htmlbase');
	}
	
	/**
	 * Parses the information for json
	 *
	 * @return	String 	String with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseJson() {
		$ret = '';
		$ret .= 'oGeneralProperties='.$this->CORE->MAINCFG->parseGeneralProperties().';'."\n";
		$ret .= 'oWorkerProperties='.$this->CORE->MAINCFG->parseWorkerProperties().';'."\n";
		$ret .= 'oFileAges='.$this->parseFileAges().';'."\n";
		$ret .= 'oPageProperties='.$this->parseIndexPropertiesJson().';'."\n";
		$ret .= 'aInitialMaps='.$this->parseMapsJson().';'."\n";
		$ret .= 'aInitialRotations='.$this->parseRotationsJson().';'."\n";
		$ret .= 'aMaps=Array();'."\n";
		$ret .= 'aRotations=Array();'."\n";
		
		// Kick of the worker
		$ret .= 'addDOMLoadEvent(function(){runWorker(0, \'overview\')});';
		
		return $ret;
	}
	
	/**
	 * Parses the config file ages
	 *
	 * @return	String 	JSON Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseFileAges() {
		$arr = Array();
		
		$arr['main_config'] = $this->CORE->MAINCFG->getConfigFileAge();
		
		return json_encode($arr);
	}
	
	/**
	 * Parses the maps for the overview page
	 *
	 * @return	String  Json Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseMapsJson() {
		$aMaps = Array();
		
		foreach($this->CORE->getAvailableMaps() AS $object_id => $mapName) {
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
				foreach($MAPCFG->getValidTypeKeys('map') AS $key) {
					$objConf[$key] = $MAPCFG->getValue('global', 0, $key);
				}
				$objConf['type'] = 'map';
				$objConf['map_name'] = $MAPCFG->getName();
				$objConf['object_id'] = $object_id;
				
				$MAP->MAPOBJ->setConfiguration($objConf);
				
				// Get the icon of the map
				$MAP->MAPOBJ->fetchIcon();
				
				// Check if the user is permitted to view this map
				if($MAPCFG->checkPermissions($MAPCFG->getValue('global',0, 'allowed_user'),FALSE)) {
					if($MAP->MAPOBJ->checkMaintenance(0)) {
						$class = '';
						$url = '';
						
						if($mapName == '__automap') {
							$url = $this->htmlBase.'/index.php?automap=1'.$this->CORE->MAINCFG->getValue('automap','defaultparams');
						} else {
							$url = $this->htmlBase.'/index.php?map='.$mapName;
						}
						
						$summaryOutput = $MAP->MAPOBJ->getSummaryOutput();
					} else {
						$class = 'disabled';
						
						$url = 'javascript:alert(\''.$this->CORE->LANG->getText('mapInMaintenance').'\');';
						$summaryOutput = $this->CORE->LANG->getText('mapInMaintenance');
					}
					
					// If this is the automap display the last rendered image
					if($mapName == '__automap') {
						$imgPath = $this->CORE->MAINCFG->getValue('paths','var').'automap.png';
						$imgPathHtml = $this->CORE->MAINCFG->getValue('paths','htmlvar').'automap.png';
					} else {
						$imgPath = $this->CORE->MAINCFG->getValue('paths','map').$MAPCFG->BACKGROUND->getFileName();
						$imgPathHtml = $this->CORE->MAINCFG->getValue('paths','htmlmap').$MAPCFG->BACKGROUND->getFileName();
					}
					
					if($this->CORE->checkGd(0) && $MAPCFG->BACKGROUND->getFileName() != '') {
						$sThumbFile = $mapName.'-thumb.'.$this->getFileType($imgPath);
						$sThumbPath = $this->CORE->MAINCFG->getValue('paths','var').$sThumbFile;
						$sThumbPathHtml = $this->CORE->MAINCFG->getValue('paths','htmlvar').$sThumbFile;
						
						// Only create a new thumb when there is no cached one
						$FCACHE = new GlobalFileCache($this->CORE, $imgPath, $sThumbPath);
						if($FCACHE->isCached() === -1) {
							$image = $this->createThumbnail($imgPath, $sThumbPath);
						}
						
						$image = $sThumbPathHtml;
					} else {
						$image = $imgPathHtml;
					}
					
					$arr = $MAP->MAPOBJ->parseJson();
					
					$arr['overview_class'] = $class;
					$arr['overview_url'] = $url;
					$arr['overview_image'] = $image;
					
					$aMaps[] = $arr;
				}
			}
		}
		
		return json_encode($aMaps);
	}
	
	/**
	 * Parses the rotations for the overview page
	 *
	 * @return	String  Json Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseRotationsJson() {
		$aRotations = Array();
		
		// Only display the rotation list when enabled
		if($this->CORE->MAINCFG->getValue('index','showrotations') == 1) {
			$aRotationPools = $this->CORE->getDefinedRotationPools();
			if(count($aRotationPools) > 0) {
				foreach($aRotationPools AS $poolName) {
					$ROTATION = new NagVisRotation($this->CORE, $poolName);
					
					$aSteps = Array();
					
					// Parse the code for the step list
					foreach($ROTATION->getSteps() AS $intId => $arrStep) {
						$aSteps[] = Array('name' => $ROTATION->getStepLabelById($intId),
						                  'url' => $ROTATION->getStepUrlById($intId));
					}
					
					$aRotations[] = Array('name' => $poolName,
					                      'url' => $ROTATION->getNextStepUrl(),
					                      'num_steps' => $ROTATION->getNumSteps(),
														    'steps' => $aSteps);
				}
			}
		}
		
		return json_encode($aRotations);
	}
	
	/**
	 * Parses the overview page options in json format
	 *
	 * @return	String 	String with JSON Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseIndexPropertiesJson() {
		$arr = Array();
		
		$arr['cellsperrow'] = $this->CORE->MAINCFG->getValue('index','cellsperrow');
		$arr['showrotations'] = $this->CORE->MAINCFG->getValue('index','showrotations');
		
		$arr['page_title'] = $this->CORE->MAINCFG->getValue('internal', 'title');
		// FIXME: State of the overview like on maps would be nice
		$arr['favicon_image'] = $this->CORE->MAINCFG->getValue('paths', 'htmlimages').'internal/favicon.png';
		$arr['background_color'] = $this->CORE->MAINCFG->getValue('index','backgroundcolor');
		
		$arr['lang_mapIndex'] = $this->CORE->LANG->getText('mapIndex');
		$arr['lang_rotationPools'] = $this->CORE->LANG->getText('rotationPools');
		
		$arr['event_log'] = $this->CORE->MAINCFG->getValue('defaults', 'eventlog');
		$arr['event_log_level'] = $this->CORE->MAINCFG->getValue('defaults', 'eventloglevel');
		$arr['event_log_height'] = $this->CORE->MAINCFG->getValue('defaults', 'eventlogheight');
		$arr['event_log_hidden'] = $this->CORE->MAINCFG->getValue('defaults', 'eventloghidden');
		
		return json_encode($arr);
	}
	
	/**
	 * Returns the filetype
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getFileType($imgPath) {
		$imgSize = getimagesize($imgPath);
		switch($imgSize[2]) {
			case 1:
				$strFileType = 'gif';
			break;
			case 2:
				$strFileType = 'jpg';
			break;
			case 3:
				$strFileType = 'png';
			break;
			default:
				$strFileType = '';
			break;
		}
		
		return $strFileType;
	}
	
	/**
	 * Creates thumbnail images for the index map
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function createThumbnail($imgPath, $thumbPath) {
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
					new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('onlyPngOrJpgImages'));
				break;
			}
			
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
					imagegif($thumb, $thumbPath);
				break;
				case 2:
					imagejpeg($thumb, $thumbPath);
				break;
				case 3:
					imagepng($thumb, $thumbPath);
				break;
				default:
					new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('onlyPngOrJpgImages'));
				break;
			}
			
			return $thumbPath;
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
	private function checkImageExists($imgPath, $printErr) {
		if(file_exists($imgPath)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalFrontendMessage('WARNING', $this->CORE->LANG->getText('imageNotExists','FILE~'.$imgPath));
			}
			return FALSE;
		}
	}
}
?>
