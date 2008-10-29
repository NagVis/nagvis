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

// call from the onMouseDown event, passing the event if standards compliant
function contextMouseDown(event) {
	// IE is evil and doesn't pass the event object
	if (event === null) {
		event = window.event;
	}
	
	// we assume we have a standards compliant browser, but check if we have IE
	var target = event.target !== null ? event.target : event.srcElement;
	
	// Hide all context menus
	contextHide();
	
	// only show the context menu if the right mouse button is pressed on the obj
	if (event.button === 2) {
		// Prepare to show the context menu
		_replaceContext = true;
	}
}

function contextHide() {
	// Loop all open context menus
	for (var i = 0; i < _openContextMenus.length; i++) {
		_openContextMenus[i].style.display = 'none';
	}
}

function contextShow(event) {
	// IE is evil and doesn't pass the event object
	if (event === null) {
		event = window.event;
	}
	
	// we assume we have a standards compliant browser, but check if we have IE
	var target = event.target !== null ? event.target : event.srcElement;
	
	if(_replaceContext) {
    // Hide hover menu
		hideHoverMenu();
		
		// document.body.scrollTop does not work in IE
		var scrollTop = document.body.scrollTop ? document.body.scrollTop :
			document.documentElement.scrollTop;
		var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft :
			document.documentElement.scrollLeft;
		
		// Workaround for the different structure of targets on lines/icons
		// Would be nice to fix the structure
		var id;
		if(target.id !== '') {
			id = target.id;
		} else {
			id = target.parentNode.parentNode.parentNode.id;
		}
		
		var contextMenu = document.getElementById(id+'-context');
		
		// hide the menu first to avoid an "up-then-over" visual effect
		contextMenu.style.display = 'none';
		contextMenu.style.left = event.clientX + scrollLeft + 'px';
		contextMenu.style.top = event.clientY + scrollTop + 'px';
		contextMenu.style.display = '';
		
		// Append to visible menus array
		_openContextMenus.push(contextMenu);
		
		_replaceContext = false;
		
		// If this returns false, the browser's context menu will not show up
		return false;
	}
}
