function getDomObjViewType(id) {
	if(document.getElementById(id+'-icondiv'))
		return 'icon';
	else if(document.getElementById(id+'-linediv'))
		return 'line';
	// FIXME: What is in case of shapes,gadgets,textboxes,...
}

function getDomObjType(id) {
	// FIXME: Code this!
	return 'service';
}

function getMidOfAnchor(oObj) {
	return [ oObj.x + parseInt(oObj.style.width)  / 2,
	         oObj.y + parseInt(oObj.style.height) / 2 ];
}

function handleDragResult(objId, anchorId) {
	var urlParts = '';
	var jsObj = getMapObjByDomObjId(objId);

	urlParts = '&x=' + escapeUrlValues(jsObj.conf.x) + '&y=' + escapeUrlValues(jsObj.conf.y);

	jsObj = null;
	return urlParts;
}

/**
 * Whenever an anchor action is performed this method should be called
 * once to send the changes to the server and make the changes permanent.
 */
function saveObjectAfterAnchorAction(oAnchor) {
	// Split id to get object information
	var arr        = oAnchor.id.split('-');
	var objId      = arr[0];
	var anchorType = arr[1];
	var anchorId   = arr[2];
  arr = null;
	var urlPart    = '';

	if(anchorType === 'drag')
		urlPart = handleDragResult(objId, anchorId);
	
	var mod = 'Map';
	var mapParam = 'map';
	if(oPageProperties.view_type === 'automap') {
		mod      = 'AutoMap';
		mapParam = 'show';
	}

	getAsyncRequest(oGeneralProperties.path_server + '?mod='+mod+'&act=modifyObject&'+mapParam+'='
	                + escapeUrlValues(oPageProperties.map_name)
	                + '&id=' + escapeUrlValues(objId) + urlPart);
}

function saveObjectAttr(objId, attr) {
	var urlPart = '';
	for (var key in attr)
		urlPart += '&' + key + '=' + escapeUrlValues(attr[key]);
	
	var mod = 'Map';
	var mapParam = 'map';
	if(oPageProperties.view_type === 'automap') {
		mod      = 'AutoMap';
		mapParam = 'show';
	}
	
	getAsyncRequest(oGeneralProperties.path_server + '?mod='+mod+'&act=modifyObject&'+mapParam+'='
	                + escapeUrlValues(oPageProperties.map_name) + '&id=' + escapeUrlValues(objId) + urlPart);
}
