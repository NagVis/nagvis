function getMidOfAnchor(oObj) {
    return [ oObj.x + parseInt(oObj.style.width)  / 2,
             oObj.y + parseInt(oObj.style.height) / 2 ];
}

function saveObjectAfterResize(oObj) {
    var objId = oObj.id.split('-')[0];
    var objX = rmZoomFactor(pxToInt(oObj.style.left), true);
    var objY = rmZoomFactor(pxToInt(oObj.style.top), true);
    var objW = rmZoomFactor(parseInt(oObj.style.width));
    var objH = rmZoomFactor(parseInt(oObj.style.height));

    // Reposition in frontend
    var obj = getMapObjByDomObjId(objId);
    obj.conf.x = objX;
    obj.conf.y = objY;
    obj.conf.w = objW;
    obj.conf.h = objH;
    obj.place();

    if(!isInt(objX) || !isInt(objY) || !isInt(objW) || !isInt(objH)) {
        alert('ERROR: Invalid coords ('+objX+'/'+objY+'/'+objW+'/'+objH+'). Terminating.');
        return false;
    }

    var urlPart = '&x='+objX+'&y='+objY+'&w='+objW+'&h='+objH;
    call_ajax(oGeneralProperties.path_server + '?mod=Map&act=modifyObject'
              +'&map=' + escapeUrlValues(oPageProperties.map_name)
              + '&id=' + escapeUrlValues(objId) + urlPart);
}

function saveObjectAttr(objId, attr) {
    var urlPart = '';
    for (var key in attr)
        urlPart += '&' + key + '=' + escapeUrlValues(attr[key]);

    call_ajax(oGeneralProperties.path_server + '?mod=Map&act=modifyObject&map='
              + escapeUrlValues(oPageProperties.map_name) + '&id=' + escapeUrlValues(objId) + urlPart);
}

function saveObjectRemove(objId) {
    call_ajax(oGeneralProperties.path_server + '?mod=Map&act=deleteObject&map='
              + escapeUrlValues(oPageProperties.map_name) + '&id=' + escapeUrlValues(objId));

}
