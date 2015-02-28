/*****************************************************************************
 *
 * hover.js - Function collection for handling the hover menu in NagVis
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

/**
 * Returns the HTML code of a hover template
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */

function replaceHoverTemplateChildMacros(oObj, sTemplateCode) {
    var mapName = '';
    var childsHtmlCode = '';
    var regex = '';

    if(typeof(oPageProperties) != 'undefined' && oPageProperties != null)
        mapName = oPageProperties.map_name;

    var rowHtmlCode = oHoverTemplatesChild[oObj.conf.hover_template];
    if(typeof(rowHtmlCode) != 'undefined' && rowHtmlCode != '' && oObj.members && oObj.members.length > 0) {
        // Loop all child objects until all looped or the child limit is reached
        for(var i = 0, len1 = oObj.conf.hover_childs_limit, len2 = oObj.members.length;
            (len1 == -1 || (len1 >= 0 && i <= len1)) && i < len2; i++) {
            if(len1 == -1 || (len1 >= 0 && i < len1)) {
                // Try to catch some error
                if(!oObj.members[i].conf) {
                    eventlog("hover-parsing", "critical",
                             "Problem while parsing child in hover template (t:" & oObj.conf.type & " n:" & oObj.conf.name &")");
                } else {
                    if(oObj.members[i].conf.type !== 'textbox' && oObj.members[i].conf.type !== 'shape') {
                        // Children need to know where they belong
                        oObj.members[i].parent_type = oObj.conf.type;
                        oObj.members[i].parent_name = oObj.conf.name;

                        childsHtmlCode += replaceHoverTemplateMacrosChild(oObj.members[i], rowHtmlCode);
                    }
                }
            } else {
                // Create an end line which shows the number of hidden child items
                var oMember = {
                    'conf': {
                        'type': 'host',
                        'name': '',
                        'summary_state': '',
                        'summary_output': (oObj.conf.num_members - oObj.conf.hover_childs_limit) + ' ' + _('more items...'),
                        '<!--\\sBEGIN\\sservicegroup_child\\s-->.+?<!--\\sEND\\sservicegroup_child\\s-->': ''
                    }
                };

                childsHtmlCode += replaceHoverTemplateMacrosChild(oMember, rowHtmlCode);
            }
        }
    }

    if(childsHtmlCode != '')
        regex = getRegEx('loopChild', "<!--\\sBEGIN\\sloop_child\\s-->(.+?)<!--\\sEND\\sloop_child\\s-->");
    else
        regex = getRegEx('loopChildEmpty', '<!--\\sBEGIN\\schilds\\s-->.+?<!--\\sEND\\schilds\\s-->');

    sTemplateCode = sTemplateCode.replace(regex, childsHtmlCode);
    regex = null;
    childsHtmlCode = null;
    rowHtmlCode = null;

    return sTemplateCode;
}

function replaceHoverTemplateMacrosChild(oObj, sTemplateCode) {
    // Try to catch some error
    if(oObj.conf === null || oObj.conf === undefined) {
        eventlog("hover-parsing", "critical", "Problem while parsing hover template");
        return sTemplateCode;
    }

    var oMacros = {
        'obj_summary_state':  oObj.conf.summary_state,
        'obj_summary_output': oObj.conf.summary_output
    }

    if(oObj.conf.summary_problem_has_been_acknowledged && oObj.conf.summary_problem_has_been_acknowledged == 1)
        oMacros.obj_summary_acknowledged = '(Acknowledged)';

    if(oObj.conf.summary_in_downtime && oObj.conf.summary_in_downtime == 1)
        oMacros.obj_summary_in_downtime = '(Downtime)';

    if(oObj.conf.summary_stale)
        oMacros.obj_summary_stale = '(Stale)';

    // On child service objects in hover menu replace obj_name with
    // service_description
    if(oObj.conf.type === 'service')
        oMacros.obj_name = oObj.conf.service_description;
    else
        oMacros.obj_name = oObj.conf.name;

    if((oObj.parent_type === 'servicegroup' || oObj.parent_type === 'dyngroup') && oObj.conf.type === 'service')
        oMacros.obj_name1 = oObj.conf.name;
    else {
        var regex = getRegEx('section-sgchild', '<!--\\sBEGIN\\sservicegroup_child\\s-->.+?<!--\\sEND\\sservicegroup_child\\s-->', 'gm');
        if(sTemplateCode.search(regex) !== -1)
            sTemplateCode = sTemplateCode.replace(regex, '');
        regex = null;
    }

    // Replace all normal macros
    sTemplateCode = sTemplateCode.replace(/\[(\w*)\]/g, function(){ return oMacros[ arguments[1] ] || "";});
    oMacros = null;

    return sTemplateCode;
}

function replaceHoverTemplateDynamicMacros(oObj) {
    var oMacros = {};

    if(isset(oPageProperties) && oPageProperties.view_type === 'map')
        oMacros.map_name = oPageProperties.map_name;

    oMacros.last_status_refresh = date(oGeneralProperties.date_format, oObj.lastUpdate);

    oMacros.obj_state = oObj.conf.state;
    oMacros.obj_summary_state = oObj.conf.summary_state;

    if(oObj.conf.summary_problem_has_been_acknowledged && oObj.conf.summary_problem_has_been_acknowledged === 1)
        oMacros.obj_summary_acknowledged = '(Acknowledged)';

    if(oObj.conf.problem_has_been_acknowledged && oObj.conf.problem_has_been_acknowledged === 1)
        oMacros.obj_acknowledged = '(Acknowledged)';

    if(oObj.conf.summary_in_downtime && oObj.conf.summary_in_downtime === 1)
        oMacros.obj_summary_in_downtime = '(Downtime)';

    if(oObj.conf.in_downtime && oObj.conf.in_downtime === 1)
        oMacros.obj_in_downtime = '(Downtime)';

    if(oObj.conf.summary_stale)
        oMacros.obj_summary_stale = '(Stale)';

    if(oObj.conf.stale)
        oMacros.obj_stale = '(Stale)';

    oMacros.obj_output = oObj.conf.output;
    oMacros.obj_summary_output = oObj.conf.summary_output;

    // Macros which are only for services and hosts
    if(oObj.conf.type === 'host' || oObj.conf.type === 'service') {
        oMacros.obj_last_check = oObj.conf.last_check;
        oMacros.obj_next_check = oObj.conf.next_check;
        oMacros.obj_state_type = oObj.conf.state_type;
        oMacros.obj_current_check_attempt = oObj.conf.current_check_attempt;
        oMacros.obj_max_check_attempts = oObj.conf.max_check_attempts;
        oMacros.obj_last_state_change = oObj.conf.last_state_change;
        oMacros.obj_last_hard_state_change = oObj.conf.last_hard_state_change;
        oMacros.obj_state_duration = oObj.conf.state_duration;
        oMacros.obj_perfdata = oObj.conf.perfdata;
    }

    var sTemplateCode = oObj.hover_template_code;

    // On a update the image url replacement is easier. Just replace the old
    // timestamp with the current
    if(oObj.firstUpdate !== null) {
        var regex = getRegEx('img_timestamp', '_t='+oObj.firstUpdate, 'g');
        // Search before matching - saves some time
        if(sTemplateCode.search(regex) !== -1)
            sTemplateCode = sTemplateCode.replace(regex, '_t='+oObj.lastUpdate);
        regex = null;
    }

    // Replace child macros
    if(oObj.conf.hover_childs_show && oObj.conf.hover_childs_show == '1')
        sTemplateCode = replaceHoverTemplateChildMacros(oObj, sTemplateCode);

    // Replace all normal macros
    sTemplateCode = sTemplateCode.replace(/\[(\w*)\]/g, function(){ return oMacros[ arguments[1] ] || "";});
    oMacros = null;
    return sTemplateCode;
}

function replaceHoverTemplateStaticMacros(oObj, sTemplateCode) {
    var oMacros = {};
    var oSectionMacros = {};

    // Try to catch some error
    if(oObj.conf === null)
        eventlog("hover-parsing", "critical", "Problem while parsing hover template");

    if(oObj.conf.type && oObj.conf.type != '')
        oMacros.obj_type = oObj.conf.type;

    // Replace language strings
    oMacros.lang_obj_type = oObj.conf.lang_obj_type;
    oMacros.lang_name = oObj.conf.lang_name;
    oMacros.lang_child_name = oObj.conf.lang_child_name;
    oMacros.lang_child_name1 = oObj.conf.lang_child_name1;

    // On child service objects in hover menu replace obj_name with
    // service_description
    oMacros.obj_name = oObj.conf.name;

    if(oObj.conf.alias && oObj.conf.alias !== '') {
        oMacros.obj_alias        = oObj.conf.alias;
        oMacros.obj_alias_braces = ' (' +oObj.conf.alias + ')';
    } else {
        oMacros.obj_alias        = '';
        oMacros.obj_alias_braces = '';
    }

    if(oObj.conf.display_name && oObj.conf.display_name !== '')
        oMacros.obj_display_name = oObj.conf.display_name;
    else
        oMacros.obj_display_name = '';

    if(oObj.conf.notes && oObj.conf.notes !== '')
        oMacros.obj_notes = oObj.conf.notes;
    else
        oMacros.obj_notes = '';

    if(oObj.conf.type !== 'map') {
        oMacros.obj_backendid = oObj.conf.backend_id;
        oMacros.obj_backend_instancename = oObj.conf.backend_instancename;
        oMacros.html_cgi = oObj.conf.htmlcgi;
        oMacros.custom_1 = oObj.conf.custom_1;
        oMacros.custom_2 = oObj.conf.custom_2;
        oMacros.custom_3 = oObj.conf.custom_3;
    } else {
        // Remove the macros in map objects
        oMacros.obj_backendid = '';
        oMacros.obj_backend_instancename = '';
        oMacros.html_cgi = '';
        oMacros.custom_1 = '';
        oMacros.custom_2 = '';
        oMacros.custom_3 = '';
    }

    // Macros which are only for services and hosts
    if(oObj.conf.type === 'host' || oObj.conf.type === 'service') {
        oMacros.obj_address = oObj.conf.address;
        oMacros.obj_tags    = oObj.conf.tags.join(', ');

        // Add taggroup information
        for (var group_id in oObj.conf.taggroups) {
            var group = oObj.conf.taggroups[group_id];
            oMacros['obj_taggroup_' + group_id + '_title'] = group.title;
            oMacros['obj_taggroup_' + group_id + '_topic'] = group.topic;
            if (group.value) {
                oMacros['obj_taggroup_' + group_id + '_value']       = group.value[0];
                oMacros['obj_taggroup_' + group_id + '_value_title'] = group.value[1];
            } else {
                oMacros['obj_taggroup_' + group_id + '_value']       = '';
                oMacros['obj_taggroup_' + group_id + '_value_title'] = '';
            }
        }
    } else {
        oMacros.obj_address = '';
        oMacros.obj_tags    = '';
    }

    if (oMacros.obj_tags == '') {
        oSectionMacros.has_tags = '<!--\\sBEGIN\\shas_tags\\s-->.+?<!--\\sEND\\shas_tags\\s-->';
    }

    if(oObj.conf.type === 'service') {
        oMacros.service_description = oObj.conf.service_description;
        oMacros.pnp_hostname = oObj.conf.name.replace(/\s/g,'%20');
        oMacros.pnp_service_description = oObj.conf.service_description.replace(/\s/g,'%20');
    } else
        oSectionMacros.service = '<!--\\sBEGIN\\sservice\\s-->.+?<!--\\sEND\\sservice\\s-->';

    // Macros which are only for hosts
    if(oObj.conf.type === 'host')
        oMacros.pnp_hostname = oObj.conf.name.replace(' ','%20');
    else
        oSectionMacros.host = '<!--\\sBEGIN\\shost\\s-->.+?<!--\\sEND\\shost\\s-->';

    // Replace servicegroup sections when not servicegroup object
    if(oObj.conf.type !== 'servicegroup' && !(oObj.conf.type === 'dyngroup' && oObj.conf.object_types == 'service')) {
        oSectionMacros.servicegroup = '<!--\\sBEGIN\\sservicegroup\\s-->.+?<!--\\sEND\\sservicegroup\\s-->';
    }

    // Replace hostgroup sections when not hostgroup object
    if(oObj.conf.type !== 'hostgroup')
        oSectionMacros.hostgroup = '<!--\\sBEGIN\\shostgroup\\s-->.+?<!--\\sEND\\shostgroup\\s-->';

    // Replace map sections when not map object
    if(oObj.conf.type !== 'map')
        oSectionMacros.map = '<!--\\sBEGIN\\smap\\s-->.+?<!--\\sEND\\smap\\s-->';

    // Replace child section when unwanted
    if(oObj.conf.hover_childs_show && oObj.conf.hover_childs_show != '1')
        oSectionMacros.childs = '<!--\\sBEGIN\\schilds\\s-->.+?<!--\\sEND\\schilds\\s-->';

    // Loop and replace all unwanted section macros
    for (var key in oSectionMacros) {
        var regex = getRegEx('section-'+key, oSectionMacros[key], 'gm');
        if(sTemplateCode.search(regex) !== -1)
            sTemplateCode = sTemplateCode.replace(regex, '');
        regex = null;
    }
    oSectionMacros = null;

    // Loop and replace all normal macros
    sTemplateCode = sTemplateCode.replace(/\[(\w*)\]/g, function(){ return oMacros[ arguments[1] ] || '['+arguments[1]+']';});

    oMacros = null;

    // Re-add the clean child code
    // This workaround is needed cause the obj_name macro is replaced
    // by the parent objects macro in current progress
    var regex = getRegEx('loopChild', "<!--\\sBEGIN\\sloop_child\\s-->(.+?)<!--\\sEND\\sloop_child\\s-->");
    if(sTemplateCode.search(regex) !== -1)
        sTemplateCode = sTemplateCode.replace(regex, '<!-- BEGIN loop_child -->'+oHoverTemplatesChild[oObj.hover_template]+'<!-- END loop_child -->');
    regex = null;

    // Search for images and append current timestamp to src (prevent caching of
    // images e.a. when some graphs should be fresh)
    var regex = getRegEx('img', "<img.*src=['\"]?([^>'\"]*)['\"]?");
    var results = regex.exec(sTemplateCode);
    if(results !== null) {
        for(var i = 0, len = results.length; i < len; i=i+2) {
            var sTmp;

            // Replace src value
            sTmp = results[i].replace(results[i+1], results[i+1]+"&_t="+oObj.firstUpdate);

            // replace image code
            sTemplateCode = sTemplateCode.replace(results[i], sTmp);

            sTmp = null;
        }
    }
    results = null;
    regex = null;

    return sTemplateCode;
}

function displayHoverMenu(event, objId, iHoverDelay) {
    if(!event) {
        alert('ERROR: The event object is not defined.');
        return;
    }
    if(!objId) {
        alert('ERROR: The object id is not defined.');
        return;
    }

    // Only show up hover menu when no context menu is opened
    // and only handle the events when no timer is in schedule at the moment to
    // prevent strange movement effects when the timer has finished
    if(!dragging() && !contextOpen() && _hoverTimer === null) {
        if(iHoverDelay && iHoverDelay != "0" && !hoverOpen())
            _hoverTimer = setTimeout('hoverShow('+event.clientX+', '+event.clientY+', "'+objId+'")', parseInt(iHoverDelay)*1000);
        else
            hoverShow(event.clientX, event.clientY, objId);
    }
}
