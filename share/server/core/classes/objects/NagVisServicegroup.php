<?php
/*****************************************************************************
 *
 * NagVisServicegroup.php - Class of a Servicegroup in NagVis with all necessary
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
class NagVisServicegroup extends NagiosServicegroup {
    protected static $langType = null;
    protected static $langSelf = null;
    protected static $langChild = null;
    protected static $langChild1 = null;

    public function __construct($CORE, $BACKEND, $backend_id, $servicegroupName) {
        $this->type = 'servicegroup';
        $this->iconset = 'std_medium';
        parent::__construct($CORE, $BACKEND, $backend_id, $servicegroupName);
    }

    # End public methods
    # #########################################################################
}
?>
