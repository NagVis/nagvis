/*****************************************************************************
 *
 * addmodify.js - Functions which are needed by addmodify page
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

function printObjects(aObjects,oOpt) {
	var type = oOpt.type;
	var field = oOpt.field;
	var selected = oOpt.selected;
	
	var oField = document.getElementById(field);
	
	if(oField.nodeName == "SELECT") {
		// delete old options
		for(i=oField.length; i>=0; i--){
			oField.options[i] = null;
		}
	}
	
	if(aObjects && aObjects.length > 0) {
		var bSelected = false;
		
		// fill with new options
		for(i = 0; i < aObjects.length; i++) {
			var oName = '';
			var bSelect = false;
			var bSelectDefault = false;
			
			if(type == "service") {
				oName = aObjects[i].service_description;
			} else {
				oName = aObjects[i].name;
			}
			
			if(selected != '' && oName == selected) {
				bSelectDefault = true;
				bSelect = true;
				bSelected = true;
			}
			
			oField.options[i] = new Option(oName, oName, bSelectDefault, bSelect);
		}
	}
	
	// Fallback to input field when configured value could not be selected or
	// the list is empty
	if((selected != '' && !bSelected) || !aObjects || aObjects.length <= 0) {
		oField.parentNode.innerHTML = '<input id="'+oField.name+'" name="'+oField.name+'" value="" />';
		document.addmodify.elements[field].value=selected;
	}
}

// function that checks the object is valid : all the properties marked with a * (required) have a value
// if the object is valid it writes the list of its properties/values in an invisible field, which will be passed when the form is submitted
function check_object() {
	object_name='';
	line_type='';
	iconset='';
	x='';
	y='';
	
	for(i=0;i<document.addmodify.elements.length;i++) {
		if(document.addmodify.elements[i].type != 'submit' && document.addmodify.elements[i].type != 'hidden') {
		
			if(document.addmodify.elements[i].name.substring(document.addmodify.elements[i].name.length-6,document.addmodify.elements[i].name.length)=='_name') {
				object_name=document.addmodify.elements[i].value;
			}
			if(document.addmodify.elements[i].name == 'iconset') {
				iconset=document.addmodify.elements[i].value;
			}
			if(document.addmodify.elements[i].name == 'x') {
				x=document.addmodify.elements[i].value;
			}			
			if(document.addmodify.elements[i].name == 'y') {
				y=document.addmodify.elements[i].value;
			}
			
			if(document.addmodify.elements[i].name == 'allowed_for_config') {
				users_tab=document.addmodify.elements[i].value.split(',');
				suicide=true;
				for(k=0;k<users_tab.length;k++) {
					if ( (users_tab[k]=='EVERYONE') || (users_tab[k]==username) ) { suicide=false; }
				}
				if(suicide) {
					alert(printLang(lang['unableToWorkWithMap'],''));
					document.addmodify.elements[i].focus();
					return false;
				}
			}		
			
			if(document.addmodify.elements[i].value != '') {
				// Print a note to the user: This map object will display the summary state of the current map
				if(document.addmodify.type.value == "map" && document.addmodify.elements[i].name == "map_name" && document.addmodify.elements[i].value == document.addmodify.map.value) {
					alert(printLang(lang['mapObjectWillShowSummaryState'],''));
				}
			} else {
				if(validMapConfig[document.addmodify.type.value][document.addmodify.elements[i].name]['must'] == '1') {
					alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~'+document.addmodify.elements[i].name+',TYPE~'+document.addmodify.type.value+',MAPNAME~'+document.addmodify.map.value));
					document.addmodify.elements[i].focus();
					
					return false;
				}
			}
		}
	}
	
	// we make some post tests (concerning the line_type and iconset values)
	if(view_type && view_type == 'line') {
		// we verify that the current line_type is valid
		valid_list=new Array("10","11","20");
		for(j=0;valid_list[j]!=line_type && j<valid_list.length;j++);
		if(j==valid_list.length) {
			alert(printLang(lang['chosenLineTypeNotValid'],''));
			return false;
		}
		
		// we verify we don't have both iconset and line_type defined
		if(iconset != '') {
			alert(printLang(lang['onlyLineOrIcon'],''));
			return false;
		}
		
		// we verify we have 2 x coordinates and 2 y coordinates
		if(x.split(",").length != 2) {
			alert(printLang(lang['not2coordsX'],'COORD~X'));
			return false;
		}
		
		if(y.split(",").length != 2) {
			alert(printLang(lang['not2coordsY'],'COORD~Y'));
			return false;
		}
		
		if(line_type == '') {
			alert(printLang(lang["lineTypeNotSet"]));
			return false;
		}
	}
	
	if(x.split(",").length > 1) {
		if(x.split(",").length != 2) {
			alert(printLang(lang["only1or2coordsX"],'COORD~X'));
			return false;
		} else {
			if(view_type != 'line') {
				alert(printLang(lang["viewTypeWrong"],'COORD~X'));
				return false;
			}
			
			if(line_type == '') {
				alert(printLang(lang["lineTypeNotSet"]));
				return false;
			}
		}
	}
	
	if(y.split(",").length > 1) {
		if(y.split(",").length != 2) {
			alert(printLang(lang["only1or2coordsY"],'COORD~Y'));
			alert(mess);
			return false;
		} else {
			if(view_type != 'line') {
				alert(printLang(lang["viewTypeWrong"],'COORD~Y'));
				return false;
			}
			
			if(line_type == '') {
				alert(printLang(lang["lineTypeNotSet"]));
				return false;
			}
		}
	}
	return true;
}

/**
 * toggleDependingFields
 *
 * This function shows/hides the fields which depend on the changed field
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleDependingFields(name, value) {
	for(var i=0, len=document.addmodify.elements.length;i<len;i++) {
		if(document.addmodify.elements[i].type != 'hidden' && document.addmodify.elements[i].type != 'submit') {
			if(validMapConfig[document.addmodify.type.value][document.addmodify.elements[i].name]['depends_on'] === name
				 && validMapConfig[document.addmodify.type.value][document.addmodify.elements[i].name]['depends_value'] !== value) {
				
				document.getElementById(document.addmodify.elements[i].name).parentNode.parentNode.style.display = 'none';
			} else if(validMapConfig[document.addmodify.type.value][document.addmodify.elements[i].name]['depends_on'] === name
				 && validMapConfig[document.addmodify.type.value][document.addmodify.elements[i].name]['depends_value'] === value) {
				document.getElementById(document.addmodify.elements[i].name).parentNode.parentNode.style.display = '';
			}
		}
	}
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
	// Check if some fields depend on this. If so: Add a javacript 
	// event handler function to toggle these fields
	toggleDependingFields(oField.name, oField.value);
	
	return validateValue(oField.name, oField.value, validMapConfig[document.addmodify.type.value][oField.name].match)
}
