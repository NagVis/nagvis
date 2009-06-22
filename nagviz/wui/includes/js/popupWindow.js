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

/**
 * movemouse()
 *
 * Eventhandler for moving the dialogs on the page
 *
 * @param   Object   Event object
 * @return  Boolean  
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function movemouse(e) {
 if(bDragging) {
   dobj.style.left = popupNN6 ? (tx + e.clientX - x) + 'px' : (tx + event.clientX - x) + 'px';
   dobj.style.top  = popupNN6 ? (ty + e.clientY - y) + 'px' : (ty + event.clientY - y) + 'px';
	 
   return false;
 }
 
 return true;
}

/**
 * selectmouse()
 *
 * Eventhandler for mouse clicking on the page
 *
 * @param   Object   Event object
 * @return  Boolean  
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function selectmouse(e) {
	bDragging = true;
	
	dobj = document.getElementById('popupWindow');
	
	tx = parseInt(dobj.style.left+0, 10);
	ty = parseInt(dobj.style.top+0, 10);
	x = popupNN6 ? e.clientX : event.clientX;
	y = popupNN6 ? e.clientY : event.clientY;
	
	document.onmousemove=movemouse;
	return false;
}

/**
 * popupWindowClose()
 *
 * Closes and removes the open dialog
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function popupWindowClose() {
	document.body.removeChild(document.getElementById('popupWindow'));
}

/**
 * popupWindow()
 *
 * Creates a javascript dialog
 *
 * @param   String   Window title
 * @param   Object   Object containing the contents
 * @return  Boolean  
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function popupWindow(title, oContent) {
	if(oContent === null || oContent.code === null) {
		return false;
	}
	
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
	
	oCell.innerHTML = oContent.code;
	oRow.appendChild(oCell);
	// Don't reset cell to null here, it's been reset at bottom of the method
	// after executing the js
	
	oTbody.appendChild(oRow);
	oRow = null;
	
	oTable.appendChild(oTbody);
	oTbody = null;
	
	oContainerDiv.appendChild(oTable);
	oTable = null;
	
	document.body.appendChild(oContainerDiv);
	oContainerDiv = null;
	
	// Need to fix javascript execution in innerHTML
	// Works in firefox so don't do it for firefox
	if(!isFirefox()) {
		var aScripts = oCell.getElementsByTagName('script');
		for(var i = 0, len = aScripts.length; i < len; i++) {
			if(aScripts[i].src && aScripts[i].src !== '') {
				var oScr = document.createElement('script');  
				oScr.src = aScripts[i].src;  
				document.body.appendChild(oScr);
				oScr = null;
			} else {
				try {
					eval(aScripts[i].text);
				} catch(e) {
					alert(oDump(e)+": "+aScripts[i].text);
				}
			}
		}
	}
	oCell = null;
	
	return false;
}
