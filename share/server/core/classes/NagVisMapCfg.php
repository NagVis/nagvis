<?php
/*****************************************************************************
 *
 * NagVisMapCfg.php - Class for handling the NagVis map configuration files
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
class NagVisMapCfg extends GlobalMapCfg {
    /**
     * Class Constructor
     *
     * @param	GlobalCore	$CORE
     * @param	String			$name		Name of the map
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct($CORE, $name='') {
        parent::__construct($CORE, $this->getMap($name));
    }

    /**
     * Reads which map should be displayed, primary use
     * the map defined in the url, if there is no map
     * in url, use first entry of "maps" defined in
     * the NagVis main config
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function getMap($name) {
        // check the $this->name string for security reasons (its the ONLY value we get directly from external...)
        // Allow ONLY Characters, Numbers, - and _ inside the Name of a Map
        return preg_replace('/[^a-zA-Z0-9_-]/','',$name);
    }
}
?>