<?php
/*****************************************************************************
 *
 * NagiosHostgroup.php - Class of a Hostgroup in Nagios with all necessary
 *                  information
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
class NagiosHostgroup extends NagVisStatefulObject {
    protected $hostgroup_name;
    protected $alias;
    protected $display_name;
    protected $address;

    protected $in_downtime;

    protected $members;

    public function __construct($CORE, $BACKEND, $backend_id, $hostgroupName) {
        $this->backend_id = $backend_id;
        $this->hostgroup_name = $hostgroupName;

        $this->members = Array();

        parent::__construct($CORE, $BACKEND);
    }

    /**
     * PUBLIC queueState()
     *
     * Queues the state fetching to the backend.
     *
     * @param   Boolean  Unused flag here
     * @param   Boolean  Optional flag to disable fetching of member status
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function queueState($_unused = true, $bFetchMemberState = true) {
        $queries = Array('hostgroupMemberState' => true);

        if($this->hover_menu == 1
           && $this->hover_childs_show == 1
           && $bFetchMemberState)
            $queries['hostgroupMemberDetails'] = true;

        $this->BACKEND->queue($queries, $this);
    }

    /**
     * PUBLIC applyState()
     *
     * Applies the fetched state
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function applyState() {
        if($this->problem_msg) {
            $this->summary_state = 'ERROR';
            $this->summary_output = $this->problem_msg;
            $this->members = Array();
            return;
        }

        if($this->hasMembers()) {
            foreach($this->members AS $MOBJ) {
                $MOBJ->applyState();
            }
        }

        // Use state summaries when some are available to
        // calculate summary state and output
        if($this->aStateCounts !== null) {
            // Calculate summary state and output

            // Only create summary from childs when not set yet (e.g by backend)
            if($this->summary_state === '')
                $this->fetchSummaryStateFromCounts();

            // Only create summary from childs when not set yet (e.g by backend)
            if($this->summary_output === '')
                $this->fetchSummaryOutputFromCounts();
        } else {
            if($this->summary_state === '')
                $this->fetchSummaryState();

            if($this->summary_output === '')
                $this->fetchSummaryOutput();
        }

        $this->state = $this->summary_state;
    }

    /**
     * PUBLIC getNumMembers()
     *
     * Returns the number of member objects
     *
     * @return	Integer		Number of members
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getNumMembers() {
        return count($this->members);
    }

    /**
     * PUBLIC getMembers()
     *
     * Returns the member objects
     *
     * @return	Array		Member objects
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getMembers() {
        return $this->members;
    }

    /**
     * PUBLIC hasMembers()
     *
     * Simple check if the hostgroup has at least one member
     *
     * @return Boolean	Yes, No
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function hasMembers() {
        return isset($this->members[0]);
    }

    # End public methods
    # #########################################################################

    /**
     * PRIVATE fetchSummaryOutputFromCounts()
     *
     * Fetches the summary output from the object state counts
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function fetchSummaryOutputFromCounts() {
        $arrHostStates = Array();
        $arrServiceStates = Array();

        // Loop all major states
        $iSumCount = 0;
        foreach($this->aStateCounts AS $sState => $aSubstates) {
            // Loop all substates (normal,ack,downtime,...)
            foreach($aSubstates AS $sSubState => $iCount) {
                // Found some objects with this state+substate
                if($iCount > 0) {
                    // Count all child objects
                    $iSumCount += $iCount;

                    if($sState === 'UP' || $sState === 'DOWN' || $sState === 'UNREACHABLE' || $sState === 'UNCHECKED') {
                        if(!isset($arrHostStates[$sState])) {
                            $arrHostStates[$sState] = $iCount;
                        } else {
                            $arrHostStates[$sState] += $iCount;
                        }
                    } else {
                        if(!isset($arrServiceStates[$sState])) {
                            $arrServiceStates[$sState] = $iCount;
                        } else {
                            $arrServiceStates[$sState] += $iCount;
                        }
                    }
                }
            }
        }

        // Fallback for hostgroups without members
        if($iSumCount == 0) {
            $this->summary_output = l('The hostgroup "[GROUP]" has no members or does not exist (Backend: [BACKEND]).',
                                                                                        Array('GROUP' => $this->getName(),
                                                                                              'BACKEND' => $this->backend_id));
        } else {
            // FIXME: Recode mergeSummaryOutput method
            $this->mergeSummaryOutput($arrHostStates, l('hosts'));
            if($this->recognize_services) {
                $this->summary_output .= "<br />";
                $this->mergeSummaryOutput($arrServiceStates, l('services'));
            }
        }
    }

    /**
     * PRIVATE fetchSummaryState()
     *
     * Fetches the summary state from all members recursive
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function fetchSummaryState() {
        // Get summary state from member objects
        if($this->hasMembers())
            $this->wrapChildState($this->members);
        else
            $this->summary_state = 'ERROR';
    }

    /**
     * PRIVATE fetchSummaryOutput()
     *
     * Fetches the summary output from all members
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    private function fetchSummaryOutput() {
        if($this->hasMembers()) {
            $arrStates = Array('CRITICAL' => 0, 'DOWN'    => 0, 'WARNING'   => 0,
                               'UNKNOWN'  => 0, 'UP'      => 0, 'OK'        => 0,
                               'ERROR'    => 0, 'PENDING' => 0, 'UNCHECKED' => 0);

            // Get summary state of this and child objects
            foreach($this->members AS &$MEMBER) {
                $arrStates[$MEMBER->getSummaryState()]++;
            }

            $this->mergeSummaryOutput($arrStates, l('hosts'));
        } else {
            $this->summary_output = l('hostGroupNotFoundInDB','HOSTGROUP~'.$this->hostgroup_name);
        }
    }
}
?>
