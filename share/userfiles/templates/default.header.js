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
		c.style.top = (h.offsetTop)+"px";
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
