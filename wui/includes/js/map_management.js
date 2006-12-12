// checks that the file the user wants to upload has the .png extension
function checkPng(imageName) {
	if(imageName.substring(imageName.length-3,imageName.length).toLowerCase() != 'png') {
		return false; 
	} else {
		return true;
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
		alert(lang['firstMustChoosePngImage']);
		
		return false;
	}
	
	if(!checkPng(document.new_image.fichier.value)) {
		alert(lang['mustChoosePngImage']);
		
		return false;
	}
	
	return true;
	
}

function check_create_map() {
	if (document.map_create.map_name.value=='') {
		alert(lang['chooseMapName']);
		return false;
	}
	if (document.map_create.map_name.value.split(" ").length > 1) {
		alert(lang['noSpaceAllowed']);
		return false;
	}
	if (document.map_create.allowed_users.value=='') {
		alert(lang['minOneUserAccess']);
		return false;
	}
	if (document.map_create.allowed_for_config.value=='') {
		alert(lang['minOneUserWriteAccess']);
		return false;
	}
	if (document.map_create.map_image.value=='') {
		alert(lang['mustChooseBackgroundImage']);
		return false;
	}
	
	for(var i=0;i<document.map_rename.map_name.length;i++) {
		if(document.map_rename.map_name.options[i].value == document.map_create.map_name.value) {
			alert(lang['mapAlreadyExists']);
			return false;
		}
	}
	
	if (confirm(lang['confirmNewMap']) === false) {
		return false;
	}
	
	return true;
}

function check_map_rename() {
	if (document.map_rename.map_name.value=='') {
		alert(lang['noMapToRename']);
		return false;
	}
	
	if (document.map_rename.map_new_name.value.split(" ").length > 1) {
		alert(lang['noSpaceAllowed']);
		return false;
	}
	
	if (document.map_rename.map_new_name.value=='') {
		alert(lang['noNewNameGiven']);
		return false;
	}
	
	for(var i=0;i<document.map_rename.map_name.length;i++) {
		if(document.map_rename.map_name.options[i].value == document.map_rename.map_new_name.value) {
			alert(lang['mapAlreadyExists']);
			return false;
		}
	}
	
	if (is_user_allowed(document.map_rename.map_name.value)===false) {
		alert(lang['noPermissions']);
		return false;
	}
	
	if (confirm(lang['confirmMapRename']) === false) {
		return false;
	}
	
	return true;
}

function check_map_delete() {
	if(document.map_delete.map_name.value=='') {
		alert(lang['foundNoMapToDelete']);
		return false;
	}
	
	if(!checkUserAllowed(document.map_delete.map_name.value, window.opener.mapOptions, window.opener.username)) {
		alert(lang['noPermissions']);
		
		return false;
	}
	
	if(checkMapLinked(document.map_delete.map_name.value, window.opener.mapOptions)) {
		mess = new String(lang['unableToDeleteMap']);
		//FIXME: mess = mess.replace("[MAP]",mapname_used_by);
		//FIXME: mess = mess.replace("[IMAGENAME]",document.map_delete.map_name.value);
		
		alert(mess);
		
		return false;
	}
	
	if (confirm(lang['confirmMapDeletion']) === false) {
		return false;
	}
	
	return true;
}


function check_image_delete() {
	if(document.image_delete.map_image.value == '') {
		alert(lang['foundNotBackgroundToDelete']);
		return false;
	}
	
	if(checkImageUsed(document.image_delete.map_image.value, window.opener.mapOptions)) {
		mess = new String(lang['unableToDeleteBackground']);
		//FIXME: mess= mess.replace("[MAP]",imageUsedBy);
		mess = mess.replace("[IMAGE NAME]",document.image_delete.map_image.value);
		
		alert(mess);
		
		return false;
	}
	
	if(confirm(lang['confirmBackgroundDeletion']) === false) {
		return false;
	}
	
	return true;
}