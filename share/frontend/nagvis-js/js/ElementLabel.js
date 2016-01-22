/*****************************************************************************
 *
 * ElementLabel.js - This class realizes the object labels
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

var ElementLabel = Element.extend({
    label_text: null,

    update: function() {
        this.label_text = this.obj.conf.label_text || '';

        // Replace configuration based macros in label_text when needed
        if (this.label_text && this.label_text !== '') {
            var objName;
            // For maps use the alias as display string
            if (this.obj.conf.type == 'map') {
                objName = this.obj.conf.alias;
            } else {
                objName = this.obj.conf.name;
            }

            this.label_text = this.label_text.replace(getRegEx('name', '\\[name\\]', 'g'), objName);
            this.label_text = this.label_text.replace(getRegEx('alias', '\\[alias\\]', 'g'), this.obj.conf.alias);

            if (this.obj.conf.type == 'service') {
                this.label_text = this.label_text.replace(getRegEx('service_description', '\\[service_description\\]', 'g'), this.obj.conf.service_description);
            }
        }
    },

    updateAttrs: function(only_state) {
        // update the label on every state update where at least the output or perfdata changed
        if (!only_state || (!this.obj.stateChanged() && this.obj.outputOrPerfdataChanged())) {
            this.erase();
            this.render();
            this.draw();
        }
    },

    render: function() {
        this.dom_obj = renderNagVisTextbox(
            this.obj.conf.object_id + '-label',
            this.obj.conf.label_background, this.obj.conf.label_border,
            0, 0, // coords are set by place()
            this.obj.conf.z,
            this.obj.conf.label_width, '', this.getText(),
            this.obj.conf.label_style
        );
        this.place();
    },

    unlock: function () {
        addEvent(this.dom_obj, 'mouseover', function() {
            document.body.style.cursor = 'move';
        });
        addEvent(this.dom_obj, 'mouseout', function() {
            document.body.style.cursor = 'auto';
        });

	makeDragable(this.dom_obj, this.obj, this.saveLabel, this.dragLabel);
    },

    lock: function () {
        this.dom_obj.onmouseover = null;
        this.dom_obj.onmouseout = null;

	makeUndragable(this.dom_obj);
    },

    /**
     * Calculates and applies the real positions of the objects label. It uses the configuration
     * variables label_x/label_y and repositions the labels based on the config. The label
     * must have been rendered and added to dom to have the dimensions of the object to be able
     * to realize the center/bottom coordinate definitions.
     */
    place: function () {
        this.dom_obj.style.left = this.parseLabelCoord('x') + 'px';
        this.dom_obj.style.top  = this.parseLabelCoord('y') + 'px';
    },

    //
    // END OF PUBLIC METHODS
    //

    // Returns the label with even state based macros replaced
    getText: function () {
        var text = this.label_text;

        // Replace static macros in label_text when needed
        if (text && text !== '') {
            text = text.replace(getRegEx('output', '\\[output\\]', 'g'), this.obj.conf.output);

            if (this.obj.conf.type == 'service' || this.obj.conf.type == 'host') {
                text = text.replace(getRegEx('perfdata', '\\[perfdata\\]', 'g'), this.obj.conf.perfdata);
            }
        }

        if (this.obj.conf.label_maxlen > 0 && text.length > this.obj.conf.label_maxlen)
            text = text.substr(0, this.obj.conf.label_maxlen - 2) + '...';

        return text;
    },

    /**
     * This needs to calculate the offset of the current position to the first position,
     * then create a new coord (relative/absolue) and save them in label_x/y attributes
     */
    // Important: It is called from an event handler the 'this.' keyword can not be used here.
    dragLabel: function(trigger_obj, obj, event) {
        var isRelative = function(coord) {
            return coord.toString().match(/^(?:\+|\-|center|bottom)/);
        };

        // Calculates relative/absolute coords depending on the current configured type
        var calcNewLabelCoord = function (labelCoord, coord, newCoord) {
            if (isRelative(labelCoord)) {
                var ret = newCoord - coord;
                if(ret >= 0)
                    return '+' + ret;
                return ret;
            } else
                return newCoord;
        };

        obj.conf.label_x = calcNewLabelCoord(obj.conf.label_x,
                                             obj.parseCoord(obj.conf.x, 'x', false), trigger_obj.x);
        obj.conf.label_y = calcNewLabelCoord(obj.conf.label_y,
                                             obj.parseCoord(obj.conf.y, 'y', false), trigger_obj.y);

        if (isRelative(obj.conf.label_x) || isRelative(obj.conf.label_y))
            obj.highlight(true);
    },

    // Important: It is called from an event handler the 'this.' keyword can not be used here.
    saveLabel: function(trigger_obj, obj, oParent) {
        saveObjectAttr(obj.conf.object_id, {
            'label_x': obj.conf.label_x,
            'label_y': obj.conf.label_y
        });
        obj.highlight(false);
    },

    parseLabelCoord: function (dir) {
        if (dir === 'x') {
            var coord = this.obj.conf.label_x;

            if (this.obj.conf.view_type && this.obj.conf.view_type == 'line') {
                var obj_coord = this.obj.getLineMid(this.obj.conf.x, 'x');
            } else {
                var obj_coord = addZoomFactor(this.obj.parseCoords(this.obj.conf.x, 'x', false)[0], true);
            }
        } else {
            var coord = this.obj.conf.label_y;
            if (this.obj.conf.view_type && this.obj.conf.view_type == 'line') {
                var obj_coord = this.obj.getLineMid(this.obj.conf.y, 'y');
            } else {
                var obj_coord = addZoomFactor(this.obj.parseCoords(this.obj.conf.y, 'y', false)[0], true);
            }
        }

        if (dir == 'x' && coord && coord.toString() == 'center') {
            var diff = parseInt(parseInt(this.dom_obj.clientWidth) - rmZoomFactor(this.obj.getObjWidth())) / 2;
            coord = obj_coord - diff;
        } else if (dir == 'y' && coord && coord.toString() == 'bottom') {
            coord = obj_coord + rmZoomFactor(this.obj.getObjHeight());
        } else if (coord && coord.toString().match(/^(?:\+|\-)/)) {
            // If there is a presign it should be relative to the objects x/y
            coord = obj_coord + addZoomFactor(parseFloat(coord));
        } else if (!coord || coord === '0') {
           // If no x/y coords set, fallback to object x/y
            coord = obj_coord;
        } else {
            // This must be absolute coordinates, apply zoom factor
            coord = addZoomFactor(coord, true);
        }

        return coord;
    }
});
