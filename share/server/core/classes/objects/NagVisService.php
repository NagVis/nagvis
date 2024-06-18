<?php
/*****************************************************************************
 *
 * NagVisService.php - Class of a Service in NagVis with all necessary
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

/**
 * @author	Lars Michelsen <lm@larsmichelsen.com>
 */
class NagVisService extends NagVisStatefulObject {
    protected $type = 'service';

    protected static $langType = null;
    protected static $langSelf = null;

    protected $gadget_url;
    protected $host_name;
    protected $service_description;
    protected $line_label_show;
    protected $line_label_in;
    protected $line_label_out;
    protected $line_label_pos_in;
    protected $line_label_pos_out;
    protected $line_label_y_offset;

    public function __construct($backend_id, $hostName, $serviceDescription) {
        $this->backend_id = [$backend_id[0]]; // only supports one backend
        $this->host_name = $hostName;
        $this->service_description = $serviceDescription;
        parent::__construct();
    }

    public function getName() {
        return $this->host_name;
    }

    public function getNumMembers() {
         return null;
    }

    public function hasMembers() {
         return false;
    }
    
    public function getStateRelevantMembers() {
        return [];
    }

    /**
     * Queues state fetching for this object
     */
    public function queueState($_unused_flag = true, $_unused_flag2 = true) {
        global $_BACKEND;
        $_BACKEND->queue(['serviceState' => true], $this);
    }

    /**
     * Applies the fetched state
     */
    public function applyState() {
        if($this->problem_msg !== null) {
            $this->setState([
                ERROR,
                $this->problem_msg,
                null,
                null,
                null,
            ]);
        }

        $this->sum = $this->state;
    }

    public function getServiceDescription() {
        return $this->service_description;
    }

    protected function fetchObjectAsChild() {
        $aChild = parent::fetchObjectAsChild();
        $aChild['service_description'] = $this->getServiceDescription();
        return $aChild;
    }
}
?>
