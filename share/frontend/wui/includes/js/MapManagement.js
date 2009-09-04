/*****************************************************************************
 *
 * map_management.js - Functions which are used by the map management
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
 
function checkCfg(mapName) {
	if(mapName.substring(mapName.length-3,mapName.length).toLowerCase() != 'cfg') {
		return false; 
	} else {
		return true;
	}
}

function getMapNameByPath(mapPath) {
	// split path
	mapName = mapPath.replace(/\\/g,'/').split('/');
	// get filename with ext
	mapName = mapName[mapName.length-1];
	// replace ext and return
	return mapName.substring(0,mapName.length-4);
}

function checkMapExists(mapName,mapOptions) {
	for(var i=0;i<mapOptions.length;i++) {
		if(mapOptions[i].mapName == mapName) {
			return true;
		}
	}
}

function checkMapLinked(mapName,mapOptions) {
	for(var i=0;i<mapOptions.length;i++) {
		for(var a = 0; a < mapOptions[i].linkedMaps.length; a++) {
			if(mapOptions[i].linkedMaps[a] == mapName) {
				return mapOptions[i].mapName;
			}
		}
	}
	
	return "";
}

function check_create_map() {
	if (document.map_create.map_name.value=='') {
		alert(printLang(lang['chooseMapName'],''));
		return false;
	}
	if (document.map_create.map_name.value.split(" ").length > 1) {
		alert(printLang(lang['noSpaceAllowed'],''));
		return false;
	}
	if (document.map_create.allowed_users.value=='') {
		alert(printLang(lang['minOneUserAccess'],''));
		return false;
	}
	if (document.map_create.allowed_for_config.value=='') {
		alert(printLang(lang['minOneUserWriteAccess'],''));
		return false;
	}
	
	for(var i=0;i<document.map_rename.map_name.length;i++) {
		if(document.map_rename.map_name.options[i].value == document.map_create.map_name.value) {
			alert(printLang(lang['mapAlreadyExists'],''));
			return false;
		}
	}
	
	return true;
}

function check_map_rename() {
	if (document.map_rename.map_name.value=='') {
		alert(printLang(lang['noMapToRename'],''));
		return false;
	}
	
	if (document.map_rename.map_new_name.value.split(" ").length > 1) {
		alert(printLang(lang['noSpaceAllowed'],''));
		return false;
	}
	
	if (document.map_rename.map_new_name.value=='') {
		alert(printLang(lang['noNewNameGiven'],''));
		return false;
	}
	
	for(var i=0;i<document.map_rename.map_name.length;i++) {
		if(document.map_rename.map_name.options[i].value == document.map_rename.map_new_name.value) {
			alert(printLang(lang['mapAlreadyExists'],''));
			return false;
		}
	}
	
	if(!checkUserAllowed(getMapPermissions(document.map_rename.map_name.value, mapOptions,"allowedForConfig"), username)) {
		alert(printLang(lang['noPermissions'],''));
		return false;
	}
	
	if (confirm(printLang(lang['confirmMapRename'],'')) === false) {
		return false;
	}
	
	return true;
}

function check_map_export() {
	if(document.map_export.map_name.value=='') {
		alert(printLang(lang['foundNoMapToExport'],''));
		return false;
	}
	
	// read and write users are allowed to export the map
	if(!checkUserAllowed(getMapPermissions(document.map_export.map_name.value, mapOptions,"allowedUsersOrAllowedForConfig"), username)) {
		alert(printLang(lang['noPermissions'],''));
		return false;
	}
	
	return true;
}

function check_map_import() {
	// check if a file to import is set
	if(document.map_import.map_file.value=='') {
		alert(printLang(lang['foundNoMapToImport'],''));
		return false;
	}
	
	// check the file extention if this is a cfg file
	if(!checkCfg(document.map_import.map_file.value)) {
		alert(printLang(lang['notCfgFile'],''));
		return false;
	}
	
	// check if a map with this name already exists
	if(checkMapExists(getMapNameByPath(document.map_import.map_file.value), mapOptions)) {
		alert(printLang(lang['mapAlreadyExists'],'MAPNAME~'+getMapNameByPath(document.map_import.map_file.value)));
		return false;
	}
	
	return true;
}

function check_map_delete() {
	if(document.map_delete.map_name.value=='') {
		alert(printLang(lang['foundNoMapToDelete'],''));
		return false;
	}
	
	if(!checkUserAllowed(getMapPermissions(document.map_delete.map_name.value,mapOptions,"allowedForConfig"), username)) {
		alert(printLang(lang['noPermissions'],''));
		return false;
	}
	
	var mapWithLink = checkMapLinked(document.map_delete.map_name.value, mapOptions)
	if(mapWithLink != "") {
		alert(printLang(lang['unableToDeleteMap'],'PARENTMAP~'+mapWithLink+',MAP~'+document.map_delete.map_name.value));
		return false;
	}
	
	if (confirm(printLang(lang['confirmMapDeletion'],'')) === false) {
		return false;
	}
	
	return true;
}