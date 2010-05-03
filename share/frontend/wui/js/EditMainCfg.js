/*****************************************************************************
 *
 * EditMainCfg.js - Functions for edit main configuration form
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

function validateMainCfgForm() {
	// FIXME: Better validating?
	
	// Everything seems OK
	return true;
}

/**
 * validateMainConfigFieldValue(oField)
 *
 * This function checks a config field value for valid format. The check is done
 * by the match regex from validMainConfig array.
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function validateMainConfigFieldValue(oField) {
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
	toggleDependingFields("edit_config", sName, oField.value);
	
	// Only validate when field type not changed
	if(!bChanged) {
		//FIXME: bFormIsValid = validateValue(sName, oField.value, validMainConfig[document.edit_config.type.value][sName].match);
		bFormIsValid = true;
		
		return bFormIsValid;
	} else {
		return false;
	}
}