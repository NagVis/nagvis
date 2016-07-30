/*****************************************************************************
 *
 * edit.js - Some NagVis edit code
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

function getTargetRaw(event) {
    return event.target ? event.target : event.srcElement;
}

function getTargetByClass(event, className) {
    var target = getTargetRaw(event);
    while (target && !has_class(target, className))
        target = target.parentNode;
    return target;
}

function toggleMapObjectLock(event, object_id) {
    g_view.toggleObjectLock(object_id);
    return preventDefaultEvents(event);
}

// Toggles the mode of all map objects: editable or not
function toggleAllMapObjectsLock(event) {
    var lock = g_view.hasUnlocked();

    for (var object_id in g_view.objects)
        g_view.toggleObjectLock(object_id, lock);

    if (!lock)
        storeUserOption('unlocked-' + oPageProperties.map_name, '*');
    else
        storeUserOption('unlocked-' + oPageProperties.map_name, '');
    return preventDefaultEvents(event);
}

var draggingEnabled = true;
var draggingObject = null;
var dragObjectOffset = null;
var dragObjectPos = null;
var dragObjectStartPos = null;
var dragObjectChilds = {};
var dragStopHandlers = {};
var dragMoveHandlers = {};
var dragObjects      = {};

var g_resize_obj = null; //This gets a value as soon as a resize start

/** Object resizing **/

function g_resize_object() {
    this.el        = null; //pointer to the object
    this.dir    = "";      //type of current resize (n, s, e, w, ne, nw, se, sw)
    this.grabx = null;     //Some useful values
    this.graby = null;
    this.width = null;
    this.height = null;
    this.left = null;
    this.top = null;
}

// Find out what kind of resize! Return a string inlcluding the directions
function getDirection(event, el) {
    var xPos, yPos, offset, dir;
    dir = "";

    // Handle IE and other browsers
    xPos = event.layerX ? event.layerX : event.offsetX ? event.offsetX : 0;
    yPos = event.layerY ? event.layerY : event.offsetY ? event.offsetY : 0;

    // The distance from the edge in pixels
    offset = 8;

    if (yPos < offset) {
        dir += "n";
    }	else if (yPos > el.offsetHeight - offset) {
        dir += "s";
    }

    if(xPos < offset) {
        dir += "w";
    }	else if (xPos > el.offsetWidth - offset) {
        dir += "e";
    }

    return dir;
}

function resizeMouseDown(event) {
    event = event || window.event;
    var target = getTargetByClass(event, 'resizeable');

    if (!target || target.id == '')
        return true;

    var dir = getDirection(event, target);
    if (dir == "")
        return true;

    // Disable dragging while resizing
    draggingEnabled = false;
    draggingObject = null;

    g_resize_obj = new g_resize_object();

    g_resize_obj.el = target;
    g_resize_obj.dir = dir;

    g_resize_obj.grabx  = event.clientX;
    g_resize_obj.graby  = event.clientY;
    g_resize_obj.width  = target.offsetWidth;
    g_resize_obj.height = target.offsetHeight;
    g_resize_obj.left   = pxToInt(target.style.left);
    g_resize_obj.top    = pxToInt(target.style.top);

    return preventDefaultEvents(event);
}

function resizeMouseUp(event) {
    event = event || window.event;
    if (g_resize_obj === null)
        return true;

    // Re-enable dragging
    draggingEnabled = true;
    draggingObject = null;

    var dom_obj = g_resize_obj.el;

    var objId = dom_obj.id.split('-')[0];
    var objX = rmZoomFactor(pxToInt(dom_obj.style.left), true);
    var objY = rmZoomFactor(pxToInt(dom_obj.style.top), true);
    var objW = rmZoomFactor(parseInt(dom_obj.style.width));
    var objH = rmZoomFactor(parseInt(dom_obj.style.height));

    // Reposition in frontend
    var obj = getMapObjByDomObjId(objId);
    obj.conf.x = objX;
    obj.conf.y = objY;
    obj.conf.w = objW;
    obj.conf.h = objH;
    obj.place();

    if (!isInt(objX) || !isInt(objY) || !isInt(objW) || !isInt(objH)) {
        alert('ERROR: Invalid coords ('+objX+'/'+objY+'/'+objW+'/'+objH+'). Terminating.');
        return false;
    }

    var parts = g_view.unproject(objX, objY);
    objX = parts[0];
    objY = parts[1];

    saveObjectAttr(objId, {
        'x': objX,
        'y': objY,
        'w': objW,
        'h': objH
    });

    g_resize_obj = null;

    return preventDefaultEvents(event);
}

function resizeMouseMove(event) {
    event = event || window.event;
    var target = getTargetByClass(event, 'resizeable');

    // First update the cursor. This needs to be done even
    // when not yet resizing to visualize that it is possible

    if (target) {
        var str = getDirection(event, target);

        // Fix the cursor
        if (str == "")
            str = "";
        else
            str += "-resize";
        target.style.cursor = str;
    }

    // The following code is only relevant when already resizing

    if (g_resize_obj === null)
        return true;

    var xMin = 8, // The smallest width and height possible
        yMin = 8;

    if(g_resize_obj.dir.indexOf("e") != -1)
        g_resize_obj.el.style.width = Math.max(xMin, g_resize_obj.width + event.clientX - g_resize_obj.grabx) + "px";

    if(g_resize_obj.dir.indexOf("s") != -1) {
        g_resize_obj.el.style.height = Math.max(yMin, g_resize_obj.height + event.clientY - g_resize_obj.graby) + "px";
    }

    if(g_resize_obj.dir.indexOf("w") != -1) {
        g_resize_obj.el.style.left = Math.min(g_resize_obj.left + event.clientX - g_resize_obj.grabx, g_resize_obj.left + g_resize_obj.width - xMin) + "px";
        g_resize_obj.el.style.width = Math.max(xMin, g_resize_obj.width - event.clientX + g_resize_obj.grabx) + "px";
    }
    if(g_resize_obj.dir.indexOf("n") != -1) {
        g_resize_obj.el.style.top = Math.min(g_resize_obj.top + event.clientY - g_resize_obj.graby, g_resize_obj.top + g_resize_obj.height - yMin) + "px";
        g_resize_obj.el.style.height = Math.max(yMin, g_resize_obj.height - event.clientY + g_resize_obj.graby) + "px";
    }

    return preventDefaultEvents(event);
}

function makeResizeable(trigger_obj) {
    add_class(trigger_obj, 'resizeable');
    addEvent(trigger_obj, 'mousedown', resizeMouseDown);
    addEvent(trigger_obj, 'mouseup', resizeMouseUp);
}

function makeUnresizeable(trigger_obj) {
    // when locking the object while the cursor is a resize cursor,
    // it will stay as it is, when not removing them.
    trigger_obj.style.cursor = '';
    remove_class(trigger_obj, 'resizeable');
    removeEvent(trigger_obj, 'mousedown', resizeMouseDown);
    removeEvent(trigger_obj, 'mouseup', resizeMouseUp);
}

/*** Handles the object dragging ***/

function getButton(event) {
    if (event.which == null)
        /* IE case */
        return (event.button < 2) ? "LEFT" : ((event.button == 4) ? "MIDDLE" : "RIGHT");
    else
        /* All others */
        return (event.which < 2) ? "LEFT" : ((event.which == 2) ? "MIDDLE" : "RIGHT");
}

function makeUndragable(trigger_obj) {
    delete dragStopHandlers[trigger_obj.id];
    delete dragMoveHandlers[trigger_obj.id];
    delete dragObjects[trigger_obj.id];

    remove_class(trigger_obj, 'dragger');

    removeEvent(trigger_obj, 'mousedown', dragStart);
    removeEvent(document, 'mouseup', dragStop);
}

function makeDragable(trigger_obj, obj, dragStopHandler, dragMoveHandler) {
    dragStopHandlers[trigger_obj.id] = dragStopHandler;
    dragMoveHandlers[trigger_obj.id] = dragMoveHandler;
    dragObjects[trigger_obj.id] = obj;

    add_class(trigger_obj, 'dragger');

    addEvent(trigger_obj, "mousedown", dragStart);
    // The drag stop event is registered globally on the whole document to prevent
    // problems with too fast mouse movement which might lead to lag the dragging
    // object behind the mouse and make it impossible to stop dragging.
    addEvent(document, "mouseup", dragStop);
}

/**
 * This function is called once an object is picked for dragging
 */
function dragStart(event) {
    event = event || window.event;

    var target = getTargetByClass(event, 'dragger');
    var button = getButton(event);

    // Skip calls when already dragging or other button than left mouse
    if (draggingObject !== null || button != 'LEFT' || !target || !draggingEnabled)
        return true;

    contextHide();

    var parts = getEventMousePos(event),
        posx  = parts[0],
        posy  = parts[1];

    draggingObject = target;
    draggingObject.x = rmZoomFactor(pxToInt(draggingObject.style.left), true);
    draggingObject.y = rmZoomFactor(pxToInt(draggingObject.style.top), true);

    // Save relative offset of the mouse
    dragObjectOffset   = [ posx - draggingObject.x, posy - draggingObject.y ];
    dragObjectStartPos = [ draggingObject.x, draggingObject.y ];

    // Save diff coords of relative objects
    var sLabelName = target.id.replace('box_', 'rel_label_');
    var oLabel = document.getElementById(sLabelName);
    if(oLabel) {
        dragObjectChilds[sLabelName] = [ oLabel.offsetLeft - draggingObject.x,
                                         oLabel.offsetTop - draggingObject.y ];
    }
    return preventDefaultEvents(event);
}

/**
 * This function is called repeated while the object is being dragged
 */
function dragObject(event) {
    event = event || window.event;

    if (draggingObject === null || !draggingEnabled)
        return true;

    var parts = getEventMousePos(event),
        posx  = parts[0],
        posy  = parts[1],
        newLeft = posx - dragObjectOffset[0],
        newTop  = posy - dragObjectOffset[1];

    // skip further handling when moving out of screen
    if (typeof posx === 'undefined' || typeof posy === undefined)
        return preventDefaultEvents(event);

    draggingObject.style.position = 'absolute';
    draggingObject.style.left = addZoomFactor(newLeft) + 'px';
    draggingObject.style.top  = addZoomFactor(newTop) + 'px';
    draggingObject.x = newLeft;
    draggingObject.y = newTop;

    // When this object has a relative coordinated label, then move this too
    moveRelativeObject(draggingObject.id, newTop, newLeft);

    // Is this object currently relative positioned?
    var idParts = draggingObject.id.split('-');
    var obj = g_view.objects[idParts[0]];
    if (obj.conf.view_type === 'line') {
        var anchorId = idParts[2];
        var parents = obj.getParentObjectIds(anchorId);
    } else {
        var parents = obj.getParentObjectIds();
    }
    var isRel = Object.keys(parents).length > 0;

    // Unhighlight all other objects
    for(var i in g_view.objects)
        g_view.objects[i].highlight(false);


    // Highlight parents when relative
    for (var objectId in parents)
        g_view.objects[objectId].highlight(true);

    // With pressed CTRL key the icon should be docked
    // This means the object will be positioned relative to that object
    // This code only highlights that object. When the CTRL key is still pressed
    // when dropping the object the currently moved object will be positioned
    // relative to this object.
    var msg = null;
    if(event.ctrlKey) {
        // Find the nearest object to the current position and highlight it
        var o = getNearestObject(draggingObject, newLeft, newTop)
        if(o) {
            o.highlight(true);
            o = null;
        }

        if (!isRel)
            msg = 'Hold CTRL till drop for relative positioning';
    }

    // Shift key makes the object absolute positioned when still held during dropping
    else if (event.shiftKey) {
        // Unhighlight all objects
        for(var i in g_view.objects)
            g_view.objects[i].highlight(false);

        if (isRel)
            msg = 'Hold SHIFT till drop for absolute positioning';
    } else {
        if (isRel)
            msg = 'Press SHIFT for absolute positioning';
        else
            msg = 'Press CTRL for relative positioning';
    }

    if (msg !== null)
        displayStatusMessage(msg, 'notice', true);

    // Call the dragging handler when one is set
    if(dragMoveHandlers[draggingObject.id])
        dragMoveHandlers[draggingObject.id](draggingObject,
                                            dragObjects[draggingObject.id], event);
    return preventDefaultEvents(event);
}

/**
 * Is called to find the nearest object to the given position. This must
 * check if there is a direct or indirect reference to the current object
 * in order to prevent relative coordinate loops.
 */
function getNearestObject(draggingObject, x, y) {
    var nearest = null;
    var min     = null;
    var dist;

    var obj;
    for(var i in g_view.objects) {
        obj = g_view.objects[i];

        // Skip own object
        if(draggingObject.id.split('-')[0] == obj.conf.object_id)
            continue;

        // FIXME: Also handle lines
        if(obj.conf.view_type !== 'icon' || obj.conf.type == 'line')
            continue;

        var objX = obj.parseCoord(obj.conf.x, 'x');
        var objY = obj.parseCoord(obj.conf.y, 'y');
        dist = Math.sqrt(((objX - x) * (objX - x)) + ((objY - y) * (objY - y)));
        if(min === null || dist < min) {
            // Got a nearer one. Ok. But does it have a reference to us?
            if(coordsReferTo(obj, draggingObject.id.split('-')[0])) {
                continue;
            }
            min     = dist;
            nearest = obj;
        }
    }

    obj     = null;
    min     = null;
    dist    = null;
    return nearest;
}

/**
 * This function checks wether or not the source object coords refert to
 * the target object directly or indirectly
 */
function coordsReferTo(obj, target_object_id) {
    if (obj.conf.object_id == target_object_id) {
        return true;
    }

    if (isRelativeCoord(obj.conf.x)) {
        var xParent = getMapObjByDomObjId(obj.getCoordParent(obj.conf.x, -1));
        if(coordsReferTo(xParent, target_object_id)) {
            return true;
        }
    }

    if (isRelativeCoord(obj.conf.y)) {
        var yParent = getMapObjByDomObjId(obj.getCoordParent(obj.conf.y, -1));
        if(coordsReferTo(yParent, target_object_id)) {
            return true;
        }
    }

    return false;
}

function moveRelativeObject(parentId, parentTop, parentLeft) {
    var sLabelName = parentId.replace('box_', 'rel_label_');
    if(typeof dragObjectChilds[sLabelName] !== 'undefined') {
        var oLabel = document.getElementById(sLabelName);
        if(oLabel) {
            oLabel.style.position = 'absolute';
            oLabel.style.left = (dragObjectChilds[sLabelName][0] + parentLeft) + 'px';
            oLabel.style.top  = (dragObjectChilds[sLabelName][1] + parentTop) + 'px';
            oLabel = null;
        }
    }
    sLabelName = null;
}

function dragStop(event) {
    if(draggingObject === null || !draggingEnabled
       || typeof draggingObject.y == 'undefined' || typeof draggingObject.x == 'undefined')
        return;

    hideStatusMessage();

    // When x or y are negative just return this and make no change
    if(draggingObject.y < 0 || draggingObject.x < 0) {
        draggingObject.style.left = dragObjectStartPos[0] + 'px';
        draggingObject.style.top  = dragObjectStartPos[1] + 'px';
        draggingObject.x = dragObjectStartPos[0];
        draggingObject.y = dragObjectStartPos[1];

        moveRelativeObject(draggingObject.id, dragObjectStartPos[1], dragObjectStartPos[0]);

        // Call the dragging handler when one is set
        if(dragMoveHandlers[draggingObject.id])
            dragMoveHandlers[draggingObject.id](draggingObject, dragObjects[draggingObject.id], event);

        draggingObject = null;
        return;
    }

    // Skip when the object has not been moved
    if(draggingObject.y == dragObjectStartPos[1] && draggingObject.x == dragObjectStartPos[0]) {
        draggingObject = null;
        return;
    }

    var oParent = null;
    if(event.ctrlKey) {
        var oParent = getNearestObject(draggingObject, draggingObject.x, draggingObject.y);
        if(oParent)
            oParent.highlight(false);
    }

    if(event.shiftKey)
        oParent = false;

    // Unhilight parent objects
    for(var objectId in getMapObjByDomObjId(draggingObject.id.split('-')[0]).getParentObjectIds())
        getMapObjByDomObjId(objectId).highlight(false);

    dragStopHandlers[draggingObject.id](draggingObject, dragObjects[draggingObject.id], oParent);

    oParent = null;
    draggingObject    = null;
}

/**
 * Returns true when currently dragging an object
 */
function dragging() {
    return draggingObject !== null;
}

/** add object code **/

var addObjType  = null,
    addViewType = null,
    addNumLeft  = null,
    addAction   = null,
    addX        = [],
    addY        = [],
    addFollow   = false,
    addShape    = null,
    cloneId     = null;

function cloneObject(e, objId) {
    cloneId = objId;
    var obj = getMapObjByDomObjId(objId);

    var numClicks = 1;
    if(obj.conf.type == 'textbox'|| obj.conf.type == 'container' || obj.conf.view_type == 'line' || obj.type == 'line')
        numClicks = 2;

    return addObject(e, obj.conf.type, obj.conf.view_type, numClicks, 'clone');
}

/**
 * Is called once to start the object creation
 */
function addObject(event, objType, viewType, numLeft, action) {
    event = event || window.event;

    addObjType  = objType;
    addViewType = viewType;
    addNumLeft  = numLeft;
    addAction   = action;

    add_class(document.body, 'add');

    return preventDefaultEvents(event);
}

function getEventMousePos(event) {
    event = event || window.event;

    // Only accept "left" mouse clicks
    if(getButton(event) != 'LEFT')
        return null;

    // Ignore clicks on the header menu
    if(event.target) {
        var target = event.target;
        while(target) {
            if(target.id && target.id == 'header') {
                return false;
            }
            target = target.parentNode;
        }
    }

    var posx, posy;
    if (event.pageX || event.pageY) {
        posx = event.pageX;
        posy = event.pageY;
    } else if (event.clientX || event.clientY) {
        posx = event.clientX;
        posy = event.clientY;
    }

    // Substract height of header menu here
    posy -= getHeaderHeight();

    // Take the zoom into account. If the map is zoomed this function gathers
    // coordinates where the zoom factor is included. It has to be removed for
    // further processing.
    posx = rmZoomFactor(posx, true);
    posy = rmZoomFactor(posy, true);

    return [ posx, posy ];
}

function stopAdding() {
    remove_class(document.body, 'add');
    addObjType  = null,
    addViewType = null,
    addNumLeft  = null,
    addAction   = null,
    addX        = [],
    addY        = [],
    addFollow   = false,
    addShape    = null;
}

function addClick(e) {
    if(!adding())
        return true;

    var pos = getEventMousePos(e);
    if (pos === false) {
        // abort adding when clicking on the header
        stopAdding();
        return false;
    }

    addX.push(pos[0] - getSidebarWidth());
    addY.push(pos[1]);
    addNumLeft -= 1;
    pos = null;

    // Draw a line to illustrate the progress of drawing the current line
    if((addViewType === 'line' || addObjType === 'textbox' || addObjType === 'container' || addObjType === 'line')
       && addShape === null) {
        addShape = new jsGraphics('map');
        addShape.cnv.setAttribute('id', 'drawing');

        addShape.setColor('#06B606');
        addShape.setStroke(1);

        addFollow = true;
    }

    if(addNumLeft > 0)
        return false;

    //
    // If this is reached all object coords have been collected
    //

    if(addObjType == 'textbox' || addObjType == 'container') {
        var w = addX.pop() - addX[0];
        var h = addY.pop() - addY[0];
    }

    var parts = g_view.unproject(addX, addY);
    addX = parts[0];
    addY = parts[1];

    var sUrl = '';
    if(addAction == 'add' || addAction == 'clone')
        sUrl = oGeneralProperties.path_server + '?mod=Map&act=addModify'
               + '&show=' + oPageProperties.map_name
               + '&type=' + addObjType
               + '&x=' + addX.join(',')
               + '&y=' + addY.join(',');

    if(addObjType != 'textbox' && addObjType != 'container' 
       && addObjType != 'shape' && addViewType != 'icon' && addViewType != '')
        sUrl += '&view_type=' + addViewType;

    if(addAction == 'clone' && cloneId !== null)
        sUrl += '&clone_id=' + cloneId;

    if(addObjType == 'textbox' || addObjType == 'container')
        sUrl += '&w=' + w+ '&h=' + h;

    if(sUrl === '')
        return false;

    // remove the drawing area. Once reached this it is not needed anymore
    if(addShape !== null) {
        addShape.clear();
        document.getElementById('map').removeChild(addShape.cnv);
    }

    showFrontendDialog(sUrl, _('Create Object'));
    stopAdding();
    return false;
}

function adding() {
    return addNumLeft !== null;
}

function addFollowing(e) {
    if(!addFollow)
        return true;

    var pos = getEventMousePos(e);

    var start_x = addZoomFactor(addX[0]),
        start_y = addZoomFactor(addY[0]);

    var end_x = addZoomFactor(pos[0]),
        end_y = addZoomFactor(pos[1]);

    addShape.clear();

    if(addViewType === 'line' || addObjType === 'line')
        addShape.drawLine(start_x, start_y, end_x - getSidebarWidth(), end_y);
    else
        addShape.drawRect(start_x, start_y, (end_x - getSidebarWidth() - start_x),
                                            (end_y - start_y));

    addShape.paint();
}

function useGrid() {
    return oViewProperties.grid_show === 1;
}

/**
 * Parses a grind to make the alignment of the icons easier
 */
function gridParse() {
    // Only show when user configured to see a grid
    if (!useGrid())
        return;

    // Create grid container and append to map
    var oGrid = document.getElementById('grid');
    if (!oGrid) {
        var oGrid = document.createElement('div');
        oGrid.setAttribute('id', 'grid');
        document.getElementById('map').appendChild(oGrid);
    }
    else {
        while (oGrid.firstChild) {
            oGrid.removeChild(oGrid.firstChild);
        }
    }

    // Add options: grid_show, grid_steps, grid_color
    var line = document.createElement('div');
    line.style.backgroundColor = oViewProperties.grid_color;

    var gridStep = addZoomFactor(oViewProperties.grid_steps);
    var gridYStart = 0;
    var gridXStart = 0;
    var gridYEnd = pageHeight() - getHeaderHeight();
    var gridXEnd = pageWidth();

    var vline = null;
    for(var gridX = gridStep; gridX < gridXEnd; gridX = gridX + gridStep) {
        vline = line.cloneNode();
        vline.className = "vertical";
        vline.style.left   = gridX + 'px';
        vline.style.top    = gridYStart + 'px';
        vline.style.bottom = 0;
        oGrid.appendChild(vline);
    }

    var hline;
    for(var gridY = gridStep; gridY < gridYEnd; gridY = gridY + gridStep) {
        hline = line.cloneNode();
        hline.className = "horizontal";
        hline.style.top    = gridY +'px';
        hline.style.left   = gridXStart + 'px';
        hline.style.right  = 0;
        oGrid.appendChild(hline);
    }

    addEvent(window, "resize", gridRedraw);
    addEvent(window, "scroll", gridRedraw);
}

function gridRemove() {
    var oGrid = document.getElementById('grid');
    if (oGrid)
        oGrid.parentNode.removeChild(oGrid);

    removeEvent(window, "resize", gridRedraw);
    removeEvent(window, "scroll", gridRedraw);
}

function gridRedraw() {
    gridRemove();
    gridParse();
}

/**
 * Toggle the grid state in the current view and sends
 * current setting to server component for persistance
 */
function gridToggle() {
    // Toggle the grid state
    if(useGrid()) {
        oViewProperties.grid_show = 0;
        gridRemove();
    } else {
        oViewProperties.grid_show = 1;
        gridParse();
    }

    // Send current option to server for persistance
    call_ajax(oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='
              + oPageProperties.map_name+'&type=global&id=0&grid_show='+oViewProperties.grid_show);
}

/**
 * Alligns the current coordinates to the current grid
 */
function coordsToGrid(x, y) {
    x = "" + x;
    y = "" + y;
    if(x.indexOf(',') !== -1) {
        x = x.split(',');
        y = y.split(',');
        for(var i = 0; i < x.length; i++) {
            x[i] = x[i] - (x[i] % addZoomFactor(oViewProperties.grid_steps));
            y[i] = y[i] - (y[i] % addZoomFactor(ooViewProperties.grid_steps));
        }
        return [ x.join(','), y.join(',') ];
    } else {
        x = +x;
        y = +y;
        var gridMoveX = x - (x % addZoomFactor(oViewProperties.grid_steps));
        var gridMoveY = y - (y % addZoomFactor(oViewProperties.grid_steps));
        return [ gridMoveX, gridMoveY ];
    }
}

function toggle_section(sec) {
    var sections = document.getElementsByClassName('section');
    for (var i = 0; i < sections.length; i++) {
        if (sections[i].id == 'sec_' + sec)
            sections[i].style.display = '';
        else
            sections[i].style.display = 'none';
    }

    // update the navigation
    var nav_items = document.getElementById('nav').childNodes;
    for (var i = 0; i < nav_items.length; i++) {
        if (nav_items[i].id == 'nav_' + sec)
            add_class(nav_items[i], 'active');
        else
            remove_class(nav_items[i], 'active');
    }

    // update the helper field value
    document.getElementById('sec').value = sec;
}

// Toggles the state of an option (Showing inherited value or the current configured value)
function toggle_option(name) {
    var field = document.getElementById(name);
    var txt   = document.getElementById('_txt_' + name);

    if(field && txt) {
        if(field.style.display === 'none') {
            field.style.display = '';
            txt.style.display = 'none';
        } else {
            field.style.display = 'none';
            txt.style.display = '';
        }
        txt = null;
        field = null;
    }
}

function togglePicker(id, state) {
    var o = document.getElementById(id);
    if(jscolor.picker && jscolor.picker.owner == o.color)
        o.color.hidePicker();
    else
        o.color.showPicker();
    o = null;
}

function pickWindowSize(id, dimension) {
    var o = document.getElementById(id);
    if(dimension == 'width') {
        o.value = pageWidth();
    } else {
        o.value = pageHeight() - getHeaderHeight();
    }
    o = null;
}

function updateUserRoles(bAdd) {
    var user_roles = document.getElementById('user_roles');
    var available  = document.getElementById('roles_available');
    var selected   = document.getElementById('roles_selected');
    if (bAdd) {
        var source = available;
        var target = selected;
    } else {
        var source = selected;
        var target = available;
    }

    // Quit when no source selected
    if (source.selectedIndex == -1)
        return false;

    // Save strings
    var optName  = source.options[source.selectedIndex].text;
    var optValue = source.options[source.selectedIndex].value;

    // Move from source to target
    source.options.remove(source.selectedIndex);
    target.options[target.options.length] = new Option(optName, optValue, false, false);

    // now update the internal helper field with the selected options
    var selected_values = [];
    for(var i = 0; i < selected.options.length; i++)
        selected_values.push(selected.options[i].value);
    user_roles.value = selected_values.join(',');
}

function addOption(form) {
    var field = form.num_options;
    field.value = parseInt(field.value) + 1;
    updateForm(form);
}

// Removes an element from the map
function removeMapObject(event, object_id) {
    event = event || window.event;
    g_view.removeObject(object_id);
    return preventDefaultEvents(event);
}

/************************************************
 * Register events
 *************************************************/

addEvent(document, 'mousemove', function(event) {
    return resizeMouseMove(event)
            && dragObject(event)
            && addFollowing(event);
});

addEvent(document, 'click', function(event) {
    return addClick(event);
});
