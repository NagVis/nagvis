<?php
/*****************************************************************************
 *
 * NagVisAutomapCfg.php - Class for handling the NagVis automap configuration
 * files
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
class NagVisAutomapCfg extends GlobalMapCfg {
    private $defaultConf = null;

    private $objIds = Array();
    private $objIdFile;

    /**
     * Class Constructor
     *
     * @param	GlobalCore      $CORE
     * @param	String			$name		Name of the map
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct($CORE, $name) {
        // Fix the automap name (backward compatible to old automap=1)
        if(isset($name) && $name === '1') {
            $this->name = '__automap';
        } else {
            $this->name	= $name;
        }

        $this->objIdFile = cfg('paths', 'var').'automap.hostids';
        $this->type = 'automap';

        // Start of the parent constructor
        parent::__construct($CORE, $this->name);

        // Modify must values -> coords don't need to be set
        parent::$validConfig['host']['x']['must'] = 0;
        parent::$validConfig['host']['y']['must'] = 0;

        // Override the default map configuration path with automap path
        $this->setConfigFile(cfg('paths', 'automapcfg').$this->name.'.cfg');

        // Re-initialize the cache
        $this->initCache();
    }

    /**
     * Gets the configuration of the objects using the global configuration
     *
     * @param   Strin   Optional: Name of the object to get the config for
     * @return	Array		Object configuration
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function &getObjectConfiguration() {
        // Load the settings once and then remove the dummy host from host list
        if(!$this->defaultConf) {
            $this->defaultConf = Array();

            $keys = array_keys($this->getMapObjects());
            // Use either the __dummy__ host or the global section for gathering
            // the default configuration
            if(isset($keys[1]))
                $objectId = $keys[1];
            else
                $objectId = 0;

            /*
             * Get object default configuration from configuration file
             */
            foreach($this->getValidTypeKeys('host') AS $key) {
                if($key != 'type'
                     && $key != 'backend_id'
                     && $key != 'host_name'
                     && $key != 'object_id'
                     && $key != 'x'
                     && $key != 'y'
                     && $key != 'line_width') {
                    $this->defaultConf[$key] = $this->getValue($objectId, $key);
                }
            }

            // Delete the dummy object when it has been used
            if($objectId != 0)            
                $this->deleteElement($objectId);
        }

        return $this->defaultConf;
    }

    public function storeAddElement($unused) { }
    public function storeDeleteElement($unused, $unused1 = null) { }

    public function genObjIdAutomap($s) {
        return $this->genObjId($s);
    }

    public function filterMapObjects($a) {
        if(isset($this->mapConfig[2])) {
            $global = $this->mapConfig[0];
            $dummy  = $this->mapConfig[1];
            parent::filterMapObjects($a);
            $this->mapConfig = array_merge(Array($global, $dummy), $this->mapConfig);
        }
    }

    /**
     * Transforms a list of automap object_ids to hostnames using the object_id
     * translation file. Unknown object_ids are skipped
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function objIdsToNames($ids) {
        $names = Array();
        $map = $this->loadObjIds();
        foreach($ids AS $id) {
            $name = array_search($id, $map);
            if($name !== FALSE)
                $names[] = $name;
        }
        return $names;
    }

    /**
     * Transforms an object_id to the hostname
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function objIdToName($id) {
        return array_search($id, $this->loadObjIds());
    }

    /**
     * Loads the hostname to object_id mapping table from the central file
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function loadObjIds() {
        if(!isset($this->objIds[0]))
            if(GlobalCore::getInstance()->checkExisting($this->objIdFile, false))
                $this->objIds = json_decode(file_get_contents($this->objIdFile), true);
            else
                $this->objIds = Array();

        return $this->objIds;
    }

    /**
     * Saves the given hostname to object_id mapping table in the central file
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function storeObjIds($a) {
        $this->objIds = $a;

        return file_put_contents($this->objIdFile, json_encode($a)) !== false;
    }
}
?>
