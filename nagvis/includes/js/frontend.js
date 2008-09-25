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
var oHoverTemplates = Object();

/**
 * runWorker()
 *
 * This function is the heart of the new NagVis frontend. It's called worker.
 * The worker is being called by setTimeout() every second. This method checks
 * for tasks which need to be performed like:
 * - Countdown the timers
 * - Rotating to other page
 * - Reload page cause of configuration file changes
 * - Handling configuration file changes
 * - Get objects which need an update of the state informations
 * - Update the state informations
 * - Handle state changes
 * After all work it goes to sleep for 1 second and calls itselfs again. These
 * tasks are note performed every run, some every second and some every 
 * configured worker_interval. The state informations are refreshed like 
 * configured in worker_update_object_states.
 *
 * @param   Integer  The iterator for the run id
 * @param   String   The type of the page which is currently displayed
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function runWorker(iCount, sType) {
	// If the iterator is 0 it is the first run of the worker. It's only task is
	// to render the page
	if(iCount == 0) {
		// Initialize everything
		eventlog("worker", "info", "Initializing Worker (Run-ID: "+iCount+")");
		
		// Handle the map rendering
		if(sType == 'map') {
			setMapBasics(oMapProperties);
			
			eventlog("worker", "info", "Parsing map objects");
			setMapObjects(aInitialMapObjects);
			
			// Bulk get all hover templates which are needed on the map
			eventlog("worker", "info", "Fetching hover templates");
			setMapHoverTemplates();
			
			// Asign the hover templates to the objects and parse them
			eventlog("worker", "info", "Parse hover menus");
			parseMapObjectsHoverMenu();
			
			eventlog("worker", "info", "Finished parsing map");
		}
	} else {
		/**
		 * Do these actions every run (Every second)
		 */
		
		// Countdown the rotation counter
		// FIXME: Not handled by ajax frontend. Reload the page with the new url
		// If it returns true this means that the page is being changed: Stop the
		// worker.
		if(rotationCountdown() === true) {
			eventlog("worker", "debug", "Worker stopped: Rotate/Refresh detected");
			return false;
		}
		
		/**
		 * Do these actions every X runs (Every worker_interval seconds)
		 */
		
		if(sType == 'map') {
			if(iCount % oWorkerProperties.worker_interval == 0) {
				// Log normal worker step
				eventlog("worker", "debug", "Update (Run-ID: "+iCount+")");
				
				// Check for changed main configuration
				if(checkMainCfgChanged()) {
					// FIXME: Not handled by ajax frontend, reload the page
					window.location.reload(true);
				}
				
				// Check for changed map configuration
				if(checkMapCfgChanged(oMapProperties.map_name)) {
					// Set map basics and map objects
					setMapBasics(getSyncRequest(htmlBase+'/nagvis/ajax_handler.php?action=getMapProperties&objName1='+oMapProperties.map_name));
					setMapObjects(getSyncRequest(htmlBase+'/nagvis/ajax_handler.php?action=getMapObjects&objName1='+oMapProperties.map_name));
					
					// Bulk get all hover templates which are needed on the map
					setMapHoverTemplates();
					
					// Asign the hover templates to the objects and parse them
					parseMapObjectsHoverMenu();
				}
				
				// Now proceed with real actions when everything is OK
				var arrObj = getObjectsToUpdate();
				
				// Create the ajax request for bulk update
				var aUrlParts = Array();
				for(var i = 0; i < arrObj.length; i++) {
					var obj_id = aMapObjects[arrObj[i]].objId;
					var type = aMapObjects[arrObj[i]].conf.type;
					var name = aMapObjects[arrObj[i]].conf.name;
					var service_description = aMapObjects[arrObj[i]].conf.service_description;
					var map = oMapProperties.map_name;
					var sUrlPart = '';
					
					if(name) {
						sUrlPart += '&i[]='+obj_id;
						sUrlPart += '&m[]='+map;
						sUrlPart += '&t[]='+type;
						sUrlPart += '&n1[]='+name;
					
						if(service_description) {
							sUrlPart += '&n2[]='+service_description;
						} else {
							sUrlPart += '&n2[]=';
						}
						
						// Append part to array of parts
						aUrlParts.push(sUrlPart);
					}
				}
				
				// Get the updated objects via bulk request
				updateMapObjects(getBulkSyncRequest(htmlBase+'/nagvis/ajax_handler.php?action=getObjectStates&ty=state', aUrlParts, 1900, false));
			}
		}
	}
	
	// Sleep until next worker run (1 Second)
	window.setTimeout("runWorker("+(iCount+1)+", '"+sType+"')", 1000);
}

/**
 * getObjectsToUpdate()
 *
 * Detects objects with deprecated state informations
 *
 * @return  Array    The array of aMapObjects indexes which need an update
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getObjectsToUpdate() {
	eventlog("worker", "debug", "getObjectsToUpdate: Start");
	var arrReturn = Array();
	
	// Asign all object indexes to return Array
	for(var i = 0; i < aMapObjects.length; i++) {
		if(aMapObjects[i].lastUpdate <= (Date.parse(new Date())-(oWorkerProperties.worker_update_object_states*1000))) {
			arrReturn.push(i);
		}
	}
	
	// Now spread the objects in the available timeslots
	var iNumTimeslots = Math.ceil(oWorkerProperties.worker_update_object_states / oWorkerProperties.worker_interval);
	var iNumObjectsPerTimeslot = Math.ceil(aMapObjects.length / iNumTimeslots);
	eventlog("worker", "debug", "Number of timeslots: "+iNumTimeslots+" Number of Objects per Slot: "+iNumObjectsPerTimeslot);
	
	// Only spread when the number of objects is larger than the objects for each
	// timeslot
	if(arrReturn.length > iNumObjectsPerTimeslot) {
		eventlog("worker", "debug", "Spreading map objects in timeslots");
		// Just remove all elements from the end of the array
		arrReturn = arrReturn.splice(iNumObjectsPerTimeslot, arrReturn.length-iNumObjectsPerTimeslot);
	}
	
	eventlog("worker", "debug", "getObjectsToUpdate: Have to update "+arrReturn.length+" objects");
	return arrReturn;
}

/**
 * checkMainCfgChanged()
 *
 * Detects if the main configuration file has changed since last load
 *
 * @return  Boolean
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function checkMainCfgChanged() {
	var iCurrentAge = getSyncRequest(htmlBase+'/nagvis/ajax_handler.php?action=getMainCfgFileAge').age;
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
function checkMapCfgChanged(mapName) {
	var iCurrentAge = getSyncRequest(htmlBase+'/nagvis/ajax_handler.php?action=getMapCfgFileAge&objName1='+mapName).age;
	eventlog("worker", "debug", "MainCfg Current: "+date(oGeneralProperties.date_format, iCurrentAge)+" Cached: "+date(oGeneralProperties.date_format, oFileAges.map_config));
	
	if(oFileAges.map_config != iCurrentAge) {
		return true;
	} else {
		return false;
	}
}

/**
 * setMapHoverTemplates()
 *
 * Gets the code for needed hover templates and saves it for later use in icons
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapHoverTemplates() {
	var aUrlParts = Array();
	var aTemplateObjects;
	
	// Loop all map objects to get the used hover templates
	for(var a = 0; a < aMapObjects.length; a++) {
		// Ignore objects which
		// a) have a disabled hover menu
		// b) use hover_url
		if(aMapObjects[a].conf.hover_menu && (!aMapObjects[a].conf.hover_url || aMapObjects[a].conf.hover_url == '')) {
			oHoverTemplates[aMapObjects[a].conf.hover_template] = '';
		}
	}
	
	// Build string for bulk fetching the templates
	for(i in oHoverTemplates) {
		aUrlParts.push('&name[]='+i);
	}
	
	// Get the needed templates via bulk request
	aTemplateObjects = getBulkSyncRequest(htmlBase+'/nagvis/ajax_handler.php?action=getHoverTemplate', aUrlParts, 1900, true);
	
	// Set the code to global object oHoverTemplates
	if(aTemplateObjects.length > 0) {
		for(var i = 0; i < aTemplateObjects.length; i++) {
			oHoverTemplates[aTemplateObjects[i].name] = aTemplateObjects[i].code;
		}
	}
}


/**
 * parseMapObjectsHoverMenu()
 *
 * Asigns the hover template code to the object, replaces all macros and
 * adds the menu to all map objects
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseMapObjectsHoverMenu() {
	for(var a = 0; a < aMapObjects.length; a++) {
		aMapObjects[a].parseHoverMenu();
	}
}

/**
 * setMapBasics()
 *
 * Sets basic informations like background image, favicon and page title
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapBasics(oProperties) {
	setMapBackground(oProperties.background_image);
	setMapFavicon(oProperties.favicon_image);
	setMapPageTitle(oProperties.page_title);
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
	for(var i = 0; i < aMapObjectConf.length; i++) {
		var sType = aMapObjectConf[i].type
		var oObj;
		
		switch (sType) {
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
				alert('Error: Unknown object type');
			break;
		}
		
		if(oObj != null) {
			// Save object to map objects array
			aMapObjects.push(oObj);
			
			// Parse object to map
			oObj.parse();
		}
	}
	eventlog("worker", "debug", "setMapObjects: End setting map objects");
}

/**
 * updateMapObjects()
 *
 * Bulk update map objects
 *
 * @param   Array    Array of objects to parse to the map
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function updateMapObjects(aMapObjectInformations) {
	for(var i = 0; i < aMapObjectInformations.length; i++) {
		var objId = aMapObjectInformations[i].objId;
		var intIndex = -1;
		
		// Find the id with the matching objId
		for(var a = 0; a < aMapObjects.length; a++) {
			if(aMapObjects[a].objId == objId) {
				intIndex = a;
			}
		}
		
		var oObj = aMapObjects[intIndex];
		
		// Save old state for later "change detection"
		oObj.saveLastState();
		
		if(intIndex >= 0) {
			// Update this object
			for (var strIndex in aMapObjectInformations[i]) {
				if(aMapObjectInformations[i][strIndex] != 'objId') {
					oObj.conf[strIndex] = aMapObjectInformations[i][strIndex];
				}
			}
		}
		
		// Update lastUpdate timestamp
		oObj.setLastUpdate();

		// Reparse the hover menu
		oObj.parseHoverMenu();
		
		// Detect state changes and do some custom actions
		if(oObj.stateChanged()) {
			
			/* Internal handling */
			
			// Reparse object to map
			oObj.parse();
			
			/**
			 * Additional eventhandling
			 *
			 * Make the event-handling configurable
			 * This should be configured in map globals section
			 *
			 * event_log=1/0 -> already implemented
			 * event_log_level=1/0 -> already implemented
			 * event_highlight=1/0
			 * event_scrollto=1/0
			 * event_sound=1/0
			 */
			
			// - Highlight (Flashing)
			if(oObj.conf.line_type) {
				//FIXME: Atm only flash icons, not lines
			} else {
				flashIcon(intIndex, 10);
			}
			
			// - Scroll to object
			//window.scrollTo(oObj.conf.x, oObj.conf.y);
			//FIXME: scrollSlow(oObj.conf.x, oObj.conf.y, 50);
			
			// - Eventlog
			if(oObj.conf.type == 'service') {
				eventlog("state-change", "info", oObj.conf.type+" "+oObj.conf.name+" "+oObj.conf.service_description+": Old state: "+oObj.last_conf.summary_state+" New state: "+oObj.conf.summary_state);
			} else {
				eventlog("state-change", "info", oObj.conf.type+" "+oObj.conf.name+": Old state: "+oObj.last_conf.summary_state+" New state: "+oObj.conf.summary_state);
			}
			
			// - Sound
			playSound(intIndex, 1);
		}
		
	}
}

/**
 * playSound()
 *
 * Play a sound
 *
 * @param   Integer  Index in aMapObjects
 * @param   Integer  Iterator for number of left runs
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function playSound(intIndex, iNumTimes){
	var id = aMapObjects[intIndex].parsedObject.id
	
	var oObjIcon = document.getElementById(id+'-icon');
	var oObjIconDiv = document.getElementById(id+'-icondiv');
	
	var sSound = oStates[aMapObjects[intIndex].conf.summary_state].sound;
	
	// Check if there is a sound defined for the current state
	if(sSound != '') {
		//FIXME: Play sound
	}
	
	iNumTimes = iNumTimes - 1;
	
	if(iNumTimes > 0) {
		setTimeout("playSound("+intIndex+", "+iNumTimes+");", 500);
	}
}

/**
 * flashIcon()
 *
 * Highlights and object by show/hide a border arround the icon
 *
 * @param   Integer  Index in aMapObjects
 * @param   Integer  Iterator for number of left runs
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function flashIcon(intIndex, iNumTimes){
	var id = aMapObjects[intIndex].parsedObject.id
	
	var oObjIcon = document.getElementById(id+'-icon');
	var oObjIconDiv = document.getElementById(id+'-icondiv');
	
	var sColor = oStates[aMapObjects[intIndex].conf.summary_state].color;
	
	if(oObjIcon.style.border && oObjIcon.style.border.indexOf("none") != -1) {
		oObjIcon.style.border = "5px solid "+sColor;
		oObjIconDiv.style.top = (aMapObjects[intIndex].conf.y-5)+'px';
		oObjIconDiv.style.left = (aMapObjects[intIndex].conf.x-5)+'px';
	} else {
		oObjIcon.style.border = "none";
		oObjIconDiv.style.top = aMapObjects[intIndex].conf.y+'px';
		oObjIconDiv.style.left = aMapObjects[intIndex].conf.x+'px';
	}
	
	iNumTimes = iNumTimes - 1;
	
	if(iNumTimes > 0 || (iNumTimes == 0 && oObjIcon.style.border.indexOf("none") == -1)) {
		setTimeout("flashIcon("+intIndex+", "+iNumTimes+");", 500);
	}
}

/**
 * setMapBackground()
 *
 * Parses the background image to the map
 *
 * @param   String   Path to map images
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapBackground(sImage) {
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
}

/**
 * setMapFavicon()
 *
 * Sets the favicon of the pages
 *
 * @param   String   Path to the icon image
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapFavicon(sFavicon) {
	favicon.change(sFavicon);
}

/**
 * setMapPageTitle()
 *
 * Sets the title of the current page
 *
 * @param   String   Title
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapPageTitle(sTitle) {
	document.title = sTitle;
}
