<?php
/*****************************************************************************
 *
 * GlobalIndexPage.php - Class for handling the map index page
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
class GlobalIndexPage {
	private $CORE;
	private $BACKEND;
	private $AUTHORISATION = null;
	
	private $htmlBase;
	
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @param 	CoreBackendMgmt	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(GlobalCore $CORE, CoreBackendMgmt $BACKEND, CoreAuthorisationHandler $AUTHORISATION) {
		$this->CORE = $CORE;
		$this->BACKEND = $BACKEND;
		$this->AUTHORISATION = $AUTHORISATION;
		
		$this->htmlBase = $this->CORE->getMainCfg()->getValue('paths','htmlbase');
	}
	
	/**
	 * Parses the maps and automaps for the overview page
	 *
	 * @return	String  Json Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 * FIXME: More cleanups, compacting and extraction of single parts
	 */
	public function parseMapsJson($type) {
		// Only display the rotation list when enabled
		if(!$this->CORE->getMainCfg()->getValue('index','show'.$type.'s') == 1)
			return Array();

		if($type == 'automap')
			$mapList = $this->CORE->getAvailableAutomaps();
		else
			$mapList = $this->CORE->getAvailableMaps();

		$aMaps = Array();
		$aObjs = Array();
		foreach($mapList AS $object_id => $mapName) {
			// Check if the user is permitted to view this
			if($type == 'automap') {
				if(!$this->AUTHORISATION->isPermitted('AutoMap', 'view', $mapName))
					continue;
			} else {
				if(!$this->AUTHORISATION->isPermitted('Map', 'view', $mapName))
					continue;
			}
			
			$map = Array();
			$map['type'] = $type;
			
			if($type == 'automap')
				$MAPCFG = new NagVisAutomapCfg($this->CORE, $mapName);
			else
				$MAPCFG = new NagVisMapCfg($this->CORE, $mapName);
			
			try {
				$MAPCFG->readMapConfig();
			} catch(MapCfgInvalid $e) {
				$map['configError'] = true;
				$map['configErrorMsg'] = $e->getMessage();
			}
			
			if($MAPCFG->getValue('global',0, 'show_in_lists') != 1)
				continue;
			
			if($type == 'automap')
				$MAP = new NagVisAutoMap($this->CORE, $MAPCFG, $this->BACKEND, Array('automap' => $mapName, 'preview' => 1), !IS_VIEW);
			else
				$MAP = new NagVisMap($this->CORE, $MAPCFG, $this->BACKEND, GET_STATE, !IS_VIEW);
			
			// Apply default configuration to object
			$objConf = $MAPCFG->getTypeDefaults('global');
			$objConf['type'] = 'map';
			$objConf['map_name'] = $MAPCFG->getName();
			$objConf['object_id'] = $type.'-'.$object_id;
			// Enable the hover menu in all cases - maybe make it configurable
			$objConf['hover_menu'] = 1;
			$objConf['hover_childs_show'] = 1;
			$objConf['hover_template'] = 'default';
			unset($objConf['alias']);
			
			$MAP->MAPOBJ->setConfiguration($objConf);
			
			if(isset($map['configError'])) {
				$map['overview_class']  = 'error';
				$map['overview_url']    = 'javascript:alert(\''.$map['configErrorMsg'].'\');';
				$map['summary_output']  = $this->CORE->getLang()->getText('Map Configuration Error: '.$map['configErrorMsg']);
				
				$MAP->MAPOBJ->clearMembers();
				$MAP->MAPOBJ->setSummaryState('ERROR');
				$MAP->MAPOBJ->fetchIcon();
			} elseif($MAP->MAPOBJ->checkMaintenance(0)) {
				if($type == 'automap')
					$map['overview_url']    = $this->htmlBase.'/index.php?mod=AutoMap&act=view&show='.$mapName.$MAPCFG->getValue('global', 0, 'default_params');
				else
					$map['overview_url']    = $this->htmlBase.'/index.php?mod=Map&act=view&show='.$mapName;
				
				$map['overview_class']  = '';
			} else {
				$map['overview_class']  = 'disabled';
				$map['overview_url']    = 'javascript:alert(\''.$this->CORE->getLang()->getText('The map is in maintenance mode. Please be patient.').'\');';
				$map['summary_output']  = $this->CORE->getLang()->getText('The map is in maintenance mode. Please be patient.');
				
				$MAP->MAPOBJ->clearMembers();
				$MAP->MAPOBJ->setSummaryState('UNKNOWN');
				$MAP->MAPOBJ->fetchIcon();
			}
			
			// If this is the automap display the last rendered image
			if($type == 'automap') {
				$imgPath     = $this->CORE->getMainCfg()->getValue('paths', 'sharedvar') . $mapName . '.png';
				$imgPathHtml = $this->CORE->getMainCfg()->getValue('paths', 'htmlsharedvar') . $mapName . '.png';
				
				// Only handle the thumbnail immage when told to do so
				if($this->CORE->getMainCfg()->getValue('index','showmapthumbs') == 1) {
					// If there is no automap image on first load of the index page,
					// render the image
					if(!$this->checkImageExists($imgPath, FALSE))
						$MAP->renderMap();

					// If the message still does not exist print an error and skip the thumbnail generation
          if($this->checkImageExists($imgPath, FALSE)) {
						if($this->CORE->checkGd(0)) {
							$sThumbFile = $mapName.'-thumb.'.$this->getFileType($imgPath);
							$sThumbPath = $this->CORE->getMainCfg()->getValue('paths','sharedvar').$sThumbFile;
							$sThumbPathHtml = $this->CORE->getMainCfg()->getValue('paths','htmlsharedvar').$sThumbFile;
							
							// Only create a new thumb when there is no cached one
							$FCACHE = new GlobalFileCache($this->CORE, $imgPath, $sThumbPath);
							if($FCACHE->isCached() === -1) {
								$image = $this->createThumbnail($imgPath, $sThumbPath);
							}
							
							$map['overview_image'] = $sThumbPathHtml;
						} else {
							$map['overview_image'] = $imgPathHtml;
						}
					}
				}
				
				$MAP->MAPOBJ->fetchIcon();
				
				$aMaps[] = array_merge($MAP->MAPOBJ->parseJson(), $map);
			} else {
				// Only handle thumbnail image when told to do so
				if($this->CORE->getMainCfg()->getValue('index','showmapthumbs') == 1) {
					$imgPath = $MAPCFG->BACKGROUND->getFile(GET_PHYSICAL_PATH);
					$imgPathHtml = $MAPCFG->BACKGROUND->getFile();
					
					// Check if
					// a) PHP supports gd
					// b) The image is a local one
					// c) The image exists
					if($this->CORE->checkGd(0) && $MAPCFG->BACKGROUND->getFileType() == 'local' && file_exists($imgPath)) {
						$sThumbFile = $mapName.'-thumb.'.$this->getFileType($imgPath);
						$sThumbPath = $this->CORE->getMainCfg()->getValue('paths','sharedvar').$sThumbFile;
						$sThumbPathHtml = $this->CORE->getMainCfg()->getValue('paths','htmlsharedvar').$sThumbFile;
						
						// Only create a new thumb when there is no cached one
						$FCACHE = new GlobalFileCache($this->CORE, $imgPath, $sThumbPath);
						if($FCACHE->isCached() === -1) {
							$image = $this->createThumbnail($imgPath, $sThumbPath);
						}
						
						$map['overview_image'] = $sThumbPathHtml;
					} else {
						$map['overview_image'] = $imgPathHtml;
					}
				}
				
				$MAP->MAPOBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
				$aObjs[] = Array($MAP->MAPOBJ, $map);
			}
		}
		
		if($type == 'map') {
			$this->BACKEND->execute();
				
			foreach($aObjs AS $aObj) {
				$aObj[0]->applyState();
				$aObj[0]->fetchIcon();

				$aMaps[] = array_merge($aObj[0]->parseJson(), $aObj[1]);
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
		// Only display the rotation list when enabled
		if($this->CORE->getMainCfg()->getValue('index','showrotations') != 1)
			return json_encode(Array());

		$aRotations = Array();
		foreach($this->CORE->getDefinedRotationPools() AS $poolName) {
			// Check if the user is permitted to view this rotation
			if(!$this->AUTHORISATION->isPermitted('Rotation', 'view', $poolName))
				continue;
			
			$ROTATION = new CoreRotation($this->CORE, $poolName);
			$iNum = $ROTATION->getNumSteps();
			$aSteps = Array();
			for($i = 0; $i < $iNum; $i++) {
				$aSteps[] = Array('name' => $ROTATION->getStepLabelById($i),
													'url'  => $ROTATION->getStepUrlById($i));
			}
			
			$aRotations[] = Array('name'      => $poolName,
														'url'       => $ROTATION->getStepUrlById(0),
														'num_steps' => $ROTATION->getNumSteps(),
														'steps'     => $aSteps);
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
		
		$arr['cellsperrow'] = $this->CORE->getMainCfg()->getValue('index', 'cellsperrow');
		$arr['showautomaps'] = $this->CORE->getMainCfg()->getValue('index', 'showautomaps');
		$arr['showmaps'] = $this->CORE->getMainCfg()->getValue('index', 'showmaps');
		$arr['showgeomap'] = $this->CORE->getMainCfg()->getValue('index', 'showgeomap');
		$arr['showmapthumbs'] = $this->CORE->getMainCfg()->getValue('index', 'showmapthumbs');
		$arr['showrotations'] = $this->CORE->getMainCfg()->getValue('index', 'showrotations');
		
		$arr['page_title'] = $this->CORE->getMainCfg()->getValue('internal', 'title');
		$arr['favicon_image'] = $this->CORE->getMainCfg()->getValue('paths', 'htmlimages').'internal/favicon.png';
		$arr['background_color'] = $this->CORE->getMainCfg()->getValue('index','backgroundcolor');
		
		$arr['lang_mapIndex'] = $this->CORE->getLang()->getText('mapIndex');
		$arr['lang_automapIndex'] = $this->CORE->getLang()->getText('Automap Index');
		$arr['lang_rotationPools'] = $this->CORE->getLang()->getText('rotationPools');
		
		$arr['event_log'] = $this->CORE->getMainCfg()->getValue('defaults', 'eventlog');
		$arr['event_log_level'] = $this->CORE->getMainCfg()->getValue('defaults', 'eventloglevel');
		$arr['event_log_height'] = $this->CORE->getMainCfg()->getValue('defaults', 'eventlogheight');
		$arr['event_log_hidden'] = $this->CORE->getMainCfg()->getValue('defaults', 'eventloghidden');
		
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
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('onlyPngOrJpgImages'));
				break;
			}
			
			// Size of source images
			list($bgWidth, $bgHeight) = $imgSize;
			
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
					new GlobalMessage('ERROR', $this->CORE->getLang()->getText('onlyPngOrJpgImages'));
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
				new GlobalMessage('WARNING', $this->CORE->getLang()->getText('imageNotExists','FILE~'.$imgPath));
			}
			return FALSE;
		}
	}
}
?>
