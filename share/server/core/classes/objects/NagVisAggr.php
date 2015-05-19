<?php
/*****************************************************************************
 *
 * NagVisAggr.php - Handles aggregations of either hosts or services
 *                      for example Check_MK BI aggregations
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

class NagVisAggr extends NagVisStatefulObject {
    protected $type = 'aggr';

    protected static $langType   = null;
    protected static $langSelf   = null;
    protected static $langChild  = null;
    protected static $langChild1 = null;

    protected $name;

    protected $members = array();

    public function __construct($backend_id, $name) {
        $this->backend_id = $backend_id;
        $this->name       = $name;
        parent::__construct();
    }

    /**
     * Queues the state fetching to the backend.
     * @param   Boolean  Unused flag here
     * @param   Boolean  Optional flag to disable fetching of member status
     */
    public function queueState($_unused = true, $bFetchMemberState = true) {
        global $_BACKEND;
        $queries = Array('AGGR_MEMBER_STATE' => true);

        if($this->hover_menu == 1
           && $this->hover_childs_show == 1
           && $bFetchMemberState)
            $queries['AGGR_MEMBER_DETAILS'] = true;

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

        // use the summary state provided by the backend, this is
        // the summary state provided by the aggregation tool based
        // on the configured rules
        if ($this->state[STATE] !== null)
            $this->sum[STATE] = $this->state[STATE];

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
        $node_states = Array();

        // Loop all major states
        $iSumCount = 0;
        foreach($this->aStateCounts AS $sState => $aSubstates) {
            // Loop all substates (normal,ack,downtime,...)
            foreach($aSubstates AS $sSubState => $iCount) {
                // Found some objects with this state+substate
                if($iCount > 0) {
                    // Count all child objects
                    $iSumCount += $iCount;

                    if(!isset($node_staets[$sState]))
                        $node_states[$sState] = $iCount;
                    else
                        $node_states[$sState] += $iCount;
                }
            }
        }

        // Fallback for hostgroups without members
        if($iSumCount == 0) {
            $this->sum[OUTPUT] = l('The aggregation "[NAME]" has no members (Backend: [BACKEND]).',
                                                       Array('NAME' => $this->name,
                                                             'BACKEND' => implode(',', $this->backend_id)));
        } else {
            $this->mergeSummaryOutput($node_states, l('Nodes'), true);
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
            $this->sum[OUTPUT] = l('The aggregation "[NAME]" has no members (Backend: [BACKEND]).',
                                                       Array('NAME' => $this->name,
                                                       'BACKEND' => implode(',', $this->backend_id)));
        }
    }
}
?>
