<?php
/*****************************************************************************
 *
 * GlobalCore.php - The core of NagVis pages
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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
    protected $selectable_sources = array();

    public $statelessObjectTypes = Array(
        'textbox'   => true,
        'shape'     => true,
        'line'      => true,
        'container' => true,
    );

    public $demoMaps = Array(
        'demo-germany',
        'demo-ham-racks',
        'demo-load',
        'demo-muc-srv1',
        'demo-overview',
        'demo-geomap',
        'demo-automap',
    );

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
     * FIXME: Remove this wrapper
     */
    public static function getMainCfg() {
        global $_MAINCFG;
        return $_MAINCFG;
    }

    /**
     * Getter function to initialize the user maincfg instance
     * Only needed in some rare cases when only the values from
     * the user (web) controllable nagvis.ini.php file are needed
     */
    public static function getUserMainCfg() {
        global $_UMAINCFG;
        if(!isset($_UMAINCFG)) {
            $_UMAINCFG = new GlobalMainCfg();
            $_UMAINCFG->setConfigFiles(Array(CONST_MAINCFG));
            $_UMAINCFG->init(True, '-user-only');
        }
        return $_UMAINCFG;
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
            if($printErr)
                throw new NagVisException(l('gdLibNotFound'));
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
    public function getDefinedBackends($onlyUserCfg = false) {
        if($onlyUserCfg) {
            $MAINCFG = self::getUserMainCfg();
        } else {
            $MAINCFG = self::getMainCfg();
        }
        $ret = Array();
        foreach($MAINCFG->getSections() AS $name) {
            if(preg_match('/^backend_/i', $name)) {
                $ret[] = $MAINCFG->getValue($name, 'backendid');
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
     * Only returns rotations the users is permitted for
     */
    public function getPermittedRotationPools() {
        global $AUTHORISATION;
        $list = array();
        foreach($this->getDefinedRotationPools() AS $poolName) {
            if($AUTHORISATION->isPermitted('Rotation', 'view', $poolName)) {
                $list[$poolName] = $poolName;
            }
        }
        return $list;
    }

    /**
     * Gets all available custom actions
     */
    public function getDefinedCustomActions() {
        $ret = Array();
        foreach(self::getMainCfg()->getSections() AS $name) {
            if(preg_match('/^action_/i', $name)) {
                $id = self::getMainCfg()->getValue($name, 'action_id');
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
     * Returns languages of all available documentations
     *
     * @return	Array list
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableDocs() {
        return self::listDirectory(self::getMainCfg()->getValue('paths', 'doc'), MATCH_DOC_DIR);
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
          self::listDirectory(path('sys', 'global', 'templates'), MATCH_HOVER_TEMPLATE_FILE),
          self::listDirectory(path('sys', 'local',  'templates'), MATCH_HOVER_TEMPLATE_FILE, null, null, null, null, false)
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
          self::listDirectory(path('sys', 'global', 'templates'), MATCH_HEADER_TEMPLATE_FILE),
          self::listDirectory(path('sys', 'local',  'templates'), MATCH_HEADER_TEMPLATE_FILE, null, null, null, null, false)
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
          self::listDirectory(path('sys', 'global', 'templates'), MATCH_CONTEXT_TEMPLATE_FILE),
          self::listDirectory(path('sys', 'local',  'templates'), MATCH_CONTEXT_TEMPLATE_FILE, null, null, null, null, false)
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
          self::listDirectory(path('sys', 'global', 'shapes'), MATCH_PNG_GIF_JPG_FILE, null, null, 0),
          self::listDirectory(path('sys', 'local',  'shapes'), MATCH_PNG_GIF_JPG_FILE, null, null, 0, null, false)
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
          self::listDirectory(path('sys', 'global', 'icons'), MATCH_ICONSET),
          self::listDirectory(path('sys', 'local',  'icons'), MATCH_ICONSET, null, null, null, null, false)
        );
    }

    /**
     * Reads all available sources
     *
     * @return	Array hover templates
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableSources() {
        return array_merge(
          self::listDirectory(path('sys', 'global', 'sources'), MATCH_SOURCE_FILE),
          self::listDirectory(path('sys', 'local',  'sources'), MATCH_SOURCE_FILE, null, null, null, null, false)
        );
    }

    /**
     * Returns the list of available custom action files
     */
    public function getAvailableCustomActions() {
        return array_merge(
          self::listDirectory(path('sys', 'global', 'actions'), MATCH_PHP_FILE),
          self::listDirectory(path('sys', 'local',  'actions'), MATCH_PHP_FILE, null, null, null, null, false)
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
            foreach(Array(path('sys', 'local',  'icons'),
                          path('sys', 'global', 'icons')) AS $path)
                if(file_exists($path))
                    foreach(Array('png', 'gif', 'jpg') AS $ext)
                        if(file_exists($path . $iconset . '_ok.'.$ext))
                            return $ext;

        // Catch error when iconset filetype could not be fetched
        if($type === '')
            throw new NagVisException(l('iconsetFiletypeUnknown', Array('ICONSET' => $iconset)));

        $this->iconsetTypeCache[$iconset] = $type;
        return $type;
    }

    /**
     * Reads all source files for the geomap in the specified path
     */
    public function getAvailableGeomapSourceFiles($strMatch = null, $setKey = null) {
        return self::listDirectory(self::getMainCfg()->getValue('paths', 'geomap'), MATCH_CSV_FILE, null, $strMatch, null, $setKey);
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

    public function getPermittedMaps() {
        global $AUTHORISATION;

        $list = array();
        foreach ($this->getAvailableMaps() AS $mapName) {
            // Check if the user is permitted to view this
            if(!$AUTHORISATION->isPermitted('Map', 'view', $mapName))
                continue;

            // If the parameter filterUser is set, filter the maps by the username
            // given in this parameter. This is a mechanism to be authed as generic
            // user but see the maps of another user. This feature is disabled by
            // default but could be enabled if you need it.
            if(cfg('global', 'user_filtering') && isset($_GET['filterUser']) && $_GET['filterUser'] != '') {
                $AUTHORISATION2 = new CoreAuthorisationHandler();
                $AUTHORISATION2->parsePermissions($_GET['filterUser']);
                if(!$AUTHORISATION2->isPermitted('Map', 'view', $mapName))
                    continue;

                // Switch the auth cookie to this user
                global $SHANDLER;
                $SHANDLER->aquire();
                $SHANDLER->set('authCredentials', array('user' => $_GET['filterUser'], 'password' => ''));
                $SHANDLER->set('authTrusted',     true);
                $SHANDLER->commit();
            }

            $list[$mapName] = $mapName;
        }
        return $list;
    }

    public function getListMaps() {
        $list = array();
        $maps = $this->getPermittedMaps();
        foreach ($maps AS $mapName) {
            $MAPCFG = new GlobalMapCfg($mapName);
            $MAPCFG->checkMapConfigExists(true);
            try {
                $MAPCFG->readMapConfig(ONLY_GLOBAL);
            } catch(MapCfgInvalid $e) {
                continue; // skip configs with broken global sections
            } catch(NagVisException $e) {
                continue; // skip e.g. not read config files
            }
            
            if($MAPCFG->getValue(0, 'show_in_lists') == 1)
                $list[$mapName] = $MAPCFG->getAlias();
        }
        natcasesort($list);
        return array_keys($list);
    }

    /**
     * Reads all map images in map path
     *
     * @return	Array map images
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAvailableBackgroundImages() {
        return array_merge(
          self::listDirectory(path('sys', 'global', 'backgrounds'), MATCH_PNG_GIF_JPG_FILE, null, null, 0),
          self::listDirectory(path('sys', 'local',  'backgrounds'), MATCH_PNG_GIF_JPG_FILE, null, null, 0, null, false)
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
            self::listDirectory(path('sys', 'global', 'gadgets'), MATCH_PHP_FILE, Array('gadgets_core.php' => true), null, null, null, true),
            self::listDirectory(path('sys', 'local',  'gadgets'), MATCH_PHP_FILE, Array('gadgets_core.php' => true), null, null, null, false)
        );
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
    public function listDirectory($dir, $allowRegex = null, $ignoreList = null, $allowPartRegex = null, $returnPart = null, $setKey = null, $printErr = true) {
        $files = Array();

        if($returnPart === null)
            $returnPart = 1;
        if($setKey === null)
            $setKey = false;

        if(!self::checkExisting($dir, $printErr) || !self::checkReadable($dir, $printErr))
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
            throw new NagVisException(l('The path "[PATH]" does not exist.', Array('PATH' => $path)));

        return false;
    }

    public function checkReadable($path, $printErr = true) {
        if($path != '' && is_readable($path))
            return true;

        if($printErr) {
            throw new NagVisException(l('The path "[PATH]" is not readable.', Array('PATH' => $path)));
        }

        return false;
    }
    public function checkWriteable($path, $printErr = true) {
        if($path != '' && is_writeable($path))
            return true;

        if($printErr)
            throw new NagVisException(l('The path "[PATH]" is not writeable.', Array('PATH' => $path)));

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
        if(count($parts) == 2) {
            // e.g. 1.6   -> 106060
            // e.g. 1.5   -> 105060
            array_push($parts, '0');
            array_push($parts, '60');
        }
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
        switch($id) {
            case 1:  return l('File is too large (PHP limit)');
            case 2:  return l('File is too large (FORM limit)');
            case 3:  return l('Upload incomplete');
            case 4:  return l('No file uploaded');
            case 6:  return l('Missing a temporary folder');
            case 7:  return l('Failed to write file to disk');
            case 8:  return l('File upload stopped by extension');
            default: return l('Unhandled error');
        }
    }

    /**
     * Parses the needed language strings to javascript
     * Used for the edit code which was moved from the WUI
     *
     * @return  String    JSON encoded array
     * @author  Lars Michelsen <lars@vertical-visions.de>
     * FIXME: Remove this when the edit dialogs are rewritten to
     * a validation within the PHP code and do not need the js
     * validation anymore.
     */
    public function getJsLang() {
        $lang = Array(
            'wrongValueFormatOption'        => l('wrongValueFormatOption'),
            'mustValueNotSet'               => l('mustValueNotSet'),
            'firstMustChoosePngImage'       => l('firstMustChoosePngImage'),
            'noSpaceAllowedInName'          => l('Spaces are not allowed in file names.'),
            'mustChooseValidImageFormat'    => l('mustChooseValidImageFormat'),
            'foundNoBackgroundToDelete'     => l('foundNoBackgroundToDelete'),
            'confirmBackgroundDeletion'     => l('confirmBackgroundDeletion'),
            'unableToDeleteBackground'      => l('unableToDeleteBackground'),
            'mustValueNotSet1'              => l('mustValueNotSet1'),
            'foundNoShapeToDelete'          => l('foundNoShapeToDelete'),
            'shapeInUse'                    => l('shapeInUse'),
            'confirmShapeDeletion'          => l('confirmShapeDeletion'),
            'unableToDeleteShape'           => l('unableToDeleteShape'),
            'properties'                    => l('properties'),
        );

        return json_encode($lang);
    }

    // Sort array of map arrays by alias
    static function cmpAlias($a, $b) {
        return strnatcasecmp($a['alias'], $b['alias']);
    }

    // Sort array of map arrays by alias used for header menu
    static function cmpMapAlias($a, $b) {
        return strnatcasecmp($a['mapAlias'], $b['mapAlias']);
    }

    public function omdSite() {
        if(substr($_SERVER["SCRIPT_FILENAME"], 0, 4) == '/omd') {
            $site_parts = array_slice(explode('/' ,dirname($_SERVER["SCRIPT_FILENAME"])), 0, -3);
            return $site_parts[count($site_parts) - 1];
        }
        return null;
    }

    public function addSelectableSource($source_name) {
        $this->selectable_sources[$source_name] = $source_name;
    }

    public function getSelectableSources() {
        return $this->selectable_sources;
    }
}
?>
