<?php
/*****************************************************************************
 *
 * NagVisStatefulObject.php - Abstract class of a stateful object in NagVis
 *                  with all necessary information which belong to the object
 *                  handling in NagVis
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
class NagVisStatefulObject extends NagVisObject {
    // "Global" Configuration variables for all stateful objects
    protected $backend_id;
    protected $problem_msg = null;

    protected $label_show;
    protected $recognize_services;
    protected $only_hard_states;

    protected $line_type;

    // Details about the icon image (cache)
    protected $iconDetails;

    protected static $iconPath         = null;
    protected static $iconPathLocal    = null;
    protected static $langMemberStates = null;
    protected static $dateFormat       = null;

    protected $state = null;

    public $sum = array(null, null, null, null, null);
    protected $aStateCounts = null;

    /**
     * Sets the state of the object
     */
    public function setState($arr) {
        $this->state = $arr;
    }

    public function getStateAttr($attr) {
        return val($this->state, $attr);
    }

    /**
     * Adds state counts of members to the object. Works incremental!
     */
    public function addStateCounts($arr) {
        if($this->aStateCounts === null) {
            $this->aStateCounts = $arr;
        } else {
            // Add new state counts to current ones
            foreach($arr AS $state => $substates)
                foreach($substates AS $substate => $num)
                    $this->aStateCounts[$state][$substate] += $num;
        }
    }

    /**
     * Adds new members to the object. It works incremental!
     */
    public function addMembers($arr) {
        $this->members = array_merge($this->members, $arr);
    }

    /**
     * Returns the number of member objects
     */
    public function getNumMembers() {
        return count($this->members);
    }

    /**
     * Returns the member objects
     */
    public function getMembers() {
        return $this->members;
    }

    /**
     * Simple check if the object has at least one member
     */
    public function hasMembers() {
        return isset($this->members[0]);
    }

    /**
     * PUBLIC getStateRelevantMembers
     *
     * This is a wrapper function. When not implemented by the specific
     * object it only calls the getMembers() function. It is useful to
     * exclude uninteresting objects on maps.
     *
     * @return  Array  Array of child objects
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getStateRelevantMembers() {
        return $this->getMembers();
    }

    /**
     * Method to get details about the used icon image.
     * This is mainly a wrapper arround getimagesize with caching code.
     *
     * @return  Array  Attributes/Details about the image
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getIconDetails() {
        if($this->iconDetails == null)
            $this->iconDetails = getimagesize(NagVisStatefulObject::$iconPath . $this->icon);
        return $this->iconDetails;
    }

    /**
     * Get method for the in downtime option
     */
    public function getSummaryInDowntime() {
        return $this->sum[DOWNTIME];
    }

    /**
     * PUBLIC getOnlyHardStates()
     *
     * Get method for the only hard states option
     *
     * @return	Boolean		True: Only hard states, False: Not only hard states
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getOnlyHardStates() {
        return $this->only_hard_states;
    }

    /**
     * PUBLIC getRecognizeServices()
     *
     * Get method for the recognize services option
     *
     * @return	Boolean		True: Recognize service states, False: Not recognize service states
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getRecognizeServices() {
        return $this->recognize_services;
    }

    /**
     * Returns the array of all backend_ids
     */
    public function getBackendIds() {
        return $this->backend_id;
    }

    /**
     * PUBLIC getSummaryState()
     *
     * Get method for the summary state of this object and members/childs
     *
     * @return	String		Summary state
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getSummaryState() {
        return $this->sum[STATE];
    }

    public function isStale($summary = false) {
        if ($summary) {
            return $this->sum[STALE] > cfg('global', 'staleness_threshold');
        } else {
            return val($this->state, STALE, 0) > cfg('global', 'staleness_threshold');
        }
    }

    /**
     * Returns the current sub-state of the object
     */
    public function getSubState($summary = false) {
        if($summary) {
            if($this->sum[ACK] == 1)
                return  'ack';
            elseif($this->sum[DOWNTIME] == 1)
                return 'downtime';
            elseif($this->isStale($summary))
                return 'stale';
        } else {
            if($this->state[ACK] == 1)
                return  'ack';
            elseif($this->state[DOWNTIME] == 1)
                return 'downtime';
            elseif($this->isStale($summary))
                return 'stale';
        }
        return 'normal';
    }

    /**
     * PUBLIC setSummaryState()
     *
     * Set the summary state of the object
     *
     * @return	String		Summary state
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setSummaryState($s) {
        $this->sum[STATE] = $s;
    }

    /**
     * PUBLIC getSummaryOutput()
     *
     * Get method for the summary output of this object and members/childs
     *
     * @return	String		Summary output
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getSummaryOutput() {
        return $this->sum[OUTPUT];
    }

    /**
     * PUBLIC getSummaryAcknowledgement()
     *
     * Get method for the acknowledgement state of this object and members/childs
     *
     * @return	Boolean		True: Acknowledged, False: Not Acknowledged
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getSummaryAcknowledgement() {
        return $this->sum[ACK];
    }

    /**
     * PUBLIC getStateDuration()
     *
     * Get method for the duration of the current state
     *
     * @return	String		Time in the configured format
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getStateDuration() {
        if(isset($this->state['last_state_change']) && $this->state['last_state_change'] != '0'
           && $this->state['last_state_change'] != '') {
            if(self::$dateFormat == '') {
                self::$dateFormat = cfg('global','dateformat');
            }

            return date(self::$dateFormat, ($_SERVER['REQUEST_TIME'] - $this->state['last_state_change']));
        } else {
            return 'N/A';
        }
    }

    /**
     * Returns state timestamp as human readable date
     */
    public function get_date($attr) {
        if(isset($this->state[$attr]) && $this->state[$attr] != '0' && $this->state[$attr] != '') {
            if(self::$dateFormat == '') {
                self::$dateFormat = cfg('global','dateformat');
            }
            return date(self::$dateFormat, $this->state[$attr]);
        } else {
            return 'N/A';
        }
    }

    /**
     * PUBLIC getStateType()
     *
     * Get method for the type of the current state
     *
     * @return	String		Type of state (HARD/SOFT)
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getStateType() {
        if(isset($this->state[STATE_TYPE]) && $this->state[STATE_TYPE] != '') {
            $stateTypes = Array(0 => 'SOFT', 1 => 'HARD');
            return $stateTypes[$this->state[STATE_TYPE]];
        } else {
            return 'N/A';
        }
    }

    public function hasExcludeFilters($isCount) {
        // When this is a count use both options exclude_members and
        // exclude_member_states
        if($isCount)
            return (isset($this->exclude_members) && $this->exclude_members !== '')
                   || (isset($this->exclude_member_states) && $this->exclude_member_states !== '');
        else
            return isset($this->exclude_members) && $this->exclude_members !== '';
    }

    public function getExcludeFilter($isCount) {
        // When this is a count use the exclude_member_states over the 
        // exclude_members
        $key = $this->getExcludeFilterKey($isCount);
        if($key == 'exclude_member_states')
            return $this->exclude_member_states;
        if($key == 'exclude_members')
            return $this->exclude_members;
        else
            return '';
    }

    public function getExcludeFilterKey($isCount) {
        // When this is a count use the exclude_member_states over the 
        // exclude_members
        if($isCount && $this->exclude_member_states !== '')
            return 'exclude_member_states';
        elseif($this->exclude_members !== '')
            return 'exclude_members';
        else
            return '';
    }

    /**
     * PUBLIC getObjectStateInformations()
     *
     * Gets the state information of the object
     *
     * @return	Array		Object configuration
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getObjectStateInformations($bFetchChilds=true) {
        $arr = Array();

        $arr['state']                         = state_str(val($this->state, STATE));
        $arr['problem_has_been_acknowledged'] = val($this->state, ACK);
        $arr['in_downtime']                   = val($this->state, DOWNTIME);
        $arr['stale']                         = $this->isStale(false);
        $arr['output']         = $this->escapeStringForJson(val($this->state, OUTPUT, ''));

        $arr['summary_state']                 = state_str(val($this->sum, STATE));
        $arr['summary_problem_has_been_acknowledged'] = val($this->sum, ACK);
        $arr['summary_in_downtime']           = val($this->sum, DOWNTIME);
        $arr['summary_stale']                 = $this->isStale(true);
        $arr['summary_output']                = $this->escapeStringForJson(val($this->sum, OUTPUT, ''));

        // Macros which are only for services and hosts
        if($this->type == 'host' || $this->type == 'service') {
            $arr['custom_variables'] = val($this->state, CUSTOM_VARS);

            // Add (Check_MK) tags as array of tags (when available)
            if (isset($arr['custom_variables']['TAGS']))
                $arr['tags'] = explode(' ', $arr['custom_variables']['TAGS']);
            else
                $arr['tags'] = array();

            // Now, to be very user friendly, we now try to use the Check_MK WATO php-api to gather
            // titles and grouping information of the tags. These can, for example, be used in the hover
            // templates. This has been implemented to only work in OMD environments.
            $arr['taggroups'] = array();
            if ($arr['tags'] && isset($_SERVER['OMD_ROOT'])) {
                $path = $_SERVER['OMD_ROOT'] . '/var/check_mk/wato/php-api/hosttags.php';
                if (file_exists($path)) {
                    require_once($path);
                    $arr['taggroups'] = all_taggroup_choices($arr['tags']);
                }
            }

            $arr['downtime_author'] = val($this->state, DOWNTIME_AUTHOR);
            $arr['downtime_data']   = val($this->state, DOWNTIME_DATA);
            $arr['downtime_start']  = val($this->state, DOWNTIME_START);
            $arr['downtime_end']    = val($this->state, DOWNTIME_END);

            $arr['last_check'] = $this->get_date(LAST_CHECK);
            $arr['next_check'] = $this->get_date(NEXT_CHECK);
            $arr['state_type'] = $this->getStateType();
            $arr['current_check_attempt']  = val($this->state, CURRENT_ATTEMPT);
            $arr['max_check_attempts']     = val($this->state, MAX_CHECK_ATTEMPTS);
            $arr['last_state_change']      = $this->get_date(LAST_STATE_CHANGE);
            $arr['last_hard_state_change'] = $this->get_date(LAST_HARD_STATE_CHANGE);
            $arr['state_duration']         = $this->getStateDuration();
            $arr['perfdata'] = $this->escapeStringForJson(val($this->state, PERFDATA, ''));
        }

        // Enable/Disable fetching children
        $arr['members'] = Array();
        if($bFetchChilds && method_exists($this, 'getMembers'))
            foreach($this->getSortedObjectMembers() AS $OBJ)
                $arr['members'][] = $OBJ->fetchObjectAsChild();

        $arr['num_members'] = $this->getNumMembers();

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
        // Get the correct url
        $this->url = $this->getUrl();

        // When this is a gadget parse the url
        if($this->view_type == 'gadget') {
            $this->parseGadgetUrl();
        }

        // Get all information of the object (configuration, state, ...)
        return parent::parseJson();
    }

    /**
     * PUBLIC fetchIcon()
     *
     * Fetches the icon for the object depending on the summary state
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function fetchIcon() {
        // Set the paths of this iconset
        if(NagVisStatefulObject::$iconPath === null) {
            NagVisStatefulObject::$iconPath      = path('sys',  'global', 'icons');
            NagVisStatefulObject::$iconPathLocal = path('sys',  'local',  'icons');
        }

        // Read the filetype of the iconset
        global $CORE;
        $fileType = $CORE->getIconsetFiletype($this->iconset);

        if ($this->sum[STATE] !== null) {
            $stateLow = strtolower(state_str($this->sum[STATE]));
            switch($stateLow) {
                case 'unknown':
                case 'unreachable':
                case 'down':
                case 'critical':
                case 'warning':
                    if($this->sum[ACK]) {
                        $icon = $this->iconset.'_'.$stateLow.'_ack.'.$fileType;
                    } elseif($this->sum[DOWNTIME]) {
                        $icon = $this->iconset.'_'.$stateLow.'_dt.'.$fileType;
                    } elseif($this->isStale(true)) {
                        $icon = $this->iconset.'_'.$stateLow.'_stale.'.$fileType;
                    } else {
                        $icon = $this->iconset.'_'.$stateLow.'.'.$fileType;
                    }
                break;
                case 'up':
                case 'ok':
                    if($this->sum[DOWNTIME]) {
                        $icon = $this->iconset.'_'.$stateLow.'_dt.'.$fileType;
                    } elseif($this->isStale(true)) {
                        $icon = $this->iconset.'_'.$stateLow.'_stale.'.$fileType;
                    } else {
                        $icon = $this->iconset.'_'.$stateLow.'.'.$fileType;
                    }
                break;
                case 'unchecked':
                case 'pending':
                    $icon = $this->iconset.'_'.$stateLow.'.'.$fileType;
                break;
                default:
                    $icon = $this->iconset.'_error.'.$fileType;
                break;
            }

            //Checks whether the needed file exists
            if(@file_exists(NagVisStatefulObject::$iconPath . $icon)
               || @file_exists(NagVisStatefulObject::$iconPathLocal . $icon)) {
                $this->icon = $icon;
            } else {
                $this->icon = $this->iconset.'_error.'.$fileType;
            }
        } else {
            $this->icon = $this->iconset.'_error.'.$fileType;
        }
    }

    /**
     * Is used to build a state filter for the backend when fetching child
     * objects for the hover menu child list. If the childs should be sorted
     * by state it may be possible to limit the number of requested objects
     * dramatically when using the state filters.
     */
    public function getChildFetchingStateFilters() {
        global $_MAINCFG;
        $stateCounts = Array();
        $stateWeight = $_MAINCFG->getStateWeight();

        if($this->aStateCounts !== null) {
            foreach($this->aStateCounts AS $sState => $aSubstates) {
                if(isset($stateWeight[$sState])
                    && isset($stateWeight[$sState]['normal'])
                    && isset($aSubstates['normal'])
                    && $aSubstates['normal'] !== 0) {
                    $stateCounts[] = Array('name'   => $sState,
                                           'weight' => $stateWeight[$sState]['normal'],
                                           'count'  => $aSubstates['normal']);
                }
            }
        }
        NagVisObject::$sSortOrder = $this->hover_childs_order;
        usort($stateCounts, Array("NagVisStatefulObject", "sortStateCountsByState"));

        $objCount = 0;
        $stateFilter = Array();
        foreach($stateCounts AS $aState) {
            $stateFilter[] = $aState['name'];
            if(($objCount += $aState['count']) >= $this->hover_childs_limit)
                break;
        }

        return $stateFilter;
    }

    /**
     * Sets output/state on backend problems
     */
    public function setBackendProblem($s, $backendId = null) {
        if($backendId === null)
            $backendId = $this->backend_id[0];
        $this->problem_msg = l('Problem (Backend: [BACKENDID]): [MSG]',
                               Array('BACKENDID' => $backendId, 'MSG' => $s));
    }

    /**
     * Sets output/state on object handling problems
     */
    public function setProblem($s) {
        $this->problem_msg = l('Problem: [MSG]', Array('MSG' => $s));
    }

    public function hasProblem() {
        return $this->problem_msg !== null;
    }

    # End public methods
    # #########################################################################

    private static function sortStateCountsByState($a1, $a2) {
        if($a1['weight'] == $a2['weight']) {
            return 0;
        } elseif($a1['weight'] < $a2['weight']) {
            if(NagVisObject::$sSortOrder === 'asc') {
                return +1;
            } else {
                return -1;
            }
        } else {
            if(NagVisObject::$sSortOrder === 'asc') {
                return -1;
            } else {
                return +1;
            }
        }
    }

    /**
     * PROTECTED belowHoverChildsLimit()
     *
     * Checks if the current count is below the hover childs limit
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function belowHoverChildsLimit($i) {
        return (($this->hover_childs_limit >= 0 && $i <= $this->hover_childs_limit) || $this->hover_childs_limit == -1);
    }

    /**
     * Escapes special chars in a string for putting it to a json string
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    protected function escapeStringForJson($s) {
        return strtr($s, Array("\r" => '<br />',
                               "\n" => '<br />',
                               '"'  => '&quot;',
                               '\'' => '&#145;',
                               '$'  => '&#36;'));
    }


    /**
     * PROTECTED fetchObjectAsChild()
     *
     * Is called when an object should only be displayed as child
     * e.g. in hover menus. There are much less macros needed for this.
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function fetchObjectAsChild() {
        $aChild = Array(
            'type'                => $this->getType(),
            'name'                => $this->getName(),
            'summary_state'       => state_str($this->sum[STATE]),
            'summary_in_downtime' => $this->sum[DOWNTIME],
            'summary_problem_has_been_acknowledged' => $this->sum[ACK],
            'summary_stale'       => $this->isStale(true),
            'summary_output'      => $this->escapeStringForJson($this->sum[OUTPUT])
        );

        if($this->type == 'service') {
            $aChild['service_description'] = $this->getServiceDescription();
        }

        return $aChild;
    }

    /**
     * Sets the path of gadget_url. The method adds htmlgadgets path when relative
     * path or will remove [] when full url given
     */
    protected function parseGadgetUrl() {
        if(preg_match('/^\[(.*)\]$/',$this->gadget_url,$match) > 0)
            $this->gadget_url = $match[1];
        else
            $this->gadget_url = path('html', 'global', 'gadgets', $this->gadget_url);
    }

    /**
     * PROTECTED fetchSummaryStateFromCounts()
     *
     * Fetches the summary state from the member state counts
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function fetchSummaryStateFromCounts() {
        global $_MAINCFG;
        $stateWeight = $_MAINCFG->getStateWeight();

        // Fetch the current state to start with
        if($this->sum[STATE] == null) {
            $currWeight = null;
        } else {
            if(isset($stateWeight[$this->sum[STATE]])) {
                $currWeight = $stateWeight[$this->sum[STATE]][$this->getSubState(SUMMARY_STATE)];
            } else {
                throw new NagVisException(l('Invalid state+substate ([STATE], [SUBSTATE]) found while loading the current summary state of an object of type [TYPE].',
                                            Array('STATE'    => $this->sum[STATE],
                                                  'SUBSTATE' => $this->getSubState(SUMMARY_STATE),
                                                  'TYPE'     => $this->getType())));
            }
        }

        // Loop all major states
        $iSumCount = 0;
        if($this->aStateCounts !== null) {
            foreach($this->aStateCounts AS $sState => $aSubstates) {
                // Loop all substates (normal,ack,downtime,...)
                foreach($aSubstates AS $sSubState => $iCount) {
                    if($iCount === 0)
                        continue;

                    // Count all child objects
                    $iSumCount += $iCount;

                    // Get weight
                    if(isset($stateWeight[$sState]) && isset($stateWeight[$sState][$sSubState])) {
                        $weight = $stateWeight[$sState][$sSubState];

                        // No "current state" yet
                        // The new state is worse than the current state
                        if($currWeight === null || $currWeight < $weight) {
                            // Set the new weight for next compare
                            $currWeight = $weight;

                            // Modify the summary information

                            $this->sum[STATE] = $sState;

                            if($sSubState == 'ack') {
                                $this->sum[ACK] = 1;
                            } else {
                                $this->sum[ACK] = 0;
                            }

                            if($sSubState == 'downtime') {
                                $this->sum[DOWNTIME] = 1;
                            } else {
                                $this->sum[DOWNTIME] = 0;
                            }

                            if($sSubState == 'stale') {
                                $this->sum[STALE] = 1;
                            } else {
                                $this->sum[STALE] = 0;
                            }
                        }
                    } else {
                        throw new NagVisException(l('Invalid state+substate ([STATE], [SUBSTATE]) found on state comparision in an object of type [TYPE] named [NAME].',
                                                    Array('STATE'    => $sState,
                                                          'SUBSTATE' => $sSubState,
                                                          'TYPE'     => $this->getType(),
                                                          'NAME'     => $this->getName())));
                    }
                }
            }
        }

        // Fallback for objects without state and without members
        if($this->sum[STATE] == null && $iSumCount == 0) {
            $this->sum[STATE] = ERROR;
        }
    }

    /**
     * Merges the summary output from objects and all child objects together
     */
    protected function mergeSummaryOutput($arrStates, $objLabel, $finish = true, $continue = false) {
        if(NagVisStatefulObject::$langMemberStates === null)
            NagVisStatefulObject::$langMemberStates = l('Contains');

        if($this->sum[OUTPUT] === null)
            $this->sum[OUTPUT] = '';

        if (!$continue)
            $this->sum[OUTPUT] .= NagVisStatefulObject::$langMemberStates.' ';

        foreach($arrStates AS $state => $num)
            if($num > 0)
                $this->sum[OUTPUT] .= $num.' '.state_str($state).', ';

        // If some has been added remove last comma, else add a simple 0
        if(substr($this->sum[OUTPUT], -2, 2) == ', ')
            $this->sum[OUTPUT] = rtrim($this->sum[OUTPUT], ', ');
        else
            $this->sum[OUTPUT] .= '0 ';

        $this->sum[OUTPUT] .= ' '.$objLabel;
        if ($finish)
            $this->sum[OUTPUT] .=  '.';
    }

    /**
     * Loops all member objects to calculate the summary state
     */
    protected function calcSummaryState($objects = null) {
        global $_MAINCFG;
        $stateWeight = $_MAINCFG->getStateWeight();

        // Initialize empty or with current object state
        $currentStateWeight = null;
        if($this->sum[STATE] != null)
            $currentStateWeight = $stateWeight[$this->sum[STATE]][$this->getSubState(SUMMARY_STATE)];

        if ($objects === null)
            $objects = $this->members;

        // Loop all object to gather the worst state and set it as summary
        // state of the current object
        foreach($objects AS $OBJ) {
            $objSummaryState = $OBJ->sum[STATE];
            $objAck          = $OBJ->sum[ACK];
            $objDowntime     = $OBJ->sum[DOWNTIME];
            $objStale        = $OBJ->sum[STALE];

            if(isset($stateWeight[$objSummaryState])) {
                // Gather the object summary state type
                $objType = 'normal';
                if($objAck == 1 && isset($stateWeight[$objSummaryState]['ack']))
                    $objType = 'ack';
                elseif($objDowntime == 1 && isset($stateWeight[$objSummaryState]['downtime']))
                    $objType = 'downtime';
                elseif($objStale == 1 && isset($stateWeight[$objSummaryState]['stale']))
                    $objType = 'stale';

                if(isset($stateWeight[$objSummaryState][$objType])
                   && ($currentStateWeight === null || $stateWeight[$objSummaryState][$objType] >= $currentStateWeight)) {
                    $this->sum[STATE]    = $objSummaryState;
                    $this->sum[ACK]      = $objAck;
                    $this->sum[DOWNTIME] = $objDowntime;
                    $this->sum[STALE]    = $objStale;
                    $currentStateWeight  = $stateWeight[$objSummaryState][$objType];
                }
            }
        }
    }
}
?>
