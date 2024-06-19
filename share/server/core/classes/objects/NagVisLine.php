<?php
/*****************************************************************************
 *
 * NagVisLine.php - Class of a Stateless line in NagVis with all necessary
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
class NagVisLine extends NagVisStatelessObject
{
    protected $line_color;
    protected $line_color_border;

    public function __construct() {
        $this->type = 'line';
        parent::__construct();
    }

    /**
     * PUBLIC fetchIcon()
     *
     * Just a dummy here (Shape won't need an icon)
     *
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function fetchIcon() {
        // Nothing to do here, icon is set in constructor
    }

    # End public methods
    # #########################################################################
}

