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

function saveObjectAfterResize(oObj) {
    var objId = oObj.id.split('-')[0];
    var objX = pxToInt(oObj.style.left);
    var objY = pxToInt(oObj.style.top);
    var objW = parseInt(oObj.style.width);
    var objH = parseInt(oObj.style.height);

    // Reposition in frontend
    var obj = getMapObjByDomObjId(objId);
    obj.conf.x = objX;
    obj.conf.y = objY;
    obj.conf.w = objW;
    obj.conf.h = objH;
    obj.reposition();

    if(!isInt(objX) || !isInt(objY) || !isInt(objW) || !isInt(objH)) {
        alert('ERROR: Invalid coords ('+objX+'/'+objY+'/'+objW+'/'+objH+'). Terminating.');
        return false;
    }

    var urlPart = '&x='+objX+'&y='+objY+'&w='+objW+'&h='+objH;
    getAsyncRequest(oGeneralProperties.path_server + '?mod=Map&act=modifyObject'
                    +'&map=' + escapeUrlValues(oPageProperties.map_name)
                    + '&id=' + escapeUrlValues(objId) + urlPart);
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
    var action     = 'modifyObject';

    if(anchorType === 'drag' || anchorType === 'icondiv' || anchorType === 'label') {
        urlPart = handleDragResult(objId, anchorId);
    } else if(anchorType === 'delete') {
        action  = 'deleteObject';
    } else {
        alert('Unhandled action object: ' + anchorType);
    }

    var mod = 'Map';
    var mapParam = 'map';
    if(oPageProperties.view_type === 'automap') {
        mod      = 'AutoMap';
        mapParam = 'show';
    }

    getAsyncRequest(oGeneralProperties.path_server + '?mod='+mod+'&act=' + action + '&' + mapParam + '='
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

function saveObjectRemove(objId) {
    getAsyncRequest(oGeneralProperties.path_server + '?mod=Map&act=deleteObject&map='
                    + escapeUrlValues(oPageProperties.map_name) + '&id=' + escapeUrlValues(objId));

}
