<?php
/*****************************************************************************
 *
 * NagVisStatefulObject.php - Abstract class of a stateful object in NagVis
 *                  with all necessary information which belong to the object
 *                  handling in NagVis
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
class NagVisStatefulObject extends NagVisObject {
    // "Global" Configuration variables for all stateful objects
    protected $backend_id;
    protected $problem_msg = null;

    protected $label_show;
    protected $recognize_services;
    protected $only_hard_states;

    protected $line_type;
    protected $line_arrow = 'none';

    // Highly used and therefor public to prevent continous getter calls
    public $summary_state = null;
    protected $summary_output = null;
    protected $summary_problem_has_been_acknowledged = 0;
    protected $summary_in_downtime = 0;

    // Details about the icon image (cache)
    protected $iconDetails;

    protected static $iconPath        = null;
    protected static $iconPathLocal   = null;
    protected static $langChildStates = null;
    protected static $dateFormat      = null;

    protected $state = null;
    protected $aStateCounts = null;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Sets the state of the object
     */
    public function setState($arr) {
        $this->state = $arr;
    }

    /**
     * PUBLIC setStateCounts()
     *
     * Sets the state counts of the object members
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setStateCounts($arr) {
        $this->aStateCounts = $arr;
    }

    /**
     * PUBLIC setMembers()
     *
     * Adds a new list of members to the object
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function setMembers($arr) {
        $this->members = $arr;
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
     * Simple check if the hostgroup has at least one member
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
     * PUBLIC getDowntimeAuthor()
     *
     * Get method for the in downtime author
     *
     * @return	String		The username of the downtime author
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getDowntimeAuthor() {
        return $this->state['downtime_author'];
    }

    /**
     * PUBLIC getDowntimeData()
     *
     * Get method for the in downtime data
     *
     * @return	String		The downtime data
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getDowntimeData() {
        return $this->state['downtime_data'];
    }

    /**
     * PUBLIC getDowntimeStart()
     *
     * Get method for the in downtime start time
     *
     * @return	String		The formated downtime start time
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getDowntimeStart() {
        if(isset($this->state['in_downtime']) && $this->state['in_downtime'] == 1) {
            if(self::$dateFormat === null) {
                self::$dateFormat = cfg('global','dateformat');
            }

            return date(self::$dateFormat, $this->state['downtime_start']);
        } else {
            return 'N/A';
        }
    }

    /**
     * PUBLIC getDowntimeEnd()
     *
     * Get method for the in downtime end time
     *
     * @return	String		The formated downtime end time
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getDowntimeEnd() {
        if(isset($this->state['in_downtime']) && $this->state['in_downtime'] == 1) {
            if(self::$dateFormat === null) {
                self::$dateFormat = cfg('global','dateformat');
            }

            return date(self::$dateFormat, $this->state['downtime_end']);
        } else {
            return 'N/A';
        }
    }

    /**
     * Get method for the in downtime option
     */
    public function getSummaryInDowntime() {
        return $this->summary_in_downtime;
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
     * PUBLIC getBackendId()
     *
     * Get method for the backend_id of this object
     *
     * @return	String		Output of the object
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getBackendId() {
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
        return $this->summary_state;
    }

    /**
     * Returns the current sub-state of the object
     */
    public function getSubState($summary = false) {
        if($summary) {
            if($this->summary_problem_has_been_acknowledged == 1)
                return  'ack';
            elseif($this->summary_in_downtime == 1)
                return 'downtime';
        } else {
            if($this->state['problem_has_been_acknowledged'] == 1)
                return  'ack';
            elseif($this->state['in_downtime'] == 1)
                return 'downtime';
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
        $this->summary_state = $s;
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
        return $this->summary_output;
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
        return $this->summary_problem_has_been_acknowledged;
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
        if(isset($this->state['state_type']) && $this->state['state_type'] != '') {
            $stateTypes = Array(0 => 'SOFT', 1 => 'HARD');
            return $stateTypes[$this->state['state_type']];
        } else {
            return 'N/A';
        }
    }

    /**
     * PUBLIC getCurrentCheckAttempt()
     *
     * Get method for the current check attempt
     *
     * @return	Integer		Current check attempt
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getCurrentCheckAttempt() {
        if(isset($this->state['current_check_attempt']) && $this->state['current_check_attempt'] != '') {
            return $this->state['current_check_attempt'];
        } else {
            return '';
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
        // exclude_member_states
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
        // exclude_member_states
        if($isCount && $this->exclude_member_states !== '')
            return 'exclude_member_states';
        elseif($this->exclude_members !== '')
            return 'exclude_members';
        else
            return '';
    }

    /**
     * PUBLIC getMaxCheckAttempts()
     *
     * Get method for the maximum check attempt
     *
     * @return	Integer		maximum check attempts
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getMaxCheckAttempts() {
        if(isset($this->state['max_check_attempts']) && $this->state['max_check_attempts'] != '') {
            return $this->state['max_check_attempts'];
        } else {
            return '';
        }
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

        $arr['state']                         = val($this->state, 'state');
        $arr['summary_state']                 = $this->getSummaryState();
        $arr['summary_problem_has_been_acknowledged'] = $this->getSummaryAcknowledgement();
        $arr['problem_has_been_acknowledged'] = val($this->state, 'problem_has_been_acknowledged');
        $arr['summary_in_downtime']           = $this->getSummaryInDowntime();
        $arr['in_downtime']                   = val($this->state, 'in_downtime');

        $arr['output']         = $this->escapeStringForJson(val($this->state, 'output', ''));
        $arr['summary_output'] = $this->escapeStringForJson($this->getSummaryOutput());

        // Macros which are only for services and hosts
        if($this->type == 'host' || $this->type == 'service') {
            $arr['downtime_author'] = val($this->state, 'downtime_author');
            $arr['downtime_data']   = val($this->state, 'downtime_data');
            $arr['downtime_end']    = val($this->state, 'downtime_end');
            $arr['downtime_start']  = val($this->state, 'downtime_start');

            $arr['last_check'] = $this->get_date('last_check');
            $arr['next_check'] = $this->get_date('next_check');
            $arr['state_type'] = $this->getStateType();
            $arr['current_check_attempt'] = $this->getCurrentCheckAttempt();
            $arr['max_check_attempts'] = $this->getMaxCheckAttempts();
            $arr['last_state_change']      = $this->get_date('last_state_change');
            $arr['last_hard_state_change'] = $this->get_date('last_hard_state_change');
            $arr['state_duration'] = $this->getStateDuration();
            $arr['perfdata'] = $this->escapeStringForJson($this->state['perfdata']);
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

        if($this->getSummaryState() != '') {
            $stateLow = strtolower($this->getSummaryState());

            switch($stateLow) {
                case 'unknown':
                case 'unreachable':
                case 'down':
                case 'critical':
                case 'warning':
                    if($this->getSummaryAcknowledgement() == 1) {
                        $icon = $this->iconset.'_'.$stateLow.'_ack.'.$fileType;
                    } elseif($this->getSummaryInDowntime() == 1) {
                        $icon = $this->iconset.'_'.$stateLow.'_dt.'.$fileType;
                    } else {
                        $icon = $this->iconset.'_'.$stateLow.'.'.$fileType;
                    }
                break;
                case 'up':
                case 'ok':
                    if($this->getSummaryInDowntime() == 1) {
                        $icon = $this->iconset.'_'.$stateLow.'_dt.'.$fileType;
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
     * public getChildFetchingStateFilters()
     *
     * Is used to build a state filter for the backend when fetching child
     * objects for the hover menu child list. If the childs should be sorted
     * by state it may be possible to limit the number of requested objects
     * dramatically when using the state filters.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     *
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
        $aChild = Array('type' => $this->getType(),
                        'name' => $this->getName(),
                        'summary_state' => $this->getSummaryState(),
                        'summary_in_downtime' => $this->getSummaryInDowntime(),
                        'summary_problem_has_been_acknowledged' => $this->getSummaryAcknowledgement(),
                        'summary_output' => $this->escapeStringForJson($this->getSummaryOutput()));

        if($this->type == 'service') {
            $aChild['service_description'] = $this->getServiceDescription();
        }

        return $aChild;
    }


    /**
     * PUBLIC setBackendProblem()
     *
     * Sets output/state on backend problems
     *
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function setBackendProblem($s) {
        $this->problem_msg = l('Problem (Backend: [BACKENDID]): [MSG]',
                               Array('BACKENDID' => $this->backend_id, 'MSG' => $s));
    }

    /**
     * PUBLIC setProblem()
     *
     * Sets output/state on object handling problems
     *
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function setProblem($s) {
        $this->problem_msg = l('Problem: [MSG]', Array('MSG' => $s));
    }

    public function hasProblem() {
        return $this->problem_msg !== null;
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
        if($this->summary_state == null) {
            $currWeight = null;
        } else {
            if(isset($stateWeight[$this->summary_state])) {
                $currWeight = $stateWeight[$this->summary_state][$this->getSubState(SUMMARY_STATE)];
            } else {
                throw new NagVisException(l('Invalid state+substate ([STATE], [SUBSTATE]) found while loading the current summary state of an object of type [TYPE].',
                                            Array('STATE'    => $this->summary_state,
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

                            $this->summary_state = $sState;

                            if($sSubState == 'ack') {
                                $this->summary_problem_has_been_acknowledged = 1;
                            } else {
                                $this->summary_problem_has_been_acknowledged = 0;
                            }

                            if($sSubState == 'downtime') {
                                $this->summary_in_downtime = 1;
                            } else {
                                $this->summary_in_downtime = 0;
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
        if($this->summary_state == null && $iSumCount == 0) {
            $this->summary_state = 'ERROR';
        }
    }

    /**
     * RPROTECTED mergeSummaryOutput()
     *
     * Merges the summary output from objects and all child objects together
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function mergeSummaryOutput($arrStates, $objLabel) {
        if(NagVisStatefulObject::$langChildStates === null)
            NagVisStatefulObject::$langChildStates = l('childStatesAre');

        $this->summary_output .= NagVisStatefulObject::$langChildStates.' ';
        foreach($arrStates AS $state => $num)
            if($num > 0)
                $this->summary_output .= $num.' '.$state.', ';

        // If some has been added remove last comma, else add a simple 0
        if(substr($this->summary_output, -2, 2) == ', ')
            $this->summary_output = rtrim($this->summary_output, ', ');
        else
            $this->summary_output .= '0 ';

        $this->summary_output .= ' '.$objLabel.'.';
    }

    /**
     * PROTECTED wrapChildState()
     *
     * Loops all given object so gather the summary state of the current object
     *
     * @param   Array   List of objects to gather the summary state from
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    protected function wrapChildState($OBJS) {
        global $_MAINCFG;
        $stateWeight = $_MAINCFG->getStateWeight();

        // Initialize empty or with current object state
        $currentStateWeight = null;
        if($this->summary_state != null)
            $currentStateWeight = $stateWeight[$this->summary_state][$this->getSubState(SUMMARY_STATE)];

        // Loop all object to gather the worst state and set it as summary
        // state of the current object
        foreach($OBJS AS $OBJ) {
            $objSummaryState = $OBJ->summary_state;
            $objAck          = $OBJ->summary_problem_has_been_acknowledged;
            $objDowntime     = $OBJ->summary_in_downtime;

            if(isset($stateWeight[$objSummaryState])) {
                // Gather the object summary state type
                $objType = 'normal';
                if($objAck == 1 && isset($stateWeight[$objSummaryState]['ack']))
                    $objType = 'ack';
                elseif($objDowntime == 1 && isset($stateWeight[$objSummaryState]['downtime']))
                    $objType = 'downtime';

                if(isset($stateWeight[$objSummaryState][$objType])
                   && ($currentStateWeight === null || $stateWeight[$objSummaryState][$objType] >= $currentStateWeight)) {
                    $this->summary_state                         = $objSummaryState;
                    $this->summary_problem_has_been_acknowledged = $objAck;
                    $this->summary_in_downtime                   = $objDowntime;
                    $currentStateWeight                          = $stateWeight[$objSummaryState][$objType];
                }
            }
        }
    }
}
?>
