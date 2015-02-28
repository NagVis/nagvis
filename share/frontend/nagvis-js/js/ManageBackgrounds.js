/*****************************************************************************
 *
 * BackgroundManagement.js - Functions which are used by the bg management
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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

function checkImageUsed(imageName) {
    var using = getSyncRequest(oGeneralProperties.path_server
                               + '?mod=ManageBackgrounds&act=checkUsed&image='
                               + escapeUrlValues(imageName));
    if(isset(using) && using.length > 0)
        return using.join(', ');
    return '';
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

    if(!checkPngGifOrJpg(document.new_image.image_file.value)) {
        alert(printLang(lang['mustChooseValidImageFormat'],''));
        return false;
    }

    return true;

}

function check_image_delete() {
    if(document.image_delete.map_image.value == '') {
        alert(printLang(lang['foundNoBackgroundToDelete'],''));
        return false;
    }

    var imageUsedBy = checkImageUsed(document.image_delete.map_image.value);
    if(imageUsedBy !== "") {
        alert(printLang(lang['unableToDeleteBackground'],'MAP~'+imageUsedBy+',IMAGENAME~'+document.image_delete.map_image.value));
        return false;
    }

    if(confirm(printLang(lang['confirmBackgroundDeletion'],'')) === false) {
        return false;
    }

    return true;
}
