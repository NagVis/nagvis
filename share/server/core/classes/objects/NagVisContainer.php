<?php
/*****************************************************************************
 *
 * NagVisContainer.php - Class of a container object in NagVis with all necessary
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

class NagVisContainer extends NagVisStatelessObject {
    protected $type = 'container';

    /**
     * PUBLIC parseJson()
     * Parses the object in json format
     */
    public function parseJson() {
        // Prepare the URL attribute. If it is an absolute url, leave it as it is
        // If it is a simple filename add the url to the scripts path
        $parts = parse_url($this->url);
        if(!isset($parts['scheme']) && $parts['path'][0] !== '/') {
            $this->url = cfg('paths', 'htmlbase') . '/userfiles/scripts/' . $this->url;
        }

        return parent::parseJson();
    }

    /**
     * PUBLIC fetchIcon()
     * Just a dummy here (Container won't need an icon)
     */
    public function fetchIcon() {
        // Nothing to do here, icon is set in constructor
    }
}
?>
