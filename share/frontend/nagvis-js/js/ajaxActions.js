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

	urlParts = '&x=' + jsObj.conf.x + '&y=' + jsObj.conf.y;

	jsObj = null;
	return urlParts;
}

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
	
	var oResult = getSyncRequest(oGeneralProperties.path_server + '?mod=Map&act=modifyObject&map='
	                             + oPageProperties.map_name + '&oid=' + objId + urlPart);

	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
	}
	oResult = null;
}
