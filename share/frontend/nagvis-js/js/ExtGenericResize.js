/*****************************************************************************
 *
 * ExtGenericResize.js - Functions to make textboxes resizable
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

/////////////////////////////////////////////////////////////////////////
// Generic Resize by Erik Arvidsson                                    //
//                                                                     //
// You may use this script as long as this disclaimer is remained.     //
// See www.dtek.chalmers.se/~d96erik/dhtml/ for mor info               //
//                                                                     //
// How to use this script!                                             //
// Link the script in the HEAD and create a container (DIV, preferable //
// absolute positioned) and add the class="resizeMe" to it.            //
/////////////////////////////////////////////////////////////////////////

var resizeObj = null; //This gets a value as soon as a resize start

function resizeObject() {
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

function doDown(event) {
    // IE is evil and doesn't pass the event object
    if (event === null || typeof event === 'undefined') {
        event = window.event;
    }

    // we assume we have a standards compliant browser, but check if we have IE
    if(typeof event.target != 'undefined' && event.target !== null) {
        target = event.target;
    } else {
        target = event.srcElement;
    }

    var el = getReal(target, "className", "resizeMe");

    if(el == null || el.id == '') {
        resizeObj = null;
        return;
    }

    if(el.className.indexOf("resizeMe") !== -1) {
        dir = getDirection(event, el);
        if (dir == "") return;

        // Disable dragging while resizing
        draggingEnabled = false;
        draggingObject = null;

        resizeObj = new resizeObject();

        resizeObj.el = el;
        resizeObj.dir = dir;

        resizeObj.grabx  = event.clientX;
        resizeObj.graby  = event.clientY;
        resizeObj.width  = el.offsetWidth;
        resizeObj.height = el.offsetHeight;
        resizeObj.left   = el.offsetLeft;
        resizeObj.top    = el.offsetTop;

        event.returnValue = false;
        event.cancelBubble = true;
    }
}

function doUp(event) {
    // IE is evil and doesn't pass the event object
    if (event === null || typeof event === 'undefined') {
        event = window.event;
    }

    if(resizeObj != null) {
        // Re-enable dragging
        draggingEnabled = true;
        draggingObject = null;

        // Send new size to backend
        saveObjectAfterResize(resizeObj.el);

        resizeObj = null;

        event.returnValue = false;
        event.cancelBubble = true;
    }

    return false;
}

function doMove(event) {
    // IE is evil and doesn't pass the event object
    if (event === null || typeof event === 'undefined') {
        event = window.event;
    }

    // we assume we have a standards compliant browser, but check if we have IE
    if(typeof event.target != 'undefined' && event.target !== null) {
        target = event.target;
    } else {
        target = event.srcElement;
    }

    var el, xPos, yPos, xMin, yMin;
    // The smallest width and height possible
    xMin = 8;
    yMin = 8;

    el = getReal(target, "className", "resizeMe");
    if(!isset(el.className))
        return;   

    if(el.className.indexOf("resizeMe") !== -1) {
        var str = getDirection(event, el);

        // Fix the cursor
        if (str == "") str = "default";
        else str += "-resize";
        el.style.cursor = str;

        str = null;
    }

    //Dragging starts here
    if(resizeObj != null) {
        if(resizeObj.dir.indexOf("e") != -1)
            resizeObj.el.style.width = Math.max(xMin, resizeObj.width + event.clientX - resizeObj.grabx) + "px";

        if(resizeObj.dir.indexOf("s") != -1) {
            resizeObj.el.style.height = Math.max(yMin, resizeObj.height + event.clientY - resizeObj.graby) + "px";
        }

        if(resizeObj.dir.indexOf("w") != -1) {
            resizeObj.el.style.left = Math.min(resizeObj.left + event.clientX - resizeObj.grabx, resizeObj.left + resizeObj.width - xMin) + "px";
            resizeObj.el.style.width = Math.max(xMin, resizeObj.width - event.clientX + resizeObj.grabx) + "px";
        }
        if(resizeObj.dir.indexOf("n") != -1) {
            resizeObj.el.style.top = Math.min(resizeObj.top + event.clientY - resizeObj.graby, resizeObj.top + resizeObj.height - yMin) + "px";
            resizeObj.el.style.height = Math.max(yMin, resizeObj.height - event.clientY + resizeObj.graby) + "px";
        }

        event.returnValue = false;
        event.cancelBubble = true;
    }
    return false;
}


function getReal(el, type, value) {
    temp = el;
    while(isset(temp) && temp != null && temp.tagName != "BODY") {
        var o = temp[type];
        if(isset(o) && o.indexOf(value) !== -1) {
            el = temp;
            return el;
        }
        o = null;
        temp = temp.parentElement;
    }
    return el;
}

addEvent(document, "mousedown", doDown);
addEvent(document, "mouseup",   doUp);
addEvent(document, "mousemove", doMove);
