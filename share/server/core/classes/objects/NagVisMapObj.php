<?php
/*****************************************************************************
 *
 * NagVisMapObj.php - Class of a Map object in NagVis with all necessary
 *                  information which belong to the object handling in NagVis
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
 *
 * Modifications by Super-Visions BVBA
 * Copyright (c) 2010 Super-Visions BVBA (Contact: nagvis@super-visions.com)
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
class NagVisMapObj extends NagVisStatefulObject {
    protected $MAPCFG;
    private $MAP;

    protected static $langType = null;
    protected static $langSelf = null;
    protected static $langChild = null;

    protected $members;
    protected $linkedMaps;

    protected $map_name;
    protected $alias;

    protected $in_downtime;

    // When this map object summarizes the state of a map this is true
    // Prevents loops
    protected $isSummaryObject;

    // When this map points back to an earlier map and produces a loop
    // this option is set to true
    protected $isLoopingBacklink;

    // This controlls wether this is MapObj is used as view or as object on a map
    protected $isView;

    public function __construct($CORE, $BACKEND, $MAPCFG, $bIsView = IS_VIEW) {
        $this->MAPCFG = $MAPCFG;

        $this->map_name = $this->MAPCFG->getName();
        $this->alias = $this->MAPCFG->getAlias();
        $this->type = 'map';
        $this->iconset = 'std_medium';

        $this->linkedMaps = Array();
        $this->isSummaryObject = false;
        $this->isLoopingBacklink = false;
        $this->isView = $bIsView;

        $this->clearMembers();

        $this->backend_id = $this->MAPCFG->getValue(0, 'backend_id');

        parent::__construct($CORE, $BACKEND);
    }

    /**
     * PUBLIC clearMembers()
     *
     * Clears the map
     *
     * @return	Array	Array with map objects
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function clearMembers() {
        $this->members = Array();
    }

    /**
     * PUBLIC getMembers()
     *
     * Returns the array of objects on the map
     *
     * @return	Array	Array with map objects
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getMembers() {
        return $this->members;
    }

    /**
     * Adds several members to the map
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function addMembers($add) {
        $this->members = array_merge($this->members, $add);
    }

    /**
     * PUBLIC getStateRelevantMembers()
     *
     * Returns an array of state relevant members
     * textboxes, shapes and "summary objects" are
     * excluded here
     *
     * @return  Array Array with map objects
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getStateRelevantMembers($excludeMemberStates = false) {
        $a = Array();

        // Loop all members
        foreach($this->members AS $OBJ) {
            $sType = $OBJ->type;

            // Skip unrelevant object types
            if($sType == 'textbox' || $sType == 'shape' || $sType == 'line')
                continue;

            /**
             * When the current map object is a summary object skip the map
             * child for preventing a loop
             */
            if($sType == 'map' && $this->MAPCFG->getName() == $OBJ->MAPCFG->getName() && $this->isSummaryObject == true)
                continue;

            /**
             * All maps which produce a loop by linking back to earlier maps
             * need to be skipped here.
             */
            if($sType == 'map' && $OBJ->isLoopingBacklink)
                continue;

            /**
             * Exclude map objects based on "exclude_member_states" option
             */
            if($excludeMemberStates && $this->hasExcludeFilters(COUNT_QUERY)
               && $this->excludeMapObject($OBJ, COUNT_QUERY))
                continue;

            // Add relevant objects to array
            $a[] = $OBJ;
        }

        return $a;
    }

    /**
     * PUBLIC getNumMembers()
     *
     * Returns the number of objects on the map
     *
     * @return	Integer	Number of objects on the map
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getNumMembers() {
        return count($this->members);
    }

    /**
     * PUBLIC getNumiStatefulMembers()
     *
     * Returns the number of stateful objects on the map
     *
     * @return  Integer    Number of stateful objects on the map
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getNumStatefulMembers() {
        $i = 0;
        // Loop all objects except the stateless ones and count them
        foreach($this->members AS $OBJ) {
            $type = $OBJ->type;
            if($type != 'textbox' && $type != 'shape' && $type != 'line') {
                $i++;
            }
        }

        return $i;
    }

    /**
     * PUBLIC hasObjects()
     *
     * The fastest way I can expect to check if the map has objects
     *
     * @return	Boolean
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function hasObjects() {
        return isset($this->members[0]);
    }

    /**
     * PUBLIC hasObjects()
     *
     * The fastest way I can expect to check if the map has objects
     *
     * @return	Boolean
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function hasMembers() {
        return isset($this->members[0]);
    }

    /**
     * PUBLIC hasStatefulObjects()
     *
     * Check if the map has a stateful object on it
     *
     * @return	Boolean
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function hasStatefulObjects() {
        // Loop all objects on the map
        foreach($this->members AS $OBJ) {
            $type = $OBJ->getType();
            if($type != 'textbox' && $type != 'shape' && $type != 'line') {
                // Exit on first result
                return true;
            }
        }

        // No stateful object found
        return false;
    }

    /**
     * PUBLIC queueState()
     *
     * Queues fetching of the object state to the assigned backend. After all objects
     * are queued they can be executed. Then all fetched information gets assigned to
     * the single objects.
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function queueState($_unused_flag = true, $_unused_flag = true) {
        // Get state of all member objects
        foreach($this->getStateRelevantMembers() AS $OBJ) {
            // The states of the map objects members only need to be fetched when this
            // is MapObj is used as a view.
            if($this->isView === true) {
                $OBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
            } else {
                // Get the summary state of the host but not the single member states
                // Not needed cause no hover menu is displayed for this
                $OBJ->queueState(GET_STATE, DONT_GET_SINGLE_MEMBER_STATES);
            }
        }
    }

    /**
     * PUBLIC applyState()
     *
     * Apllies the object state after queueing and fetching by the backend.
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function applyState() {
        if($this->problem_msg) {
            $this->summary_state = 'ERROR';
            $this->summary_output = $this->problem_msg;
            $this->clearMembers();
            return;
        }

        // Get state of all member objects
        foreach($this->getStateRelevantMembers() AS $OBJ) {
            $OBJ->applyState();

            // The icon is only needed when this is a view
            if($this->isView === true)
                $OBJ->fetchIcon();
        }

        // Also get summary state
        $this->fetchSummaryState();

        // At least summary output
        $this->fetchSummaryOutput();

        $this->state = $this->summary_state;
    }

    /**
     * PUBLIC objectTreeToMapObjects()
     *
     * Links the object in the object tree to the map objects
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function objectTreeToMapObjects(&$OBJ, &$arrHostnames=Array()) {
        $this->members[] = $OBJ;

        foreach($OBJ->getChildsAndParents() AS $OBJ1) {
            /*
             * Check if the host is already on the map (If it's not done, the
             * objects with more than one parent will be printed several times on
             * the map, especially the links to child objects will be too many.
             */
            if(is_object($OBJ1) && !in_array($OBJ1->getName(), $arrHostnames)){
                // Add the name of this host to the array with hostnames which are
                // already on the map
                $arrHostnames[] = $OBJ1->getName();

                $this->objectTreeToMapObjects($OBJ1, $arrHostnames);
            }
        }
    }

    /**
     * Checks if the map is in maintenance mode
     *
     * @param 	Boolean	$printErr
     * @return	Boolean	Is Check Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function checkMaintenance($printErr) {
        if($this->MAPCFG->getValue(0, 'in_maintenance')) {
            if($printErr)
                throw new MapInMaintenance($this->getName());
            return false;
        }
        return true;
    }

    /**
     * Cares about excluding members of map objects. The other objects excludes
     * are handled by the backends, not in the NagVis code.
     */
    private function excludeMapObject($OBJ, $isCount) {
        // at the moment only handle the complete exclusion
        $filter  = $this->getExcludeFilter($isCount);
        $objType = $OBJ->getType();
        $parts   = explode('~~', $filter);

        // Never exclude stateless objects
        if($objType == 'textbox' || $objType == 'shape' || $objType == 'line')
            return false;
    
        if(isset($parts[1]) && $objType == 'service'
           && preg_match('/'.$parts[0].'/', $OBJ->getName())
           && preg_match('/'.$parts[1].'/', $OBJ->getServiceDescription()))
            return true;

        if(!isset($parts[1]) && preg_match('/'.$parts[0].'/', $OBJ->getName()))
            return true;

        return false;
    }

    /**
     * Gets all objects of the map
     *
     * @author	Thomas Casteleyn <thomas.casteleyn@super-visions.com>
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function fetchMapObjects(&$arrMapNames = Array(), $depth = 0) {
        foreach($this->MAPCFG->getMapObjects() AS $objConf) {
            $type = $objConf['type'];

            if($type == 'global' || $type == 'template')
                continue;

            $typeDefs = $this->MAPCFG->getTypeDefaults($type);

            // merge with "global" settings
            foreach($typeDefs AS $key => $default)
                if(!isset($objConf[$key]))
                    $objConf[$key] = $default;

            switch($type) {
                case 'host':
                    $OBJ = new NagVisHost($this->CORE, $this->BACKEND, $objConf['backend_id'], $objConf['host_name']);
                break;
                case 'service':
                    $OBJ = new NagVisService($this->CORE, $this->BACKEND, $objConf['backend_id'], $objConf['host_name'], $objConf['service_description']);
                break;
                case 'hostgroup':
                    $OBJ = new NagVisHostgroup($this->CORE, $this->BACKEND, $objConf['backend_id'], $objConf['hostgroup_name']);
                break;
                case 'servicegroup':
                    $OBJ = new NagVisServicegroup($this->CORE, $this->BACKEND, $objConf['backend_id'], $objConf['servicegroup_name']);
                break;
                case 'map':
                    // Initialize map configuration based on map type
                    if($this->CORE->checkMapIsAutomap($objConf['map_name'])) {
                        $SUBMAPCFG = new NagVisAutomapCfg($this->CORE, $objConf['map_name']);

                        // Override the default map url for the automaps
                        $objConf['url'] = str_replace('mod=Map', 'mod=AutoMap', $objConf['url']);
                    } else
                        $SUBMAPCFG = new NagVisMapCfg($this->CORE, $objConf['map_name']);

                    $mapCfgInvalid = null;
                    if($SUBMAPCFG->checkMapConfigExists(0)) {
                        try {
                            $SUBMAPCFG->readMapConfig();
                        } catch(MapCfgInvalid $e) {
                            $mapCfgInvalid = l('Map Configuration Error: [ERR]', Array('ERR' => $e->getMessage()));
                        }
                    }

                    if($this->CORE->checkMapIsAutomap($objConf['map_name'])) {
                        $MAP = new NagVisAutoMap($this->CORE, $SUBMAPCFG, $this->BACKEND, Array(), !IS_VIEW);
                        $OBJ = $MAP->MAPOBJ;
                    } else
                        $OBJ = new NagVisMapObj($this->CORE, $this->BACKEND, $SUBMAPCFG, !IS_VIEW);

                    if($mapCfgInvalid)
                        $OBJ->setProblem($mapCfgInvalid);

                    if(!$SUBMAPCFG->checkMapConfigExists(0))
                        $OBJ->setProblem(l('mapCfgNotExists', 'MAP~'.$objConf['map_name']));

                    /**
                    * When the current map object is a summary object skip the map
                    * child for preventing a loop
                    */
                    if($this->MAPCFG->getName() == $SUBMAPCFG->getName() && $this->isSummaryObject == true)
                        continue 2;

                    /**
                    * This occurs when someone creates a map icon which links to itself
                    *
                    * The object will be marked as summary object and is ignored on next level.
                    * See the code above.
                    */
                    if($this->MAPCFG->getName() == $SUBMAPCFG->getName())
                        $OBJ->isSummaryObject = true;

                    /**
                     * All maps which were seen before are stored in the list once. If
                     * they are already in the list and depth is more than 3 levels,
                     * skip them to prevent loops.
                     */
                    if(isset($arrMapNames[$SUBMAPCFG->getName()]) && ($depth > 3)) {
                        $OBJ->isLoopingBacklink = true;
                        continue 2;
                    }

                    // Store this map in the mapNames list
                    $arrMapNames[$SUBMAPCFG->getName()] = true;

                    // Skip this map when the user is not permitted toview this map
                    if(!$this->isPermitted($OBJ)) {
                        continue 2;
                    }
                break;
                case 'shape':
                    $OBJ = new NagVisShape($this->CORE, $objConf['icon']);
                break;
                case 'textbox':
                    $OBJ = new NagVisTextbox($this->CORE);
                break;
                case 'line':
                    $OBJ = new NagVisLine($this->CORE);
                break;
                default:
                    throw new NagVisException(l('unknownObject',
                                              Array('TYPE'    => $type,
                                                    'MAPNAME' => $this->getName())));
                break;
            }

            // Apply default configuration to object
            $OBJ->setConfiguration($objConf);

            // Skip object by exclude filter? => Totally exclude (exclude_members)
            if($this->hasExcludeFilters(!COUNT_QUERY) && $this->excludeMapObject($OBJ, !COUNT_QUERY))
                continue;

            // Write member to object array
            $this->members[] = $OBJ;
        }

        // Now dig into the next map level. This has to be done here to fight
        // the loops at this level and not at the single branches of map links.
        foreach($this->members AS $OBJ) {
            $sType = $OBJ->type;

            if($sType == 'map') {
                /**
                * When the current map object is a summary object skip the map
                * child for preventing a loop
                */
                if($sType == 'map' && $this->MAPCFG->getName() == $OBJ->MAPCFG->getName() && $this->isSummaryObject == true)
                    continue;

                /**
                    * All maps which produce a loop by linking back to earlier maps
                    * need to be skipped here.
                    */
                if($sType == 'map' && $OBJ->isLoopingBacklink)
                    continue;

                if(!$OBJ->hasProblem())
                    $OBJ->fetchMapObjects($arrMapNames, $depth+1);
            }
        }
    }

    # End public methods
    # #########################################################################

    /**
     * PRIVATE fetchSummaryOutput()
     *
     * Fetches the summary output of the map
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function fetchSummaryOutput() {
        if($this->hasObjects() && $this->hasStatefulObjects()) {
            $arrStates = Array('UNREACHABLE' => 0, 'CRITICAL' => 0, 'DOWN' => 0,
                               'WARNING'     => 0, 'UNKNOWN'  => 0, 'UP'   => 0,
                               'OK'          => 0, 'ERROR'    => 0, 'ACK'  => 0,
                               'PENDING'     => 0);

            foreach($this->getStateRelevantMembers(true) AS $OBJ)
                if(isset($arrStates[$OBJ->summary_state]))
                    $arrStates[$OBJ->summary_state]++;

            $this->mergeSummaryOutput($arrStates, l('objects'));
        } else {
            $this->summary_output = l('mapIsEmpty','MAP~'.$this->getName());
        }
    }

    /**
     * PRIVATE isPermitted()
     *
     * check for permissions to view the state of the map
     *
     * @param		Object		Map object to check
     * @return	Boolean		Permitted/Not permitted
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function isPermitted($OBJ) {
        if($this->CORE->getAuthorization() !== null
           && $this->CORE->getAuthorization()->isPermitted('Map', 'view', $OBJ->getName()))
            return true;
        else {
            $OBJ->summary_state = 'UNKNOWN';
            $OBJ->summary_output = l('noReadPermissions');

            return false;
        }
    }

    /**
     * PUBLIC fetchSummaryState()
     *
     * Fetches the summary state of the map object and all members/childs
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function fetchSummaryState() {
        // Get summary state of this object from single objects
        if($this->hasObjects() && $this->hasStatefulObjects())
            $this->wrapChildState($this->getStateRelevantMembers(true));
        else
            $this->summary_state = 'UNKNOWN';
    }
}
?>
