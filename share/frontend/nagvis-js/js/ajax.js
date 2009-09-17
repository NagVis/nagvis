/*****************************************************************************
 *
 * ajax.js - Functions for handling NagVis Ajax requests
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

// Array to store the cached queries
var ajaxQueryCache = {};
// Cache lifetime is 30 seconds (30,000 milliseconds)
var ajaxQueryCacheLifetime = 30000;

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
	
	if (bCacheable === null) {
		bCacheable = true;
	}
	
	if (bRetryable === null) {
		bRetryable = true;
	}
	
	// Encode the url
	sUrl = sUrl.replace("+", "%2B");
	
	// use cache if last request is less than 30 seconds (30,000 milliseconds) ago
	if(bCacheable && typeof(ajaxQueryCache[sUrl]) !== 'undefined' && iNow - ajaxQueryCache[sUrl].timestamp <= ajaxQueryCacheLifetime) {
		eventlog("ajax", "debug", "Using cached query");
		responseText = ajaxQueryCache[sUrl].response;
		
		// Prevent using invalid code in cache
		if(responseText !== '') {
			sResponse = eval('( '+responseText+')');
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
			
			oRequest.open("GET", sUrl+"&timestamp="+timestamp, false);
			oRequest.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2005 00:00:00 GMT");
			
			try {
				oRequest.send(null);
			} catch(e) {
				// Add frontend eventlog entry
				eventlog("ajax", "critical", "Problem while ajax transaction");
				eventlog("ajax", "debug", e.toString());
				
				// Handle application message/error
				var oMsg = {};
				oMsg.type = 'CRITICAL';
				oMsg.message = "Problem while ajax transaction. Is the NagVis host reachable?";
				oMsg.title = "Ajax transaction error";
				frontendMessage(oMsg);
				oMsg = null;
				
				// This bad response should not be cached
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
					var oMsg = {};
					oMsg.type = 'CRITICAL';
					oMsg.message = "PHP error in ajax request handler:\n"+responseText;
					oMsg.title = "PHP error";
					
					// Handle application message/error
					frontendMessage(oMsg);
					oMsg = null;
				} else if(responseText.match(/^NagVisError:/)) {
					responseText = responseText.replace(/^NagVisError:/, '');
					var oMsg = eval('( '+responseText+')');
					
					// Handle application message/error
					frontendMessage(oMsg);
					oMsg = null;
					
					// Retry after sleep of x seconds for x times
					if(bRetryable) {
						// FIXME: Retry after short wait
						//for(var i = 0; i < 2 && sResponse == null; i++) {
						//	sResponse = getSyncRequest(sUrl, bCacheable, false);
						//}
					}
					
					//FIXME: Think about caching the error!
				} else {
					// Handle invalid response (No JSON format)
					try {
						sResponse = eval('( '+responseText+')');
					} catch(e) {
						var oMsg = {};
						oMsg.type = 'CRITICAL';
						oMsg.message = "Invalid JSON response:\n"+responseText;
						oMsg.title = "Syntax error";
						
						// Handle application message/error
						frontendMessage(oMsg);
						oMsg = null;
					}
					
					if(sResponse !== null && bCacheable) {
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
	
	if(sResponse !== null && sResponse !== '') {
		if(typeof frontendMessageHide == 'function') { 
			frontendMessageHide();
		}
	}
	
	return sResponse;
}

function getBulkSyncRequest(sBaseUrl, aUrlParts, iLimit, bCacheable) {
	var sUrl = '';
	var o;
	var aReturn = [];
	
	for(var i = 0, len = aUrlParts.length; i < len; i++) {
		sUrl = sUrl + aUrlParts[i];
		
		// Prevent reaching too long urls, split the update to several 
		// requests. Just start the request and clean the string strUrl
		if(sUrl !== '' && sBaseUrl.length+sUrl.length > iLimit) {
			o = getSyncRequest(sBaseUrl+sUrl, bCacheable);
			
			if(o) {
				aReturn = aReturn.concat(o);
			}
			
			o = null;
			sUrl = '';
		}
	}
	
	if(sUrl !== '') {
		// Bulk update the objects, this query should not be cached
		o = getSyncRequest(sBaseUrl+sUrl, bCacheable);
		if(o) {
			aReturn = aReturn.concat(o);
		}
		
		o = null;
		sUrl = '';
	}
	
	sUrl = null;
	
	return aReturn;
}
