/*****************************************************************************
 *
 * ExtGenericResize.js - Functions to make textboxes resizable
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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

var theobject = null; //This gets a value as soon as a resize start

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
		theobject = null;
		return;
	}
	
	if(el.className.indexOf("resizeMe") !== -1) {
		dir = getDirection(event, el);
		if (dir == "") return;
		
		// Disable dragging while
		el.ddObj.setDraggable(false);
		
		// Hide hover menu while resizing
		UnTip();
		
		theobject = new resizeObject();
			
		theobject.el = el;
		theobject.dir = dir;
	
		theobject.grabx = event.clientX;
		theobject.graby = event.clientY;
		theobject.width = el.offsetWidth;
		theobject.height = el.offsetHeight;
		theobject.left = el.offsetLeft;
		theobject.top = el.offsetTop;
	
		event.returnValue = false;
		event.cancelBubble = true;
	}
}

function doUp() {
	if(theobject != null) {
		// Enable dragging again
		theobject.el.ddObj.setDraggable(true);
		
		// Send new size to backend
		saveObjectAfterResize(theobject.el);
		
		theobject = null;
	}
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
	
	if(el.className.indexOf("resizeMe") !== -1) {
		var str = getDirection(event, el);
		
		// Fix the cursor	
		if (str == "") str = "default";
		else str += "-resize";
		el.style.cursor = str;
		
		str = null;
	}
	
	//Dragging starts here
	if(theobject != null) {
		// Hide hover menu while resizing
		UnTip();
		
		if(theobject.dir.indexOf("e") != -1)
			theobject.el.style.width = Math.max(xMin, theobject.width + event.clientX - theobject.grabx) + "px";
	
		if(theobject.dir.indexOf("s") != -1) {
			theobject.el.style.height = Math.max(yMin, theobject.height + event.clientY - theobject.graby) + "px";
		}

		if(theobject.dir.indexOf("w") != -1) {
			theobject.el.style.left = Math.min(theobject.left + event.clientX - theobject.grabx, theobject.left + theobject.width - xMin) + "px";
			theobject.el.style.width = Math.max(xMin, theobject.width - event.clientX + theobject.grabx) + "px";
		}
		if(theobject.dir.indexOf("n") != -1) {
			theobject.el.style.top = Math.min(theobject.top + event.clientY - theobject.graby, theobject.top + theobject.height - yMin) + "px";
			theobject.el.style.height = Math.max(yMin, theobject.height - event.clientY + theobject.graby) + "px";
		}
		
		event.returnValue = false;
		event.cancelBubble = true;
	}
}


function getReal(el, type, value) {
	temp = el;
	while ((temp != null) && (temp.tagName != "BODY")) {
		if (eval("temp." + type) == value) {
			el = temp;
			return el;
		}
		temp = temp.parentElement;
	}
	return el;
}