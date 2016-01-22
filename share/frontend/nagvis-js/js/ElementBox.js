/*****************************************************************************
 *
 * ElementBox.js - This class realizes the object labels
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

var ElementBox = Element.extend({
    render: function() {
        this.dom_obj = renderNagVisTextbox(
            this.obj.conf.object_id+'-label',
            this.obj.conf.background_color, this.obj.conf.border_color,
            0, 0, // coords are set by this.place()
            this.obj.conf.z, this.obj.conf.w,
            this.obj.conf.h, this.obj.getText(), this.obj.conf.style
        );
        this.obj.trigger_obj = this.dom_obj;
        this.place();
    },

    unlock: function () {
        makeResizeable(this.dom_obj);
        makeDragable(this.dom_obj, this.obj, this.obj.saveObject, this.obj.moveObject);
    },

    lock: function () {
        // when locking the object while the cursor is a resize cursor,
        // it will stay as it is, when not removing them.
        this.dom_obj.style.cursor = '';
        makeUndragable(this.dom_obj);
        makeUnresizeable(this.dom_obj);
    },

    place: function () {
        this.dom_obj.style.top  = this.obj.parseCoord(this.obj.conf.y, 'y') + 'px';
        this.dom_obj.style.left = this.obj.parseCoord(this.obj.conf.x, 'x') + 'px';
    }
});
