/*****************************************************************************
 *
 * frontendEventlog.js - Implements a small eventlog for the NagVis page
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

var _eventlog = null;

var oSeverity = {
    'debug':    4,
    'info':     3,
    'warning':  2,
    'critical': 1,
    'error':    1
};

function eventlogToggle(store) {
    var oLog = document.getElementById('eventlog');
    var oLogControl = document.getElementById('eventlogControl');

    if(store === true)
        storeUserOption('eventlog', oLog.style.display == 'none');

    if(oLog.style.display != 'none') {
        oLog.style.display = 'none';
        oLogControl.style.bottom = '0px';
    } else {
        oLog.style.display = '';
        oLog.style.height = oPageProperties.event_log_height+'px';
        oLogControl.style.bottom = (parseInt(oPageProperties.event_log_height, 10)+5)+'px';
    }

    oLog = null;
}

/**
 * eventlogInitialize()
 *
 * Initializes the eventlog when not yet present
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function eventlogInitialize() {
    var doc = document;
    var oEventlog = doc.createElement('div');
    oEventlog.setAttribute("id","eventlog");
    oEventlog.style.overflow = 'auto';
    oEventlog.style.height = oPageProperties.event_log_height+'px';

    var oEventlogControl = doc.createElement('div');
    oEventlogControl.setAttribute("id","eventlogControl");
    oEventlogControl.style.bottom = (parseInt(oPageProperties.event_log_height, 10)+5)+'px';
    oEventlogControl.appendChild(doc.createTextNode('_'));
    oEventlogControl.onmouseover = function() {
        document.body.style.cursor='pointer';
    };

    oEventlogControl.onmouseout = function() {
        document.body.style.cursor='auto';
    };

    oEventlogControl.onclick = function() {
        eventlogToggle(true);
    };

    doc.body.appendChild(oEventlog);

    doc.body.appendChild(oEventlogControl);
    oEventlogControl = null;

    // Hide eventlog when configured
    if((typeof(oUserProperties.eventlog) !== 'undefined' && oUserProperties.eventlog === false)
       || oPageProperties.event_log_hidden == 1)
        eventlogToggle(false);

    _eventlog = oEventlog;
    oEventlog = null;
    doc = null;
}

/**
 * eventlog()
 *
 * Logs sth. to the javascript eventlog. The eventlog will be initialized when
 * not already done. The entries can be logged with several severities. The
 * behaviour can be controlled by modify the settings in main configuration.
 *
 * @param   String   The component where the message occured
 * @param   String   The severity of the message
 * @param   String   The message
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function eventlog(sComponent, sSeverity, sText) {
    if(typeof(oPageProperties) != 'undefined' && oPageProperties !== null && oPageProperties.event_log && oPageProperties.event_log != '0') {
        var doc = document;

        if(_eventlog === null) {
            eventlogInitialize();
            eventlog("eventlog", "info", "Eventlog initialized (Level: "+oPageProperties.event_log_level+")");
        }
        var oEventlog = _eventlog;

        if(typeof oSeverity[sSeverity] === 'undefined') {
            eventlog('eventlog', 'error', 'Unknown severity used, skipping: '+sSeverity+' '+sComponent+': '+sText)
            oEventlog = null;
        }

        if(oSeverity[sSeverity] <= oSeverity[oPageProperties.event_log_level]) {
            // When the message limit is reached truncate the first log entry
            // 24 lines is the current limit
            if(oEventlog.childNodes && oEventlog.childNodes.length >= oPageProperties.event_log_events * 2) {
                // Remove line
                oEventlog.removeChild(oEventlog.firstChild);

                // Remove line break
                oEventlog.removeChild(oEventlog.firstChild);
            }

            // Format the new log entry
            var oEntry = doc.createTextNode(getCurrentTime()+" "+sSeverity+" "+sComponent+": "+sText);

            // Append new message to log
            oEventlog.appendChild(oEntry);
            oEntry = null;

            // Add line break after the line
            oEventlog.appendChild(doc.createElement('br'));

            // Scroll down
            oEventlog.scrollTop = oEventlog.scrollHeight;
        }

        oEventlog = null;
        doc = null;
    }
}