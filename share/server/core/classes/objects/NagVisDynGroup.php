<?php
/*****************************************************************************
 *
 * NagVisDynGroup.php - Handles dynamic grouping of either hosts or services
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

class NagVisDynGroup extends NagVisStatefulObject {
    protected $type = 'dyngroup';

    protected static $langType   = null;
    protected static $langSelf   = null;
    protected static $langChild  = null;
    protected static $langChild1 = null;

    protected $name;
    public $object_types;
    protected $object_filter;

    protected $members = [];

    public function __construct($backend_id, $name) {
        $this->backend_id = $backend_id;
        $this->name       = $name;
        parent::__construct();
    }

    public function getName() {
        return $this->name;
    }

    public function getObjectFilter() {
        // convert '\n' to \n
        return str_replace('\n', "\n", $this->object_filter);
    }

    /**
     * Queues the state fetching to the backend.
     * @param   bool $_unused Unused flag here
     * @param   bool $bFetchMemberState Optional flag to disable fetching of member status
     */
    public function queueState($_unused = true, $bFetchMemberState = true) {
        global $_BACKEND;
        $queries = ['DYN_GROUP_MEMBER_STATE' => true];

        if($this->hover_menu == 1
           && $this->hover_childs_show == 1
           && $bFetchMemberState)
            $queries['DYN_GROUP_MEMBER_DETAILS'] = true;

        $_BACKEND->queue($queries, $this);
    }

    /**
     * Applies the fetched state
     */
    public function applyState() {
        if($this->problem_msg) {
            $this->sum = [
                ERROR,
                $this->problem_msg,
                null,
                null,
                null,
            ];
            $this->members = [];
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
        $arrHostStates = [];
        $arrServiceStates = [];

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
            $this->sum[OUTPUT] = l('The dynamic group "[GROUP]" has no members (Backend: [BACKEND]).',
                                                       [
                                                           'GROUP' => $this->name,
                                                       'BACKEND' => implode(',', $this->backend_id)
                                                       ]);
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
            $arrStates = [
                CRITICAL => 0, DOWN    => 0, WARNING   => 0,
                               UNKNOWN  => 0, UP      => 0, OK        => 0,
                               ERROR    => 0, PENDING => 0, UNCHECKED => 0
            ];

            // Get summary state of this and child objects
            foreach($this->members AS &$MEMBER) {
                $arrStates[$MEMBER->getSummaryState()]++;
            }

            $this->mergeSummaryOutput($arrStates, l('hosts'));
        } else {
            $this->sum[OUTPUT] = l('The dynamic group "[GROUP]" has no members (Backend: [BACKEND]).',
                                                       [
                                                           'GROUP' => $this->name,
                                                       'BACKEND' => implode(',', $this->backend_id)
                                                       ]);
        }
    }
}

