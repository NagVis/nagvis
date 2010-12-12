<?php
/*****************************************************************************
 *
 * WuiCore.php - The core of NagVis WUI pages
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
class WuiCore extends GlobalCore {
	protected static $MAINCFG = null;
	private static $instance = null;
	
	/**
	 * Class Constructor
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	private function __construct() {}
	
	/**
	 * Getter function to initialize MAINCFG
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getMainCfg() {
		// Initialize main configuration when not set yet
		if(parent::$MAINCFG === null) {
			if(defined('CONST_MAINCFG_SITE'))
				parent::$MAINCFG = new WuiMainCfg(Array(CONST_MAINCFG_SITE, CONST_MAINCFG));
			else
				parent::$MAINCFG = new WuiMainCfg(Array(CONST_MAINCFG));

			parent::$MAINCFG->init();
			
			// Set WuiCore MAINCFG too
			self::$MAINCFG = parent::$MAINCFG;
		}
		
		return parent::$MAINCFG;
	}
	
	/**
	 * Static method for getting the instance
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	
	/**
	 * Loads and parses permissions of all maps in js array
	 *
	 * @return  String  JSON encoded array
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMapOptions() {
		$aArr = Array();
		foreach($this->getAvailableMaps() AS $map) {
			$aOpts = Array();
			
			$MAPCFG1 = new GlobalMapCfg($this, $map);
			try {
				$MAPCFG1->readMapConfig();
			} catch(MapCfgInvalid $e) {
				$aOpts['configError'] = true;
				$aOpts['configErrorMsg'] = $e->getMessage();
			}
			
			$aOpts['mapName'] = $map;
			
			// map alias
			$aOpts['mapAlias'] = $MAPCFG1->getValue('global', '0', 'alias');
			
			// used image
			$aOpts['mapImage'] = $MAPCFG1->getValue('global', '0', 'map_image');
			
			// linked maps
			$aOpts['linkedMaps'] = Array();
			foreach($MAPCFG1->getDefinitions('map') AS $key => $obj) {
				if(isset($obj['map_name'])) {
					$aOpts['linkedMaps'][] = $obj['map_name'];
				}
			}

			// used shapes
			$aOpts['usedShapes'] = Array();
			foreach($MAPCFG1->getDefinitions('shape') AS $key => $obj) {
				if(isset($obj['icon'])) {
					$aOpts['usedShapes'][] = $obj['icon'];
				}
			}
			
			$aArr[] = $aOpts;
		}
		
		return json_encode($aArr);
	}

	/**
	 * Parses the needed language strings to javascript
	 *
	 * @return  String    JSON encoded array
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getJsLang() {
		$lang = Array(
			'clickMapToSetPoints' => $this->getLang()->getText('clickMapToSetPoints'),
			'confirmDelete' => $this->getLang()->getText('confirmDelete'),
			'confirmRestore' => $this->getLang()->getText('confirmRestore'),
			'wrongValueFormat' => $this->getLang()->getText('wrongValueFormat'),
			'wrongValueFormatMap' => $this->getLang()->getText('wrongValueFormatMap'),
			'wrongValueFormatOption' => $this->getLang()->getText('wrongValueFormatOption'),
			'unableToWorkWithMap' => $this->getLang()->getText('unableToWorkWithMap'),
			'mustValueNotSet' => $this->getLang()->getText('mustValueNotSet'),
			'chosenLineTypeNotValid' => $this->getLang()->getText('chosenLineTypeNotValid'),
			'onlyLineOrIcon' => $this->getLang()->getText('onlyLineOrIcon'),
			'not2coordsX' => $this->getLang()->getText('not2coords','COORD~X'),
			'not2coordsY' => $this->getLang()->getText('not2coords','COORD~Y'),
			'only1or2coordsX' => $this->getLang()->getText('only1or2coords','COORD~X'),
			'only1or2coordsY' => $this->getLang()->getText('only1or2coords','COORD~Y'),
			'viewTypeWrong' => $this->getLang()->getText('viewTypeWrong'),
			'lineTypeNotSet' => $this->getLang()->getText('lineTypeNotSet'),
			'loopInMapRecursion' => $this->getLang()->getText('loopInMapRecursion'),
			'mapObjectWillShowSummaryState' => $this->getLang()->getText('mapObjectWillShowSummaryState'),
			'firstMustChoosePngImage' => $this->getLang()->getText('firstMustChoosePngImage'),
			'noSpaceAllowedInName' => $this->getLang()->getText('Spaces are not allowed in file names.'),
			'mustChooseValidImageFormat' => $this->getLang()->getText('mustChooseValidImageFormat'),
			'foundNoBackgroundToDelete' => $this->getLang()->getText('foundNoBackgroundToDelete'),
			'confirmBackgroundDeletion' => $this->getLang()->getText('confirmBackgroundDeletion'),
			'unableToDeleteBackground' => $this->getLang()->getText('unableToDeleteBackground'),
			'mustValueNotSet1' => $this->getLang()->getText('mustValueNotSet1'),
			'foundNoShapeToDelete' => $this->getLang()->getText('foundNoShapeToDelete'),
			'shapeInUse' => $this->getLang()->getText('shapeInUse'),
			'confirmShapeDeletion' => $this->getLang()->getText('confirmShapeDeletion'),
			'unableToDeleteShape' => $this->getLang()->getText('unableToDeleteShape'),
			'chooseMapName' => $this->getLang()->getText('chooseMapName'),
			'minOneUserAccess' => $this->getLang()->getText('minOneUserAccess'),
			'noMapToRename' => $this->getLang()->getText('noMapToRename'),
			'noNewNameGiven' => $this->getLang()->getText('noNewNameGiven'),
			'mapAlreadyExists' => $this->getLang()->getText('mapAlreadyExists'),
			'foundNoMapToDelete' => $this->getLang()->getText('foundNoMapToDelete'),
			'foundNoMapToExport' => $this->getLang()->getText('foundNoMapToExport'),
			'foundNoMapToImport' => $this->getLang()->getText('foundNoMapToImport'),
			'notCfgFile' => $this->getLang()->getText('notCfgFile'),
			'confirmNewMap' => $this->getLang()->getText('confirmNewMap'),
			'confirmMapRename' => $this->getLang()->getText('confirmMapRename'),
			'confirmMapDeletion' => $this->getLang()->getText('confirmMapDeletion'),
			'unableToDeleteMap' => $this->getLang()->getText('unableToDeleteMap'),
			'noPermissions' => $this->getLang()->getText('noPermissions'),
			'minOneUserWriteAccess' => $this->getLang()->getText('minOneUserWriteAccess'),
			'noSpaceAllowed' => $this->getLang()->getText('noSpaceAllowed'),
			'properties' => $this->getLang()->getText('properties'),
			'manualInput' => $this->getLang()->getText('manualInput'));
		
		return json_encode($lang);
	}
	
	/**
	 * Parses the validation regex of the main configuration values to javascript
	 *
	 * @return  String    JSON encoded array
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getJsValidMainConfig() {
		return json_encode(self::$MAINCFG->getValidConfig());
	}
	
	/**
	 * Checks if the mbstring extension is loaded
	 *
	 * @return  Boolean
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function checkPHPMBString($printErr=1) {
		if (!extension_loaded('mbstring')) {
			if($printErr) {
				new GlobalMessage('ERROR', $this->getLang()->getText('phpModuleNotLoaded','MODULE~mbstring'));
			}
			return FALSE;
		} else {
			return TRUE;
		}
	}
}
?>
