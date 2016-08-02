/*****************************************************************************
 *
 * Element.js - Base class for all elements
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

var Element = Base.extend({
    // Holds the NagVis object which this element is associated with
    obj     : null,
    // Holds the root dom object of this element
    dom_obj : null,

    constructor: function(obj) {
        this.obj = obj;
    },

    // Is called once when the object is being initialized and
    // on every time the config has changed. It's task is to
    // initialize this element and all eventual sub elements
    update: function() {},

    // Is called on every state/config update. For the most objects
    // this re-renders the element on each non state update.
    updateAttrs: function(only_state, is_locked) {
        if (!only_state) {
            this.erase();
            this.render();

            if (!is_locked)
                this.unlock();

            this.draw();
        }
    },

    // Is called to draw this elements DOM nodes this is called
    // once during initializaton and whenever the parent element
    // is redrawn, for example during a state update.
    render: function() {},

    // Is called to update the position of this elements DOM node.
    // It is called during movements of the objects and some of
    // the objects call it within the render() function to place
    // the objects during render() processing.
    place: function() {},

    // Is called by the parent element to add this elements DOM
    // object to the parent elements DOM object
    draw: function() {
        if (this.dom_obj)
            this.obj.dom_obj.appendChild(this.dom_obj);
    },

    // Is called by the parent element to remove this elements DOM
    // object from the parent elements DOM object. This is the counterpart
    // of the draw() method.
    erase: function() {
        // FIXME: Remove all possible event handlers. Just to be
        // sure to prevent memory leaks

        if (this.dom_obj && this.dom_obj.parentNode)
            this.obj.dom_obj.removeChild(this.dom_obj);
    },

    // When the object is intially locked this is called once
    // during initialization and whenever the object shal be
    // locked
    lock: function() {},

    // When the object is intially unlocked this is called once
    // during initialization and whenever the object shal be
    // unlocked
    unlock: function() {},

    // Is called to add this element to the parent element
    addTo: function(obj) {
        obj.addElement(this);
        return this;
    },

    // Is called to remove this element from the parent element
    removeFrom: function(obj) {
        obj.removeElement(this);
        return this;
    },

    //
    // Some internal helper functions
    //

    // This enables/disables the the object left click action (link)
    toggleLink: function(enable) {
	if (enable) {
            if (this.obj.trigger_obj.parentNode.tagName == 'A') {
                this.obj.trigger_obj.parentNode.onclick = null;
            }
	} else if (!enable) {
            if (this.obj.trigger_obj.parentNode.tagName == 'A') {
                this.obj.trigger_obj.parentNode.onclick = function(event) {
                    var event = !event ? window.event : event;
                    if(event.stopPropagation)
                        event.stopPropagation();
                    event.cancelBubble = true;
                    return false;
                };
            }
	}
    }
});
