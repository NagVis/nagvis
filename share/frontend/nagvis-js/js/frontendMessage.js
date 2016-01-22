/*****************************************************************************
 *
 * frontendMessage.js - Creates a messagebox in NagVis JS frontend
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

var frontendMessages = {};

function frontendMessagePresent(key) {
    return isset(frontendMessages[key]);
}

function frontendMessageRemove(key) {
    if(frontendMessagePresent(key)) {
        document.body.removeChild(frontendMessages[key]);
        delete frontendMessages[key];
    }
}

function frontendMessage(oMessage, key) {
    var sTitle = '';
    if (typeof oMessage.title !== 'undefined')
        sTitle = oMessage.title;

    var closable = true;
    if (typeof oMessage.closable !== 'undefined')
        closable = oMessage.closable;

    // Skip processing when message with same key already shown
    if (isset(key) && frontendMessagePresent(key))
        return;

    popupWindow(sTitle, {'code': '<div class="'+oMessage.type.toLowerCase()+'">'
                                 +oMessage.message+'</div>'}, 500, closable);

    // Maybe there is a request for a reload/redirect
    if (typeof oMessage.reloadTime !== 'undefined' && oMessage.reloadTime !== null) {
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
