/*****************************************************************************
 *
 * ElementIcon.js - This class handles the visualisation of icons
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

var ElementIcon = Element.extend({
    render: function() {
        this.renderIcon();
        this.place();
    },

    // Moves the icon to it's location as described by this js object
    place: function () {
        this.dom_obj.style.top  = this.obj.parseCoord(this.obj.conf.y, 'y') + 'px';
        this.dom_obj.style.left = this.obj.parseCoord(this.obj.conf.x, 'x') + 'px';
    },

    unlock: function () {
        this.toggleLink(false);
        makeDragable(this.dom_obj, this.obj, this.obj.saveObject, this.obj.moveObject);
    },

    lock: function () {
        this.toggleLink(true);
        makeUndragable(this.dom_obj);
    },

    //
    // END OF PUBLIC METHODS
    //

    // Renders the object as icon and returns the icon container js object
    renderIcon: function () {
        var alt = '';
        if(this.obj.conf.type == 'service')
            alt = this.obj.conf.name+'-'+this.obj.conf.service_description;
        else
            alt = this.obj.conf.name;

        var oIcon = document.createElement('img');
        oIcon.setAttribute('id', this.obj.conf.object_id+'-icon');
        oIcon.className = 'icon';
        this.obj.trigger_obj = oIcon;

        // When no icon size is configured, the native size of the image is used.
        // An icon size might be either one integer for even sized images or
        // two integers for differen height/width images
        if (this.obj.conf.icon_size) {
            var size = this.obj.conf.icon_size;
            if (size.length == 1) {
                var w = parseInt(size),
                    h = parseInt(size);
            } else {
                var w = parseInt(size[0]),
                    h = parseInt(size[1]);
            }
            oIcon.style.width = w + 'px';
            oIcon.style.height = h + 'px';
        }

        // Register controls reposition handler to handle resizes during
        // loading the image (from alt="" text to the real image)
        addEvent(oIcon, 'load', function(obj) {
            return function() {
                obj.place();
                obj = null;
            };
        }(this.obj));

        addZoomHandler(oIcon);

        oIcon.src = oGeneralProperties.path_iconsets + this.obj.conf.icon;
        oIcon.alt = this.obj.conf.type + '-' + alt;

        var oIconDiv = document.createElement('div');
        this.dom_obj = oIconDiv;
        oIconDiv.setAttribute('id', this.obj.conf.object_id+'-icondiv');
        oIconDiv.className = 'icondiv';
        oIconDiv.style.zIndex = this.obj.conf.z;

        // Parse link only when set
        if (this.obj.conf.url && this.obj.conf.url !== '' && this.obj.conf.url !== '#') {
            var oIconLink = document.createElement('a');
            oIconLink.href = this.obj.conf.url;
            oIconLink.target = this.obj.conf.url_target;
            oIconLink.appendChild(oIcon);
            oIconDiv.appendChild(oIconLink);
        } else {
            oIconDiv.appendChild(oIcon);
        }
    }

});
