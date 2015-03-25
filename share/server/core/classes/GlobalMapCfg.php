<?php
/*****************************************************************************
 *
 * GlobalMapCfg.php - Class for handling the map configuration files of NagVis
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
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalMapCfg {
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

    protected $ignoreSourceErrors = false;

    // Array for config validation
    protected static $validConfig       = null;
    protected static $updateValidConfig = array();
    protected static $hiddenConfigVars  = array();

    // Array for holding the registered map sources
    protected static $viewParams = array();

    /**
     * Class Constructor
     */
    public function __construct($name = '') {
        $this->name = $name;

        if(self::$validConfig == null)
            $this->fetchValidConfig();

        if(self::$viewParams == null)
            $this->fetchMapSources();

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
        global $CORE;
        self::$validConfig = Array();
        // Holds all registered config vars
        $mapConfigVars   = Array();
        // Maps the config vars to the object types
        $mapConfigVarMap = Array();

        $path = cfg('paths', 'server') . '/mapcfg';
        $files = $CORE->listDirectory($path, MATCH_PHP_FILE);
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
            'label_maxlen',
            'url_target',
            'hover_childs_show',
            'hover_childs_sort',
            'hover_childs_order',
            'hover_childs_limit');
        foreach($aVars As $sVar) {
            $sTmp = $this->getValue(0, $sVar);

            $this->typeDefaults['host'][$sVar] = $sTmp;
            $this->typeDefaults['hostgroup'][$sVar] = $sTmp;
            $this->typeDefaults['dyngroup'][$sVar] = $sTmp;
            $this->typeDefaults['aggr'][$sVar] = $sTmp;

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
        $RET = new GlobalBackground($this->getValue(0, 'map_image'));
        return $RET;
    }

    /**
     * Creates a new Configfile
     *
     * @return	Boolean	Is Successful?
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public function createMapConfig() {
        global $CORE;
        // does file exist?
        if($this->checkMapConfigReadable(false))
            return false;

        if(!$this->checkMapCfgFolderWriteable(true))
            return false;

        // create empty file
        fclose(fopen($this->configFile, 'w'));
        $CORE->setPerms($this->configFile);
        return true;
    }

    /**
     * Really parses a map configuration file
     */
    public function parseConfigFile($onlyGlobal) {
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

        // Don't read these keys
        $ignoreKeys = Array('type' => 0);

        $l = 0;

        // These variables set which object is currently being filled
        $sObjType = '';
        $iObjId = 0;
        $obj = Array();

        // Loop each line
        $iNumLines = count($file);
        for($l = 0; $l < $iNumLines; $l++) {
            // Remove spaces, newlines, tabs, etc. (http://de.php.net/rtrim)
            $file[$l] = rtrim($file[$l]);

            // Don't recognize empty lines
            if($file[$l] == '')
                continue;

            // Don't recognize comments and empty lines, do nothing with ending delimiters
            $sFirstChar = substr(ltrim($file[$l]), 0, 1);
            if($sFirstChar == ';' || $sFirstChar == '#')
                continue;

            // Fix ISO-8859-1 encoding. Convert to UTF-8. The mbstring extension might
            // be misssing. Simply skip this in that case.
            if(function_exists('mb_detect_encoding')
               && mb_detect_encoding($file[$l], 'UTF-8, ISO-8859-1') == 'ISO-8859-1')
                $file[$l] = utf8_encode($file[$l]);


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

            if(isset(self::$validConfig[$sObjType])
               && isset(self::$validConfig[$sObjType][$sKey])
               && isset(self::$validConfig[$sObjType][$sKey]['array']))
                $obj[$sKey] = explode(',', $sValue);
            else
                $obj[$sKey] = $sValue;
        }
    }

    /**
     * Reads the map config file (copied from readFile->readNagVisCfg())
     *
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function readMapConfig($onlyGlobal = 0, $resolveTemplates = true, $useCache = true) {
        global $_MAINCFG, $AUTHORISATION;
        // Only use cache when there is
        // a) The cache should be used
        // b) When whole config file should be read
        // c) Some valid cache file
        // d) Some valid main configuration cache file
        // e) This cache file newer than main configuration cache file
        if($onlyGlobal == 0
           && $useCache === true
           && $this->CACHE->isCached() !== -1
           && $_MAINCFG->isCached() !== -1
           && $this->CACHE->isCached() >= $_MAINCFG->isCached()) {
            $this->mapConfig = $this->CACHE->getCache();
            $this->typeDefaults = $this->DCACHE->getCache();

            // Now process the information from the sources
            $this->processSources();

            // Cache objects are not needed anymore
            $this->CACHE = null;
            $this->DCACHE = null;

            $this->BACKGROUND = $this->getBackground();

            // YAY! Got cached data.
            return TRUE;
        }

        //
        // Got no cached data. Now parse the map config.
        //

        if($this->hasConfigFile()) {
            $this->parseConfigFile($onlyGlobal);
        } else {
            // Initialize basic configuration for configless (ondemand) maps
            // Now load the user parameters into global config
            $this->mapConfig[0] = array_merge(array(
                'type' => 'global',
            ), $this->getSourceParams(true, true));
        }

        // Update the valid config construct with source specific adaptions
        // It is a hack to do it here, because these changes might affect maps
        // which are using other sources. But for the moment this seems to be
        // okay, because the sources only adapt options which are related to
        // the visualisation of objects.
        $this->addSourceDefaults();

        // Gather the default values for the object types
        $this->gatherTypeDefaults($onlyGlobal);

        if($onlyGlobal == 0) {
            if($resolveTemplates == true) {
                // Merge the objects with the linked templates
                $this->mergeTemplates();
            }
        }

        try {
            $this->checkMapConfigIsValid();
        } catch(MapCfgInvalid $e) {
            $this->BACKGROUND = $this->getBackground();
            throw $e;
        }

        if($onlyGlobal == 0) {
            // Check object id attribute and if there is none generate a new unique
            // object_id on the map for the object
            $this->verifyObjectIds();

            // Trigger the autorization backend to create new permissions when needed
            if($AUTHORISATION !== null) {
                $AUTHORISATION->createPermission('Map', $this->getName());
            }

            // Build cache
            if($useCache === true) {
                $this->CACHE->writeCache($this->mapConfig, 1);
                $this->DCACHE->writeCache($this->typeDefaults, 1);
            }
        }

        if($onlyGlobal == 0 || (isset($_GET['act']) && $_GET['act'] == 'getMapProperties')) {
            // Now process the data from the sources
            $this->processSources();
        }

        // Cache objects are not needed anymore
        $this->CACHE = null;
        $this->DCACHE = null;

        $this->BACKGROUND = $this->getBackground();

        return TRUE;
    }

    /**
     * Performs the initial map source loading
     */
    private function fetchMapSources() {
        global $CORE;
        foreach($CORE->getAvailableSources() AS $source_name) {
            $viewParams       = array();
            $configVars       = array();
            $updateConfigVars = array();
            $hiddenConfigVars = array();
            $selectable       = false;

            if(file_exists(path('sys', 'local', 'sources'))) {
                include_once(path('sys', 'local', 'sources') . '/'. $source_name . '.php');
            } else {
                include_once(path('sys', 'global', 'sources') . '/'. $source_name. '.php');
            }

            // Add the view params of that source to the list of parameters
            foreach($viewParams AS $source => $val) {
                if(isset(self::$viewParams[$source]))
                    self::$viewParams[$source] = array_merge(self::$viewParams[$source], $val);
                else
                    self::$viewParams[$source] = $val;
            }

            // Also feed the valid config array to get the options from the sources
            foreach($configVars AS $key => $val) {
                self::$validConfig['global'][$key] = $val;
                // Mark this option as source parameter. Save the source file in the value
                self::$validConfig['global'][$key]['source_param']  = $source_name;
            }

            // Apply adaptions to the generic options
            if (count($updateConfigVars) > 0) {
                self::$updateValidConfig[$source_name] = $updateConfigVars;
            }

            // Apply adaptions to the generic options
            if (count($hiddenConfigVars) > 0) {
                self::$hiddenConfigVars[$source_name] = $hiddenConfigVars;
            }

            // Register the slectable source
            if ($selectable) {
                $CORE->addSelectableSource($source_name);
            }
        }
    }

    /**
     * Returns possible options for the source params to make them selectable by lists
     */
    public function getSourceParamChoices($params) {
        global $CORE;
        $values = array();
        $validConfig = self::$validConfig['global'];
        foreach($params AS $param) {
            if(isset($validConfig[$param]) && isset($validConfig[$param]['list'])) {
                $func = $validConfig[$param]['list'];
                try {
                    $vals = $func($CORE, $this, 0, array());

                    // When this is an associative array use labels instead of real values
                    // Change other arrays to associative ones for easier handling afterwards
                    if(isset($vals[0])) {
                        // Change the format to assoc array with null values
                        $new = Array();
                        foreach($vals AS $val)
                            $new[$val] = $val;
                        $vals = $new;
                    }

                    if(isset($validConfig[$param]['must']) && $validConfig[$param]['must'] == false) {
                        $values[$param] = array_merge(array('' => ''), $vals);
                    } else {
                        $values[$param] = $vals;
                    }
                } catch(BackendConnectionProblem $e) {
                    // FIXME: Show error message?
                    $values[$param] = array();
                }
            }
        }
        return $values;
    }

    public function getSourceParamDefs($params) {
        $defs = array();
        foreach($params as $param) {
            $defs[$param] = self::$validConfig['global'][$param];
        }
        return $defs;
    }

    // Handles
    // a) map config
    // b) user config
    // c) url parameters
    public function getSourceParam($key, $only_user_supplied = false, $only_customized = false) {
        // Allow _GET or _POST (_POST is needed for add/modify dialog submission)
        if(isset($_REQUEST[$key])) {
            // Only get options which differ from the defaults
            // Maybe convert the type, if requested
            if(isset(self::$validConfig['global'][$key]['array'])
               && self::$validConfig['global'][$key]['array'] === true) {
                if ($_REQUEST[$key] !== '')
                    $val = explode(',', $_REQUEST[$key]);
                else
                    $val = array();
            } else {
                $val = $_REQUEST[$key];
            }
                
            if(!$only_customized || ($val != $this->getValue(0, $key))) {
                return $val;
            }
        } else {
            // Try to use the user profile
            $USERCFG = new CoreUserCfg();
            $userParams = $USERCFG->getValue('params-' . $this->name);
            if(isset($userParams[$key])) {
                return $userParams[$key];
                
            } elseif(!$only_user_supplied) {
                // Otherwise use the map global value (if allowed)
                return $this->getValue(0, $key);

            }
        }

        return null;
    }

    private function getSourceParamsOfSources($sources, $only_user_supplied, $only_customized, $only_view_parameters) {
        $keys = array();
        // Get keys of all view params belonging to all configured sources
        foreach($sources AS $source) {
            if(!isset(self::$viewParams[$source])) {
                throw new NagVisException(l('Requested source "[S]" does not exist',
                                                            array('S' => $source)));
            }
            $keys = array_merge($keys, self::$viewParams[$source]);
        }

        if(!$only_view_parameters) {
            // If allowed, also use the configuration parameters which are no view parameters
            // (These are the configuration options which belong to a source but are not
            //  available as user modifyable view parameters)
            foreach (self::$validConfig['global'] as $key => $opt) {
                if (isset($opt['source_param']) && in_array($opt['source_param'], $sources)) {
                    $keys[] = $key;
                }
            }
        }

        // Now get the values. First try to fetch the value by _GET parameter (if allowed)
        // Otherwise use the value from mapcfg or default coded value
        $params = array();
        foreach($keys AS $key) {
            $val = $this->getSourceParam($key, $only_user_supplied, $only_customized);
            if($val !== null)
                $params[$key] = $val;
        }

        return $params;
    }

    /**
     * Returns an associative array of all parameters with values for all sources
     * used on the current map.
     * The default case is to return view parameters and config values of the
     * enabled sources. But in some cases the function on returns the view parameters.
     */
    public function getSourceParams($only_user_supplied = false, $only_customized = false, $only_view_parameters = false) {
        // First get a list of source names to get the parameters for
        $config  = $this->getValue(0, 'sources') !== false ? $this->getValue(0, 'sources') : array();
        $sources = array_merge(array('*'), $config);
        $params  = $this->getSourceParamsOfSources($sources, $only_user_supplied, $only_customized, $only_view_parameters);

        // The map sources might have changed basd on source params - we need an
        // additional run to get params which belong to this sources
        if(isset($params['sources'])) {
            $sources = array_merge(array('*'), $params['sources']);
            $params = $this->getSourceParamsOfSources($sources, $only_user_supplied, $only_customized, $only_view_parameters);
        }

        return $params;
    }

    public function getHiddenConfigVars() {
        $hidden = array();
        $sources = $this->getValue(0, 'sources') !== false ? $this->getValue(0, 'sources') : array();
        foreach ($sources AS $source_name) {
            if (isset(self::$hiddenConfigVars[$source_name])) {
                $hidden = array_merge($hidden, self::$hiddenConfigVars[$source_name]);
            }
        }
        return $hidden;
    }

    private function addSourceDefaults() {
        $sources = $this->getValue(0, 'sources') !== false ? $this->getValue(0, 'sources') : array();
        foreach ($sources AS $source_name) {
            if (isset(self::$updateValidConfig[$source_name])) {
                foreach(self::$updateValidConfig[$source_name] AS $param => $spec) {
                    foreach ($spec AS $key => $val) {
                        self::$validConfig['global'][$param][$key] = $val;
                    }
                }
            }
        }
    }

    /**
     * Stores the user given options as parameters in the map configuration when
     * the user requested this.
     */
    public function storeParams() {
        // FIXME: Only make permanent for current user. Make it optinally
        // permanent for all users by a second flag
        foreach($this->getSourceParams(true) AS $param => $value)
            $this->setValue(0, $param, $value);
        $this->storeUpdateElement(0);
    }

    /**
     * Returns true on the first source which reports it has changed.
     */
    private function sourcesChanged($compareTime) {
        $sources = $this->getValue(0, 'sources');
        if(!$sources)
            return false;

        foreach($sources AS $source) {
            $func = 'changed_'.$source;
            if($func($this, $compareTime)) {
                return true;
            }
        }
        return false;
    }

    public function skipSourceErrors($flag = true) {
        $this->ignoreSourceErrors = $flag;
    }

    // converts params to their string representations, like used in filenames for caches
    private function paramsToString($params) {
        $p = array();
        foreach ($params AS $key => $val) {
            if(isset(self::$validConfig['global'][$key]['array']) && self::$validConfig['global'][$key]['array'] === true) {
                $val = implode(',', $val);
            }
            $p[$key] = $val;
        }
        return implode('_', $p);
    }

    /**
     * A source can modify the map configuration before it is used for further processing.
     * Such a source is a key which points to a "process", "params" and "changed" function
     * which can
     *  1. modify the map config array
     *  2. gather all the parameters used in this source
     *  3. tell the source processing that the data used in this source has changed and the
     *     source needs processed again
     */
    private function processSources() {
        global $_MAINCFG;
        $sources = $this->getValue(0, 'sources');
        if(!$sources)
            return;

        // 1.  Check if there is a cache file for a query with this params
        $params = $this->getSourceParams();
        // FIXME: Add a flag to exclude options from cache file naming
        if(isset($params['source_file']))
            unset($params['source_file']);
        $param_values = $this->paramsToString($params);
        $cacheFile = cfg('paths','var').'source-'.$this->name.'.cfg-'.$param_values.'-'.CONST_VERSION.'.cache';
        $CACHE = new GlobalFileCache(array(), $cacheFile);

        // 2a. Check if the cache file exists
        // 2b. Check if the cache file is newer than the latest changed source
        $cache_sources = $CACHE->isCached();
        $cache_map     = $this->CACHE->isCached();
        $cache_maincfg = $_MAINCFG->isCached();
        if($cache_sources != -1 && $cache_map != -1 && $cache_maincfg != -1
           && $cache_sources >= $cache_maincfg && $cache_sources >= $cache_map
           && !$this->sourcesChanged($cache_sources)) {
            // 3a. Use the cache
            $this->mapConfig = $CACHE->getCache();
            return;
        }

        // 3b. Process all the sources
        foreach($sources AS $source) {
            $func = 'process_'.$source;
            if(!function_exists($func))
                throw new NagVisException(l('Requested source "[S]" does not exist',
                                                                array('S' => $source)));
            try {
                $func($this, $this->name, $this->mapConfig);
            } catch(Exception $e) {
                if(!$this->ignoreSourceErrors) {
                    throw $e;
                }
            }
        }

        // Call process filter implicit if not already done
        process_filter($this, $this->name, $this->mapConfig);

        // Write cache
        $CACHE->writeCache($this->mapConfig, 1);

        // FIXME: Invalidate/remove cache files on changed configurations
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
     * It might be possible that a map does not use a configuration file and is only
     * computed on-demand using parameters, this informs the callers about this
     */
    public function hasConfigFile() {
        return $this->name != '';
    }

    /**
     * Checks for existing config file
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkMapConfigExists($printErr) {
        global $CORE;
        return $CORE->checkExisting($this->configFile, $printErr);
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
        global $CORE;
        return $CORE->checkReadable($this->configFile, $printErr);
    }

    /**
     * Generates a new object id for an object on the map
     *
     * @return  String  The object ID
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function genObjId($s) {
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
        global $CORE;
        foreach($this->mapConfig AS $id => $element) {
            $type = $element['type'];

            // check given objects and attributes
            if($type == 'global')
                $exception = 'MapCfgInvalid';
            else
                $exception = 'MapCfgInvalidObject';

            // loop validConfig for checking: => missing "must" attributes
            foreach(self::$validConfig[$type] AS $key => $val) {
                // In case of "source" options only validate the ones which belong
                // to currently enabled sources
                if(isset($val['source_param']) && !in_array($val['source_param'], $this->getValue(0, 'sources')))
                    continue;

                if(isset($val['must']) && $val['must'] == true) {
                    if((!isset($element[$key]) || $element[$key] == '') && (!isset($val['default']) || $val['default'] == '')) {
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
                    if($type == 'global' && $key == 'backend_id')
                        foreach($val as $backend_id)
                            if(!in_array($backend_id, $CORE->getDefinedBackends()))
                                throw new $exception(l('backendNotDefined', Array('BACKENDID' => $backend_id)));
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
    public function getFileModificationTime($compareTime = null) {
        // on-demand maps have no age, return the compare time
        if(!$this->hasConfigFile())
            return $compareTime;

        if($this->checkMapConfigReadable(1)) {
            // When the sources changed compared to the given time,
            // return always the current time
            if($compareTime !== null) {
                if($this->sourcesChanged($compareTime)) {
                    return time();
                }
            }

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
        global $CORE;
        return $CORE->checkReadable(dirname($this->configFile), $printErr);
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
                if(is_array($this->mapConfig[$id][$key]))
                    $a[] = $key.'='.implode(',', $this->mapConfig[$id][$key])."\n";
                else
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

        if(!$inObj) {
            // It might happen that the object can not be found in the current map config.
            // For example if the map object has been created bya "source". The object
            // needs to be persisted even if the object does not exist in the map config.
            // Before 1.6.4 this simply returned false to refuse persisting non existants.
            return $this->storeAddElement($id);
        }

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

        if (function_exists('hook_map_config_saved'))
            hook_map_config_saved($this->getName(), $this->configFile);
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
        global $CORE;
        return $CORE->checkWriteable($this->configFile, $printErr);
    }

    /**
     * Deletes the map configfile
     *
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function deleteMapConfig($printErr=1) {
        global $AUTHORISATION;
        // is file writeable?
        if($this->checkMapConfigWriteable($printErr)) {
            if(unlink($this->configFile)) {
                // Also remove cache file
                if(file_exists($this->cacheFile))
                    unlink($this->cacheFile);

                // And also remove the permission
                $AUTHORISATION->deletePermission('Map', $this->name);

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
        global $AUTH;
        // read lockfile
        $lockdata = $this->readMapLock();
        if(is_array($lockdata)) {
            // Only check locks which are not too old
            if(time() - $lockdata['time'] < cfg('wui','maplocktime') * 60) {
                // there is a lock and it should be recognized
                // check if this is the lock of the current user (Happens e.g. by pressing F5)
                if($AUTH->getUser() == $lockdata['user'] && $_SERVER['REMOTE_ADDR'] == $lockdata['ip']) {
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
        global $CORE, $AUTH;
        // Can an existing lock be updated?
        if($this->checkMapLockExists(0) && !$this->checkMapLockWriteable(0))
            return false;

        // If no map lock exists: Can a new one be created?
        if(!$this->checkMapLockExists(0) && !$CORE->checkWriteable(dirname($this->mapLockPath), 0))
            return false;

        // open file for writing and insert the needed information
        $fp = fopen($this->mapLockPath, 'w');
        fwrite($fp, time() . ':' . $AUTH->getUser() . ':' . $_SERVER['REMOTE_ADDR']);
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
        global $CORE;
        return $CORE->checkExisting($this->mapLockPath, $printErr);
    }

    /**
     * Checks for readable lockfile
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkMapLockReadable($printErr) {
        global $CORE;
        return $CORE->checkReadable($this->mapLockPath, $printErr);
    }

    /**
     * Checks for writeable lockfile
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkMapLockWriteable($printErr) {
        global $CORE;
        return $CORE->checkWriteable($this->mapLockPath, $printErr);
    }
}
?>
