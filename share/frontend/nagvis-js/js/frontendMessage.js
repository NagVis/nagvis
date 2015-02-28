/*****************************************************************************
 *
 * frontendMessage.js - Creates a messagebox in NagVis JS frontend
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

var frontendMessages = {};
// This is used to track all open messages (even the ones without keys)
var frontendMessageList = [];

function frontendMessageActive() {
    return frontendMessageList.length > 0;
}

// Always hide the last message
function frontendMessageHide() {
    if(frontendMessageActive())
        document.body.removeChild(frontendMessageList.pop());
}

function frontendMessagePresent(key) {
    return isset(frontendMessages[key]);
}

function frontendMessageRemove(key) {
    if(frontendMessagePresent(key)) {
        document.body.removeChild(frontendMessages[key]);
        delete frontendMessages[key];
    }
}

function frontendMessage(oMessage, iTimeout, key) {
    var oContainerDiv;
    var oTable;
    var oTbody;
    var oRow;
    var oCell;
    var oImg;

    var sBoxType = oMessage.type.toLowerCase();
    var sTitle = '';

    if(typeof oMessage.title !== 'undefined')
        sTitle = oMessage.title;

    // Skip processing when message with same key already shown
    if(isset(key) && frontendMessagePresent(key))
        return;

    // Set a close timeout when called to do so
    if(typeof iTimeout !== 'undefined' && iTimeout !== 0) {
        window.setTimeout(function() { frontendMessageHide(); }, iTimeout*1000);
    }

    var imgPath = '/nagvis/frontend/nagvis-js/images/';
    if(isset(oGeneralProperties) && isset(oGeneralProperties.path_images))
        imgPath = oGeneralProperties.path_images

    oContainerDiv = document.createElement('div');
    oContainerDiv.setAttribute('id', 'messageBoxDiv');

    // An optional key can be defined to create this message.
    // This key can later be used to
    // a) Remove this message again
    // b) Don't open the same message twice
    if(isset(key)) {
        frontendMessages[key] = oContainerDiv;
    }

    frontendMessageList.push(oContainerDiv);

    oTable = document.createElement('table');
    oTable.setAttribute('id', 'messageBox');
    oTable.setAttribute('class', sBoxType);
    oTable.setAttribute('className', sBoxType);
    oTable.style.height = '100%';
    oTable.style.width = '100%';
    oTable.cellPadding = '0';
    oTable.cellSpacing = '0';

    oTbody = document.createElement('tbody');

    oRow = document.createElement('tr');

    oCell = document.createElement('td');
    oCell.setAttribute('class', sBoxType);
    oCell.setAttribute('className', sBoxType);
    oCell.colSpan = '3';
    oCell.style.height = '16px';
    oCell.style.textAlign = 'right';
    oCell.style.paddingRight = '5px';
    oCell.style.fontSize = '10px';

    var oLink = document.createElement('a');
    oLink.href = '#';
    oLink.onclick = function() {
        frontendMessageHide();
        return false;
    };
    oLink.appendChild(document.createTextNode('[close]'));

    oCell.appendChild(oLink);

    oRow.appendChild(oCell);
    oCell = null;

    oTbody.appendChild(oRow);
    oRow = null;

    oRow = document.createElement('tr');
    oRow.style.height = '32px';

    oCell = document.createElement('th');
    oCell.setAttribute('class', sBoxType);
    oCell.setAttribute('className', sBoxType);
    oCell.style.width = '60px';

    oImg = document.createElement('img');
    oImg.src = imgPath + 'internal/msg_' + sBoxType + '.png';

    oCell.appendChild(oImg);
    oImg = null;
    oRow.appendChild(oCell);
    oCell = null;

    oCell = document.createElement('th');
    oCell.style.width = '474px';
    oCell.setAttribute('class', sBoxType);
    oCell.setAttribute('className', sBoxType);
    oCell.appendChild(document.createTextNode(sTitle));

    oRow.appendChild(oCell);
    oCell = null;

    oCell = document.createElement('th');
    oCell.setAttribute('class', sBoxType);
    oCell.setAttribute('className', sBoxType);
    oCell.style.width = '60px';

    oImg = document.createElement('img');
    oImg.src = imgPath + 'internal/msg_' + sBoxType + '.png';

    oCell.appendChild(oImg);
    oImg = null;
    oRow.appendChild(oCell);
    oCell = null;

    oTbody.appendChild(oRow);
    oRow = null;

    oRow = document.createElement('tr');

    oCell = document.createElement('td');
    oCell.setAttribute('class', sBoxType);
    oCell.setAttribute('className', sBoxType);
    oCell.colSpan = '3';
    oCell.style.padding = '16px';
    oCell.style.height = '202px';
    oCell.innerHTML = oMessage.message

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

    // Maybe there is a request for a reload/redirect
    if(typeof oMessage.reloadTime !== 'undefined' && oMessage.reloadTime !== null) {
        var sUrl = window.location.href;

        // Maybe enable redirect
        if(typeof oMessage.reloadUrl !== 'undefined' && oMessage.reloadUrl !== null) {
            sUrl = oMessage.reloadUrl;
        }

        // Remove # signs. Seems to prevent firefox from performing the reload
        sUrl = sUrl.replace('#', '');

        // Register reload/redirect
        window.setTimeout(function() { window.location.href = sUrl; }, oMessage.reloadTime*1000);
    }
}
