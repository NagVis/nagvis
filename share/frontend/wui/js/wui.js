/*****************************************************************************
 *
 * wui.js - Functions which are used by the WUI
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
 
var cpt_clicks = 0;
var coords = '';
var objtype = '';
var follow_mouse = false;
var action_click = "";
var myshape = null;
var myshape_background = null;
var myshapex = 0;
var myshapey = 0;
var objid = -1;
var viewType = '';

/**
 * Toggle the grid state in the current view and sends
 * current setting to server component for persistance
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function gridToggle() {
	// Toggle the grid state
	if(oViewProperties.grid_show === 1) {
		oViewProperties.grid_show = 0;
		
		// Remove from view
		var oMap = document.getElementById('mymap');
		var oGrid = document.getElementById('grid');
		oMap.removeChild(oGrid);
		oGrid = null;
		oMap = null;
	} else {
		oViewProperties.grid_show = 1;
		
		// Add to view
		gridParse();
	}
	
	// Send current option to server component
	var url = oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='+mapname+'&type=global&id=0&grid_show='+oViewProperties.grid_show;
	
	// Sync ajax request
	var oResult = getSyncRequest(url);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
	}
	
	oResult = null;
}

/**
 * Parses a grind to make the alignment of the icons easier
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function gridParse() {
	// Only show when user configured to see a grid
	if(oViewProperties.grid_show === 1) {
		// Create grid container and append to map
		var oMap = document.getElementById('mymap');
		var oGrid = document.createElement('div');
		oGrid.setAttribute('id', 'grid');
		oMap.appendChild(oGrid);
		oGrid = null;
		oMap = null;
		
		// Add an options: grid_show, grid_steps, grid_color
		var grid = new jsGraphics('grid');
		grid.setColor(oViewProperties.grid_color);
		grid.setStroke(1);
		
		var gridStep = oViewProperties.grid_steps;
		
		// Start
		var gridYStart = 0;
		var gridXStart = 0;
		
		// End: Get screen height, width
		var gridYEnd = pageHeight() - getHeaderHeight();
		var gridXEnd = pageWidth();
		
		// Draw vertical lines
		for(var gridX = 32; gridX < gridXEnd; gridX = gridX + gridStep) {
			grid.drawLine(gridX, gridYStart, gridX, gridYEnd);
		}
		// Draw horizontal lines
		for(var gridY = 32; gridY < gridYEnd; gridY = gridY + gridStep) {
			grid.drawLine(gridXStart, gridY, gridXEnd, gridY);
		}
		
		grid.paint();
		
		gridXEnd = null
		gridYEnd = null;
		gridXStart = null;
		gridYStart = null;
		gridStep = null;
		grid = null;
	}
}

// FIXME: Maybe move to nagvis-js frontend file to have it available in 
// regular frontend in the future
function getHeaderHeight() {
	var oHeader = null;
	var ret = 0;
	
	// FIXME: Check if header is shown
	
	oHeader = document.getElementById('header');
	if(oHeader !== null) {
		ret = oHeader.clientHeight;
	}
	
	oHeader = null;
	
	return ret;
}

// function that says if the current user is allowed to have access to the map
function checkUserAllowed(allowedUsers,username) {
	for(var i=0;i<allowedUsers.length;i++) {
		if((allowedUsers[i] == username) || (allowedUsers[i] == "EVERYONE") ) {
			return true;
		}
	}
	return false;
}

function getMapPermissions(mapName, mapOptions, permissionLevel) {
	if(permissionLevel == "") {
		permissionLevel = "allowedUsers";
	}
	
	for(var i=0;i<mapOptions.length;i++) {
		if(mapOptions[i].mapName == mapName) {
			if(permissionLevel == "allowedForConfig") {
				return mapOptions[i].allowedForConfig;
			} else if(permissionLevel == "allowedUsers") {
				return mapOptions[i].allowedUsers;
			} else if(permissionLevel == "allowedUsersOrAllowedForConfig") {
				return mapOptions[i].allowedForConfig.concat(mapOptions[i].allowedUsers);
			} else {
				return false;	
			}
		}
	}
	return false;
}

/**
 * validateValue(oField)
 *
 * This function checks a string for valid format. The check is done by the
 * given regex.
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function validateValue(sName, sValue, sRegex) {
	// Remove PHP delimiters
	sRegex = sRegex.replace(/^\//, "");
	sRegex = sRegex.replace(/\/[igm]*$/, "");
	
	// Match the current value
	var regex = new RegExp(sRegex, "i");
	var match = regex.exec(sValue);
	if(sValue == '' || match != null) {
		return true;
	} else {
		alert(printLang(lang['wrongValueFormatOption'],'ATTRIBUTE~'+sName));
		return false;
	}
}

// functions used to track the mouse movements, when the user is adding an object. Draw a line a rectangle following the mouse
// when the user has defined enough points we open the "add object" window
function get_click(newtype,nbclicks,action) {
	coords = '';
	action_click = action;
	objtype = newtype;
	
	// we init the number of points coordinates we're going to wait for before we display the add object window
	cpt_clicks=nbclicks;
	
	if(document.images['background']) {
		document.images['background'].style.cursor = 'crosshair';
	}
	
	document.onclick = get_click_pos;
	document.onmousemove = track_mouse;
}

function printLang(sLang,sReplace) {
	sLang = sLang.replace(/<(\/|)(i|b)>/ig,'');
	
	// sReplace maybe optional
	if(typeof sReplace != "undefined") {
		aReplace = sReplace.split(",")
		for(var i = 0; i < aReplace.length; i++) {
			var aReplaceSplit = aReplace[i].split("~");
			sLang = sLang.replace("["+aReplaceSplit[0]+"]",aReplaceSplit[1]);
		}
	}
	
	return sLang;
}

function track_mouse(e) {
	if(follow_mouse) {
		var event;
		if(!e) {
			event = window.event;
		} else {
			event = e;
		}
		
		if (event.pageX || event.pageY) {
			posx = event.pageX;
			posy = event.pageY;
		} else if (event.clientX || event.clientY) {
			posx = event.clientX;
			posy = event.clientY;
		}
		
		// Substract height of header menu here
		posy -= getHeaderHeight();
		
		myshape.clear();
		
		if(objtype != 'textbox') {
			myshape.drawLine(myshapex, myshapey, posx, posy);
		} else {
			myshape.drawRect(myshapex, myshapey, (posx - myshapex), (posy - myshapey));
		}
		
		myshape.paint();
	}
	
	return true;
}

function get_click_pos(e) {
	if(cpt_clicks > 0) {
		var posx = 0;
		var posy = 0;
		
		var event;
		if(!e) {
			event = window.event;
		} else {
			event = e;
		}
	
		if (event.pageX || event.pageY) {
			posx = event.pageX;
			posy = event.pageY;
		}
		else if (event.clientX || event.clientY) {
			posx = event.clientX;
			posy = event.clientY;
		}
		
		// Substract height of header menu here
		posy -= getHeaderHeight();
		
		// Start drawing a line
		if(cpt_clicks == 2) {		
			myshape = new jsGraphics("mymap");
			myshapex = posx;
			// Substract height of header menu here
			myshapey = posy;
			
			myshape.setColor('#06B606');
			myshape.setStroke(1);
			
			follow_mouse = true;
			
			// Save view_type for default selection in addmodify dialog
			viewType = 'line';
		}
		
		if(viewType == '') {
			viewType = 'icon';
		}
		
		// Save current click position
		coords = coords + posx + ',' + posy + ',';
		
		// Reduce number of clicks left
		cpt_clicks = cpt_clicks - 1;
	}
	
	if(cpt_clicks == 0) {
		if(follow_mouse) {
			myshape.clear();
		}
		
		coords = coords.substr(0, coords.length-1);
		
		if(document.images['background']) {
			document.images['background'].style.cursor = 'default';
		}
		
		follow_mouse = false;
		var sUrl;
		if(action_click == 'add' || action_click == 'clone') {
			sUrl = oGeneralProperties.path_server+'?mod=Map&act=addModify&do=add&map='+mapname+'&type='+objtype+'&coords='+coords+'&viewType='+viewType;
			
			if(action_click == 'clone' && objid !== -1) {
				sUrl += '&clone='+objid;
			}
		} else if(action_click == 'modify' && objid !== -1) {
			sUrl = oGeneralProperties.path_server+'?mod=Map&act=addModify&do=modify&map='+mapname+'&type='+objtype+'&id='+objid+'&coords='+coords;
		}
		
		// FIXME: Title "+get_label('properties')+"
		popupWindow('TITLE', getSyncRequest(sUrl, true, false));
		
		objid = -1;
		cpt_clicks = -1;
	}	
}

function moveMapObject(oObj) {
	// Save old coords for later return when some problem occured
	oObj.oldX = oObj.x;
	oObj.oldY = oObj.y;
	
	// Check if this is a box
	if(oObj.name.search('box_') != -1) {
		// When this object has a relative coordinated label, then move this too
		var sLabelName = oObj.name.replace('box_','rel_label_');
		var oLabel = document.getElementById(sLabelName);
		if(oLabel) {
			ADD_DHTML(sLabelName);
			oObj.addChild(sLabelName);
		}
		oLabel = null;
	}
}

function dragMapObject(oObj) {
	// Hide the hover menu while dragging
	// This function should not be called outside wz_tooltip (Not public) but the
	// normal function UnTip() does not work here
	tt_Hide();
}

function saveObjectAfterResize(oObj) {
	// Split id to get object informations
	var arr = oObj.id.split('_');
	
	var type = arr[1];
	var id = arr[2];
	
	var objX = parseInt(oObj.style.left.replace('px', ''));
	var objY = parseInt(oObj.style.top.replace('px', ''));
	var objW = parseInt(oObj.style.width);
	var objH = parseInt(oObj.style.height);
	
	// Don't forget to substract height of header menu
	var url = oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='+mapname+'&type='+type+'&id='+id+'&x='+objX+'&y='+objY+'&w='+objW+'&h='+objH;
	
	// Sync ajax request
	var oResult = getSyncRequest(url);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
	}
	
	oResult = null;
}

function saveObjectAfterMoveAndDrop(oObj) {
	// When x or y are negative just return this and make no change
	if((oObj.y - getHeaderHeight()) < 0 || oObj.x < 0) {
		oObj.moveTo(oObj.oldX, oObj.oldY);
		return;
	}
	
	// When a grid is enabled align the dragged object in the nearest grid
	if(oViewProperties.grid_show === 1) {
		var gridMoveX = oObj.x - (oObj.x % oViewProperties.grid_steps);
		var gridMoveY = oObj.y - (oObj.y % oViewProperties.grid_steps);
		
		oObj.moveTo(gridMoveX, gridMoveY);
	}
	
	// Reset z-index to configured value
	oObj.setZ(oObj.defz);
	
	// Split id to get object informations
	var arr = oObj.name.split('_');
	
	// Handle different ojects (Normal icons and labels)
	var type, id , url;
	if(arr[1] === 'label') {
		var align = arr[0];
		type = arr[2];
		id = arr[3];
		var x, y;
		
		// Handle relative and absolute aligned labels
		if(align === 'rel') {
			// Calculate relative coordinates
			var objX = parseInt(document.getElementById('box_'+type+'_'+id).style.left.replace('px', ''));
			var objY = parseInt(document.getElementById('box_'+type+'_'+id).style.top.replace('px', ''));
			
			// Substract height of header menu here
			objY += getHeaderHeight();
			
			x = oObj.x - objX;
			y = oObj.y - objY;
			
			// Add + sign to mark relative positive coords (On negative relative coord
			// the - sign is added automaticaly
			if(x >= 0) {
				// %2B is escaped +
				x = '%2B'+x;
			}
			if(y >= 0) {
				// %2B is escaped +
				y = '%2B'+y;
			}
		} else {
			x = oObj.x;
			// Substract height of header menu here
			y = oObj.y - getHeaderHeight();
		}
		
		url = oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='+mapname+'&type='+type+'&id='+id+'&label_x='+x+'&label_y='+y;
	} else {
		type = arr[1];
		id = arr[2];
		
		// Don't forget to substract height of header menu
		url = oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='+mapname+'&type='+type+'&id='+id+'&x='+oObj.x+'&y='+(oObj.y - getHeaderHeight());
	}
	
	// Sync ajax request
	var oResult = getSyncRequest(url);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
	}
	oResult = null;
}

// This function handles object deletions on maps
function deleteMapObject(objId) {
	if(confirm(printLang(lang['confirmDelete'],''))) {
		var arr = objId.split('_');
		var map = mapname;
		var type = arr[1];
		var id = arr[2];
		
		// Sync ajax request
		var oResult = getSyncRequest(oGeneralProperties.path_server+'?mod=Map&act=deleteObject&map='+map+'&type='+type+'&id='+id);
		if(oResult && oResult.status != 'OK') {
			alert(oResult.message);
			return false;
		}
		oResult = null;
		
		// Remove the object from the map
		document.getElementById('mymap').removeChild(document.getElementById(objId));
		
		return true;
	} else {
		return false;
	}
}

// simple function to ask to confirm before we restore a map
function confirm_restore() {
	if(confirm(printLang(lang['confirmRestore'],''))) {
		document.location.href='./form_handler.php?myaction=map_restore&map='+mapname;
	}
	return true;
}

/**
 * formSubmit()
 *
 * Submits the form contents to the ajax handler by a synchronous HTTP-GET
 *
 * @param   String   ID of the form
 * @param   String   Action to send to the ajax handler
 * @return  Boolean  
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function formSubmit(formId, target, bReloadPage) {
	if(typeof bReloadPage === 'undefined') {
		bReloadPage = true;
	}
	
	// Read form contents
	var getstr = getFormParams(formId);
	
	// Submit data
	var oResult = getSyncRequest(target+'&'+getstr);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
		return false;
	}
	oResult = null;
	
	// Close form
	popupWindowClose();
	
	// FIXME: Reloading the map (Change to reload object)
	if(bReloadPage === true) {
		document.location.href='./index.php?mod=Map&act=edit&show='+mapname;
	}
}

/**
 * toggleDefaultOption
 *
 * This function checks the value of the field to reset it to the default value
 * which is stored in a "helper field". The default value is inserted when there
 * is no option given in the current object.
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleDefaultOption(sName, bOverrideCurrentValue) {
	var oField = document.getElementById(sName);
	var oFieldDefault = document.getElementById('_'+sName);
	
	if(typeof bOverrideCurrentValue === 'undefined') {
		bOverrideCurrentValue = false;
	}
	
	if(oField && oFieldDefault) {
		// Set option only when the field is emtpy and the default value has a value
		// Added override flag to ignore the current value
		if((bOverrideCurrentValue === true || (bOverrideCurrentValue === false && oField.value === '')) && oFieldDefault.value !== '') {
			// Set value to default value
			oField.value = oFieldDefault.value;
			
			// Visualize the default value
			oField.style.color = '#B0A8B8';
		} else if(oField.value != oFieldDefault.value) {
			// Reset the visualisation
			oField.style.color = '';
		} else if(oField.value == oFieldDefault.value) {
			// Visualize the default value
			oField.style.color = '#B0A8B8';
		}
	}
	
	oFieldDefault = null;
	oField = null;
}

/**
 * validateMainConfigFieldValue(oField)
 *
 * This function checks a config field value for valid format. The check is done
 * by the match regex from validMapConfig array.
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function validateMainConfigFieldValue(oField) {
	var sName = oField.name.split('_');
	return validateValue(sName, oField.value, validMainConfig[sName[0]][sName[1]].match);
}

/**
 * toggleDependingFields
 *
 * This function shows/hides the fields which depend on the changed field
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleDependingFields(formName, name, value) {
	var aFields = document.getElementById(formName).elements;
	
	for(var i = 0, len = aFields.length; i < len; i++) {
		// Filter helper fields
		if(aFields[i].name.charAt(0) !== '_') {
			if(aFields[i].type != 'hidden' && aFields[i].type != 'submit') {
				// Handle different structures of main cfg and map cfg editing
				if(formName == 'edit_config') {
					var sMasterName = name.replace(sTypeName+'_', '');
					var sTypeName = aFields[i].name.split('_')[0];
					var sOptName = aFields[i].name.replace(sTypeName+'_', '');
					var sFieldName = aFields[i].name;
					var oConfig = validMainConfig;
				} else {
					var sMasterName = name;
					var sTypeName = document.getElementById(formName).type.value;
					var sOptName = aFields[i].name;
					var oConfig = validMapConfig;
				}
				
				var sFieldName = aFields[i].name;
				
				// Show option fields when parent field value is equal and hide when 
				// parent field value differs
				if(oConfig[sTypeName][sOptName]['depends_on'] === sMasterName
					 && oConfig[sTypeName][sOptName]['depends_value'] != value) {
					
					document.getElementById(sFieldName).parentNode.parentNode.style.display = 'none';
				} else if(oConfig[sTypeName][sOptName]['depends_on'] === sMasterName
					 && oConfig[sTypeName][sOptName]['depends_value'] == value) {
					
					document.getElementById(sFieldName).parentNode.parentNode.style.display = '';
				}
			}
		}
	}
	
	aFields = null;
}

/**
 * toggleFieldType
 *
 * Changes the field type from select to input and vice versa
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleFieldType(sName, sValue) {
	var bReturn = false;
	var sBaseName;
	var bInputHelper = false;
	
	if(sName.indexOf('_inp_') !== -1) {
		sBaseName = sName.replace('_inp_', '');
		bInputHelper = true;
	} else {
		sBaseName = sName;
	}
	
	// Check if the field should be changed
	// this is toggled on
	// a) Select field set to "Manual Input..." or
	// b) Input helper field set to ""
	if((bInputHelper == false && sValue === lang['manualInput']) || (bInputHelper === true && sValue == '')) {
		var oSelectField = document.getElementById(sBaseName);
		var oInputField = document.getElementById('_inp_' + sBaseName);
		
		if(bInputHelper == false) {
			oSelectField.parentNode.parentNode.style.display = 'none';
			oInputField.parentNode.parentNode.style.display = '';
		} else {
			oSelectField.parentNode.parentNode.style.display = '';
			oInputField.parentNode.parentNode.style.display = 'none';
		}
		
		oInputField = null;
		oSelectField = null;
		
		bReturn = true;
	}
	
	return bReturn;
}
