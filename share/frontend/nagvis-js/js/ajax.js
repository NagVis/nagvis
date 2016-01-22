/*****************************************************************************
 *
 * ajax.js - Functions for handling NagVis Ajax requests
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

function call_ajax(url, args)
{
    args = merge_args({
        add_ajax_id      : true,
        response_handler : null,
        error_handler    : function(status_code, response, handler_data) {
            frontendMessage(response, 'serverError');
        },
        handler_data     : null,
        method           : "GET",
        post_data        : null,
        sync             : false,
        decode_json      : true,
    }, args);

    var AJAX = window.XMLHttpRequest ? new XMLHttpRequest()
                                     : new ActiveXObject("Microsoft.XMLHTTP");
    if (!AJAX)
        return null;

    if (args.add_ajax_id) {
        url += url.indexOf('\?') !== -1 ? "&" : "?";
        url += "_ajaxid="+iNow;
    }

    AJAX.open(args.method, url, !args.sync);

    if (args.method == "POST") {
        // Set post specific options. request might be a FormData object. In this case
        // the request is not using form-urlencoded data, instead it is automatically
        // set to multipart/form-data
        if (typeof args.post_data !== 'object')
            AJAX.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    if (!args.sync) {
        AJAX.onreadystatechange = function() {
            if (AJAX && AJAX.readyState == 4) {
                if (AJAX.status == 200) {
                    var response = AJAX.responseText;
                    if (args.decode_json) {
                        try {
                            response = JSON.parse(response);
                        } catch(e) {
                            args.error_handler(AJAX.status, {
                                'type'    : 'error',
                                'title'   : 'Error: Syntax Error',
                                'message' : "Invalid JSON response<div class=details>\nTime: "
                                      + iNow + "<br />\nURL: " + url.replace(/</g, '&lt;') + "<br />\n"
                                      + "Response: <code>" + response + '</code></div>'
                            }, 0, 'jsonError');
                            return '';
                        }

                        // The server might return a json object which is representing
                        // an error which happened on the server.
                        // We adapt the old behaviour of showing a frontend message for
                        // all errors here by using the default error handler. Callers
                        // which register their own error handlers need to deal with
                        // this kind of error data on their own.
                        if (isset(response.type) && isset(response.message)) {
                            args.error_handler(AJAX.status, response, args.handler_data);
                            return '';
                        }
                        // in case this is no error remove all frontend messages of type
                        // serverError which might have been shown by the default
                        // args.error_handler.
                        frontendMessageRemove('serverError');
                    }

                    if (args.response_handler)
                        args.response_handler(response, args.handler_data);
                }
                else if (AJAX.status == 401) {
                    // This is reached when someone is not authenticated anymore
                    // but has some webservices running which are still fetching
                    // infos via AJAX. Reload the whole frameset or only the
                    // single page in that case.
                    document.location.reload();
                }
                else if (AJAX.status == 0) {
                    // this happens when a user aborts currently running ajax
                    // requests by reloading the page. simply ignore
                }
                else {
                    args.error_handler(AJAX.status, null, args.handler_data);
                }
            }
        }
    }

    AJAX.send(args.post_data);
    return AJAX;
}

/**
 * Parses all values from the given form to a string or form data object
 * which is then be used as request data in ajax queries
 */
function getFormParams(formId, skipHelperFields) {
    if (window.FormData) {
        var data = new FormData();
        var formdata = true;
    } else {
        var formdata = false;
        var data = '';
    }

    var add_data = function(key, val) {
        if (formdata)
            data.append(key, val);
        else
            data += key + "=" + escapeUrlValues(val) + "&";
    };

    // Read form contents
    var oForm = document.getElementById(formId);
    if (typeof oForm === 'undefined')
        return data;

    // Get relevant input elements
    var aFields = oForm.getElementsByTagName('input');
    for (var i = 0, len = aFields.length; i < len; i++) {
        // Filter helper fields (if told to do so)
        if (skipHelperFields && aFields[i].name.charAt(0) === '_')
            continue;

        // Skip options which use the default value and where the value has
        // not been set before
        var oFieldDefault    = document.getElementById('_'+aFields[i].name);
        if (aFields[i] && oFieldDefault && !document.getElementById('_conf_'+aFields[i].name)) {
            if (aFields[i].value === oFieldDefault.value) {
                continue;
            }
        }
        oFieldDefault = null;

        if (aFields[i].type == "hidden"
            || aFields[i].type == "text"
            || aFields[i].type == "password"
            || aFields[i].type == "submit") {
            add_data(aFields[i].name, aFields[i].value);
        }
        else if (aFields[i].type == "checkbox") {
            if (aFields[i].checked) {
                add_data(aFields[i].name, aFields[i].value);
            }
            else {
                add_data(aFields[i].name, '');
            }
        }
        else if (aFields[i].type == "radio") {
            if (aFields[i].checked) {
                add_data(aFields[i].name, aFields[i].value);
            }
        }
        else if (aFields[i].type == "file") {
            // Now handle the file upload elements (which lead to an error when not
            // using the form data mechanism)
            if (!formdata) {
                throw new Error('File upload not supported with your browser. '
                               +'This form can only be used when using a browser '
                               +'which suports javascript file uploads (FormData).');
            }

            if (aFields[i].files.length > 0) {
                var file = aFields[i].files[0];
                data.append(aFields[i].name, file, file.name);
            }
        }

    }

    // Get relevant select elements
    aFields = oForm.getElementsByTagName('select');
    for(var i = 0, len = aFields.length; i < len; i++) {
        // Filter helper fields (NagVis WUI specific)
        if (skipHelperFields && aFields[i].name.charAt(0) === '_')
            continue;

        // Skip options which use the default value
        var oFieldDefault = document.getElementById('_'+aFields[i].name);
        if(aFields[i] && oFieldDefault) {
            if(aFields[i].value === oFieldDefault.value) {
                continue;
            }
        }
        oFieldDefault = null;

        // Can't use the selectedIndex when using select fields with multiple
        if(!aFields[i].multiple || aFields[i].multiple !== true) {
            // Skip fields where nothing is selected
            if (aFields[i].selectedIndex != -1) {
                add_data(aFields[i].name, aFields[i].options[aFields[i].selectedIndex].value);
            }
        } else {
            for (var a = 0; a < aFields[i].options.length; a++) {
                // Only add selected options
                if (aFields[i].options[a].selected == true) {
                    add_data(aFields[i].name+'[]', aFields[i].options[a].value);
                }
            }
        }
    }

    aFields = null;
    oForm = null;

    return data;
}
