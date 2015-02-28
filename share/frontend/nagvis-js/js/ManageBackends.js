/*****************************************************************************
 *
 * ManageBackends.js - Functions which are used by the backend management
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

function updateBackendOptions(sBackendType, backendId, sFormId) {
    var backendType = sBackendType;
    var form = document.getElementById(sFormId);
    var tbl = document.getElementById('table_'+sFormId);

    var toDelete = [];

    // Remove old backend options
    for(var i=0, len = tbl.tBodies[0].rows.length; i < len; i++) {
        if(tbl.tBodies[0].rows[i].attributes.length > 0) {
            if(tbl.tBodies[0].rows[i].id !== '') {
                if(tbl.tBodies[0].rows[i].id.search("row_") !== -1) {
                    toDelete[toDelete.length] = i;
                }
            }
        }
    }

    toDelete.reverse();
    for(var i=0, len = toDelete.length; i < len; i++) {
        tbl.deleteRow(toDelete[i]);
    }

    var lastRow = tbl.rows.length-1;

    // Add spacer row
    var row = tbl.insertRow(lastRow);
    row.id = 'row_'+lastRow;
    var label = row.insertCell(0);
    label.innerHTML = "&nbsp;";
    var input = row.insertCell(1);
    input.innerHTML = "&nbsp;";

    lastRow++;

    // When no backendId and no backendType set terminate here
    if(backendId === '' && backendType === '')
        return false;

    // Get configured values
    var oValues;
    if(backendId !== '')
        oValues = getSyncRequest(oGeneralProperties.path_server+'?mod=MainCfg&act=getBackendOptions&backendid='+backendId);

    // Get backend type from configued values when not set via function call
    // This occurs when editing backends cause only backendid is given
    if(backendType === '') {
        backendType = oValues['backendtype'];
        // Also set the value in the form
        document.backend_edit.backendtype.value = backendType;
    }

    // Fallback to default backendtype when nothing set here
    if(backendType === '')
        backendType = validMainConfig['backend']['backendtype']['default'];

    // Merge global backend options with type specific options
    var oOptions;
    oOptions = validMainConfig['backend']['options'][backendType];
    for(var sKey in validMainConfig['backend']) {
        // Exclude: backendid, backendtype, options
        if(sKey === 'backendid' || sKey === 'backendtype' || sKey === 'options')
            continue;

        oOptions[sKey] = validMainConfig['backend'][sKey];
    }

    for(var sKey in oOptions) {
        var sValue = "";

        var row = tbl.insertRow(lastRow);
        row.id = 'row_'+lastRow;
        if(oOptions[sKey].must === 1)
            add_class(row, "must");

        // Add label
        var label = row.insertCell(0);
        label.className = "tdlabel";
        label.innerHTML = sKey;

        // Add option
        var input = row.insertCell(1);
        input.className = "tdfield";

        // When editing fill the fields with configured values
        if(backendId !== '') {
            if(oValues[sKey] !== null && oValues[sKey] !== '') {
                sValue = oValues[sKey];
            }
        }

        input.innerHTML = "<input name='"+sKey+"' id='"+sKey+"' value='"+sValue+"' />";

        lastRow++;
    }
}

function check_backend_add() {
    var backendType = document.backend_add.backendtype.value;

    if (backendType == '') {
        alert(_("Backend type not selected. Please choose one."));
        return false;
    }

    // Merge global backend options with type specific options
    var oOptions;
    oOptions = validMainConfig['backend']['options'][backendType];
    for(var sKey in validMainConfig['backend']) {
        // Exclude: options
        if(sKey !== 'options') {
            oOptions[sKey] = validMainConfig['backend'][sKey];
        }
    }

    for(var i = 0, len = document.backend_add.elements.length ; i < len;i++) {
        var oField = document.backend_add.elements[i];

        if(oField.name !== 'submit') {
            // if this value is a "must" and emtpy, error
            if(oOptions[oField.name].must == '1' && oField.value == '') {
                alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~'+oField.name));
                return false;
            }
        }
    }

    for(var i = 0, len = document.backend_add.elements.length ; i < len;i++) {
        var oField = document.backend_add.elements[i];

        if(oField.name !== 'submit') {
            // Validate value format
            if(!validateValue(oField.name, oField.value, oOptions[oField.name].match)) {
                return false;
            }
        }
    }

    return true;
}

function check_backend_edit() {
    var backendType = document.backend_edit.backendtype.value;

    // Merge global backend options with type specific options
    var oOptions;
    oOptions = validMainConfig['backend']['options'][backendType];
    for(var sKey in validMainConfig['backend']) {
        // Exclude: options
        if(sKey !== 'options') {
            oOptions[sKey] = validMainConfig['backend'][sKey];
        }
    }

    for(var i = 0, len = document.backend_edit.elements.length ; i < len;i++) {
        var oField = document.backend_edit.elements[i];

        if(oField.name !== 'submit') {
            // if this value is a "must" and emtpy, error
            if(oOptions[oField.name].must == '1' && oField.value == '') {
                alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~'+oField.name));
                return false;
            }
        }
    }

    for(var i = 0, len = document.backend_edit.elements.length ; i < len;i++) {
        var oField = document.backend_edit.elements[i];

        if(oField.name !== 'submit') {
            // Validate value format
            if(!validateValue(oField.name, oField.value, oOptions[oField.name].match)) {
                return false;
            }
        }
    }

    return true;
}

function check_backend_del() {
    var form = document.backend_del;

    if(form.backendid.value == '') {
        alert('backendid not set. You have to set a backendid.');
        return false;
    }

    //FIXME: Check if backend is used in any maps/objects

    return true;
}
