/*****************************************************************************
 *
 * frontendEventlog.js - Implements a small eventlog for the NagVis page
 *
 * Copyright (c) 2004-2008 NagVis Project
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

var oSeverity = {};
oSeverity.debug = 4;
oSeverity.info = 3;
oSeverity.warning = 2;
oSeverity.critical = 1;


function eventlogToggle() {
	var oLog = document.getElementById('eventlog');
	var oLogControl = document.getElementById('eventlogControl');
	
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
	var oEventlog = document.getElementById('eventlog');
	
	if(!oEventlog) {
		oEventlog = document.createElement('div');
		oEventlog.setAttribute("id","eventlog");
		oEventlog.style.overflow = 'auto';
		
		var oEventlogControl = document.createElement('div');
		oEventlogControl.setAttribute("id","eventlogControl");
		oEventlogControl.appendChild(document.createTextNode('_'));
		oEventlogControl.onmouseover = function() {
			document.body.style.cursor='pointer';
		};
		
		oEventlogControl.onmouseout = function() {
			document.body.style.cursor='auto';
		};
		
		oEventlogControl.onclick = function() {
			eventlogToggle();
		};
		
		document.body.appendChild(oEventlog);
		
		document.body.appendChild(oEventlogControl);
		oEventlogControl = null;
		
		// Hide eventlog when configured
		if(oPageProperties.event_log_hidden == 1) {
			eventlogToggle();
		}
	}
	
	oEventlog = null;
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
		var oEventlog = document.getElementById('eventlog');
		
		if(!oEventlog) {
			eventlogInitialize();
			eventlog("eventlog", "info", "Eventlog initialized (Level: "+oPageProperties.event_log_level+")");
			oEventlog = document.getElementById('eventlog');
		}
		
		if(oSeverity[sSeverity] <= oSeverity[oPageProperties.event_log_level]) {
			// When the message limit is reached truncate the first log entry
			// 24 lines is the current limit
			if(oEventlog.childNodes && oEventlog.childNodes.length >= 24*2) {
				// Remove line
				oEventlog.removeChild(oEventlog.firstChild);
				
				// Remove line break
				oEventlog.removeChild(oEventlog.firstChild);
			}
			
			// Format the new log entry
			var oEntry = document.createTextNode(getCurrentTime()+" "+sSeverity+" "+sComponent+": "+sText);
			
			// Append new message to log
			oEventlog.appendChild(oEntry);
			oEntry = null;
			
			// Add line break after the line
			var oBr = document.createElement('br');
			oEventlog.appendChild(oBr);
			oBr = null;
			
			// Scroll down
			oEventlog.scrollTop = oEventlog.scrollHeight;
		}
		
		oEventlog = null;
	}
}
