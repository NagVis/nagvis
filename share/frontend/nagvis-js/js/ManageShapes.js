/*****************************************************************************
 *
 * ManageShapes.js - Functions which are used by the shape management
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

function checkShapeInUse(imageName) {
    var using = getSyncRequest(oGeneralProperties.path_server
                               + '?mod=ManageShapes&act=checkUsed&image='
                               + escapeUrlValues(imageName));
    if(isset(using) && using.length > 0)
        return using.join(', ');
    return '';
}

function check_image_add() {
  if(document.shape_add.image_file.value.length == 0) {
        alert(printLang(lang['firstMustChoosePngImage'],''));
        return false;
    }

    if(!checkPngGifOrJpg(document.shape_add.image_file.value)) {
        alert(printLang(lang['mustChooseValidImageFormat'],''));
        return false;
    }

  if(document.shape_add.image_file.value.indexOf(" ") != -1) {
        alert(printLang(lang['noSpaceAllowedInName'],''));
        return false;
    }
}

function check_image_delete() {
    if(document.shape_delete.image.value == '') {
        alert(printLang(lang['foundNoShapeToDelete'],''));
        return false;
    }

    var using = checkShapeInUse(document.shape_delete.image.value);
    if(using != '') {
        alert(printLang(lang['shapeInUse'], 'MAP~' + using));
        return false;
    }

    if(confirm(printLang(lang['confirmShapeDeletion'],'')) === false) {
        return false;
    }

    return true;
}
