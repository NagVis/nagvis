/*****************************************************************************
 *
 * ShapeManagement.js - Functions which are used by the shape management
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
 
// checks that the file the user wants to upload has the .png extension
function checkPng(imageName) {
	if(imageName.substring(imageName.length-3,imageName.length).toLowerCase() != 'png') {
		return false; 
	} else {
		return true;
	}
}

function checkJpg(imageName) {
	if(imageName.substring(imageName.length-3,imageName.length).toLowerCase() != 'jpg') {
		return false; 
	} else {
		return true;
	}
}

function checkGif(imageName) {
	if(imageName.substring(imageName.length-3,imageName.length).toLowerCase() != 'gif') {
		return false; 
	} else {
		return true;
	}
}

var usedInMap = "";
function checkShapeInUse(shapeName,mapOptions) {
	for(var i=0;i<mapOptions.length;i++) {
		for(var a = 0; a < mapOptions[i].usedShapes.length; a++) {
			if(mapOptions[i].usedShapes[a] == shapeName) {
				return true;
			}
		}
		usedInMap =mapOptions[i].mapName;
	}
	
	return false;
}

function check_image_add() {
    if(document.shape_add.shape_image.value.length == 0) {
		alert(printLang(lang['firstMustChoosePngImage'],''));
		
		return false;
	}
	
	if(!checkPng(document.shape_add.shape_image.value) && !checkJpg(document.shape_add.shape_image.value) && !checkGif(document.shape_add.shape_image.value)) {
		alert(printLang(lang['mustChooseValidImageFormat'],''));
		
		return false;
	}
}

function check_image_delete() {
    if(document.shape_delete.shape_image.value == '') {
        alert(printLang(lang['foundNoShapeToDelete'],''));
        return false;
    }

		if(checkShapeInUse(document.shape_delete.shape_image.value, mapOptions)) {
			alert(printLang(lang['shapeInUse'],'MAP~'+usedInMap));
			return false;
		}
    
    if(confirm(printLang(lang['confirmShapeDeletion'],'')) === false) {
        return false;
    }
    
    return true;
}
