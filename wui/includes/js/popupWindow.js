/*****************************************************************************
 *
 * popupWindow.js - Handles javascript popup windows in NagVis WUI
 *
 * Copyright (c) 2004-2009 NagVis Project
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

var popupIE = document.all;
var popupNN6 = document.getElementById && !document.all;

var bDragging = false;
var x, y, tx, ty;
var dobj;

function movemouse( e ) {
 if(bDragging) {
   dobj.style.left = popupNN6 ? (tx + e.clientX - x) + 'px' : (tx + event.clientX - x) + 'px';
   dobj.style.top  = popupNN6 ? (ty + e.clientY - y) + 'px' : (ty + event.clientY - y) + 'px';
	 
   return false;
 }
}

function selectmouse(e) {
	bDragging = true;
	
	dobj = document.getElementById('popupWindow');
	
	tx = parseInt(dobj.style.left+0);
	ty = parseInt(dobj.style.top+0);
	x = popupNN6 ? e.clientX : event.clientX;
	y = popupNN6 ? e.clientY : event.clientY;
	
	document.onmousemove=movemouse;
	return false;
}

function popupWindowClose() {
	document.body.removeChild(document.getElementById('popupWindow'));
}

function popupWindow(title, content) {
	var oContainerDiv = document.createElement('div');
	oContainerDiv.setAttribute('id', 'popupWindow');
	oContainerDiv.style.position = 'absolute';
	oContainerDiv.style.width = '400px';
	oContainerDiv.style.left = '100px';
	oContainerDiv.style.top = '20px';
	
	var oTable = document.createElement('table');
	var oTbody = document.createElement('tbody');
	var oRow = document.createElement('tr');
	var oCell = document.createElement('th');
	oCell.setAttribute('id', 'dragbar');
	
	oCell.onmousedown = selectmouse;
	oCell.onmouseup = function() {
		bDragging = false;
	};
	
	oCell.appendChild(document.createTextNode(title));
	
	oRow.appendChild(oCell);
	oCell = null;
	
	oCell = document.createElement('th');
	oCell.setAttribute('class', 'control');
	oCell.setAttribute('className', 'control');
	
	oCell.onclick = function() {
		popupWindowClose(); 
		return false;
	};
	
	oCell.appendChild(document.createTextNode('x'));
	
	oRow.appendChild(oCell);
	oCell = null;
	
	oTbody.appendChild(oRow);
	oRow = null;
	
	oRow = document.createElement('tr');
	
	oCell = document.createElement('td');
	oCell.colSpan = '2';
	
	oCell.innerHTML = content.code;
	
	oRow.appendChild(oCell);
	oCell = null;
	
	oTbody.appendChild(oRow);
	oRow = null;
	
	oTable.appendChild(oTbody);
	oTbody = null;
	
	oContainerDiv.appendChild(oTable);
	oTable = null;
	
	document.body.appendChild(oContainerDiv);
	oContainerDiv = null;
}
