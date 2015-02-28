<?php
/*******************************************************************************
 *
 * CoreModuleHandler.php - Class to handle core modules
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModuleHandler {
    protected $CORE;
    protected $aRegistered;
    protected $sPrefix;

    public function __construct($CORE = null) {
      if($CORE === null)
            $this->CORE = GlobalCore::getInstance();
        else
            $this->CORE = $CORE;

        $this->aRegistered = Array();
        $this->sPrefix = 'CoreMod';
    }

    /**
     * Loads an instance of the given module. The module needs to be registered
     * before loading.
     *
     * @param  String  Name of the module to load
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public function loadModule($sModule) {
        // Check if module class is registered
        if(isset($this->aRegistered[$this->sPrefix.$sModule]) && $this->aRegistered[$this->sPrefix.$sModule] === 'active') {
            $className = $this->sPrefix.$sModule;

            // create instance of module
            $MOD = new $className($this->CORE);

            // return instance
            return $MOD;
        } else {
            throw new NagVisException(l('The given module is not registered'));
        }
    }

    /**
     * Registers a module by its name. After registering it is available
     * to be loaded.
     *
     * @param  String  Name of the module to register
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    public function regModule($sModule) {
        // Check if module class exists
        if(class_exists($this->sPrefix.$sModule)) {
            // Register the module at the module handler
            $this->aRegistered[$this->sPrefix.$sModule] = 'active';
            return true;
        } else {
            throw new NagVisException(l('The module class does not exist'));
        }
    }
}

?>
