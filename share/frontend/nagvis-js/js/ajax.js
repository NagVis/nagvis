/*****************************************************************************
 *
 * ajax.js - Functions for handling NagVis Ajax requests
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

// Array to store the cached queries
var ajaxQueryCache = {};
// Cache lifetime is 30 seconds
var ajaxQueryCacheLifetime = 30;

/**
 * Function to create an XMLHttpClient in a cross-browser manner
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function initXMLHttpClient() {
    var xmlhttp;

    try {
        // Mozilla / Safari / IE7
        xmlhttp = new XMLHttpRequest();
    } catch (e) {
        // IE
        var XMLHTTP_IDS = [ 'MSXML2.XMLHTTP.5.0',
                            'MSXML2.XMLHTTP.4.0',
                            'MSXML2.XMLHTTP.3.0',
                            'MSXML2.XMLHTTP',
                            'Microsoft.XMLHTTP' ];

        var success = false;

        for(var i = 0, len = XMLHTTP_IDS.length; i < len && !success; i++) {
            try {
                xmlhttp = new ActiveXObject(XMLHTTP_IDS[i]);
                success = true;
            } catch (e) {}
        }

        if (!success) {
            throw new Error('Unable to create XMLHttpRequest.');
        }
    }

    return xmlhttp;
}

/**
 * Saves the query information to the query cache. The query cache persists
 * unless the page is being reloaded
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function updateQueryCache(url, timestamp, response) {
    ajaxQueryCache[url] = { "timestamp": timestamp, "response": response };
    eventlog("ajax", "debug", "Caching Query: "+url);
}

/**
 * Removes the query cache for a given url
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function cleanupQueryCache(sUrl) {
    // Set to null in array
    ajaxQueryCache[sUrl] = null;

    // Really remove key
    delete ajaxQueryCache[sUrl];

    eventlog("ajax", "debug", "Removed cached ajax query:"+sUrl);
}

/**
 * Cleans up the ajax query cache. It removes the deprecated cache entries and
 * shrinks the cache array.
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function cleanupAjaxQueryCache() {
    // Loop query cache array
    eventlog("ajax", "debug", "Removing old cached ajax queries");
    for(var sKey in ajaxQueryCache) {
        // If cache expired remove and shrink the array
        if(iNow - ajaxQueryCache[sKey].timestamp > ajaxQueryCacheLifetime) {
            cleanupQueryCache(sKey);
        }
    }
}

function ajaxError(e) {
    // Add frontend eventlog entry
    eventlog("ajax", "critical", "Problem while ajax transaction");
    eventlog("ajax", "debug", e.toString());

    frontendMessage({'type': 'CRITICAL',
                     'title': 'Ajax transaction error',
                     'message': 'Problem while ajax transaction. Is the NagVis host reachable?'},
                    0, 'ajaxError');
}

function httpError(text) {
    frontendMessage({'type': 'CRITICAL',
                     'title': 'HTTP error',
                     'message': text}, 0, 'httpError');
}

function phpError(text) {
    frontendMessage({'type': 'CRITICAL',
                     'title': 'PHP error',
                     'message': "PHP error in ajax request handler:\n" + text});
}

function jsonError(text) {
    frontendMessage({'type': 'CRITICAL',
                     'title': 'Syntax error',
                     'message': text}, 0, 'jsonError');
}

/**
 * Function for creating a asynchronous GET request
 * - Uses query cache
 * - Response needs to be JS code or JSON => Parses the response with eval()
 * - Errors need to match following Regex: /^Notice:|^Warning:|^Error:|^Parse error:/
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getAsyncRequest(sUrl, bCacheable, callback, callbackParams) {
    // Handle default params
    if(bCacheable === null)
        bCacheable = true;
    if(!isset(callback))
        callback = handleAsyncResponse;
    if(!isset(callbackParams))
        callbackParams = null;

    // Encode the url
    sUrl = sUrl.replace("+", "%2B");

    // use cache if last request is less than 30 seconds (30,000 milliseconds) ago
    if(bCacheable
       && typeof(ajaxQueryCache[sUrl]) !== 'undefined'
       && iNow - ajaxQueryCache[sUrl].timestamp <= ajaxQueryCacheLifetime) {
        // Prevent using invalid code in cache
        eventlog("ajax", "debug", "Using cached query");
        if(ajaxQueryCache[sUrl].response !== '')
            callback(eval('( '+ajaxQueryCache[sUrl].response+')'), callbackParams);
        else
            cleanupQueryCache(sUrl);
    } else {
        var oRequest = initXMLHttpClient();

        if(!oRequest)
            return false;

        oRequest.open("GET", sUrl+"&_t="+iNow);
        oRequest.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2005 00:00:00 GMT");
        oRequest.onreadystatechange = function() {
            if(oRequest && oRequest.readyState == 4) {
                // Handle unexpected HTTP responses. Normally everything comes with code 200.
                // If something different is received, something must be wrong. Raise a whole
                // screen message in this case.
                if(oRequest.status != 200) {
                    if(oRequest.status == 0) {
                        return; // silently skip status code 0 (occurs e.g. during page switching)
                    }

                    var msg = 'HTTP-Response: ' + oRequest.status;
                    if(oRequest.responseText != '') {
                        msg += ' - Body: ' + oRequest.responseText;
                    } else if(oRequest.status == 500) {
                        msg += ' - Internal Server Error (Take a look at the apache error log for details.)'
                    }

                    httpError(msg);
                    return;
                }

                frontendMessageRemove('httpError');
                frontendMessageRemove('ajaxError');

                var oResponse = null;
                if(oRequest.responseText.replace(/\s+/g, '').length === 0) {
                    if(bCacheable)
                        updateQueryCache(sUrl, iNow, '');
                } else {
                    var responseText = oRequest.responseText.replace(/^\s+/,"");

                    // Error handling for the AJAX methods
                    if(responseText.match(/^Notice:|^Warning:|^Error:|^Parse error:/)) {
                        phpError(responseText);
                    } else {
                        // Handle responses of json objects - including eval and wron response
                        // error handling and clearing
                        oResponse = handleJsonResponse(sUrl, responseText);
                        if(oResponse === '')
                            oResponse = null;
                    }
                    responseText = null;
                }
                callback(oResponse, callbackParams);
                oResponse = null;
            }
        }

        try {
            oRequest.send(null);
        } catch(e) {
            ajaxError(e);
        }
    }
}

/**
 * Default async request response handler
 *
 * This handler only displays error messages when some error occured.
 */
function handleAsyncResponse(oResponse) {
    if(isset(oResponse) && oResponse.status != 'OK')
        alert(oResponse.message);
    oResponse = null;
}

/**
 * Function for creating a synchronous GET request
 * - Uses query cache
 * - Response needs to be JS code or JSON => Parses the response with eval()
 * - Errors need to match following Regex: /^Notice:|^Warning:|^Error:|^Parse error:/
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getSyncRequest(sUrl, bCacheable, bRetryable) {
    var sResponse = null;
    var responseText;

    if(!isset(bCacheable))
        bCacheable = true;

    if(!isset(bRetryable))
        bRetryable = true;

    // Encode the url
    sUrl = sUrl.replace("+", "%2B");

    // use cache if last request is less than 30 seconds (30,000 milliseconds) ago
    if(bCacheable
       && typeof(ajaxQueryCache[sUrl]) !== 'undefined'
       && iNow - ajaxQueryCache[sUrl].timestamp <= ajaxQueryCacheLifetime) {
        eventlog("ajax", "debug", "Using cached query");
        responseText = ajaxQueryCache[sUrl].response;

        // Prevent using invalid code in cache
        if(responseText !== '') {
            // Handle responses of json objects - including eval and wron response
            // error handling and clearing
            sResponse = handleJsonResponse(sUrl, responseText);
        } else {
            // Remove the invalid code from cache
            cleanupQueryCache(sUrl);
        }

        responseText = null;
    } else {
        var oRequest = initXMLHttpClient();

        if(oRequest) {
            // Save this options to oOpt (needed for query cache)
            var url = sUrl;
            var timestamp = iNow;

            oRequest.open("GET", sUrl+"&_t="+timestamp, false);
            oRequest.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2005 00:00:00 GMT");

            try {
                oRequest.send(null);
                frontendMessageRemove('ajaxError');
            } catch(e) {
                ajaxError(e);
                bCacheable = false;
            }

            responseText = oRequest.responseText;

            if(responseText.replace(/\s+/g, '').length === 0) {
                if(bCacheable) {
                    // Cache that dialog
                    updateQueryCache(url, timestamp, '');
                }

                sResponse = '';
            } else {
                // Trim the left of the response
                responseText = responseText.replace(/^\s+/,"");

                // Error handling for the AJAX methods
                if(responseText.match(/^Notice:|^Warning:|^Error:|^Parse error:/)) {
                    phpError(responseText);
                } else {
                    // Handle responses of json objects - including eval and wron response
                    // error handling and clearing
                    sResponse = handleJsonResponse(sUrl, responseText)

                    if(sResponse !== '' && bCacheable) {
                        // Cache that answer (only when no error/warning/...)
                        updateQueryCache(url, timestamp, responseText);
                    }

                    responseText = null;
                }
            }

            url = null;
            timestamp = null;
        }

        oRequest = null;
    }
    return sResponse;
}

function handleJsonResponse(sUrl, responseText) {
    try {
        oResponse = eval('( '+responseText+')');
        frontendMessageRemove('jsonError');
    } catch(e) {
        jsonError("Invalid json response<div class=details>\nTime: " + iNow + "<br />\nURL: " + sUrl + "<br />\nResponse: <code>" + responseText + '</code></div>');
        return '';
    }

    if(typeof(oResponse) !== 'object') {
        jsonError("Invalid json response:\nTime:" + iNow + "\nURL: " + sUrl + "\nResponse: " + responseText);
        return '';
    } else {
        if(isset(oResponse.type) && isset(oResponse.message)) {
            frontendMessage(oResponse, 0, 'miscError');
            return '';
        }
        frontendMessageRemove('miscError');
        return oResponse;
    }
}

/**
 * This function simply loads a remote url and returns the responseText 1:1
 * without any special error handling
 */
function getSyncUrl(url) {
    var oReq = initXMLHttpClient();
    oReq.open('GET', url, false);
    oReq.send(null);
    return oReq.responseText;
}

 /**
  * This function simply loads a remote url using a POST request including sending
  * a request body and returns the responseText 1:1 without any special error handling
  */
function postSyncUrl(url, data) {
    var oReq = initXMLHttpClient();
    oReq.open('POST', url, false);
    oReq.setRequestHeader("If-Modified-Since", "Sat, 22 Sep 1986 00:00:00 GMT");
    // Set post specific options
    oReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    oReq.setRequestHeader("Content-length", data.length);
    oReq.setRequestHeader("Connection", "close");
    oReq.send(data);
    return oReq.responseText;
}

/**
 * Prevent reaching too long urls, split the update to several
 * requests. Just start the request and clean the string strUrl
 * Plus fire the rest of the request on the last iteration
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getBulkRequest(sBaseUrl, aUrlParts, iLimit, bCacheable, handler, handlerParams) {
    var sUrl = '';
    var o;
    var aReturn = [];

    var async = false;
    if(typeof handler == 'function')
        async = true;

    eventlog("ajax", "debug", "Bulk parts: "+aUrlParts.length+" Async: "+ async);
    var count = 0;
    for(var i = 0, len = aUrlParts.length; i < len; i++) {
        sUrl = sUrl + aUrlParts[i];
        count += 1;
        if(sUrl !== '' && (sBaseUrl.length + sUrl.length > iLimit || i == len - 1)) {
            eventlog("ajax", "debug", "Bulk go: "+ count);
            if(async)
                getAsyncRequest(sBaseUrl + sUrl, bCacheable, handler, handlerParams);
            else {
                o = getSyncRequest(sBaseUrl + sUrl, bCacheable);
                if(o)
                    aReturn = aReturn.concat(o);
                o = null;
            }

            count = 0;
            sUrl = '';
        }
    }
    return aReturn;
}

/**
 * Function for creating a synchronous POST request
 * - Response needs to be JS code or JSON => Parses the response with eval()
 * - Errors need to match following Regex: /^Notice:|^Warning:|^Error:|^Parse error:/
 */
function postSyncRequest(sUrl, request) {
    var oResponse = null;
    var responseText;

    // Encode the url
    sUrl = sUrl.replace("+", "%2B");

    var oRequest = initXMLHttpClient();

    if(oRequest) {
        oRequest.open("POST", sUrl+"&_t="+iNow, false);
        oRequest.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2005 00:00:00 GMT");

        // Set post specific options. request might be a FormData object. In this case
        // the request is not using form-urlencoded data, instead it is automatically
        // set to multipart/form-data
        if (typeof request !== 'object')
            oRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        try {
            oRequest.send(request);
            frontendMessageRemove('ajaxError');
        } catch(e) {
            ajaxError(e);
        }

        responseText = oRequest.responseText;

        if(oResponse === null && responseText.replace(/\s+/g, '').length !== 0) {
            // Trim the left of the response
            responseText = responseText.replace(/^\s+/,"");

            // Error handling for the AJAX methods
            if(responseText.match(/^Notice:|^Warning:|^Error:|^Parse error:/)) {
                oResponse = {};
                oResponse.type = 'CRITICAL';
                oResponse.message = "PHP error in ajax request handler:\n"+responseText;
                oResponse.title = "PHP error";
            } else {
                // Handle invalid response (No JSON format)
                try {
                    oResponse = eval('( '+responseText+')');
                } catch(e) {
                    oResponse = {};
                    oResponse.type = 'CRITICAL';
                    oResponse.message = "Invalid JSON response:\n"+responseText;
                    oResponse.title = "Syntax error";
                }

                responseText = null;
            }
        }
    }

    oRequest = null;

    return oResponse;
}


/**
 * Parses all values from the given form to a string or form data object
 * which is then be used as request data in ajax queries
 */
function getFormParams(formId, skipHelperFields) {
    if (window.FormData) {
        var data = new FormData();
        var formdata = true;
    } else {
        var formdata = false;
        var data = '';
    }

    var add_data = function(key, val) {
        if (formdata)
            data.append(key, val);
        else
            data += key + "=" + escapeUrlValues(val) + "&";
    };

    // Read form contents
    var oForm = document.getElementById(formId);
    if (typeof oForm === 'undefined')
        return data;

    // Get relevant input elements
    var aFields = oForm.getElementsByTagName('input');
    for (var i = 0, len = aFields.length; i < len; i++) {
        // Filter helper fields (if told to do so)
        if (skipHelperFields && aFields[i].name.charAt(0) === '_')
            continue;

        // Skip options which use the default value and where the value has
        // not been set before
        var oFieldDefault    = document.getElementById('_'+aFields[i].name);
        if (aFields[i] && oFieldDefault && !document.getElementById('_conf_'+aFields[i].name)) {
            if (aFields[i].value === oFieldDefault.value) {
                continue;
            }
        }
        oFieldDefault = null;

        if (aFields[i].type == "hidden"
            || aFields[i].type == "text"
            || aFields[i].type == "password"
            || aFields[i].type == "submit") {
            add_data(aFields[i].name, aFields[i].value);
        }
        else if (aFields[i].type == "checkbox") {
            if (aFields[i].checked) {
                add_data(aFields[i].name, aFields[i].value);
            }
            else {
                add_data(aFields[i].name, '');
            }
        }
        else if (aFields[i].type == "radio") {
            if (aFields[i].checked) {
                add_data(aFields[i].name, aFields[i].value);
            }
        }
        else if (aFields[i].type == "file") {
            // Now handle the file upload elements (which lead to an error when not
            // using the form data mechanism)
            if (!formdata) {
                throw new Error('File upload not supported with your browser. '
                               +'This form can only be used when using a browser '
                               +'which suports javascript file uploads (FormData).');
            }

            if (aFields[i].files.length > 0) {
                var file = aFields[i].files[0];
                data.append(aFields[i].name, file, file.name);
            }
        }

    }

    // Get relevant select elements
    aFields = oForm.getElementsByTagName('select');
    for(var i = 0, len = aFields.length; i < len; i++) {
        // Filter helper fields (NagVis WUI specific)
        if (skipHelperFields && aFields[i].name.charAt(0) === '_')
            continue;

        // Skip options which use the default value
        var oFieldDefault = document.getElementById('_'+aFields[i].name);
        if(aFields[i] && oFieldDefault) {
            if(aFields[i].value === oFieldDefault.value) {
                continue;
            }
        }
        oFieldDefault = null;

        // Can't use the selectedIndex when using select fields with multiple
        if(!aFields[i].multiple || aFields[i].multiple !== true) {
            // Skip fields where nothing is selected
            if (aFields[i].selectedIndex != -1) {
                add_data(aFields[i].name, aFields[i].options[aFields[i].selectedIndex].value);
            }
        } else {
            for (var a = 0; a < aFields[i].options.length; a++) {
                // Only add selected options
                if (aFields[i].options[a].selected == true) {
                    add_data(aFields[i].name+'[]', aFields[i].options[a].value);
                }
            }
        }
    }

    aFields = null;
    oForm = null;

    return data;
}
