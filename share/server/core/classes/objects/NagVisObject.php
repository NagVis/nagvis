<?php
/*****************************************************************************
 *
 * NagVisObject.php - Abstract class of an object in NagVis with all necessary
 *                  information which belong to the object handling in NagVis
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
class NagVisObject {
    protected $conf = array();

    protected $object_id;
    protected $x;
    protected $y;
    protected $z;
    protected $icon;
    protected $url;
    protected $url_target;

    protected $view_type;
    protected $hover_menu;
    protected $hover_childs_show;
    protected $hover_childs_sort;
    protected $hover_childs_order;
    protected $hover_childs_limit;
    protected $label_show;

    protected static $sSortOrder = 'asc';
    protected static $stateWeight = null;
    private static $arrDenyKeys = null;

    public function __construct() {}

    /**
     * Get method for all options
     *
     * @return	Value  Value of the given option
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function get($option) {
        return $this->{$option};
    }

    /**
     * Get method for x coordinate of the object
     *
     * @return	Integer		x coordinate on the map
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getX() {
        return $this->x;
    }

    /**
     * Get method for y coordinate of the object
     *
     * @return	Integer		y coordinate on the map
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getY() {
        return $this->y;
    }

    /**
     * Get method for z coordinate of the object
     *
     * @return	Integer		z coordinate on the map
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getZ() {
        return $this->z;
    }

    /**
     * Get method for type of the object
     *
     * @return	String		Type of the object
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getType() {
        return $this->type;
    }

    /**
     * PUBLIC getObjectId()
     *
     * Get method for the object id
     *
     * @return	Integer		Object ID
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getObjectId() {
        return $this->object_id;
    }

    /**
     * PUBLIC setObjectId()
     *
     * Set method for the object id
     *
     * @param   Integer    Object id to set for the object
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setObjectId($id) {
        $this->object_id = $id;
    }

    /**
     * Get method for the name of the object
     *
     * @return	String		Name of the object
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getName() {
        if($this->type == 'dyngroup' || $this->type == 'aggr') {
            return $this->name;
        } elseif ($this->type == 'service') {
            return $this->host_name;
        } else {
            return $this->{$this->type.'_name'};
        }
    }

    /**
     * Get method for the hover template of the object
     *
     * @return	String		Hover template of the object
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getHoverTemplate() {
        return $this->hover_template;
    }

    /**
     * Set method for the object coords
     *
     * @return	Array		Array of the objects coords
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setMapCoords($arrCoords) {
        $this->setConfiguration($arrCoords);
    }

    /**
     * PUBLIC setConfiguration()
     *
     * Sets options of the object
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setConfiguration($obj) {
        foreach($obj AS $key => $val) {
            $this->{$key} = $val;
        }
    }

    /**
     * PUBLIC setObjectInformation()
     *
     * Sets extended information of the object
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setObjectInformation($obj) {
        foreach($obj AS $key => $val) {
            $this->{$key} = $val;
        }
    }

    /**
     * PUBLIC getObjectInformation()
     *
     * Gets all necessary information of the object as array
     *
     * @return	Array		Object configuration
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getObjectInformation($bFetchChilds = true) {
        global $CORE;
        $arr = Array();

        // When the childs don't need to be fetched this object is a child
        // itselfs. So much less information are needed. Progress them here
        // If someone wants more information in hover menu children, this is
        // the place to change.
        if(!$bFetchChilds)
            return $this->fetchObjectAsChild();

        // Need to remove some options which are not relevant
        // FIXME: Would be much better to name the needed vars explicit
        if(self::$arrDenyKeys == null)
            self::$arrDenyKeys = Array(
                'MAPCFG' => '', 'MAP'  => '',
                'conf' => '', 'services' => '', 'fetchedChildObjects' => '', 'childObjects' => '',
                'parentObjects' => '', 'members' => '', 'objects' => '', 'linkedMaps' => '',
                'isSummaryObject' => '', 'isView' => '', 'dateFormat' => '', 'arrDenyKeys' => '',
                'aStateCounts' => '',	'iconDetails' => '', 'problem_msg' => '', 'isLoopingBacklink' => ''
            );

        foreach($this AS $key => $val)
            if(!isset(self::$arrDenyKeys[$key]) && $val !== null)
                $arr[$key] = $val;

        if($this instanceof NagVisStatefulObject) {
            $num_members = $this->getNumMembers();
            if($num_members !== null)
                $arr['num_members'] = $num_members;

            /**
             * FIXME: Find another place for that! This is a bad place for language strings!
             */
            switch($this->type) {
                case 'host':
                    if(NagVisHost::$langType === null) {
                        NagVisHost::$langType  = l('host');
                        NagVisHost::$langSelf  = l('hostname');
                        NagVisHost::$langChild = l('servicename');
                    }

                    $arr['lang_obj_type']    = NagVisHost::$langType;
                    $arr['lang_name']        = NagVisHost::$langSelf;
                    $arr['lang_child_name']  = NagVisHost::$langChild;
                break;
                case 'service':
                    if(NagVisService::$langType === null) {
                        NagVisService::$langType  = l('service');
                        NagVisService::$langSelf  = l('servicename');
                    }
                    if(NagVisHost::$langType === null)
                        NagVisHost::$langSelf = l('hostname');

                    $arr['lang_obj_type']    = NagVisService::$langType;
                    $arr['lang_name']        = NagVisHost::$langSelf;
                break;
                case 'hostgroup':
                    if(NagVisHostgroup::$langType === null) {
                        NagVisHostgroup::$langType  = l('hostgroup');
                        NagVisHostgroup::$langSelf  = l('hostgroupname');
                        NagVisHostgroup::$langChild = l('hostname');
                    }

                    $arr['lang_obj_type']    = NagVisHostgroup::$langType;
                    $arr['lang_name']        = NagVisHostgroup::$langSelf;
                    $arr['lang_child_name']  = NagVisHostgroup::$langChild;
                break;
                case 'servicegroup':
                    if(NagVisServicegroup::$langType === null) {
                        NagVisServicegroup::$langType   = l('servicegroup');
                        NagVisServicegroup::$langSelf   = l('servicegroupname');
                        NagVisServicegroup::$langChild  = l('servicename');
                        NagVisServicegroup::$langChild1 = l('hostname');
                    }

                    $arr['lang_obj_type']     = NagVisServicegroup::$langType;
                    $arr['lang_name']         = NagVisServicegroup::$langSelf;
                    $arr['lang_child_name']   = NagVisServicegroup::$langChild;
                    $arr['lang_child_name1']  = NagVisServicegroup::$langChild1;
                break;
                case 'dyngroup':
                    if(NagVisDynGroup::$langType === null) {
                        NagVisDynGroup::$langType   = l('Dynamic Group');
                        NagVisDynGroup::$langSelf   = l('Dynamic Group Name');
                        NagVisDynGroup::$langChild  = l('Object Name');
                        NagVisDynGroup::$langChild1 = l('hostname');
                    }

                    $arr['lang_obj_type']    = NagVisDynGroup::$langType;
                    $arr['lang_name']        = NagVisDynGroup::$langSelf;
                    $arr['lang_child_name']  = NagVisDynGroup::$langChild;
                    if ($this->object_types == 'service')
                        $arr['lang_child_name1'] = NagVisDynGroup::$langChild1;
                break;
                case 'aggr':
                    if(NagVisAggr::$langType === null) {
                        NagVisAggr::$langType   = l('Aggregation');
                        NagVisAggr::$langSelf   = l('Aggregation Name');
                        NagVisAggr::$langChild  = l('Name');
                        NagVisAggr::$langChild1 = l('Name');
                    }

                    $arr['lang_obj_type']    = NagVisAggr::$langType;
                    $arr['lang_name']        = NagVisAggr::$langSelf;
                    $arr['lang_child_name']  = NagVisAggr::$langChild;
                    $arr['lang_child_name1'] = NagVisAggr::$langChild1;
                break;
                case 'map':
                    if(NagVisMapObj::$langType === null) {
                        NagVisMapObj::$langType   = l('map');
                        NagVisMapObj::$langSelf   = l('mapname');
                        NagVisMapObj::$langChild  = l('objectname');
                    }

                    $arr['lang_obj_type']    = NagVisMapObj::$langType;
                    $arr['lang_name']        = NagVisMapObj::$langSelf;
                    $arr['lang_child_name']  = NagVisMapObj::$langChild;
                break;
            }
        }

        // I want only "name" in js
        if(!isset($CORE->statelessObjectTypes[$this->type])) {
            $arr['name'] = $this->getName();

            if($this->type == 'service') {
                unset($arr['host_name']);
            } else {
                unset($arr[$this->type.'_name']);
            }

            if ($this->type == 'host' || $this->type == 'service') {
                $obj_attrs = array(
                    'alias'         => ALIAS,
                    'display_name'  => DISPLAY_NAME,
                    'address'       => ADDRESS,
                    'notes'         => NOTES,
                    'check_command' => CHECK_COMMAND,
                );
                foreach ($obj_attrs AS $attr => $state_key) {
                    if (isset($this->state[$state_key]) && $this->state[$state_key] != '')
                        $arr[$attr] = $this->state[$state_key];
                    else
                        $arr[$attr] = '';
                }
            } elseif ($this->type == 'map'
                      || $this->type == 'servicegroup'
                      || $this->type == 'hostgroup'
                      || $this->type == 'aggregation') {
                if (isset($this->state[ALIAS]))
                    $arr['alias'] = $this->state[ALIAS];
                else
                    $arr['alias'] = '';
            }

            // Add the custom htmlcgi path for the object
            $i = 0;
            foreach($this->backend_id as $backend_id) {
                if($i == 0) {
                    $arr['htmlcgi']  = cfg('backend_'.$backend_id, 'htmlcgi');
                    $arr['custom_1'] = cfg('backend_'.$backend_id, 'custom_1');
                    $arr['custom_2'] = cfg('backend_'.$backend_id, 'custom_2');
                    $arr['custom_3'] = cfg('backend_'.$backend_id, 'custom_3');
                } else {
                    $arr['htmlcgi_'.$i]  = cfg('backend_'.$backend_id, 'htmlcgi');
                    $arr['custom_1_'.$i] = cfg('backend_'.$backend_id, 'custom_1');
                    $arr['custom_2_'.$i] = cfg('backend_'.$backend_id, 'custom_2');
                    $arr['custom_3_'.$i] = cfg('backend_'.$backend_id, 'custom_3');
                }
                $i++;
            }

            // Little hack: Overwrite the options with correct state information
            $arr = array_merge($arr, $this->getObjectStateInformations(false));
        }

        // If there are some members fetch the information for them
        if(isset($arr['num_members']) && $arr['num_members'] > 0) {
            $members = Array();
            foreach($this->getSortedObjectMembers() AS $OBJ) {
                $members[] = $OBJ->fetchObjectAsChild();
            }
            $arr['members'] = $members;
        }

        return $arr;
    }

    /**
     * PUBLIC getSortedObjectMembers()
     *
     * Gets an array of member objects
     *
     * @return	Array		Member object information
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getSortedObjectMembers() {
        $arr = Array();

        $aTmpMembers = $this->getStateRelevantMembers();

        // Set the sort order
        self::$sSortOrder = $this->hover_childs_order;

        // Sort the array of child objects by the sort option
        switch($this->hover_childs_sort) {
            case 's':
                // Order by State
                usort($aTmpMembers, Array("NagVisObject", "sortObjectsByState"));
            break;
            case 'a':
            default:
                // Order alhpabetical
                usort($aTmpMembers, Array("NagVisObject", "sortObjectsAlphabetical"));
            break;
        }

        // Count only once, not in loop header
        $iNumObjects = count($aTmpMembers);

        // Loop all child object until all looped or the child limit is reached
        for($i = 0; $this->belowHoverChildsLimit($i) && $i < $iNumObjects; $i++) {
            $arr[] = $aTmpMembers[$i];
        }

        return $arr;
    }

    /**
     * PUBLIC getObjectConfiguration()
     *
     * Gets the configuration of the object
     *
     * @return	Array		Object configuration
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getObjectConfiguration($abstract = true) {
        // Some options have to be removed which are only for this object
        $arr = $this->conf;
        unset($arr['id']);
        unset($arr['object_id']);
        unset($arr['type']);

        // Only remove these options when the configuration should be
        // completely independent from this object
        if($abstract == true) {
            unset($arr['host_name']);
            unset($arr[$this->type.'_name']);
            unset($arr['service_description']);
        }

        return $arr;
    }

    /**
     * PUBLIC parseJson()
     *
     * Parses the object in json format
     *
     * @return	String  JSON code of the object
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parseJson() {
        return $this->getObjectInformation();
    }

    /**
     * PUBLIC parseMapCfg()
     *
     * Parses the object in map configuration format
     *
     * @param   Array   Array of global map options
     * @return	String  This object in map config format
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parseMapCfg($globalOpts = Array()) {
        $ret = 'define '.$this->type." {\n";
        if($this->type === 'host')
            $ret .= '  host_name='.$this->host_name."\n";
        $ret .= '  object_id='.$this->object_id."\n";
        foreach($this->getObjectConfiguration(false) AS $key => $val) {
            // Only set options which are different to global option
            if((!isset($globalOpts[$key]) || $globalOpts[$key] != $val) && $val != '') {
                $ret .= '  '.$key.'='.$val."\n";
            }
        }
        $ret .= "}\n\n";

        return $ret;
    }

    /**
     * PUBLIC getUrl()
     * Returns the url for the object link
     */
    public function getUrl() {
        if(isset($this->url)) {
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
     * @return	String	Target
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function getUrlTarget() {
        return $this->url_target;
    }

    /**
     * PRIVATE STATIC sortObjectsAlphabetical()
     *
     * Sorts both objects alphabetically by name
     *
     * @param	OBJ		First object to sort
     * @param	OBJ		Second object to sort
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private static function sortObjectsAlphabetical($OBJ1, $OBJ2) {
        if($OBJ1->type == 'service') {
            $name1 = strtolower($OBJ1->getName().$OBJ1->getServiceDescription());
        } else {
            $name1 = strtolower($OBJ1->getName());
        }

        if($OBJ2->type == 'service') {
            $name2 = strtolower($OBJ2->getName().$OBJ2->getServiceDescription());
        } else {
            $name2 = strtolower($OBJ2->getName());
        }

        if ($name1 == $name2) {
            return 0;
        } elseif($name1 > $name2) {
            // Sort depending on configured direction
            if(self::$sSortOrder === 'asc') {
                return +1;
            } else {
                return -1;
            }
        } else {
            // Sort depending on configured direction
            if(self::$sSortOrder === 'asc') {
                return -1;
            } else {
                return +1;
            }
        }
    }

    /**
     * PRIVATE STATIC sortObjectsByState()
     *
     * Sorts both by state of the object
     *
     * @param	OBJ		First object to sort
     * @param	OBJ		Second object to sort
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private static function sortObjectsByState($OBJ1, $OBJ2) {
        global $_MAINCFG;
        $state1 = $OBJ1->sum[STATE];
        $state2 = $OBJ2->sum[STATE];

        // Quit when nothing to compare
        if($state1 === null || $state2 === null) {
            return 0;
        }

        $stateWeight = $_MAINCFG->getStateWeight();

        // Handle normal/ack/downtime states

        $stubState1 = $OBJ1->getSubState(SUMMARY_STATE);
        $stubState2 = $OBJ2->getSubState(SUMMARY_STATE);

        if($stateWeight[$state1][$stubState1] == $stateWeight[$state2][$stubState2]) {
            return 0;
        } elseif($stateWeight[$state1][$stubState1] < $stateWeight[$state2][$stubState2]) {
            // Sort depending on configured direction
            if(self::$sSortOrder === 'asc') {
                return +1;
            } else {
                return -1;
            }
        } else {
            // Sort depending on configured direction
            if(self::$sSortOrder === 'asc') {
                return -1;
            } else {
                return +1;
            }
        }
    }
}
?>
