/*****************************************************************************
 *
 * edit.js - Some NagVis edit code
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

/**
 * Changes the handling of the line middle for lines with two parts
 */
function toggleLineMidLock(event, objectId) {
    getMapObjByDomObjId(objectId).toggleLineMidLock();

    var event = !event ? window.event : event;
    if(event.stopPropagation)
    event.stopPropagation();
    event.cancelBubble = true;
    return false;
}

/**
 * Toggles the mode of the object: editable or not
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleMapObjectLock(event, objectId) {
    updateNumUnlocked(getMapObjByDomObjId(objectId).toggleLock());

    var event = !event ? window.event : event;
    if(event.stopPropagation)
    event.stopPropagation();
    event.cancelBubble = true;
    return false;
}

/**
 * Toggles the mode of all map objects: editable or not
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleAllMapObjectsLock() {
    var lock = false;
    if(iNumUnlocked > 0)
        lock = true;

    for(var i in oMapObjects)
        updateNumUnlocked(oMapObjects[i].toggleLock(lock));

    if(!lock)
        storeUserOption('unlocked-' + oPageProperties.map_name, '*');
    else
        storeUserOption('unlocked-' + oPageProperties.map_name, '');
}

/*** Handles the object dragging ***/

var draggingEnabled = true;
var draggingObject = null;
var dragObjectOffset = null;
var dragObjectPos = null;
var dragObjectStartPos = null;
var dragObjectChilds = {};
var dragStopHandlers = {};
var dragMoveHandlers = {};

function getTargetRaw(event) {
    return event.target ? event.target : event.srcElement;
}

function getTarget(event, ignoreType) {
    if(typeof(ignoreType) === 'undefined')
        var ignoreType = null;

    var target = event.target ? event.target : event.srcElement;
    while(target && (target.tagName != 'DIV' 
          || typeof(target.id) === 'undefined'
          || (ignoreType !== null && (target.id.split('-')[1] === ignoreType)))) {
        target = target.parentNode;
    }
    return target;
}

function getButton(event) {
    if (event.which == null)
        /* IE case */
        return (event.button < 2) ? "LEFT" : ((event.button == 4) ? "MIDDLE" : "RIGHT");
    else
        /* All others */
        return (event.which < 2) ? "LEFT" : ((event.which == 2) ? "MIDDLE" : "RIGHT");
}

function makeUndragable(objects) {
    var len = objects.length;
    if(len == 0)
        return false;

    for(var i = 0; i < len; i++) {
	if(typeof(objects[i]) === 'object')
	    var o = objects[i];
	else
            var o = document.getElementById(objects[i]);

        if(o)  {
            // Remove the handlers
            delete dragStopHandlers[o.id];
            delete dragMoveHandlers[o.id];

            removeEvent(o, 'mousedown', dragStart);
            removeEvent(o, 'mouseup',   dragStop);

            o = null;
        }
    }
}

function makeDragable(objects, dragStopHandler, dragMoveHandler) {
    var len = objects.length;
    if(len == 0)
        return false;

    for(var i = 0; i < len; i++) {
	if(typeof(objects[i]) === 'object')
	    var o = objects[i];
	else
            var o = document.getElementById(objects[i]);

        if(o) {
            // Register the handlers
            dragStopHandlers[o.id] = dragStopHandler;
            dragMoveHandlers[o.id] = dragMoveHandler;

            addEvent(o, "mousedown", dragStart);
            // The drag stop event is registered globally on the whole document to prevent
            // problems with too fast mouse movement which might lead to lag the dragging
            // object behind the mouse and make it impossible to stop dragging.
            addEvent(document, "mouseup", dragStop);
            o = null;
        }
    }
    len = null;
}

/**
 * This function is called once an object is picked for dragging
 */
function dragStart(event) {
    if(!event)
        event = window.event;

    var target = getTarget(event, 'icon');
    var button = getButton(event);

    // Skip calls when already dragging or other button than left mouse
    if(draggingObject !== null || button != 'LEFT' || !draggingEnabled)
        return true;

    var posx, posy;
    if (event.pageX || event.pageY) {
        posx = event.pageX;
        posy = event.pageY;
    } else if (event.clientX || event.clientY) {
        posx = event.clientX;
        posy = event.clientY;
    }

    /*if(event.stopPropagation)
        event.stopPropagation();
    event.cancelBubble = true;*/

    draggingObject = target;
    draggingObject.x = draggingObject.offsetLeft;
    draggingObject.y = draggingObject.offsetTop;

    // Save relative offset of the mouse
    dragObjectOffset   = [ posy - draggingObject.offsetTop - getHeaderHeight(),
                           posx - draggingObject.offsetLeft ];
    dragObjectStartPos = [ draggingObject.offsetTop, draggingObject.offsetLeft ];

    // Save diff coords of relative objects
    var sLabelName = target.id.replace('box_', 'rel_label_');
    var oLabel = document.getElementById(sLabelName);
    if(oLabel) {
        dragObjectChilds[sLabelName] = [ oLabel.offsetTop - draggingObject.offsetTop,
                                         oLabel.offsetLeft - draggingObject.offsetLeft ];
        oLabel = null;
    }
    sLabelName = null;

    // Disable the default events for all the different browsers
    if(event.preventDefault)
        event.preventDefault();
    else
        event.returnValue = false;
    return true;
}

/**
 * This function is called repeated while the object is being dragged
 */
function dragObject(event) {
    if(!event)
        event = window.event;

    if(draggingObject === null || !draggingEnabled)
        return true;

    var posx, posy;
    if (event.pageX || event.pageY) {
        posx = event.pageX;
        posy = event.pageY;
    } else if (event.clientX || event.clientY) {
        posx = event.clientX;
        posy = event.clientY;
    }

    var newTop  = posy - dragObjectOffset[0] - getHeaderHeight();
    var newLeft = posx - dragObjectOffset[1];

    draggingObject.style.position = 'absolute';
    draggingObject.style.top  = newTop + 'px';
    draggingObject.style.left = newLeft + 'px';
    draggingObject.x = rmZoomFactor(newLeft, true);
    draggingObject.y = rmZoomFactor(newTop, true);

    // When this object has a relative coordinated label, then move this too
    moveRelativeObject(draggingObject.id, newTop, newLeft);

    // With pressed CTRL key the icon should be docked
    // This means the object will be positioned relative to that object
    // This code only highlights that object. When the CTRL key is still pressed
    // when dropping the object the currently moved object will be positioned
    // relative to this object.
    if(event.ctrlKey) {
        // Unhighlight all other objects
        for(var i in oMapObjects)
            oMapObjects[i].highlight(false);

        // Find the nearest object to the current position and highlight it
        var o = getNearestObject(draggingObject, newLeft, newTop)
        if(o) {
            o.highlight(true);
            o = null;
        }
    }

    // Shift key
    if(event.shiftKey) {
        // Unhighlight all other objects
        for(var i in oMapObjects)
            oMapObjects[i].highlight(false);
    }

    // Call the dragging handler when one is set
    if(dragMoveHandlers[draggingObject.id])
        dragMoveHandlers[draggingObject.id](draggingObject, event);
    oParent = null;
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
    for(var i in oMapObjects) {
        obj = oMapObjects[i];

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
            oLabel.style.top  = (dragObjectChilds[sLabelName][0] + parentTop) + 'px';
            oLabel.style.left = (dragObjectChilds[sLabelName][1] + parentLeft) + 'px';
            oLabel = null;
        }
    }
    sLabelName = null;
}

function dragStop(event) {
    if(draggingObject === null || !draggingEnabled
       || typeof draggingObject.y == 'undefined' || typeof draggingObject.x == 'undefined')
        return;

    // When x or y are negative just return this and make no change
    if(draggingObject.y < 0 || draggingObject.x < 0) {
        draggingObject.style.top  = dragObjectStartPos[0] + 'px';
        draggingObject.style.left = dragObjectStartPos[1] + 'px';
        draggingObject.x = dragObjectStartPos[1];
        draggingObject.y = dragObjectStartPos[0];

        moveRelativeObject(draggingObject.id, dragObjectStartPos[0], dragObjectStartPos[1]);

        // Call the dragging handler when one is set
        if(dragMoveHandlers[draggingObject.id])
            dragMoveHandlers[draggingObject.id](draggingObject, event);

        draggingObject = null;
        return;
    }

    // Skip when the object has not been moved
    if(draggingObject.y == dragObjectStartPos[0] && draggingObject.x == dragObjectStartPos[1]) {
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

    dragStopHandlers[draggingObject.id](draggingObject, oParent);

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
function addObject(e, objType, viewType, numLeft, action) {
    addObjType  = objType;
    addViewType = viewType;
    addNumLeft  = numLeft;
    addAction   = action;

    if(document.body)
        document.body.style.cursor = 'crosshair';

    var event = !e ? window.event : e;
    if(event.stopPropagation)
        event.stopPropagation();
    event.cancelBubble = true;
    return false;
}

function getEventMousePos(e) {
    var event = !e ? window.event : e;

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

function stop_adding() {
    if(document.body)
        document.body.style.cursor = 'default';

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
        return;

    var pos = getEventMousePos(e);
    if (pos === false) {
        // abort adding when clicking on the header
        stop_adding();
        return;
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
        return;

    //
    // If this is reached all object coords have been collected
    //

    if(addObjType == 'textbox' || addObjType == 'container') {
        var w = addX.pop();
        var h = addY.pop();
    }

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
        sUrl += '&w=' + (w - addX[0]) + '&h=' + (h - addY[0]);

    if(sUrl === '')
        return false;

    // remove the drawing area. Once reached this it is not needed anymore
    if(addShape !== null) {
        addShape.clear();
        document.getElementById('map').removeChild(addShape.cnv);
    }

    showFrontendDialog(sUrl, _('Create Object'));
    sUrl = '';

    stop_adding();
}

function adding() {
    return addNumLeft !== null;
}

function addFollowing(e) {
    if(!addFollow)
        return;

    var pos = getEventMousePos(e);

    addShape.clear();

    if(addViewType === 'line' || addObjType === 'line')
        addShape.drawLine(addX[0], addY[0], pos[0] - getSidebarWidth(), pos[1]);
    else
        addShape.drawRect(addX[0], addY[0], (pos[0] - getSidebarWidth() - addX[0]), (pos[1] - addY[0]));

    addShape.paint();
}

/************************************************
 * Register events
 *************************************************/

// First firefox and then the IE
if (window.addEventListener) {
  window.addEventListener("mousemove", function(e) {
    dragObject(e);
    addFollowing(e);
    return false;
  }, false);

  window.addEventListener("click", function(e) {
    addClick(e);
    return false;
  }, false);
} else {
  document.documentElement.onmousemove  = function(e) {
    dragObject(e);
    addFollowing(e);
    return false;
  };

  document.documentElement.onclick = function(e) {
    addClick(e);
    // Never return false here! This would prevent open links in IE
    return true;
  };
}

/**
 * validateValue(oField)
 *
 * This function checks a string for valid format. The check is done by the
 * given regex.
 * @FIXME: Remove this. Replace dialogs with "server validate"
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function validateValue(sName, sValue, sRegex) {
    // Remove PHP delimiters
    sRegex = sRegex.replace(/^\//, "");
    sRegex = sRegex.replace(/\/[iugm]*$/, "");

    // Match the current value
    var regex = new RegExp(sRegex, "i");
    var match = regex.exec(sValue);
    if(sValue == '' || match != null) {
        return true;
    } else {
        alert(printLang(lang['wrongValueFormatOption'],'ATTRIBUTE~'+sName));
        return false;
    }
}

function useGrid() {
    return oViewProperties.grid_show === 1;
}

/**
 * Parses a grind to make the alignment of the icons easier
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function gridParse() {
    // Only show when user configured to see a grid
    if(useGrid()) {
        // Create grid container and append to map
        var oGrid = document.createElement('div');
        oGrid.setAttribute('id', 'grid');
        document.getElementById('map').appendChild(oGrid);
        oGrid = null;

        // Add an options: grid_show, grid_steps, grid_color
        var grid = new jsGraphics('grid');
        grid.setColor(oViewProperties.grid_color);
        grid.setStroke(1);

        var gridStep = addZoomFactor(oViewProperties.grid_steps);

        // Start
        var gridYStart = 0;
        var gridXStart = 0;

        // End: Get screen height, width
        var gridYEnd = pageHeight() - getHeaderHeight();
        var gridXEnd = pageWidth();

        // Draw vertical lines
        for(var gridX = gridStep; gridX < gridXEnd; gridX = gridX + gridStep) {
            grid.drawLine(gridX, gridYStart, gridX, gridYEnd);
        }
        // Draw horizontal lines
        for(var gridY = gridStep; gridY < gridYEnd; gridY = gridY + gridStep) {
            grid.drawLine(gridXStart, gridY, gridXEnd, gridY);
        }

        grid.paint();

        gridXEnd = null
        gridYEnd = null;
        gridXStart = null;
        gridYStart = null;
        gridStep = null;
        grid = null;

        addEvent(window, "resize", gridRedraw);
    }
}

function gridRemove() {
    var oMap = document.getElementById('map');
    if(oMap) {
        var oGrid = document.getElementById('grid')
        if(oGrid) {
            oMap.removeChild(oGrid);
            oGrid = null;
        }
        oMap = null;
    }

    removeEvent(window, "resize", gridRedraw);
}

function gridRedraw() {
    gridRemove();
    gridParse();
}

/**
 * Toggle the grid state in the current view and sends
 * current setting to server component for persistance
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
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

    // Send current option to server component
    var url = oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='
              + oPageProperties.map_name+'&type=global&id=0&grid_show='+oViewProperties.grid_show;

    // Sync ajax request
    var oResult = getSyncRequest(url);
    if(oResult && oResult.status != 'OK') {
        alert(oResult.message);
    }

    oResult = null;
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

function toggle_maincfg_section(sec) {
    var tables = document.getElementsByClassName('section');
    for (var i = 0; i < tables.length; i++) {
        if (tables[i].id == 'sec_' + sec)
            tables[i].style.display = '';
        else
            tables[i].style.display = 'none';
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

function printLang(sLang, sReplace) {
    if(typeof sLang === 'undefined')
        return '';

    sLang = sLang.replace(/<(\/|)(i|b)>/ig, '');
    sLang = sLang.replace('&auml;', 'ä').replace('&uuml;', 'ü');
    sLang = sLang.replace('&ouml;', 'ö').replace('&szlig;', '');

    // sReplace maybe optional
    if(typeof sReplace != "undefined") {
        aReplace = sReplace.split(",");
        for(var i = 0; i < aReplace.length; i++) {
            var aReplaceSplit = aReplace[i].split("~");
            sLang = sLang.replace("["+aReplaceSplit[0]+"]",aReplaceSplit[1]);
        }
    }

    return sLang;
}

// checks that the file the user wants to upload has a valid extension
function checkPngGifOrJpg(imageName) {
    var type = imageName.substring(imageName.length-3,imageName.length).toLowerCase();
    return type == 'png' || type == 'jpg' || type == 'gif';
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
