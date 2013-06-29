<?php
/*****************************************************************************
 *
 * NagVisService.php - Class of a Service in NagVis with all necessary
 *                  information which belong to the object handling in NagVis
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
class NagVisService extends NagVisStatefulObject {
    protected $type = 'service';

    protected static $langType = null;
    protected static $langSelf = null;

    protected $host_name;
    protected $service_description;
    protected $alias;
    protected $display_name;
    protected $address;
    protected $notes;
    protected $check_command;

    protected $perfdata;
    protected $last_check;
    protected $next_check;
    protected $state_type;
    protected $current_check_attempt;
    protected $max_check_attempts;
    protected $last_state_change;
    protected $last_hard_state_change;

    protected $gadget_url;

    public function __construct($backend_id, $hostName, $serviceDescription) {
        $this->backend_id = $backend_id;
        $this->host_name = $hostName;
        $this->service_description = $serviceDescription;
        parent::__construct();
    }

    public function getNumMembers() {
         return null;
    }

    public function hasMembers() {
         return false;
    }
    
    public function getStateRelevantMembers() {
        return array();
    }

    /**
     * Queues state fetching for this object
     */
    public function queueState($_unused_flag = true, $_unused_flag = true) {
        global $_BACKEND;
        $_BACKEND->queue(Array('serviceState' => true), $this);
    }

    /**
     * Applies the fetched state
     */
    public function applyState() {
        if($this->problem_msg !== null) {
            $this->setState(array(
                ERROR,
                $this->problem_msg,
                null,
                null,
            ));
        }

        $this->sum = $this->state;
    }

    /**
     * Returns the service description
     */
    public function getServiceDescription() {
        return $this->service_description;
    }

    # End public methods
    # #########################################################################

    /**
     * PROTECTED parseGadgetUrl()
     *
     * Sets the path of gadget_url. The method adds htmlgadgets path when relative
     * path or will remove [] when full url given
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function parseGadgetUrl() {
        if(preg_match('/^\[(.*)\]$/',$this->gadget_url,$match) > 0)
            $this->gadget_url = $match[1];
        else
            $this->gadget_url = path('html', 'global', 'gadgets', $this->gadget_url);
    }
}
?>
