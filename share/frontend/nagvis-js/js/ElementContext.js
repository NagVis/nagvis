/*****************************************************************************
 *
 * ElementContext.js - This class realizes the context menus
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

var g_context_templates = {};
var g_context_open      = null;

// Hides the currently open context menu
function contextHide() {
    if (g_context_open !== null)
        g_context_open.hide();
}

function context_handle_global_mousedown(event) {
    event = event ? event : window.event; // IE FIX
    if (usesSource('worldmap'))
        event = event.originalEvent;
    var target = getTargetRaw(event);

    while (target && !has_class(target, 'context'))
        target = target.parentNode;

    if (target === null) {
        // when not clicked on the context menu, hide it
        contextHide();
    }
    else {
        // Check for click on the context menu and do nothing in this case
        var object_id = target.id.split('-')[0];
        if (object_id == g_context_open.obj.conf.object_id)
            return preventDefaultEvents(event);
    }
}

var ElementContext = Element.extend({
    template_html : null,
    spacing       : 5,    // px from screen border
    coords        : null, // list of x, y coordinates of the hover menu top left corner

    update: function() {
        this.getTemplate();
    },

    render: function() {
        this.getTemplate();
        if (this.template_html === null || this.template_html === true)
            return false; // template not available yet, skip rendering

        this.renderMenu();
    },

    draw: function() {
        // Not rendered yet, e.g. because template was not fetched yet during
        // initial rendering. Re-try rendering now
        if (this.dom_obj === null)
            this.render();

        this.base();

        addEvent(this.obj.trigger_obj, 'contextmenu', function(element_obj) {
            return function(event) {
                // During render/drawing calls the template was not ready, so the menu
                // has not been drawn yet. Do it now.
                if (element_obj.dom_obj === null) {
                    element_obj.draw();
                }
                element_obj.coords = [event.clientX, event.clientY];
                element_obj.show();
                return preventDefaultEvents(event);
            };
        }(this));
    },

    erase: function() {
        removeEvent(this.obj.trigger_obj, 'mousedown');
        removeEvent(this.obj.trigger_obj, 'contextmenu');
        this.base();
    },

    lock: function() {
        // FIXME: Re-render for new content
        this.erase();
        this.render();
        this.draw();
    },

    unlock: function() {
        // FIXME: Re-render for new content
        this.erase();
        this.render();
        this.draw();
    },

    //
    // END OF PUBLIC METHODS
    //

    getTemplate: function() {
        var name = this.obj.conf.context_template;
        if (!isset(g_context_templates[name])) {
            this.requestTemplate();
        }
        else if (g_context_templates[name] !== true) {
            this.template_html = g_context_templates[name];
            this.replaceStaticMacros();
        }
    },

    handleTemplate: function (templates) {
        var name = templates[0]['name'];
        var code = templates[0]['code'];

        g_context_templates[name] = code;

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
        call_ajax(oGeneralProperties.path_server+'?mod=General&act=getContextTemplate'
                  +'&name[]='+escapeUrlValues(this.obj.conf.context_template), {
            response_handler: this.handleTemplate.bind(this)
        });
        g_context_templates[this.obj.conf.context_template] = true; // mark as already requested
    },

    show: function() {
        hoverHide();

        // document.body.scrollTop does not work in IE
        var scrollTop = document.body.scrollTop ? document.body.scrollTop :
                                                  document.documentElement.scrollTop;
        var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft :
                                                    document.documentElement.scrollLeft;
    
        // hide the menu first to avoid an "up-then-over" visual effect
        this.dom_obj.style.display = 'none';
        this.dom_obj.style.left = this.coords[0] + this.spacing + scrollLeft - getSidebarWidth() + 'px';
        this.dom_obj.style.top = this.coords[1] + this.spacing + scrollTop - getHeaderHeight() + 'px';
        this.dom_obj.style.display = '';
    
        // Check if the context menu is "in screen".
        // When not: reposition
        var contextLeft = parseInt(this.dom_obj.style.left.replace('px', ''));
        if(contextLeft+this.dom_obj.clientWidth > pageWidth()) {
            // move the context menu to the left
            this.dom_obj.style.left = contextLeft - this.dom_obj.clientWidth + 'px';
        }
    
        var contextTop = parseInt(this.dom_obj.style.top.replace('px', ''));
        if(contextTop+this.dom_obj.clientHeight > pageHeight()) {
            // Only move the context menu to the top when the new top will not be
            // out of sight
            if(contextTop - this.dom_obj.clientHeight >= 0) {
                this.dom_obj.style.top = contextTop - this.dom_obj.clientHeight + 'px';
            }
        }

        g_context_open = this;
    },

    hide: function() {
        if (this.dom_obj) {
            this.dom_obj.style.display = 'none';
        }
        g_context_open = null;
    },

    renderMenu: function () {
        // Only create a new div when the context menu does not exist
        // Create context menu div
        var contextMenu = document.createElement('div');
        this.dom_obj = contextMenu;
        contextMenu.setAttribute('id', this.obj.conf.object_id+'-context');
        contextMenu.className = 'context';
        contextMenu.style.display = 'none';

        // Append template code to context menu div
        contextMenu.innerHTML = this.template_html;
    },

    // Replaces object configuration specific macros in the template code
    replaceStaticMacros: function() {
        var oSectionMacros = {};

        // Break when no template code found
        if(!this.template_html || this.template_html === '') {
            return false;
        }

        var oMacros = {
            'obj_id':      this.obj.conf.object_id,
            'type':        this.obj.conf.type,
            'name':        this.obj.conf.name,
            'alias':       this.obj.conf.alias,
            'address':     this.obj.conf.address,
            'html_cgi':    this.obj.conf.htmlcgi,
            'backend_id':  this.obj.conf.backend_id,
            'custom_1':    this.obj.conf.custom_1,
            'custom_2':    this.obj.conf.custom_2,
            'custom_3':    this.obj.conf.custom_3
        };

        if (g_view.type === 'map')
            oMacros.map_name = g_view.id;

        if(this.obj.conf.type === 'service') {
            oMacros.service_description = escapeUrlValues(this.obj.conf.service_description);

            oMacros.pnp_hostname = this.obj.conf.name.replace(/\s/g,'%20');
            oMacros.pnp_service_description = this.obj.conf.service_description.replace(/\s/g,'%20');
        } else
            oSectionMacros.service = '<!--\\sBEGIN\\sservice\\s-->.+?<!--\\sEND\\sservice\\s-->';

        // Macros which are only for hosts
        if(this.obj.conf.type === 'host')
            oMacros.pnp_hostname = this.obj.conf.name.replace(/\s/g,'%20');
        else
            oSectionMacros.host = '<!--\\sBEGIN\\shost\\s-->.+?<!--\\sEND\\shost\\s-->';

        if(this.obj.conf.type !== 'host' && this.obj.conf.type !== 'shape')
            oSectionMacros.host_or_shape = '<!--\\sBEGIN\\shost_or_shape\\s-->.+?<!--\\sEND\\shost_or_shape\\s-->';

        if(this.obj.conf.type === 'line' || this.obj.conf.type == 'shape'
           || this.obj.conf.type == 'textbox' || this.obj.conf.type === 'container')
            oSectionMacros.stateful = '<!--\\sBEGIN\\sstateful\\s-->.+?<!--\\sEND\\sstateful\\s-->';

        // Remove unlocked section for locked objects
        if(this.obj.bIsLocked)
            oSectionMacros.unlocked = '<!--\\sBEGIN\\sunlocked\\s-->.+?<!--\\sEND\\sunlocked\\s-->';
        else
            oSectionMacros.locked = '<!--\\sBEGIN\\slocked\\s-->.+?<!--\\sEND\\slocked\\s-->';

        if(!oViewProperties || !oViewProperties.permitted_edit)
            oSectionMacros.permitted_edit = '<!--\\sBEGIN\\spermitted_edit\\s-->.+?<!--\\sEND\\spermitted_edit\\s-->';

        if(!oViewProperties || !oViewProperties.permitted_perform)
            oSectionMacros.permitted_perform = '<!--\\sBEGIN\\spermitted_perform\\s-->.+?<!--\\sEND\\spermitted_perform\\s-->';

        if(usesSource('automap')) {
            oSectionMacros.not_automap = '<!--\\sBEGIN\\snot_automap\\s-->.+?<!--\\sEND\\snot_automap\\s-->';
	    // Skip the root change link for the root host
            if(this.obj.conf.name === getUrlParam('root'))
		oSectionMacros.automap_not_root = '<!--\\sBEGIN\\sautomap_not_root\\s-->.+?<!--\\sEND\\sautomap_not_root\\s-->';
        } else {
	    oSectionMacros.automap_not_root = '<!--\\sBEGIN\\sautomap_not_root\\s-->.+?<!--\\sEND\\sautomap_not_root\\s-->';
            oSectionMacros.automap = '<!--\\sBEGIN\\sautomap\\s-->.+?<!--\\sEND\\sautomap\\s-->';
        }
        if(this.obj.conf.view_type !== 'line')
            oSectionMacros.line = '<!--\\sBEGIN\\sline\\s-->.+?<!--\\sEND\\sline\\s-->';
        if(this.obj.conf.view_type !== 'line'
           || (this.obj.conf.line_type == 11 || this.obj.conf.line_type == 12))
            oSectionMacros.line_type = '<!--\\sBEGIN\\sline_two_parts\\s-->.+?<!--\\sEND\\sline_two_parts\\s-->';

        // Replace hostgroup range macros when not in a hostgroup
        if(this.obj.conf.type !== 'hostgroup')
            oSectionMacros.hostgroup = '<!--\\sBEGIN\\shostgroup\\s-->.+?<!--\\sEND\\shostgroup\\s-->';

        // Replace servicegroup range macros when not in a servicegroup
        if(this.obj.conf.type !== 'servicegroup' && !(this.obj.conf.type === 'dyngroup' && this.obj.conf.object_types == 'service'))
            oSectionMacros.servicegroup = '<!--\\sBEGIN\\sservicegroup\\s-->.+?<!--\\sEND\\sservicegroup\\s-->';

        // Replace map range macros when not in a hostgroup
        if(this.obj.conf.type !== 'map')
            oSectionMacros.map = '<!--\\sBEGIN\\smap\\s-->.+?<!--\\sEND\\smap\\s-->';

        // Loop all registered actions, check wether or not this action should be shown for this object
        // and either add the replacement section or not
        for (var key in oGeneralProperties.actions) {
            if(key == "indexOf")
                continue; // skip indexOf prototype (seems to be looped in IE)
            var action = oGeneralProperties.actions[key];
            var hide = false;

            // Check object type
            hide = action.obj_type.indexOf(this.obj.conf.type) == -1;

            // Only check the condition when not already hidden by another check before
            if(!hide && isset(action.client_os) && action.client_os.length > 0) {
                // Check the client os
                var os = navigator.platform.toLowerCase();
                if (os.indexOf('win') !== -1)
                    os = 'win';
                else if (os.indexOf('linux') !== -1)
                    os = 'lnx';
                else if (os.indexOf('mac') !== -1)
                    os = 'mac';

                hide = action.client_os.indexOf(os) == -1;
            }

            // Only check the condition when not already hidden by another check before
            if(!hide && isset(action.condition) && action.condition !== '') {
                var cond = action.condition;

                var op = '';
                if (cond.indexOf('~') != -1) {
                    op = '~';
                } else if (cond.indexOf('=') != -1) {
                    op = '=';
                }

                var parts = cond.split(op);
                var attr  = parts[0];
                var val   = parts[1];
                var to_be_checked;
                if (isset(this.obj.conf.custom_variables) && isset(this.obj.conf.custom_variables[attr])) {
                    to_be_checked = this.obj.conf.custom_variables[attr];
                } else if(isset(this.obj.conf[attr])) {
                    to_be_checked = this.obj.conf[attr];
                }

                if (to_be_checked) {
                    if (op == '=' && to_be_checked != val) {
                        hide = true;
                    } else if (op == '~' && to_be_checked.indexOf(val) == -1) {
                        hide = true;
                    }
                } else {
                    hide = true;
                }
            }

            // Remove the section macros of not hidden actions
            if(!hide) {
                oSectionMacros['action_'+key] = '<!--\\s(BEGIN|END)\\saction_'+key+'\\s-->';
            }
            cond = null;
            action = null;
        }

        // Remove all not hidden actions
        oSectionMacros['actions'] = '<!--\\sBEGIN\\saction_.+?\\s-->.+?<!--\\sEND\\saction_.+?\\s-->';

        // Loop and replace all unwanted section macros
        for (var key in oSectionMacros) {
            var regex = getRegEx('section-'+key, oSectionMacros[key], 'gm');
            this.template_html = this.template_html.replace(regex, '');
            regex = null;
        }
        oSectionMacros = null;

        // Loop and replace all normal macros
        this.template_html = this.template_html.replace(/\[(\w*)\]/g,
                                     function(){ return oMacros[ arguments[1] ] || '';});
        oMacros = null;
    }

});
