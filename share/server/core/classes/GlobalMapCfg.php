<?php
/*****************************************************************************
 *
 * GlobalMapCfg.php - Class for handling the map configuration files of NagVis
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
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalMapCfg {
    private $CORE;
    public $BACKGROUND;
    private $CACHE;
    private $DCACHE;

    protected $name;
    protected $type = 'map';
    protected $mapConfig = Array();
    protected $typeDefaults = Array();

    private $configFile = '';
    private $configFileContents = null;
    protected $cacheFile = '';
    protected $defaultsCacheFile = '';
    protected $mapLockPath;

    // Array for config validation
    protected static $validConfig = null;

    /**
     * Class Constructor
     */
    public function __construct($CORE, $name = '') {
        $this->CORE = $CORE;
        $this->name = $name;

        if(self::$validConfig == null)
            $this->fetchValidConfig();

        $this->mapLockPath = cfg('paths', 'mapcfg').$this->name.'.lock';

        // Define the map configuration file when no one set until here
        if($this->configFile === '')
            $this->setConfigFile(cfg('paths', 'mapcfg').$name.'.cfg');

        if($this->cacheFile === '') {
            $this->cacheFile = cfg('paths','var').$this->type.'-'.$name.'.cfg-'.CONST_VERSION.'-cache';
            $this->defaultsCacheFile = $this->cacheFile.'-defs';
        }

        // Initialize the map configuration cache
        $this->initCache();
    }

    /**
     * Loads the valid configuration definitions from the mapcfg files.
     * Those files define the options which are available for the different
     * object types. The Infos fetched from these files contain all data about
     * the options like variable format, validation regexes and so on.
     */
    private function fetchValidConfig() {
        self::$validConfig = Array();
        // Holds all registered config vars
        $mapConfigVars   = Array();
        // Maps the config vars to the object types
        $mapConfigVarMap = Array();

        $path = cfg('paths', 'server') . '/mapcfg';
        $files = $this->CORE->listDirectory($path, MATCH_PHP_FILE);
        foreach($files AS $f) {
            include_once(realpath($path . '/' . $f));
        }

        // Now register the variables for the objec types
        foreach($mapConfigVarMap AS $type => $vars) {
            self::$validConfig[$type] = Array();
            foreach($vars AS $var => $alias) {
                if($alias === null)
                    $alias = $var;
                self::$validConfig[$type][$alias] = $mapConfigVars[$var];
            }
        }
    }

    /**
     * Gets the default values for the different object types
     *
     * @param   Boolean  Only fetch global type settings
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function gatherTypeDefaults($onlyGlobal) {
        if($onlyGlobal)
            $types = Array('global');
        else
            $types = array_keys(self::$validConfig);

        // Extract defaults from valid config array
        foreach($types AS $type) {
            if(!isset($this->typeDefaults[$type]))
                $this->typeDefaults[$type] = Array();

            foreach(array_keys(self::$validConfig[$type]) AS $key) {
                if(isset(self::$validConfig[$type][$key]['default']))
                    $this->typeDefaults[$type][$key] = self::$validConfig[$type][$key]['default'];
            }
        }

        // Treating the alias
        $this->typeDefaults['global']['alias'] = $this->name;

        if($onlyGlobal)
            return true;

        // And now feed the typeDefaults array with the new
        // default options based on the global section of the current map
        $aVars = Array('recognize_services',
            'only_hard_states',
            'backend_id',
            'iconset',
            'exclude_members',
            'exclude_member_states',
            'line_type',
            'line_width',
            'line_arrow',
            'line_weather_colors',
            'context_menu',
            'context_template',
            'hover_menu',
            'hover_template',
            'hover_timeout',
            'hover_delay',
            'hover_url',
            'label_show',
            'label_text',
            'label_x',
            'label_y',
            'label_width',
            'label_background',
            'label_border',
            'label_style',
            'url_target',
            'hover_childs_show',
            'hover_childs_sort',
            'hover_childs_order',
            'hover_childs_limit');
        foreach($aVars As $sVar) {
            $sTmp = $this->getValue(0, $sVar);

            $this->typeDefaults['host'][$sVar] = $sTmp;
            $this->typeDefaults['hostgroup'][$sVar] = $sTmp;

            // Handle exceptions for servicegroups
            if($sVar != 'recognize_services') {
                $this->typeDefaults['servicegroup'][$sVar] = $sTmp;
            }

            // Handle exceptions for services
            if($sVar != 'recognize_services' && $sVar != 'label_text') {
                $this->typeDefaults['service'][$sVar] = $sTmp;
            }

            // Handle exceptions for maps
            if($sVar != 'recognize_services' && $sVar != 'backend_id') {
                $this->typeDefaults['map'][$sVar] = $sTmp;
            }

            // Handle exceptions for shapes
            if($sVar == 'url_target' || $sVar == 'hover_delay') {
                $this->typeDefaults['shape'][$sVar] = $sTmp;
            }

            // Handle exceptions for lines
            if($sVar == 'url_target' || $sVar == 'hover_delay') {
                $this->typeDefaults['line'][$sVar] = $sTmp;
            }
        }
    }

    /**
     * Initializes the map configuration file caching
     *
     * @param   String   Path to the configuration file
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function setConfigFile($file) {
        $this->configFile = $file;
    }

    /**
     * Initializes the map configuration file caching
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function initCache() {
        if($this->cacheFile !== '') {
            $this->CACHE = new GlobalFileCache($this->configFile, $this->cacheFile);
            $this->DCACHE = new GlobalFileCache($this->configFile, $this->defaultsCacheFile);
        }
    }

    /**
     * Initializes the background image
     *
     * @return	GlobalBackground
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function getBackground() {
        $RET = new GlobalBackground($this->CORE, $this->getValue(0, 'map_image'));
        return $RET;
    }

    /**
     * Creates a new Configfile
     *
     * @return	Boolean	Is Successful?
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public function createMapConfig() {
        // does file exist?
        if($this->checkMapConfigReadable(false))
            return false;

        if(!$this->checkMapCfgFolderWriteable(true))
            return false;

        // create empty file
        fclose(fopen($this->configFile, 'w'));
        $this->CORE->setPerms($this->configFile);
        return true;
    }

    /**
     * Reads the map config file (copied from readFile->readNagVisCfg())
     *
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function readMapConfig($onlyGlobal = 0, $resolveTemplates = true, $useCache = true) {
        if($this->name == '')
            return false;

        // Only use cache when there is
        // a) The cache should be used
        // b) When whole config file should be read
        // c) Some valid cache file
        // d) Some valid main configuration cache file
        // e) This cache file newer than main configuration cache file
        if($onlyGlobal == 0
           && $useCache === true
           && $this->CACHE->isCached() !== -1
             && $this->CORE->getMainCfg()->isCached() !== -1
             && $this->CACHE->isCached() >= $this->CORE->getMainCfg()->isCached()) {
            $this->mapConfig = $this->CACHE->getCache();
            $this->typeDefaults = $this->DCACHE->getCache();
            // Cache objects are not needed anymore
            $this->CACHE = null;
            $this->DCACHE = null;

            $this->BACKGROUND = $this->getBackground();

            return TRUE;
        } else {
            if(!$this->checkMapConfigExists(TRUE) || !$this->checkMapConfigReadable(TRUE))
                return false;

            // Read file in array (Don't read empty lines and ignore new line chars)
            //$file = file($this->configFile, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
            // Calling file() with "FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES" caused strange
            // problems with PHP 5.1.2 while testing with e.g. SLES10SP1. So added that workaround
            // here.
            if(version_compare(PHP_VERSION, '5.1.2', '==')) {
                $file = file($this->configFile);
            } else { 
                $file = file($this->configFile, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
            }

            // Create an array for these options
            $createArray = Array('use' => 1);

            // Don't read these keys
            $ignoreKeys = Array('type' => 0);

            $l = 0;

            // These variables set which object is currently being filled
            $sObjType = '';
            $iObjId = 0;
            $obj = Array();

            // Loop each line
            $iNumLines = count($file);
            $unknownObject = null;
            for($l = 0; $l < $iNumLines; $l++) {
                // Remove spaces, newlines, tabs, etc. (http://de.php.net/rtrim)
                $file[$l] = rtrim($file[$l]);

                // Don't recognize empty lines
                if($file[$l] == '')
                    continue;

                // Fix ISO-8859-1 encoding. Convert to UTF-8. The mbstring extension might
                // be misssing. Simply skip this in that case.
                if(function_exists('mb_detect_encoding')
                   && mb_detect_encoding($file[$l], 'UTF-8, ISO-8859-1') == 'ISO-8859-1')
                    $file[$l] = utf8_encode($file[$l]);

                // Don't recognize comments and empty lines, do nothing with ending delimiters
                $sFirstChar = substr(ltrim($file[$l]), 0, 1);
                if($sFirstChar == ';' || $sFirstChar == '#')
                    continue;

                // This is an object ending. Reset the object type and skip to next line
                if($sFirstChar == '}') {
                    if($obj['type'] === 'global')
                        $id = 0;
                    else
                        $id = isset($obj['object_id']) ? $obj['object_id'] : '_'.$iObjId;

                    // It might happen that there is a duplicate object on the map
                    // This generates a new object_id for the later objects
                    if(isset($this->mapConfig[$id])) {
                        $new = $id;
                        while(isset($this->mapConfig[$new]))
                            $new = $this->genObjId($new . time());
                        $obj['object_id']      = $new;
                        $this->mapConfig[$new] = $obj;
                        $this->storeDeleteElement('_'.$iObjId, $this->formatElement($new));
                    } else {
                        $this->mapConfig[$id] = $obj;
                    }

                    $sObjType = '';

                    // Increase the map object id to identify the object on the map
                    $iObjId++;

                    // If only the global section should be read break the loop after the global section
                    if($onlyGlobal == 1 && isset($this->mapConfig[0]))
                        break;
                    else
                        continue;
                }

                // Determine if this is a new object definition
                if(strpos($file[$l], 'define') !== FALSE) {
                    $sObjType = substr($file[$l], 7, (strpos($file[$l], '{', 8) - 8));
                    if(!isset($sObjType) || !isset(self::$validConfig[$sObjType])) {
                        throw new NagVisException(l('unknownObject',
                                                    Array('TYPE'    => $sObjType,
                                                          'MAPNAME' => $this->name)));
                    }

                    // This is a new definition and it's a valid one
                    $obj = Array(
                      'type' => $sObjType,
                    );

                    continue;
                }

                // This is another attribute. But it is only ok to proceed here when
                // there is an open object
                if($sObjType === '') {
                    throw new NagVisException(l('Attribute definition out of object. In map [MAPNAME] at line #[LINE].',
                                              Array('MAPNAME' => $this->name, 'LINE' => $l+1)));
                }

                $iDelimPos = strpos($file[$l], '=');
                $sKey = trim(substr($file[$l],0,$iDelimPos));
                $sValue = trim(substr($file[$l],($iDelimPos+1)));

                if(isset($ignoreKeys[$sKey]))
                    continue;

                if(isset($createArray[$sKey]))
                    $obj[$sKey] = explode(',', $sValue);
                else
                    $obj[$sKey] = $sValue;
            }

            // Gather the default values for the object types
            $this->gatherTypeDefaults($onlyGlobal);

            if($onlyGlobal == 0) {
                if($resolveTemplates == true) {
                    // Merge the objects with the linked templates
                    $this->mergeTemplates();
                }
            }

            // unknown object type found on map
            if($unknownObject)
                throw new MapCfgInvalid($unknownObject);

            try {
                $this->checkMapConfigIsValid();
                $this->BACKGROUND = $this->getBackground();
            } catch(MapCfgInvalid $e) {
                $this->BACKGROUND = $this->getBackground();
                throw $e;
            }

            if($onlyGlobal == 0) {
                // Check object id attribute and if there is none generate a new unique
                // object_id on the map for the object
                $this->verifyObjectIds();

                // Build cache
                if($useCache === true) {
                    $this->CACHE->writeCache($this->mapConfig, 1);
                    $this->DCACHE->writeCache($this->typeDefaults, 1);
                    // Cache objects are not needed anymore
                    $this->CACHE = null;
                    $this->DCACHE = null;
                }

                // The automap also uses this method, so handle the different type
                if($this->type === 'automap') {
                    $mod = 'AutoMap';
                } else {
                    $mod = 'Map';
                }

                // Trigger the autorization backend to create new permissions when needed
                $AUTHORIZATION = $this->CORE->getAuthorization();
                if($AUTHORIZATION !== null) {
                    $this->CORE->getAuthorization()->createPermission($mod, $this->getName());
                }
            }

            return TRUE;
        }
    }

    /**
     * Gets the numeric index of a template by the name
     *
     * @param   String   Name of the template
     * @return  Integer  ID of the template
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getTemplateIdByName($name) {
        foreach($this->mapConfig AS $id => $arr) {
            if($arr['type'] !== 'template')
                continue;

            if(isset($arr['name']) && $arr['name'] === $name)
                return $id;
        }

        return false;
    }

    /**
     * Merges the object which "use" a template with the template values
     *
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function mergeTemplates() {
        // Loop all objects
        foreach($this->mapConfig AS $id => $element) {
            // Except global and templates (makes no sense)
            if($id == '0')
                continue;

            // Check for "use" value
            if(isset($element['use']) && is_array($element['use'])) {
                // loop all given templates
                foreach($element['use'] AS $templateName) {
                    $tmpl_id = $this->getTemplateIdByName($templateName);
                    if($tmpl_id === false)
                        continue;

                    if(isset($this->mapConfig[$tmpl_id]) && is_array($this->mapConfig[$tmpl_id])) {
                        // merge object array with template object array (except type and name attribute)
                        $tmpArray = $this->mapConfig[$tmpl_id];
                        unset($tmpArray['type']);
                        unset($tmpArray['name']);
                        unset($tmpArray['object_id']);
                        $this->mapConfig[$id] = array_merge($tmpArray, $element);
                    }
                }
            }
        }

        // Everything is merged: The templates are not relevant anymore
        foreach($this->getDefinitions('template') AS $id => $template) {
            unset($this->mapConfig[$id]);
        }
    }

    /**
     * Checks for existing config file
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkMapConfigExists($printErr) {
        return GlobalCore::getInstance()->checkExisting($this->configFile, $printErr);
    }

    /**
     * PROTECTED  checkMapConfigReadable()
     *
     * Checks for readable config file
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function checkMapConfigReadable($printErr) {
        return GlobalCore::getInstance()->checkReadable($this->configFile, $printErr);
    }

    /**
     * Generates a new object id for an object on the map
     *
     * @return  String  The object ID
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function genObjId($s) {
        return substr(sha1($s), 0, 6);
    }

    /**
     * Verifies that all objects on the map have a valid
     * and unique object id. Objects without valid object
     * IDs will get a new one generated
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function verifyObjectIds() {
        $toBeWritten = Array();
        $alreadySeen = Array();

        foreach(array_keys($this->mapConfig) AS $id) {
            $todo = false;

            // Replace default integer object IDs
            if($id[0] == '_')
                $todo = true;

            // Remove duplicates by generating new IDs for the later objects
            if(isset($alreadySeen[$id])) {
                $todo = true;
            }

            if($todo) {
                $new = $this->genObjId($id);
                while(isset($this->mapConfig[$new]))
                    $new = $this->genObjId($id . time());

                $this->mapConfig[$id]['object_id'] = $new;
                $this->mapConfig[$new] = $this->mapConfig[$id];
                unset($this->mapConfig[$id]);

                $toBeWritten[$new] = $id;
                $aleadySeen[$new] = true;
            }
        }

        // Now write down all the updated objects
        foreach($toBeWritten AS $new => $id)
            $this->storeDeleteElement($id, $this->formatElement($new));
    }

    /**
     * Checks if the config file is valid
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkMapConfigIsValid() {
        foreach($this->mapConfig AS $id => $element) {
            $type = $element['type'];

            // check given objects and attributes
            if($type == 'global')
                $exception = 'MapCfgInvalid';
            else
                $exception = 'MapCfgInvalidObject';

            // loop validConfig for checking: => missing "must" attributes
            foreach(self::$validConfig[$type] AS $key => $val) {
                if(isset($val['must']) && $val['must'] == '1') {
                    if(!isset($element[$key]) || $element[$key] == '') {
                        throw new $exception(l('mapCfgMustValueNotSet',
                                             Array('MAPNAME' => $this->name, 'ATTRIBUTE' => $key,
                                                                       'TYPE'    => $type,       'ID'        => $id)));
                    }
                }
            }

            if($type == 'template')
                continue;

            // loop given elements for checking: => all given attributes valid
            foreach($element AS $key => $val) {
                // check for valid attributes
                if(!isset(self::$validConfig[$type][$key])) {
                    // unknown attribute
                    throw new $exception(l('unknownAttribute', Array('MAPNAME' => $this->name, 'ATTRIBUTE' => $key, 'TYPE' => $type)));
                } elseif(isset(self::$validConfig[$type][$key]['deprecated']) && self::$validConfig[$type][$key]['deprecated'] === true) {
                    // deprecated option
                    throw new $exception(l('mapDeprecatedOption', Array('MAP' => $this->getName(), 'ATTRIBUTE' => $key, 'TYPE' => $type)));
                } else {
                    // The object has a match regex, it can be checked
                    if(isset(self::$validConfig[$type][$key]['match'])) {
                        if(is_array($val)) {
                            // This is an array

                            // Loop and check each element
                            foreach($val AS $key2 => $val2) {
                                if(!preg_match(self::$validConfig[$type][$key]['match'], $val2)) {
                                    // wrong format
                                    throw new $exception(l('wrongValueFormatMap', Array('MAP' => $this->getName(), 'TYPE' => $type, 'ATTRIBUTE' => $key)));
                                }
                            }
                        } else {
                            // This is a string value

                            if(!preg_match(self::$validConfig[$type][$key]['match'],$val)) {
                                // Wrong format
                                throw new $exception(l('wrongValueFormatMap', Array('MAP' => $this->getName(), 'TYPE' => $type, 'ATTRIBUTE' => $key)));
                            }
                        }
                    }

                    // Check if the configured backend is defined in main configuration file
                    // Raise such an exception only when error is found in global section
                    if($type == 'global' && $key == 'backend_id' && !in_array($val, $this->CORE->getDefinedBackends())) {
                        throw new $exception(l('backendNotDefined', Array('BACKENDID' => $val)));
                    }
                }
            }

        }
    }

    /**
     * Finds out if an attribute has dependant attributes
     */
    public function hasDependants($type, $attr) {
        foreach(self::$validConfig[$type] AS $arr)
            if(isset($arr['depends_on']) && $arr['depends_on'] == $attr)
                return true;
        return false;
    }

    /**
     * Gets valid keys for a specific object type
     *
     * @param   String  Specific object type
     * @return  Array   Valid object keys
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getValidTypeKeys($sType) {
        $aRet = Array();
        foreach(self::$validConfig[$sType] AS $key => $arr) {
            if(!isset($arr['deprecated']) || $arr['deprecated'] !== true) {
                $aRet[] = $key;
            }
        }
        return $aRet;
    }

    /**
     * Gets all valid object types
     *
     * @return  Array  Valid object types
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getValidObjectTypes() {
        $aRet = Array();
        foreach($this->typeDefaults AS $key => $arr) {
            $aRet[] = $key;
        }
        return $aRet;
    }

    /**
     * Gets the default configuration on the map for the given type
     *
     * @return  Array  Array of default options
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getTypeDefaults($type) {
        return $this->typeDefaults[$type];
    }

    /**
     * Gets all definitions of type $type
     *
     * @param	String	$type
     * @return	Array	All elements of this type
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getDefinitions($type) {
        // FIXME: Can be replaced?
        $arr = Array();
        foreach($this->mapConfig AS $id => &$elem)
            if($elem['type'] === $type)
                $arr[$id] = $elem;
        return $arr;
    }

    public function getMapObject($objId) {
        if(!isset($this->mapConfig[$objId]))
            return null;
        return $this->mapConfig[$objId];
    }

    public function getMapObjects() {
        return $this->mapConfig;
    }

    /**
     * Gets a list of available templates with optional regex filtering
     * the templates are listed as keys
     *
     * @param   String  Filter regex
     * @return  Array	  List of templates as keys
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getTemplateNames($strMatch = NULL) {
        $a = Array();
        foreach($this->getDefinitions('template') AS $id => $aOpts) {
            if($strMatch == NULL || ($strMatch != NULL && preg_match($strMatch, $aOpts['name']))) {
                $a[$aOpts['name']] = true;
            }
        }

        return $a;
    }

    /**
     * Gets the last modification time of the configuration file
     *
     * @return	Integer Unix timestamp with last modification time
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getFileModificationTime() {
        if($this->checkMapConfigReadable(1)) {
            $time = filemtime($this->configFile);
            return $time;
        } else {
            return FALSE;
        }
    }

    /**
     * Checks for writeable MapCfgFolder
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    function checkMapCfgFolderWriteable($printErr) {
        return GlobalCore::getInstance()->checkReadable(dirname($this->configFile), $printErr);
    }

    private function getConfig() {
        if($this->configFileContents === null)
            $this->configFileContents = file($this->configFile);
        return $this->configFileContents;
    }

    /**
     * Checks if an element with the given id exists
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function objExists($id) {
        return isset($this->mapConfig[$id]);
    }

    /**
     * Sets a config value in the array
     *
     * @param	Integer	$id
     * @param	String	$key
     * @param	String	$value
     * @return	Boolean	TRUE
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setValue($id, $key, $value) {
        $prop = $this->getValidObjectType($this->mapConfig[$id]['type']);
        if(isset($prop['array']) && $prop['array']) {
            $value = explode(',', $value);
        }

        $this->mapConfig[$id][$key] = $value;
        return TRUE;
    }

    /**
     * Gets a config value from the array
     *
     * @param	Integer	$id
     * @param	String	$key
     * @param	Boolean	$ignoreDefault
     * @return	String	Value
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getValue($id, $key, $ignoreDefault = false) {
        if(isset($this->mapConfig[$id]) && isset($this->mapConfig[$id][$key])) {
            return $this->mapConfig[$id][$key];
        } elseif(!$ignoreDefault && isset($this->mapConfig[$id]['type'])) {
            $type = $this->mapConfig[$id]['type'];
            return isset($this->typeDefaults[$type][$key]) ? $this->typeDefaults[$type][$key] : false;
        } else {
            return false;
        }
    }

    /**
     * Gets the mapName
     *
     * @return	String	MapName
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Gets the map alias
     *
     * @return	String	Map alias
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getAlias() {
        return $this->getValue(0, 'alias');
    }

    public function objIdToTypeAndNum($objId) {
        foreach($this->mapConfig AS $type => $objects)
            foreach($objects AS $typeId => $opts)
                if($opts['object_id'] == $objId)
                    return Array($type, $typeId);
        return Array(null, null);
    }

    /**
     * Only selects the wanted objects of the map and removes the others
     *
     * @param   Array of object ids
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function filterMapObjects($objIds) {
        $newConfig =  Array();
        foreach($objIds AS $id)
            if(isset($this->mapConfig[$id]))
                $newConfig[$id] = $this->mapConfig[$id];
        $this->mapConfig = $newConfig;
    }

    /****************************************************************************
     * EDIT STUFF BELOW
     ***************************************************************************/

    /**
     * Formats a map object for the map configuration file
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function formatElement($id) {
        $a = Array();
        $type = $this->mapConfig[$id]['type'];

        $a[] = 'define '.$type." {\n";

        // Templates need a special handling here cause they can have all types
        // of options. So read all keys which are currently set
        if($type !== 'template')
            $keys = $this->getValidTypeKeys($type);
        else
            $keys = array_keys($this->mapConfig[$id]);

        foreach($keys AS $key)
            if($key !== 'type' && isset($this->mapConfig[$id][$key]) && $this->mapConfig[$id][$key] !== '')
                $a[] = $key.'='.$this->mapConfig[$id][$key]."\n";

        $a[] = "}\n";
        $a[] = "\n";

        return $a;
    }

    /**
     * Adds an element of the specified type to the config array
     *
     * @param	Array	$properties
     * @return	Integer	Id of the Element
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function addElement($type, $properties, $perm = false, $id = null) {
        if($id === null)
            $id = $this->genObjId((count($this->mapConfig) + 1) . time());

        $this->mapConfig[$id]              = $properties;
        $this->mapConfig[$id]['object_id'] = $id;
        $this->mapConfig[$id]['type']      = $type;
        if($perm)
            $this->storeAddElement($id);
        return $id;
    }

    /**
     * Adds the given element at the end of the configuration file
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function storeAddElement($id) {
        $f = $this->getConfig();

        if(count($f) > 0 && trim($f[count($f) - 1]) !== '')
            $f[] = "\n";

        $f = array_merge($f, $this->formatElement($id));
        $this->writeConfig($f);
        return true;
    }

    /**
     * Deletes an element of the specified type from the config array
     *
     * @param	Integer	$id
     * @return	Boolean	TRUE
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function deleteElement($id, $perm = false) {
        unset($this->mapConfig[$id]);
        if($perm)
            $this->storeDeleteElement($id);
        return true;
    }

    /**
     * Searches for an element in the configuration file and deletes it if found.
     * The element can be given as object_id which is the new default for object
     * referencing in whole NagVis. Or by object number of the map with a leading
     * "_" sign.
     *
     * @param   Integer $id
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function storeDeleteElement($id, $replaceWith = null) {
        $start = null;
        $inObj = false;
        $end = null;

        if($id[0] === '_')
            list($inObj, $start, $end) = $this->getObjectLinesByNum((int) str_replace('_', '', $id));
        else
            list($inObj, $start, $end) = $this->getObjectLinesById($id);

        if(!$inObj)
            return false;

        $f = $this->getConfig();
        if($replaceWith)
            array_splice($f, $start, $end - $start + 1, $replaceWith);
        else
            array_splice($f, $start, $end - $start + 1);
        $this->writeConfig($f);
    }

    /**
     * Updates an existing map object with the given attributes.
     * Existing attribtes are changed and new ones are set.
     */
    public function updateElement($id, $properties, $perm = false) {
        if(!isset($this->mapConfig[$id]))
            return false;

        $type = $this->mapConfig[$id]['type'];

        $this->mapConfig[$id]              = $properties;
        $this->mapConfig[$id]['type']      = $type;
        $this->mapConfig[$id]['object_id'] = $id;

        if($perm)
            $this->storeUpdateElement($id);
    }

    /**
     * Updates an element in the configuration file with the
     * current object config
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function storeUpdateElement($id) {
        $type = $this->mapConfig[$id]['type'];

        if(is_numeric($id) && $id == 0)
            list($inObj, $start, $end) = $this->getObjectLinesByNum(0);
        else
            list($inObj, $start, $end) = $this->getObjectLinesById($id);
        // Remove object head/foot
        $start += 1;
        $end   -= 1;

        if(!$inObj)
            return false;

        $f = $this->getConfig();

        // Loop all object lines from file to remove all parameters which can not be
        // found in the current array anymore
        for($i = $end - 1; $i >= $start; $i--) {
            $entry = explode('=', $f[$i], 2);
            $key = trim($entry[0]);
            if(!isset($this->mapConfig[$id][$key])) {
                array_splice($f, $i, 1);
                $end -= 1;
            }
        }

        // Loop all parameters from array
        foreach($this->mapConfig[$id] AS $key => $val) {
            $lineNum = null;
            $newLine = '';

            if($key === 'type')
                continue;

            // Search for the param in the map config
            for($i = $start; $i <= $end; $i++) {
                $entry = explode('=', $f[$i], 2);

                // Skip non matching keys
                if(trim($entry[0]) !== $key)
                    continue;

                $lineNum = $i;
            }

            if(is_array($val))
                $val = implode(',', $val);

            $newLine = $key.'='.$val."\n";

            if($lineNum !== null && $newLine !== '') {
                // if a parameter was found in file and value is not empty, replace line
                $f[$lineNum] = $newLine;
            } elseif($lineNum === null && $newLine !== '') {
                // if a parameter was not found in array and a value is not empty, create line
                array_splice($f, $end, 1, Array($newLine, $f[$end]));
                $end += 1;
            }
        }

        $this->writeConfig($f);
        return true;
    }

    /**
     * Writes the file contents to the configuration file and removes the cache
     * after finishing the write operation.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function writeConfig($cfg = null) {
        if($cfg !== null)
            $this->configFileContents = $cfg;

        // open file for writing and replace it
        $fp = fopen($this->configFile, 'w');
        fwrite($fp,implode('', $this->configFileContents));
        fclose($fp);

        // Also remove cache file
        if(file_exists($this->cacheFile))
            unlink($this->cacheFile);
    }

    /**
     * Gathers the lines of an object by the number of the object
     *
     * @param   Integer $num
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function getObjectLinesByNum($num) {
        $count = 0;
        $start = null;
        $inObj = false;
        $end   = null;

        $f = $this->getConfig();
        for($i = 0, $len = count($f); $i < $len; $i++) {
            if(strpos($f[$i], 'define') !== false) {
                if($count === $num) {
                    $inObj = true;
                    $start = $i;
                }

                $count++;
                continue;
            }

            // Terminate on first object end when in correct object
            if($inObj && trim($f[$i]) === '}') {
                if(isset($f[$i + 1]) && trim($f[$i + 1]) === '')
                    $end = $i + 1;
                else
                    $end = $i;
                break;
            }
        }

        return Array($inObj, $start, $end);
    }

    /**
     * Gathers the lines of an object by the given object id
     *
     * @param   Integer $id
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function getObjectLinesById($id) {
        $start = null;
        $inObj = false;
        $end   = null;

        $f = $this->getConfig();
        for($i = 0, $len = count($f); $i < $len; $i++) {
            // Save all object beginnings
            if(strpos($f[$i], 'define') !== false) {
                $start = $i;
                continue;
            }

            // Terminate on first object end when in correct object
            if($inObj && trim($f[$i]) === '}') {
                if(isset($f[$i + 1]) && trim($f[$i + 1]) === '')
                    $end = $i + 1;
                else
                    $end = $i;
                break;
            }

            // Check if this is the object_id line
            $delimPos = strpos($f[$i], '=');
            if($delimPos !== false) {
                $key   = trim(substr($f[$i], 0, $delimPos));
                $value = trim(substr($f[$i], ($delimPos+1)));
                if($key === 'object_id' && $value === $id) {
                    $inObj = true;
                    continue;
                }
            }
        }

        return Array($inObj, $start, $end);
    }

    /**
     * Gets all information about an object type
     *
     * @param   String  Type to get the information for
     * @return  Array   The validConfig array
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getValidObjectType($type) {
        return self::$validConfig[$type];
    }

    /**
     * Gets the valid configuration array
     *
     * @return	Array The validConfig array
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getValidConfig() {
        return self::$validConfig;
    }

    /**
     * Returns the name of the list function for the given map config option
     */
    public function getListFunc($type, $var) {
        if(isset(self::$validConfig[$type][$var]['list']))
            return self::$validConfig[$type][$var]['list'];
        else
            throw new NagVisException(l('No "list" function registered for option "[OPT]" of type "[TYPE]"',
                                                                       Array('OPT' => $var, 'TYPE' => $type)));
    }

    /**
     * Reads the configuration file of the map and
     * sends it as download to the client.
     *
     * @return	Boolean   Only returns FALSE if something went wrong
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function exportMap() {
        if($this->checkMapConfigReadable(1)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.$this->getName().'.cfg');
            header('Content-Length: '.filesize($this->configFile));

            if(readfile($this->configFile)) {
                exit;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Checks for writeable map config file
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkMapConfigWriteable($printErr) {
        return GlobalCore::getInstance()->checkWriteable($this->configFile, $printErr);
    }

    /**
     * Deletes the map configfile
     *
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function deleteMapConfig($printErr=1) {
        // is file writeable?
        if($this->checkMapConfigWriteable($printErr)) {
            if(unlink($this->configFile)) {
                // Also remove cache file
                if(file_exists($this->cacheFile))
                    unlink($this->cacheFile);

                // And also remove the permission
                GlobalCore::getInstance()->getAuthorization()->deletePermission('Map', $this->name);

                return TRUE;
            } else {
                if($printErr)
                    throw new NagVisException(l('couldNotDeleteMapCfg',
                                              Array('MAPPATH' => $this->configFile)));
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Gets lockfile information
     *
     * @param	Boolean $printErr
     * @return	Array/Boolean   Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
   */
    public function checkMapLocked($printErr=1) {
        // read lockfile
        $lockdata = $this->readMapLock();
        if(is_array($lockdata)) {
            // Only check locks which are not too old
            if(time() - $lockdata['time'] < cfg('wui','maplocktime') * 60) {
                // there is a lock and it should be recognized
                // check if this is the lock of the current user (Happens e.g. by pressing F5)
                if(GlobalCore::getInstance()->getAuthentication()->getUser() == $lockdata['user']
                                                                                        && $_SERVER['REMOTE_ADDR'] == $lockdata['ip']) {
                    // refresh the lock (write a new lock)
                    $this->writeMapLock();
                    // it's locked by the current user, so it's not locked for him
                    return FALSE;
                }

                // message the user that there is a lock by another user,
                // the user can decide wether he want's to override it or not
                if($printErr == 1)
                    print '<script>if(!confirm(\''.str_replace("\n", "\\n", l('mapLocked',
                                    Array('MAP' =>  $this->name,       'TIME' => date('d.m.Y H:i', $lockdata['time']),
                                                'USER' => $lockdata['user'], 'IP' =>   $lockdata['ip']))).'\', \'\')) { history.back(); }</script>';
                return TRUE;
            } else {
                // delete lockfile & continue
                // try to delete map lock, if nothing to delete its OK
                $this->deleteMapLock();
                return FALSE;
            }
        } else {
            // no valid information in lock or no lock there
            // try to delete map lock, if nothing to delete its OK
            $this->deleteMapLock();
            return FALSE;
        }
    }

    /**
     * Reads the contents of the lockfile
     *
     * @return	Array/Boolean   Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function readMapLock() {
        if($this->checkMapLockReadable(0)) {
            $fileContent = file($this->mapLockPath);
            // only recognize the first line, explode it by :
            $arrContent = explode(':',$fileContent[0]);
            // if there are more elements in the array it is OK
            if(count($arrContent) > 0) {
                return Array('time' => $arrContent[0], 'user' => $arrContent[1], 'ip' => trim($arrContent[2]));
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Writes the lockfile for a map
     *
     * @return	Boolean     Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function writeMapLock() {
        // Can an existing lock be updated?
        if($this->checkMapLockExists(0) && !$this->checkMapLockWriteable(0))
            return false;

        // If no map lock exists: Can a new one be created?
        if(!$this->checkMapLockExists(0) && !GlobalCore::getInstance()->checkWriteable(dirname($this->mapLockPath), 0))
            return false;

        // open file for writing and insert the needed information
        $fp = fopen($this->mapLockPath, 'w');
        fwrite($fp, time() . ':' . GlobalCore::getInstance()->getAuthentication()->getUser() . ':' . $_SERVER['REMOTE_ADDR']);
        fclose($fp);
        return true;
    }

    /**
     * Deletes the lockfile for a map
     *
     * @return	Boolean     Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function deleteMapLock() {
        if($this->checkMapLockWriteable(0)) {
            return unlink($this->mapLockPath);
        } else {
            // no map lock to delete => OK
            return TRUE;
        }
    }

    /**
     * Checks for existing lockfile
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkMapLockExists($printErr) {
        return GlobalCore::getInstance()->checkExisting($this->mapLockPath, $printErr);
    }

    /**
     * Checks for readable lockfile
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkMapLockReadable($printErr) {
        return GlobalCore::getInstance()->checkReadable($this->mapLockPath, $printErr);
    }

    /**
     * Checks for writeable lockfile
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkMapLockWriteable($printErr) {
        return GlobalCore::getInstance()->checkWriteable($this->mapLockPath, $printErr);
    }
}
?>
