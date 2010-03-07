/*****************************************************************************
 *
 * frontendContext.js - Implements functions for context menu functionality
 *
 * Copyright (c) 2004-2008 NagVis Project
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

// replace the system context menu?
var _replaceContext = false;
var _openContextMenus = [];

/**
 * Checks if a context menu is open at the moment
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function contextOpen() {
	var bReturn;
	if(_openContextMenus.length > 0) {
		bReturn = true;
	} else {
		bReturn = false;
	}
	
	return bReturn
}

/**
 * Hides all open context menus
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function contextHide() {
	// Loop all open context menus
	while(_openContextMenus.length > 0) {
		_openContextMenus[0].style.display = 'none';
		_openContextMenus[0] = null;
		_openContextMenus.splice(0,1);
	}
}

// call from the onMouseDown event, passing the event if standards compliant
function contextMouseDown(event) {
	var target;
	var id = -1;

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
	
	// Workaround for the different structure of targets on lines/icons
  // Would be nice to fix the structure
	// For example needed by the WUI contextMenu links
	var oNode = target;
	while(oNode.parentNode !== null && (typeof oNode.id === 'undefined' || oNode.id === '')) {
		oNode = oNode.parentNode;
	}
	
	if(typeof oNode.id !== 'undefined' && oNode.id !== '') {
		id = oNode.id;
	}
	oNode = null;
	
	//document.getElementById('box_host_0').appendChild(document.createTextNode("click: "+id+"\n"));
	// Hide all context menus except clicking the current open context menu
	if(id === -1 || id.indexOf('http:') === -1 && id.indexOf('-context') === -1) {
		// Hide all context menus
		contextHide();
	}
	
	// only show the context menu if the right mouse button is pressed on the obj
	if(event.button === 2) {
		// Prepare to show the context menu
		_replaceContext = true;
	}
}

function contextShow(event) {
	var target;
	
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
	
	if(_replaceContext) {
    // Hide hover menu
		hideHoverMenu();
		
		// document.body.scrollTop does not work in IE
		var scrollTop = document.body.scrollTop ? document.body.scrollTop :
			document.documentElement.scrollTop;
		var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft :
			document.documentElement.scrollLeft;
		
		// Maybe the event is triggered by a child si try to get the right object
		// id from a parent element
		if(typeof target.id == 'undefined' || target.id == '') {
			// Take the first parent
			if(target.parentNode != null && typeof target.parentNode.id != 'undefined' && target.parentNode.id !== '') {
				target = target.parentNode;
			}
			
			// At the moment there is no need to loop more layers. Maybe this is
			// needed in the future?
		}

		// Workaround for the different structure of targets on lines/icons
		// Would be nice to fix the structure
		var id;
		if(target.id !== '') {
			id = target.id;
		}

		if(typeof id === 'undefined') {
			eventlog("context", "error", "Target object search had no id");

			_replaceContext = false;
			return false;
		}
		
		// Only the object id is interesing so remove the other contents
		// like -icon or -line. Simply split the string by - and take the
		// first element
		if(id.indexOf('-') !== -1) {
			var aId = id.split("-");
			id = aId[0];
			aId = null;
		}
		
		var contextMenu = document.getElementById(id+'-context');

		// Maybe there is no context menu defined for one object?
		if(contextMenu === null) {
			eventlog("context", "error", "Found no context menu wit the id \""+id+"-context\"");
			
			_replaceContext = false;
			return false;
		}
		
		// hide the menu first to avoid an "up-then-over" visual effect
		contextMenu.style.display = 'none';
		contextMenu.style.left = event.clientX + scrollLeft + 'px';
		// Need to corrent the position by 30px in IE & FF. Don't know why...
		contextMenu.style.top = event.clientY + scrollTop - 30 + 'px';
		contextMenu.style.display = '';
		
		// Check if the context menu is "in screen".
		// When not: reposition
		var contextLeft = parseInt(contextMenu.style.left.replace('px', ''));
		if(contextLeft+contextMenu.clientWidth > pageWidth()) {
			// move the context menu to the left 
			contextMenu.style.left = contextLeft - contextMenu.clientWidth + 'px';
		}
		contextLeft = null;
		
		var contextTop = parseInt(contextMenu.style.top.replace('px', ''));
		if(contextTop+contextMenu.clientHeight > pageHeight()) {
			// Only move the context menu to the top when the new top will not be
			// out of sight
			if(contextTop - contextMenu.clientHeight >= 0) {
				contextMenu.style.top = contextTop - contextMenu.clientHeight + 'px';
			}
		}
		contextTop = null;
		
		// Append to visible menus array
		_openContextMenus.push(contextMenu);
		
		contextMenu = null;
		
		_replaceContext = false;
		
		// If this returns false, the browser's context menu will not show up
		return false;
	} else {
		return true;
	}
}
