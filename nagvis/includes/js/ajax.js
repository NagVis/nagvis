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
 
var ajaxQueryCache = [];

/**
 * function to create an XMLHttpClient in a cross-browser manner
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
		var XMLHTTP_IDS = new Array('MSXML2.XMLHTTP.5.0',
									'MSXML2.XMLHTTP.4.0',
									'MSXML2.XMLHTTP.3.0',
									'MSXML2.XMLHTTP',
									'Microsoft.XMLHTTP' );
		var success = false;
		
		for (var i=0;i < XMLHTTP_IDS.length && !success; i++) {
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
 * Simple HTTP-Get to given URL
 * - Uses query cache
 * - Escapes the following chars:
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getHttpRequest(sUrl, bCacheable) {
	var responseText = "";
	
	if (bCacheable == null) {
		bCacheable = true;
	}
	
	// Benutze cache, wenn die letzte Anfrage weniger als 30 Sekunden (30000 milisekunden) her ist
	if(bCacheable && typeof(ajaxQueryCache[sUrl]) != 'undefined' && Date.parse(new Date())-ajaxQueryCache[sUrl].timestamp <= 30000) {
		responseText = ajaxQueryCache[sUrl].response;
	} else {
		var oRequest = initXMLHttpClient();
		
		if(oRequest) {
			try {
				oRequest.open("GET", sUrl, false);
				oRequest.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2005 00:00:00 GMT");
				oRequest.send(null);
			} catch(e) {
				alert("Error! URL: "+ sUrl +"\nError message: "+ e);
			}
			
			
			var responseText = oRequest.responseText;
			
			if(responseText.replace(/\s+/g,'').length == 0) {
				responseText = '';
			} else {
				// Trim the left of the response
				responseText = responseText.replace(/^\s+/,"");
			}
			
			if(bCacheable) {
				// Cache that dialog
				updateQueryCache(sUrl, Date.parse(new Date()), responseText);
			}
		}
	}
	
	return responseText;
}

function getBulkSyncRequest(sBaseUrl, aUrlParts, iLimit, bCacheable) {
	var sUrl = '';
	var o;
	var aReturn = Array();
	for(var i = 0; i < aUrlParts.length; i++) {
		sUrl += aUrlParts[i];
		
		// Prevent reaching too long urls, split the update to several 
		// requests. Just start the request and clean the string strUrl
		if(sUrl != '' && sBaseUrl.length+sUrl.length > iLimit) {
			o = getSyncRequest(sBaseUrl+sUrl, bCacheable);
			if(o) {
				aReturn = aReturn.concat(o);
			}
			sUrl = '';
		}
	}
	
	if(sUrl != '') {
		// Bulk update the objects, this query should not be cached
		o = getSyncRequest(sBaseUrl+sUrl, bCacheable);
		if(o) {
			aReturn = aReturn.concat(o);
		}
	}
	
	return aReturn;
}

/**
 * Function for creating an synchronous GET request
 * - Uses query cache
 * - Response needs to be JS code or JSON => Parses the response with eval()
 * - Errors need to match following Regex: /^Notice:|^Warning:|^Error:|^Parse error:/
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getSyncRequest(sUrl, bCacheable, bRetryable) {
	var sResponse = null;
	
	if (bCacheable == null) {
		bCacheable = true;
	}
	
	if (bRetryable == null) {
		bRetryable = true;
	}
	
	// Benutze cache, wenn die letzte Anfrage weniger als 30 Sekunden (30000 milisekunden) her ist
	if(bCacheable && typeof(ajaxQueryCache[sUrl]) != 'undefined' && Date.parse(new Date())-ajaxQueryCache[sUrl].timestamp <= 30000) {
		responseText = ajaxQueryCache[sUrl].response;
		
		sResponse = eval('( '+responseText+')');
	} else {
		var oRequest = initXMLHttpClient();
		
		if(oRequest) {
			var oOpt = Object();
			// Save this options to oOpt (needed for query cache)
			oOpt.url = sUrl;
			oOpt.timestamp = Date.parse(new Date());
			
			oRequest.open("GET", sUrl+"&timestamp="+oOpt.timestamp, false);
			oRequest.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2005 00:00:00 GMT");
			oRequest.send(null);
			
			var responseText = oRequest.responseText;
			
			if(responseText.replace(/\s+/g,'').length == 0) {
				if(bCacheable) {
					// Cache that dialog
					updateQueryCache(oOpt.url, oOpt.timestamp, '');
				}
				
				sResponse = '';
			} else {
				// Trim the left of the response
				responseText = responseText.replace(/^\s+/,"");
				
				// Error handling for the AJAX methods
				if(responseText.match(/^Notice:|^Warning:|^Error:|^Parse error:/)) {
					var oMsg = Object();
					oMsg.type = 'CRITICAL';
					oMsg.message = "PHP error in ajax request handler:\n"+responseText;
					oMsg.title = "PHP error";
					
					// Handle application message/error
					frontendMessage(oMsg);
				} else if(responseText.match(/^NagVisError:/)) {
					responseText = responseText.replace(/^NagVisError:/, '');
					var oMsg = eval('( '+responseText+')');
					
					// Handle application message/error
					frontendMessage(oMsg);
					
					// Retry after sleep of x seconds for x times
					if(bRetryable) {
						// FIXME: Retry after short wait
						//for(var i = 0; i < 2 && sResponse == null; i++) {
						//	sResponse = getSyncRequest(sUrl, bCacheable, false);
						//}
					}
					
					//FIXME: Think about caching the error!
				} else {
					if(bCacheable) {
						// Cache that answer (only when no error/warning/...)
						updateQueryCache(oOpt.url, oOpt.timestamp, responseText);
					}
					
					sResponse = eval('( '+responseText+')');
				}
			}
		}
	}
	
	if(sResponse != null) {
		frontendMessageHide();
	}
	
	return sResponse;
}

/**
 * Function for creating an async GET request
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getRequest(url,myCallback,oOpt) {
	// Benutze cache, wenn die letzte Anfrage weniger als 30 Sekunden (30000 milisekunden) her ist
	if(typeof(ajaxQueryCache[url]) != 'undefined' && Date.parse(new Date())-ajaxQueryCache[url].timestamp <= 30000) {
		getAnswer(undefined, myCallback, ajaxQueryCache[url], true);
	} else {
		var oRequest = initXMLHttpClient();
		
		if (oRequest != null) {
			// Save this options to oOpt (needed for query cache)
			oOpt.url = url;
			oOpt.timestamp = Date.parse(new Date());
			
			oRequest.open("GET", url+"&timestamp="+oOpt.timestamp, true);
			oRequest.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2005 00:00:00 GMT");
			oRequest.onreadystatechange = function() { getAnswer(oRequest,myCallback,oOpt,false); };
			oRequest.send(null);
		}
	}
}

/**
 * Function for handling the answers of the ajax requests (including error handling)
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getAnswer(oRequest,myCallback,oOpt,bCached) {
	if(bCached || (!bCached && oRequest.readyState == 4)) {
		if(bCached || (!bCached && oRequest.status == 200)) {
			var responseText;
			if(!bCached) {
				responseText = oRequest.responseText;
			} else {
				responseText = oOpt.response;
			}
			
			if(responseText.replace(/\s+/g,'').length == 0) {
				// Cache that dialog
				updateQueryCache(oOpt.url, oOpt.timestamp, '');
				
				window[myCallback]('',oOpt);
			} else {
				// Cache that dialog
				updateQueryCache(oOpt.url, oOpt.timestamp, responseText);
				
				// Trim the left of the response
				responseText = responseText.replace(/^\s+/,"");
				
				// Error handling for the AJAX methods
				if(responseText.match(/^Notice:|^Warning:|^Error:|^Parse error:/)) {
					alert("Error in ajax request handler:\n"+responseText);
				} else {
					window[myCallback](eval('( '+responseText+')'),oOpt);
				}
			}
		}
	}
}

function updateQueryCache(url,timestamp,response) {
	ajaxQueryCache[url] = { "timestamp": timestamp, "response": response };
}
