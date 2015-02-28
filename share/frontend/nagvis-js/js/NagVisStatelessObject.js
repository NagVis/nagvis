/*****************************************************************************
 *
 * NagVisStatelessObject.js - This class handles the visualisation of
 *                            stateless objects like shape and textbox
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

var NagVisStatelessObject = NagVisObject.extend({
    has_state: false,

    // Initialize
    constructor: function(oConf) {
        // Call parent constructor
        this.base(oConf);
    },

    /**
     * Is called to remove the rendered object
     * This must not remove the object from the JS lists
     */
    remove: function () {
        if(!this.parsedObject)
            return;

        // Remove event listeners
        var oObj;
        oObj = document.getElementById(this.conf.object_id);
        if(oObj) {
            oObj.onmousedown = null;
            oObj.oncontextmenu = null;
            oObj.onmouseover = null;
            oObj.onmouseout = null;
            oObj = null;
        }

        // Remove all controls
        if(!this.bIsLocked)
            this.removeControls();

        // Remove object from DOM
        var oMap = document.getElementById('map');
        if(oMap) {
            oMap.removeChild(this.parsedObject);
            oMap = null;
        }

        // Remove object reference
        this.parsedObject = null;
    }
});
