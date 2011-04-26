<?php
/*****************************************************************************
 *
 * GlobalCore.php - The core of NagVis pages
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
 * class GlobalCore
 *
 * @author  Lars Michelsen <lars@vertical-visions.de
 */
class GlobalCore {
    protected static $MAINCFG = null;
    protected static $LANG = null;
    protected static $AUTHENTICATION = null;
    protected static $AUTHORIZATION = null;

    private static $instance = null;
    protected $iconsetTypeCache = Array();

    /**
     * Deny construct
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    private function __construct() {}

    /**
     * Deny clone
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    private function __clone() {}

    /**
     * Getter function to initialize MAINCFG
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public static function getMainCfg() {
        // Initialize main configuration when not set yet
        if(self::$MAINCFG === null) {
            if(defined('CONST_MAINCFG_SITE'))
                self::$MAINCFG = new GlobalMainCfg(Array(CONST_MAINCFG_SITE, CONST_MAINCFG));
            else
                self::$MAINCFG = new GlobalMainCfg(Array(CONST_MAINCFG));

            self::$MAINCFG->init();
        }

        return self::$MAINCFG;
    }

    /**
     * Getter function to initialize LANG
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public static function getLang() {
        if(self::$LANG === null) {
            // Initialize language when not set yet
            self::$LANG = new GlobalLanguage();
        }

        return self::$LANG;
    }

    /**
     * Setter for AA
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public static function setAA(CoreAuthHandler $A1, CoreAuthorisationHandler $A2 = null) {
        self::$AUTHENTICATION = $A1;
        self::$AUTHORIZATION = $A2;
    }

    /**
     * Getter function for AUTHORIZATION
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public static function getAuthorization() {
        return self::$AUTHORIZATION;
    }

    /**
     * Getter function for AUTHENTICATION
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public static function getAuthentication() {
        return self::$AUTHENTICATION;
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

    /* Here are some methods defined which get used all over NagVis and have
     * no other special place where they could be located */

    /**
     * Check if GD-Libs installed, when GD-Libs are enabled
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkGd($printErr) {
    if(!extension_loaded('gd')) {
        if($printErr) {
            new GlobalMessage('WARNING', self::getLang()->getText('gdLibNotFound'));
        }
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Reads all defined Backend-ids from the main configuration
     *
     * @return	Array Backend-IDs
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getDefinedBackends() {
        $ret = Array();
        foreach(self::getMainCfg()->getSections() AS $name) {
            if(preg_match('/^backend_/i', $name)) {
                $ret[] = self::getMainCfg()->getValue($name, 'backendid');
            }
        }

        return $ret;
    }

    /**
     * Gets all rotation pools
     *
     * @return	Array pools
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public function getDefinedRotationPools() {
        $ret = Array();
        foreach(self::getMainCfg()->getSections() AS $name) {
            if(preg_match('/^rotation_/i', $name)) {
                $id = self::getMainCfg()->getValue($name, 'rotationid');
                $ret[$id] = $id;
            }
        }

        return $ret;
    }

    /**
     * Reads all languages which are available in NagVis and
     * are enabled by the configuration
     *
     * @return	Array list
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableAndEnabledLanguages() {
        $aRet = Array();

        foreach($this->getAvailableLanguages() AS $val) {
            if(in_array($val, self::getMainCfg()->getValue('global', 'language_available'))) {
                $aRet[] = $val;
            }
        }

        return $aRet;
    }

    /**
     * Reads all languages
     *
     * @return	Array list
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableLanguages() {
        return self::listDirectory(self::getMainCfg()->getValue('paths', 'language'), MATCH_LANGUAGE_FILE);
    }

    /**
     * Reads all available backends
     *
     * @return	Array Html
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableBackends() {
        return self::listDirectory(self::getMainCfg()->getValue('paths', 'class'), MATCH_BACKEND_FILE);
    }

    /**
     * Reads all hover templates
     *
     * @return	Array hover templates
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableHoverTemplates() {
        return array_merge(
          self::listDirectory(self::getMainCfg()->getPath('sys', 'global', 'templates'), MATCH_HOVER_TEMPLATE_FILE),
          self::listDirectory(self::getMainCfg()->getPath('sys', 'local',  'templates'), MATCH_HOVER_TEMPLATE_FILE, null, null, null, null, false)
        );
    }

    /**
     * Reads all header templates
     *
     * @return	Array list
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableHeaderTemplates() {
        return array_merge(
          self::listDirectory(self::getMainCfg()->getPath('sys', 'global', 'templates'), MATCH_HEADER_TEMPLATE_FILE),
          self::listDirectory(self::getMainCfg()->getPath('sys', 'local',  'templates'), MATCH_HEADER_TEMPLATE_FILE, null, null, null, null, false)
        );
    }

    /**
     * Reads all header templates
     *
     * @return	Array list
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableContextTemplates() {
        return array_merge(
          self::listDirectory(self::getMainCfg()->getPath('sys', 'global', 'templates'), MATCH_CONTEXT_TEMPLATE_FILE),
          self::listDirectory(self::getMainCfg()->getPath('sys', 'local',  'templates'), MATCH_CONTEXT_TEMPLATE_FILE, null, null, null, null, false)
        );
    }

    /**
     * Reads all PNG images in shape path
     *
     * @return	Array Shapes
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableShapes() {
        return array_merge(
          self::listDirectory(self::getMainCfg()->getPath('sys', 'global', 'shapes'), MATCH_PNG_GIF_JPG_FILE, null, null, 0),
          self::listDirectory(self::getMainCfg()->getPath('sys', 'local',  'shapes'), MATCH_PNG_GIF_JPG_FILE, null, null, 0, null, false)
        );
    }

    /**
     * Reads all iconsets in icon path
     *
     * @return	Array iconsets
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableIconsets() {
        return array_merge(
          self::listDirectory(self::getMainCfg()->getPath('sys', 'global', 'icons'), MATCH_ICONSET),
          self::listDirectory(self::getMainCfg()->getPath('sys', 'local',  'icons'), MATCH_ICONSET, null, null, null, null, false)
        );
    }

    /**
     * Returns the filetype of an iconset
     *
     * @param   String  Iconset name
     * @return	String  Iconset file type
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getIconsetFiletype($iconset) {
        $type = '';

        if(isset($this->iconsetTypeCache[$iconset]))
            $type = $this->iconsetTypeCache[$iconset];
        else
            foreach(Array(self::getMainCfg()->getPath('sys', 'local',  'icons'),
                          self::getMainCfg()->getPath('sys', 'global', 'icons')) AS $path)
                if(file_exists($path))
                    foreach(Array('png', 'gif', 'jpg') AS $ext)
                        if(file_exists($path . $iconset . '_ok.'.$ext))
                            return $ext;

        // Catch error when iconset filetype could not be fetched
        if($type === '')
            new GlobalMessage('ERROR', self::getLang()->getText('iconsetFiletypeUnknown', Array('ICONSET' => $iconset)));

        $this->iconsetTypeCache[$iconset] = $type;
        return $type;
    }

    /**
     * Reads all automaps in automapcfg path
     *
     * @param   String  Regex to match the map name
     * @return	Array   Array of maps
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableAutomaps($strMatch = null, $setKey = null) {
        return self::listDirectory(self::getMainCfg()->getValue('paths', 'automapcfg'), MATCH_CFG_FILE, null, $strMatch, null, $setKey);
    }

    /**
     * Reads all maps in mapcfg path
     *
     * @param   String  Regex to match the map name
     * @return	Array   Array of maps
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableMaps($strMatch = null, $setKey = null) {
        return self::listDirectory(self::getMainCfg()->getValue('paths', 'mapcfg'), MATCH_CFG_FILE, null, $strMatch, null, $setKey);
    }

    /**
     * Reads all map images in map path
     *
     * @return	Array map images
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableBackgroundImages() {
        return array_merge(
          self::listDirectory(self::getMainCfg()->getPath('sys', 'global', 'backgrounds'), MATCH_PNG_GIF_JPG_FILE, null, null, 0),
          self::listDirectory(self::getMainCfg()->getPath('sys', 'local',  'backgrounds'), MATCH_PNG_GIF_JPG_FILE, null, null, 0, null, false)
        );
    }

    /**
     * Reads all available gadgets
     *
     * @return	Array   Array of gadgets
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableGadgets() {
        return array_merge(
            self::listDirectory(self::getMainCfg()->getPath('sys', 'global', 'gadgets'), MATCH_PHP_FILE, Array('gadgets_core.php' => true), null, null, null, true),
            self::listDirectory(self::getMainCfg()->getPath('sys', 'local',  'gadgets'), MATCH_PHP_FILE, Array('gadgets_core.php' => true), null, null, null, false)
        );
    }

    /**
     * This method checks if the given map is a automap
     * This is quite hackish but have no better option at the moment
     *
     * @param   String      Name of the map
     * @return	Boolean		Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkMapIsAutomap($sMap) {
        return in_array($sMap, $this->getAvailableAutomaps());
    }

    /**
   * Lists the contents of a directory with some checking, filtering and sorting
     *
     * @param   String  Path to the directory
     * @param   String  Regex the file(s) need to match
     * @param   Array   Lists of filenames to ignore (The filenames need to be the keys)
     * @param   Integer Match part to be returned
     * @param   Boolean Print errors when dir does not exist
     * @return	Array   Sorted list of file names/parts in this directory
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function listDirectory($dir, $allowRegex = null, $ignoreList = null, $allowPartRegex = null, $returnPart = null, $setKey = null, $printErr = true) {
        $files = Array();

    if($returnPart === null)
            $returnPart = 1;
    if($setKey === null)
            $setKey = false;

    if(!self::checkExisting($dir, $printErr))
            return $files;

        if($handle = opendir($dir)) {
 			while (false !== ($file = readdir($handle))) {
                if($allowRegex && !preg_match($allowRegex, $file, $arrRet))
                    continue;
                if($ignoreList && isset($ignoreList[$file]))
                    continue;
        if($allowPartRegex && !preg_match($allowPartRegex, $arrRet[1]))
                    continue;

                if($setKey)
                    $files[$arrRet[$returnPart]] = $arrRet[$returnPart];
                else
                    $files[] = $arrRet[$returnPart];
      }

            if($files)
                natcasesort($files);

            closedir($handle);
        }

        return $files;

    }

    public function checkExisting($path, $printErr = true) {
        if($path != '' && file_exists($path))
            return true;

        if($printErr)
            new GlobalMessage('ERROR', self::getLang()->getText('The path "[PATH]" does not exist.', Array('PATH' => $path)));

        return false;
    }

    public function checkReadable($path, $printErr = true) {
        if($path != '' && is_readable($path))
            return true;

        if($printErr)
            new GlobalMessage('ERROR', self::getLang()->getText('The path "[PATH]" is not readable.', Array('PATH' => $path)));

        return false;
    }
    public function checkWriteable($path, $printErr = true) {
        if($path != '' && is_writeable($path))
            return true;

        if($printErr)
            new GlobalMessage('ERROR', self::getLang()->getText('The path "[PATH]" is not writeable.', Array('PATH' => $path)));

        return false;
    }

    /**
     * Checks for existing var folder
     *
     * @param		Boolean 	$printErr
     * @return	Boolean		Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkVarFolderExists($printErr) {
        return $this->checkExisting(substr(self::getMainCfg()->getValue('paths', 'var'),0,-1), $printErr);
    }

    /**
     * Checks for writeable VarFolder
     *
     * @param		Boolean 	$printErr
     * @return	Boolean		Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkVarFolderWriteable($printErr) {
        return $this->checkWriteable(substr(self::getMainCfg()->getValue('paths', 'var'),0,-1), $printErr);
    }

    /**
     * Checks for existing shared var folder
     *
     * @param		Boolean 	$printErr
     * @return	Boolean		Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkSharedVarFolderExists($printErr) {
        return $this->checkExisting(substr(self::getMainCfg()->getValue('paths', 'sharedvar'),0,-1), $printErr);
    }

    /**
     * Checks for writeable shared var folder
     *
     * @param		Boolean 	$printErr
     * @return	Boolean		Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkSharedVarFolderWriteable($printErr) {
        return $this->checkWriteable(substr(self::getMainCfg()->getValue('paths', 'sharedvar'),0,-1), $printErr);
    }

    /**
     * Tries to set correct permissions on files
     * Works completely silent - no error messages here
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setPerms($file) {
        try {
            $group = self::getMainCfg()->getValue('global', 'file_group');
            $old = error_reporting(0);
            if($group !== '')
                chgrp($file, $group);
            chmod($file, octdec(self::getMainCfg()->getValue('global', 'file_mode')));
            error_reporting($old);
        } catch(Exception $e) {
            error_reporting($old);
        }
        return true;
    }

    /**
     * Transforms a NagVis version to integer which can be used
     * for comparing different versions.
     *
     * @param	  String    Version string
     * @return  Integer   Version as integer
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function versionToTag($s) {
        $s = str_replace('a', '.0.0', str_replace('b', '.0.2', str_replace('rc', '.0.4', $s)));
        $parts = explode('.', $s);
        $tag = '';
        foreach($parts AS $part)
            $tag .= sprintf("%02s", $part);
        return (int) sprintf("%-08s", $tag);
    }

    /**
     * Returns the human readable upload error message
     * matching the given error code.
     *
     * @param	  Integer   Error code from $_FILE
     * @return  String    The error message
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getUploadErrorMsg($id) {
        $LANG = self::getLang();
        switch($id) {
            case 1:  return $LANG->getText('File is too large (PHP limit)');
            case 2:  return $LANG->getText('File is too large (FORM limit)');
            case 3:  return $LANG->getText('Upload incomplete');
            case 4:  return $LANG->getText('No file uploaded');
            case 6:  return $LANG->getText('Missing a temporary folder');
            case 7:  return $LANG->getText('Failed to write file to disk');
            case 8:  return $LANG->getText('File upload stopped by extension');
            default: return $LANG->getText('Unhandled error');
        }
    }

    /**
     * Parses the needed language strings to javascript
     * Used for the edit code which was moved from the WUI
     *
     * @return  String    JSON encoded array
     * @author  Lars Michelsen <lars@vertical-visions.de>
     * FIXME: DEPRECATED? Check if still needed!
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
     * Loads and parses permissions of all maps in js array
     *
     * @return  String  JSON encoded array
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     * FIXME: Only used by ex WUI components - check if needed!
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
            $aOpts['mapAlias'] = $MAPCFG1->getValue(0, 'alias');

            // used image
            $aOpts['mapImage'] = $MAPCFG1->getValue(0, 'map_image');

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
}
?>