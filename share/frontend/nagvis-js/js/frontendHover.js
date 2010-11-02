/*****************************************************************************
 *
 * frontendHover.js - Implements functions for hover menu functionality
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
var _showHover = false;
var _openHoverMenus = [];
var _hoverTimer = null;

/**
 * Checks if a hover menu is open at the moment
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function hoverOpen() {
	return _openHoverMenus.length > 0;
}

/**
 * Hides all open context menus
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function hoverHide() {
	// Loop all open context menus
	while(_openHoverMenus.length > 0) {
		_openHoverMenus[0].style.display = 'none';
		_openHoverMenus[0] = null;
		_openHoverMenus.splice(0,1);
	}

	// Remove the hover timer
	if(_hoverTimer !== null) {
		clearTimeout(_hoverTimer);
		_hoverTimer = null;
	}
	
	// Change cursor to auto when hiding hover menu
	document.body.style.cursor = 'auto';
}

function hoverShow(x, y, id) {
	// Hide all other hover menus
	hoverHide();

	var hoverSpacer = 5;

	// document.body.scrollTop does not work in IE
	var scrollTop = document.body.scrollTop ? document.body.scrollTop :
	document.documentElement.scrollTop;
	var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft :
	document.documentElement.scrollLeft;

	var hoverMenu = document.getElementById(id+'-hover');

	// Maybe there is no hover menu defined for one object?
	if(hoverMenu === null) {
		eventlog('hover', 'error', 'Found no hover menu wit the id "'+id+'-hover"');
		return false;
	}
	
	// Change cursor to "hand" when displaying hover menu
	document.body.style.cursor = 'pointer';

	// hide the menu first to avoid an "up-then-over" visual effect
	hoverMenu.style.display = 'none';
	hoverMenu.style.left = x + scrollLeft + hoverSpacer - getSidebarWidth() + 'px';
	hoverMenu.style.top = y + scrollTop + hoverSpacer - getHeaderHeight() + 'px';
	hoverMenu.style.display = '';

	// Check if the context menu is "in screen".
	// When not: reposition
	var hoverLeft = parseInt(hoverMenu.style.left.replace('px', ''));
	if(hoverLeft+hoverMenu.clientWidth > pageWidth())
		hoverMenu.style.left = hoverLeft - hoverMenu.clientWidth - hoverSpacer + 'px';
	hoverLeft = null;

	var hoverTop = parseInt(hoverMenu.style.top.replace('px', ''));
	// Only move the context menu to the top when the new top will not be
	// out of sight
	if(hoverTop+hoverMenu.clientHeight > pageHeight() && hoverTop - hoverMenu.clientHeight >= 0)
		hoverMenu.style.top = hoverTop - hoverMenu.clientHeight - hoverSpacer + 'px';
	hoverTop = null;

	// Append to visible menus array
	_openHoverMenus.push(hoverMenu);

	hoverMenu = null;
	return false;
}
