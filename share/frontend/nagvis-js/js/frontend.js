/*****************************************************************************
 *
 * frontend.js - Functions implementing the new ajax frontend with automatic
 *               worker function etc.
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

// Contains the number of pixels the header menu currently consumes
var g_header_height_cache = null;
// Points to the timer object of the worker timer
var g_worker_id = null;
// Holds the global JS map object (e.g. leaflet js map object)
var g_map = null;
// Holds all map objects
var g_map_objects = null;
// Holds the view management object
var g_view = null;

/**
 * Checks if a view is in maintenance mode and shows a message if
 * e.g. a map is in maintenance mode. The frontend keeps refreshing
 * to check if the mainentance mode has finished
 */
function inMaintenance(displayMsg) {
    if(!isset(displayMsg))
        var displayMsg = true;
    if(oPageProperties && oPageProperties.in_maintenance === '1') {
        hideStatusMessage();
        if(displayMsg)
            frontendMessage({
                'type': 'note',
                'title': 'Maintenance',
                'message': 'The current page is in maintenance mode.<br />Please be patient.'
            }, 'maintenance');
        return true;
    } else {
        return false;
    }
}

/**
 * Returns the current height of the header menu
 */
function getHeaderHeight() {
    // Only gather the header height once.
    if(g_header_height_cache === null) {
        var ret = 0;

        var oHeader = document.getElementById('header');
        if(oHeader) {
            // Only return header height when header is shown
            if(oHeader.style.display != 'none')
                ret = oHeader.clientHeight;
        }

        g_header_height_cache = ret;
    }

    return g_header_height_cache;
}

function logout() {
    call_ajax(oGeneralProperties.path_server+'?mod=Auth&act=logout', {
        response_handler: function(response) {
            if (response)
                window.location.reload();
        },
        decode_json: false
    });
}

/**
 * Submits a form in the frontend using ajax without reloading the page.
 * And simply reprints the response in the currently open window
 */
function submitForm(sUrl, sFormId) {
    var oResult = call_ajax(sUrl, {
        method    : "POST",
        post_data : getFormParams(sFormId, false),
        response_handler : function(oResult) {
            if (oResult && oResult.type) {
                // In case of an error show message and close the window
                frontendMessage(oResult);
                if (typeof popupWindowClose == 'function')
                    popupWindowClose();
            } else {
                popupWindowPutContent(oResult);
            }
        }
    });
}

function updateForm(form) {
    form._update.value = '1';
    form._submit.click();
}

function clearFormValue(id) {
    document.getElementById(id).value = '';
}

function showFrontendDialog(sUrl, sTitle, sWidth) {
    if (typeof sWidth === 'undefined' || sWidth === null)
        sWidth = 450;

    call_ajax(sUrl, {
        response_handler: function(response, data) {
            if (isset(response)) {
                // Store url for maybe later refresh
                response.url = sUrl;

                if(typeof response !== 'undefined' && typeof response.code !== 'undefined') {
                    popupWindow(data.title, response, data.width);
                }
            }
        },
        handler_data: {
            title: sTitle,
            width: sWidth
        }
    });

}

/**
 * searchObjectsKeyCheck()
 *
 * Checks the keys which are entered to the object search field
 * if the user hits enter it searches for the matching object(s)
 *
 * @author  Lars Michelsen <lm@larsmichelsen.com>
 */
function searchObjectsKeyCheck(sMatch, e) {
    var charCode;

    if(e && e.which) {
        charCode = e.which;
    } else if(window.event) {
        e = window.event;
        charCode = e.keyCode;
    }

    // Search on enter key press
    if(charCode == 13) {
        searchObjects(sMatch);
    }
}

/**
 * searchObjects()
 *
 * Searches for matching objects on the map an highlights / focuses
 * the found object(s)
 *
 * @author  Lars Michelsen <lm@larsmichelsen.com>
 */
function searchObjects(sMatch) {
    var aResults = [];
    var bMatch = false;

    // Skip empty searches or searches on non initialized views
    if (sMatch === '' || g_view === null)
        return false;

    // Loop all map objects and search the matching attributes
    var obj;
    for(var i in g_view.objects) {
        obj = g_view.objects[i];
        // Don't search shapes/textboxes/lines
        if(obj.conf.type != 'shape'
           && obj.conf.type != 'textbox'
           && obj.conf.type != 'container'
             && obj.conf.type != 'line') {
            bMatch = false;
            var regex = new RegExp(sMatch, 'g');

            // Current matching attributes:
            // - type
            // - name1
            // - name2

            if(obj.conf.type.search(regex) !== -1)
                bMatch = true;

            if(obj.conf.name.search(regex) !== -1)
                bMatch = true;

            // only search the service_description on service objects
            if(obj.conf.type === 'service'
                   && obj.conf.service_description.search(regex) !== -1)
                bMatch = true;

            regex = null;

            // Found some match?
            if(bMatch === true)
                aResults.push(i);
        }
    }
    obj = null;

    // Actions for the results:
    // When multiple found: highlight all
    // When single found: highlight and focus the object
    for(var i = 0, len = aResults.length; i < len; i++) {
        var objectId = aResults[i];

        // - highlight the object
        if(g_view.objects[objectId].conf.view_type && g_view.objects[objectId].conf.view_type === 'icon') {
            // Detach the handler
            //  Had problems with this. Could not give the index to function:
            //  function() { flashIcon(iIndex, 10); iIndex = null; }
            setTimeout('flashIcon("'+objectId+'", '+oPageProperties.event_highlight_duration+', '+oPageProperties.event_highlight_interval+')', 0);
        } else {
            // FIXME: Atm only flash icons, not lines or gadgets
        }

        // - Scroll to object
        if(len == 1) {
            // Detach the handler
            setTimeout('scrollSlow('+g_view.objects[objectId].parsedX()+', '+g_view.objects[objectId].parsedY()+', 1)', 0);
        }

        objectId = null;
    }
}

function getMapObjByDomObjId(id) {
    try {
        return g_view.objects[id];
    } catch(er) {
        return null;
    }
}

/**
 * Shows the add/modify frontend dialog for the given object
 */
function showAddModifyDialog(mapname, objectId) {
    showFrontendDialog(oGeneralProperties.path_server
                       + '?mod=Map&act=addModify&show='
                       + escapeUrlValues(mapname)
                       + '&object_id=' + escapeUrlValues(objectId), 'Modify Object');

}

/**
 * Shows the dialog to acknowledge host/service problems
 */
function showAckDialog(map_name, objectId) {
    showFrontendDialog(oGeneralProperties.path_server
                       + '?mod=Action&act=acknowledge&map=' + escapeUrlValues(map_name)
                       + '&object_id=' + escapeUrlValues(objectId), 'Acknowledge Problem');
}

// Handles manual map object update triggered by e.g. the context menu
function refreshMapObject(event, objectId, only_state) {
    if (typeof only_state === 'undefined')
        only_state = true;

    // Only append map param if it is a known map
    var sMapPart = '';
    var sMod = '';
    var sAddPart = '';
    if (g_view.type === 'map') {
        sMod = 'Map';
        sMapPart = '&show='+escapeUrlValues(g_view.id);
        sAddPart = getViewParams();
    }
    else if (g_view.type === 'overview') {
        sMod = 'Overview';
        sMapPart = '';
    }

    var ty = only_state ? 'state' : 'full';

    // Start the ajax request to update this single object
    call_ajax(oGeneralProperties.path_server+'?mod=' + sMod + '&act=getObjectStates'
              + sMapPart + '&ty='+ty+'&i[]=' + objectId + sAddPart, {
        response_handler: function(response) {
            g_view.updateObjects(response, only_state);
        }
    });

    // event might be null in case this is not called by context menu,
    // but instead directly when adding/modifying an object
    if (event)
        return preventDefaultEvents(event);
}

/**
 * playSound()
 *
 * Play a sound for an object state
 *
 * @param   Integer  Index in g_view.objects
 * @param   Integer  Iterator for number of left runs
 * @author	Lars Michelsen <lm@larsmichelsen.com>
 */
function playSound(objectId, iNumTimes){
    var sSound = '';

    var id = g_view.objects[objectId].dom_obj.id;

    var oObjIcon = document.getElementById(id+'-icon');
    var oObjIconDiv = document.getElementById(id+'-icondiv');

    var sState = g_view.objects[objectId].conf.summary_state;

    if(oStates[sState] && oStates[sState].sound && oStates[sState].sound !== '') {
        sSound = oStates[sState].sound;
    }

    eventlog("state-change", "debug", "Sound to play: "+sSound);

    if(sSound !== '') {
        // Remove old sound when present
        if(document.getElementById('sound'+sState)) {
            document.body.removeChild(document.getElementById('sound'+sState));
        }

        // Load sound
        var oEmbed = document.createElement('embed');
        oEmbed.setAttribute('id', 'sound'+sState);
        // Relative URL does not work, add full url
        oEmbed.setAttribute('src', window.location.protocol + '//' + window.location.host + ':'
                                                 	+ window.location.port + oGeneralProperties.path_sounds+sSound);
        oEmbed.setAttribute('width', '0');
        oEmbed.setAttribute('height', '0');
        oEmbed.setAttribute('hidden', 'true');
        oEmbed.setAttribute('loop', 'false');
        oEmbed.setAttribute('autostart', 'true');
        oEmbed.setAttribute('enablejavascript', 'true');

        // Add object to body => the sound is played
        oEmbed = document.body.appendChild(oEmbed);
        oEmbed = null;

        iNumTimes = iNumTimes - 1;

        if(iNumTimes > 0) {
            setTimeout(function() { playSound(objectId, iNumTimes); }, 500);
        }
    }

    oObjIcon = null;
    oObjIconDiv = null;
}

/**
 * flashIcon()
 *
 * Highlights an object by show/hide a border around the icon
 *
 * @param   Integer  Index in g_view.objects
 * @param   Integer  Time remaining in miliseconds
 * @param   Integer  Interval in miliseconds
 * @author	Lars Michelsen <lm@larsmichelsen.com>
 */
function flashIcon(objectId, iDuration, iInterval){
    if(isset(g_view.objects[objectId])) {
        g_view.objects[objectId].highlight(!g_view.objects[objectId].bIsFlashing);

        var iDurationNew = iDuration - iInterval;

        // Flash again until timer counted down and the border is hidden
        if(iDurationNew > 0 || (iDurationNew <= 0 && g_view.objects[objectId].bIsFlashing === true))
            setTimeout(function() { flashIcon(objectId, iDurationNew, iInterval); }, iInterval);
    }
}

/**
 * Calculates which zoom factor shal be used to zoom the map
 * to fill the whole screen. To reach this, it must loop
 * all map objects to get the extreme coordinates of top/left
 * and bottom/right. Mixing this with the width/height of the
 * viewport, this function calculates the correct zoom factor.
 */
function set_fill_zoom_factor() {
    var obj, zoom;
    var c_top = null, c_left = null, c_bottom = null, c_right = null;
    var o_top, o_left, o_bottom, o_right;
    for(var i in g_view.objects) {
        obj = g_view.objects[i];
        if (obj && obj.getObjLeft && obj.getObjTop && obj.getObjHeight && obj.getObjWidth) {
            o_top = obj.getObjTop();
            if (c_top === null || o_top < c_top)
                c_top = o_top;

            o_left = obj.getObjLeft();
            if (c_left === null || o_left < c_left)
                c_left = o_left;

            o_bottom = o_top + obj.getObjHeight();
            if (c_bottom === null || o_bottom > c_bottom)
                c_bottom = o_bottom;

            o_right = o_left + obj.getObjWidth();
            if (c_right === null || o_right > c_right)
                c_right = o_right;
        }
    }
    var border = 40; // border per side in px * 2
    var zoom_y = parseInt((pageHeight() - border - getHeaderHeight()) / parseFloat(c_bottom) * 100);
    var zoom_x = parseInt((pageWidth() - border - getSidebarWidth())/ parseFloat(c_right) * 100);
    set_zoom(Math.min(zoom_y, zoom_x));
}

function set_zoom(val) {
    setViewParam('zoom', val);
    if (g_worker_id)
        window.clearTimeout(g_worker_id);
    window.location = makeuri({'zoom': val});
}

function zoom(how) {
    var cur_zoom = getZoomFactor();
    // This is not really correct. Assume 
    if (cur_zoom == 'fill')
        cur_zoom = 100;
    var new_zoom = 100;
    if (how != 0) {
        new_zoom = cur_zoom + how;

        if (new_zoom <= 0 || new_zoom >= 200)
            return;
    }

    if (cur_zoom != new_zoom) {
        set_zoom(new_zoom);
    }
}

function wheel_zoom(event) {
    if (!event)
        event = window.event;

    var delta = 0;

    if (!event.altKey)
        return; // only proceed with pressed ALT

    if (event.wheelDelta) { // IE/Opera.
        delta = event.wheelDelta/120;
    } else if (event.detail) { // firefox
        delta = -event.detail/3;
    }

    if (delta > 0) {
        zoom(delta * 5);
    } else if (delta < 0) {
        zoom(delta * 5);
    }

    return preventDefaultEvents(event);
}

var g_drag_ind = null;

function zoombarDragStart(event) {
    if (!event)
        event = window.event;

    if ((event.which === null && event.button >= 2) || (event.which !== null && event.which >= 2))
        return; // skip clicks with other than left mouse

    g_left_clicked = true;
    g_drag_ind = document.getElementById('zoombar-drag_ind');
}

function zoombarDrag(event) {
    if (!event)
        event = window.event;

    if (g_drag_ind === null)
        return true;

    // Is the mouse still pressed? if not, stop dragging!
    if (!g_left_clicked) {
        zoombarDragStop(event);
        return;
    }

    var top_offset = 62;
    var ind_offset = 3; // height / 2
    var pos = (event.clientY - top_offset);

    if (pos > g_drag_ind.parentNode.clientHeight) {
        pos = g_drag_ind.parentNode.clientHeight;
    } else if (pos < 0) {
        pos = 0;
    }

    g_drag_ind.style.top = (pos - ind_offset) + 'px';
}

function zoombarDragStop(event) {
    if (!event)
        event = window.event;

    if (g_drag_ind === null)
        return true;

    if ((event.which === null && event.button >= 2) || (event.which !== null && event.which >= 2))
        return; // skip clicks with other than left mouse

    g_left_clicked = false;

    // Get the zoom value
    var zoom = getZoomFactor();
    var val = parseInt((100 - (parseInt(g_drag_ind.style.top.replace('px', '')) + 3)) / 100 * 200);
    if (val != zoom) {
        if (val <= 0)
            val = 10;
        set_zoom(val);
    }

    g_drag_ind = null;
    return preventDefaultEvents(event);
}

var g_left_clicked = false;

function mouse_click(event) {
    if (!event)
        event = window.event;

    if ((event.which === null && event.button >= 2) || (event.which !== null && event.which >= 2))
        return; // skip clicks with other than left mouse

    g_left_clicked = true;
}

function mouse_release(event) {
    if (!event)
        event = window.event;

    if ((event.which === null && event.button >= 2) || (event.which !== null && event.which >= 2))
        return; // skip clicks with other than left mouse

    g_left_clicked = false;

    zoombarDragStop(event);
}

function updateZoomIndicator() {
    var zoom = getZoomFactor();
    var ind = document.getElementById('zoombar-drag_ind');

    // zoom is 0 to 200, the bar is 0px to 100px, the
    // indicator has a heihgt of 6px
    ind.style.top = (100 - ((zoom / 200 * 100)) - 3) + 'px';
    ind = null;
}

/**
 * Zoom bar rendering
 */
function renderZoombar() {
    if (getViewParam('zoombar') == 0)
        return;

    var bar = document.createElement('div');
    bar.setAttribute('id', 'zoombar');

    var plus = document.createElement('a');
    plus.setAttribute('id', 'zoombar-plus');
    plus.setAttribute('class', 'plus');
    plus.appendChild(document.createTextNode('+'));
    plus.onclick = function() {
        zoom(10);
    };
    bar.appendChild(plus);

    var drag = document.createElement('div');
    drag.setAttribute('id', 'zoombar-drag');
    drag.setAttribute('class', 'drag');
    bar.appendChild(drag);

    var drag_ind = document.createElement('div');
    drag_ind.setAttribute('id', 'zoombar-drag_ind');
    drag_ind.setAttribute('class', 'drag_ind');
    addEvent(drag_ind, 'mousedown', zoombarDragStart);
    addEvent(drag,     'mousemove', zoombarDrag);
    addEvent(drag,     'mouseup',   zoombarDragStop);
    drag.appendChild(drag_ind);

    var minus = document.createElement('a');
    minus.setAttribute('id', 'zoombar-minus');
    minus.setAttribute('class', 'minus');
    minus.appendChild(document.createTextNode('-'));
    minus.onclick = function() {
        zoom(-10);
    };
    bar.appendChild(minus);

    var norm = document.createElement('a');
    norm.setAttribute('class', 'norm');
    norm.appendChild(document.createTextNode('o'));
    norm.onclick = function() {
        zoom(0);
    };
    bar.appendChild(norm);

    // Register scroll events (mouse wheel)
    var wheel_event = (/Firefox/i.test(navigator.userAgent)) ? "DOMMouseScroll" : "mousewheel";
    addEvent(document, wheel_event, wheel_zoom);
    addEvent(document, 'mousedown', mouse_click);
    addEvent(document, 'mouseup',   mouse_release);

    document.body.appendChild(bar);
    updateZoomIndicator();
}

/**
 * The view parameters are parameters which affect the current view in some
 * way. For example a map can have a header menu enabled but a user might
 * override this setting to disable the header menu by setting the view
 * parameter header_menu=0.
 *
 * It's possible to modify the current view parameters by providing an object
 * as first argument which overrides some attributes of the view parameters.
 *
 * When userParams is set to true, only parameters customized by the user
 * and values different to the default settings are returned. Otherwise the
 * complete set of final parameters is returned.
 */
function getViewParams(update, userParams) {
    if(!isset(userParams))
        userParams = false;

    if(!userParams && isset(oViewProperties) && isset(oViewProperties['params'])) {
        var params = oViewProperties['params'];
    } else if(isset(oViewProperties) && isset(oViewProperties['user_params'])) {
        var params = oViewProperties['user_params'];
    } else if(update) {
        var params = {}
    } else {
        return '';
    }

    // Udate the params before processing url
    if(isset(update)) {
        for(var param in update) {
            params[param] = update[param];
        }
    }

    if(!isset(params))
        return '';

    if (g_map && usesSource('worldmap'))
        params['bbox'] = g_map.getBounds().toBBoxString();

    var sParams = '';
    for(var param in params) {
        if(params[param] != '') {
            sParams += '&' + param + '=' + escapeUrlValues(params[param]);
        }
    }

    return sParams;
}

/**
 * Returns the real and final view parameter value including all sources
 * a) hardcoded values
 * b) global section values
 * c) user profile values
 * d) url values
 * The PHP backend computes all the parameters
 */
function getViewParam(param) {
    if (oViewProperties && isset(oViewProperties['params'])
        && isset(oViewProperties['params'][param]))
        return oViewProperties['params'][param];
    return null;
}

function setViewParam(param, val) {
    oViewProperties['params'][param] = val;
    oViewProperties['user_params'][param] = val;
}

// Returns true if the current view is
// a) a map
// b) uses the given source
function usesSource(source) {
    return oPageProperties
        && oPageProperties.sources
        && oPageProperties.sources.indexOf(source) !== -1;
}

// Checks whether or not the users browser has the needed javascript
// features. If not, an error message is shown and further page processing
// will be terminated.
function hasNeededJSFeatures() {
    var msg = null;
    if (typeof JSON === 'undefined') {
        msg = 'It seems you are using an outdated browser to access NagVis. '
             +'I am sorry, but you will not be able to use NagVis with this '
             +'old browser. Please consider updating. (Missing JSON)';
    }

    if (msg) {
        frontendMessage({
            'type'     : 'error',
            'title'    : 'Error: Outdated Browser',
            'message'  : msg,
            'closable' : false
        });
        return false;
    } else {
        return true;
    }
}

// Does the initial parsing of the pages
function workerInitialize(type, ident) {
    displayStatusMessage('Loading...', 'loading', true);

    // Initialize the view objects. This code should not perform any rendering
    // taks. This is only meant to initialize all needed objects
    switch (type) {
        case 'map':
            if (usesSource('worldmap'))
                g_view = new ViewWorldmap(ident);
            else
                g_view = new ViewMap(ident);
        break;
        case 'overview':
            g_view = new ViewOverview();
        break;
        case 'url':
            g_view = new ViewUrl(ident);
        break;
        default:
            eventlog("worker", "error", "Unknown view type: "+type);
            hideStatusMessage();
            return;
    }
    g_view.init();
}


// Updates the page on a regular base
function workerUpdate(iCount, sType, sIdentifier) {
    eventlog("worker", "debug", "Update (Run-ID: "+iCount+")");
    oWorkerProperties.last_run = iNow;
    g_view.update();
    updateWorkerCounter(); // Update the worker last run time on maps
}

/**
 * This function is the heart of the new NagVis frontend. It's called worker.
 * The worker is being called by setTimeout() every second. This method checks
 * for tasks which need to be performed like:
 * - Countdown the timers
 * - Rotating to other page
 * - Reload page because of configuration file changes
 * - Handling configuration file changes
 * - Get objects which need an update of the state information
 * - Update the state information
 * - Handle state changes
 * After all work it goes to sleep for 1 second and calls itselfs again. These
 * tasks are not performed every run, some every second and some every
 * configured worker_interval. The state information is refreshed like
 * configured in worker_update_object_states.
 *
 * @param   Integer  The iterator for the run id
 * @param   String   The type of the page which is currently displayed
 * @param   String   Optional: Identifier of the page to be displayed
 */
function runWorker(iCount, sType, sIdentifier) {
    // If the iterator is 0 it is the first run of the worker. Its only task is
    // to render the page
    if(iCount === 0) {
        if (!hasNeededJSFeatures())
            return; // sorry!
        workerInitialize(sType, sIdentifier);
    } else {
        /**
         * Do these actions every run (every second) excepting the first run
         */

        iNow = Math.floor(Date.parse(new Date()) / 1000);

        // Countdown the rotation counter
        // Not handled by ajax frontend. Reload the page with the new url
        // If it returns true this means that the page is being changed: Stop the
        // worker.
        if(rotationCountdown() === true) {
            eventlog("worker", "debug", "Worker stopped: Rotate/Refresh detected");
            return false;
        }

        /**
         * Do these actions every X runs (Every worker_interval seconds)
         */
        if(iCount % oWorkerProperties.worker_interval === 0)
            workerUpdate(iCount, sType, sIdentifier);
    }

    // Sleep until next worker run (1 Second)
    g_worker_id = window.setTimeout(function() {
        runWorker((iCount+1), sType, sIdentifier);
    }, 1000);
}
