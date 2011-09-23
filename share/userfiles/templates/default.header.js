/*****************************************************************************
 *
 * tmpl.default.js - javascript for default header template
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
 *
 * Note: I found some of the functions below (ddMenu*) at several places in
 * the net. So I don't know about the real author and could not add any note here.
 * Hope this is ok - if not -> tell me. Anyways, thanks for the code!
 */

function headerDraw() {
    scaleView();

    if(typeof(oUserProperties) === 'undefined')
        return;

    if(typeof(oUserProperties.header) !== 'undefined' && oUserProperties.header === false)
    headerToggle(false);
}

function headerToggle(store) {
    var header = document.getElementById('header');
    var spacer = document.getElementById('headerspacer');
    var show   = document.getElementById('headershow');
    var state = true;

    if(header.style.display === '') {
        header.style.display = 'none';
        spacer.style.display = 'none';
        show.style.display   = 'block';
        state = false;
    } else {
        header.style.display = '';
        spacer.style.display = '';
        show.style.display   = 'none';
    }

    // Reset the header height cache
    cacheHeaderHeight    = null;

    if(store === true)
        storeUserOption('header', state);

    show   = null;
    spacer = null;
    header = null;
}

// Hide the given menus instant
function ddMenuHide(aIds) {
    var h;
    for(var i = 0; i < aIds.length; i++) {
        h = document.getElementById(aIds[i] + '-ddheader');

        // Not imediately hide the hover menu. It might happen
        // that the user opens a submenu. To prevent strange
        // effects the timer is used to hide the menu after
        // some time if no submenu was opened within this time.
        clearTimeout(h.timer);
        var id = aIds[i];
        h.timer = window.setTimeout(function () {
            document.getElementById(id + '-ddcontent').style.display = "none";
        }, 50);
    }

    h = null;
    return false;
}

// main function to handle the mouse events //
function ddMenu(id, d, reposition){
    var h = document.getElementById(id + '-ddheader');
    var c = document.getElementById(id + '-ddcontent');

    clearTimeout(h.timer);

    // Reposition by trigger object when some given (used on submenus)
    if(typeof reposition !== 'undefined' && d == 1) {
        c.style.display = 'block';
        c.style.position = 'absolute';
        c.style.left = '204px';
        c.style.top = h.offsetTop + "px";
    }

    //clearInterval(c.timer);
    if(d == 1)
        c.style.display = 'block';
    else
        c.style.display = 'none';
    return false;
}

// ------------------------------
// Functions for the sidebar menu
// ------------------------------

function toggleSidebar(store) {
    var sidebar = document.getElementById('sidebar');
    var content = document.getElementById('map');
    if(content == null)
        content = document.getElementById('automap');
    if(content == null)
        content = document.getElementById('overview');
    // If there is still no content don't execute the main code. So the sidebar
    // will not be available in undefined views like the WUI
    if(content == null)
        return false;

    var state = 1;
    if(sidebarOpen()) {
        sidebar.style.display = 'none';
        content.style.left = '0';
        state = 0;
    } else {
        sidebar.style.display = 'inline';
        content.style.left = '200px';
    }

    if(store === true)
        storeUserOption('sidebar', state);

    state   = null;
  content = null;
  sidebar = null;
    return false;
}

function sidebarOpen() {
    display = document.getElementById('sidebar').style.display;
    return !(display  == 'none' || display == '');
}

function getSidebarWidth() {
    if(!sidebarOpen())
        return 0;
    else
        return document.getElementById('sidebar').clientWidth;
}

// Cares about the initial drawing of the sidebar on page load
// Loads the sidebar visibility state from the server
function sidebarDraw() {
    if(typeof(oUserProperties) === 'undefined')
        return;

    if(typeof(oUserProperties.sidebar) !== 'undefined' && oUserProperties.sidebar === 1)
    toggleSidebar(false);

    // Initialize value
    if(typeof(oUserProperties.sidebarOpenNodes) === 'undefined')
        oUserProperties.sidebarOpenNodes = '';

    // If no nodes are open don't try to open some
    if(oUserProperties.sidebarOpenNodes === '')
        return;

    var openNodes = oUserProperties.sidebarOpenNodes.split(',');
    var nodes = document.getElementById('sidebar').getElementsByTagName('ul');
    for(var i = 0, len = openNodes.length; i < len; i++) {
        var node = nodes[openNodes[i]];
        node.style.display = 'block';
        node.parentNode.setAttribute('class', 'open');
        node.parentNode.setAttribute('className', 'open');
        node = null;
    }
}

function sidebarDrawSubtree(node, index) {
    // Check if this node is expanded
    for(var i = 0, len = oUserProperties.sidebarOpenNodes.length; i < len; i++)
        if(oUserProperties.sidebarOpenNodes[i] == index)
            return;

    // Hide sidebar when not in openNodes
    if(node.parentNode != document.getElementById('sidebar'))
        node.style.display = 'none';
}

function sidebarToggleSubtree(oTitle) {
    var oList = sidebarGetListByTitle(oTitle);
    var index = getListNodeIndex(oList);
    var state = 1;
    if(oUserProperties.sidebarOpenNodes === '')
        var openNodes = [];
    else
        var openNodes = oUserProperties.sidebarOpenNodes.split(',');

    if(oList.style.display == 'none' || oList.style.display == '') {
        // Make the sublist visible
        oList.style.display = 'block';

        // Open the folder
        oTitle.parentNode.setAttribute('class', 'open');
        oTitle.parentNode.setAttribute('className', 'open');
    } else {
        // Hide the sublist
        oList.style.display = 'none';

        // Close the folder
        oTitle.parentNode.setAttribute('class', 'closed');
        oTitle.parentNode.setAttribute('className', 'closed');
        state = 0;
    }

    // Is it visible at the moment? Search for the index in openNodes list
    var open = null;
    for(var i = 0, len = openNodes.length; i < len; i++) {
        if(openNodes[i] == index) {
            open = i;
            break;
        }
    }

    // When the new state is "closed" remove it from the openNodes list
    // When the node is visible and is not in the list yet, append it
    if(state === 0 && open !== null) {
        openNodes.splice(open, 1);
    } else if(state === 1 && open === null)
        openNodes.push(index);

    storeUserOption('sidebarOpenNodes', openNodes.join(','));

    open = null;
    openNodes = null;
    oTitle = null;
    oList = null;
}

function getListNodeIndex(oList) {
    var nodes = document.getElementById('sidebar').getElementsByTagName('ul');
    for (var i = 0; i < nodes.length; i++)
        if(nodes[i] == oList)
            return i;
    return -1;
}

function sidebarGetListByTitle(title) {
    return title.parentNode.getElementsByTagName('ul')[0];
}
