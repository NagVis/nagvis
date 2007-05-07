// checks that the file the user wants to upload has the .png extension
function checkPng(imageName) {
	if(imageName.substring(imageName.length-3,imageName.length).toLowerCase() != 'png') {
		return false; 
	} else {
		return true;
	}
}

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
				return true;
			}
		}
	}
	
	return false;
}

function checkImageUsed(imageName,mapOptions) {
	for(var i=0;i<mapOptions.length;i++) {
		if(mapOptions[i].mapImage == imageName) {
			return true;
		}
	}
	
	return false;
}

function check_image_add() {
	if(document.new_image.fichier.value.length == 0) {
		alert(printLang(lang['firstMustChoosePngImage'],''));
		
		return false;
	}
	
	if(!checkPng(document.new_image.fichier.value)) {
		alert(printLang(lang['mustChoosePngImage'],''));
		
		return false;
	}
	
	return true;
	
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
	if (document.map_create.map_image.value=='') {
		alert(printLang(lang['mustChooseBackgroundImage'],''));
		return false;
	}
	
	for(var i=0;i<document.map_rename.map_name.length;i++) {
		if(document.map_rename.map_name.options[i].value == document.map_create.map_name.value) {
			alert(printLang(lang['mapAlreadyExists'],''));
			return false;
		}
	}
	
	if (confirm(printLang(lang['confirmNewMap'],'')) === false) {
		return false;
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
	
	if(!checkUserAllowed(getMapPermissions(document.map_rename.map_name.value,window.opener.mapOptions,"allowedForConfig"), window.opener.username)) {
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
	if(!checkUserAllowed(getMapPermissions(document.map_export.map_name.value,window.opener.mapOptions,"allowedUsersOrAllowedForConfig"), window.opener.username)) {
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
	if(checkMapExists(getMapNameByPath(document.map_import.map_file.value), window.opener.mapOptions)) {
		alert(printLang(lang['mapAlreadyExists'],'MAPNAME~'+getMapNameByPath(document.map_import.map_file.value)));
		return false;
	}
	
	return false;
}

function check_map_delete() {
	if(document.map_delete.map_name.value=='') {
		alert(printLang(lang['foundNoMapToDelete'],''));
		return false;
	}
	
	if(!checkUserAllowed(getMapPermissions(document.map_delete.map_name.value,window.opener.mapOptions,"allowedForConfig"), window.opener.username)) {
		alert(printLang(lang['noPermissions'],''));
		return false;
	}
	
	if(checkMapLinked(document.map_delete.map_name.value, window.opener.mapOptions)) {
		alert(printLang(lang['unableToDeleteMap'],'MAP~'+mapname_used_by+',IMAGENAME~'+document.map_delete.map_name.value));
		return false;
	}
	
	if (confirm(printLang(lang['confirmMapDeletion'],'')) === false) {
		return false;
	}
	
	return true;
}


function check_image_delete() {
	if(document.image_delete.map_image.value == '') {
		alert(printLang(lang['foundNoBackgroundToDelete'],''));
		return false;
	}
	
	if(checkImageUsed(document.image_delete.map_image.value, window.opener.mapOptions)) {
		alert(printLang(lang['unableToDeleteBackground'],'MAP~'+imageUsedBy+',IMAGENAME~'+document.image_delete.map_image.value));
		return false;
	}
	
	if(confirm(printLang(lang['confirmBackgroundDeletion'],'')) === false) {
		return false;
	}
	
	return true;
}