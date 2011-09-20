/*****************************************************************************
 *
 * popupWindow.js - Handles javascript popup windows in NagVis
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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

/**
 * popupWindowClose()
 *
 * Closes and removes the open dialog
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function popupWindowClose() {
    var w = document.getElementById('popupWindow');

    if(w) {
        document.body.removeChild(w);
        w = null;
    }
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

        // Need to fix javascript execution in innerHTML
        // Works in firefox so don't do it for firefox
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
        aScripts = null;
    }
    oCell = null;
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

    var oTable = document.createElement('table');
    oTable.setAttribute('id', 'popupWindowMaster');

    // When width is not set the window should be auto adjusted
    if(sWidth !== '') {
        oContainerDiv.style.width = sWidth+'px';
        oTable.style.width = sWidth+'px';
    }

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
