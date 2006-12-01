// checks that the file the user wants to upload has the .png extension
function check_png() {
  if(document.new_image.fichier.value.length == 0) {
  	 alert(lang['firstMustChoosePngImage']);
	 return false;
  } else {
  	  var ext = document.new_image.fichier.value;
	  ext = ext.substring(ext.length-3,ext.length);
	  ext = ext.toLowerCase();
	  if(ext != 'png') {
	    alert(lang['mustChoosePngImage']);
	    return false; 
	  } else {
	  	return true;
	  }
  }
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


function is_user_allowed(mapname) {
	username=window.opener.document.myvalues.username.value;
	temp=window.opener.document.myvalues.allowed_users_by_map.value.split("^");

	for(var i=0;i<temp.length;i++) {
		temp2=temp[i].split("~");
		if(temp2[0]==mapname) {
			temp3=temp2[1].split(",");
			for(var j=0;j<temp3.length;j++) {
				if( (temp3[j]==username) || (temp3[j]=="EVERYONE") ) return true;
			}
			return false;
		}
	}
	return false;
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

var mapname_used_by;
function is_mapname_used(map_name) {
	mapname_used_by="";
	temp=window.opener.document.forms['myvalues'].mapname_by_map.value.split("^");
	for(var i=0;i<temp.length;i++) {
		temp2=temp[i].split("~");
		if(temp2[1]==map_name) {
			mapname_used_by=temp2[0];
			return true;
		}
	}
	return false;
}


function check_map_delete() {
	if (document.map_delete.map_name.value=='') {
		alert(lang['foundNoMapToDelete']);
		return false;
	}
	
	if (is_user_allowed(document.map_delete.map_name.value)===false) {
		alert(lang['noPermissions']);
		return false;
	}
	
	if(is_mapname_used(document.map_delete.map_name.value)) {
		mess=new String(lang['unableToDeleteBackground']);
		mess=mess.replace("[MAP]",mapname_used_by);
		mess=mess.replace("[IMAGENAME]",document.map_delete.map_name.value);
		alert(mess);
		return false;
	}
	
	if (confirm(lang['confirmMapDeletion']) === false) {
		return false;
	}
	
	return true;
}


function check_image_delete() {
	if(document.image_delete.map_image.value=='') {
		alert(lang['foundNotBackgroundToDelete']);
		return false;
	}
	getMapImageInUse(document.image_delete.map_image.value);
	var imageUsedBy = window.opener.document.forms['myvalues'].ajax_data.value;
	if(imageUsedBy.length > 0) {
		mess = new String(lang['unableToDeleteBackground']);
		mess= mess.replace("[MAP]",imageUsedBy);
		mess = mess.replace("[IMAGE NAME]",document.image_delete.map_image.value);
		alert(mess);
		return false;
	}
	
	if (confirm(lang['confirmBackgroundDeletion']) === false) {
		return false;
	}
	
	return true;
}

function printMapImageInUse(aObjects,oOpts) {
	dataField = window.opener.document.forms['myvalues'].ajax_data;
	dataField.value = '';
	for(var i=0;i<aObjects.length;i++) {
		if(i>0) {
			dataField.value = dataField.value+','	
		}
		dataField.value=dataField.value+aObjects[i];
	}
}