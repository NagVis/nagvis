// checks that the file the user wants to upload has the .png extension
function checkPng(imageName) {
	if(imageName.substring(imageName.length-3,imageName.length).toLowerCase() != 'png') {
		return false; 
	} else {
		return true;
	}
}

function check_image_add() {
    if(document.shape_add.shape_image.value.length == 0) {
		alert(printLang(lang['firstMustChoosePngImage'],''));
		
		return false;
	}
	
	if(!checkPng(document.shape_add.shape_image.value)) {
		alert(printLang(lang['mustChoosePngImage'],''));
		
		return false;
	}
}

function check_image_delete() {
    if(document.shape_delete.shape_image.value == '') {
        alert(printLang(lang['foundNoShapeToDelete'],''));
        return false;
    }
    
    if(confirm(printLang(lang['confirmShapeDeletion'],'')) === false) {
        return false;
    }
    
    return true;
}