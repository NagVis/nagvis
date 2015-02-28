/*****************************************************************************
 *
 * NagVisLine.js - This class handles the visualisation of stateless line
 *                 objects in the NagVis js frontend
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


var NagVisLine = NagVisStatelessObject.extend({
    constructor: function(oConf) {
        // Call parent constructor;
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
    parse: function () {
        var oContainerDiv;

        // Create container div
        oContainerDiv = document.createElement('div');
        oContainerDiv.setAttribute('id', this.conf.object_id);

        var oLine = this.parseLine();
        oContainerDiv.appendChild(oLine);
        oShape = null;

        // Append child to map and save reference in parsedObject
        var oMap = document.getElementById('map');
        if(oMap) {
            this.parsedObject = oMap.appendChild(oContainerDiv);
            oMap = null;
        }
        oContainerDiv = null;

        this.drawLine();

        // Enable the controls when the object is not locked
        if(!this.bIsLocked) {
            this.parseControls();
	    if(typeof(this.unlockLabel) == 'function')
	        this.unlockLabel();
            this.toggleObjectActions(this.bIsLocked);
        }
    },

    /**
     * Parses the HTML-Code of a line
     *
     * @return	String		HTML code
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    parseLine: function () {
        var ret = '';
        var link = '';

        // Create container div
        var oContainerDiv = document.createElement('div');
        oContainerDiv.setAttribute('id', this.conf.object_id+'-linediv');

        // Create line div
        var oLineDiv = document.createElement('div');
        oLineDiv.setAttribute('id', this.conf.object_id+'-line');
        oLineDiv.style.zIndex = this.conf.z;

        oContainerDiv.appendChild(oLineDiv);
        oLineDiv = null;

        // Parse hover/link area only when needed
        this.parseLineHoverArea(oContainerDiv);

        return oContainerDiv;
    },

    /**
     * Draws the NagVis lines on the already added divs.
     *
     * @return	String		HTML code
     * @author	Lars Michelsen <lars@vertical-visions.de>
     * FIXME: Eliminate duplicate code with NagVisStatefulObject
     */
    drawLine: function() {
        var width = addZoomFactor(this.conf.line_width);
        if(width <= 0)
            width = 1; // minimal width for lines

        var colorFill = this.conf.line_color;
        var colorBorder = this.conf.line_color_border;

        // Parse the line object
        drawNagVisLine(this.conf.object_id, this.conf.line_type, this.lineCoords(),
                       this.conf.z, width, colorFill, null, null, colorBorder,
                       this.needsLineHoverArea(),
                       (this.conf.line_label_show && this.conf.line_label_show === '1'),
                       parseInt(this.conf.line_label_y_offset));
    },

    remove: function () {
        if(!this.parsedObject)
            return

        var oMap = document.getElementById('map');
        if(oMap)
            oMap.removeChild(this.parsedObject);

        // Remove object reference
        this.parsedObject = null;

        oMap = null;
    },

    parseHoverMenu: function () {
        this.getHoverMenu(this.conf.object_id+'-linelink');
    }
});
