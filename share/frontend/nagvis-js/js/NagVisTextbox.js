/*****************************************************************************
 *
 * NagVisTextbox.js - This class handles the visualisation of textbox objects
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

var NagVisTextbox = NagVisStatelessObject.extend({
    // Initialize
    constructor: function(oConf) {
        // Call parent constructor
        this.base(oConf);
    },

    /**
     * PUBLIC parse()
     *
     * Parses the object
     *
     * @return	String		HTML code of the object
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    parse: function() {
        var oContainerDiv;

        this.replaceMacros();

        // Create container div
        oContainerDiv = document.createElement('div');
        oContainerDiv.setAttribute('id', this.conf.object_id);

        // When this is an update, remove the object first
        this.remove();

        var oMap = document.getElementById('map');
        if(oMap) {
            this.parsedObject = oMap.appendChild(oContainerDiv);
            oMap = null;
        }

        this.parseTextbox(oContainerDiv);
        oContainerDiv = null;

        // Enable the controls when the object is not locked
        if(!this.bIsLocked)
            this.parseControls();
    },

    /**
     * Replaces macros of urls and hover_urls
     *
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    replaceMacros: function () {
        this.conf.text = this.conf.text.replace('[refresh_counter]', '<font id="refreshCounter"></font>');
        this.conf.text = this.conf.text.replace('[worker_last_run]', '<font id="workerLastRunCounter"></font>');
    },

    /**
     * Create a Comment-Textbox
     *
     * @return	String	String with HTML Code
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    parseTextbox: function (oContainer) {
        drawNagVisTextbox(
            oContainer,
            this.conf.object_id+'-label', 'box',
            this.conf.background_color, this.conf.border_color,
            this.parseCoord(this.conf.x, 'x'), this.parseCoord(this.conf.y, 'y'), this.conf.z, this.conf.w,
            this.conf.h, this.conf.text, this.conf.style
        );
    },

    parseBoxControls: function () {
        var oBox = document.getElementById(this.conf.object_id+'-label');
        oBox.setAttribute('class',     'box resizeMe');
        oBox.setAttribute('className', 'box resizeMe');
        oBox = null;

        // Simply make it dragable. Maybe will be extended in the future...
        makeDragable([this.conf.object_id+'-label'], this.saveObject, this.moveObject);
    },

    removeBoxControls: function () {
        var oBox = document.getElementById(this.conf.object_id+'-label');
        oBox.setAttribute('class',     'box');
        oBox.setAttribute('className', 'box');
        oBox = null;
    },

    moveBox: function () {
        var container = document.getElementById(this.conf.object_id + '-label');
        container.style.top  = this.parseCoord(this.conf.y, 'y') + 'px';
        container.style.left = this.parseCoord(this.conf.x, 'x') + 'px';
        container = null;
    }
});
