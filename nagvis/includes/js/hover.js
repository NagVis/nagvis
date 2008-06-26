/*****************************************************************************
 *
 * hover.js - Function collection for handling the hover menu in NagVis
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
 * Function to show the status of the ajax request/response as hover menu
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function ajaxStatusHoverMenu(strTitle, strText) {
	var strHtmlCode = '<table class="ajax_hover_table">'
		+'<tr><th>'+strTitle+'</th></tr>'
		+'<tr><td>'+strText+'</td></tr>';
	
	return strHtmlCode;
}

/**
 * This function calls the ajax handler for fetching the hover menu informations
 * for the hovered object
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getObjectHoverMenu(objType,objName1,objName2) {
	var oOpt = Object();
	
	// Clear old data
	clearHoverMenu();
	
	// Enable showing the hover menu
	showHoverMenu = true;
	
	// Release the request
	getRequest(htmlBase+'/nagvis/ajax_handler.php?action=getObjectHoverMenu&map='+mapName+'&objType='+objType+'&objName1='+objName1+'&objName2='+objName2,'setHoverMenu',oOpt);
}

/**
 * This is the function which is being recalled when the ajax backend responsed
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setHoverMenu(oResponse,oOpt) {
	hoverMenu = oResponse.code;
}

/**
 * Shows the hover menu. While it's waiting for the response from the backend
 * the function displays a "waiting" message. If the request runs in a timeout
 * it displays an error message. When the backend responded it calls the overlib
 * function to display the responsed code.
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function displayHoverMenu(delay, intTimeout, intWaitTimer) {
	// Default timeout value is given timeout value seconds
	intWaitTimer = typeof(intWaitTimer) != 'undefined' ? intWaitTimer : intTimeout ;
	
	// When the hover menu should still be fetched (user did not move mouse out)
	if(showHoverMenu) {
		// When the ajax query has not yet been answered
		if(hoverMenu.length == 0) {
			// When the response is in time
			if(intWaitTimer >= 0) {
				// Display wait status
				overlib(ajaxStatusHoverMenu('Waiting...', 'Waiting for response. Timeout in '+Math.round(intWaitTimer/1000)+' seconds.'), WRAP, VAUTO, DELAY, delay);
				
				// recall this method in 100ms
				window.setTimeout('displayHoverMenu('+delay+','+intTimeout+','+(intWaitTimer-100)+')', 100);
			} else {
				// The response did timeout (no response within the timeout value)
				overlib(ajaxStatusHoverMenu('Error', 'Timeout - Waited 5 seconds for response.'), WRAP, VAUTO, DELAY, delay);
			}
		} else {
			// Everything seems ok, display the hover menu
			overlib(hoverMenu, WRAP, VAUTO, DELAY, delay);
		}
	}
}

/**
 * Hides/Closes the hover menu 
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function hideHoverMenu() {
	clearHoverMenu();
	return nd();
}

/**
 * resets the values for controlling the hover menu
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function clearHoverMenu() {
	hoverMenu = '';
	showHoverMenu = false;
}


/**
 * Function to replace macros in hover template areas
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function replaceHoverTemplateMacros(templateHtml, arrMacros, arrChildObjects) {
	var htmlCode = templateHtml;
	
	// Check if there are childs which should be replaced
	if(arrChildObjects.length > 0) {
		var regex = new RegExp("<!--\\\sBEGIN\\\sloop_child\\\s-->(.+?)<!--\\\sEND\\\sloop_child\\\s-->");
		var results = regex.exec(htmlCode);
		if(results != null) {
			var childsHtmlCode = '';
			var rowHtmlCode = results[1];
			
			for(var i = 0; i < arrChildObjects.length; i++) {
				childsHtmlCode += replaceHoverTemplateMacros(rowHtmlCode, arrChildObjects[i], Array());
			}
			
			htmlCode = htmlCode.replace(regex, childsHtmlCode);
		}
	}
	
	for (var key in arrMacros) {
		if(key.match('^\\\[.+\\\]$')) {
			key1 = key.replace('[','\\\[');
			key1 = key1.replace(']','\\\]');
			
			var regex = new RegExp(key1, 'g');
			htmlCode = htmlCode.replace(regex, arrMacros[key]);
		} else {
			var regex = new RegExp(key, 'gm');
			htmlCode = htmlCode.replace(regex, arrMacros[key]);
		}
	}
	
	return htmlCode;
}
