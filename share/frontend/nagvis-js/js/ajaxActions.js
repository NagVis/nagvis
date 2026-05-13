function getMidOfAnchor(oObj) {
    return [oObj.x + parseInt(oObj.style.width) / 2, oObj.y + parseInt(oObj.style.height) / 2];
}

function saveObjectAttr(objId, attr) {
    let urlPart = "";
    for (const key in attr)
        // parseInt() returned NaN, because value was set to "auto";
        // but also allow relative coordinate strings (contain '%') and
        // comma-separated line endpoint coordinates (e.g. "100,500")
        if (!isNaN(attr[key]) || isRelativeCoord(attr[key]) || (typeof attr[key] === "string" && attr[key].includes(",")))
            urlPart += "&" + key + "=" + escapeUrlValues(attr[key]);

    call_ajax(
        oGeneralProperties.path_server +
            "?mod=Map&act=modifyObject&map=" +
            escapeUrlValues(oPageProperties.map_name) +
            "&id=" +
            escapeUrlValues(objId) +
            urlPart
    );
}

function saveObjectRemove(objId) {
    call_ajax(
        oGeneralProperties.path_server +
            "?mod=Map&act=deleteObject&map=" +
            escapeUrlValues(oPageProperties.map_name) +
            "&id=" +
            escapeUrlValues(objId)
    );
}
