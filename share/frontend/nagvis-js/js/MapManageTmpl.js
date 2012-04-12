// FIXME: Validate the add template form
function checkTmplAdd() {
    return true;
}

// FIXME: Validate the modify template form
function checkTmplModify() {
    return true;
}

// FIXME: Validate the delete template form
function checkTmplDelete() {
    return true;
}

function putTmplOpts(val) {
    formDeleteDynamicOptions('modify');

    if(val !== '') {
        // fetch template options via ajax
        var oResponse = getSyncRequest(
            oGeneralProperties.path_server + '?mod=Map&act=getTmplOpts&show='
            + escapeUrlValues(oPageProperties.map_name)+'&name='+val,
            false, false
        );

        if(typeof oResponse !== 'undefined' && typeof oResponse.opts !== 'undefined') {
            // render form lines
            for(var key in oResponse.opts) {
                formAddOption('modify', key, oResponse.opts[key])
            }
        }

        oResponse = null;
    }
}

/**
 * Add a line with additional fields to the template form
 */
var iAddLineNumStart = null;

function formDeleteDynamicOptions(formId) {
    var oTBody = document.getElementById(formId).firstElementChild.firstElementChild;

    // Cleanup old dynamic nodes
    var toDelete = [];

    // Remove old backend options
    for(var i = 0, len = oTBody.rows.length; i < len; i++) {
        if(oTBody.rows[i].attributes.length > 0) {
            if(oTBody.rows[i].id && oTBody.rows[i].id !== '') {
                if(oTBody.rows[i].id.search("dyn_") !== -1) {
                    toDelete[toDelete.length] = i;
                }
            }
        }
    }

    toDelete.reverse();
    for(var i = 0, len = toDelete.length; i < len; i++) {
        document.getElementById(formId).firstElementChild.deleteRow(toDelete[i]);
    }

    toDelete = null;
}

function formAddOption(formId, fieldName, fieldVal) {
    if(typeof fieldName === 'undefined') {
        fieldName = '';
    }

    if(typeof fieldVal === 'undefined') {
        fieldVal = '';
    }

    var oTBody = document.getElementById(formId).firstElementChild.firstElementChild;
    var iLineNum = oTBody.childNodes.length;

    if(iAddLineNumStart == null) {
        // Save num of lines at start
        iAddLineNumStart = oTBody.childNodes.length;
    }

    var oTr = document.createElement('tr');

    if(iAddLineNumStart != iLineNum) {
        oTr.setAttribute('id', 'dyn_'+iLineNum);
    }

    var oTd = document.createElement('td');
    oTd.setAttribute('class', 'tdfield');
    oTd.setAttribute('className', 'tdfield');

    if(iAddLineNumStart == iLineNum) {
        oTd.appendChild(document.createTextNode('Name'));
    } else {
        var oInput = document.createElement('input');
        oInput.name = 'opt'+(oTBody.childNodes.length-iAddLineNumStart)+'[name]';
        oInput.value = fieldName;
        oTd.appendChild(oInput);
        oInput = null;
    }

    oTr.appendChild(oTd);

    var oTd = document.createElement('td');
    oTd.setAttribute('class', 'tdfield');
    oTd.setAttribute('className', 'tdfield');

    if(iAddLineNumStart == iLineNum) {
        oTd.appendChild(document.createTextNode('Value'));
    } else {
        var oInput = document.createElement('input');
        oInput.name = 'opt'+(oTBody.childNodes.length-iAddLineNumStart)+'[value]';
        oInput.value = fieldVal;
        oTd.appendChild(oInput);
        oInput = null;
    }

    oTr.appendChild(oTd);
    oTd = null;

    // add line to tbody before the submit line
    oTBody.insertBefore(oTr, oTBody.lastElementChild);
    oTr = null;
    oTBody = null;

    // On first call the labels are added above. Now add the first field line
    if(iAddLineNumStart == iLineNum) {
        formAddOption(formId, fieldName, fieldVal);
    }
}
