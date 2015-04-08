/*****************************************************************************
 *
 * NagVisObject.js - This class handles the visualisation of Nagvis objects
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

var NagVisObject = Base.extend({
    dom_obj:               null,
    trigger_obj:           null,
    conf:                  null,
    lastUpdate:            null,
    firstUpdate:           null,
    bIsFlashing:           false,
    bIsLocked:             true,
    objControls:           null,
    childs:                null,
    // Holds all drawable GUI elements
    elements:              null,

    constructor: function(conf) {
        this.setLastUpdate();

        this.childs      = [];
        this.objControls = [];
        this.elements    = [];
        this.conf        = conf;

        // When no object_id given by server: generate own id
        if (this.conf.object_id == null)
            this.conf.object_id = getRandomLowerCaseLetter() + getRandom(1, 99999);

        // Load lock options
        this.loadLocked();
        this.loadViewOpts();
    },

    update: function() {
        if (this.needsContextMenu())
            new ElementContext(this).addTo(this);

        if (this.needsHoverMenu())
            new ElementHover(this).addTo(this);

        if (this.conf.label_show && this.conf.label_show == '1')
            new ElementLabel(this).addTo(this);

        for (var i = 0; i < this.elements.length; i++)
            this.elements[i].update();
    },

    // Renders the current object and all it's elements
    render: function () {
        // Create container div
        var container = document.createElement('div');
        container.setAttribute('id', this.conf.object_id);

        // Save reference to DOM obj in js obj
        this.dom_obj = container;

        // Add the objects container to the map object
        if (!usesSource('worldmap')) {
            var oMap = document.getElementById('map');
            if (oMap) {
                oMap.appendChild(container);
            }
        }

        for (var i = 0; i < this.elements.length; i++) {
            this.elements[i].render();
            this.elements[i].place();

            if (!this.bIsLocked)
                this.elements[i].unlock();
        }

        // FIXME
        //else {
        //    L.marker([parseFloat(this.conf.x), parseFloat(this.conf.y)], {
        //        icon: L.nagVisObj({node: oContainerDiv})
        //    }).addTo(g_map_objects);
        //}

        // Enable the controls when the object is not locked
        if (!this.bIsLocked) {
            this.parseControls();
	}
    },

    draw: function() {
        for (var i = 0; i < this.elements.length; i++)
            this.elements[i].draw(this);
    },

    erase: function () {
        // Don't erase when it has not performed draw()
        if (!this.dom_obj)
            return;

        for (var i = 0; i < this.elements.length; i++)
            this.elements[i].erase(this);

        // Remove all controls
        if(!this.bIsLocked)
            this.removeControls();

        var oMap = document.getElementById('map');
        if (!oMap) {
            return;
        }

        // Remove object from DOM
        if (!usesSource('worldmap'))
            oMap.removeChild(this.dom_obj);
    },

    addElement: function(obj) {
        if(this.elements.indexOf(obj) === -1)
            this.elements.push(obj);
        obj = null;
    },

    removeElement: function(obj) {
        this.elements.splice(this.elements.indexOf(obj), 1);
        obj = null;
    },

    /**
     * PRIVATE loadLocked
     * Loads the lock state of an object from the user properties.
     *
     * Another way to unlock the object is the optional view property
     * "edit_mode". In this case all map objects are unlocked but this
     * state is not saved to the user properties.
     */
    loadLocked: function() {
        // Editing is only possible in maps
        if(oPageProperties.view_type != 'map')
            return;

        if(!oUserProperties.hasOwnProperty('unlocked-' + oPageProperties.map_name))
            return;

        if(oViewProperties.hasOwnProperty('edit_mode') && oViewProperties['edit_mode'] === true) {
            this.bIsLocked = false;
            return;
        }

        var unlocked = oUserProperties['unlocked-' + oPageProperties.map_name].split(',');
        this.bIsLocked = unlocked.indexOf(this.conf.object_id) === -1 && unlocked.indexOf('*') === -1;
    },

    /**
     * PUBLIC loadViewOpts
     *
     * Loads view specific options. Basically this options are triggered by url params
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    loadViewOpts: function() {
        // Do not load the view options for stateless lines
        if(this.conf.type == 'line')
            return;

        // View specific hover modifier set. Will override the map configured option
        if(isset(oViewProperties) && isset(oViewProperties.hover_menu))
            this.conf.hover_menu = oViewProperties.hover_menu;

        // View specific context modifier set. Will override the map configured option
        if(isset(oViewProperties) && isset(oViewProperties.context_menu))
            this.conf.context_menu = oViewProperties.context_menu;
    },

    /**
     * PUBLIC setLastUpdate
     *
     * Sets the time of last status update of this object
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    setLastUpdate: function() {
        this.lastUpdate = iNow;

        // Save datetime of the first state update (needed for hover parsing)
        if(this.firstUpdate === null)
            this.firstUpdate = this.lastUpdate;
    },

    /**
     * Locks/Unlocks the object and fires dependent actions
     * It returns +1,-1 or 0 depending on the final state of the object
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    toggleLock: function(lock) {
        var equal = false;
        if(isset(lock) && lock === this.bIsLocked)
            equal = true;

        if(isset(lock))
            this.bIsLocked = lock;
        else
            this.bIsLocked = !this.bIsLocked;

        for (var i = 0; i < this.elements.length; i++)
            if (this.bIsLocked)
                this.elements[i].lock();
            else
                this.elements[i].unlock();

        if(this.toggleObjControls()) {
            // Only save the user option when not using the edit_mode
            if(!isset(lock) && (!oViewProperties.hasOwnProperty('edit_mode') || oViewProperties['edit_mode'] !== true)) {
                var unlocked = [];
                if(oUserProperties.hasOwnProperty('unlocked-' + oPageProperties.map_name))
                    unlocked = oUserProperties['unlocked-' + oPageProperties.map_name].split(',');

                if(this.bIsLocked)
                    unlocked.splice(unlocked.indexOf(this.conf.object_id), 1);
                else
                    unlocked.push(this.conf.object_id);
                storeUserOption('unlocked-' + oPageProperties.map_name, unlocked.join(','));
                unlocked = null;
            }

            if(equal === true)
                return 0;
            else
                return this.bIsLocked ? -1 : 1;
        } else {
            return 0;
        }
    },

    /**
     * Shows or hides all object controls of a map object depending
     * on the lock state of this object.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    toggleObjControls: function() {
        if(!this.bIsLocked) {
            if(isset(this.parseControls)) {
                this.parseControls();
                return true;
            }
        } else {
            if(isset(this.removeControls)) {
                this.removeControls();
                return true;
            }
        }
        return false;
    },

    getObjLeft: function () {
        if (this.conf.x.toString().split(',').length > 1) {
            return Math.min.apply(Math, this.parseCoords(this.conf.x, 'x'));
        } else {
            return this.parseCoord(this.conf.x, 'x');
        }
    },

    getObjTop: function () {
        if (this.conf.x.toString().split(',').length > 1) {
            return Math.min.apply(Math, this.parseCoords(this.conf.y, 'y'));
        } else {
            return this.parseCoord(this.conf.y, 'y');
        }
    },

    getObjWidth: function () {
        var o = document.getElementById(this.conf.object_id + '-icondiv');
        if(o && o.clientWidth)
            return parseInt(o.clientWidth);
        else
            return 0;
    },

    getObjHeight: function () {
        var o = document.getElementById(this.conf.object_id + '-icondiv');
        if(o && o.clientHeight)
            return parseInt(o.clientHeight);
        else
            return 0;
    },

    /**
     * This method parses a given coordinate which can be a simple integer
     * which is simply returned or a reference to another object and/or
     * a specified anchor of the object.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    parseCoord: function(val, dir, addZoom) {
        if (addZoom === undefined)
            addZoom = true;

        var coord = 0;
        if(!isRelativeCoord(val)) {
            coord = parseInt(val);
        } else {
            // This must be an object id. Is there an offset given?
            if(val.search('%') !== -1) {
                var parts     = val.split('%');
                var objectId  = parts[0];
                var offset    = parts[1];
                var refObj    = getMapObjByDomObjId(objectId);
                if (refObj) {
                    coord = parseFloat(refObj.parseCoord(refObj.conf[dir], dir, false));
                    if (addZoom)
                        coord = addZoomFactor(coord, true);

                    if (addZoom)
                        coord += addZoomFactor(parseFloat(offset), false);
                    else
                        coord += parseFloat(offset);

                    return coord;
                }
            } else {
                // Only an object id. Get the coordinate and return it
                var refObj = getMapObjByDomObjId(val);
                if(refObj)
                    coord = parseInt(refObj.parseCoord(refObj.conf[dir], dir));
            }
        }

        if (addZoom)
            return addZoomFactor(coord, true);
        else
            return coord;
    },

    /**
     * Wrapper for the parseCoord method to parse multiple coords at once
     * e.g. for lines.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    parseCoords: function(val, dir, addZoom) {
        var l = [];

	if(val)
            l = val.toString().split(',');

        for(var i = 0, len = l.length; i < len; i++)
            l[i] = this.parseCoord(l[i], dir, addZoom);

        return l;
    },

    // Transform the current coords to absolute coords when relative
    makeAbsoluteCoords: function(num) {
        var x = num === -1 ? this.conf.x : this.conf.x.split(',')[num];
        var y = num === -1 ? this.conf.y : this.conf.y.split(',')[num];

        // Skip when already absolute
        if(!isRelativeCoord(x) && !isRelativeCoord(y))
            return;

        // Get parent object ids
        var xParent = this.getCoordParent(this.conf.x, num);
        var yParent = this.getCoordParent(this.conf.y, num);

        if(xParent == yParent) {
            var o = getMapObjByDomObjId(xParent);
            // Don't remove when another coord is a child of this object
            if(o && getKeys(this.getRelativeCoordsUsingParent(xParent)).length == 1) {
                o.delChild(this);
                o = null
            }
        } else {
            var o = getMapObjByDomObjId(xParent);
            // Don't remove when another coord is a child of this object
            if(o && getKeys(this.getRelativeCoordsUsingParent(xParent)).length == 1) {
                o.delChild(this);
                o = null
            }
            var o = getMapObjByDomObjId(yParent);
            // Don't remove when another coord is a child of this object
            if(o && getKeys(this.getRelativeCoordsUsingParent(yParent)).length == 1) {
                o.delChild(this);
                o = null
            }
        }
        xParent = null;
        yParent = null;

        // FIXME: Maybe the parent object is also a line. Then -1 is not correct
        //        But it is not coded to attach relative objects to lines. So it is no big
        //        deal to leave this as it is.
        if(num === -1) {
            this.conf.x = this.parseCoord(x, 'x', false);
            this.conf.y = this.parseCoord(y, 'y', false);
        } else {
            var old  = this.conf.x.split(',');
            old[num] = this.parseCoord(x, 'x', false);
            this.conf.x = old.join(',');

            old  = this.conf.y.split(',');
            old[num] = this.parseCoord(y, 'y', false);
            this.conf.y = old.join(',');
            old = null;
        }
    },

    // Transform the current coords to relative
    // coords to the given object
    makeRelativeCoords: function(oParent, num) {
        var xParent = this.getCoordParent(this.conf.x, num);
        var yParent = this.getCoordParent(this.conf.y, num);

        var x = num === -1 ? this.conf.x : this.conf.x.split(',')[num];
        var y = num === -1 ? this.conf.y : this.conf.y.split(',')[num];

        if(isRelativeCoord(x) && isRelativeCoord(y)) {
            // Skip this when already relative to the same object
            if(xParent == oParent.conf.object_id
              && yParent == oParent.conf.object_id)
                return;

            // If this object was attached to another parent before, remove the attachment
            if(xParent != oParent.conf.object_id) {
                var o = getMapObjByDomObjId(xParent);
                if(o) {
                    o.delChild(this);
                    o = null;
                }
            }
            if(yParent != oParent.conf.object_id) {
                var o = getMapObjByDomObjId(yParent);
                if(o) {
                    o.delChild(this);
                    o = null;
                }
            }
        }

        // Add this object to the new parent
        oParent.addChild(this);

        // FIXME: Maybe the parent object is also a line. Then -1 is not correct
        //        But it is not coded to attach relative objects to lines. So it is no big
        //        deal to leave this as it is.
        if(num === -1) {
            this.conf.x = this.getRelCoords(oParent, this.parseCoord(this.conf.x, 'x', false), 'x', -1);
            this.conf.y = this.getRelCoords(oParent, this.parseCoord(this.conf.y, 'y', false), 'y', -1);
        } else {
            var newX = this.getRelCoords(oParent, this.parseCoords(this.conf.x, 'x', false)[num], 'x', -1);
            var newY = this.getRelCoords(oParent, this.parseCoords(this.conf.y, 'y', false)[num], 'y', -1);

            var old  = this.conf.x.split(',');
            old[num] = newX;
            this.conf.x = old.join(',');

            old  = this.conf.y.split(',');
            old[num] = newY;
            this.conf.y = old.join(',');
        }
    },

    /**
     * Returns the object id of the parent object
     */
    getCoordParent: function(val, num) {
        var coord = num === -1 ? val.toString() : val.split(',')[num].toString();
        return coord.search('%') !== -1 ? coord.split('%')[0] : coord;
    },

    getRelCoords: function(refObj, val, dir, num) {
        var refPos = num === -1 ? refObj.conf[dir] : refObj.conf[dir].split(',')[num];
        var offset = parseInt(val) - parseInt(refObj.parseCoord(refPos, dir, false));
        var pre    = offset >= 0 ? '+' : '';
        val        = refObj.conf.object_id + '%' + pre + offset;
        refObj     = null;
        return val;
    },

    /**
     * Calculates new coordinates for the object where the given parameter
     * 'val' is the integer representing the current position of the object
     * in absolute px coordinates. If the object position is related to
     * another object this function detects it and transforms the abslute px
     * coordinate to a relative coordinate and returns it.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    calcNewCoord: function(val, dir, num) {
        if(!isset(num))
            var num = -1;

        var oldVal = num === -1 ? this.conf[dir] : this.conf[dir].split(',')[num];
        // Check if the current value is an integer or a relative coord
        if(isset(oldVal) && isRelativeCoord(oldVal)) {
            // This must be an object id
            var objectId = null;
            if(oldVal.search('%') !== -1)
                objectId = oldVal.split('%')[0];
            else
                objectId = oldVal;

            // Only an object id. Get the coordinate and return it
            var refObj = getMapObjByDomObjId(objectId);
            // FIXME: Maybe the parent object is also a line. Then -1 is not correct
            if(refObj)
                val = this.getRelCoords(refObj, val, dir, -1);
            objectId = null;
        } else if(num === -1) {
            val = Math.round(val);
        }
        oldVal = null;

        if(num === -1) {
            return val;
        } else {
            var old  = this.conf[dir].split(',');
            if(isRelativeCoord(val))
                old[num] = val;
            else
                old[num] = Math.round(val);
            return old.join(',');
        }
    },

    /**
     * Used to gather all referenced parent object ids from the object
     * configuration. Returns a object where the keys are the gathered
     * parent object ids.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    getParentObjectIds: function(num) {
        var parentIds = {};

        if(isset(num))
            var coords = (this.conf['x'].split(',')[num] + ',' + this.conf['y'].split(',')[num]).split(',');
        else
            var coords = (this.conf.x + ',' + this.conf.y).split(',');

        for(var i = 0, len = coords.length; i < len; i++)
            if(isRelativeCoord(coords[i]))
                if(coords[i].search('%') !== -1)
                    parentIds[coords[i].split('%')[0]] = true;
                else
                    parentIds[coords[i]] = true;
        coords = null;

        return parentIds;
    },

    /**
     * Returns the coord indexes which use a specific parent object_id
     */
    getRelativeCoordsUsingParent: function(parentId) {
        var matches = {};
        for(var i = 0, len = this.conf.x.split(',').length; i < len; i++) {
        if(this.getCoordParent(this.conf.x, i) === parentId && !isset(matches[i]))
            matches[i] = true;
        else if(this.getCoordParent(this.conf.y, i) === parentId && !isset(matches[i]))
            matches[i] = true;
        }
        return matches;
    },

    /**
     * This is used to add a child item to the object. Child items are
     * gathered automatically by the frontend. Child positions depend
     * on the related parent position on the map -> relative positioning.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    addChild: function(obj) {
        if(this.childs.indexOf(obj) === -1)
            this.childs.push(obj);
        obj = null;
    },

    delChild: function(obj) {
        this.childs.splice(this.childs.indexOf(obj), 1);
        obj = null;
    },

    /**
     * This method removes all attached map objects and make their coordinates
     * absolute.
     *
     * Find the coords which have a relative coord and are using
     * this object id as parent object. Then make these coordinates
     * absolute using child.makeAbsoluteCoords(num).
     * After that the change must be sent to the core using saveObject...
     */
    detachChilds: function() {
        for(var i = this.childs.length - 1; i >= 0; i--) {
        var nums = this.childs[i].getRelativeCoordsUsingParent(this.conf.object_id);
        var obj = this.childs[i];

        for(var num in nums) {
            obj.makeAbsoluteCoords(num);
        }

        saveObjectAttr(obj.conf.object_id, {'x': obj.conf.x, 'y': obj.conf.y });

        obj  = null;
        nums = null;
        }
    },

    /**
     * Returns the x coordinate of the object in px
     */
    parsedX: function() {
        return this.parseCoords(this.conf.x, 'x');
    },

    /**
     * Returns the y coordinate of the object in px
     */
    parsedY: function() {
        return this.parseCoords(this.conf.y, 'y');
    },

    /**
     * Entry point for repositioning objects in NagVis frontend
     * Handles whole redrawing of the object while moving. The new
     * coordinates have already been set
     */
    place: function() {
        for(var i = 0, l = this.elements.length; i < l; i++)
            this.elements[i].place();

        // Move child objects
        for(var i = 0, l = this.childs.length; i < l; i++)
            this.childs[i].place();

        // redraw the controls
        if(!this.bIsLocked)
            this.redrawControls();
    },

    /*** CONTROL FUNCTIONS ***/

    redrawControls: function () {
        if(typeof(this.removeControls) == 'function')
            this.removeControls();
        if(typeof(this.parseControls) == 'function')
            this.parseControls();
    },

    parseControls: function () {
        // Ensure the controls container exists
        var oControls = document.getElementById(this.conf.object_id+'-controls');
        if(!oControls) {
            oControls = document.createElement('div');
            oControls.setAttribute('id', this.conf.object_id+'-controls');
            if (this.dom_obj)
                this.dom_obj.appendChild(oControls);
        }
        oControls = null;

        if(this.conf.view_type === 'line' || this.conf.type === 'line')
            this.parseLineControls();
    },

    addControl: function (obj) {
        var o = document.getElementById(this.conf.object_id+'-controls');
        if(o) {
            // Add to DOM
            o.appendChild(obj);
            o = null;

            // Add to controls list
            this.objControls.push(obj);
        }
    },

    /**
     * Toggles the position of the line middle. The mid of the line
     * can either be the 2nd of three line coords or is automaticaly
     * the middle between two line coords.
     */
    toggleLineMidLock: function() {
        // What is the current state?
        var x = this.conf.x.split(',');
        var y = this.conf.y.split(',')

        if(this.conf.line_type != 10 && this.conf.line_type != 13
           && this.conf.line_type != 14 && this.conf.line_type != 15) {
            alert('Not available for this line. Only lines with 2 line parts have a middle coordinate.');
            return;
        }

        if(x.length == 2) {
            // The line has 2 coords configured
            // - Calculate and add the 3rd coord as 2nd
            // - Add a drag control for the 2nd coord
            this.conf.x = [
              x[0],
              middle(this.parseCoords(this.conf.x, 'x', false)[0], this.parseCoords(this.conf.x, 'x', false)[1], this.conf.line_cut),
              x[1],
            ].join(',');
            this.conf.y = [
                y[0],
                middle(this.parseCoords(this.conf.y, 'y', false)[0], this.parseCoords(this.conf.y, 'y', false)[1], this.conf.line_cut),
                y[1],
            ].join(',');
        } else {
            // The line has 3 coords configured
            // - Remove the 2nd coord
            // - Remove the drag control for the 2nd coord
            this.conf.x = [ x[0], x[2] ].join(',');
            this.conf.y = [ y[0], y[2] ].join(',');
        }

        // send to server
        saveObjectAttr(this.conf.object_id, { 'x': this.conf.x, 'y': this.conf.y});

        // redraw the controls
        if(!this.bIsLocked)
            this.redrawControls();

        // redraw the line
        this.drawLine();
    },

    needsContextMenu: function () {
        return (this.conf.context_menu && this.conf.context_menu !== '' && this.conf.context_menu !== '0'
            && this.conf.context_template && this.conf.context_template !== '') || !this.bIsLocked;
    },

    needsHoverMenu: function() {
        return this.conf.hover_menu && this.conf.hover_menu !== '' && this.conf.hover_menu !== '0'
            && ((this.conf.hover_template && this.conf.hover_template !== '') ||
                (this.conf.hover_url && this.conf.hover_url !== ''));
    },

    needsLink: function() {
        return this.conf.url && this.conf.url !== '' && this.conf.url !== '#';
    },

    needsLineHoverArea: function() {
        return this.needsHoverMenu() || this.needsContextMenu() || this.needsLink() || !this.bIsLocked;
    },

    parseLineControls: function () {
        var x = this.parseCoords(this.conf.x, 'x');
        var y = this.parseCoords(this.conf.y, 'y');

        var size = oGeneralProperties['controls_size'];
	var lineEndSize = size;
	if(size < 20)
	    lineEndSize = 20;
        var obj;
        for(var i = 0, l = x.length; i < l; i++) {
	    // Line middle drag coord needs to be smaller
	    if(l > 2 && i == 1) 
		obj = this.parseControlDrag(i, x[i], y[i], - size / 2, - size / 2, size);
	    else
		obj = this.parseControlDrag(i, x[i], y[i], - lineEndSize / 2, - lineEndSize / 2, lineEndSize);
            makeDragable(obj, this, this.saveObject, this.moveObject);
        }

        if(this.conf.view_type === 'line' && (this.conf.line_type == 10
           || this.conf.line_type == 13 || this.conf.line_type == 14 || this.conf.line_type == 15))
	    this.parseControlToggleLineMid(x.length+2, this.getLineMid(this.conf.x, 'x'), this.getLineMid(this.conf.y, 'y'), 20 - size / 2, -size / 2 + 5, size);

        lineEndSize = null;
        size = null;
        x = null;
        y = null;
    },

    getLineMid: function(coord, dir) {
        var c = coord.split(',');
        if(c.length == 2)
            return middle(this.parseCoords(coord, dir)[0],
                          this.parseCoords(coord, dir)[1],
                          this.conf.line_cut);
        else
            return this.parseCoords(coord, dir)[1];
    },

    removeControls: function() {
        var oControls = document.getElementById(this.conf.object_id+'-controls');
        if(oControls)
            for(var i = oControls.childNodes.length; i > 0; i--)
                oControls.removeChild(oControls.childNodes[0]);
        this.objControls = [];
        oControls = null;
    },

    parseControlDrag: function (num, objX, objY, offX, offY, size) {
        var ctl = document.createElement('div');
        ctl.setAttribute('id',         this.conf.object_id+'-drag-' + num);
        ctl.setAttribute('class',     'control drag');
        ctl.setAttribute('className', 'control drag');
	// FIXME: Multilanguage
	ctl.title          = 'Move object';
        ctl.style.zIndex   = parseInt(this.conf.z)+1;
        ctl.style.width    = addZoomFactor(size) + 'px';
        ctl.style.height   = addZoomFactor(size) + 'px';
        ctl.style.left     = (objX + offX) + 'px';
        ctl.style.top      = (objY + offY) + 'px';
        ctl.objOffsetX     = offX;
        ctl.objOffsetY     = offY;

        ctl.onmouseover = function() {
            document.body.style.cursor = 'move';
        };

        ctl.onmouseout = function() {
            document.body.style.cursor = 'auto';
        };

        this.addControl(ctl);
        return ctl;
    },

    /**
     * Adds the modify button to the controls including
     * all eventhandlers
     *
     * Author: Lars Michelsen <lm@larsmichelsen.com>
     */
    parseControlToggleLineMid: function (num, objX, objY, offX, offY, size) {
        var ctl= document.createElement('div');
        ctl.setAttribute('id',         this.conf.object_id+'-togglemid-' + num);
        ctl.setAttribute('class',     'control togglemid');
        ctl.setAttribute('className', 'control togglemid');
	// FIXME: Multilanguage
	ctl.title          = 'Lock/Unlock line middle';
        ctl.style.zIndex   = parseInt(this.conf.z)+1;
        ctl.style.width    = addZoomFactor(size) + 'px';
        ctl.style.height   = addZoomFactor(size) + 'px';
        ctl.style.left     = (objX + offX) + 'px';
        ctl.style.top      = (objY + offY) + 'px';
        ctl.objOffsetX     = offX;
        ctl.objOffsetY     = offY;

        ctl.onclick = function(event) {
            // In the event handler this points to the ctl object
            var arr   = this.id.split('-');
            var objId = arr[0];

	    toggleLineMidLock(event, objId);
	    contextHide(); // FIXME: Cleanup to close the context menu of the current object

            objId = null;
            arr   = null;

            document.body.style.cursor = 'auto';
        };

        ctl.onmouseover = function() {
            document.body.style.cursor = 'pointer';
        };

        ctl.onmouseout = function() {
            document.body.style.cursor = 'auto';
        };

        this.addControl(ctl);
        ctl = null;
    },

    /**
     * Handler for the move event
     *
     * Important: This is called from an event handler
     * the 'this.' keyword can not be used here.
     */
    moveObject: function(trigger_obj, obj) {
        var arr = trigger_obj.id.split('-');
        var newPos;
        if (obj.conf.view_type === 'line') {
            newPos = getMidOfAnchor(trigger_obj);

            // Get current positions and replace only the current one
            var anchorId = arr[2];
            newPos = [ obj.calcNewCoord(newPos[0], 'x', anchorId),
                       obj.calcNewCoord(newPos[1], 'y', anchorId) ];

            var parents = obj.getParentObjectIds(anchorId);

            anchorId   = null;
        } else {
            // In case of an anchor there is an offset to the real object.
            // Handle this offset in the coordinate calculation for the obj
            var offsetX = isset(trigger_obj.objOffsetX) ? trigger_obj.objOffsetX : 0;
            var offsetY = isset(trigger_obj.objOffsetY) ? trigger_obj.objOffsetY : 0;

            newPos = [ obj.calcNewCoord(trigger_obj.x - offsetX, 'x'),
                       obj.calcNewCoord(trigger_obj.y - offsetY, 'y') ];

            var parents = obj.getParentObjectIds();
        }

        // Highlight parents when relative
        for (var objectId in parents) {
            var p = getMapObjByDomObjId(objectId);
            if(p) {
                p.highlight(true);
                p = null;
            }
        }

        obj.conf.x = newPos[0];
        obj.conf.y = newPos[1];
        obj.place();
    },

    /**
     * Handler for the drop event
     *
     * Important: This is called from an event handler
     * the 'this.' keyword can not be used here.
     */
    saveObject: function(trigger_obj, obj, oParent) {
        var arr = trigger_obj.id.split('-');
        if(arr.length > 2)
            var anchorId = arr[2];
        if (obj.conf.view_type !== 'line')
            anchorId = -1;

        // Honor the enabled grid and reposition the object after dropping
        if (useGrid()) {
            if (obj.conf.view_type === 'line') {
               var pos = coordsToGrid(obj.parseCoords(obj.conf.x, 'x', false)[anchorId],
                                      obj.parseCoords(obj.conf.y, 'y', false)[anchorId]);
               obj.conf.x = obj.calcNewCoord(pos[0], 'x', anchorId);
               obj.conf.y = obj.calcNewCoord(pos[1], 'y', anchorId);
            } else {
               var pos = coordsToGrid(obj.parseCoord(obj.conf.x, 'x', false),
                                      obj.parseCoord(obj.conf.y, 'y', false));
               obj.conf.x = obj.calcNewCoord(pos[0], 'x');
               obj.conf.y = obj.calcNewCoord(pos[1], 'y');
            }
            obj.place();
        }

        // Make relative when oParent set and not already relative
        if (isset(oParent))
            if(oParent !== false)
                obj.makeRelativeCoords(oParent, anchorId);
            else
                obj.makeAbsoluteCoords(anchorId);

        saveObjectAfterAnchorAction(trigger_obj);

        // Remove the dragging hand after dropping
        document.body.style.cursor = 'auto';
    },

    highlight: function(show) {}
});
