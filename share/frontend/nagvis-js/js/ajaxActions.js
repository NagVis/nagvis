function getMidOfAnchor(oObj) {
    return [ oObj.x + parseInt(oObj.style.width)  / 2,
             oObj.y + parseInt(oObj.style.height) / 2 ];
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
