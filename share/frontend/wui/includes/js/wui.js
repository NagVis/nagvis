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
var coords= '';
var objtype= '';
var follow_mouse=false;
var action_click="";
var myshape = null;
var myshape_background = null;
var myshapex=0;
var myshapey=0;
var objid=0;
var viewType = '';

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
	coords='';
	action_click=action;
	objtype=newtype;
	
	// we init the number of points coordinates we're going to wait for before we display the add object window
	cpt_clicks=nbclicks;
	
	if(document.images['background']) {
		document.images['background'].style.cursor='crosshair';
	}
	
	document.onclick=get_click_pos;
	document.onmousemove=track_mouse;
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
		
		// Start drawing a line
		if(cpt_clicks == 2) {		
			myshape = new jsGraphics("mymap");
			myshapex = posx;
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
		if (follow_mouse) myshape.clear();
		coords=coords.substr(0,coords.length-1);
		
		if(document.images['background']) {
			document.images['background'].style.cursor='default';
		}
		
		follow_mouse=false;
		if(action_click=='add') {
			link = './ajax_handler.php?action=getFormContents&form=addmodify&do=add&map='+mapname+'&type='+objtype+'&coords='+coords+'&viewType='+viewType;
		} else if(action_click=='modify') {
			link = './ajax_handler.php?action=getFormContents&form=addmodify&do=modify&map='+mapname+'&type='+objtype+'&id='+objid+'&coords='+coords;
		}
		
		// FIXME: Title "+get_label('properties')+"
		popupWindow('TITLE', getSyncRequest(link, true, false));
		
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

function saveObjectAfterMoveAndDrop(oObj) {
	// When x or y are negative just return this and make no change
	if(oObj.y < 0 || oObj.x < 0) {
		oObj.moveTo(oObj.oldX, oObj.oldY);
		return;
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
			var objX = document.getElementById('box_'+type+'_'+id).style.left.replace('px', '');
			var objY = document.getElementById('box_'+type+'_'+id).style.top.replace('px', '');
			
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
			y = oObj.y;
		}
		
		url = './ajax_handler.php?action=modifyMapObject&map='+mapname+'&type='+type+'&id='+id+'&label_x='+x+'&label_y='+y;
	} else {
		type = arr[1];
		id = arr[2];
		
		url = './ajax_handler.php?action=modifyMapObject&map='+mapname+'&type='+type+'&id='+id+'&x='+oObj.x+'&y='+oObj.y;
	}
	
	// Sync ajax request
	var oResult = getSyncRequest(url);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
	}
	oResult = null;
}

// This function handles object deletions on maps
function deleteMapObject(oObj) {
	if(confirm(printLang(lang['confirmDelete'],''))) {
		var arr = oObj.id.split('_');
		var map = mapname;
		var type = arr[1];
		var id = arr[2];
		
		// Sync ajax request
		var oResult = getSyncRequest('./ajax_handler.php?action=deleteMapObject&map='+map+'&type='+type+'&id='+id);
		if(oResult && oResult.status != 'OK') {
			alert(oResult.message);
			return false;
		}
		oResult = null;
		
		// FIXME: Reloading the map (Change to simply removing the object)
		document.location.href='./index.php?map='+map;
		
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
function formSubmit(formId, action) {
	var getstr = '';
	
	// Read form contents
	var oForm = document.getElementById(formId);
	
	// Get relevant input elements
	var aFields = oForm.getElementsByTagName('input');
	for (var i = 0, len = aFields.length; i < len; i++) {
		// Filter helper fields
		if(aFields[i].name.charAt(0) !== '_') {
			if (aFields[i].type == "hidden") {
				getstr += aFields[i].name + "=" + escapeUrlValues(aFields[i].value) + "&";
			}
			
			if (aFields[i].type == "text") {
				getstr += aFields[i].name + "=" + escapeUrlValues(aFields[i].value) + "&";
			}
			
			if (aFields[i].type == "checkbox") {
				if (aFields[i].checked) {
					getstr += aFields[i].name + "=" + escapeUrlValues(aFields[i].value) + "&";
				} else {
					getstr += aFields[i].name + "=&";
				}
			}
			
			if (aFields[i].type == "radio") {
				if (aFields[i].checked) {
					getstr += aFields[i].name + "=" + escapeUrlValues(aFields[i].value) + "&";
				}
			}
		}
	}
	
	// Get relevant select elements
	aFields = oForm.getElementsByTagName('select');
	for (var i = 0, len = aFields.length; i < len; i++) {
		// Filter helper fields
		if(aFields[i].name.charAt(0) !== '_') {
			
			// Check if the value of the input helper field should be used
			if(aFields[i].options[aFields[i].selectedIndex].value === lang['manualInput']) {
				getstr += aFields[i].name + "=" + escapeUrlValues(document.getElementById('_inp_'+aFields[i].name).value) + "&";
			} else {
				getstr += aFields[i].name + "=" + escapeUrlValues(aFields[i].options[aFields[i].selectedIndex].value) + "&";
			}
		}
	}
	
	// Submit data
	var oResult = getSyncRequest('./ajax_handler.php?action='+action+'&'+getstr);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
		return false;
	}
	oResult = null;
	
	// Close form
	popupWindowClose();
	
	// FIXME: Reloading the map (Change to reload object)
	document.location.href='./index.php?map='+mapname;
	
	return true;
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
