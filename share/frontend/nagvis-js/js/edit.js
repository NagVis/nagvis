/*****************************************************************************
 *
 * edit.js - Some NagVis edit code
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
 * Changes the handling of the line middle for lines with two parts
 */
function toggleLineMidLock(objectId) {
	getMapObjByDomObjId(objectId).toggleLineMidLock();
}

/**
 * Toggles the mode of the object: editable or not
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleMapObjectLock(objectId) {
	updateNumUnlocked(getMapObjByDomObjId(objectId).toggleLock());
}

/**
 * Toggles the mode of all map objects: editable or not
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleAllMapObjectsLock() {
	var lock = false;
	if(iNumUnlocked > 0)
		lock = true;
		
	for(var i in oMapObjects)
		updateNumUnlocked(oMapObjects[i].toggleLock(lock));

	if(!lock)
	    storeUserOption('unlocked-' + oPageProperties.map_name, '*');
	else
	    storeUserOption('unlocked-' + oPageProperties.map_name, '');
}

/*** Handles the object dragging ***/

var draggingEnabled = true;
var draggingObject = null;
var dragObjectOffset = null;
var dragObjectPos = null;
var dragObjectStartPos = null;
var dragObjectChilds = {};
var dragObjectHandler = null;

function getTarget(event) {
	var target = event.target ? event.target : event.srcElement;
	while(target && target.tagName != 'DIV') {
		target = target.parentNode;
  }
	return target;
}

function getButton(event) {
	if (event.which == null)
		/* IE case */
		return (event.button < 2) ? "LEFT" : ((event.button == 4) ? "MIDDLE" : "RIGHT");
	else
		/* All others */
		return (event.which < 2) ? "LEFT" : ((event.which == 2) ? "MIDDLE" : "RIGHT");
}

function makeDragable(objects, dragStopHandler, dragMoveHandler) {
	var len = objects.length;
	if(len == 0)
		return false;
	
	for(var i = 0; i < len; i++) {
		var o = document.getElementById(objects[i]);
		if(o) {
			addEvent(o, "mousedown", function(e) { dragStart(e, dragMoveHandler); }); 
			addEvent(o, "mouseup",   function(e) { dragStop(e,  dragStopHandler); }); 
			o = null;
		}
	}
	len = null;
}

function dragStart(event, dragHandler) {
	if(!event)
		event = window.event;
	
	var target = getTarget(event);
	var button = getButton(event);
	
	// Skip calls when already dragging or other button than left mouse
	if(draggingObject !== null || button != 'LEFT' || !draggingEnabled)
		return true;
	
	var posx, posy;
	if (event.pageX || event.pageY) {
		posx = event.pageX;
		posy = event.pageY;
	} else if (event.clientX || event.clientY) {
		posx = event.clientX;
		posy = event.clientY;
	}
	
	/*if(event.stopPropagation)
		event.stopPropagation();
	event.cancelBubble = true;*/
	
	draggingObject = target;
	draggingObject.x = draggingObject.offsetLeft;
	draggingObject.y = draggingObject.offsetTop;
	
  // Save relative offset of the mouse 
  dragObjectOffset   = [ posy - draggingObject.offsetTop - getHeaderHeight(), 
                         posx - draggingObject.offsetLeft ];
  dragObjectStartPos = [ draggingObject.offsetTop, draggingObject.offsetLeft ];

	// Assign the handler which is called during object movements
	dragObjectHandler = dragHandler;

	// Save diff coords of relative objects
	var sLabelName = target.id.replace('box_', 'rel_label_');
	var oLabel = document.getElementById(sLabelName);
	if(oLabel) {
		dragObjectChilds[sLabelName] = [ oLabel.offsetTop - draggingObject.offsetTop,
		                                 oLabel.offsetLeft - draggingObject.offsetLeft ];
		oLabel = null;
	}
	sLabelName = null;
	
	// Disable the default events for all the different browsers
	if(event.preventDefault)
		event.preventDefault();
	else
		event.returnValue = false;
	return true;
}

function dragObject(event) {
	if(!event)
		event = window.event;
	
	if(draggingObject === null || !draggingEnabled)
		return true;

	var posx, posy;
	if (event.pageX || event.pageY) {
		posx = event.pageX;
		posy = event.pageY;
	} else if (event.clientX || event.clientY) {
		posx = event.clientX;
		posy = event.clientY;
	}
	
	var newTop  = posy - dragObjectOffset[0] - getHeaderHeight();
	var newLeft = posx - dragObjectOffset[1];

	draggingObject.style.position = 'absolute';
	draggingObject.style.top  = newTop + 'px';
	draggingObject.style.left = newLeft + 'px';
	draggingObject.x = newLeft;
	draggingObject.y = newTop;

	// When this object has a relative coordinated label, then move this too
	moveRelativeObject(draggingObject.id, newTop, newLeft);

	// With pressed CTRL key the icon should be docked
	// This means the object will be positioned relative to that object
	// This code only highlights that object. When the CTRL key is still pressed
	// when dropping the object the currently moved object will be positioned
	// relative to this object.
	if(event.ctrlKey) {
		for(var i in oMapObjects)
			oMapObjects[i].highlight(false);
		var o = getNearestObject(draggingObject.id, newLeft, newTop)
		if(o) {
		    o.highlight(true);
		    o = null;
		}
	}

	// Call the dragging handler when one is ste
	if(dragObjectHandler)
		dragObjectHandler(draggingObject);
	oParent = null;
}

function getNearestObject(id, x, y) {
	var nearest = null;
	var min     = null;
	var dist;

	var obj;
	for(var i in oMapObjects) {
		obj = oMapObjects[i];

		// Skip own object
		if(id.split('-')[0] == obj.conf.object_id)
			continue;

		// FIXME: Also handle lines
		if(obj.conf.view_type !== 'icon')
			continue;

		var objX = obj.parseCoord(obj.conf.x, 'x');
		var objY = obj.parseCoord(obj.conf.y, 'y');
		dist = Math.sqrt(((objX - x) * (objX - x)) + ((objY - y) * (objY - y)));
		if(min === null || dist < min) {
			min     = dist;
			nearest = obj;
		}
	}

	obj     = null;
	min     = null;
	dist    = null;
	return nearest;
}

function moveRelativeObject(parentId, parentTop, parentLeft) {
	var sLabelName = parentId.replace('box_', 'rel_label_');
	if(typeof dragObjectChilds[sLabelName] !== 'undefined') {
		var oLabel = document.getElementById(sLabelName);
		if(oLabel) {
  		oLabel.style.position = 'absolute';
			oLabel.style.top  = (dragObjectChilds[sLabelName][0] + parentTop) + 'px';
			oLabel.style.left = (dragObjectChilds[sLabelName][1] + parentLeft) + 'px';
			oLabel = null;
		}
	}
	sLabelName = null;
}

function dragStop(event, handler) {
	if(draggingObject === null || !draggingEnabled
	   || typeof draggingObject.y == 'undefined' || typeof draggingObject.x == 'undefined')
		return;
	
	// When x or y are negative just return this and make no change
	if(draggingObject.y < 0 || draggingObject.x < 0) {
		draggingObject.style.top  = dragObjectStartPos[0] + 'px';
		draggingObject.style.left = dragObjectStartPos[1] + 'px';
		moveRelativeObject(draggingObject.id, dragObjectStartPos[0], dragObjectStartPos[1])
		draggingObject = null;
		return;
	}

	// Skip when the object has not been moved
	if(draggingObject.y == dragObjectStartPos[0] && draggingObject.x == dragObjectStartPos[1]) {
		draggingObject = null;
		return;
	}

	var oParent = null;
	if(event.ctrlKey) {
		var oParent = getNearestObject(draggingObject.id, draggingObject.x, draggingObject.y)
		oParent.highlight(false);
	}

	if(event.shiftKey)
		oParent = false;

	// Unhighlight all parents when relative
	// This condition is a quick fix to make the dragging code work again in the WUI. Once
	// the WUI is dropped in the future this can be removed
	if(draggingObject.id.indexOf('-') > -1)
	    for(var objectId in getMapObjByDomObjId(draggingObject.id.split('-')[0]).getParentObjectIds())
		getMapObjByDomObjId(objectId).highlight(false);

	handler(draggingObject, oParent);
	
	oParent = null;
	dragObjectHandler = null;
	draggingObject    = null;
}

/**
 * Returns true when currently dragging an object
 */
function dragging() {
	return draggingObject !== null;
}

/** add object code **/

var addObjType  = null,
    addViewType = null,
    addNumLeft  = null,
    addAction   = null,
    addX        = [],
    addY        = [],
    addFollow   = false,
    addShape    = null;

/**
 * Is called once to start the object creation
 */
function addObject(objType, viewType, numLeft, action) {
    addObjType  = objType;
    addViewType = viewType;
    addNumLeft  = numLeft;
    addAction   = action;
    
    if(document.body)
	document.body.style.cursor = 'crosshair';
}

function getEventMousePos(e) {
    var event = !e ? window.event : e;
	
    if (event.pageX || event.pageY) {
	posx = event.pageX;
	posy = event.pageY;
    } else if (event.clientX || event.clientY) {
	posx = event.clientX;
	posy = event.clientY;
    }

    // FIXME: Check the clicked area. Only handle clicks on the map!

    // Substract height of header menu here
    posy -= getHeaderHeight();

    // When a grid is enabled align the dragged object in the nearest grid
    if(oViewProperties.grid_show === 1)
	[ posx, posy ] = coordsToGrid(posx, posy);

    return [ posx, posy ];
}

function addClick(e) {
    if(!adding())
	return;

    var pos = getEventMousePos(e);
    addX.push(pos[0]);
    addY.push(pos[1]);
    addNumLeft -= 1;
    pos = null;
    
    // Draw a line to illustrate the progress of drawing the current line
    if(addViewType === 'line' || addObjType === 'textbox') {
	addShape = new jsGraphics('map');
	
	addShape.setColor('#06B606');
	addShape.setStroke(1);
	
	addFollow = true;
    }

    if(addNumLeft > 0)
	return;

    //
    // If this is reached all object coords have been collected
    //
    
    if(document.body)
	document.body.style.cursor = 'default';
    
    var sUrl = '';
    if(addAction == 'add' || addAction == 'clone') {
	sUrl = oGeneralProperties.path_server + '?mod=Map&act=addModify&do=add'
	       + '&show=' + oPageProperties.map_name
	       + '&type=' + addObjType
	       + '&x=' + addX.join(',')
	       + '&y=' + addY.join(',')
	       + '&viewType=' + addViewType;

	if(addAction == 'clone' && objid !== -1)
	    sUrl += '&clone='+objid;
    } else if(addAction == 'modify' && objid !== -1) {
	sUrl = oGeneralProperties.path_server+'?mod=Map&act=addModify&do=modify'
	       + '&show=' + oPageProperties.map_name
	       + '&type=' + addObjType
	       + '&id=' + objid
	       + '&x=' + addX.join(',')
	       + '&y=' + addY.join(',');
    }

    if(sUrl === '')
	return false;

    // FIXME: Language string
    showFrontendDialog(sUrl, 'Properties');
    sUrl = '';

    addObjType  = null,
    addViewType = null,
    addNumLeft  = null,
    addAction   = null,
    addX        = [],
    addY        = [],
    addFollow   = false,
    addShape    = null;
}

function adding() {
    return addNumLeft !== null;
}

function addFollowing(e) {
    if(!addFollow)
	return;

    var pos = getEventMousePos(e);

    addShape.clear();

    if(addViewType === 'line')
	addShape.drawLine(addX[0], addY[0], pos[0], pos[1]);
    else
	addShape.drawRect(addX[0], addY[0], (pos[0] - addX[0]), (pos[1] - addY[0]));

    addShape.paint();
}

/************************************************
 * Register events
 *************************************************/

// First firefox and the IE
if (window.addEventListener) {
  window.addEventListener("mousemove", function(e) {
    dragObject(e);
    addFollowing(e);
    return false;
  }, false);

  window.addEventListener("click", function(e) {
    addClick(e);
    return false;
  }, false);
} else {
  document.documentElement.onmousemove  = function(e) {
    dragObject(e);
    addFollowing(e);
    return false;
  };

  document.documentElement.onclick = function(e) {
    addClick(e);
    return false;
  };
}



/******************************************************************************
 * Edit code, moved from WUI
 *****************************************************************************/

/**
 * toggleDependingFields
 *
 * This function shows/hides the fields which depend on the changed field
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleDependingFields(formName, name, value) {
	var aFields = document.getElementById(formName).elements;

	var oConfig;
	if(formName == 'edit_config')
	    oConfig = validMainConfig;
	else
	    oConfig = validMapConfig;

	for(var i = 0, len = aFields.length; i < len; i++) {
		// Filter helper fields
		if(aFields[i].name.charAt(0) !== '_') {
			if(aFields[i].type != 'hidden' && aFields[i].type != 'submit') {
				// Handle different structures of main cfg and map cfg editing
				var sMasterName, sTypeName, sOptName, sFieldName;
				if(formName == 'edit_config') {
					sMasterName = name.replace(sTypeName+'_', '');
					sTypeName = aFields[i].name.split('_')[0];
					sOptName = aFields[i].name.replace(sTypeName+'_', '');
					sFieldName = aFields[i].name;
				} else {
					sMasterName = name;
					sTypeName = document.getElementById(formName).type.value;
					sOptName = aFields[i].name;
				}
				
				var sFieldName = aFields[i].name;

				// Show option fields when parent field value is equal and hide when 
				// parent field value differs
				if(oConfig[sTypeName] && oConfig[sTypeName][sOptName]['depends_on'] === sMasterName
					 && oConfig[sTypeName][sOptName]['depends_value'] != value) {
					
					document.getElementById(sFieldName).parentNode.parentNode.style.display = 'none';
					document.getElementById(sFieldName).value = '';
				} else if(oConfig[sTypeName] && oConfig[sTypeName][sOptName]['depends_on'] === sMasterName
					 && oConfig[sTypeName][sOptName]['depends_value'] == value) {
					
					document.getElementById(sFieldName).parentNode.parentNode.style.display = '';
					
					// Toggle the value of the field. If empty or just switched the function will
					// try to display the default value
					toggleDefaultOption(sFieldName);
				} else if(!oConfig[sTypeName]) {
					alert('No data for type: '+sTypeName);
				}

			}
		}
	}
	
	oConfig = null;
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
	sRegex = sRegex.replace(/\/[iugm]*$/, "");
	
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

function getObjects(backend_id, type, field, selected, name1) {
	var oOpt = Object();
	oOpt.field = field;
	oOpt.selected = selected;
	oOpt.type = type;

	if(backend_id === lang['manualInput'])
		return true;

	if(type === 'service'
	   && (typeof name1 === 'undefined' || name1 == ''
	       || name1 === lang['manualInput'])) {
		return true;
	}

	if(typeof name1 === 'undefined')
		name1 = ''
	
	printObjects(getSyncRequest(oGeneralProperties.path_server
	                            +'?mod=Map&act=getObjects&backendid='+backend_id
	                            +'&backendid='+backend_id+'&type='+type+'&name1='+name1), oOpt);
}

function printObjects(aObjects, oOpt) {
	var type = oOpt.type;
	var field = oOpt.field;
	var selected = oOpt.selected;
	var bSelected = false;
	
	var oField = document.getElementById(field);
	
	if(oField.nodeName == "SELECT") {
		// delete old options
		for(var i = oField.length; i >= 0; i--)
			oField.options[i] = null;
		
		if(aObjects && aObjects.length > 0) {
			// fill with new options
			for(i = 0; i < aObjects.length; i++) {
				var oName = '';
				var bSelect = false;
				var bSelectDefault = false;
				
				if(type == "service")
					oName = aObjects[i].name2;
				else
					oName = aObjects[i].name1;
				
				if(selected != '' && oName == selected) {
					bSelectDefault = true;
					bSelect = true;
					bSelected = true;
				}
				
				oField.options[i] = new Option(oName, oName, bSelectDefault, bSelect);
			}
		}
		
		// Give the users the option give manual input
		oField.options[oField.options.length] = new Option(lang['manualInput'], lang['manualInput'], false, false);
	}
	
	// Fallback to input field when configured value could not be selected or
	// the list is empty
	if((selected != '' && !bSelected) || !aObjects || aObjects.length == 0) {
		toggleFieldType(oOpt.field, lang['manualInput']);
		document.getElementById(oOpt.field).value = lang['manualInput'];
		document.getElementById('_inp_'+oOpt.field).value = selected;
	}
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
		var oGrid = document.createElement('div');
		oGrid.setAttribute('id', 'grid');
		document.getElementById('map').appendChild(oGrid);
		oGrid = null;
		
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
		for(var gridX = gridStep; gridX < gridXEnd; gridX = gridX + gridStep) {
			grid.drawLine(gridX, gridYStart, gridX, gridYEnd);
		}
		// Draw horizontal lines
		for(var gridY = gridStep; gridY < gridYEnd; gridY = gridY + gridStep) {
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
		var oMap = document.getElementById('map');
		if(oMap) {
		    var oGrid = document.getElementById('grid')
		    if(oGrid) {
			oMap.removeChild(oGrid);
			oGrid = null;
		    }
		    oMap = null;
		}
	} else {
		oViewProperties.grid_show = 1;
		
		// Add to view
		gridParse();
	}
	
	// Send current option to server component
	var url = oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='
	          + oPageProperties.map_name+'&type=global&id=0&grid_show='+oViewProperties.grid_show;
	
	// Sync ajax request
	var oResult = getSyncRequest(url);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
	}
	
	oResult = null;
}

/**
 * Alligns the current coordinates to the current grid
 */
function coordsToGrid(x, y) {
    x = "" + x;
    y = "" + y;
    if(x.indexOf(',') !== -1) {
        x = x.split(',');
        y = y.split(',');
        for(var i = 0; i < x.length; i++) {
            x[i] = x[i] - (x[i] % oViewProperties.grid_steps);
            y[i] = y[i] - (y[i] % oViewProperties.grid_steps);
        }
        return [ x.join(','), y.join(',') ];
    } else {
        x = +x;
        y = +y;
        var gridMoveX = x - (x % oViewProperties.grid_steps);
        var gridMoveY = y - (y % oViewProperties.grid_steps);
        return [ gridMoveX, gridMoveY ];
    }
}

/**
 * validateMainConfigFieldValue(oField)
 *
 * This function checks a config field value for valid format. The check is done
 * by the match regex from validMainConfig array.
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function validateMainConfigFieldValue(oField, init) {
	var sName;
	var bInputHelper = false;
	var bChanged;

	if(!oField)
		return false;
	
	if(oField.name.indexOf('_inp_') !== -1) {
		sName = oField.name.replace('_inp_', '');
		bInputHelper = true;
	} else {
		sName = oField.name;
	}
	
	// Check if "manual input" was selected in this field. If so: change the field
	// type from select to input
	bChanged = toggleFieldType(oField.name, oField.value);
	
	// Toggle the value of the field. If empty or just switched the function will
	// try to display the default value
	toggleDefaultOption(sName, bChanged);
	
	// Check if some fields depend on this. If so: Add a javacript 
	// event handler function to toggle these fields
	toggleDependingFields("edit_config", sName, oField.value);
	
	// Only validate when field type not changed
	if(!bChanged) {
		// Only validate when not initial parsing
		if(!init) {
        		var aName = sName.split('_');
                        var sSec  = aName.shift();
			return validateValue(sName, oField.value, validMainConfig[sSec][aName.join('_')].match);
		} else {
			return true;
		}
	} else {
		return false;
	}
}

function printLang(sLang,sReplace) {
	if(typeof sLang === 'undefined')
		return '';
	
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
