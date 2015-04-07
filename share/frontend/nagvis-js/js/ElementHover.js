/*****************************************************************************
 *
 * ElementHover.js - This class realizes the hover menus
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

var g_hover_templates       = {};
var g_hover_template_childs = {};
var g_hover_urls            = {};

var ElementHover = Element.extend({
    hover_url:     null,
    template_html: null,

    update: function() {
        this.hover_url = this.obj.conf.hover_url;

        if (this.hover_url && this.hover_url !== '') {
            this.hover_url = this.hover_url.replace(getRegEx(name,
                                                    '\\['+name+'\\]', 'g'), this.obj.conf.name);

            if (this.obj.conf.type == 'service')
                this.hover_url = this.hover_url.replace(getRegEx('service_description',
                                    '\\[service_description\\]', 'g'), this.obj.conf.service_description);
        }
        this.getTemplate();
    },

    update_state: function() {
        this.render();
    },

    render: function() {
        this.getTemplate();
        if (this.template_html === null || this.template_html === true)
            return false; // template not available yet, skip rendering

        this.renderMenu();
    },

    draw: function(obj) {
        // Not rendered yet, e.g. because template was not fetched yet during
        // initial rendering. Re-try rendering now
        if (this.dom_obj === null)
            this.render();

        this.base(obj);
        this.enable();
    },

    erase: function(obj) {
        this.disable();
        this.base(obj);
    },

    lock: function() {
        this.enable();
    },

    unlock: function() {
        this.disable();
    },

    //
    // END OF PUBLIC METHODS
    //

    getTemplate: function() {
        if (this.hover_url && this.hover_url !== '') {
            if (!isset(g_hover_urls[this.hover_url])) {
                this.requestUrl();
            }
            else {
                this.template_html = g_hover_urls[this.hover_url];
            }
        }
        else {
            var name = this.obj.conf.hover_template;
            if (!isset(g_hover_templates[name])) {
                this.requestTemplate();
            }
            else if (g_hover_templates[name] !== true) {
                this.template_html = g_hover_templates[name];
                this.replaceStaticMacros();
            }
        }
    },

    handleUrl: function (urls, element_obj) {
        g_hover_urls[urls[0]['url']] = urls[0]['code'];
        element_obj.getTemplate(); // assign template to the object
    },

    requestUrl: function () {
        getAsyncRequest(oGeneralProperties.path_server+'?mod=General&act=getHoverUrl'
                       +'&url[]='+escapeUrlValues(this.hover_url), false, this.handleUrl, this);
        g_hover_urls[this.hover_url] = true; // mark as already requested
    },

    // Extracts the childs code from the hover templates
    getChildCode: function (template_html) {
        var regex = getRegEx('loopChild', "<!--\\sBEGIN\\sloop_child\\s-->(.+?)<!--\\sEND\\sloop_child\\s-->");
        var results = regex.exec(template_html);
        if (results !== null)
            return results[1];
        else
            return '';
    },

    handleTemplate: function (templates, element_obj) {
        var name = templates[0]['name'];
        var code = templates[0]['code'];

        g_hover_templates[name] = code;
        g_hover_template_childs[name] = element_obj.getChildCode(code);

        element_obj.getTemplate(); // assign template to the object
    
        // Load css file is one is available
        if (isset(templates[0]['css_file'])) {
            // This is needed for some old browsers which do no load css files
            // which are included in such fetched html code
            var oLink = document.createElement('link');
            oLink.href = templates[0]['css_file'];
            oLink.rel = 'stylesheet';
            oLink.type = 'text/css';
            document.getElementsByTagName("head")[0].appendChild(oLink);
        }
    },

    requestTemplate: function () {
        getAsyncRequest(oGeneralProperties.path_server+'?mod=General&act=getHoverTemplate'
                       +'&name[]='+escapeUrlValues(this.obj.conf.hover_template), false, this.handleTemplate, this);
        g_hover_templates[this.obj.conf.hover_template] = true; // mark as already requested
    },

    enable: function() {
        this.obj.trigger_obj.onmousemove = function(element_obj) {
            return function(event) {
                if (!isset(event)) // Fix for IE
                    event = window.event;

                // During render/drawing calls the template was not ready, so the hover menu
                // has not been drawn yet. Do it now.
                if (element_obj.dom_obj === null) {
                    element_obj.draw(element_obj.obj);
                }

                var hover_delay = parseInt(element_obj.obj.conf.hover_delay);

                // Only show up hover menu when no context menu is opened
                // and only handle the events when no timer is in schedule at the moment to
                // prevent strange movement effects when the timer has finished
                if (!dragging() && !contextOpen() && _hoverTimer === null) {
                    if (hover_delay && !hoverOpen())
                        _hoverTimer = setTimeout(function(x, y, obj_id) {
                            return function() {
                                hoverShow(x, y, obj_id);
                            };
                        }(event.clientX, event.clientY, obj_id), hover_delay*1000);
                    else
                        hoverShow(event.clientX, event.clientY, element_obj.obj.conf.object_id);
                }
            };
        }(this);

        this.obj.trigger_obj.onmouseout = function(obj_id) {
            return function(e) {
                hoverHide(obj_id);
            };
        }(this.obj.conf.object_id);
    },

    disable: function() {
        this.obj.trigger_obj.onmousemove = null;
        this.obj.trigger_obj.onmouseout  = null;
    },

    renderMenu: function () {
        var template_html = this.template_html;
        if (!this.obj.conf.hover_url || this.obj.conf.hover_url === '') {
            // Replace dynamic (state dependent) macros
            if (isset(this.obj.conf.hover_template))
                template_html = this.replaceDynamicMacros(template_html);
        }

        // Only create a new div when the hover menu does not exist
        var hoverMenu = document.getElementById(this.obj.conf.object_id+'-hover');
        if(!hoverMenu) {
            // Create hover menu div
            var hoverMenu = document.createElement('div');
            this.dom_obj = hoverMenu;
            hoverMenu.setAttribute('id', this.obj.conf.object_id+'-hover');
            hoverMenu.setAttribute('class', 'hover');
            hoverMenu.setAttribute('className', 'hover');
            hoverMenu.style.zIndex = '1000';
            hoverMenu.style.display = 'none';
            hoverMenu.style.position = 'absolute';
            hoverMenu.style.overflow = 'visible';
        }

        // Append template code to hover menu div
        hoverMenu.innerHTML = template_html;

        // Display the hover menu again, when it was open before re-rendering
        if (this.hoverX !== null)
            hoverShow(this.hoverX, this.hoverY, this.obj.conf.object_id);
    },

    replaceChildMacros: function (template_html) {
        var childsHtmlCode = '';
        var regex = '';
    
        var rowHtmlCode = g_hover_template_childs[this.obj.conf.hover_template];
        if(typeof(rowHtmlCode) != 'undefined' && rowHtmlCode != '' && this.obj.members && this.obj.members.length > 0) {
            // Loop all child objects until all looped or the child limit is reached
            for(var i = 0, len1 = this.obj.conf.hover_childs_limit, len2 = this.obj.members.length;
                (len1 == -1 || (len1 >= 0 && i <= len1)) && i < len2; i++) {
                if(len1 == -1 || (len1 >= 0 && i < len1)) {
                    // Try to catch some error
                    if(!this.obj.members[i].conf) {
                        eventlog("hover-parsing", "critical",
                                 "Problem while parsing child in hover template (t:" & this.obj.conf.type & " n:" & this.obj.conf.name &")");
                    } else {
                        if(this.obj.members[i].conf.type !== 'textbox' && this.obj.members[i].conf.type !== 'shape') {
                            // Children need to know where they belong
                            this.obj.members[i].parent_type = this.obj.conf.type;
                            this.obj.members[i].parent_name = this.obj.conf.name;
    
                            childsHtmlCode += this.replaceMacrosOfChild(this.obj.members[i], rowHtmlCode);
                        }
                    }
                } else {
                    // Create an end line which shows the number of hidden child items
                    var oMember = {
                        'conf': {
                            'type': 'host',
                            'name': '',
                            'summary_state': '',
                            'summary_output': (this.obj.conf.num_members - this.obj.conf.hover_childs_limit) + ' ' + _('more items...'),
                            '<!--\\sBEGIN\\sservicegroup_child\\s-->.+?<!--\\sEND\\sservicegroup_child\\s-->': ''
                        }
                    };
    
                    childsHtmlCode += this.replaceMacrosOfChild(oMember, rowHtmlCode);
                }
            }
        }
    
        if(childsHtmlCode != '')
            regex = getRegEx('loopChild', "<!--\\sBEGIN\\sloop_child\\s-->(.+?)<!--\\sEND\\sloop_child\\s-->");
        else
            regex = getRegEx('loopChildEmpty', '<!--\\sBEGIN\\schilds\\s-->.+?<!--\\sEND\\schilds\\s-->');
    
        template_html = template_html.replace(regex, childsHtmlCode);
        return template_html;
    },

    replaceMacrosOfChild: function (member_obj, template_html) {
        var oMacros = {
            'obj_summary_state'  : member_obj.conf.summary_state,
            'obj_summary_output' : member_obj.conf.summary_output,
            'obj_display_name'   : member_obj.conf.display_name
        }

        if (member_obj.conf.summary_problem_has_been_acknowledged && member_obj.conf.summary_problem_has_been_acknowledged == 1)
            oMacros.obj_summary_acknowledged = '(Acknowledged)';

        if (member_obj.conf.summary_in_downtime && member_obj.conf.summary_in_downtime == 1)
            oMacros.obj_summary_in_downtime = '(Downtime)';

        if (member_obj.conf.summary_stale)
            oMacros.obj_summary_stale = '(Stale)';

        // On child service objects in hover menu replace obj_name with
        // service_description
        if (member_obj.conf.type === 'service')
            oMacros.obj_name = member_obj.conf.service_description;
        else
            oMacros.obj_name = member_obj.conf.name;

        if ((member_obj.parent_type === 'servicegroup' || member_obj.parent_type === 'dyngroup')
            && member_obj.conf.type === 'service')
            oMacros.obj_name1 = member_obj.conf.name;
        else {
            var regex = getRegEx('section-sgchild', '<!--\\sBEGIN\\sservicegroup_child\\s-->.+?<!--\\sEND\\sservicegroup_child\\s-->', 'gm');
            if (template_html.search(regex) !== -1)
                template_html = template_html.replace(regex, '');
        }

        // Replace all normal macros
        template_html = template_html.replace(/\[(\w*)\]/g, function() {
            return oMacros[ arguments[1] ] || "";
        });
        return template_html;
    },

    replaceDynamicMacros: function (template_html) {
        var oMacros = {};
    
        if (oPageProperties.view_type === 'map')
            oMacros.map_name = oPageProperties.map_name;
    
        oMacros.last_status_refresh = date(oGeneralProperties.date_format, this.obj.lastUpdate);
    
        oMacros.obj_state = this.obj.conf.state;
        oMacros.obj_summary_state = this.obj.conf.summary_state;
    
        if (this.obj.conf.summary_problem_has_been_acknowledged && this.obj.conf.summary_problem_has_been_acknowledged === 1)
            oMacros.obj_summary_acknowledged = '(Acknowledged)';
    
        if (this.obj.conf.problem_has_been_acknowledged && this.obj.conf.problem_has_been_acknowledged === 1)
            oMacros.obj_acknowledged = '(Acknowledged)';
    
        if (this.obj.conf.summary_in_downtime && this.obj.conf.summary_in_downtime === 1)
            oMacros.obj_summary_in_downtime = '(Downtime)';
    
        if (this.obj.conf.in_downtime && this.obj.conf.in_downtime === 1)
            oMacros.obj_in_downtime = '(Downtime)';
    
        if (this.obj.conf.summary_stale)
            oMacros.obj_summary_stale = '(Stale)';
    
        if (this.obj.conf.stale)
            oMacros.obj_stale = '(Stale)';
    
        oMacros.obj_output = this.obj.conf.output;
        oMacros.obj_summary_output = this.obj.conf.summary_output;
    
        // Macros which are only for services and hosts
        if (this.obj.conf.type === 'host' || this.obj.conf.type === 'service') {
            oMacros.obj_last_check = this.obj.conf.last_check;
            oMacros.obj_next_check = this.obj.conf.next_check;
            oMacros.obj_state_type = this.obj.conf.state_type;
            oMacros.obj_current_check_attempt = this.obj.conf.current_check_attempt;
            oMacros.obj_max_check_attempts = this.obj.conf.max_check_attempts;
            oMacros.obj_last_state_change = this.obj.conf.last_state_change;
            oMacros.obj_last_hard_state_change = this.obj.conf.last_hard_state_change;
            oMacros.obj_state_duration = this.obj.conf.state_duration;
            oMacros.obj_perfdata = this.obj.conf.perfdata;
        }
    
        // On a update the image url replacement is easier. Just replace the old
        // timestamp with the current
        if (this.obj.firstUpdate !== null) {
            var regex = getRegEx('img_timestamp', '_t='+this.obj.firstUpdate, 'g');
            // Search before matching - saves some time
            if(template_html.search(regex) !== -1)
                template_html = template_html.replace(regex, '_t='+this.obj.lastUpdate);
        }
    
        // Replace child macros
        if (this.obj.conf.hover_childs_show && this.obj.conf.hover_childs_show == '1')
            template_html = this.replaceChildMacros(template_html);
    
        // Replace all normal macros
        template_html = template_html.replace(/\[(\w*)\]/g, function() {
            return oMacros[ arguments[1] ] || "";
        });
        return template_html;
    },

    replaceStaticMacros: function() {
        var oMacros = {};
        var oSectionMacros = {};
    
        if(this.obj.conf.type && this.obj.conf.type != '')
            oMacros.obj_type = this.obj.conf.type;
    
        // Replace language strings
        oMacros.lang_obj_type = this.obj.conf.lang_obj_type;
        oMacros.lang_name = this.obj.conf.lang_name;
        oMacros.lang_child_name = this.obj.conf.lang_child_name;
        oMacros.lang_child_name1 = this.obj.conf.lang_child_name1;
    
        // On child service objects in hover menu replace obj_name with
        // service_description
        oMacros.obj_name = this.obj.conf.name;
    
        if(this.obj.conf.alias && this.obj.conf.alias !== '') {
            oMacros.obj_alias        = this.obj.conf.alias;
            oMacros.obj_alias_braces = ' (' +this.obj.conf.alias + ')';
        } else {
            oMacros.obj_alias        = '';
            oMacros.obj_alias_braces = '';
        }
    
        if(this.obj.conf.display_name && this.obj.conf.display_name !== '')
            oMacros.obj_display_name = this.obj.conf.display_name;
        else
            oMacros.obj_display_name = '';
    
        if(this.obj.conf.notes && this.obj.conf.notes !== '')
            oMacros.obj_notes = this.obj.conf.notes;
        else
            oMacros.obj_notes = '';
    
        if(this.obj.conf.type !== 'map') {
            oMacros.obj_backendid = this.obj.conf.backend_id;
            oMacros.obj_backend_instancename = this.obj.conf.backend_instancename;
            oMacros.html_cgi = this.obj.conf.htmlcgi;
            oMacros.custom_1 = this.obj.conf.custom_1;
            oMacros.custom_2 = this.obj.conf.custom_2;
            oMacros.custom_3 = this.obj.conf.custom_3;
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
        if(this.obj.conf.type === 'host' || this.obj.conf.type === 'service') {
            oMacros.obj_address = this.obj.conf.address;
            oMacros.obj_tags    = this.obj.conf.tags.join(', ');
    
            // Add taggroup information
            for (var group_id in this.obj.conf.taggroups) {
                var group = this.obj.conf.taggroups[group_id];
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
    
        if(this.obj.conf.type === 'service') {
            oMacros.service_description = this.obj.conf.service_description;
            oMacros.pnp_hostname = this.obj.conf.name.replace(/\s/g,'%20');
            oMacros.pnp_service_description = this.obj.conf.service_description.replace(/\s/g,'%20');
        } else
            oSectionMacros.service = '<!--\\sBEGIN\\sservice\\s-->.+?<!--\\sEND\\sservice\\s-->';
    
        // Macros which are only for hosts
        if(this.obj.conf.type === 'host')
            oMacros.pnp_hostname = this.obj.conf.name.replace(' ','%20');
        else
            oSectionMacros.host = '<!--\\sBEGIN\\shost\\s-->.+?<!--\\sEND\\shost\\s-->';
    
        // Replace servicegroup sections when not servicegroup object
        if(this.obj.conf.type !== 'servicegroup' && !(this.obj.conf.type === 'dyngroup' && this.obj.conf.object_types == 'service')) {
            oSectionMacros.servicegroup = '<!--\\sBEGIN\\sservicegroup\\s-->.+?<!--\\sEND\\sservicegroup\\s-->';
        }
    
        // Replace hostgroup sections when not hostgroup object
        if(this.obj.conf.type !== 'hostgroup')
            oSectionMacros.hostgroup = '<!--\\sBEGIN\\shostgroup\\s-->.+?<!--\\sEND\\shostgroup\\s-->';
    
        // Replace map sections when not map object
        if(this.obj.conf.type !== 'map')
            oSectionMacros.map = '<!--\\sBEGIN\\smap\\s-->.+?<!--\\sEND\\smap\\s-->';
    
        // Replace child section when unwanted
        if(this.obj.conf.hover_childs_show && this.obj.conf.hover_childs_show != '1')
            oSectionMacros.childs = '<!--\\sBEGIN\\schilds\\s-->.+?<!--\\sEND\\schilds\\s-->';
    
        // Loop and replace all unwanted section macros
        for (var key in oSectionMacros) {
            var regex = getRegEx('section-'+key, oSectionMacros[key], 'gm');
            if (this.template_html.search(regex) !== -1)
                this.template_html = this.template_html.replace(regex, '');
            regex = null;
        }
    
        // Loop and replace all normal macros
        this.template_html = this.template_html.replace(/\[(\w*)\]/g, function() {
            return oMacros[ arguments[1] ] || '['+arguments[1]+']';
        });
    
        // Re-add the clean child code
        // This workaround is needed cause the obj_name macro is replaced
        // by the parent objects macro in current progress
        var regex = getRegEx('loopChild', "<!--\\sBEGIN\\sloop_child\\s-->(.+?)<!--\\sEND\\sloop_child\\s-->");
        if(this.template_html.search(regex) !== -1)
            this.template_html = this.template_html.replace(regex, '<!-- BEGIN loop_child -->'
                                                                   + g_hover_template_childs[this.obj.hover_template]
                                                                   + '<!-- END loop_child -->');
    
        // Search for images and append current timestamp to src (prevent caching of
        // images e.a. when some graphs should be fresh)
        var regex = getRegEx('img', "<img.*src=['\"]?([^>'\"]*)['\"]?");
        var results = regex.exec(this.template_html);
        if(results !== null) {
            for(var i = 0, len = results.length; i < len; i=i+2) {
                // Replace src value
                var sTmp = results[i].replace(results[i+1], results[i+1]+"&_t="+this.obj.firstUpdate);
    
                // replace image code
                this.template_html = this.template_html.replace(results[i], sTmp);
            }
        }
    }

});
