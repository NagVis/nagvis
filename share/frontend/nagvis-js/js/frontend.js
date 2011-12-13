/*****************************************************************************
 *
 * frontend.js - Functions implementing the new ajax frontend with automatic
 *               worker function etc.
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
 */

/**
 * Definition of needed variables
 */
var oHoverTemplates = {};
var oHoverTemplatesChild = {};
var oHoverUrls = {};
var oContextTemplates = {};
var oAutomapParams = {};
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

    var oResult = postSyncRequest(sUrl, getFormParams(sFormId));

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
    var oResult = postSyncRequest(sUrl, getFormParams(sFormId));
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
            setTimeout('scrollSlow('+oMapObjects[objectId].conf.x+', '+oMapObjects[objectId].conf.y+', 15)', 0);
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
    for(var i in oMapObjects)
        if(oMapObjects[i].lastUpdate <= (iNow-(oWorkerProperties.worker_update_object_states*1000)))
            // Do not update shapes where enable_refresh=0
            if(oMapObjects[i].conf.type !== 'shape'
               || (oMapObjects[i].conf.type === 'shape'
                     && oMapObjects[i].conf.enable_refresh
                         && oMapObjects[i].conf.enable_refresh == '1'))
                arrReturn.push(i);

    // Now spread the objects in the available timeslots
    var iNumTimeslots = Math.ceil(oWorkerProperties.worker_update_object_states / oWorkerProperties.worker_interval);
    var iNumObjectsPerTimeslot = Math.ceil(oLength(oMapObjects) / iNumTimeslots);
    eventlog("worker", "debug", "Number of timeslots: "+iNumTimeslots+" Number of Objects per Slot: "+iNumObjectsPerTimeslot);

    // Only spread when the number of objects is larger than the objects for each
    // timeslot
    if(arrReturn.length > iNumObjectsPerTimeslot) {
        eventlog("worker", "debug", "Spreading map objects in timeslots");
        // Just remove all elements from the end of the array
        arrReturn = arrReturn.slice(0, iNumObjectsPerTimeslot);
    }

    eventlog("worker", "debug", "getObjectsToUpdate: Have to update "+arrReturn.length+" objects");
    return arrReturn;
}

/**
 * getCfgFileAges()
 *
 * Bulk get file ages of important files
 * The request is being cached to prevent too often updates. The worker asks
 * every 5 seconds by default - this is too much for a config check.
 *
 * @return  Boolean
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getCfgFileAges(viewType, mapName) {
    if(!isset(viewType))
        var viewType = oPageProperties.view_type;
    if(!isset(mapName))
        var mapName = oPageProperties.map_name;

    var result = null;
    if(viewType === 'map')
        result = getSyncRequest(oGeneralProperties.path_server+'?mod=General&act=getCfgFileAges&f[]=mainCfg&m[]='+escapeUrlValues(mapName), true);
    else if(viewType === 'automap')
        result = getSyncRequest(oGeneralProperties.path_server+'?mod=General&act=getCfgFileAges&f[]=mainCfg&am[]='+escapeUrlValues(mapName), true);
    else
        result = getSyncRequest(oGeneralProperties.path_server+'?mod=General&act=getCfgFileAges&f[]=mainCfg', true);

    viewType = null;
    if(isset(result))
        return result;
    else
        return {};
}

/**
 * checkMainCfgChanged()
 *
 * Detects if the main configuration file has changed since last load
 *
 * @return  Boolean
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function checkMainCfgChanged(iCurrentAge) {
    eventlog("worker", "debug", "MainCfg Current: "+date(oGeneralProperties.date_format, iCurrentAge)+" In Use: "+date(oGeneralProperties.date_format, oFileAges.mainCfg));
    return oFileAges.mainCfg != iCurrentAge;
}

/**
 * checkMapCfgChanged()
 *
 * Detects if the map configuration file has changed since last load
 *
 * @return  Boolean
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function checkMapCfgChanged(iCurrentAge, mapName) {
    eventlog("worker", "debug", "MapCfg " + mapName +
                     " Current: " + date(oGeneralProperties.date_format, iCurrentAge) +
                     " Cached: " + date(oGeneralProperties.date_format, oFileAges.map_config));

    return oFileAges[mapName] != iCurrentAge;
}

/**
 * setMapHoverUrls()
 *
 * Gets the code for needed hover templates and saves it for later use in icons
 *
 * @param   Object   Object with basic page properties
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setMapHoverUrls() {
    var aUrlParts = [];
    var aTemplateObjects;

    // Loop all map objects to get the used hover templates
    for(var i in oMapObjects) {
        // Ignore objects which
        // a) have a disabled hover menu
        // b) do use hover_url
        if(oMapObjects[i].conf.hover_menu && oMapObjects[i].conf.hover_menu == 1 && oMapObjects[i].conf.hover_url && oMapObjects[i].conf.hover_url !== '')
            oHoverUrls[oMapObjects[i].conf.hover_url] = '';
    }

    // Build string for bulk fetching the templates
    for(var i in oHoverUrls)
        if(i != 'Inherits')
            aUrlParts.push('&url[]='+escapeUrlValues(i));

    // Get the needed templates via bulk request
    aTemplateObjects = getBulkRequest(oGeneralProperties.path_server+'?mod=General&act=getHoverUrl', aUrlParts, oWorkerProperties.worker_request_max_length, true);

    // Set the code to global object oHoverTemplates
    if(aTemplateObjects.length > 0)
        for(var i = 0, len = aTemplateObjects.length; i < len; i++)
            oHoverUrls[aTemplateObjects[i].url] = aTemplateObjects[i].code;
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
        if(oMapObjects[i].conf.hover_menu && oMapObjects[i].conf.hover_menu !== '0')
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
           && (!isset(oHoverTemplates[oMapObjects[i].conf.hover_template]) || oHoverTemplates[oMapObjects[i].conf.hover_template] === ''))
            oHoverTemplates[oMapObjects[i].conf.hover_template] = '';
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
        // a) have a disabled menu
        // b) template is already known
        // FIXME: conf.context_menu has inconsistent types (with and without quotes)
        //        fix this and  === can be used here
        if(isset(oMapObjects[i].conf.context_menu) && oMapObjects[i].conf.context_menu == 1
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
        if((oMapObjects[i].conf.context_menu && oMapObjects[i].conf.context_menu != '0')
           || !oMapObjects[i].bIsLocked)
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
    var sAutomapParams = '';
    var mod = 'Map';
    if(oPageProperties.view_type === 'automap') {
        sAutomapParams = getAutomapParams();
        mod = 'AutoMap';
    }

    // Get new map state from core
    oMapSummaryObj = new NagVisMap(getSyncRequest(oGeneralProperties.path_server
                                   + '?mod=' + mod + '&act=getObjectStates&ty=summary&show='
                                                                 + escapeUrlValues(oPageProperties.map_name)
                                                                 + sAutomapParams, false)[0]);
    sAutomapParams = null;

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
    if(oPageProperties.view_type === 'automap')
        setMapBackgroundImage(oPageProperties.background_image+iNow);
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
        if((sType === 'map' || sType === 'automap')
           && !oMapObjects[objectId].stateChanged() && oMapObjects[objectId].outputOrPerfdataChanged()) {
            // Reparse object to map (only for maps!)
            // No else for overview here, senseless!
            if(sType === 'map') {
                oMapObjects[objectId].parse();
            } else if(sType === 'automap') {
                oMapObjects[objectId].parseAutomap();
            }
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
            } else if(sType === 'automap') {
                oMapObjects[objectId].parseAutomap();
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
                if(oPageProperties.event_scroll === '1')
                    setTimeout(function() { scrollSlow(oMapObjects[objectId].conf.x, oMapObjects[objectId].conf.y, 15); }, 0);

                // - Eventlog
                if(oMapObjects[objectId].conf.type == 'service') {
                    eventlog("state-change", "info", oMapObjects[objectId].conf.type+" "+oMapObjects[objectId].conf.name+" "+oMapObjects[objectId].conf.service_description+": Old: "+oMapObjects[objectId].last_state.summary_state+"/"+oMapObjects[objectId].last_state.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].last_state.summary_in_downtime+" New: "+oMapObjects[objectId].conf.summary_state+"/"+oMapObjects[objectId].conf.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].conf.summary_in_downtime);
                } else {
                    eventlog("state-change", "info", oMapObjects[objectId].conf.type+" "+oMapObjects[objectId].conf.name+": Old: "+oMapObjects[objectId].last_state.summary_state+"/"+oMapObjects[objectId].last_state.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].last_state.summary_in_downtime+" New: "+oMapObjects[objectId].conf.summary_state+"/"+oMapObjects[objectId].conf.summary_problem_has_been_acknowledged+"/"+oMapObjects[objectId].conf.summary_in_downtime);
                }

                // - Sound
                if(oPageProperties.event_sound === '1') {
                    setTimeout('playSound("'+objectId+'", 1)', 0);
                }
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
        var o = document.getElementById('editIndicator');
        if(o) {
            o.style.display = 'none';
            o = null;
        }
    } else {
        var o = document.getElementById('editIndicator');
        if(o) {
            o.style.display = '';
            o = null;
        }
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
        sMapPart = '&show='+escapeUrlValues(map);
    } else if(oPageProperties.view_type === 'automap') {
        sMod = 'AutoMap';
        sMapPart = '&show='+escapeUrlValues(map);
        sAddPart = getAutomapParams();
    } else if(oPageProperties.view_type === 'overview') {
        sMod = 'Overview';
        sMapPart = '';
    }

    // Create request string
    var sUrlPart = '&i[]=' + escapeUrlValues(obj_id);

    // Get the updated objectsupdateMapObjects via bulk request
    var o = getSyncRequest(oGeneralProperties.path_server+'?mod='
                          + escapeUrlValues(sMod) + '&act=getObjectStates'
                          + sMapPart + '&ty=state' + sUrlPart + sAddPart, false);

    sUrlPart = null;
    sMod = null;
    sMapPart = null;
    map = null;
    service_description = null;
    obj_id = null;
    name = null;

    var bStateChanged = false;
    if(isset(o) && o.length > 0)
        bStateChanged = updateObjects(o, oPageProperties.view_type);
    o = null;

    // Don't update basics on the overview page
    if(oPageProperties.view_type !== 'overview' && bStateChanged)
        updateMapBasics();
    bStateChanged = null;

    var event = !event ? window.event : event;
    if(event.stopPropagation)
        event.stopPropagation();
    event.cancelBubble = true;
    return false
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
        if(document.getElementById('backgroundImage')) {
            var oImage = document.getElementById('backgroundImage');
        } else {
            var oImage = document.createElement('img');
            oImage.id = 'backgroundImage';
            document.body.appendChild(oImage);
        }

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
            case 'map':
                oObj = new NagVisMap(aMapObjectConf[i]);
            break;
            case 'textbox':
                oObj = new NagVisTextbox(aMapObjectConf[i]);
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
        } else if(oPageProperties.view_type === 'automap') {
            if(oMapObjects[i].conf.type === 'host')
                oMapObjects[i].parseAutomap();
            else
                oMapObjects[i].parse();
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
 * updateShapes()
 *
 * Bulk refreshes shapes (Only reparsing)
 *
 * @param   Array    Array of objects to reparse
 * @return  Boolean  Returns true when some state has changed
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function updateShapes(aShapes) {
    for(var i = 0, len = aShapes.length; i < len; i++) {
        oMapObjects[aShapes[i]].parse();
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
    oMapObjects[objectId].highlight(!oMapObjects[objectId].bIsFlashing);

    var iDurationNew = iDuration - iInterval;

    // Flash again until timer counted down and the border is hidden
    if(iDurationNew > 0 || (iDurationNew <= 0 && oMapObjects[objectId].bIsFlashing === true))
        setTimeout(function() { flashIcon(objectId, iDurationNew, iInterval); }, iInterval);
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

    // Render maps, automaps, geomaps and the rotations when enabled
    var types = [ [ oPageProperties.showmaps,      'overviewMaps',      oPageProperties.lang_mapIndex ],
                    [ oPageProperties.showautomaps,  'overviewAutomaps',  oPageProperties.lang_automapIndex],
                              [ oPageProperties.showgeomap,    'overviewGeomap',    'Geomap' ],
                              [ oPageProperties.showrotations, 'overviewRotations', oPageProperties.lang_rotationPools ] ];
    for(var i = 0; i < types.length; i++) {
        if(types[i][0] === 1) {
            var oTable = document.createElement('table');
            oTable.setAttribute('class', 'infobox');
            oTable.setAttribute('className', 'infobox');

            var oTbody = document.createElement('tbody');
            oTbody.setAttribute('id', types[i][1]);

            var oTr = document.createElement('tr');

            var oTh = document.createElement('th');
            if(types[i][1] == 'overviewRotations')
                oTh.colSpan = 2
            else
                oTh.colSpan = oPageProperties.cellsperrow;
            oTh.innerHTML = types[i][2];

            oTr.appendChild(oTh);
            oTh = null;

            oTbody.appendChild(oTr);
            oTr = null;

            oTable.appendChild(oTbody);
            oTbody = null;

            oContainer.appendChild(oTable);
            oTable = null;
        }
    }

    oContainer = null;
}

/**
 * parseOverviewMaps()
 *
 * Does initial parsing of maps on the overview page
 *
 * @param   Array    Array of objects to parse to the map
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseOverviewMaps(aMapsConf) {
    eventlog("worker", "debug", "parseOverviewMaps: Start setting maps");

    // Exit this function on invalid call
    if(aMapsConf === null)  {
        eventlog("worker", "warning", "parseOverviewMaps: Invalid call - maybe broken ajax response");
        return false;
    }

    // Render the maps when enabled
    if(oPageProperties.showmaps === 1) {
        if(aMapsConf.length == 0) {
            document.getElementById('overviewMaps').parentNode.style.display = 'none';
            return false;
        }

        var oTable = document.getElementById('overviewMaps');
        var oTr = document.createElement('tr');

        for(var i = 0, len = aMapsConf.length; i < len; i++) {
            var oObj;

            oObj = new NagVisMap(aMapsConf[i]);

            if(oObj !== null) {
                // Save object to map objects array
                oMapObjects[oObj.conf.object_id] = oObj;

                // Parse child and save reference in parsedObject
                oObj.parsedObject = oTr.appendChild(oObj.parseOverview());
            }

            if((i+1) % oPageProperties.cellsperrow === 0) {
                oTable.appendChild(oTr);
                oTr = null;
                oTr = document.createElement('tr');
            }

            oObj = null;
        }

        // Fill table with empty cells if there are not enough maps to get the last
        // row filled
        if(i % oPageProperties.cellsperrow !== 0) {
            for(var a = 0; a < (oPageProperties.cellsperrow - (i % oPageProperties.cellsperrow)); a++) {
                var oTd = document.createElement('td');
                oTr.appendChild(oTd);
                oTd = null;
            }
        }

        // Append last row
        oTable.appendChild(oTr);
        oTr = null;

        oTable = null;
    }

    eventlog("worker", "debug", "parseOverviewMaps: End setting maps");
}

/**
 * parseOverviewAutomaps()
 *
 * Does initial parsing of automaps on the overview page
 *
 * @param   Array    Array of objects to parse to the page
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function parseOverviewAutomaps(aMapsConf) {
    eventlog("worker", "debug", "parseOverviewAutomaps: Start setting automaps");

    // Exit this function on invalid call
    if(aMapsConf === null)  {
        eventlog("worker", "warning", "parseOverviewAutomaps: Invalid call - maybe broken ajax response");
        return false;
    }

    // Render the maps when enabled
    if(oPageProperties.showautomaps === 1) {
        if(aMapsConf.length == 0) {
            document.getElementById('overviewAutomaps').parentNode.style.display = 'none';
            return false;
        }

        var oTable = document.getElementById('overviewAutomaps');
        var oTr = document.createElement('tr');

        for(var i = 0, len = aMapsConf.length; i < len; i++) {
            var oObj;

            oObj = new NagVisMap(aMapsConf[i]);

            if(oObj !== null) {
                // Save object to map objects array
                oMapObjects[oObj.conf.object_id] = oObj;

                // Parse child and save reference in parsedObject
                oObj.parsedObject = oTr.appendChild(oObj.parseOverview());
            }

            if((i+1) % oPageProperties.cellsperrow === 0) {
                oTable.appendChild(oTr);
                oTr = null;
                oTr = document.createElement('tr');
            }

            oObj = null;
        }

        // Fill table with empty cells if there are not enough maps to get the last
        // row filled
        if(i % oPageProperties.cellsperrow !== 0) {
            for(var a = 0; a < (oPageProperties.cellsperrow - (i % oPageProperties.cellsperrow)); a++) {
                var oTd = document.createElement('td');
                oTr.appendChild(oTd);
                oTd = null;
            }
        }

        // Append last row
        oTable.appendChild(oTr);
        oTr = null;

        oTable = null;
    }

    eventlog("worker", "debug", "parseOverviewAutomaps: End setting automaps");
}

/**
 * parseOverviewGeomap()
 *
 * Does initial parsing of geomap on the overview page
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 * @author	Roman Kyrylych <rkyrylych@op5.com>
 */
function parseOverviewGeomap() {
    eventlog("worker", "debug", "parseOverviewGeomap: Start setting geomap");

    // Render the maps when enabled
    if(oPageProperties.showgeomap === 1) {
        var oTable = document.getElementById('overviewGeomap');
        var oTr = document.createElement('tr');

        var oTd = document.createElement('td');
        oTd.setAttribute('id', 'geomap-icon');
        oTd.setAttribute('class', 'geomap');
        oTd.setAttribute('className', 'geomap');
        oTd.style.width = '200px';

        // Only show map thumb when configured
        if(oPageProperties.showmapthumbs === 1) {
            oTd.style.height = '200px';
        }

        oTr.appendChild(oTd);

        // Link
        var oLink = document.createElement('a');
        oLink.href = oGeneralProperties.path_base+'/netmap/shell.html';

        // Status image
        var oImg = document.createElement('img');
        oImg.align = "right";
        oImg.src = oGeneralProperties.path_iconsets+'std_small_unknown.png';
        oImg.alt = 'geomap';

        oLink.appendChild(oImg);
        oImg = null;

        // Title
        var h2 = document.createElement('h2');
        h2.appendChild(document.createTextNode('Geomap'));
        oLink.appendChild(h2);
        h2 = null;

        var br = document.createElement('br');
        oLink.appendChild(br);
        br = null;

        // Only show map thumb when configured
        if(oPageProperties.showmapthumbs === 1) {
            oImg = document.createElement('img');
            oImg.style.width = '200px';
            oImg.style.height = '150px';
            oImg.src = oGeneralProperties.path_images+'maps/geomap-thumb.png';
            oLink.appendChild(oImg);
            oImg = null;
        }

        oTd.appendChild(oLink);
        oLink = null;

        oTd = null;

        for(var a = 0; a < (oPageProperties.cellsperrow - 1); a++) {
            var oTd = document.createElement('td');
            oTr.appendChild(oTd);
            oTd = null;
        }

        // Append last row
        oTable.appendChild(oTr);
        oTr = null;

        oTable = null;
    }

    eventlog("worker", "debug", "parseOverviewGeomap: End setting geomap");
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
                // Save object to map objects array
                aRotations.push(oObj);

                // Parse object to overview
                oObj.parseOverview();
            }
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
 * getOverviewMaps()
 *
 * Fetches all maps to be shown on the overview page
 *
 * @return  Array of maps
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getOverviewMaps() {
    return getSyncRequest(oGeneralProperties.path_server+'?mod=Overview&act=getOverviewMaps')
}

/**
 * getOverviewAutomaps()
 *
 * Fetches all automaps to be shown on the overview page
 *
 * @return  Array of maps
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getOverviewAutomaps() {
    return getSyncRequest(oGeneralProperties.path_server+'?mod=Overview&act=getOverviewAutomaps')
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
 * getAutomapParams()
 *
 * Parses the url params from the oAutomapParams object
 *
 * @return  String    URL part with params and values
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getAutomapParams() {
    var sParams = '';
    for(var param in oAutomapParams) {
        if(oAutomapParams[param] != '') {
            sParams += '&' + param + '=' + escapeUrlValues(oAutomapParams[param]);
        }
    }
    return sParams;
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
    if(type === 'automap')
        return getSyncRequest(oGeneralProperties.path_server+'?mod=AutoMap&act=getAutomapProperties&show='
                            + escapeUrlValues(mapName)+getAutomapParams())
    else
        return getSyncRequest(oGeneralProperties.path_server+'?mod=Map&act=getMapProperties&show='
                              + escapeUrlValues(mapName))
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
                                           + escapeUrlValues(sUrl))
}

/**
 * automapParse()
 *
 * Parses the automap background image
 *
 * @return  Boolean  Success?
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function automapParse(mapName) {
    return getSyncRequest(oGeneralProperties.path_server+'?mod=AutoMap&act=parseAutomap&show='
                          + escapeUrlValues(mapName)+getAutomapParams())
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

    // Block updates of the current map
    bBlockUpdates = true;

    var wasInMaintenance = inMaintenance(false);

    // Get new map/object information from ajax handler
    oPageProperties = getMapProperties(type, mapName);
    oPageProperties.view_type = type;

    if(inMaintenance()) {
        bBlockUpdates = false;
        return false
    } else if(wasInMaintenance === true) {
        // Hide the maintenance message when it was in maintenance before
        frontendMessageHide();
    }
    wasInMaintenance = null;

    var oObjects;
    if(type === 'automap')
        oObjects = getSyncRequest(oGeneralProperties.path_server
                                  + '?mod=AutoMap&act=getAutomapObjects&show='
                                  + mapName+getAutomapParams(), false);
    else
        oObjects = getSyncRequest(oGeneralProperties.path_server+'?mod=Map&act=getMapObjects&show='+mapName, false);

    // Only perform the reparsing actions when all information are there
    if(oPageProperties && oObjects) {
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

        // Set map basics
        // Needs to be called after the summary state of the map is known
        setMapBasics(oPageProperties);

        // Bulk get all hover templates which are needed on the map
        eventlog("worker", "info", "Fetching hover templates and hover urls");
        getHoverTemplates();
        setMapHoverUrls();

        // Assign the hover templates to the objects and parse them
        eventlog("worker", "info", "Parse hover menus");
        parseHoverMenus();

        // Bulk get all context templates which are needed on the map
        eventlog("worker", "info", "Fetching context templates");
        getContextTemplates();

        // Assign the context templates to the objects and parse them
        eventlog("worker", "info", "Parse context menus");
        parseContextMenus();

        // When user searches for an object highlight it
        eventlog("worker", "info", "Searching for matching object(s)");
        if(oViewProperties && oViewProperties.search && oViewProperties.search != '')
            searchObjects(oViewProperties.search);

        bReturn = true;
    } else {
        bReturn = false;
    }

    oObjects = null;

    // Updates are allowed again
    bBlockUpdates = false;

    return bReturn;
}

/**
 * Fetches the contents of the given url and prints it on the current page
 *
 * @param   String   The url to fetch
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function parseUrl(sUrl) {
    // Fetch contents from server
    var oUrlContents = getSyncRequest(oGeneralProperties.path_server
                       + '?mod=Url&act=getContents&show='
                       + escapeUrlValues(sUrl));

    if(typeof oUrlContents !== 'undefined' && oUrlContents.content) {
        // Replace the current contents with the new url
        var urlContainer = document.getElementById('url');
        urlContainer.innerHTML = oUrlContents.content;
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
        // Loading a simple map
        eventlog("worker", "debug", "Parsing map: " + sIdentifier);

        // Parse the map
        if(parseMap(oFileAges[sIdentifier], sType, sIdentifier) === false)
            eventlog("worker", "error", "Problem while parsing the map on page load");

        eventlog("worker", "info", "Finished parsing map");
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

        eventlog("worker", "debug", "Parsing maps");
        parseOverviewMaps(getOverviewMaps());

        eventlog("worker", "debug", "Parsing automaps");
        parseOverviewAutomaps(getOverviewAutomaps());

        eventlog("worker", "debug", "Parsing geomap");
        parseOverviewGeomap();

        eventlog("worker", "debug", "Parsing rotations");
        parseOverviewRotations(getOverviewRotations());

        // Bulk get all hover templates which are needed on the overview page
        eventlog("worker", "debug", "Fetching hover templates");
        getHoverTemplates();

        // Assign the hover templates to the objects and parse them
        eventlog("worker", "debug", "Parse hover menus");
        parseHoverMenus();

        // Bulk get all context templates which are needed on the overview page
        eventlog("worker", "debug", "Fetching context templates");
        getContextTemplates();

        // Assign the context templates to the objects and parse them
        eventlog("worker", "debug", "Parse context menus");
        parseContextMenus();

        eventlog("worker", "info", "Finished parsing overview");
    } else if(sType === 'url') {
        // Load the map properties
        eventlog("worker", "debug", "Loading the url properties");
        oPageProperties = getUrlProperties(sIdentifier);
        oPageProperties.view_type = sType;

        // Fetches the contents from the server and prints it to the page
        eventlog("worker", "debug", "Parsing url page");
        parseUrl(sIdentifier);

        // Load the file ages of the important configuration files
        eventlog("worker", "debug", "Loading the file ages");
    } else if(sType == 'automap') {
        // Loading a simple map
        eventlog("worker", "debug", "Parsing automap: " + sIdentifier);

        // Parse the map
        if(parseMap(oFileAges[sIdentifier], sType, sIdentifier) === false)
            eventlog("worker", "error", "Problem while parsing the automap on page load");

        eventlog("worker", "info", "Finished parsing automap");
    } else {
        eventlog("worker", "error", "Unknown view type: "+sType);
    }

    // Close the status message window
    hideStatusMessage();
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

    if(o.length > 0)
        bStateChanged = updateObjects(o, sType);

    // When some state changed on the map update the title and favicon
    if((sType == 'map' || sType == 'automap') && bStateChanged)
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
    for(var i = 0, len = arrObj.length; i < len && (oWorkerProperties.worker_request_max_params == 0 || (oWorkerProperties.worker_request_max_params != 0 && iUrlParams < oWorkerProperties.worker_request_max_params)); i++) {
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

    // Get the file ages of important files
    eventlog("worker", "debug", "Loading the file ages");
    var oCurrentFileAges = getCfgFileAges();

    // Check for changed main configuration
    if(oCurrentFileAges && checkMainCfgChanged(oCurrentFileAges.mainCfg)) {
        // FIXME: Not handled by ajax frontend, reload the page
        eventlog("worker", "info", "Main configuration file was updated. Need to reload the page");
        // Clear the scheduling timeout to prevent problems with FF4 bugs
        if(workerTimeoutID)
            window.clearTimeout(workerTimeoutID);
        window.location.reload(true);
        return;
    }

    if(sType === 'map') {
        // Check for changed map configuration
        if(oCurrentFileAges && checkMapCfgChanged(oCurrentFileAges[oPageProperties.map_name], oPageProperties.map_name)) {
            if(iNumUnlocked > 0) {
                eventlog("worker", "info", "Map config updated. "+iNumUnlocked+" objects unlocked - not reloading.");
            } else {
                eventlog("worker", "info", "Map configuration file was updated. Reparsing the map.");
                if(parseMap(oCurrentFileAges[oPageProperties.map_name], sType, oPageProperties.map_name) === false)
                    eventlog("worker", "error", "Problem while reparsing the map after new map configuration");
            }
        }

        // I don't think empty maps make any sense. So when no objects are present:
        // Try to fetch them continously
        if(oLength(oMapObjects) === 0) {
            eventlog("worker", "info", "Map is empty. Strange. Re-fetching objects");

            if(parseMap(oCurrentFileAges[oPageProperties.map_name], sType, oPageProperties.map_name) === false)
                eventlog("worker", "error", "Problem while reparsing the map after new map configuration");
        }

        oCurrentFileAges = null;

        /*
         * Now proceed with real actions when everything is OK
         */

        // Get objects which need an update
        var arrObj = getObjectsToUpdate();

        // Get the updated objects via bulk request
        getBulkRequest(oGeneralProperties.path_server+'?mod=Map&act=getObjectStates&show='
                        + oPageProperties.map_name+'&ty=state',
                   getUrlParts(arrObj), oWorkerProperties.worker_request_max_length,
                                     false, handleUpdate, [ sType ]);

        // Shapes which need to be updated need a special handling
        var aShapesToUpdate = [];
        for(var i = 0, len = arrObj.length; i < len; i++)
            if(oMapObjects[arrObj[i]].conf.type === 'shape')
                aShapesToUpdate.push(arrObj[i]);

        // Update shapes when needed
        if(aShapesToUpdate.length > 0)
            updateShapes(aShapesToUpdate);
        aShapesToUpdate = null;
    } else if(sType === 'automap') {

        // Check for changed map configuration
        if(oCurrentFileAges && checkMapCfgChanged(oCurrentFileAges[oPageProperties.map_name], oPageProperties.map_name)) {
            // Render new background image and dot file
            automapParse(oPageProperties.map_name);

            // Update background image for automap
            if(oPageProperties.view_type === 'automap')
                setMapBackgroundImage(oPageProperties.background_image+iNow);

            // Reparse the automap on changed map configuration
            eventlog("worker", "info", "Automap configuration file was updated. Reparsing the map.");
            if(parseMap(oCurrentFileAges[oPageProperties.map_name], sType, oPageProperties.map_name) === false)
                eventlog("worker", "error", "Problem while reparsing the automap after new configuration");
        }

        // I don't think empty maps make sense. So when no objects are present:
        // Try to fetch them continously
        if(oLength(oMapObjects) === 0) {
            eventlog("worker", "info", "Automap is empty. Strange. Re-fetching objects");

            // Render new background image and dot file
            automapParse(oPageProperties.map_name);

            // Update background image for automap
            setMapBackgroundImage(oPageProperties.background_image+iNow);

            // Reparse the automap on changed map configuration
            eventlog("worker", "info", "Reparsing the map.");
            if(parseMap(oCurrentFileAges[oPageProperties.map_name], sType, oPageProperties.map_name) === false)
                eventlog("worker", "error", "Problem while reparsing the automap");
        }

        oCurrentFileAges = null;

        /*
         * Now proceed with real actions when everything is OK
         */

        // Get the updated objectsupdateMapObjects via bulk request
        getBulkRequest(oGeneralProperties.path_server+'?mod=AutoMap&act=getObjectStates&show='+
                   escapeUrlValues(oPageProperties.map_name)+'&ty=state'+getAutomapParams(),
                   getUrlParts(getObjectsToUpdate()),
                                     oWorkerProperties.worker_request_max_length, false, handleUpdate, [ sType ]);
    } else if(sType === 'url') {

        // Fetches the contents from the server and prints it to the page
        eventlog("worker", "debug", "Reparsing url page");
        parseUrl(oPageProperties.url);

    } else if(sType === 'overview') {

        //FIXME: Map configuration(s) changed?

        // When no automaps/maps present: Try to fetch them continously
        if(oLength(oMapObjects) === 0) {
            eventlog("worker", "debug", "No automaps/maps found, reparsing...");
            parseOverviewMaps(getOverviewMaps());
            parseOverviewAutomaps(getOverviewAutomaps());

            // Bulk get all hover templates which are needed on the overview page
            eventlog("worker", "debug", "Fetching hover templates");
            getHoverTemplates();

            // Assign the hover templates to the objects and parse them
            eventlog("worker", "debug", "Parse hover menus");
            parseHoverMenus();

            // Bulk get all context templates which are needed on the overview page
            eventlog("worker", "oMapObjects", "Fetching context templates");
            getContextTemplates();

            // Assign the context templates to the objects and parse them
            eventlog("worker", "info", "Parse context menus");
            parseContextMenus();
        }

        /*
         * Now proceed with real actions when everything is OK
         */

        // Get the updated objectsupdateMapObjects via bulk request
        getBulkRequest(oGeneralProperties.path_server+'?mod=Overview&act=getObjectStates&ty=state',
                       getUrlParts(getObjectsToUpdate()),
                                     oWorkerProperties.worker_request_max_length, false, handleUpdate, [ sType ]);
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
    // The identifier is only used on first load when page properties is not
    // present
    if(typeof(sIdentifier) === 'undefined')
        sIdentifier = '';

    // If the iterator is 0 it is the first run of the worker. Its only task is
    // to render the page
    if(iCount === 0) {
        workerInitialize(iCount, sType, sIdentifier);
    } else {
        /**
         * Do these actions every run (every second) excepting the first run
         */

        iNow = Date.parse(new Date());

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
    workerTimeoutID = window.setTimeout(function() { runWorker((iCount+1), sType); }, 1000);

    // Pro forma return
    return true;
}
