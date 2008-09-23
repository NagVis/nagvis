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
		var oEventlog = document.createElement('div');
		oEventlog.setAttribute("id","eventlog"); 
		document.body.appendChild(oEventlog);
	}
	
	eventlog("eventlog", "info", "Eventlog initialized (Level: "+oMapProperties.event_log_level+")");
}

/**
 * eventlog()
 *
 * Logs sth. to the javascript eventlog. The eventlog will be initialized when
 * not already done. The entrys can be logged with several severities. The
 * behavior can be controlled by modify the settings in main configuration.
 *
 * @param   String   The type of message
 * @param   String   The severity of the message
 * @param   String   The message
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function eventlog(sType, sSeverity, sText) {
	if(typeof(oMapProperties) != 'undefined' && oMapProperties !== null && oMapProperties.event_log && oMapProperties.event_log != '0') {
		var oSeverity = Object();
		oSeverity.debug = 4;
		oSeverity.info = 3;
		oSeverity.warning = 2;
		oSeverity.critical = 1;
		
		var oEventlog = document.getElementById('eventlog');
		
		if(!oEventlog) {
			eventlogInitialize();
			var oEventlog = document.getElementById('eventlog');
		}
		
		if(oSeverity[sSeverity] <= oSeverity[oMapProperties.event_log_level]) {
			// Format the log entry
			var oEntry = document.createTextNode(getCurrentTime()+" "+sSeverity+" "+sType+": "+sText);
			
			// When the message limit is reached truncate the first log entry
			if(oEventlog.childNodes && oEventlog.childNodes.length >= 6*2) {
				oEventlog.removeChild(oEventlog.firstChild);
				oEventlog.removeChild(oEventlog.firstChild);
			}
			
			// Append new message to log
			oEventlog.appendChild(oEntry);
			oEventlog.appendChild(document.createElement('br'));
		}
	}
}
