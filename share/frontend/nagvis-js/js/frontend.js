/*****************************************************************************
 *
 * frontend.js - Functions implementing the new ajax frontend with automatic
 *               worker function etc.
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

/**
 * Definition of needed variables
 */
var oHoverTemplates = {};
var oHoverTemplatesChild = {};
var oHoverUrls = {};
var oContextTemplates = {};
// This is turned to true when the map is currently reparsing (e.g. due to
// a changed map config file). This blocks object updates.
var bBlockUpdates = false;
// This is turned to true when at least one object on the map is unlocked
// for editing. When set to true this flag prevents map reloading by changed
// map config files
var iNumUnlocked = false;
var cacheHeaderHeight = null;
var workerTimeoutID   = null;

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
        if(displayMsg && !frontendMessageActive())
            frontendMessage({'type': 'NOTE', 'title': 'Maintenance', 'message': 'The current page is in maintenance mode.<br />Please be patient.'});
        return true;
    } else {
        return false;
    }
}

/**
 * Returns the current height of the header menu
 */
function getHeaderHeight() {
    // Only gather the heather height once.
    if(cacheHeaderHeight === null) {
        var ret = 0;

        var oHeader = document.getElementById('header');
        if(oHeader) {
            // Only return header height when header is shown
            if(oHeader.style.display != 'none')
                ret = oHeader.clientHeight;
            oHeader = null;
        }

        cacheHeaderHeight = ret;
    }

    return cacheHeaderHeight;
}

/**
 * submitFrontendForm()
 *
 * Submits a form in the frontend using ajax without reloading the page
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function submitFrontendForm(sUrl, sFormId, bReloadOnSuccess) {
    if(typeof bReloadOnSuccess === 'undefined' || bReloadOnSuccess === null) {
        bReloadOnSuccess = '';
    }

    var oResult = postSyncRequest(sUrl, getFormParams(sFormId, true));

    if(oResult && oResult.type) {
        if(oResult.type === 'ok' && bReloadOnSuccess) {
            if(typeof popupWindowRefresh == 'function') {
                popupWindowRefresh();
            }
        } else {
            // Show message and close the window
            frontendMessage(oResult);

            // Additionally close the popup window when the response is positive
            if(typeof popupWindowClose == 'function') {
                popupWindowClose();
            }
        }
    }

    oResult = null;
}

/**
 * submitFrontendForm2()
 *
 * Submits a form in the frontend using ajax without reloading the page.
 * And simply reprints the response in the currently open window
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function submitFrontendForm2(sUrl, sFormId) {
    var oResult = postSyncRequest(sUrl, getFormParams(sFormId, false));

    if(oResult && oResult.type) {
        // In case of an error show message and close the window
        frontendMessage(oResult);
        if(typeof popupWindowClose == 'function')
            popupWindowClose();
    } else {
        popupWindowPutContent(oResult);
    }
    oResult = null;
}

function updateForm() {
    document.getElementById('_update').value = '1';
    document.getElementById('_submit').click();
}

/**
 * showFrontendDialog()
 *
 * Show a dialog to the user
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function showFrontendDialog(sUrl, sTitle, sWidth) {
    if(typeof sWidth === 'undefined' || sWidth === null) {
        sWidth = 350;
    }

    var oContent = getSyncRequest(sUrl, false, false);
  if(isset(oContent)) {
        // Store url for maybe later refresh
        oContent.url = sUrl;

        if(typeof oContent !== 'undefined' && typeof oContent.code !== 'undefined') {
            popupWindow(sTitle, oContent, true, sWidth);
        }

        oContent = null;
    }
}

/**
 * searchObjectsKeyCheck()
 *
 * Checks the keys which are entered to the object search field
 * if the user hits enter it searches for the matching object(s)
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
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
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function searchObjects(sMatch) {
    var aResults = [];
    var bMatch = false;

    // Skip empty searches
    if(sMatch == '')
        return false;

    // Loop all map objects and search the matching attributes
    var obj;
    for(var i in oMapObjects) {
        obj = oMapObjects[i];
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
        if(oMapObjects[objectId].conf.view_type && oMapObjects[objectId].conf.view_type === 'icon') {
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
            setTimeout('scrollSlow('+oMapObjects[objectId].parsedX()+', '+oMapObjects[objectId].parsedY()+', 1)', 0);
        }

        objectId = null;
    }
}

/**
 * getObjectsToUpdate()
 *
 * Detects objects with deprecated state information
 *
 * @return  Array    The array of oMapObjects indexes which need an update
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getObjectsToUpdate() {
    eventlog("worker", "debug", "getObjectsToUpdate: Start");
    var arrReturn = [];

    // Assign all object which need an update indexes to return Array
    for(var i in oMapObjects) {
        if(oMapObjects[i].lastUpdate <= iNow - oWorkerProperties.worker_update_object_states) {
            // Do not update objects where enable_refresh=0
            if(oMapObjects[i].conf.type !== 'textbox' 
               && oMapObjects[i].conf.type !== 'shape'
               && oMapObjects[i].conf.type !== 'container') {
                arrReturn.push(i);
            } else if(oMapObjects[i].conf.enable_refresh && oMapObjects[i].conf.enable_refresh == '1') {
                arrReturn.push(i);
            }
        }
    }

    // Now spread the objects in the available timeslots
    var iNumTimeslots = Math.ceil(oWorkerProperties.worker_update_object_states / oWorkerProperties.worker_interval);
    var iNumObjectsPerTimeslot = Math.ceil(oLength(oMapObjects) / iNumTimeslots);
    eventlog("worker", "debug", "Number of timeslots: "+iNumTimeslots+" Number of Objects per Slot: "+iNumObjectsPerTimeslot);

    // Only spread when the number of objects is larger than the objects for each
    // timeslot
    if(arrReturn.length > iNumObjectsPerTimeslot) {
        eventlog("worker", "debug", "Spreading map objects in timeslots");

        // Sort the objects by age and get the oldest objects first
        arrReturn.sort(function(id_1, id_2) {
            return oMapObjects[id_1].lastUpdate > oMapObjects[id_2].lastUpdate;
        });

        // Just remove all elements from the end of the array
        arrReturn = arrReturn.slice(0, iNumObjectsPerTimeslot);
    }

    eventlog("worker", "debug", "getObjectsToUpdate: Have to update "+arrReturn.length+" objects");
    return arrReturn;
}

function getFileAgeParams(viewType, mapName) {
    if(!isset(viewType))
        var viewType = oPageProperties.view_type;
    if(!isset(mapName))
        var mapName = oPageProperties.map_name;

    var addParams = '';
    if(viewType === 'map' && mapName !== false)
        addParams = '&f[]=map,' + mapName + ',' + oFileAges[mapName];
    return '&f[]=maincfg,maincfg,' + oFileAges['maincfg'] + addParams;
}

/**
 * Gets the code for needed hover templates and saves it for later use in icons
 */
function getHoverUrls() {
    var aUrlParts = [];

    // Loop all map objects to get the urls which need to be fetched
    for(var i in oMapObjects) {
        if(oMapObjects[i].conf.hover_menu && oMapObjects[i].conf.hover_menu == 1
           && oMapObjects[i].conf.hover_url && oMapObjects[i].conf.hover_url !== '') {
            oHoverUrls[oMapObjects[i].conf.hover_url] = '';
        }
    }

    // Build string for bulk fetching the templates
    for(var i in oHoverUrls)
        if(i != 'Inherits')
            aUrlParts.push('&url[]='+escapeUrlValues(i));

    // Get the needed templates via bulk request
    var aTemplateObjects = getBulkRequest(oGeneralProperties.path_server+'?mod=General&act=getHoverUrl',
                                          aUrlParts, oWorkerProperties.worker_request_max_length, true);

    // Set the code to global object oHoverUrls
    if(aTemplateObjects.length > 0)
        for(var i = 0, len = aTemplateObjects.length; i < len; i++)
            oHoverUrls[aTemplateObjects[i].url] = aTemplateObjects[i].code;
    aTemplateObjects = null;
}

/**
 * parseHoverMenus()
 *
 * Assigns the hover template code to the object, replaces all macros and
 * adds the menu to all map objects
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseHoverMenus() {
    for(var i in oMapObjects)
        if(oMapObjects[i].needsHoverMenu())
            oMapObjects[i].parseHoverMenu();
}

/**
 * getHoverTemplateChildCode()
 *
 * Extracts the childs code from the hover templates
 *
 * @param   String   The whole template code
 * @return  String   The child part template code
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getHoverTemplateChildCode(sTemplateCode) {
    var regex = getRegEx('loopChild', "<!--\\sBEGIN\\sloop_child\\s-->(.+?)<!--\\sEND\\sloop_child\\s-->");
    var results = regex.exec(sTemplateCode);
    regex = null;

    if(results !== null)
        return results[1];
    else
        return '';
}

/**
 * getHoverTemplates()
 *
 * Gets the code for needed hover templates and saves it for later use in icons
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getHoverTemplates() {
    var aUrlParts = [];

    // Loop all map objects to get the used hover templates
    for(var i in oMapObjects) {
        // Ignore templates of objects which
        // a) have a disabled hover menu
        // b) do not use hover_url
        // c) templates are already fetched
        if(isset(oMapObjects[i].conf.hover_menu) && oMapObjects[i].conf.hover_menu == '1'
           && (!oMapObjects[i].conf.hover_url || oMapObjects[i].conf.hover_url === '')
           && isset(oMapObjects[i].conf.hover_template)
           && (!isset(oHoverTemplates[oMapObjects[i].conf.hover_template]) || oHoverTemplates[oMapObjects[i].conf.hover_template] === '')) {
            oHoverTemplates[oMapObjects[i].conf.hover_template] = '';
        }
    }

    // Build string for bulk fetching the templates
    for(var i in oHoverTemplates)
        if(i !== 'Inherits' && oHoverTemplates[i] === '')
            aUrlParts.push('&name[]='+i);

    if(aUrlParts.length == 0)
        return;

    // Get the needed templates via bulk request
    var aTemplateObjects = getBulkRequest(oGeneralProperties.path_server+'?mod=General&act=getHoverTemplate',
                                          aUrlParts, oWorkerProperties.worker_request_max_length, true);

    // Set the code to global object oHoverTemplates
    if(aTemplateObjects.length > 0)
        for(var i = 0, len = aTemplateObjects.length; i < len; i++) {
            oHoverTemplates[aTemplateObjects[i].name] = aTemplateObjects[i].code;
            oHoverTemplatesChild[aTemplateObjects[i].name] = getHoverTemplateChildCode(aTemplateObjects[i].code);

            // Load css file is one is available
            if(isset(aTemplateObjects[i].css_file)) {
                // This is needed for some old browsers which do no load css files
                // which are included in such fetched html code
                var oLink = document.createElement('link');
                oLink.href = aTemplateObjects[i].css_file
                oLink.rel = 'stylesheet';
                oLink.type = 'text/css';
                document.getElementsByTagName("head")[0].appendChild(oLink);
                oLink = null;
            }
        }
    aTemplateObjects = null;
}

/**
 * getContextTemplates()
 *
 * Gets the code for needed context templates and saves it for later use in icons
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getContextTemplates() {
    var aUrlParts = [];

    // Loop all map objects to get the used templates
    for(var i in oMapObjects)
        // Ignore templates of objects which
        // b) template is already known
        // FIXME: conf.context_menu has inconsistent types (with and without quotes)
        //        fix this and  === can be used here
        if(isset(oMapObjects[i].conf.context_template)
           && (!isset(oContextTemplates[oMapObjects[i].conf.context_template]) || oContextTemplates[oMapObjects[i].conf.context_template] === ''))
            oContextTemplates[oMapObjects[i].conf.context_template] = '';

    // Build string for bulk fetching the templates
    for(var sName in oContextTemplates)
        if(sName !== 'Inherits' && oContextTemplates[sName] === '')
            aUrlParts.push('&name[]='+sName);

    if(aUrlParts.length === 0)
        return;

    // Get the needed templates via bulk request
    var aTemplateObjects = getBulkRequest(oGeneralProperties.path_server+'?mod=General&act=getContextTemplate', aUrlParts, oWorkerProperties.worker_request_max_length, true);

    // Set the code to global object oContextTemplates
    if(aTemplateObjects.length > 0) {
        for(var i = 0, len = aTemplateObjects.length; i < len; i++) {
            oContextTemplates[aTemplateObjects[i].name] = aTemplateObjects[i].code;

            // Load css file is one is available
            if(isset(aTemplateObjects[i].css_file)) {
                // This is needed for some old browsers which do no load css files
                // which are included in such fetched html code
                var oLink = document.createElement('link');
                oLink.href = aTemplateObjects[i].css_file
                oLink.rel = 'stylesheet';
                oLink.type = 'text/css';
                document.getElementsByTagName("head")[0].appendChild(oLink);
                oLink = null;
            }
        }
    }
    aTemplateObjects = null;
}

/**
 * parseContextMenus()
 *
 * Assigns the context template code to the object, replaces all macros and
 * adds the menu to all map objects
 *
 * @param   Object   Array of map objects to parse the context menu for
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseContextMenus() {
    for(var i in oMapObjects)
        if(oMapObjects[i].needsContextMenu())
            oMapObjects[i].parseContextMenu();
}

/**
 * getBackgroundColor()
 *
 * Gets the background color of the map by the summary state of the map
 *
 * @param   Object   Map object
 * @return  String   Hex code representing the color
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getBackgroundColor(oObj) {
    var sColor;

    // When state is PENDING, OK, UP, set default background color
    if(!oObj.summary_state || oObj.summary_state == 'PENDING' || oObj.summary_state == 'OK' || oObj.summary_state == 'UP')
        sColor = oPageProperties.background_color;
    else {
        sColor = oStates[oObj.summary_state].bgcolor;

        // Ack or downtime?
        if(oObj.summary_in_downtime && oObj.summary_in_downtime === 1)
            if(isset(oStates[oObj.summary_state]['downtime_bgcolor']) && oStates[oObj.summary_state]['downtime_bgcolor'] != '')
                sColor = oStates[oObj.summary_state]['downtime_bgcolor'];
            else
                sColor = lightenColor(sColor, 100, 100, 100);
        else if(oObj.summary_problem_has_been_acknowledged && oObj.summary_problem_has_been_acknowledged === 1)
            if(isset(oStates[oObj.summary_state]['ack_bgcolor']) && oStates[oObj.summary_state]['ack_bgcolor'] != '')
                sColor = oStates[oObj.summary_state]['ack_bgcolor'];
            else
                sColor = lightenColor(sColor, 100, 100, 100);
    }

    oObj = null;
    return sColor;
}

/**
 * Gets the favicon of the page representation the state of the map
 *
 * @return	String	Path to the favicon
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function getFaviconImage(oObj) {
    var sFavicon;

    // Gather image on summary state of the object
    if(oObj.summary_in_downtime && oObj.summary_in_downtime === 1)
        sFavicon = 'downtime';
    else if(oObj.summary_problem_has_been_acknowledged && oObj.summary_problem_has_been_acknowledged === 1)
        sFavicon = 'ack';
    else if(oObj.summary_state.toLowerCase() == 'unreachable')
        sFavicon = 'down';
    else
        sFavicon = oObj.summary_state.toLowerCase();

    oObj = null;

    // Set full path
    sFavicon = oGeneralProperties.path_images+'internal/favicon_'+sFavicon+'.png';

    return sFavicon;
}

/**
 * setPageBackgroundColor()
 *
 * Sets the background color of the page
 *
 * @param   String   Hex code
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setPageBackgroundColor(sColor) {
    eventlog("background", "debug", "Setting backgroundcolor to " + sColor);
    eventlog("background", "debug", "Old backgroundcolor: " + document.body.style.backgroundColor);
    document.body.style.backgroundColor = sColor;
    eventlog("background", "debug", "New backgroundcolor: " + document.body.style.backgroundColor);
}

/**
 * setPageFavicon()
 *
 * Sets the favicon of the pages
 *
 * @param   String   Path to the icon image
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setPageFavicon(sFavicon) {
    favicon.change(sFavicon);
}

/**
 * setPagePageTitle()
 *
 * Sets the title of the current page
 *
 * @param   String   Title
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setPageTitle(sTitle) {
    document.title = sTitle;
}

/**
 * updateMapBasics()
 *
 * This function updates the map basics like background, favicon and title with
 * current information
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function updateMapBasics() {
    var show_filter = '';
    if (oPageProperties.map_name !== false)
        show_filter = '&show=' + escapeUrlValues(oPageProperties.map_name)

    // Get new map state from core
    oMapSummaryObj = new NagVisMap(getSyncRequest(oGeneralProperties.path_server
                                   + '?mod=Map&act=getObjectStates&ty=summary'
                                   + show_filter + getViewParams(), false)[0]);

    // FIXME: Add method to refetch oMapSummaryObj when it is null
    // Be tolerant - check if oMapSummaryObj is null or anything unexpected
    if(oMapSummaryObj == null || typeof oMapSummaryObj === 'undefined') {
        eventlog("worker", "debug", "The oMapSummaryObj is null. Maybe a communication problem with the backend");
        return false;
    }

    // Update favicon
    setPageFavicon(getFaviconImage(oMapSummaryObj.conf));

    // Update page title
    setPageTitle(oPageProperties.alias + ' ('
                  + oMapSummaryObj.conf.summary_state + ') :: '
                  + oGeneralProperties.internal_title);

    // Change background color
    if(oPageProperties.event_background && oPageProperties.event_background == '1')
        setPageBackgroundColor(getBackgroundColor(oMapSummaryObj.conf));

    // Update background image for automap
    // FIXME: Maybe update for regular maps?
    //if(oPageProperties.view_type === 'automap')
    //    setMapBackgroundImage(oPageProperties.background_image+iNow);
}

/**
 * Initializes repeated events (if configured to do so) after first event handling
 */
function initRepeatedEvents(objectId) {
    // Are the events configured to be re-raised?
    if(isset(oViewProperties.event_repeat_interval)
       && oViewProperties.event_repeat_interval != 0) {
        oMapObjects[objectId].event_time_first = iNow;
        oMapObjects[objectId].event_time_last  = iNow;
    }
}

/**
 * Is called by the worker function to check all objects for repeated
 * events to be triggered independent of the state updates.
 */
function handleRepeatEvents() {
    eventlog("worker", "debug", "handleRepeatEvents: Start");
    for(var i in oMapObjects) {
        // Trigger repeated event checking/raising for eacht stateful object which
        // has event_time_first set (means there was an event before which is
        // required to be repeated some time)
        if(oMapObjects[i].has_state && oMapObjects[i].event_time_first !== null) {
            checkRepeatEvents(i);
        }
    }
    eventlog("worker", "debug", "handleRepeatEvents: End");
}

/**
 * Checks wether or not repeated events need to be re-raised and re-raises
 * them if the time has come
 */
function checkRepeatEvents(objectId) {
    // Terminate repeated events after the state has changed to OK state
    if(!oMapObjects[objectId].hasProblematicState()) {
        // Terminate, reset vars
        oMapObjects[objectId].event_time_first = null;
        oMapObjects[objectId].event_time_last  = null;
        return;
    }

    // Terminate repeated events after duration has been reached when
    // a limited duration has been configured
    if(oViewProperties.event_repeat_duration != -1 
       && oMapObjects[objectId].event_time_first 
          + oViewProperties.event_repeat_duration < iNow) {
        // Terminate, reset vars
        oMapObjects[objectId].event_time_first = null;
        oMapObjects[objectId].event_time_last  = null;
        return;
    }
    
    // Time for next event interval?
    if(oMapObjects[objectId].event_time_last
       + oViewProperties.event_repeat_interval >= iNow) {
        raiseEvents(objectId, false);
        oMapObjects[objectId].event_time_last = iNow;
    }
}

/**
 * Raise enabled frontend events for the object with the given object id
 */
function raiseEvents(objectId, stateChanged) {
    // - Highlight (Flashing)
    if(oPageProperties.event_highlight === '1') {
        if(oMapObjects[objectId].conf.view_type && oMapObjects[objectId].conf.view_type === 'icon') {
            // Detach the handler
            //  Had problems with this. Could not give the index to function:
            //  function() { flashIcon(objectId, 10); iIndex = null; }
            setTimeout('flashIcon("'+objectId+'", '+oPageProperties.event_highlight_duration+', '+oPageProperties.event_highlight_interval+')', 0);
        } else {
            // FIXME: Atm only flash icons, not lines or gadgets
        }
    }

    // - Scroll to object
    if(oPageProperties.event_scroll === '1') {
        setTimeout('scrollSlow('+oMapObjects[objectId].parsedX()+', '+oMapObjects[objectId].parsedY()+', 1)', 0);
    }

    // - Eventlog
    if(oMapObjects[objectId].conf.type == 'service') {
        if(stateChanged) {
            eventlog("state-change", "info", oMapObjects[objectId].conf.type+" "+oMapObjects[objectId].conf.name+" "+oMapObjects[objectId].conf.service_description+": Old: "+oMapObjects[objectId].last_state.summary_state+"/"+oMapObjects[objectId].last_state.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].last_state.summary_in_downtime+" New: "+oMapObjects[objectId].conf.summary_state+"/"+oMapObjects[objectId].conf.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].conf.summary_in_downtime);
        } else {
            eventlog("state-log", "info", oMapObjects[objectId].conf.type+" "+oMapObjects[objectId].conf.name+" "+oMapObjects[objectId].conf.service_description+": State: "+oMapObjects[objectId].conf.summary_state+"/"+oMapObjects[objectId].conf.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].conf.summary_in_downtime);
        }
    } else {
        if(stateChanged) {
            eventlog("state-change", "info", oMapObjects[objectId].conf.type+" "+oMapObjects[objectId].conf.name+": Old: "+oMapObjects[objectId].last_state.summary_state+"/"+oMapObjects[objectId].last_state.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].last_state.summary_in_downtime+" New: "+oMapObjects[objectId].conf.summary_state+"/"+oMapObjects[objectId].conf.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].conf.summary_in_downtime);
        } else {
            eventlog("state-log", "info", oMapObjects[objectId].conf.type+" "+oMapObjects[objectId].conf.name+": State: "+oMapObjects[objectId].conf.summary_state+"/"+oMapObjects[objectId].conf.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].conf.summary_in_downtime);
        }
    }

    // - Sound
    if(oPageProperties.event_sound === '1') {
        setTimeout('playSound("'+objectId+'", 1)', 0);
    }
}

/**
 * Bulk update map objects using the given object information
 *
 * @param   Array    Array of objects with new information
 * @param   String   Type of the page
 * @return  Boolean  Returns true when some state has changed
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function updateObjects(aMapObjectInformations, sType) {
    var bStateChanged = false;

    // Loop all object which have new information
    for(var i = 0, len = aMapObjectInformations.length; i < len; i++) {
        var objectId = aMapObjectInformations[i].object_id;

        // Object not found
        if(!isset(oMapObjects[objectId])) {
            eventlog("updateObjects", "critical", "Could not find an object with the id "+objectId+" in object array");
            return false;
        }

        // Save old state for later "change detection"
        oMapObjects[objectId].saveLastState();

        // Update this object (loop all options from array and set in current obj)
        for (var strIndex in aMapObjectInformations[i])
            if(aMapObjectInformations[i][strIndex] != 'object_id')
                oMapObjects[objectId].conf[strIndex] = aMapObjectInformations[i][strIndex];

        // Update members list
        oMapObjects[objectId].getMembers();

        // Update lastUpdate timestamp
        oMapObjects[objectId].setLastUpdate();

        // Some objects need to be reloaded even when no state changed (perfdata or
        // output could have changed since last update). Basically this is only
        // needed for gadgets and/or labels with [output] or [perfdata] macros
        if(sType === 'map'
           && !oMapObjects[objectId].stateChanged()
           && oMapObjects[objectId].outputOrPerfdataChanged()) {
            oMapObjects[objectId].parse();
        }

        // Detect state changes and do some actions
        if(isset(oMapObjects) && oMapObjects[objectId].stateChanged()) {

            /* Internal handling */

            // Save for return code
            bStateChanged = true;

            // Reparse the static macros of the hover template.
            // It might happen that some static things like if the host has
            // members or not change during state change. e.g. when a map is
            // removed while viewed via the overview page
            oMapObjects[objectId].hover_template_code = null;

            // Reparse object to map
            if(sType === 'map') {
                oMapObjects[objectId].parse();
            } else if(sType === 'overview') {
                // Reparsing the object on index page.
                // replaceChild seems not to work in all cases so workaround it
                var oOld = oMapObjects[objectId].parsedObject;
                oMapObjects[objectId].parsedObject = oMapObjects[objectId].parsedObject.parentNode.insertBefore(oMapObjects[objectId].parseOverview(), oMapObjects[objectId].parsedObject);
                oMapObjects[objectId].parsedObject.parentNode.removeChild(oOld);

                oOld = null;
            }

            /**
             * Additional eventhandling
             *
             * event_log=1/0
             * event_highlight=1/0
             * event_scroll=1/0
             * event_sound=1/0
             */

            // Only do eventhandling when object state changed to a worse state
            if(oMapObjects[objectId].stateChangedToWorse()) {
                raiseEvents(objectId, true);
                initRepeatedEvents(objectId);
            }
        }

        // Reparse the hover menu
        oMapObjects[objectId].parseHoverMenu();

        // Reparse the context menu
        // The context menu only needs to be reparsed when the
        // icon object has been reparsed
        oMapObjects[objectId].parseContextMenu();
    }

    return bStateChanged;
}

function getMapObjByDomObjId(id) {
    try {
        return oMapObjects[id];
    } catch(er) {
        return null;
    }
}

function updateNumUnlocked(num) {
    iNumUnlocked += num;
    if(iNumUnlocked == 0) {
        // Not in edit mode anymore
        var o = document.getElementById('editIndicator');
        if(o) {
            o.style.display = 'none';
            o = null;
        }

        gridRemove();
    } else {
        // In edit mode (for at least one object)
        var o = document.getElementById('editIndicator');
        if(o) {
            o.style.display = '';
            o = null;
        }

        gridParse();
    }
}

/**
 * Removes an element from the map
 */
function removeMapObject(objectId, msg) {
    if(msg != '' && !confirm(msg))
        return;

    var obj = getMapObjByDomObjId(objectId);

    obj.detachChilds();
    saveObjectRemove(objectId);
    obj.remove();

    if(!obj.bIsLocked)
        updateNumUnlocked(-1);

    obj = null;

    delete oMapObjects[objectId];
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

/**
 * refreshMapObject()
 *
 * Handles manual map object update triggered by e.g. the context menu
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function refreshMapObject(event, objectId) {
    var oObj = getMapObjByDomObjId(objectId);

    var name = oObj.conf.name;
    var obj_id = oObj.conf.object_id;
    var map = oPageProperties.map_name;
    oObj = null;

    // Only append map param if it is a known map
    var sMapPart = '';
    var sMod = '';
    var sAddPart = '';
    if(oPageProperties.view_type === 'map') {
        sMod = 'Map';
        if (map !== false)
            sMapPart = '&show='+escapeUrlValues(map);
        sAddPart = getViewParams();
    } else if(oPageProperties.view_type === 'overview') {
        sMod = 'Overview';
        sMapPart = '';
    }

    // Start the ajax request to update this single object
    getAsyncRequest(oGeneralProperties.path_server+'?mod='
                    + escapeUrlValues(sMod) + '&act=getObjectStates'
                    + sMapPart + '&ty=state&i[]=' + escapeUrlValues(obj_id) + sAddPart, false,
                    getObjectStatesCallback);

    sMod = null;
    sMapPart = null;
    map = null;
    service_description = null;
    obj_id = null;
    name = null;

    var event = !event ? window.event : event;
    if(event) {
        if(event.stopPropagation)
            event.stopPropagation();
        event.cancelBubble = true;
    }
    return false
}

/**
 * Handles object state request answers received from the server
 */
function getObjectStatesCallback(oResponse) {
    var bStateChanged = false;
    if(isset(oResponse) && oResponse.length > 0)
        bStateChanged = updateObjects(oResponse, oPageProperties.view_type);
    oResponse = null;

    // Don't update basics on the overview page
    if(oPageProperties.view_type !== 'overview' && bStateChanged)
        updateMapBasics();
    bStateChanged = null;
}

/**
 * setMapBackgroundImage()
 *
 * Parses the background image to the map
 *
 * @param   String   Path to map images
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapBackgroundImage(sImage) {
    // Only work with the background image if some is configured
    if(typeof sImage !== 'undefined' && sImage !== 'none' && sImage !== '') {
        // Use existing image or create new
        var oImage = document.getElementById('backgroundImage');
        if(!oImage) {
            var oImage = document.createElement('img');
            oImage.id = 'backgroundImage';
            document.getElementById('map').appendChild(oImage);
        }

        addZoomHandler(oImage, true);

        oImage.src = sImage;
        oImage = null;
    }
}

/**
 * setPageBasics()
 *
 * Sets basic information like favicon and page title
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setPageBasics(oProperties) {
    setPageFavicon(oProperties.favicon_image);
    setPageTitle(oProperties.page_title);

    // Set background color. When eventhandling enabled use the state for
    // background color detection
    if(oPageProperties.event_background && oPageProperties.event_background == '1')
        setPageBackgroundColor(getBackgroundColor(oMapSummaryObj.conf));
    else
        setPageBackgroundColor(oProperties.background_color);
}

/**
 * setMapBasics()
 *
 * Sets basic information like background image
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapBasics(oProperties) {
    // Set dynamic page title
    oProperties.page_title = oPageProperties.alias
                                      + ' (' + oMapSummaryObj.conf.summary_state + ') :: '
                                                      + oGeneralProperties.internal_title;
    // Set dynamic favicon image
    oProperties.favicon_image = getFaviconImage(oMapSummaryObj.conf);

    setPageBasics(oProperties);
    setMapBackgroundImage(oProperties.background_image);
}

/**
 * setMapObjects()
 *
 * Does initial parsing of map objects
 *
 * @param   Array    Array of objects to parse to the map
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapObjects(aMapObjectConf) {
    eventlog("worker", "debug", "setMapObjects: Start setting map objects");

    // Don't loop the first object - that is the summary of the current map
    oMapSummaryObj = new NagVisMap(aMapObjectConf[0]);

    for(var i = 1, len = aMapObjectConf.length; i < len; i++) {
        var oObj;

        switch (aMapObjectConf[i].type) {
            case 'host':
                oObj = new NagVisHost(aMapObjectConf[i]);
            break;
            case 'service':
                oObj = new NagVisService(aMapObjectConf[i]);
            break;
            case 'hostgroup':
                oObj = new NagVisHostgroup(aMapObjectConf[i]);
            break;
            case 'servicegroup':
                oObj = new NagVisServicegroup(aMapObjectConf[i]);
            break;
            case 'dyngroup':
                oObj = new NagVisDynGroup(aMapObjectConf[i]);
            break;
            case 'aggr':
                oObj = new NagVisAggr(aMapObjectConf[i]);
            break;
            case 'map':
                oObj = new NagVisMap(aMapObjectConf[i]);
            break;
            case 'textbox':
                oObj = new NagVisTextbox(aMapObjectConf[i]);
            break;
            case 'container':
                oObj = new NagVisContainer(aMapObjectConf[i]);
            break;
            case 'shape':
                oObj = new NagVisShape(aMapObjectConf[i]);
            break;
            case 'line':
                oObj = new NagVisLine(aMapObjectConf[i]);
            break;
            default:
                oObj = null;
                alert('Error: Unknown object type');
            break;
        }

        // Save the number of unlocked objects
        if(!oObj.bIsLocked)
            updateNumUnlocked(1);

        // Save object to map objects array
        if(oObj !== null)
            oMapObjects[oObj.conf.object_id] = oObj;
        oObj = null;
    }

    // First parse the objects on the map
    // Then store the object position dependencies.
    //
    // Before both can be done all objects need to be added
    // to the map objects list
    for(var i in oMapObjects) {
        // Parse object to map
        if(oPageProperties.view_type === 'map') {
            oMapObjects[i].parse();

            // add eventhandling when enabled via event_on_load option
            if(isset(oViewProperties.event_on_load) && oViewProperties.event_on_load == 1
               && oMapObjects[i].has_state
               && oMapObjects[i].hasProblematicState()) {
                raiseEvents(oMapObjects[i].conf.object_id, false);
                initRepeatedEvents(oMapObjects[i].conf.object_id);
            }
        }

        // Store object dependencies
        var parents = oMapObjects[i].getParentObjectIds();
        if(parents) {
            for (var objectId in parents) {
                var refObj = getMapObjByDomObjId(objectId);
                if(refObj)
                    refObj.addChild(oMapObjects[i]);
                refObj = null;
            }
        }
        parents = null;
    }

    eventlog("worker", "debug", "setMapObjects: End setting map objects");
}

/**
 * reloadObjects()
 *
 * Bulk reload, reparse shapes and containers
 *
 * @param   Array    Array of objects to reparse
 */
function reloadObjects(aObjs) {
    for(var i = 0, len = aObjs.length; i < len; i++) {
        oMapObjects[aObjs[i]].parse();
    }
}

/**
 * playSound()
 *
 * Play a sound for an object state
 *
 * @param   Integer  Index in oMapObjects
 * @param   Integer  Iterator for number of left runs
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function playSound(objectId, iNumTimes){
    var sSound = '';

    var id = oMapObjects[objectId].parsedObject.id;

    var oObjIcon = document.getElementById(id+'-icon');
    var oObjIconDiv = document.getElementById(id+'-icondiv');

    var sState = oMapObjects[objectId].conf.summary_state;

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
 * @param   Integer  Index in oMapObjects
 * @param   Integer  Time remaining in miliseconds
 * @param   Integer  Interval in miliseconds
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function flashIcon(objectId, iDuration, iInterval){
    if(isset(oMapObjects[objectId])) {
        oMapObjects[objectId].highlight(!oMapObjects[objectId].bIsFlashing);

        var iDurationNew = iDuration - iInterval;

        // Flash again until timer counted down and the border is hidden
        if(iDurationNew > 0 || (iDurationNew <= 0 && oMapObjects[objectId].bIsFlashing === true))
            setTimeout(function() { flashIcon(objectId, iDurationNew, iInterval); }, iInterval);
    }
}

//--- Overview -----------------------------------------------------------------


/**
 * parseOverviewPage()
 *
 * Parses the static html code of the overview page
 *
 * @param   Array    Array of objects to parse to the map
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseOverviewPage() {
    var oContainer = document.getElementById('overview');

    // Render maps and the rotations when enabled
    var types = [ [ oPageProperties.showmaps,      'overviewMaps',      oPageProperties.lang_mapIndex ],
                  [ oPageProperties.showrotations, 'overviewRotations', oPageProperties.lang_rotationPools ] ];
    for(var i = 0; i < types.length; i++) {
        if(types[i][0] === 1) {
            var h2 = document.createElement('h2');
            h2.innerHTML = types[i][2];
            oContainer.appendChild(h2);

            var container = document.createElement('div');
            container.setAttribute('id', types[i][1]);
            container.className = 'infobox';

            oContainer.appendChild(container);
            oTable = null;
        }
    }

    oContainer = null;
}

g_rendered_maps = 0;
g_processed_maps = 0;

/**
 * Adds a single map to the overview map list
 */
function addOverviewMap(map_conf, map_name) {
    g_processed_maps += 1;

    // Exit this function on invalid call
    if(map_conf === null || map_conf.length != 1)  {
        eventlog("worker", "warning", "addOverviewMap: Invalid call - maybe broken ajax response ("+map_name+")");
        if (g_processed_maps == g_map_names.length)
            finishOverviewMaps();
        return false;
    }

    g_rendered_maps += 1; // also count errors

    var container = document.getElementById('overviewMaps');

    // Find the map placeholder div (replace it to keep sorting)
    var mapdiv = null;
    var child = null;
    for (var i = 0; i < container.childNodes.length; i++) {
        child = container.childNodes[i];
        if (child.id == map_name) {
            mapdiv = child;
            break;
        }
    }

    // render the map object
    var oObj = new NagVisMap(map_conf[0]);
    if(oObj !== null) {
        // Save object to map objects array
        oMapObjects[oObj.conf.object_id] = oObj;

        // Parse child and save reference in parsedObject
        oObj.parsedObject = oObj.parseOverview();
        container.replaceChild(oObj.parsedObject, mapdiv);
    }
    oObj = null;

    // Finalize rendering after last map...
    if (g_processed_maps == g_map_names.length)
        finishOverviewMaps();

    container = null;
}

function finishOverviewMaps() {
    eventlog("worker", "debug", "addOverviewMap: Finished parsing all maps");
    getAndParseTemplates();

    // Hide the "Loading..." message. This is not the best place since rotations 
    // might not have been loaded now but in most cases this is the longest running request
    hideStatusMessage();
}

/**
 * parseOverviewRotations()
 *
 * Does initial parsing of rotations on the overview page
 *
 * @param   Array    Array of objects to parse to the map
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseOverviewRotations(aRotationsConf) {
    eventlog("worker", "debug", "setOverviewObjects: Start setting rotations");

    if(oPageProperties.showrotations === 1 && aRotationsConf.length > 0) {
        for(var i = 0, len = aRotationsConf.length; i < len; i++) {
            var oObj;

            oObj = new NagVisRotation(aRotationsConf[i]);

            if(oObj !== null) {
                // Parse object to overview
                oObj.parseOverview();
            }
        }
    } else {
        // Hide the rotations container
        var container = document.getElementById('overviewRotations');
        if (container) {
            container.style.display = 'none';
            container = null;
        }
    }

    eventlog("worker", "debug", "setOverviewObjects: End setting rotations");
}

/**
 * getOverviewProperties()
 *
 * Fetches the current map properties from the core
 *
 * @return  Boolean  Success?
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getOverviewProperties(mapName) {
    return getSyncRequest(oGeneralProperties.path_server+'?mod=Overview&act=getOverviewProperties')
}

/**
 * Fetches all maps to be shown on the overview page
 */
function getOverviewMaps() {
    var map_container = document.getElementById('overviewMaps');

    if(oPageProperties.showmaps !== 1 || g_map_names.length == 0) {
        if (map_container)
            map_container.parentNode.style.display = 'none';
        hideStatusMessage();
        return false;
    }

    eventlog("worker", "debug", "getOverviewMaps: Start requesting maps...");

    for (var i = 0, len = g_map_names.length; i < len; i++) {
        var mapdiv = document.createElement('div');
        mapdiv.setAttribute('id', g_map_names[i])
        map_container.appendChild(mapdiv);
        mapdiv = null;
        getAsyncRequest(oGeneralProperties.path_server+'?mod=Overview&act=getObjectStates'
                        + '&i[]=map-' + escapeUrlValues(g_map_names[i]) + getViewParams(),
                        false, addOverviewMap, g_map_names[i]);
    }
}

/**
 * getOverviewRotations()
 *
 * Fetches all rotations to be shown on the overview page
 *
 * @return  Array of rotations
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getOverviewRotations() {
    return getSyncRequest(oGeneralProperties.path_server+'?mod=Overview&act=getOverviewRotations')
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
    for(var i in oMapObjects) {
        obj = oMapObjects[i];
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
    if(workerTimeoutID)
        window.clearTimeout(workerTimeoutID);
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

    // Prevent default events
    if (event.preventDefault)
        event.preventDefault();
    event.returnValue = false;
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

    if (event.preventDefault)
        event.preventDefault();
    if (event.stopPropagation)
        event.stopPropagation();
    event.returnValue = false;
    return false;
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
 * getViewParams()
 *
 * Parses the url params for the current view to be used in urls.
 * Adds the width/height parameter if not set yet. It will add the
 * size of the map div area. The width/height values are not used by all views.
 *
 * @return  String    URL part with params and values
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getViewParams(update, userParams) {
    if(!isset(userParams))
        userParams = false;

    if(!userParams && isset(oViewProperties) && isset(oViewProperties['params'])) {
        var params = oViewProperties['params'];
    } else if(isset(oViewProperties) && isset(oViewProperties['user_params'])) {
        var params = oViewProperties['user_params'];
    } else {
        return '';
    }

    if(!isset(params))
        return '';

    // Udate the params before processing url
    if(isset(update)) {
        for(var param in update) {
            params[param] = update[param];
        }
    }

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
    if(oViewProperties && isset(oViewProperties['params'])
       && isset(oViewProperties['params'][param]))
        return oViewProperties['params'][param];
    return null;
}

function setViewParam(param, val) {
    oViewProperties['params'][param] = val;
}

/**
 * getMapProperties()
 *
 * Fetches the current map properties from the core
 *
 * @return  Boolean  Success?
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getMapProperties(type, mapName) {
    return getSyncRequest(oGeneralProperties.path_server+'?mod=Map&act=getMapProperties&show='
                          + escapeUrlValues(mapName)+getViewParams());
}

/**
 * getUrlProperties()
 *
 * Fetches the current url properties from the core
 *
 * @return  Boolean  Success?
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getUrlProperties(sUrl) {
    return getSyncRequest(oGeneralProperties.path_server+'?mod=Url&act=getProperties&show='
                                           + escapeUrlValues(sUrl));
}

// Returns true if the current view is
// a) a map
// b) uses the given source
function usesSource(source) {
    return oPageProperties
        && oPageProperties.view_type == 'map'
        && oPageProperties.sources
        && oPageProperties.sources.indexOf(source) !== -1;
}

/**
 * parseMap()
 *
 * Parses the map on initial page load or changed map configuration
 *
 * @return  Boolean  Success?
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseMap(iMapCfgAge, type, mapName) {
    var bReturn = false;

    // Is updated later by getMapProperties(), but we might need it in
    // the case an error occurs in getMapProperties() and one needs
    // the map name anyways, e.g. to detect the currently open map
    // during map deletion
    oPageProperties.map_name = mapName;

    // Block updates of the current map
    bBlockUpdates = true;

    var wasInMaintenance = inMaintenance(false);

    // Get new map/object information from ajax handler
    var properties = getMapProperties(type, mapName);
    if (properties)
        oPageProperties = properties;
    oPageProperties.view_type = type;

    if(inMaintenance()) {
        bBlockUpdates = false;
        return false
    } else if(wasInMaintenance === true) {
        // Hide the maintenance message when it was in maintenance before
        frontendMessageHide();
    }
    wasInMaintenance = null;

    getAsyncRequest(oGeneralProperties.path_server
                    + '?mod=Map&act=getMapObjects&show='
                    + mapName+getViewParams(), false, parseMapHandler, [iMapCfgAge, type, mapName]);
}

function parseMapHandler(oObjects, params) {
    // Only perform the reparsing actions when all information are there
    if(!oPageProperties || !oObjects) {
        // Close the status message window ("Loading...")
        hideStatusMessage();
        return;
    }

    var iMapCfgAge = params[0];
    var type       = params[1];
    var mapName    = params[2];

    // Remove all old objects
    var keys = getKeys(oMapObjects);
    for(var i = 0, len = keys.length; i < len; i++) {
        var obj = oMapObjects[keys[i]];
        if(obj && typeof obj.remove === 'function') {
            // Remove parsed object from map
            obj.remove();

            if(!obj.bIsLocked)
                updateNumUnlocked(-1);

            obj = null;

            // Remove element from object container
            delete oMapObjects[keys[i]];
        }
    }
    keys = null;

    // Update timestamp for map configuration (No reparsing next time)
    oFileAges[mapName] = iMapCfgAge;

    // Set map objects
    eventlog("worker", "info", "Parsing "+type+" objects");
    setMapObjects(oObjects);

    // Maybe force page reload when the map shal fill the viewport
    if (getViewParam('zoom') == 'fill')
        set_fill_zoom_factor();

    // Set map basics
    // Needs to be called after the summary state of the map is known
    setMapBasics(oPageProperties);

    // Load all templates
    getAndParseTemplates();

    // When user searches for an object highlight it
    if(oViewProperties && oViewProperties.search && oViewProperties.search != '') {
        eventlog("worker", "info", "Searching for matching object(s)");
        searchObjects(oViewProperties.search);
    }

    oObjects = null;

    // Close the status message window ("Loading...")
    hideStatusMessage();

    // Updates are allowed again
    bBlockUpdates = false;
}

/**
 * Fetches the contents of the given url and prints it on the current page
 *
 * @param   String   The url to fetch
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function parseUrl(sUrl) {
    var urlContainer = document.getElementById('url');
    if (urlContainer.tagName == 'DIV') {
        // Fetch contents from server
        var oUrlContents = getSyncRequest(oGeneralProperties.path_server
                           + '?mod=Url&act=getContents&show='
                           + escapeUrlValues(sUrl));

        if(typeof oUrlContents !== 'undefined' && oUrlContents.content) {
            // Replace the current contents with the new url
            urlContainer.innerHTML = oUrlContents.content;
        }
    }
    else {
        // iframe
        urlContainer.src = sUrl;
    }
}

/**
 * workerInitialize()
 *
 * Does the initial parsing of the pages
 *
 * @param   Integer  The iterator for the run id
 * @param   String   The type of the page which is currently displayed
 * @param   String   Optional: Identifier of the page to be displayed
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function workerInitialize(iCount, sType, sIdentifier) {
    // Show status message
    displayStatusMessage('Loading...', 'loading', true);

    // Initialize everything
    eventlog("worker", "info", "Initializing Worker (Run-ID: "+iCount+")");

    // Load state properties
    eventlog("worker", "debug", "Loading the state properties");

    // Handle the page rendering
    if(sType == 'map') {
        eventlog("worker", "debug", "Parsing " + sType + ": " + sIdentifier);
        renderZoombar();
        parseMap(oFileAges[sIdentifier], sType, sIdentifier);

    } else if(sType === 'overview') {
        // Load the overview properties
        eventlog("worker", "debug", "Loading the overview properties");
        oPageProperties = getOverviewProperties();
        oPageProperties.view_type = sType;

        // Loading the overview page
        eventlog("worker", "debug", "Setting page basiscs like title and favicon");
        setPageBasics(oPageProperties);

        eventlog("worker", "debug", "Parsing overview page");
        parseOverviewPage();

        getOverviewMaps();

        eventlog("worker", "debug", "Parsing rotations");
        parseOverviewRotations(getOverviewRotations());

        eventlog("worker", "info", "Finished parsing overview");
    } else if(sType === 'url') {
        // Load the map properties
        eventlog("worker", "debug", "Loading the url properties");
        oPageProperties = getUrlProperties(sIdentifier);
        oPageProperties.view_type = sType;

        // Fetches the contents from the server and prints it to the page
        eventlog("worker", "debug", "Parsing url page");
        parseUrl(sIdentifier);

        // Hide the "loading ..." message
        hideStatusMessage();
    } else {
        eventlog("worker", "error", "Unknown view type: "+sType);

        // Hide the "loading ..." message
        hideStatusMessage();
    }
}

function getAndParseTemplates() {
    // Bulk get all hover templates which are needed on the overview page
    eventlog("worker", "debug", "Fetching hover templates and urls");
    getHoverTemplates();
    getHoverUrls();

    // Assign the hover templates to the objects and parse them
    eventlog("worker", "debug", "Parse hover menus");
    parseHoverMenus();

    // Bulk get all context templates which are needed on the overview page
    eventlog("worker", "debug", "Fetching context templates");
    getContextTemplates();

    // Assign the context templates to the objects and parse them
    eventlog("worker", "debug", "Parse context menus");
    parseContextMenus();
}

/**
 * handleUpdate()
 *
 * This callback function handles the ajax response of bulk object
 * status updates
 *
 * @param   Object   List of objects with new state informations
 * @param   Array    List of parameters for this handler
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function handleUpdate(o, aParams) {
    var sType = aParams[0];
    var bStateChanged = false;

    // Stop processing these informations when the current view should not be
    // updated at the moment e.g. when reparsing the map after a changed mapcfg
    if(bBlockUpdates) {
        eventlog("ajax", "info", "Throwing new object information away since the view is blocked");
        return false;
    }

    if (!o) {
        eventlog("ajax", "info", "handleUpdate: got empty object. Terminating.");
        return false;
    }

    // Procees the "config changed" responses
    if(isset(o['status']) && o['status'] == 'CHANGED') {
        var oChanged = o['data'];
        
        for(var key in oChanged) {
            if(key == 'maincfg') {
                // FIXME: Not handled by ajax frontend, reload the page
                eventlog("worker", "info", "Main configuration file was updated. Need to reload the page");
                // Clear the scheduling timeout to prevent problems with FF4 bugs
                if(workerTimeoutID)
                    window.clearTimeout(workerTimeoutID);
                window.location.reload(true);
                return;

            } else {
                // FIXME: Maybe rerender map background images?
                //if(sType === 'automap') {
                //    // Render new background image and dot file, update background image
                //    automapParse(oPageProperties.map_name);
                //    setMapBackgroundImage(oPageProperties.background_image+iNow);
                //}

                if(iNumUnlocked > 0) {
                    eventlog("worker", "info", "Map config updated. "+iNumUnlocked+" objects unlocked - not reloading.");
                } else {
                    eventlog("worker", "info", "Map configuration file was updated. Reparsing the map.");
                    parseMap(oChanged[key], sType, oPageProperties.map_name);
                    return;
                }
            }
        }
    }

    // I don't think empty maps make any sense. So when no objects are present:
    // Try to fetch them continously
    if(oLength(oMapObjects) === 0) {
        if(sType == 'overview') {
            eventlog("worker", "info", "No maps found, reparsing...");
            getOverviewMaps();
            return;

        } else {
            eventlog("worker", "info", "Map is empty. Strange. Re-fetching objects");
            // FIXME: Maybe rerender map background images?
            //if(sType === 'automap') {
            //    // Render new background image and dot file, update background image
            //    automapParse(oPageProperties.map_name);
            //    setMapBackgroundImage(oPageProperties.background_image+iNow);
            //}
            parseMap(oFileAges[oPageProperties.map_name], sType, oPageProperties.map_name);
            return;
        }
    }

    /*
     * Now proceed with real actions when everything is OK
     */

    if(o.length > 0)
        bStateChanged = updateObjects(o, sType);

    // When some state changed on the map update the title and favicon
    if(sType == 'map' && bStateChanged)
        updateMapBasics();

    // FIXME: Add page basics (title, favicon, ...) update code for overview page

    o = null;
    bStateChanged = null;
}

/**
 * getUrlParts()
 *
 * Create the ajax request parameters for bulk updates. Bulk updates
 * can strip the url into several HTTP requests to work around too long urls.
 *
 * @param   Array    List of objects to get a new status for
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getUrlParts(arrObj) {
    var aUrlParts = [];
    var iUrlParams = 0;

    // Only continue with the loop when below param limit
    // and below maximum length
    for(var i = 0, len = arrObj.length; i < len && (oWorkerProperties.worker_request_max_params == 0 || iUrlParams < oWorkerProperties.worker_request_max_params); i++) {
        var type = oMapObjects[arrObj[i]].conf.type;
        var name = oMapObjects[arrObj[i]].conf.name;
        if(name) {
            var obj_id = oMapObjects[arrObj[i]].conf.object_id;
            var service_description = oMapObjects[arrObj[i]].conf.service_description;

            // Create request string
            var sUrlPart = '&i[]='+obj_id;

            // Adding 1 params above code, count them here
            iUrlParams += 1;

            // Append part to array of parts
            aUrlParts.push(sUrlPart);
            sUrlPart = null;

            service_description = null;
            obj_id = null;
        }

        name = null;
        type = null;
    }
    iUrlParams = null;
    arrObj = null;
    return aUrlParts;
}

/**
 * workerUpdate()
 *
 * Updates the page on a regular base
 *
 * @param   Integer  The iterator for the run id
 * @param   String   The type of the page which is currently displayed
 * @param   String   Optional: Identifier of the page to be displayed
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function workerUpdate(iCount, sType, sIdentifier) {
    // Log normal worker step
    eventlog("worker", "debug", "Update (Run-ID: "+iCount+")");

    if(sType === 'map' || sType === 'overview') {
        var mod = 'Map';
        
        var show = '';
        if (sType === 'map' && oPageProperties.map_name !== false)
            show = '&show=' + escapeUrlValues(oPageProperties.map_name);

        if (sType === 'overview') {
            mod = 'Overview';
        }

        // Get objects which need an update
        var arrObj = getObjectsToUpdate();

        // Get the updated objects via bulk request
        getBulkRequest(oGeneralProperties.path_server+'?mod=' + mod + '&act=getObjectStates'
                       + show +'&ty=state'+getViewParams()+getFileAgeParams(),
                       getUrlParts(arrObj), oWorkerProperties.worker_request_max_length,
                       false, handleUpdate, [ sType ]);

        if(sType === 'map') {
            // Stateless objects which shal be refreshed (enable_refresh=1) need a special
            // handling as they are reloaded by being reparsed.
            var aReload = [];
            for(var i = 0, len = arrObj.length; i < len; i++)
                if(oMapObjects[arrObj[i]].conf.type === 'shape'
                   || oMapObjects[arrObj[i]].conf.type === 'container')
                    aReload.push(arrObj[i]);

            // Update when needed
            if(aReload.length > 0)
                reloadObjects(aReload);
            aReload = null;
        }

        // Need to re-raise repeated events?
        handleRepeatEvents();

    } else if(sType === 'url') {
        // Fetches the contents from the server and prints it to the page
        eventlog("worker", "debug", "Reparsing url page");
        parseUrl(oPageProperties.url);
    }

    // Update lastWorkerRun
    oWorkerProperties.last_run = iNow;

    // Update the worker counter on maps
    updateWorkerCounter();

    // Cleanup ajax query cache
    cleanupAjaxQueryCache();
}

/**
 * runWorker()
 *
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
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function runWorker(iCount, sType, sIdentifier) {
    // If the iterator is 0 it is the first run of the worker. Its only task is
    // to render the page
    if(iCount === 0) {
        workerInitialize(iCount, sType, sIdentifier);
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
    workerTimeoutID = window.setTimeout(function() { runWorker((iCount+1), sType, sIdentifier); }, 1000);

    // Pro forma return
    return true;
}
