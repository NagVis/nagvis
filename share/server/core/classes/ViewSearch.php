<?php
/*****************************************************************************
 *
 * ViewSearch.php - Class for handling the search dialog
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

class ViewSearch {
    public function parse() {
        ob_start();
        echo '<div id="search">'.N;
        echo '<input type="text" name="highlightInput" id="highlightInput" '
            .'onkeypress="searchObjectsKeyCheck(this.value, event)" autofocus />'.N;
        echo '<input class="submit" type="button" name="submit" value="'.l('Search').'"'
            .' onclick="searchObjects(document.getElementById(\'highlightInput\').value)" />'.N;
        echo '</div>'.N;
        js('try{document.getElementById(\'highlightInput\').focus();}catch(e){}');
        return ob_get_clean();
    }
}
?>
