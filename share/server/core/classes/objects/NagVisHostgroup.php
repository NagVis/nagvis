<?php
/*****************************************************************************
 *
 * NagVisHostgroup.php - Class of a Hostgroup in NagVis with all necessary
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

class NagVisHostgroup extends NagVisStatefulObject {
    protected $type = 'hostgroup';

    protected static $langType = null;
    protected static $langSelf = null;
    protected static $langChild = null;

    protected $hostgroup_name;
    protected $alias;

    protected $members = array();

    public function __construct($backend_id, $hostgroupName) {
        $this->backend_id = $backend_id;
        $this->hostgroup_name = $hostgroupName;
        parent::__construct();
    }


    /**
     * Queues the state fetching to the backend.
     * @param   Boolean  Unused flag here
     * @param   Boolean  Optional flag to disable fetching of member status
     */
    public function queueState($_unused = true, $bFetchMemberState = true) {
        global $_BACKEND;
        $queries = Array('hostgroupMemberState' => true);

        if($this->hover_menu == 1
           && $this->hover_childs_show == 1
           && $bFetchMemberState)
            $queries['hostgroupMemberDetails'] = true;

        $_BACKEND->queue($queries, $this);
    }

    /**
     * Applies the fetched state
     */
    public function applyState() {
        if($this->problem_msg) {
            $this->sum = array(
                ERROR,
                $this->problem_msg,
                null,
                null,
                null,
            );
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
            if($this->sum[STATE] === null)
                $this->fetchSummaryStateFromCounts();

            // Only create summary from childs when not set yet (e.g by backend)
            if($this->sum[OUTPUT] === null)
                $this->fetchSummaryOutputFromCounts();
        } else {
            if($this->sum[STATE] === null)
                $this->fetchSummaryState();

            if($this->sum[OUTPUT] === null)
                $this->fetchSummaryOutput();
        }
    }

    # End public methods
    # #########################################################################

    /**
     * Fetches the summary output from the object state counts
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

                    if(is_host_state($sState)) {
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
            $this->sum[OUTPUT] = l('The hostgroup "[GROUP]" has no members or does not exist (Backend: [BACKEND]).',
                                                                                        Array('GROUP' => $this->getName(),
                                                                                              'BACKEND' => $this->backend_id));
        } else {
            // FIXME: Recode mergeSummaryOutput method
            $this->mergeSummaryOutput($arrHostStates, l('hosts'), false);
            if($this->recognize_services) {
                $this->sum[OUTPUT] .= ' ' . l('and') . ' ';
                $this->mergeSummaryOutput($arrServiceStates, l('services'), true, true);
            }
        }
    }

    /**
     * Fetches the summary state from all members recursive
     */
    private function fetchSummaryState() {
        if($this->hasMembers())
            $this->calcSummaryState();
        else
            $this->sum[STATE] = ERROR;
    }

    /**
     * Fetches the summary output from all members
     */
    private function fetchSummaryOutput() {
        if($this->hasMembers()) {
            $arrStates = Array(CRITICAL => 0, DOWN    => 0, WARNING   => 0,
                               UNKNOWN  => 0, UP      => 0, OK        => 0,
                               ERROR    => 0, PENDING => 0, UNCHECKED => 0);

            // Get summary state of this and child objects
            foreach($this->members AS &$MEMBER) {
                $arrStates[$MEMBER->getSummaryState()]++;
            }

            $this->mergeSummaryOutput($arrStates, l('hosts'));
        } else {
            $this->sum[OUTPUT] = l('hostGroupNotFoundInDB','HOSTGROUP~'.$this->hostgroup_name);
        }
    }
}
?>
