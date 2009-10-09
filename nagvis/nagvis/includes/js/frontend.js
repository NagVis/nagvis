/*****************************************************************************
 *
 * frontend.js - Functions implementing the new ajax frontend with automatic
 *               worker function etc.
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
 * Definition of needed variables
 */
var oHoverTemplates = {};
var oHoverUrls = {};
var oContextTemplates = {};

/**
 * getObjectsToUpdate()
 *
 * Detects objects with deprecated state information
 *
 * @return  Array    The array of aMapObjects indexes which need an update
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getObjectsToUpdate(aObjs) {
	eventlog("worker", "debug", "getObjectsToUpdate: Start");
	var arrReturn = [];
	
	// Assign all object which need an update indexes to return Array
	for(var i = 0, len = aObjs.length; i < len; i++) {
		if(aObjs[i].lastUpdate <= (iNow-(oWorkerProperties.worker_update_object_states*1000))) {
			// Do not update shapes where enable_refresh=0
			if(aObjs[i].conf.type !== 'shape' || (aObjs[i].conf.type === 'shape' && aObjs[i].conf.enable_refresh && aObjs[i].conf.enable_refresh == '1')) {
				arrReturn.push(i);
			}
		}
	}
	
	// Now spread the objects in the available timeslots
	var iNumTimeslots = Math.ceil(oWorkerProperties.worker_update_object_states / oWorkerProperties.worker_interval);
	var iNumObjectsPerTimeslot = Math.ceil(aObjs.length / iNumTimeslots);
	eventlog("worker", "debug", "Number of timeslots: "+iNumTimeslots+" Number of Objects per Slot: "+iNumObjectsPerTimeslot);
	
	// Only spread when the number of objects is larger than the objects for each
	// timeslot
	if(arrReturn.length > iNumObjectsPerTimeslot) {
		eventlog("worker", "debug", "Spreading map objects in timeslots");
		// Just remove all elements from the end of the array
		arrReturn = arrReturn.slice(0, iNumObjectsPerTimeslot);
	}
	
	eventlog("worker", "debug", "getObjectsToUpdate: Have to update "+arrReturn.length+" objects");
	return arrReturn;
}

/**
 * getCfgFileAges()
 *
 * Bulk get file ages of important files
 * The request is being cached to prevent too often updates. The worker asks
 * every 5 seconds by default - this is too much for a config check.
 *
 * @return  Boolean
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getCfgFileAges(bMap) {
	if(typeof bMap === 'undefined') {
		bMap = true;
	}
	
	if(bMap) {
		return getSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getCfgFileAges&f[]=mainCfg&m[]='+oPageProperties.map_name, true);
	} else {
		return getSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getCfgFileAges&f[]=mainCfg', true);
	}
}

/**
 * checkMainCfgChanged()
 *
 * Detects if the main configuration file has changed since last load
 *
 * @return  Boolean
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function checkMainCfgChanged(iCurrentAge) {
	eventlog("worker", "debug", "MainCfg Current: "+date(oGeneralProperties.date_format, iCurrentAge)+" Cached: "+date(oGeneralProperties.date_format, oFileAges.main_config));
	
	if(oFileAges.main_config != iCurrentAge) {
		return true;
	} else {
		return false;
	}
}

/**
 * checkMapCfgChanged()
 *
 * Detects if the map configuration file has changed since last load
 *
 * @return  Boolean
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function checkMapCfgChanged(iCurrentAge) {
	var now = date(oGeneralProperties.date_format, iCurrentAge);
	var cached = date(oGeneralProperties.date_format, oFileAges.map_config);
	
	eventlog("worker", "debug", "MapCfg Current: "+now+" Cached: "+cached);
	cached = null;
	now = null;
	
	if(oFileAges.map_config != iCurrentAge) {
		return true;
	} else {
		return false;
	}
}

/**
 * setMapHoverUrls()
 *
 * Gets the code for needed hover templates and saves it for later use in icons
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapHoverUrls() {
	var aUrlParts = [];
	var aTemplateObjects;
	
	// Loop all map objects to get the used hover templates
	for(var a = 0, len = aMapObjects.length; a < len; a++) {
		// Ignore objects which
		// a) have a disabled hover menu
		// b) do use hover_url
		if(aMapObjects[a].conf.hover_menu && aMapObjects[a].conf.hover_menu == 1 && aMapObjects[a].conf.hover_url && aMapObjects[a].conf.hover_url !== '') {
			oHoverUrls[aMapObjects[a].conf.hover_url] = '';
		}
	}
	
	// Build string for bulk fetching the templates
	for(var i in oHoverUrls) {
		if(i != 'Inherits') {
			aUrlParts.push('&url[]='+escapeUrlValues(i));
		}
	}
	
	// Get the needed templates via bulk request
	aTemplateObjects = getBulkSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getHoverUrl', aUrlParts, oWorkerProperties.worker_request_max_length, true);
	
	// Set the code to global object oHoverTemplates
	if(aTemplateObjects.length > 0) {
		for(var i = 0, len = aTemplateObjects.length; i < len; i++) {
			oHoverUrls[aTemplateObjects[i].url] = aTemplateObjects[i].code;
		}
	}
}

/**
 * parseHoverMenus()
 *
 * Assigns the hover template code to the object, replaces all macros and
 * adds the menu to all map objects
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseHoverMenus(aObjs) {
	for(var a = 0; a < aObjs.length; a++) {
		if(aObjs[a].conf.hover_menu && aObjs[a].conf.hover_menu !== '0') {
			aObjs[a].parseHoverMenu();
		}
	}
}

/**
 * getHoverTemplates()
 *
 * Gets the code for needed hover templates and saves it for later use in icons
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getHoverTemplates(aObjs) {
	var aUrlParts = [];
	var aTemplateObjects;
	
	// Loop all map objects to get the used hover templates
	for(var a = 0, len = aObjs.length; a < len; a++) {
		// Ignore objects which
		// a) have a disabled hover menu
		// b) do not use hover_url
		if(aObjs[a].conf.hover_menu && aObjs[a].conf.hover_menu == '1' && (!aObjs[a].conf.hover_url || aObjs[a].conf.hover_url === '')) {
			oHoverTemplates[aObjs[a].conf.hover_template] = '';
		}
	}
	
	// Build string for bulk fetching the templates
	for(var i in oHoverTemplates) {
		if(i !== 'Inherits') {
			aUrlParts.push('&name[]='+i);
			
			// Load template css file
			// This is needed for some old browsers which do no load css files
			// which are included in such fetched html code
			var oLink = document.createElement('link');
			oLink.href = oGeneralProperties.path_htmlbase+'/nagvis/templates/hover/tmpl.'+i+'.css';
			oLink.rel = 'stylesheet';
			oLink.type = 'text/css';
			document.body.appendChild(oLink);
			oLink = null;
		}
	}
	
	// Get the needed templates via bulk request
	aTemplateObjects = getBulkSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getHoverTemplate', aUrlParts, oWorkerProperties.worker_request_max_length, true);
	
	// Set the code to global object oHoverTemplates
	if(aTemplateObjects.length > 0) {
		for(var i = 0, len = aTemplateObjects.length; i < len; i++) {
			oHoverTemplates[aTemplateObjects[i].name] = aTemplateObjects[i].code;
		}
	}
}

/**
 * getContextTemplates()
 *
 * Gets the code for needed context templates and saves it for later use in icons
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getContextTemplates(aObjs) {
	var aUrlParts = [];
	var aTemplateObjects;
	
	// Loop all map objects to get the used templates
	for(var a = 0, len = aObjs.length; a < len; a++) {
		// Ignore objects which
		// a) have a disabled menu
		// FIXME: conf.context_menu has inconsistent types (with and without quotes)
		//        fix this and  === can be used here
		if(aObjs[a].conf.context_menu && aObjs[a].conf.context_menu == 1 && oContextTemplates[aObjs[a].conf.context_template] !== '') {
			oContextTemplates[aObjs[a].conf.context_template] = '';
		}
	}
	
	// Build string for bulk fetching the templates
	for(var sName in oContextTemplates) {
		if(sName !== 'Inherits') {
			aUrlParts.push('&name[]='+sName);
			
			// Load template css file
			var oLink = document.createElement('link');
			oLink.href = oGeneralProperties.path_htmlbase+'/nagvis/templates/context/tmpl.'+sName+'.css';
			oLink.rel = 'stylesheet';
			oLink.type = 'text/css';
			document.body.appendChild(oLink);
			oLink = null;
		}
	}
	
	// Get the needed templates via bulk request
	aTemplateObjects = getBulkSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getContextTemplate', aUrlParts, oWorkerProperties.worker_request_max_length, true);
	
	// Set the code to global object oContextTemplates
	if(aTemplateObjects.length > 0) {
		for(var i = 0, len = aTemplateObjects.length; i < len; i++) {
			oContextTemplates[aTemplateObjects[i].name] = aTemplateObjects[i].code;
		}
	}
}

/**
 * parseContextMenus()
 *
 * Assigns the context template code to the object, replaces all macros and
 * adds the menu to all map objects
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseContextMenus(aObjs) {
	for(var a = 0; a < aObjs.length; a++) {
		if(aObjs[a].conf.context_menu && aObjs[a].conf.context_menu != '0') {
			aObjs[a].parseContextMenu();
		}
	}
}

/**
 * getBackgroundColor()
 *
 * Gets the background color of the map by the summary state of the map
 *
 * @param   Object   Map object
 * @return  String   Hex code representing the color
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getBackgroundColor(oObj) {
	var sColor;
	
	// When state is PENDING, OK, UP, set default background color
	if(oObj.summary_state == 'PENDING' || oObj.summary_state == 'OK' || oObj.summary_state == 'UP') {
		sColor = oPageProperties.background_color;
	} else {
		sColor = oStates[oObj.summary_state].bgColor;
	}
	
	oObj = null;
	
	return sColor;
}

/**
 * Gets the favicon of the page representation the state of the map
 *
 * @return	String	Path to the favicon
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function getFaviconImage(oObj) {
	var sFavicon;
	
	// Gather image on summary state of the object
	if(oObj.summary_in_downtime && oObj.summary_in_downtime == '1') {
		sFavicon = 'downtime';
	} else if(oObj.summary_problem_has_been_acknowledged && oObj.summary_problem_has_been_acknowledged == '1') {
		sFavicon = 'ack';
	} else if(oObj.summary_state.toLowerCase() == 'unreachable') {
		sFavicon = 'down';
	} else {
		sFavicon = oObj.summary_state.toLowerCase();
	}
	
	oObj = null;
	
	// Set full path
	sFavicon = oGeneralProperties.path_htmlimages+'internal/favicon_'+sFavicon+'.png';
	
	return sFavicon;
}

/**
 * setPageBackgroundColor()
 *
 * Sets the background color of the page
 *
 * @param   String   Hex code
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setPageBackgroundColor(sColor) {
	document.body.style.backgroundColor = sColor;
}

/**
 * setPageFavicon()
 *
 * Sets the favicon of the pages
 *
 * @param   String   Path to the icon image
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setPageFavicon(sFavicon) {
	favicon.change(sFavicon);
}

/**
 * setPagePageTitle()
 *
 * Sets the title of the current page
 *
 * @param   String   Title
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setPageTitle(sTitle) {
	document.title = sTitle;
}

/**
 * updateMapBasics()
 *
 * This function updates the map basics like background, favicon and title with
 * current information
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function updateMapBasics() {
	// Get new map state from core
	var o = getSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getObjectStates&ty=state&i[]='+oPageProperties.map_name+'&m[]=&t[]=map&n1[]='+oPageProperties.map_name+'&n2[]=', false)[0];
	
	// Update favicon
	setPageFavicon(getFaviconImage(o));
	
	// Update page title
	setPageTitle(oPageProperties.alias+' ('+o.summary_state+') :: '+oGeneralProperties.internal_title);
	
	// Change background color
	if(oPageProperties.event_background && oPageProperties.event_background == '1') {
		setPageBackgroundColor(getBackgroundColor(o));
	}
	
	o = null;
}

/**
 * updateObjects()
 *
 * Bulk update objects
 *
 * @param   Array    Array of objects with new informations
 * @param   Array    Array of map objects
 * @param   String   Type of the page
 * @return  Boolean  Returns true when some state has changed
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function updateObjects(aMapObjectInformations, aObjs, sType) {
	var bStateChanged = false;
	
	// Loop all object which have new informations
	for(var i = 0, len = aMapObjectInformations.length; i < len; i++) {
		var objectId = aMapObjectInformations[i].object_id;
		var intIndex = -1;
		
		// Find the id (key) with the matching object_id in object array
		for(var a = 0, len1 = aObjs.length; a < len1 && intIndex == -1; a++) {
			if(aObjs[a].conf.object_id == objectId) {
				intIndex = a;
			}
		}
		
		// Save old state for later "change detection"
		aObjs[intIndex].saveLastState();
		
		// When this is a valid index
		if(intIndex >= 0) {
			// Update this object (loop all options from array and set in current obj)
			for (var strIndex in aMapObjectInformations[i]) {
				if(aMapObjectInformations[i][strIndex] != 'object_id') {
					aObjs[intIndex].conf[strIndex] = aMapObjectInformations[i][strIndex];
				}
			}
			
			// Update members list
			aObjs[intIndex].getMembers();
		}
		
		// Update lastUpdate timestamp
		aObjs[intIndex].setLastUpdate();
		
		// Some objects need to be reloaded even when no state changed (perfdata or 
		// output could have changed since last update). Basically this is only
		// needed for gadgets and/or labels with [output] or [perfdata] macros
		if(!aObjs[intIndex].stateChanged() && aObjs[intIndex].outputOrPerfdataChanged()) {
			// Reparse object to map (only for maps!)
			// No else for overview here, senseless!
      if(sType === 'map') {
				aObjs[intIndex].parse();
			}
		}
		
		// Detect state changes and do some actions
		if(aObjs[intIndex].stateChanged()) {
			
			/* Internal handling */
			
			// Save for return code
			bStateChanged = true;
			
			// Reparse object to map
			if(sType === 'map') {
				aObjs[intIndex].parse();
			} else if(sType === 'overview') {
				// Reparsing the object on index page.
				// replaceChild seems not to work in all cases so workaround it
				var oOld = aObjs[intIndex].parsedObject;
				aObjs[intIndex].parsedObject = aObjs[intIndex].parsedObject.parentNode.insertBefore(aObjs[intIndex].parseOverview(), aObjs[intIndex].parsedObject);
				aObjs[intIndex].parsedObject.parentNode.removeChild(oOld);

				oOld = null;
			}
			
			/**
			 * Additional eventhandling
			 *
			 * event_log=1/0
			 * event_highlight=1/0
			 * event_scroll=1/0
			 * event_sound=1/0
			 */
			
			// - Highlight (Flashing)
			if(oPageProperties.event_highlight === '1') {
				if(aObjs[intIndex].conf.view_type && aObjs[intIndex].conf.view_type === 'icon') {
					// Detach the handler
					//  Had problems with this. Could not give the index to function:
					//  function() { flashIcon(iIndex, 10); iIndex = null; }
					setTimeout('flashIcon('+intIndex+', '+oPageProperties.event_highlight_duration+', '+oPageProperties.event_highlight_interval+')', 0);
				} else {
					// FIXME: Atm only flash icons, not lines or gadgets
				}
			}
			
			// - Scroll to object
			if(oPageProperties.event_scroll === '1') {
				// Detach the handler
				setTimeout(function() { scrollSlow(aObjs[intIndex].conf.x, aObjs[intIndex].conf.y, 15); }, 0);
			}
			
			// - Eventlog
			if(aObjs[intIndex].conf.type == 'service') {
				eventlog("state-change", "info", aObjs[intIndex].conf.type+" "+aObjs[intIndex].conf.name+" "+aObjs[intIndex].conf.service_description+": Old: "+aObjs[intIndex].last_conf.summary_state+"/"+aObjs[intIndex].last_conf.summary_problem_has_been_acknowledged+"/"+aObjs[intIndex].last_conf.summary_in_downtime+" New: "+aObjs[intIndex].conf.summary_state+"/"+aObjs[intIndex].conf.summary_problem_has_been_acknowledged+"/"+aObjs[intIndex].conf.summary_in_downtime);
			} else {
				eventlog("state-change", "info", aObjs[intIndex].conf.type+" "+aObjs[intIndex].conf.name+": Old: "+aObjs[intIndex].last_conf.summary_state+"/"+aObjs[intIndex].last_conf.summary_problem_has_been_acknowledged+"/"+aObjs[intIndex].last_conf.summary_in_downtime+" New: "+aObjs[intIndex].conf.summary_state+"/"+aObjs[intIndex].conf.summary_problem_has_been_acknowledged+"/"+aObjs[intIndex].conf.summary_in_downtime);
			}
			
			// - Sound
			if(oPageProperties.event_sound === '1') {
				// Detach the handler
				setTimeout('playSound('+intIndex+', 1)', 0);
			}
		}

		// Reparse the hover menu
		aObjs[intIndex].parseHoverMenu();
		
		// Reparse the context menu
		// The context menu only needs to be reparsed when the
		// icon object has been reparsed
		if(sType === 'map') {
			aObjs[intIndex].parseContextMenu();
		} else if(sType === 'overview') {
			
		}
	}
	
	return bStateChanged;
}

/**
 * refreshMapObject()
 *
 * Handles manual map object update triggered by e.g. the context menu
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function refreshMapObject(objectId) {
	var iIndex = -1;
	for(var i = 0, len = aMapObjects.length; i < len && iIndex < 0; i++) {
		if(aMapObjects[i].conf.object_id == objectId) { 
			iIndex = i;
		}
	}
	
	var name = aMapObjects[iIndex].conf.name;
	
	var type = aMapObjects[iIndex].conf.type;
	var obj_id = aMapObjects[iIndex].conf.object_id;
	var service_description = aMapObjects[iIndex].conf.service_description;
	var map = oPageProperties.map_name;
	
	// Create request string
	var sUrlPart = '&i[]='+obj_id+'&m[]='+map+'&t[]='+type+'&n1[]='+name;
	if(service_description) {
		sUrlPart = sUrlPart + '&n2[]='+escapeUrlValues(service_description);
	} else {
		sUrlPart = sUrlPart + '&n2[]=';
	}
	
	// Get the updated objectsupdateMapObjects via bulk request
	var o = getSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getObjectStates&ty=state' + sUrlPart, false);
	var bStateChanged = false;
	if(o.length > 0) {
		bStateChanged = updateObjects(o, aMapObjects, 'map');
	}
	o = null;
	
	if(bStateChanged) {
		updateMapBasics();
	}
}


/**
 * setMapBackgroundImage()
 *
 * Parses the background image to the map
 *
 * @param   String   Path to map images
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapBackgroundImage(sImage) {
	// Only work with the background image if some is configured
	if(typeof sImage !== 'undefined' && sImage !== 'none' && sImage !== '') {
		// Use existing image or create new
		if(document.getElementById('backgroundImage')) {
			var oImage = document.getElementById('backgroundImage');
		} else {
			var oImage = document.createElement('img');
			oImage.id = 'backgroundImage';
			oImage.style.zIndex = 0;
			document.body.appendChild(oImage);
		}
		
		oImage.src = sImage;
		oImage = null;
	}
}

/**
 * setPageBasics()
 *
 * Sets basic information like favicon and page title
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setPageBasics(oProperties) {
	setPageFavicon(oProperties.favicon_image);
	setPageTitle(oProperties.page_title);
	setPageBackgroundColor(oProperties.background_color);
}

/**
 * setMapBasics()
 *
 * Sets basic information like background image
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapBasics(oProperties) {
	setPageBasics(oProperties);
	setMapBackgroundImage(oProperties.background_image);
}

/**
 * setMapObjects()
 *
 * Does initial parsing of map objects
 *
 * @param   Array    Array of objects to parse to the map
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapObjects(aMapObjectConf) {
	eventlog("worker", "debug", "setMapObjects: Start setting map objects");
	for(var i = 0, len = aMapObjectConf.length; i < len; i++) {
		var oObj;
		
		switch (aMapObjectConf[i].type) {
			case 'host':
				oObj = new NagVisHost(aMapObjectConf[i]);
			break;
			case 'service':
				oObj = new NagVisService(aMapObjectConf[i]);
			break;
			case 'hostgroup':
				oObj = new NagVisHostgroup(aMapObjectConf[i]);
			break;
			case 'servicegroup':
				oObj = new NagVisServicegroup(aMapObjectConf[i]);
			break;
			case 'map':
				oObj = new NagVisMap(aMapObjectConf[i]);
			break;
			case 'textbox':
				oObj = new NagVisTextbox(aMapObjectConf[i]);
			break;
			case 'shape':
				oObj = new NagVisShape(aMapObjectConf[i]);
			break;
			default:
				oObj = null;
				alert('Error: Unknown object type');
			break;
		}
		
		if(oObj !== null) {
			// Save object to map objects array
			aMapObjects.push(oObj);
			
			// Parse object to map
			aMapObjects[aMapObjects.length-1].parse();
		}
		oObj = null;
	}
	eventlog("worker", "debug", "setMapObjects: End setting map objects");
}

/**
 * updateShapes()
 *
 * Bulk refreshes shapes (Only reparsing)
 *
 * @param   Array    Array of objects to reparse
 * @return  Boolean  Returns true when some state has changed
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function updateShapes(aShapes) {
	for(var i = 0, len = aShapes.length; i < len; i++) {
		aMapObjects[aShapes[i]].parse();
	}
}

/**
 * playSound()
 *
 * Play a sound for an object state
 *
 * @param   Integer  Index in aMapObjects
 * @param   Integer  Iterator for number of left runs
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function playSound(intIndex, iNumTimes){
	var sSound = '';
	
	var id = aMapObjects[intIndex].parsedObject.id;
	
	var oObjIcon = document.getElementById(id+'-icon');
	var oObjIconDiv = document.getElementById(id+'-icondiv');
	
	var sState = aMapObjects[intIndex].conf.summary_state;
	
	if(oStates[sState] && oStates[sState].sound && oStates[sState].sound !== '') {
		sSound = oStates[sState].sound;
	}
	
	eventlog("state-change", "debug", "Sound to play: "+sSound);
	
	if(sSound !== '') {
		// Remove old sound when present
		if(document.getElementById('sound'+sState)) {
			document.body.removeChild(document.getElementById('sound'+sState));
		}
		
		// Load sound
		var oEmbed = document.createElement('embed');
		oEmbed.setAttribute('id', 'sound'+sState);
		// Relative URL does not work, add full url
		oEmbed.setAttribute('src', window.location.protocol + '//' + window.location.host + ':' + window.location.port + oGeneralProperties.path_htmlsounds+sSound);
		oEmbed.setAttribute('width', '0');
		oEmbed.setAttribute('height', '0');
		oEmbed.setAttribute('hidden', 'true');
		oEmbed.setAttribute('loop', 'false');
		oEmbed.setAttribute('autostart', 'true');
		oEmbed.setAttribute('enablejavascript', 'true');
		
		// Add object to body => the sound is played
		oEmbed = document.body.appendChild(oEmbed);
		oEmbed = null;
		
		iNumTimes = iNumTimes - 1;
		
		if(iNumTimes > 0) {
			setTimeout(function() { playSound(intIndex, iNumTimes); }, 500);
		}
	}
	
	oObjIcon = null;
	oObjIconDiv = null;
}

/**
 * flashIcon()
 *
 * Highlights an object by show/hide a border around the icon
 *
 * @param   Integer  Index in aMapObjects
 * @param   Integer  Time remaining in miliseconds
 * @param   Integer  Interval in miliseconds
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function flashIcon(intIndex, iDuration, iInterval){
	var id = aMapObjects[intIndex].parsedObject.id;
	
	var oObjIcon = document.getElementById(id+'-icon');
	var oObjIconDiv = document.getElementById(id+'-icondiv');
	
	var sColor = oStates[aMapObjects[intIndex].conf.summary_state].color;
	
	// Removed attribute check: "oObjIcon.style.border && "
	if(aMapObjects[intIndex].bIsFlashing === false) {
		// save state
		aMapObjects[intIndex].bIsFlashing = true;
		
		oObjIcon.style.border = "5px solid "+sColor;
		oObjIconDiv.style.top = (aMapObjects[intIndex].conf.y-5)+'px';
		oObjIconDiv.style.left = (aMapObjects[intIndex].conf.x-5)+'px';
	} else {
		// save state
		aMapObjects[intIndex].bIsFlashing = false;
		
		oObjIcon.style.border = "none";
		oObjIconDiv.style.top = aMapObjects[intIndex].conf.y+'px';
		oObjIconDiv.style.left = aMapObjects[intIndex].conf.x+'px';
	}
	
	var iDurationNew = iDuration - iInterval;
	
	// Flash again until timer counted down and the border is hidden
	if(iDurationNew > 0 || (iDurationNew <= 0 && aMapObjects[intIndex].bIsFlashing === true)) {
		setTimeout(function() { flashIcon(intIndex, iDurationNew, iInterval); }, iInterval);
	}
	
	oObjIcon = null;
	oObjIconDiv = null;
}

//--- Overview -----------------------------------------------------------------


/**
 * parseOverviewPage()
 *
 * Parses the static html code of the overview page 
 *
 * @param   Array    Array of objects to parse to the map
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseOverviewPage() {
	var oContainer = document.getElementById('overview');
	
	var oTable = document.createElement('table');
	oTable.setAttribute('class', 'infobox');
	oTable.setAttribute('className', 'infobox');
	
	var oTbody = document.createElement('tbody');
	oTbody.setAttribute('id', 'overviewMaps');
	
	var oTr = document.createElement('tr');
	
	var oTh = document.createElement('th');
	oTh.colSpan = oPageProperties.cellsperrow;
	oTh.appendChild(document.createTextNode(oPageProperties.lang_mapIndex));
	
	oTr.appendChild(oTh);
	oTh = null;
	
	oTbody.appendChild(oTr);
	oTr = null;
	
	oTable.appendChild(oTbody);
	oTbody = null;
	
	oContainer.appendChild(oTable);
	oTable = null;
	
	if(oPageProperties.showrotations && aInitialRotations.length > 0) {
		oTable = document.createElement('table');
		oTable.setAttribute('class', 'infobox');
		oTable.setAttribute('className', 'infobox');
		
		oTbody = document.createElement('tbody');
		oTbody.setAttribute('id', 'overviewRotations');
		
		oTr = document.createElement('tr');
		
		oTh = document.createElement('th');
		oTh.colSpan = 2;
		oTh.appendChild(document.createTextNode(oPageProperties.lang_rotationPools));
		
		oTr.appendChild(oTh);
		oTh = null;
		
		oTbody.appendChild(oTr);
		oTr = null;
		
		oTable.appendChild(oTbody);
		oTbody = null;
		
		oContainer.appendChild(oTable);
		oTable = null;
	}
	
	oContainer = null;
}

/**
 * parseOverviewMaps()
 *
 * Does initial parsing of maps on the overview page
 *
 * @param   Array    Array of objects to parse to the map
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseOverviewMaps(aMapsConf) {
	eventlog("worker", "debug", "parseOverviewMaps: Start setting maps");
	
	var oTable = document.getElementById('overviewMaps');
	var oTr = document.createElement('tr');
	
	for(var i = 0, len = aMapsConf.length; i < len; i++) {
		var oObj;
		
		oObj = new NagVisMap(aMapsConf[i]);
		
		if(oObj !== null) {
			// Save object to map objects array
			aMaps.push(oObj);
			
			// Parse child and save reference in parsedObject
			oObj.parsedObject = oTr.appendChild(oObj.parseOverview());
		}
		
		if((i+1) % oPageProperties.cellsperrow === 0) {
			oTable.appendChild(oTr);
			oTr = null;
			oTr = document.createElement('tr');
		}
		
		oObj = null;
	}
	
	// Fill table with empty cells if there are not enough maps to get the last 
	// row filled
	if(i % oPageProperties.cellsperrow !== 0) {
		for(var a = 0; a < (oPageProperties.cellsperrow - (i % oPageProperties.cellsperrow)); a++) {
			var oTd = document.createElement('td');
			oTr.appendChild(oTd);
			oTd = null;
		}
	}
	
	// Append last row
	oTable.appendChild(oTr);
	oTr = null;
	
	oTable = null;
	
	eventlog("worker", "debug", "parseOverviewMaps: End setting maps");
}

/**
 * setOverviewRotations()
 *
 * Does initial parsing of rotations on the overview page
 *
 * @param   Array    Array of objects to parse to the map
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setOverviewRotations(aRotationsConf) {
	eventlog("worker", "debug", "setOverviewObjects: Start setting rotations");
	
	if(oPageProperties.showrotations && aRotationsConf.length > 0) {
		for(var i = 0, len = aRotationsConf.length; i < len; i++) {
			var oObj;
			
			oObj = new NagVisRotation(aRotationsConf[i]);
			
			if(oObj !== null) {
				// Save object to map objects array
				aRotations.push(oObj);
				
				// Parse object to overview
				oObj.parseOverview();
			}
		}
	}
	
	eventlog("worker", "debug", "setOverviewObjects: End setting rotations");
}

/**
 * reparseMap()
 *
 * Reparses the map on e.g. changed map configuration
 *
 * @return  Boolean  Success?
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function reparseMap(iMapCfgAge) {
	var bReturn = false;
	
	// Get new map/object information from ajax handler
	var oMapBasics = getSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getMapProperties&objName1='+oPageProperties.map_name);
	var oMapObjects = getSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getMapObjects&objName1='+oPageProperties.map_name);
	
	// Only perform the reparsing actions when all information are there 
	if(oMapBasics && oMapObjects) {
		// Remove all old objects
		var a = 0;
		do {
			if(aMapObjects[a] && typeof aMapObjects[a].remove === 'function') {
				// Remove parsed object from map
				aMapObjects[a].remove();
				
				// Set to null in array
				aMapObjects[a] = null;
				
				// Remove element from map objects array
				aMapObjects.splice(a,1);
			} else {
				a++;
			}
		} while(aMapObjects.length > a);
		a = null;
		
		// Update timestamp for map configuration (No reparsing next time)
		oFileAges.map_config = iMapCfgAge;
			
		// Set map basics
		setMapBasics(oMapBasics);
		
		// Set map objects
		setMapObjects(oMapObjects);
		
		// Bulk get all hover templates which are needed on the map
		getHoverTemplates(aMapObjects);
		setMapHoverUrls();
		
		// Assign the hover templates to the objects and parse them
		parseHoverMenus(aMapObjects);
		
		// Bulk get all context templates which are needed on the map
		getContextTemplates(aMapObjects);
		
		// Assign the context templates to the objects and parse them
		parseContextMenus(aMapObjects);
		
		bReturn = true;
	} else {
		bReturn = false;
	}
	
	oMapBasics = null;
	oMapObjects = null;
	
	return bReturn;
}

/**
 * runWorker()
 *
 * This function is the heart of the new NagVis frontend. It's called worker.
 * The worker is being called by setTimeout() every second. This method checks
 * for tasks which need to be performed like:
 * - Countdown the timers
 * - Rotating to other page
 * - Reload page because of configuration file changes
 * - Handling configuration file changes
 * - Get objects which need an update of the state information
 * - Update the state information
 * - Handle state changes
 * After all work it goes to sleep for 1 second and calls itselfs again. These
 * tasks are not performed every run, some every second and some every 
 * configured worker_interval. The state information is refreshed like 
 * configured in worker_update_object_states.
 *
 * @param   Integer  The iterator for the run id
 * @param   String   The type of the page which is currently displayed
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function runWorker(iCount, sType) {
	// If the iterator is 0 it is the first run of the worker. Its only task is
	// to render the page
	if(iCount === 0) {
		// Initialize everything
		eventlog("worker", "info", "Initializing Worker (Run-ID: "+iCount+")");
		
		// Handle the map rendering
		if(sType == 'map') {
			setMapBasics(oPageProperties);
			
			// Create map objects from initialObjects and add them to aMapObjects
			eventlog("worker", "info", "Parsing map objects");
			setMapObjects(aInitialMapObjects);
			
			// Bulk get all hover templates which are needed on the map
			eventlog("worker", "info", "Fetching hover templates and hover urls");
			getHoverTemplates(aMapObjects);
			setMapHoverUrls();
			
			// Assign the hover templates to the objects and parse them
			eventlog("worker", "info", "Parse hover menus");
			parseHoverMenus(aMapObjects);
			
			// Bulk get all context templates which are needed on the map
			eventlog("worker", "info", "Fetching context templates");
			getContextTemplates(aMapObjects);
			
			// Assign the context templates to the objects and parse them
			eventlog("worker", "info", "Parse context menus");
			parseContextMenus(aMapObjects);
			
			eventlog("worker", "info", "Finished parsing map");
		} else if(sType === 'overview') {
			setPageBasics(oPageProperties);
			
			eventlog("worker", "info", "Parsing overview page");
			parseOverviewPage();
			
			eventlog("worker", "info", "Parsing maps");
			parseOverviewMaps(aInitialMaps);
			
			eventlog("worker", "info", "Parsing rotations");
			setOverviewRotations(aInitialRotations);
			
			// Bulk get all hover templates which are needed on the map
			eventlog("worker", "info", "Fetching hover templates");
			getHoverTemplates(aMaps);
			
			// Assign the hover templates to the objects and parse them
			eventlog("worker", "info", "Parse hover menus");
			parseHoverMenus(aMaps);
			
			// Bulk get all context templates which are needed on the map
			eventlog("worker", "info", "Fetching context templates");
			getContextTemplates(aMaps);
			
			// Assign the context templates to the objects and parse them
			// FIXME: No context menus on overview page atm
			//eventlog("worker", "info", "Parse context menus");
			//parseContextMenus(aMaps);
			
			eventlog("worker", "info", "Finished parsing overview");
		}
	} else {
		/**
		 * Do these actions every run (every second) excepting the first run 
		 */
		
		iNow = Date.parse(new Date());
		
		// Countdown the rotation counter
		// Not handled by ajax frontend. Reload the page with the new url
		// If it returns true this means that the page is being changed: Stop the
		// worker.
		if(rotationCountdown() === true) {
			eventlog("worker", "debug", "Worker stopped: Rotate/Refresh detected");
			return false;
		}
		
		/**
		 * Do these actions every X runs (Every worker_interval seconds)
		 */
		
		if(sType === 'map') {
			if(iCount % oWorkerProperties.worker_interval === 0) {
				// Log normal worker step
				eventlog("worker", "debug", "Update (Run-ID: "+iCount+")");
				
				// Get the file ages of important files
				var oCurrentFileAges = getCfgFileAges();
				
				// Check for changed main configuration
				if(oCurrentFileAges && checkMainCfgChanged(oCurrentFileAges.mainCfg)) {
					// FIXME: Not handled by ajax frontend, reload the page
					window.location.reload(true);
				}
				
				// Check for changed map configuration
				if(oCurrentFileAges && checkMapCfgChanged(oCurrentFileAges[oPageProperties.map_name])) {
					if(reparseMap(oCurrentFileAges[oPageProperties.map_name]) === false) {
						eventlog("worker", "error", "Problem while reparsing the map after new map configuration");
					}
				}

				oCurrentFileAges = null;
				
				/*
				 * Now proceed with real actions when everything is OK
				 */
				
				// Get objects which need an update
				var arrObj = getObjectsToUpdate(aMapObjects);
				
				// Create the ajax request for bulk update, handle shape updates
				var aUrlParts = [];
				var aShapesToUpdate = [];
				var iUrlParams = 0;
				var iUrlLength = 0;
				
				// Only continue with the loop when below param limit
				// and below maximum length
				for(var i = 0, len = arrObj.length; i < len && (oWorkerProperties.worker_request_max_params == 0 || (oWorkerProperties.worker_request_max_params != 0 && iUrlParams < oWorkerProperties.worker_request_max_params)) && iUrlLength < oWorkerProperties.worker_request_max_length; i++) {
					var type = aMapObjects[arrObj[i]].conf.type;
					
					// Seperate shapes from rest
					if(type === 'shape') {
						// Shapes which need to be updated need a special handling
						aShapesToUpdate.push(arrObj[i]);
					} else {
						// Handle other objects
						var name = aMapObjects[arrObj[i]].conf.name;
						
						if(name) {
							var obj_id = aMapObjects[arrObj[i]].conf.object_id;
							var service_description = aMapObjects[arrObj[i]].conf.service_description;
							var map = oPageProperties.map_name;
							
							// Create request string
							var sUrlPart = '&i[]='+obj_id+'&m[]='+map+'&t[]='+type+'&n1[]='+name;
							if(service_description) {
								sUrlPart = sUrlPart + '&n2[]='+escapeUrlValues(service_description);
							} else {
								sUrlPart = sUrlPart + '&n2[]=';
							}
							
							// Adding 4 params above code, count them here
							iUrlParams += 4;
							
							// Also count the length
							iUrlLength += sUrlPart.length
							
							// Append part to array of parts
							aUrlParts.push(sUrlPart);
						}
					}
				}
				iUrlParams = null;
				iUrlLength = null;
				arrObj = null;
				
				// Get the updated objectsupdateMapObjects via bulk request
				var o = getBulkSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getObjectStates&ty=state', aUrlParts, oWorkerProperties.worker_request_max_length, false);
				var bStateChanged = false;
				if(o.length > 0) {
					bStateChanged = updateObjects(o, aMapObjects, sType);
				}
				o = null;
				aUrlParts = null;
				
				// Update shapes when needed
				if(aShapesToUpdate.length > 0) {
					updateShapes(aShapesToUpdate);
				}
				aShapesToUpdate = null;
				
				// When some state changed on the map update the title and favicon
				if(bStateChanged) {
					updateMapBasics();
				}
				bStateChanged = null;
				
				// Update lastWorkerRun
				oWorkerProperties.last_run = iNow;
				
				// Update the worker counter on maps
				updateWorkerCounter();
				
				// Cleanup ajax query cache
				cleanupAjaxQueryCache();
			}
		} else if(sType === 'overview') {
			if(iCount % oWorkerProperties.worker_interval === 0) {
				// Log normal worker step
				eventlog("worker", "debug", "Update (Run-ID: "+iCount+")");
				
				// Get the file ages of important files
				var oCurrentFileAges = getCfgFileAges(false);
				
				// Check for changed main configuration
				if(oCurrentFileAges && checkMainCfgChanged(oCurrentFileAges.mainCfg)) {
					// FIXME: Not handled by ajax frontend, reload the page
					window.location.reload(true);
				}
				
				//FIXME: Map configuration changed?
				
				/*
				 * Now proceed with real actions when everything is OK
				 */
				
				// Get objects which need an update
				var arrObj = getObjectsToUpdate(aMaps);
				
				// Create the ajax request for bulk update, handle object updates
				var aUrlParts = [];
				for(var i = 0, len = arrObj.length; i < len; i++) {
					var name = aMaps[arrObj[i]].conf.name;
					
					if(name) {
						var type = aMaps[arrObj[i]].conf.type;
						var obj_id = aMaps[arrObj[i]].conf.object_id;
						var service_description = aMaps[arrObj[i]].conf.service_description;
						var map = oPageProperties.map_name;
						
						// Create request url part for this object
						var sUrlPart = '&i[]='+obj_id+'&t[]='+type+'&n1[]='+name;
						if(service_description) {
							sUrlPart = sUrlPart + '&n2[]='+escapeUrlValues(service_description);
						} else {
							sUrlPart = sUrlPart + '&n2[]=';
						}
						
						// Append part to array of parts
						aUrlParts.push(sUrlPart);
					}
				}
				
				// Get the updated objectsupdateMapObjects via bulk request
				var o = getBulkSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getObjectStates&ty=state', aUrlParts, oWorkerProperties.worker_request_max_length, false);
				var bStateChanged = false;
				if(o.length > 0) {
					bStateChanged = updateObjects(o, aMaps, sType);
				}
				aUrlParts = null;
				o = null;
				
				// When some state changed on the map update the title and favicon
				/* FIXME: if(bStateChanged) {
					var o = getSyncRequest(oGeneralProperties.path_htmlbase+'/nagvis/ajax_handler.php?action=getObjectStates&ty=state&i[]='+oPageProperties.map_name+'&m[]=&t[]=map&n1[]='+oPageProperties.map_name+'&n2[]=', false)[0];
					
					// Update favicon
					setPageFavicon(getFaviconImage(o));
					
					// Update page title
					setPageTitle(oPageProperties.alias+' ('+o.summary_state+') :: '+oGeneralProperties.internal_title);
					
					// Change background color
					if(oPageProperties.event_background && oPageProperties.event_background == '1') {
						setPageBackgroundColor(getBackgroundColor(o));
					}
				}*/
				
				// Update lastWorkerRun
				oWorkerProperties.last_run = iNow;
				
				// Update the worker counter on maps
				updateWorkerCounter();
				
				// Cleanup ajax query cache
				cleanupAjaxQueryCache();
			}
		}
	}
	
	// Sleep until next worker run (1 Second)
	window.setTimeout(function() { runWorker((iCount+1), sType); }, 1000);
	
	// Pro forma return
	return true;
}
