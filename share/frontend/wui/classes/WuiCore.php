<?php
/*****************************************************************************
 *
 * WuiCore.php - The core of NagVis WUI pages
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
		if(parent::$MAINCFG === null) {
			// Initialize main configuration when not set yet
			parent::$MAINCFG = new WuiMainCfg(CONST_MAINCFG);
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
			
			$MAPCFG1 = new WuiMapCfg($this, $map);
			$MAPCFG1->readMapConfig(0);
			
			$aOpts['mapName'] = $map;
			
			// map alias
			$aOpts['mapAlias'] = $MAPCFG1->getValue('global', '0', 'alias');
			
			// used image
			$aOpts['mapImage'] = $MAPCFG1->getValue('global', '0', 'map_image');
			
			// permited users for writing
			$aOpts['allowedForConfig'] = Array();
			$arr = $MAPCFG1->getValue('global', '0', 'allowed_for_config');
			for($i = 0; count($arr) > $i; $i++) {
				$aOpts['allowedForConfig'][] = $arr[$i];
			}
			
			// permited users for viewing the map
			$aOpts['allowedUsers'] = Array();
			$arr = $MAPCFG1->getValue('global', '0', 'allowed_user');
			for($i = 0; count($arr) > $i; $i++) {
				$aOpts['allowedUsers'][] = $arr[$i];
			}
			
			// linked maps
			$aOpts['linkedMaps'] = Array();
			foreach($MAPCFG1->getDefinitions('map') AS $key => $obj) {
				$aOpts['linkedMaps'][] = $obj['map_name'];
			}

			// used shapes
			$aOpts['usedShapes'] = Array();
			foreach($MAPCFG1->getDefinitions('shape') AS $key => $obj) {
				$aOpts['usedShapes'][] = $obj['icon'];
			}
			
			$aArr[] = $aOpts;
		}
		
		return json_encode($aArr);
	}
	
	/**
	 * Parses the needed language strings in json format
	 * for the menu
	 *
	 * @return  String    JSON encoded array
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getJsLangMenu() {
		$langMenu = Array(
			'overview' => $this->getLang()->getText('overview'),
			'restore' => $this->getLang()->getText('restore'),
			'properties' => $this->getLang()->getText('properties'),
			'addObject' => $this->getLang()->getText('addObject'),
			'nagVisConfig' => $this->getLang()->getText('nagVisConfig'),
			'help' => $this->getLang()->getText('help'),
			'open' => $this->getLang()->getText('open'),
			'openInNagVis' => $this->getLang()->getText('openInNagVis'),
			'manageMaps' => $this->getLang()->getText('manageMaps'),
			'manageBackends' => $this->getLang()->getText('manageBackends'),
			'manageBackgrounds' => $this->getLang()->getText('manageBackgrounds'),
			'manageShapes' => $this->getLang()->getText('manageShapes'),
			'icon' => $this->getLang()->getText('icon'),
			'line' => $this->getLang()->getText('line'),
			'special' => $this->getLang()->getText('special'),
			'host' => $this->getLang()->getText('host'),
			'service' => $this->getLang()->getText('service'),
			'hostgroup' => $this->getLang()->getText('hostgroup'),
			'servicegroup' => $this->getLang()->getText('servicegroup'),
			'map' => $this->getLang()->getText('map'),
			'textbox' => $this->getLang()->getText('textbox'),
			'shape' => $this->getLang()->getText('shape'),
			'manage' => $this->getLang()->getText('manage'),
			'stateless' => $this->getLang()->getText('Stateless'));
		
		return json_encode($langMenu);
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
