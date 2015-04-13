/*****************************************************************************
 *
 * NagVisContainer.js - This class handles the visualisation of textbox objects
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

var NagVisContainer = NagVisStatelessObject.extend({
    // Initialize
    constructor: function(oConf) {
        // Call parent constructor
        this.base(oConf);
    },

    /**
     * PUBLIC parse()
     * Parses the object
     */
    parse: function() {
        var oContainerDiv = document.getElementById(this.conf.object_id);
        if(!oContainerDiv) {
            // Create container div
            var oContainerDiv = document.createElement('div');
            oContainerDiv.setAttribute('id', this.conf.object_id);
            this.parseTextbox(oContainerDiv);

            var oMap = document.getElementById('map');
            if(oMap) {
                this.parsedObject = oMap.appendChild(oContainerDiv);
                oMap = null;
            }
        }
        oContainerDiv = null;

        // When this is an update, remove the object first
        //this.remove();

        // Add contents to the textbox
        var oTextbox = document.getElementById(this.conf.object_id +'-label');
        if(oTextbox) {
            var oSpan = oTextbox.childNodes[0];
            if(oSpan) {
                oSpan.style.display = 'block';
                oSpan.style.height = '100%';

                if(this.conf.view_type === 'inline') {
                    // Request data via ajax call and add it directly to the current page
                    try {
                        oSpan.innerHTML = getSyncUrl(this.conf.url);
                    } catch(e) {
                        oSpan.innerHTML = e.toString();
                    }
                } else {
                    // Create an iframe element which holds the requested url
                    oSpan.innerHTML = '';
                    var oIframe = document.createElement('iframe');
                    oIframe.style.borderWidth = 0;
                    oIframe.style.width  = '100%';
                    oIframe.style.height = '100%';
                    oIframe.src = this.conf.url;
                    oSpan.appendChild(oIframe);
                    oIframe = null;
                }

                oSpan = null;
            }
            oTextbox = null;
        }

        // Enable the controls when the object is not locked
        if(!this.bIsLocked)
            this.parseControls();
    },

    /**
     * Create a Comment-Textbox
     */
    parseTextbox: function (oContainer) {
        drawNagVisTextbox(
            oContainer,
            this.conf.object_id+'-label', 'box',
            this.conf.background_color, this.conf.border_color,
            this.parseCoord(this.conf.x, 'x'), this.parseCoord(this.conf.y, 'y'), this.conf.z, this.conf.w,
            this.conf.h, '', this.conf.style
        );
    },

    parseBoxControls: function () {
        var oBox = document.getElementById(this.conf.object_id+'-label');
        if(oBox) {
            oBox.setAttribute('class',     'box resizeMe');
            oBox.setAttribute('className', 'box resizeMe');
            oBox = null;

            // Simply make it dragable. Maybe will be extended in the future...
            makeDragable([this.conf.object_id+'-label'], this.saveObject, this.moveObject);
        }
    },

    removeBoxControls: function () {
        var oBox = document.getElementById(this.conf.object_id+'-label');
        if(oBox) {
            oBox.setAttribute('class',     'box');
            oBox.setAttribute('className', 'box');
            oBox = null;
        }
    },

    moveBox: function () {
        var container = document.getElementById(this.conf.object_id + '-label');
        if(container) {
            container.style.top  = this.parseCoord(this.conf.y, 'y') + 'px';
            container.style.left = this.parseCoord(this.conf.x, 'x') + 'px';
            container = null;
        }
    }
});
