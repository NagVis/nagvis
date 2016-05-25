/*****************************************************************************
 *
 * ElementHover.js - This class realizes the hover menus
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

var g_hover_templates       = {};
var g_hover_template_childs = {};
var g_hover_urls            = {};
var g_hover_open            = null;

// Hides the currently open hover menu
function hoverHide() {
    if (g_hover_open !== null)
        g_hover_open.hide();
}

var ElementHover = Element.extend({
    hover_url      : null,  // configured url with eventual replaced macros
    template_html  : null,  // hover HTML code with replaced config related macros
    coords         : null,  // list of x, y coordinates of the hover menu top left corner
    show_timer     : null,  // JS timer when hover delay is used
    spacing        : 5,     // px from screen border
    min_width      : 400,   // px minimum width
    enabled        : false, // when true the event handlers are enabled

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

    updateAttrs: function(only_state) {
        this.render();
    },

    render: function() {
        this.getTemplate();

        if (this.obj.bIsLocked)
            this.enable();

        if (this.template_html === null || this.template_html === true)
            return false; // template not available yet, skip rendering

        // don't render during normal render calls. We
        // want to keep the number of DOM objects low, so only render
        // them when being unlocked for the first time
        // But when the object has already been rendered, re-render the object
        // during all render() calls
        if (this.dom_obj)
            this._render();
    },

    draw: function() {
        if (this.obj.bIsLocked) {
            // Not rendered yet, e.g. because template was not fetched yet during
            // initial rendering. Re-try rendering now
            if (!this.dom_obj)
                this._render();

            this.base();
        }
    },

    erase: function() {
        this.disable();
        this.base();
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

    _render: function() {
        this.getTemplate();
        if (this.template_html === null || this.template_html === true) {
            return false; // template not available yet, skip rendering
        }
        this.renderMenu();
    },

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

    handleUrl: function (urls) {
        g_hover_urls[urls[0]['url']] = urls[0]['code'];
        this.getTemplate(); // assign template to the object
    },

    requestUrl: function () {
        call_ajax(oGeneralProperties.path_server+'?mod=General&act=getHoverUrl'
                  +'&url[]='+escapeUrlValues(this.hover_url), {
            response_handler: this.handleUrl.bind(this)
        });
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

    handleTemplate: function (templates) {
        var name = templates[0]['name'];
        var code = templates[0]['code'];

        g_hover_templates[name] = code;
        g_hover_template_childs[name] = this.getChildCode(code);

        this.getTemplate(); // assign template to the object

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
        call_ajax(oGeneralProperties.path_server+'?mod=General&act=getHoverTemplate'
                  +'&name[]='+escapeUrlValues(this.obj.conf.hover_template), {
            response_handler: this.handleTemplate.bind(this)
        });
        g_hover_templates[this.obj.conf.hover_template] = true; // mark as already requested
    },

    enable: function() {
        if (!this.enabled) {
            this._handleMouseMove = this.handleMouseMove.bind(this); 
            this._handleMouseOut  = this.handleMouseOut.bind(this);
            addEvent(this.obj.trigger_obj, 'mousemove', this._handleMouseMove);
            addEvent(this.obj.trigger_obj, 'mouseout', this._handleMouseOut);
            addEvent(this.obj.trigger_obj, 'mousedown', this._handleMouseOut);
            this.enabled = true;
        }
    },

    disable: function() {
        if (this.enabled) {
            removeEvent(this.obj.trigger_obj, 'mousemove', this._handleMouseMove);
            removeEvent(this.obj.trigger_obj, 'mouseout', this._handleMouseOut);
            removeEvent(this.obj.trigger_obj, 'mousedown', this._handleMouseOut);
            this.enabled = false;
        }
    },

    handleMouseOut: function(event) {
        this.hide();
    },

    handleMouseMove: function(event) {
        event = event ? event : window.event; // IE FIX

        // During render/drawing calls the template was not ready, so the hover menu
        // has not been drawn yet. Do it now.
        if (this.dom_obj === null)
            this.draw();

        var hover_delay = parseInt(this.obj.conf.hover_delay);

        // Only show up hover menu when no context menu is opened
        // and only handle the events when no timer is in schedule at the moment to
        // prevent strange movement effects when the timer has finished
        if (!dragging() && g_context_open === null && this.show_timer === null) {
            this.coords = [event.clientX, event.clientY];
            if (hover_delay && !this.isVisible()) {
                this.show_timer = setTimeout(function(obj) {
                    return function() {
                        obj.show();
                    };
                }(this), hover_delay*1000);
            }
            else {
                this.show();
            }
        }
    },

    // Is the menu currently visible to the user?
    isVisible: function() {
        return this.dom_obj.style.display !== 'none';
    },

    show: function() {
        // when the hover template is not loaded yet and the menu not
        // executed draw(), the menu can not be shown yet. Skip it.
        if (this.dom_obj)
            this.showAndPositionMenu();
    },

    hide: function() {
        // Abort eventual running show timer
        if (this.show_timer !== null) {
            clearTimeout(this.show_timer);
            this.show_timer = null;
        }

        // Might be called even when nothing has been rendered yet
        if (this.dom_obj)
            this.dom_obj.style.display = 'none';
        this.coords = null;
        g_hover_open = null;
    },

    showAndPositionMenu: function() {
        // document.body.scrollTop does not work in IE
        var scrollTop = document.body.scrollTop ? document.body.scrollTop :
                                                  document.documentElement.scrollTop;
        var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft :
                                                    document.documentElement.scrollLeft;

        var x = this.coords[0],
            y = this.coords[1];

        // hide the menu first to avoid an "up-then-over" visual effect
        this.dom_obj.style.display = 'none';
        this.dom_obj.style.left = (x + scrollLeft + this.spacing - getSidebarWidth()) + 'px';
        this.dom_obj.style.top = (y + scrollTop + this.spacing - getHeaderHeight()) + 'px';
        if(isIE) {
            this.dom_obj.style.width = '0px';
        } else {
            this.dom_obj.style.width = 'auto';
        }
        this.dom_obj.style.display = '';
        g_hover_open = this;

        // Set the width but leave some border at the screens edge
        if(this.dom_obj.clientWidth - this.spacing > this.min_width)
            this.dom_obj.style.width = this.dom_obj.clientWidth - this.spacing + 'px';
        else
            this.dom_obj.style.width = this.min_width + 'px';

        /**
         * Check if the menu is "in screen" or too large.
         * If there is some need for resize/reposition:
         *  - Try to resize the hover menu at least to the minimum size
         *  - If that is not possible try to reposition the hover menu
         */

        var hoverLeft = parseInt(this.dom_obj.style.left.replace('px', ''));
        var screenWidth = pageWidth();
        var hoverPosAndSizeOk = true;
        if (!this.isOnScreen()) {
            hoverPosAndSizeOk = false;
            if (this.tryResize())
                hoverPosAndSizeOk = true;
        }

        // Resizing was not enough so try to reposition the menu now
        if (!hoverPosAndSizeOk) {
            // First reposition by real size or by min width
            if (this.dom_obj.clientWidth < this.min_width) {
                this.dom_obj.style.left = (x - this.min_width - this.spacing + scrollLeft) + 'px';
            } else {
                this.dom_obj.style.left = (x - this.dom_obj.clientWidth - this.spacing + scrollLeft) + 'px';
            }

            if (this.isOnScreen()) {
                hoverPosAndSizeOk = true;
            } else {
                // Still not ok. Now try to resize on the right down side of the icon
                if (this.tryResize(true)) {
                    hoverPosAndSizeOk = true;
                }
            }
        }

        // And if the hover menu is still not on the screen move it to the left edge
        // and fill the whole screen width
        if (!this.isOnScreen()) {
            this.dom_obj.style.left = this.spacing + scrollLeft + 'px';
            this.dom_obj.style.width = pageWidth() - (2*this.spacing) + 'px';
        }

        var hoverTop = parseInt(this.dom_obj.style.top.replace('px', ''));
        // Only move the menu to the top when the new top will not be
        // out of sight
        if(hoverTop + this.dom_obj.clientHeight > pageHeight() && hoverTop - this.dom_obj.clientHeight >= 0)
            this.dom_obj.style.top = hoverTop - this.dom_obj.clientHeight - this.spacing - 5 + 'px';
        hoverTop = null;
    },

    isOnScreen: function() {
        var hoverLeft = parseInt(this.dom_obj.style.left.replace('px', ''));
        var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft :
                         document.documentElement.scrollLeft;
    
        if (hoverLeft < scrollLeft)
            return false;
    
        // The most right px of the hover menu
        var hoverRight = hoverLeft + this.dom_obj.clientWidth - scrollLeft;
        // The most right px of the viewport
        var viewRight  = pageWidth();
    
        if (hoverRight > viewRight)
            return false;
    
        // There is not enough spacing at the left viewport border
        if (hoverLeft - this.spacing < 0)
            return false;
    
        return true;
    },

    tryResize: function(rightSide) {
        if (!isset(rightSide))
            var reposition = false;
    
        var hoverLeft = parseInt(this.dom_obj.style.left.replace('px', ''));
    
        if (rightSide)
            var overhead = hoverLeft + this.dom_obj.clientWidth + this.spacing - pageWidth();
        else
            var overhead = hoverLeft;
        var widthAfterResize = this.dom_obj.clientWidth - overhead;
    
        // If width is larger than this.min_width resize it
        if (widthAfterResize > this.min_width) {
            this.dom_obj.style.width = widthAfterResize + 'px';
    
            if (rightSide) {
                if(overhead < 0)
                    overhead *= -1
                this.dom_obj.style.left = (hoverLeft + overhead) + 'px';
            }
    
            return true;
        } else {
            return false;
        }
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
            var hoverMenu = document.createElement('div');
            this.dom_obj = hoverMenu;
            hoverMenu.setAttribute('id', this.obj.conf.object_id+'-hover');
            hoverMenu.className = 'hover';
            hoverMenu.style.display = 'none';
        }

        // Append template code to hover menu div
        hoverMenu.innerHTML = template_html;

        // Display the hover menu again, when it was open before re-rendering
        if (this.coords !== null)
            this.show();
    },

    replaceChildMacros: function (template_html) {
        var childs_html = '';
        var regex = '';

        var row_tmpl = g_hover_template_childs[this.obj.conf.hover_template];
        var stateful_members = this.obj.getStatefulMembers();
        if(typeof(row_tmpl) != 'undefined' && row_tmpl != '' && stateful_members.length > 0) {
            // Loop all child objects until all looped or the child limit is reached
            for(var i = 0, len1 = this.obj.conf.hover_childs_limit, len2 = stateful_members.length;
                (len1 == -1 || (len1 >= 0 && i <= len1)) && i < len2; i++) {

                if(len1 == -1 || (len1 >= 0 && i < len1)) {
                    // Children need to know where they belong
                    stateful_members[i].parent_type = this.obj.conf.type;
                    stateful_members[i].parent_name = this.obj.conf.name;

                    childs_html += this.replaceMacrosOfChild(stateful_members[i], row_tmpl);
                } else {
                    // Create an end line which shows the number of hidden child items
                    var member = {
                        'conf': {
                            'type': 'host',
                            'name': '',
                            'summary_state': '',
                            'summary_output': (this.obj.conf.num_members - this.obj.conf.hover_childs_limit) + ' ' + _('more items...'),
                            '<!--\\sBEGIN\\sservicegroup_child\\s-->.+?<!--\\sEND\\sservicegroup_child\\s-->': ''
                        }
                    };

                    childs_html += this.replaceMacrosOfChild(member, row_tmpl);
                }
            }
        }

        if(childs_html != '')
            regex = getRegEx('loopChild', "<!--\\sBEGIN\\sloop_child\\s-->(.+?)<!--\\sEND\\sloop_child\\s-->");
        else
            regex = getRegEx('loopChildEmpty', '<!--\\sBEGIN\\schilds\\s-->.+?<!--\\sEND\\schilds\\s-->');

        template_html = template_html.replace(regex, childs_html);
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
    
        if (g_view.type === 'map')
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
        oMacros.lang_obj_type = _(this.obj.conf.type);
        if (this.obj.conf.type == 'host') {
            oMacros.lang_name        = _('hostname');
            oMacros.lang_child_name  = _('servicename');
        } else if (this.obj.conf.type == 'service') {
            oMacros.lang_name        = _('servicename');
        } else if (this.obj.conf.type == 'hostgroup') {
            oMacros.lang_name        = _('hostgroupname');
            oMacros.lang_child_name  = _('hostname');
        } else if (this.obj.conf.type == 'servicegroup') {
            oMacros.lang_name        = _('servicegroupname');
            oMacros.lang_child_name  = _('servicename');
            oMacros.lang_child_name1 = _('hostname');
        } else if (this.obj.conf.type == 'dyngroup') {
            oMacros.lang_name        = _('Dynamic Group Name');
            oMacros.lang_child_name  = _('Object Name');
            if (this.obj.conf.object_types == 'service')
                oMacros.lang_child_name1 = _('hostname');
        } else if (this.obj.conf.type == 'aggr') {
            oMacros.lang_name        = _('Aggregation Name');
            oMacros.lang_child_name  = _('Name');
            oMacros.lang_child_name1 = _('Name');
        } else if (this.obj.conf.type == 'map') {
            oMacros.lang_name        = _('mapname');
            oMacros.lang_child_name  = _('objectname');
        }

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
