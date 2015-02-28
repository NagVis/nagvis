/*****************************************************************************
 *
 * frontendHover.js - Implements functions for hover menu functionality
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

var _openHoverMenus = [];
var _hoverTimer = null;

/**
 * Checks if a hover menu is open at the moment
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function hoverOpen() {
    return _openHoverMenus.length > 0;
}

/**
 * Hides all open hover menus
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function hoverHide(id) {
    // Loop all open hover menus
    while(_openHoverMenus.length > 0) {
        _openHoverMenus[0].style.display = 'none';
        _openHoverMenus[0] = null;
        _openHoverMenus.splice(0,1);
    }

    // Remove the hover timer
    if(_hoverTimer !== null) {
        clearTimeout(_hoverTimer);
        _hoverTimer = null;
    }

    var obj = getMapObjByDomObjId(id);
    if(obj) {
        // Change cursor to auto when hiding hover menu
        obj.parsedObject.style.cursor = 'auto';

        obj.hoverX = null;
        obj.hoverY = null;
        obj = null;
    }
}

function hoverShow(x, y, id) {
    // Hide all other hover menus
    hoverHide(id);

    var hoverSpacer = 5;
    var minWidth = 400;

    var obj = getMapObjByDomObjId(id);
    obj.hoverX = x;
    obj.hoverY = y;

    // document.body.scrollTop does not work in IE
    var scrollTop = document.body.scrollTop ? document.body.scrollTop :
    document.documentElement.scrollTop;
    var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft :
    document.documentElement.scrollLeft;

    var hoverMenu = document.getElementById(id+'-hover');

    // Maybe there is no hover menu defined for one object?
    if(hoverMenu === null) {
        eventlog('hover', 'error', 'Found no hover menu with the id "'+id+'-hover"');
        return false;
    }

    // Change cursor to "hand" when displaying hover menu
    obj.parsedObject.style.cursor = 'pointer';
    obj = null;

    // hide the menu first to avoid an "up-then-over" visual effect
    hoverMenu.style.display = 'none';
    hoverMenu.style.left = (x + scrollLeft + hoverSpacer - getSidebarWidth()) + 'px';
    hoverMenu.style.top = (y + scrollTop + hoverSpacer - getHeaderHeight()) + 'px';
    if(isIE) {
        hoverMenu.style.width = '0px';
    } else {
        hoverMenu.style.width = 'auto';
    }
    hoverMenu.style.display = '';

    // Set the width but leave some border at the screens edge
    if(hoverMenu.clientWidth - hoverSpacer > minWidth)
        hoverMenu.style.width = hoverMenu.clientWidth - hoverSpacer + 'px';
    else
        hoverMenu.style.width = minWidth + 'px';

    /**
     * Check if the menu is "in screen" or too large.
     * If there is some need for resize/reposition:
     *  - Try to resize the hover menu at least to the minimum size
     *  - If that is not possible try to reposition the hover menu
     */

    var hoverLeft = parseInt(hoverMenu.style.left.replace('px', ''));
    var screenWidth = pageWidth();
    var hoverPosAndSizeOk = true;
    if(!hoverMenuInScreen(hoverMenu, hoverSpacer)) {
        hoverPosAndSizeOk = false;
        if(tryResize(hoverMenu, hoverSpacer, minWidth))
            hoverPosAndSizeOk = true;
    }

    // Resizing was not enough so try to reposition the menu now
    if(!hoverPosAndSizeOk) {
        // First reposition by real size or by min width
        if(hoverMenu.clientWidth < minWidth) {
            hoverMenu.style.left = (x - minWidth - hoverSpacer + scrollLeft) + 'px';
        } else {
            hoverMenu.style.left = (x - hoverMenu.clientWidth - hoverSpacer + scrollLeft) + 'px';
        }

        if(hoverMenuInScreen(hoverMenu, hoverSpacer)) {
            hoverPosAndSizeOk = true;
        } else {
            // Still not ok. Now try to resize on the right down side of the icon
            if(tryResize(hoverMenu, hoverSpacer, minWidth, true)) {
                hoverPosAndSizeOk = true;
            }
        }
    }

    // And if the hover menu is still not on the screen move it to the left edge
    // and fill the whole screen width
    if(!hoverMenuInScreen(hoverMenu, hoverSpacer)) {
        hoverMenu.style.left = hoverSpacer + scrollLeft + 'px';
        hoverMenu.style.width = pageWidth() - (2*hoverSpacer) + 'px';
    }

    var hoverTop = parseInt(hoverMenu.style.top.replace('px', ''));
    // Only move the menu to the top when the new top will not be
    // out of sight
    if(hoverTop + hoverMenu.clientHeight > pageHeight() && hoverTop - hoverMenu.clientHeight >= 0)
        hoverMenu.style.top = hoverTop - hoverMenu.clientHeight - hoverSpacer - 5 + 'px';
    hoverTop = null;

    // Append to visible menus array
    _openHoverMenus.push(hoverMenu);

    hoverMenu = null;
    return false;
}

function hoverMenuInScreen(hoverMenu, hoverSpacer) {
    var hoverLeft = parseInt(hoverMenu.style.left.replace('px', ''));
    var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft :
    document.documentElement.scrollLeft;

    if(hoverLeft < scrollLeft) {
        //alert('left border is out of viewport');
        return false;
    }

    // The most right px of the hover menu
    var hoverRight = hoverLeft + hoverMenu.clientWidth - scrollLeft;
    // The most right px of the viewport
    var viewRight  = pageWidth();

    if(hoverRight > viewRight) {
        //alert('right border is out of viewport');
        return false;
    }

    // There is not enough spacing at the left viewport border
    if(hoverLeft - hoverSpacer < 0) {
        // alert('not enough spacing at left viewport border');
        return false;
    }

    scrollLeft = null;
    hoverLeft = null;
    hoverMenu = null;
    return true;
}

function tryResize(hoverMenu, hoverSpacer, minWidth, rightSide) {
    if(!isset(rightSide))
        var reposition = false;

    var hoverLeft = parseInt(hoverMenu.style.left.replace('px', ''));

    if(rightSide)
        var overhead = hoverLeft + hoverMenu.clientWidth + hoverSpacer - pageWidth();
    else
        var overhead = hoverLeft;
    var widthAfterResize = hoverMenu.clientWidth - overhead;

    // If width is larger than minWidth resize it
    if(widthAfterResize > minWidth) {
        hoverMenu.style.width = widthAfterResize + 'px';

        if(rightSide) {
            if(overhead < 0)
                overhead *= -1
            hoverMenu.style.left = (hoverLeft + overhead) + 'px';
        }

        return true;
    } else {
        return false;
    }
    hoverLeft = null;
    overhead = null;
    widthAfterResize = null;
  hoverMenu = null;
    reposition = null;
}
