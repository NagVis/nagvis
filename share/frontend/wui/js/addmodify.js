/*****************************************************************************
 *
 * addmodify.js - Functions which are needed by addmodify page
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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

var bFormIsValid = true;

function printObjects(aObjects,oOpt) {
	var type = oOpt.type;
	var field = oOpt.field;
	var selected = oOpt.selected;
	
	var oField = document.getElementById(field);
	
	if(oField.nodeName == "SELECT") {
		// delete old options
		for(var i = oField.length; i >= 0; i--)
			oField.options[i] = null;
		
		if(aObjects && aObjects.length > 0) {
			var bSelected = false;
			
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
	if((selected != '' && !bSelected) || !aObjects || aObjects.length <= 0) {
		toggleFieldType(oField.name, oField.value)
	}
}

// function that checks the object is valid : all the properties marked with a * (required) have a value
// if the object is valid it writes the list of its properties/values in an invisible field, which will be passed when the form is submitted
function validateMapCfgForm() {
	// Terminate fast when validateMapConfigFieldValue marked the form contents
	// as invalid
	if(bFormIsValid === false) {
		return false;
	}
	
	for(var i=0, len = document.addmodify.elements.length; i < len; i++) {
		if(document.addmodify.elements[i].type != 'submit' && document.addmodify.elements[i].type != 'hidden') {
			var sName = document.addmodify.elements[i].name;
			var sType = document.addmodify.type.value;
			
			// Filter helper fields which contain the default values
			if(sName.charAt(0) === '_' && sName.substring(0, 5) !== '_inp_')
				continue;
			
			// When this is a input helper field get the name of the config var and
			// check wether to use or skip this field:
			// List options can be set by dropdown field with name=<option-name> or
			// by input field with name=_inp_<option-name>. Prefer the dropdown field,
			// if there is a value in the dropdown field don't check the input field.
			var varName = sName;
			if(sName.substring(0, 5) === '_inp_') {
				varName = sName.replace('_inp_', '');
				if(document.getElementById(varName).value != lang['manualInput'] && document.getElementById(varName).value != '')
					continue;
			}
			
			// Skip options which match the default value in the helper field
			// These options have not been changed and don't need to be checked
			var oField = document.getElementById(varName);
			var oFieldDefault = document.getElementById('_'+varName);
			if(oField && oFieldDefault && oField.value === oFieldDefault.value && validMapConfig[sType][varName]['must'] != '1')
				continue;
			oFieldDefault = null;
			oField = null;
			
			if(document.addmodify.elements[i].value != '') {
				// Print a note to the user: This map object will display the summary state of the current map
				if(sType == "map" && varName == "map_name" && document.addmodify.elements[i].value == document.addmodify.map.value)
					alert(printLang(lang['mapObjectWillShowSummaryState'],''));
			} else {
				if(validMapConfig[sType][varName]['must'] == '1') {
					alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~'+varName+',TYPE~'+sType+',MAPNAME~'+document.addmodify.map.value));
					document.addmodify.elements[i].focus();
					
					return false;
				}
			}
		}
	}
	
	// we make some post tests (concerning the line_type and iconset values)
	if((document.addmodify.view_type && document.addmodify.view_type.value == 'line') || document.addmodify.type == 'line') {
		// we verify that the current line_type is valid
		var valid_list = new Array("10","11","12","13","14");
		for(var j = 0, len = valid_list.length; valid_list[j] != document.addmodify.line_type.value && j < len; j++) {
			if(j==valid_list.length) {
				alert(printLang(lang['chosenLineTypeNotValid'],''));
				return false;
			}
		}
		
		// Simply unset iconset when verifying a line
		if(document.addmodify.iconset && document.addmodify.iconset.value != '') {
			document.addmodify.iconset.value = '';
		}
		
		// we verify we have 2 x coordinates and 2 y coordinates
		if(document.addmodify.x && document.addmodify.x.value.split(",").length != 2) {
			alert(printLang(lang['not2coordsX'],'COORD~X'));
			return false;
		}
		
		if(document.addmodify.y && document.addmodify.y.value.split(",").length != 2) {
			alert(printLang(lang['not2coordsY'],'COORD~Y'));
			return false;
		}
		
		if(document.addmodify.line_type && document.addmodify.line_type.value == '') {
			alert(printLang(lang["lineTypeNotSet"], ''));
			document.addmodify.line_type.focus();
			
			return false;
		}
	}
	
	if(document.addmodify.x && document.addmodify.x.value.split(",").length > 1) {
		if(document.addmodify.x.value.split(",").length != 2) {
			alert(printLang(lang["only1or2coordsX"],'COORD~X'));
			return false;
		} else {
			if(document.addmodify.type != 'line' && document.addmodify.view_type && document.addmodify.view_type.value != 'line') {
				alert(printLang(lang["viewTypeWrong"],'COORD~X'));
				return false;
			}
			
			if(document.addmodify.line_type.value == '') {
				alert(printLang(lang["lineTypeNotSet"], ''));
				return false;
			}
		}
	}
	
	if(document.addmodify.y && document.addmodify.y.value.split(",").length > 1) {
		if(document.addmodify.y.value.split(",").length != 2) {
			alert(printLang(lang["only1or2coordsY"],'COORD~Y'));
			return false;
		} else {
			if(document.addmodify.type != 'line' && document.addmodify.view_type && document.addmodify.view_type.value != 'line') {
				alert(printLang(lang["viewTypeWrong"],'COORD~Y'));
				return false;
			}
			
			if(document.addmodify.line_type.value == '') {
				alert(printLang(lang["lineTypeNotSet"], ''));
				return false;
			}
		}
	}
	
	return true;
}

/**
 * validateMapConfigFieldValue(oField)
 *
 * This function checks a config field value for valid format. The check is done
 * by the match regex from validMapConfig array.
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function validateMapConfigFieldValue(oField) {
	var sName;
	var bInputHelper = false;
	var bChanged;
	
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
	toggleDependingFields("addmodify", sName, oField.value);
	
	// Only validate when field type not changed
	if(!bChanged) {
		bFormIsValid = validateValue(sName, oField.value, validMapConfig[document.addmodify.type.value][sName].match);
		
		return bFormIsValid;
	} else {
		return false;
	}
}
