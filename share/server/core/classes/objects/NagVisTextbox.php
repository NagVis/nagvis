<?php
/*****************************************************************************
 *
 * NagVisTextbox.php - Class of a text object in NagVis with all necessary
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

/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisTextbox extends NagVisStatelessObject {

    public function __construct() {
        $this->type = 'textbox';
        parent::__construct();
    }

    /**
     * PUBLIC fetchIcon()
     *
     * Just a dummy here (Textbox won't need an icon)
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    public function fetchIcon() {
        // Nothing to do here, icon is set in constructor
    }

    # End public methods
    # #########################################################################
}
?>
