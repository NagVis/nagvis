/*****************************************************************************
 *
 * tmpl.default.js - javascript for default header template
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
 *
 * Note: I found some of the functions below (ddMenu*) at several places in 
 * the net. So I don't know about the real author and could not add any note here.
 * Hope this is ok - if not -> tell me. Anyways, thanks for the code!
 */

var DDSPEED = 10;
var DDTIMER = 15;

// Hide the given menus instant
function ddMenuHide(aIds) {
	var h;
	var c;
	for(var i = 0; i < aIds.length; i++) {
		var h = document.getElementById(aIds[i] + '-ddheader');
		var c = document.getElementById(aIds[i] + '-ddcontent');
		
		clearInterval(c.timer);
		clearTimeout(h.timer);
		
		c.style.height = '0px';
	}
	
	c = null;
	h = null;
}

// main function to handle the mouse events //
function ddMenu(id, d, reposition){
	var h = document.getElementById(id + '-ddheader');
	var c = document.getElementById(id + '-ddcontent');

	// Reposition by trigger object when some given (used on submenus)
	if(typeof reposition !== 'undefined' && d == 1) {
		c.style.left = (h.offsetWidth-1)+"px";
		c.style.top = (h.parentNode.offsetTop)+"px";
	}

	clearInterval(c.timer);
	if(d == 1){
		clearTimeout(h.timer);
		if(c.maxh && c.maxh <= c.offsetHeight){return}
		else if(!c.maxh){
			c.style.display = 'inline';
			c.style.height = 'auto';
			c.maxh = c.offsetHeight;
			c.style.height = '0px'; 
		}
		c.timer = setInterval(function(){ddSlide(c,1)},DDTIMER);
	}else{
		h.timer = setTimeout(function(){ddCollapse(c)},50);
	}
}

// collapse the menu //
function ddCollapse(c){
	c.timer = setInterval(function(){ddSlide(c,-1)},DDTIMER);
}

// cancel the collapse if a user rolls over the dropdown //
function cancelHide(id){
	var h = document.getElementById(id + '-ddheader');
	var c = document.getElementById(id + '-ddcontent');
	clearTimeout(h.timer);
	clearInterval(c.timer);
	if(c.offsetHeight < c.maxh){
		c.timer = setInterval(function(){ddSlide(c,1)},DDTIMER);
	}
}

// incrementally expand/contract the dropdown and change the opacity //
function ddSlide(c,d){
	var currh = c.offsetHeight;
	var dist;
	if(d == 1){
		dist = (Math.round((c.maxh - currh) / DDSPEED));
	}else{
		dist = (Math.round(currh / DDSPEED));
	}
	if(dist <= 1 && d == 1){
		dist = 1;
	}
	c.style.height = currh + (dist * d) + 'px';
	c.style.opacity = currh / c.maxh;
	c.style.filter = 'alpha(opacity=' + (currh * 100 / c.maxh) + ')';
	if((currh < 2 && d != 1) || (currh > (c.maxh - 2) && d == 1)){
		clearInterval(c.timer);
	}
	
	// Finish hiding (above calculation do not work properly cause dist may become 0)
	if(dist == 0 && d != 1) {
		c.style.opacity = 0;
		c.style.filter = 'alpha(opacity=0)';
		c.style.height = '0px';
		clearInterval(c.timer);
	}
}

// ------------------------------
// Functions for the sidebar menu
// ------------------------------

function toggleSidebar(store) {
	var sidebar = document.getElementById('sidebar');
	var content = document.getElementById('map');
	if(content == null)
		content = document.getElementById('automap');
	if(content == null)
		content = document.getElementById('overview');
	// If there is still no content don't execute the main code. So the sidebar
	// will not be available in undefined views like the WUI
	if(content == null)
		return false;
	
	var state = 1;
	if(sidebarOpen()) {
		sidebar.style.display = 'none';
		content.style.left = '0';
		state = 0;
	} else {
		sidebar.style.display = 'inline';
		content.style.left = '200px';
	}

	if(store === true)
		storeUserOption('sidebar', state);
	
	state   = null;
  content = null;
  sidebar = null;
	return false;
}

function sidebarOpen() {
	display = document.getElementById('sidebar').style.display;
	return !(display  == 'none' || display == '');
}

function getSidebarWidth() {
	if(!sidebarOpen())
		return 0;
	else
		return document.getElementById('sidebar').clientWidth;
}

// Cares about the initial drawing of the sidebar on page load
// Loads the sidebar visibility state from the server
function sidebarDraw() {
	if(!oUserProperties)
		return;

	if(typeof(oUserProperties.sidebar) !== 'undefined' && oUserProperties.sidebar === 1)
    toggleSidebar(false);
	
	// Initialize value
	if(typeof(oUserProperties.sidebarOpenNodes) === 'undefined')
		oUserProperties.sidebarOpenNodes = '';

	// If no nodes are open don't try to open some
	if(oUserProperties.sidebarOpenNodes === '')
		return;

	var openNodes = oUserProperties.sidebarOpenNodes.split(',');
	var nodes = document.getElementById('sidebar').getElementsByTagName('ul');
	for(var i = 0, len = openNodes.length; i < len; i++) {
		var node = nodes[openNodes[i]];
		node.style.display = 'block';
		node.parentNode.setAttribute('class', 'open');
		node.parentNode.setAttribute('className', 'open');
		node = null;
	}
}

function sidebarDrawSubtree(node, index) {
	// Check if this node is expanded
	for(var i = 0, len = oUserProperties.sidebarOpenNodes.length; i < len; i++)
		if(oUserProperties.sidebarOpenNodes[i] == index)
			return;
	
	// Hide sidebar when not in openNodes
	if(node.parentNode != document.getElementById('sidebar'))
		node.style.display = 'none';
}

function sidebarToggleSubtree(oTitle) {
	var oList = sidebarGetListByTitle(oTitle);
	var index = getListNodeIndex(oList);
	var state = 1;
	if(oUserProperties.sidebarOpenNodes === '')
		var openNodes = [];
	else
		var openNodes = oUserProperties.sidebarOpenNodes.split(',');

	if(oList.style.display == 'none' || oList.style.display == '') {
		// Make the sublist visible
		oList.style.display = 'block';
		
		// Open the folder
		oTitle.parentNode.setAttribute('class', 'open');
		oTitle.parentNode.setAttribute('className', 'open');
	} else {
		// Hide the sublist
		oList.style.display = 'none';

		// Close the folder
		oTitle.parentNode.setAttribute('class', 'closed');
		oTitle.parentNode.setAttribute('className', 'closed');
		state = 0;
	}

	// Is it visible at the moment? Search for the index in openNodes list
	var open = null;
	for(var i = 0, len = openNodes.length; i < len; i++) {
		if(openNodes[i] == index) {
			open = i;
			break;
		}
	}

	// When the new state is "closed" remove it from the openNodes list
	// When the node is visible and is not in the list yet, append it
	if(state === 0 && open !== null) {
		openNodes.splice(open, 1);
	} else if(state === 1 && open === null)
		openNodes.push(index);

	storeUserOption('sidebarOpenNodes', openNodes.join(','));

	open = null;
	openNodes = null;
	oTitle = null;
	oList = null;
}

function getListNodeIndex(oList) {
	var nodes = document.getElementById('sidebar').getElementsByTagName('ul');
	for (var i = 0; i < nodes.length; i++)
		if(nodes[i] == oList)
			return i;
	return -1;
}

function sidebarGetListByTitle(title) {
	return title.parentNode.getElementsByTagName('ul')[0];
}
