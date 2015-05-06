/*****************************************************************************
 *
 * popupWindow.js - Handles javascript popup windows in NagVis
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

var popupNN6 = document.getElementById && !document.all;
var bDragging = false;
var pwX, pwY, pwTx, pwTy;
var dragObj = null;

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
   dragObj.style.left = popupNN6 ? (pwTx + e.clientX - pwX) + 'px' : (pwTx + event.clientX - pwX) + 'px';
   dragObj.style.top  = popupNN6 ? (pwTy + e.clientY - pwY) + 'px' : (pwTy + event.clientY - pwY) + 'px';

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

    dragObj = document.getElementById('popupWindow');

    pwTx = parseInt(dragObj.style.left+0, 10);
    pwTy = parseInt(dragObj.style.top+0, 10);
    pwX = popupNN6 ? e.clientX : event.clientX;
    pwY = popupNN6 ? e.clientY : event.clientY;

    // Register the popup window handling on the body element
    // The document.onmousemove is used by the hover menu
    document.body.onmousemove=movemouse;

    return false;
}

function popupWindowClose() {
    var w = document.getElementById('popupWindow');
    if (w)
        document.body.removeChild(w);

    // Some windows use the jscolor color picker. It might be visible while
    // a user closes the window. All eventual open color pickers are opened
    // within popup windows. So it is safe to close all color pickers when
    // closing a window
    if (jscolor.picker)
        jscolor.picker.owner.hidePicker();
}

function popupWindowRefresh() {
    var oWindow = document.getElementById('popupWindow');

    if(oWindow) {
        popupWindowPutContent(getSyncRequest(oWindow.url, false, false));
        oWindow = null;
    }
}

function popupWindowPutContent(oContent) {
    if(oContent === null || oContent.code === null) {
        return false;
    }

    var oCell = document.getElementById('popupWindowContent');
    if(oCell) {
        oCell.innerHTML = oContent.code;
        executeJS(oCell);
    }
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
function popupWindow(title, oContent, openOnMousePosition, sWidth) {
    if(oContent === null || oContent.code === null)
        return false;

    if(typeof openOnMousePosition === 'undefined')
        openOnMousePosition = true;

    if(typeof sWidth === 'undefined' || sWidth === null)
        sWidth = '';

    // Maybe some other window is still open. Close it now
    popupWindowClose();

    // Default window position
    var posX = getScrollLeft() + 100;
    var posY = getScrollTop() + 20;

    // Detect the current mouse position and create the window there
    if(openOnMousePosition) {
        //FIXME: Maybe code this in the future
    }

    var oContainerDiv = document.createElement('div');
    oContainerDiv.setAttribute('id', 'popupWindow');
    oContainerDiv.style.position = 'absolute';
    oContainerDiv.style.left = posX+'px';
    oContainerDiv.style.top = posY+'px';

    oContainerDiv.url = oContent.url;

    // Render the close button
    var oClose = document.createElement('div');
    oClose.className = 'close';

    oClose.onclick = function() {
        popupWindowClose();
        return false;
    };

    oClose.appendChild(document.createTextNode('x'));
    oContainerDiv.appendChild(oClose);
    oClose = null;

    // Render the window title
    var oTitle = document.createElement('h1');
    oTitle.appendChild(document.createTextNode(title));

    // Make title the drag window handler
    oTitle.onmousedown = selectmouse;
    oTitle.onmouseup = function() {
        bDragging = false;
    };

    oContainerDiv.appendChild(oTitle);
    oTitle = null;

    var oTable = document.createElement('table');
    oTable.setAttribute('id', 'popupWindowMaster');

    // When width is not set the window should be auto adjusted
    if(sWidth !== '') {
        oContainerDiv.style.width = sWidth+'px';
        oTable.style.width = sWidth+'px';
    }

    var oTbody = document.createElement('tbody');

    oRow = document.createElement('tr');

    oCell = document.createElement('td');
    oCell.setAttribute('id', 'popupWindowContent');
    oCell.colSpan = '2';

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

    popupWindowPutContent(oContent);

    return false;
}
