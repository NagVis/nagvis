// checks that the file the user wants to upload has the .png extension
function check_png() {
  if(document.new_image.fichier.value.length == 0) {
  	 alert(lang[30]);
	 return false;
  } else {
  	  var ext = document.new_image.fichier.value;
	  ext = ext.substring(ext.length-3,ext.length);
	  ext = ext.toLowerCase();
	  if(ext != 'png') {
	    alert(lang[31]);
	    return false; 
	  } else {
	  	return true;
	  }
  }
}


function check_create_map() {
	if (document.map_create.map_name.value=='') {
		alert(lang[33]);
		return false;
	}
	if (document.map_create.map_name.value.split(" ").length > 1) {
		alert(lang[53]);
		return false;
	}
	if (document.map_create.allowed_users.value=='') {
		alert(lang[34]);
		return false;
	}
	if (document.map_create.allowed_for_config.value=='') {
		alert(lang[48]);
		return false;
	}
	if (document.map_create.map_image.value=='') {
		alert(lang[36]);
		return false;
	}
	
	for(var i=0;i<document.map_rename.map_name.length;i++) {
		if(document.map_rename.map_name.options[i].value == document.map_create.map_name.value) {
			alert(lang[39]);
			return false;
		}
	}
	
	if (confirm(lang[42]) === false) {
		return false;
	}
	
	return true;
}


function is_user_allowed(mapname) {
	username=window.opener.document.myvalues.username.value;
	temp=window.opener.document.myvalues.allowed_users_by_map.value.split("^");

	for(var i=0;i<temp.length;i++) {
		temp2=temp[i].split("=");
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
		alert(lang[37]);
		return false;
	}
	
	if (document.map_rename.map_new_name.value.split(" ").length > 1) {
		alert(lang[53]);
		return false;
	}
	
	if (document.map_rename.map_new_name.value=='') {
		alert(lang[38]);
		return false;
	}
	
	for(var i=0;i<document.map_rename.map_name.length;i++) {
		if(document.map_rename.map_name.options[i].value == document.map_rename.map_new_name.value) {
			alert(lang[39]);
			return false;
		}
	}
	
	if (is_user_allowed(document.map_rename.map_name.value)===false) {
		alert(lang[47]);
		return false;
	}
	
	if (confirm(lang[43]) === false) {
		return false;
	}
	
	return true;
}

var mapname_used_by;
function is_mapname_used(map_name) {
	mapname_used_by="";
	temp=window.opener.document.forms['myvalues'].mapname_by_map.value.split("^");
	for(var i=0;i<temp.length;i++) {
		temp2=temp[i].split("=");
		if(temp2[1]==map_name) {
			mapname_used_by=temp2[0];
			return true;
		}
	}
	return false;
}


function check_map_delete() {
	if (document.map_delete.map_name.value=='') {
		alert(lang[40]);
		return false;
	}
	
	if (is_user_allowed(document.map_delete.map_name.value)===false) {
		alert(lang[47]);
		return false;
	}
	
	if(is_mapname_used(document.map_delete.map_name.value)) {
		mess=new String(lang[46]);
		mess=mess.replace("[MAP]",mapname_used_by);
		mess=mess.replace("[IMAGENAME]",document.map_delete.map_name.value);
		alert(mess);
		return false;
	}
	
	if (confirm(lang[44]) === false) {
		return false;
	}
	
	return true;
}


var image_used_by;
function is_map_image_used(imagename) {
	image_used_by="";
	temp=window.opener.document.forms['myvalues'].image_map_by_map.value.split("^");
	for(var i=0;i<temp.length;i++) {
		temp2=temp[i].split("=");
		if(temp2[1]==imagename) {
			image_used_by=temp2[0];
			return true;
		}

	}
	
	return false;
}


function check_image_delete() {
	if (document.image_delete.map_image.value=='') {
		alert(lang[41]);
		return false;
	}
	
	if(is_map_image_used(document.image_delete.map_image.value)) {
		mess=new String(lang[46]);
		mess=mess.replace("[MAP]",image_used_by);
		mess=mess.replace("[IMAGENAME]",document.image_delete.map_image.value);
		alert(mess);
		return false;
	}
	
	if (confirm(lang[45]) === false) {
		return false;
	}
	
	return true;
}