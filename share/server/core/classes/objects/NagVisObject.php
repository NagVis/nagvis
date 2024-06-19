<?php
/*****************************************************************************
 *
 * NagVisObject.php - Abstract class of an object in NagVis with all necessary
 *                  information which belong to the object handling in NagVis
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

class NagVisObject
{
    protected $conf = [];

    protected $type;
    protected $object_id;
    protected $x;
    protected $y;
    protected $z;
    protected $icon;
    protected $url_target;
    protected $context_menu;
    protected $context_template;
    protected $use;

    protected $url; // Not supported by Textbox
    protected $view_type; // Not supported by Textbox, Shape

    protected $hover_menu;
    protected $hover_delay;
    protected $hover_url;
    protected $hover_template;
    protected $hover_childs_show;
    protected $hover_childs_sort;
    protected $hover_childs_order;
    protected $hover_childs_limit;
    protected $label_show;

    protected $line_cut;
    protected $line_type;
    protected $line_width;

    protected $min_zoom;
    protected $max_zoom;

    protected static $sSortOrder = 'asc';
    protected static $stateWeight = null;
    private static $arrDenyKeys = null;

    public function __construct() {}

    /**
     * Get method for all options
     *
     * @return	mixed  Value of the given option
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function get($option)
    {
        return $this->{$option};
    }

    /**
     * Get method for x coordinate of the object
     *
     * @return	int		x coordinate on the map
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Get method for y coordinate of the object
     *
     * @return	int		y coordinate on the map
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Get method for z coordinate of the object
     *
     * @return	int		z coordinate on the map
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getZ()
    {
        return $this->z;
    }

    /**
     * Get method for type of the object
     *
     * @return	string		Type of the object
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * PUBLIC getObjectId()
     *
     * Get method for the object id
     *
     * @return	int		Object ID
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getObjectId()
    {
        return $this->object_id;
    }

    /**
     * PUBLIC setObjectId()
     *
     * Set method for the object id
     *
     * @param   int $id Object id to set for the object
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function setObjectId($id)
    {
        $this->object_id = $id;
    }

    /**
     * Get method for the name of the object
     */
    public function getName()
    {
        return $this->{$this->type . '_name'};
    }

    // Returns the display_name of an object, if available, otherwise
    // the alias of an object, if available, otherwise the name
    public function getDisplayName()
    {
        return $this->getName();
    }

    /**
     * Get method for the hover template of the object
     *
     * @return	string		Hover template of the object
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getHoverTemplate()
    {
        return $this->hover_template;
    }

    /**
     * Set method for the object coords
     */
    public function setMapCoords($arrCoords)
    {
        $this->setConfiguration($arrCoords);
    }

    /**
     * PUBLIC setConfiguration()
     *
     * Sets options of the object
     *
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function setConfiguration($obj)
    {
        foreach ($obj as $key => $val) {
            $this->{$key} = $val;
        }
    }

    /**
     * PUBLIC setObjectInformation()
     *
     * Sets extended information of the object
     *
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function setObjectInformation($obj)
    {
        foreach ($obj as $key => $val) {
            $this->{$key} = $val;
        }
    }

    /**
     * Gets all necessary information of the object as array
     */
    public function getObjectInformation()
    {
        $arr = [];

        // Need to remove some options which are not relevant
        // FIXME: Would be much better to name the needed vars explicit
        if (self::$arrDenyKeys == null) {
            self::$arrDenyKeys = [
                'MAPCFG' => '', 'MAP' => '',
                'conf' => '', 'services' => '', 'fetchedChildObjects' => '', 'childObjects' => '',
                'parentObjects' => '', 'members' => '', 'objects' => '', 'linkedMaps' => '',
                'isSummaryObject' => '', 'isView' => '', 'dateFormat' => '', 'arrDenyKeys' => '',
                'aStateCounts' => '', 'iconDetails' => '', 'problem_msg' => '', 'isLoopingBacklink' => ''
            ];
        }

        foreach ($this as $key => $val) {
            if (!isset(self::$arrDenyKeys[$key]) && $val !== null) {
                $arr[$key] = $val;
            }
        }

        // I want only "name" in js
        $arr['name'] = $this->getName();

        if ($this->type == 'service' && isset($arr['host_name'])) {
            unset($arr['host_name']);
        } else {
            unset($arr[$this->type . '_name']);
        }

        return $arr;
    }


    /**
     * PUBLIC getObjectConfiguration()
     *
     * Gets the configuration of the object
     *
     * @return	array		Object configuration
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getObjectConfiguration($abstract = true)
    {
        // Some options have to be removed which are only for this object
        $arr = $this->conf;
        unset($arr['id']);
        unset($arr['object_id']);
        unset($arr['type']);

        // Only remove these options when the configuration should be
        // completely independent from this object
        if ($abstract) {
            unset($arr['host_name']);
            unset($arr[$this->type . '_name']);
            unset($arr['service_description']);
        }

        return $arr;
    }

    /**
     * PUBLIC parseJson()
     *
     * Parses the object in json format
     *
     * @return	string  JSON code of the object
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function parseJson()
    {
        return $this->getObjectInformation();
    }

    /**
     * PUBLIC parseMapCfg()
     *
     * Parses the object in map configuration format
     *
     * @param   array $globalOpts Array of global map options
     * @return	string  This object in map config format
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function parseMapCfg($globalOpts = [])
    {
        $ret = 'define ' . $this->type . " {\n";
        if ($this->type === 'host' && $this instanceof NagVisHost) {
            $ret .= '  host_name=' . $this->host_name . "\n";
        }
        $ret .= '  object_id=' . $this->object_id . "\n";
        foreach ($this->getObjectConfiguration(false) as $key => $val) {
            // Only set options which are different to global option
            if ((!isset($globalOpts[$key]) || $globalOpts[$key] != $val) && $val != '') {
                $ret .= '  ' . $key . '=' . $val . "\n";
            }
        }
        $ret .= "}\n\n";

        return $ret;
    }

    /**
     * PUBLIC getUrl()
     * Returns the url for the object link
     */
    public function getUrl()
    {
        if (isset($this->url)) {
            return $this->url;
        } else {
            return '';
        }
    }

    # End public methods
    # #########################################################################

    /**
     * PROTECTED getUrlTarget()
     *
     * Returns the target frame for the object link
     *
     * @return	string	Target
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    protected function getUrlTarget()
    {
        return $this->url_target;
    }

    /**
     * PRIVATE STATIC sortObjectsAlphabetical()
     *
     * Sorts both objects alphabetically by name
     *
     * @param	object $OBJ1 First object to sort
     * @param	object $OBJ2 Second object to sort
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public static function sortObjectsAlphabetical($OBJ1, $OBJ2)
    {
        if ($OBJ1->type == 'service') {
            $name1 = strtolower($OBJ1->getName() . $OBJ1->getServiceDescription());
        } else {
            $name1 = strtolower($OBJ1->getName());
        }

        if ($OBJ2->type == 'service') {
            $name2 = strtolower($OBJ2->getName() . $OBJ2->getServiceDescription());
        } else {
            $name2 = strtolower($OBJ2->getName());
        }

        if ($name1 == $name2) {
            return 0;
        } elseif ($name1 > $name2) {
            // Sort depending on configured direction
            if (self::$sSortOrder === 'asc') {
                return +1;
            } else {
                return -1;
            }
        } elseif (self::$sSortOrder === 'asc') {
            // Sort depending on configured direction
            return -1;
        } else {
            return +1;
        }
    }

    /**
     * PRIVATE STATIC sortObjectsByState()
     *
     * Sorts both by state of the object
     *
     * @param	object $OBJ1 First object to sort
     * @param	object $OBJ2 Second object to sort
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public static function sortObjectsByState($OBJ1, $OBJ2)
    {
        $state1 = $OBJ1->sum[STATE];
        $subState1 = $OBJ1->getSubState(SUMMARY_STATE);

        $state2 = $OBJ2->sum[STATE];
        $subState2 = $OBJ2->getSubState(SUMMARY_STATE);

        return NagVisObject::sortStatesByStateValues($state1, $subState1, $state2, $subState2, self::$sSortOrder);
    }

    /**
     * Helper to sort states independent of objects
     */
    public static function sortStatesByStateValues($state1, $subState1, $state2, $subState2, $sortOrder)
    {
        global $_MAINCFG;

        // Quit when nothing to compare
        if ($state1 === null || $state2 === null) {
            return 0;
        }

        $stateWeight = $_MAINCFG->getStateWeight();

        // Handle normal/ack/downtime states
        if ($stateWeight[$state1][$subState1] == $stateWeight[$state2][$subState2]) {
            return 0;
        } elseif ($stateWeight[$state1][$subState1] < $stateWeight[$state2][$subState2]) {
            // Sort depending on configured direction
            if ($sortOrder === 'asc') {
                return + 1;
            } else {
                return -1;
            }
        } elseif ($sortOrder === 'asc') {
            // Sort depending on configured direction
            return -1;
        } else {
            return + 1;
        }
    }
}
