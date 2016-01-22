/*****************************************************************************
 *
 * tmpl.default.js - javascript for default header template
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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
 * @author	Lars Michelsen <lm@larsmichelsen.com>
 *
 * Note: I found some of the functions below (ddMenu*) at several places in
 * the net. So I don't know about the real author and could not add any note here.
 * Hope this is ok - if not -> tell me. Anyways, thanks for the code!
 */

function headerDraw() {
    if(typeof(oUserProperties) === 'undefined')
        return;

    if(typeof(oUserProperties.header) !== 'undefined' && oUserProperties.header === false)
        toggleHeader(false);

    addEvent(document, 'mousedown', checkHideMenu);
}

// Is executed when the user clicks somewhere on the map. Checks whether or
// not one of the headers dropdown menus is currently open. Hides it when
// the user clicks out of the menu or takes no action when clicking inside.
function checkHideMenu(event) {
    if (!event)
        event = window.event;

    // Check whether or not a parent has the class "dropdown"
    var target = getTargetRaw(event);
    while (target) {
        if (has_class(target, 'dropdown'))
            return; // found the menu object, no further action
        target = target.parentNode;
    }

    // Did not find the menu object. Close the currently open menu
    ddMenuHide();
}

function toggleHeader(store) {
    var header  = document.getElementById('header');
    var spacer  = document.getElementById('headerspacer');
    var show    = document.getElementById('headershow');
    var state = true;

    // Reset the header height cache
    g_header_height_cache = null;

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

    sidebarUpdatePosition();
    if (g_view)
        g_view.scaleView();

    if(store === true)
        storeUserOption('header', state);

    show   = null;
    spacer = null;
    header = null;
}

// Sets/Updates the state of a map in the header menu
function headerUpdateState(map_conf) {
    // Exit this function on invalid call
    if(map_conf === null || map_conf.length != 1)  {
        eventlog("worker", "warning", "headerUpdateState: Invalid call - maybe broken ajax response");
        return false;
    }

    var map = map_conf[0];

    var side = document.getElementById('side-state-' + map['name']);
    if (side) {
        side.className = 'statediv s' + map['summary_state'];
        side = null;
    }

    head = null;
}

open_menus = [];

// Is called to initialize fetching states for the header/sidebar menu
function headerUpdateStates() {
    for (var i = 0; i < g_map_names.length; i++) {
        call_ajax(oGeneralProperties.path_server+'?mod=Overview&act=getObjectStates'
                  + '&i[]=map-' + escapeUrlValues(g_map_names[i]) + getViewParams(), {
            response_handler: headerUpdateState
        });
    }
}

function ddMenuToggle(event, id) {
    event = event || window.event;
    var some_open = open_menus.length > 0;
    var this_open = false
    for (var i = 0; i < open_menus.length; i++) {
        if (open_menus[i] == id) {
            this_open = true;
            break;
        }
    }

    // In any case close all open menus. When the triggered
    // menu was not open before, then open it up again.
    ddMenuHide();
    if (!this_open)
        ddMenu(id);
    return preventDefaultEvents(event);
}

// Hide the given menus instant
function ddMenuHide(close_menus) {
    if(typeof(close_menus) === 'undefined')
        var close_menus = open_menus.slice(); // copy open menus array

    var h, index;
    for(var i = 0; i < close_menus.length; i++) {
        h = document.getElementById(close_menus[i] + '-ddcontent');
        h.style.display = "none";

        index = open_menus.indexOf(close_menus[i]);
        if (index != -1)
            open_menus.splice(index, 1);
    }

    h = null;
    return false;
}

// main function to handle the mouse events //
function ddMenu(id, reposition) {
    // Hide all other open menus (which have not the id of the current menu in their id)
    for (var i = 0; i < open_menus.length; i++) {
        var other_id = open_menus[i];
        if (id.indexOf('-') === -1) {
            // main level menus (no minus in name) only hide other main level menus
            if (other_id.indexOf(id) !== 0)
                ddMenuHide([other_id]);
        }
        else {
            // sub level menus only hide other sub level menus of their parent menu
            if (id != other_id
                && other_id.indexOf('-') !== -1
                && other_id.indexOf(id.split('-')[0]) === 0) {
                ddMenuHide([other_id]);
            }
        }
    }

    var h = document.getElementById(id + '-ddheader');
    var c = document.getElementById(id + '-ddcontent');

    if (open_menus.indexOf(id) === -1)
        open_menus.push(id);

    // Reposition by trigger object when some given (used on submenus)
    if (reposition === true) {
        c.style.position = 'absolute';
        c.style.left = '204px';
        c.style.top = h.offsetTop + "px";
    }

    c.style.display = 'block';
    return false;
}

// ------------------------------
// Functions for the sidebar menu
// ------------------------------

function toggleSidebar(store) {
    var sidebar = document.getElementById('sidebar');
    var toggle  = document.getElementById('sidetoggle');
    var content = document.getElementById('map');
    var is_overview = false;
    if(content == null) {
        content = document.getElementById('overview');
        is_overview = true;
    }

    // If there is still no content don't execute the main code. So the sidebar
    // will not be available in undefined views like the WUI
    if(content == null)
        return false;

    var state = 1;
    if(sidebarOpen()) {
        sidebar.style.display = 'none';
        if(is_overview === false) {
            content.style.left = '0';
        } else {
            content.style.marginLeft = '0px';
        }
        toggle.firstChild.src = oGeneralProperties.path_images + 'internal/sidebar_open.png';
        state = 0;
    } else {
        // open the sidebar

        ddMenuHide(); // hide all dropdown menus

        sidebar.style.display = 'inline';
        if(is_overview === false) {
            content.style.left = '200px';
        } else {
            content.style.marginLeft = '200px';
        }
        toggle.firstChild.src = oGeneralProperties.path_images + 'internal/sidebar_close.png';

        sidebarUpdatePosition();

        if (oGeneralProperties.header_show_states)
            headerUpdateStates();
    }

    if(store === true)
        storeUserOption('sidebar', state);

    state   = null;
    content = null;
    sidebar = null;
    return false;
}

function sidebarOpen() {
    var o = document.getElementById('sidebar');
    if (!o)
        return false;
    return !(o.style.display  == 'none' || o.style.display == '');
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
    for(var i = 0, len = openNodes.length; i < len; i++) {
        var node = document.getElementById(openNodes[i] + '-childs');
        if(node) {
            node.style.display = 'block';
            add_class(node.parentNode, 'open');
            remove_class(node.parentNode, 'closed');
            node = null;
        }
    }
}

function sidebarUpdatePosition() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar && sidebarOpen())
        sidebar.style.top = getHeaderHeight() + 'px';
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
    var this_id = oTitle.id;
    var state = 1;
    if(oUserProperties.sidebarOpenNodes === '')
        var openNodes = [];
    else
        var openNodes = oUserProperties.sidebarOpenNodes.split(',');

    var oListItem = oTitle.parentNode.parentNode;
    if(oList.style.display == 'none' || oList.style.display == '') {
        // Make the sublist visible
        oList.style.display = 'block';

        // Open the folder
        add_class(oListItem, 'open');
        remove_class(oListItem, 'closed');
    } else {
        // Hide the sublist
        oList.style.display = 'none';

        // Close the folder
        add_class(oListItem, 'closed');
        remove_class(oListItem, 'open');
        state = 0;
    }

    // Loop all currently open nodes to check wether they still exist or not
    // Remove not existing nodes -> cleanup data
    for(var i = openNodes.length; i >= 0; i--) {
        var node = document.getElementById(openNodes[i]);
        if(!node)
            openNodes.splice(i, 1);
    }

    // Is it visible at the moment? Search for the index in openNodes list
    var open = openNodes.indexOf(this_id);

    // When the new state is "closed" remove it from the openNodes list
    // When the node is visible and is not in the list yet, append it
    if(state === 0 && open !== -1) {
        openNodes.splice(open, 1);
    } else if(state === 1 && open === -1)
        openNodes.push(this_id);

    storeUserOption('sidebarOpenNodes', openNodes.join(','));

    open = null;
    openNodes = null;
    oTitle = null;
    oListItem = null;
    oList = null;
}

function sidebarGetListByTitle(title) {
    return title.parentNode.parentNode.getElementsByTagName('ul')[0];
}
