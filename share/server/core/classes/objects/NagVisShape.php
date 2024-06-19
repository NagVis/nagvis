<?php
/*****************************************************************************
 *
 * NagVisShape.php - Class of a Shape in NagVis with all necessary
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
class NagVisShape extends NagVisStatelessObject
{
    protected $enable_refresh;
    protected $icon;
    protected $icon_size;

    /**
     * Class constructor
     *
     * @param		object $icon Object of class GlobalMainCfg
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function __construct($icon)
    {
        if (self::$iconPath === null) {
            self::$iconPath      = path('sys',  'global', 'shapes');
            self::$iconPathLocal = path('sys',  'local', 'shapes');
        }

        $this->icon = $icon;
        $this->type = 'shape';

        parent::__construct();
    }

    /**
     * PUBLIC parseJson()
     *
     * Parses the object in json format
     *
     * @return	string		JSON code of the object
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function parseJson()
    {
        // Checks wether the shape exists or not
        $this->fetchIcon();

        return parent::parseJson();
    }

    /**
     * PUBLIC fetchIcon()
     *
     * Is executed to detect missing shape images. Is not doing anything
     * when the shape image is an URL.
     *
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function fetchIcon()
    {
        if ($this->icon[0] != '[') {
            if (
                !file_exists(self::$iconPath . $this->icon)
                && !file_exists(self::$iconPathLocal . $this->icon)
            ) {
                $this->icon = 'std_dummy.png';
            }
        }
    }

    # End public methods
    # #########################################################################
}
