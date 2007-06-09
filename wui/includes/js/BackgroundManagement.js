// checks that the file the user wants to upload has the .png extension
function checkPng(imageName) {
	if(imageName.substring(imageName.length-3,imageName.length).toLowerCase() != 'png') {
		return false; 
	} else {
		return true;
	}
}

function checkImageUsed(imageName,mapOptions) {
	for(var i=0;i<mapOptions.length;i++) {
		if(mapOptions[i].mapImage == imageName) {
			return mapOptions[i].mapName;
		}
	}
	
	return "";
}

function check_image_create() {
	if(document.create_image.image_name.value.length == 0) {
		alert(printLang(lang['mustValueNotSet1'],'ATTRIBUTE~image_name'));
		
		return false;
	}
	if(document.create_image.image_color.value.length == 0) {
		alert(printLang(lang['mustValueNotSet1'],'ATTRIBUTE~image_color'));
		
		return false;
	}
	if(document.create_image.image_width.value.length == 0) {
		alert(printLang(lang['mustValueNotSet1'],'ATTRIBUTE~image_width'));
		
		return false;
	}
	if(document.create_image.image_height.value.length == 0) {
		alert(printLang(lang['mustValueNotSet1'],'ATTRIBUTE~image_height'));
		
		return false;
	}
	
	return true;
	
}

function check_image_add() {
	if(document.new_image.image_file.value.length == 0) {
		alert(printLang(lang['firstMustChoosePngImage'],''));
		
		return false;
	}
	
	if(!checkPng(document.new_image.image_file.value)) {
		alert(printLang(lang['mustChoosePngImage'],''));
		
		return false;
	}
	
	return true;
	
}

function check_image_delete() {
	if(document.image_delete.map_image.value == '') {
		alert(printLang(lang['foundNoBackgroundToDelete'],''));
		return false;
	}
	
	imageUsedBy = checkImageUsed(document.image_delete.map_image.value, window.opener.mapOptions);
	if(imageUsedBy != "") {
		alert(printLang(lang['unableToDeleteBackground'],'MAP~'+imageUsedBy+',IMAGENAME~'+document.image_delete.map_image.value));
		return false;
	}
	
	if(confirm(printLang(lang['confirmBackgroundDeletion'],'')) === false) {
		return false;
	}
	
	return true;
}