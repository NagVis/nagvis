<?php
/*****************************************************************************
 *
 * NagVisTextbox.php - Class of a text object in NagVis with all necessary
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
class NagVisTextbox extends NagVisStatelessObject
{
    /** @var string */
    protected $text;

    /** @var int */
    protected $w;

    /** @var int */
    protected $h;

    /** @var string */
    protected $style;
    protected $background_color;
    protected $border_color;

    // Worldmap
    protected $scale_to_zoom;
    protected $normal_size_at_zoom;

    public function __construct()
    {
        $this->type = 'textbox';
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->object_id;
    }

    /**
     * Just a dummy here (Textbox won't need an icon)
     *
     * @return void
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function fetchIcon()
    {
        // Nothing to do here, icon is set in constructor
    }

    # End public methods
    # #########################################################################
}
