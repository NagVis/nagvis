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
			setMapObjects(aInitialMapObjects);
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
				}
				
				// Now proceed with real actions when everything is OK
				var arrObj = getObjectsToUpdate();
				
				// Create the ajax request for bulk update
				var strNames = '';
				for(var i = 0; i < arrObj.length; i++) {
					var obj_id = aMapObjects[arrObj[i]].objId;
					var type = aMapObjects[arrObj[i]].conf.type;
					var name = aMapObjects[arrObj[i]].conf.name;
					var service_description = aMapObjects[arrObj[i]].conf.service_description;
					var map = oMapProperties.map_name;
					
					if(name) {
						strNames += '&objId[]='+obj_id;
						strNames += '&map[]='+map;
						strNames += '&objType[]='+type;
						strNames += '&objName1[]='+name;
					
						if(service_description) {
							strNames += '&objName2[]='+service_description;
						} else {
							strNames += '&objName2[]=';
						}
					}
				}
				
				if(strNames != '') {
					// Bulk update the objects, this query should not be cached
					updateMapObjects(getSyncRequest(htmlBase+'/nagvis/ajax_handler.php?action=getObjectStates&type=state'+strNames, false));
				}
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
	
	for(var i = 0; i < aMapObjects.length; i++) {
		if(aMapObjects[i].lastUpdate <= (Date.parse(new Date())-(oWorkerProperties.worker_update_object_states*1000))) {
			arrReturn.push(i);
		}
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
