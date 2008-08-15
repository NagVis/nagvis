/*****************************************************************************
 *
 * EditMainCfg.js - Functions for edit main configuration form
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

function validateForm() {
	
	// Everything seems OK. Run update_param to get ready to submit the form
	return update_param();
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
	return validateValue(sName, oField.value, window.opener.validMainConfig[sName[1]][sName[2]].match)
}

// function that builds up the list of parameters/values. There are 2 kinds of parameters values :
//	- the \"normal value\". example : \$param=\"value\";
//	- the other one (value computed with other ones) . example : \$param=\"part1\".\$otherparam;
function update_param() {
	document.edit_config.properties.value='';
	for(i=0;i<document.edit_config.elements.length;i++) {
		if(document.edit_config.elements[i].name.substring(0,5)=='conf_') {
			document.edit_config.properties.value=document.edit_config.properties.value+'^'+document.edit_config.elements[i].name.substring(5,document.edit_config.elements[i].name.length)+'='+document.edit_config.elements[i].value;
		}
	}
	document.edit_config.properties.value=document.edit_config.properties.value.substring(1,document.edit_config.properties.value.length);
	return true;
}
