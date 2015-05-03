/*****************************************************************************
 *
 * NagVisObject.js - This class handles the visualisation of Nagvis objects
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

var NagVisObject = Base.extend({
    parsedObject:          null,
    hover_template_code:   null,
    context_template_code: null,
    conf:                  null,
    contextMenu:           null,
    lastUpdate:            null,
    firstUpdate:           null,
    bIsFlashing:           false,
    bIsLocked:             true,
    objControls:           null,
    childs:                null,
    // Current position of the active hover menu
    hoverX:                null,
    hoverY:                null,

    constructor: function(oConf) {
        // Initialize
        this.setLastUpdate();

        this.childs      = [];
        this.objControls = [];
        this.conf        = oConf;

        // When no object_id given by server: generate own id
        if(this.conf.object_id == null)
            this.conf.object_id = getRandomLowerCaseLetter() + getRandom(1, 99999);

        // Load lock options
        this.loadLocked();
        this.loadViewOpts();
    },


    /**
     * PRIVATE loadLocked
     * Loads the lock state of an object from the user properties.
     *
     * Another way to unlock the object is the optional view property
     * "edit_mode". In this case all map objects are unlocked but this
     * state is not saved to the user properties.
     */
    loadLocked: function() {
        // Editing is only possible in maps
        if(oPageProperties.view_type != 'map')
            return;

        if(!oUserProperties.hasOwnProperty('unlocked-' + oPageProperties.map_name))
            return;

        if(oViewProperties.hasOwnProperty('edit_mode') && oViewProperties['edit_mode'] === true) {
            this.bIsLocked = false;
            return;
        }

        var unlocked = oUserProperties['unlocked-' + oPageProperties.map_name].split(',');
        this.bIsLocked = unlocked.indexOf(this.conf.object_id) === -1 && unlocked.indexOf('*') === -1;
        unlocked = null;
    },

    /**
     * PUBLIC loadViewOpts
     *
     * Loads view specific options. Basically this options are triggered by url params
     *
     * @author Lars Michelsen <lars@vertical-visions.de>
     */
    loadViewOpts: function() {
        // Do not load the view options for stateless lines
        if(this.conf.type == 'line')
            return;

        // View specific hover modifier set. Will override the map configured option
        if(isset(oViewProperties) && isset(oViewProperties.hover_menu))
            this.conf.hover_menu = oViewProperties.hover_menu;

        // View specific context modifier set. Will override the map configured option
        if(isset(oViewProperties) && isset(oViewProperties.context_menu))
            this.conf.context_menu = oViewProperties.context_menu;
    },

    /**
     * PUBLIC setLastUpdate
     *
     * Sets the time of last status update of this object
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    setLastUpdate: function() {
        this.lastUpdate = iNow;

        // Save datetime of the first state update (needed for hover parsing)
        if(this.firstUpdate === null)
            this.firstUpdate = this.lastUpdate;
    },

    /**
     * PUBLIC getContextMenu()
     *
     * Creates a context menu for the object
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    getContextMenu: function (sObjId) {
        // Writes template code to "this.context_template_code"
        this.getContextTemplateCode();

        // Replace object specific macros
        this.replaceContextTemplateMacros();

        var doc = document;
        var oObj = doc.getElementById(sObjId);
        var oContainer = doc.getElementById(this.conf.object_id);

        if(oObj == null) {
            eventlog("NagVisObject", "critical", "Could not get context menu object (ID:"+sObjId+")");
            return false;
        }

        if(oContainer == null) {
            eventlog("NagVisObject", "critical", "Could not get context menu container (ID:"+this.conf.object_id+")");
            oObj = null;
            return false;
        }

        // Only create a new div when the context menu does not exist
        var contextMenu = doc.getElementById(this.conf.object_id+'-context');
        var justAdded = false;
        if(!contextMenu) {
            // Create context menu div
            var contextMenu = doc.createElement('div');
            contextMenu.setAttribute('id', this.conf.object_id+'-context');
            contextMenu.setAttribute('class', 'context');
            contextMenu.setAttribute('className', 'context');
            contextMenu.style.zIndex = '1000';
            contextMenu.style.display = 'none';
            contextMenu.style.position = 'absolute';
            contextMenu.style.overflow = 'visible';
            justAdded = true;
        }

        // Append template code to context menu div
        contextMenu.innerHTML = this.context_template_code;

        if(justAdded) {
            // Append context menu div to object container
            oContainer.appendChild(contextMenu);

            // Add eventhandlers for context menu
            oObj.onmousedown = contextMouseDown;
            oObj.oncontextmenu = contextShow;
        }

        contextMenu = null;
        oContainer = null;
        oObj = null;
        doc = null;
    },

    /**
     * PUBLIC parseContextMenu()
     *
     * Parses the context menu. Don't add this functionality to the normal icon
     * parsing
     *
     * @return	String		HTML code of the object
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    parseContextMenu: function () {
        // Add a context menu to the object when enabled or when the object is unlocked
        if(this.needsContextMenu()) {
            if(this.conf.view_type && this.conf.view_type == 'line') {
                this.getContextMenu(this.conf.object_id+'-linelink');
            } else if(this.conf.type == 'textbox' || this.conf.type == 'container') {
                this.getContextMenu(this.conf.object_id);
            } else {
                this.getContextMenu(this.conf.object_id+'-icon');
            }
        }
    },

    /**
     * replaceContextTemplateMacros()
     *
     * Replaces object specific macros in the template code
     *
     * @return	String		HTML code for the hover box
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    replaceContextTemplateMacros: function() {
        var oSectionMacros = {};

        // Break when no template code found
        if(!this.context_template_code || this.context_template_code === '') {
            return false;
        }

        var oMacros = {
            'obj_id':      this.conf.object_id,
            'type':        this.conf.type,
            'name':        this.conf.name,
            'alias':       this.conf.alias,
            'address':     this.conf.address,
            'html_cgi':    this.conf.htmlcgi,
            'backend_id':  this.conf.backend_id,
            'custom_1':    this.conf.custom_1,
            'custom_2':    this.conf.custom_2,
            'custom_3':    this.conf.custom_3
        };

      if(typeof(oPageProperties) != 'undefined' && oPageProperties != null
           && oPageProperties.view_type === 'map')
            oMacros.map_name = oPageProperties.map_name;

        if(this.conf.type === 'service') {
            oMacros.service_description = escapeUrlValues(this.conf.service_description);

            oMacros.pnp_hostname = this.conf.name.replace(/\s/g,'%20');
            oMacros.pnp_service_description = this.conf.service_description.replace(/\s/g,'%20');
        } else
            oSectionMacros.service = '<!--\\sBEGIN\\sservice\\s-->.+?<!--\\sEND\\sservice\\s-->';

        // Macros which are only for hosts
        if(this.conf.type === 'host')
            oMacros.pnp_hostname = this.conf.name.replace(/\s/g,'%20');
        else
            oSectionMacros.host = '<!--\\sBEGIN\\shost\\s-->.+?<!--\\sEND\\shost\\s-->';

        if(this.conf.type !== 'host' && this.conf.type !== 'shape')
            oSectionMacros.host_or_shape = '<!--\\sBEGIN\\shost_or_shape\\s-->.+?<!--\\sEND\\shost_or_shape\\s-->';

        if(this.conf.type === 'line' || this.conf.type == 'shape'
           || this.conf.type == 'textbox' || this.conf.type === 'container')
            oSectionMacros.stateful = '<!--\\sBEGIN\\sstateful\\s-->.+?<!--\\sEND\\sstateful\\s-->';

        // Remove unlocked section for locked objects
        if(this.bIsLocked)
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
            if(this.conf.name === getUrlParam('root'))
		oSectionMacros.automap_not_root = '<!--\\sBEGIN\\sautomap_not_root\\s-->.+?<!--\\sEND\\sautomap_not_root\\s-->';
        } else {
	    oSectionMacros.automap_not_root = '<!--\\sBEGIN\\sautomap_not_root\\s-->.+?<!--\\sEND\\sautomap_not_root\\s-->';
            oSectionMacros.automap = '<!--\\sBEGIN\\sautomap\\s-->.+?<!--\\sEND\\sautomap\\s-->';
        }
        if(this.conf.view_type !== 'line')
            oSectionMacros.line = '<!--\\sBEGIN\\sline\\s-->.+?<!--\\sEND\\sline\\s-->';
        if(this.conf.view_type !== 'line'
           || (this.conf.line_type == 11 || this.conf.line_type == 12))
            oSectionMacros.line_type = '<!--\\sBEGIN\\sline_two_parts\\s-->.+?<!--\\sEND\\sline_two_parts\\s-->';

        // Replace hostgroup range macros when not in a hostgroup
        if(this.conf.type !== 'hostgroup')
            oSectionMacros.hostgroup = '<!--\\sBEGIN\\shostgroup\\s-->.+?<!--\\sEND\\shostgroup\\s-->';

        // Replace servicegroup range macros when not in a servicegroup
        if(this.conf.type !== 'servicegroup' && !(this.conf.type === 'dyngroup' && this.conf.object_types == 'service'))
            oSectionMacros.servicegroup = '<!--\\sBEGIN\\sservicegroup\\s-->.+?<!--\\sEND\\sservicegroup\\s-->';

        // Replace map range macros when not in a hostgroup
        if(this.conf.type !== 'map')
            oSectionMacros.map = '<!--\\sBEGIN\\smap\\s-->.+?<!--\\sEND\\smap\\s-->';

        // Loop all registered actions, check wether or not this action should be shown for this object
        // and either add the replacement section or not
        for (var key in oGeneralProperties.actions) {
            if(key == "indexOf")
                continue; // skip indexOf prototype (seems to be looped in IE)
            var action = oGeneralProperties.actions[key];
            var hide = false;

            // Check object type
            hide = action.obj_type.indexOf(this.conf.type) == -1;

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
                if (isset(this.conf.custom_variables) && isset(this.conf.custom_variables[attr])) {
                    to_be_checked = this.conf.custom_variables[attr];
                } else if(isset(this.conf[attr])) {
                    to_be_checked = this.conf[attr];
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
            this.context_template_code = this.context_template_code.replace(regex, '');
            regex = null;
        }
        oSectionMacros = null;

        // Loop and replace all normal macros
        this.context_template_code = this.context_template_code.replace(/\[(\w*)\]/g,
                                     function(){ return oMacros[ arguments[1] ] || '';});
        oMacros = null;
    },

    /**
     * getContextTemplateCode()
     *
     * Get the context template from the global object which holds all templates of
     * the map
     *
     * @return	String		HTML code for the hover box
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    getContextTemplateCode: function() {
        this.context_template_code = oContextTemplates[this.conf.context_template];
    },

    /**
     * Returns true when the given menu is displayed at the moment
     */
    menuOpened: function(ty) {
        var menu = document.getElementById(this.conf.object_id + '-' + ty);
        if(menu) {
            if(menu.style.display !== 'none') {
                return true;
            }
            menu = null;
        }
        return false;
    },

    /**
     * PUBLIC getHoverMenu
     *
     * Creates a hover box for objects
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    getHoverMenu: function (sObjId) {
        // Only enable hover menu when configured
        if(!this.conf.hover_menu || this.conf.hover_menu != '1')
            return;

        var objId = this.conf.object_id;
        var sTemplateCode = '';
        var iHoverDelay = this.conf.hover_delay;

        // Parse the configured URL or get the hover menu
        if(this.conf.hover_url && this.conf.hover_url !== '') {
            this.getHoverUrlCode();

            sTemplateCode = this.hover_template_code;
        } else {
            // Only fetch hover template code and parse static macros when this is
            // no update
            if(this.hover_template_code === null)
                this.getHoverTemplateCode();

            // Replace dynamic (state dependent) macros
            if(isset(this.conf.hover_template))
                sTemplateCode = replaceHoverTemplateDynamicMacros(this);
        }

        var doc = document;
        var oObj = doc.getElementById(sObjId);
        var oContainer = doc.getElementById(this.conf.object_id);

        if(oObj == null) {
            eventlog("NagVisObject", "critical", "Could not get hover menu object (ID:"+sObjId+")");
            return false;
        }

        if(oContainer == null) {
            eventlog("NagVisObject", "critical", "Could not get hover menu container (ID:"+this.conf.object_id+")");
            oObj = null;
            return false;
        }

        // Only create a new div when the hover menu does not exist
        var hoverMenu = doc.getElementById(this.conf.object_id+'-hover');
        var justCreated = false;
        if(!hoverMenu) {
            // Create hover menu div
            var hoverMenu = doc.createElement('div');
            hoverMenu.setAttribute('id', this.conf.object_id+'-hover');
            hoverMenu.setAttribute('class', 'hover');
            hoverMenu.setAttribute('className', 'hover');
            hoverMenu.style.zIndex = '1000';
            hoverMenu.style.display = 'none';
            hoverMenu.style.position = 'absolute';
            hoverMenu.style.overflow = 'visible';
            justCreated = true;
        }

        // Append template code to hover menu div
        hoverMenu.innerHTML = sTemplateCode;
        sTemplateCode = null;

        if(justCreated) {
            // Append hover menu div to object container
            oContainer.appendChild(hoverMenu);

            // Add eventhandlers for hover menu
            if(oObj) {
                oObj.onmousemove = function(event) {
                    // IE is evil and doesn't pass the event object
                    if(!isset(event))
                        event = window.event;
                    var id = objId;
                    var iH = iHoverDelay;
                    displayHoverMenu(event, id, iH);
                    id = null; iH = null; event = null;
                };
                oObj.onmouseout = function(e) { var id = objId; hoverHide(id); id = null; };
            }

            // Is already done during map rendering before. But the hover menu
            // is rendered after the map rendering and can not be disabled during
            // map rendering. So simply repeat that action here
	    if(typeof(this.toggleObjectActions) == 'function')
                this.toggleObjectActions(this.bIsLocked);
        }

        justCreated = null;
        hoverMenu = null;
        oContainer = null;
        oObj = null;
        doc = null;
    },

    /**
     * getHoverUrlCode()
     *
     * Get the hover code from the hover url
     *
     * @return	String		HTML code for the hover box
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    getHoverUrlCode: function() {
        this.hover_template_code = oHoverUrls[this.conf.hover_url];

        if(this.hover_template_code === null)
            this.hover_template_code = '';
    },

    /**
     * getHoverTemplateCode()
     *
     * Get the hover template from the global object which holds all templates of
     * the map
     *
     * @return	String		HTML code for the hover box
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    getHoverTemplateCode: function() {
        // Asign the template code and replace only the static macros
        // These are typicaly configured static configued values from nagios
        if(isset(this.conf.hover_template))
            this.hover_template_code = replaceHoverTemplateStaticMacros(this, oHoverTemplates[this.conf.hover_template]);
    },

    /**
     * Locks/Unlocks the object and fires dependent actions
     * It returns +1,-1 or 0 depending on the final state of the object
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    toggleLock: function(lock) {
        var equal = false;
        if(isset(lock) && lock === this.bIsLocked)
            equal = true;

        if(isset(lock))
            this.bIsLocked = lock;
        else
            this.bIsLocked = !this.bIsLocked;

        if(this.conf.view_type === 'line' || this.conf.type === 'line')
            this.parseLineHoverArea(document.getElementById(this.conf.object_id+'-linediv'));
        // Re-render the context menu
        this.parseContextMenu();

        if(this.toggleObjControls()) {

	    if(typeof(this.toggleLabelLock) == 'function')
		this.toggleLabelLock();

	    if(typeof(this.toggleObjectActions) == 'function')
		this.toggleObjectActions(this.bIsLocked);

            // Only save the user option when not using the edit_mode
            if(!isset(lock) && (!oViewProperties.hasOwnProperty('edit_mode') || oViewProperties['edit_mode'] !== true)) {
                var unlocked = [];
                if(oUserProperties.hasOwnProperty('unlocked-' + oPageProperties.map_name))
                    unlocked = oUserProperties['unlocked-' + oPageProperties.map_name].split(',');

                if(this.bIsLocked)
                    unlocked.splice(unlocked.indexOf(this.conf.object_id), 1);
                else
                    unlocked.push(this.conf.object_id);
                storeUserOption('unlocked-' + oPageProperties.map_name, unlocked.join(','));
                unlocked = null;
            }

            if(equal === true)
                return 0;
            else
                return this.bIsLocked ? -1 : 1;
        } else {
            return 0;
        }
    },

    /**
     * Shows or hides all object controls of a map object depending
     * on the lock state of this object.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    toggleObjControls: function() {
        if(!this.bIsLocked) {
            if(isset(this.parseControls)) {
                this.parseControls();
                return true;
            }
        } else {
            if(isset(this.removeControls)) {
                this.removeControls();
                return true;
            }
        }
        return false;
    },

    getObjLeft: function () {
        if (this.conf.x.toString().split(',').length > 1) {
            return Math.min.apply(Math, this.parseCoords(this.conf.x, 'x'));
        } else {
            return this.parseCoord(this.conf.x, 'x');
        }
    },

    getObjTop: function () {
        if (this.conf.x.toString().split(',').length > 1) {
            return Math.min.apply(Math, this.parseCoords(this.conf.y, 'y'));
        } else {
            return this.parseCoord(this.conf.y, 'y');
        }
    },

    getObjWidth: function () {
        var o = document.getElementById(this.conf.object_id + '-icondiv');
        if(o && o.clientWidth)
            return parseInt(o.clientWidth);
        else
            return 0;
    },

    getObjHeight: function () {
        var o = document.getElementById(this.conf.object_id + '-icondiv');
        if(o && o.clientHeight)
            return parseInt(o.clientHeight);
        else
            return 0;
    },

    /**
     * This method parses a given coordinate which can be a simple integer
     * which is simply returned or a reference to another object and/or
     * a specified anchor of the object.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    parseCoord: function(val, dir, addZoom) {
        if (addZoom === undefined)
            addZoom = true;

        var coord = 0;
        if(!isRelativeCoord(val)) {
            coord = parseInt(val);
        } else {
            // This must be an object id. Is there an offset given?
            if(val.search('%') !== -1) {
                var parts     = val.split('%');
                var objectId  = parts[0];
                var offset    = parts[1];
                var refObj    = getMapObjByDomObjId(objectId);
                if (refObj) {
                    coord = parseFloat(refObj.parseCoord(refObj.conf[dir], dir, false));
                    if (addZoom)
                        coord = addZoomFactor(coord, true);

                    if (addZoom)
                        coord += addZoomFactor(parseFloat(offset), false);
                    else
                        coord += parseFloat(offset);

                    return coord;
                }
            } else {
                // Only an object id. Get the coordinate and return it
                var refObj = getMapObjByDomObjId(val);
                if(refObj)
                    coord = parseInt(refObj.parseCoord(refObj.conf[dir], dir));
            }
        }

        if (addZoom)
            return addZoomFactor(coord, true);
        else
            return coord;
    },

    /**
     * Wrapper for the parseCoord method to parse multiple coords at once
     * e.g. for lines.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    parseCoords: function(val, dir, addZoom) {
        var l = [];

	if(val)
            l = val.toString().split(',');

        for(var i = 0, len = l.length; i < len; i++)
            l[i] = this.parseCoord(l[i], dir, addZoom);

        return l;
    },

    // Transform the current coords to absolute coords when relative
    makeAbsoluteCoords: function(num) {
        var x = num === -1 ? this.conf.x : this.conf.x.split(',')[num];
        var y = num === -1 ? this.conf.y : this.conf.y.split(',')[num];

        // Skip when already absolute
        if(!isRelativeCoord(x) && !isRelativeCoord(y))
            return;

        // Get parent object ids
        var xParent = this.getCoordParent(this.conf.x, num);
        var yParent = this.getCoordParent(this.conf.y, num);

        if(xParent == yParent) {
            var o = getMapObjByDomObjId(xParent);
            // Don't remove when another coord is a child of this object
            if(o && getKeys(this.getRelativeCoordsUsingParent(xParent)).length == 1) {
                o.delChild(this);
                o = null
            }
        } else {
            var o = getMapObjByDomObjId(xParent);
            // Don't remove when another coord is a child of this object
            if(o && getKeys(this.getRelativeCoordsUsingParent(xParent)).length == 1) {
                o.delChild(this);
                o = null
            }
            var o = getMapObjByDomObjId(yParent);
            // Don't remove when another coord is a child of this object
            if(o && getKeys(this.getRelativeCoordsUsingParent(yParent)).length == 1) {
                o.delChild(this);
                o = null
            }
        }
        xParent = null;
        yParent = null;

        // FIXME: Maybe the parent object is also a line. Then -1 is not correct
        //        But it is not coded to attach relative objects to lines. So it is no big
        //        deal to leave this as it is.
        if(num === -1) {
            this.conf.x = this.parseCoord(x, 'x', false);
            this.conf.y = this.parseCoord(y, 'y', false);
        } else {
            var old  = this.conf.x.split(',');
            old[num] = this.parseCoord(x, 'x', false);
            this.conf.x = old.join(',');

            old  = this.conf.y.split(',');
            old[num] = this.parseCoord(y, 'y', false);
            this.conf.y = old.join(',');
            old = null;
        }
    },

    // Transform the current coords to relative
    // coords to the given object
    makeRelativeCoords: function(oParent, num) {
        var xParent = this.getCoordParent(this.conf.x, num);
        var yParent = this.getCoordParent(this.conf.y, num);

        var x = num === -1 ? this.conf.x : this.conf.x.split(',')[num];
        var y = num === -1 ? this.conf.y : this.conf.y.split(',')[num];

        if(isRelativeCoord(x) && isRelativeCoord(y)) {
            // Skip this when already relative to the same object
            if(xParent == oParent.conf.object_id
              && yParent == oParent.conf.object_id)
                return;

            // If this object was attached to another parent before, remove the attachment
            if(xParent != oParent.conf.object_id) {
                var o = getMapObjByDomObjId(xParent);
                if(o) {
                    o.delChild(this);
                    o = null;
                }
            }
            if(yParent != oParent.conf.object_id) {
                var o = getMapObjByDomObjId(yParent);
                if(o) {
                    o.delChild(this);
                    o = null;
                }
            }
        }

        // Add this object to the new parent
        oParent.addChild(this);

        // FIXME: Maybe the parent object is also a line. Then -1 is not correct
        //        But it is not coded to attach relative objects to lines. So it is no big
        //        deal to leave this as it is.
        if(num === -1) {
            this.conf.x = this.getRelCoords(oParent, this.parseCoord(this.conf.x, 'x', false), 'x', -1);
            this.conf.y = this.getRelCoords(oParent, this.parseCoord(this.conf.y, 'y', false), 'y', -1);
        } else {
            var newX = this.getRelCoords(oParent, this.parseCoords(this.conf.x, 'x', false)[num], 'x', -1);
            var newY = this.getRelCoords(oParent, this.parseCoords(this.conf.y, 'y', false)[num], 'y', -1);

            var old  = this.conf.x.split(',');
            old[num] = newX;
            this.conf.x = old.join(',');

            old  = this.conf.y.split(',');
            old[num] = newY;
            this.conf.y = old.join(',');
        }
    },

    /**
     * Returns the object id of the parent object
     */
    getCoordParent: function(val, num) {
        var coord = num === -1 ? val.toString() : val.split(',')[num].toString();
        return coord.search('%') !== -1 ? coord.split('%')[0] : coord;
    },

    getRelCoords: function(refObj, val, dir, num) {
        var refPos = num === -1 ? refObj.conf[dir] : refObj.conf[dir].split(',')[num];
        var offset = parseInt(val) - parseInt(refObj.parseCoord(refPos, dir, false));
        var pre    = offset >= 0 ? '+' : '';
        val        = refObj.conf.object_id + '%' + pre + offset;
        refObj     = null;
        return val;
    },

    /**
     * Calculates new coordinates for the object where the given parameter
     * 'val' is the integer representing the current position of the object
     * in absolute px coordinates. If the object position is related to
     * another object this function detects it and transforms the abslute px
     * coordinate to a relative coordinate and returns it.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    calcNewCoord: function(val, dir, num) {
        if(!isset(num))
            var num = -1;

        var oldVal = num === -1 ? this.conf[dir] : this.conf[dir].split(',')[num];
        // Check if the current value is an integer or a relative coord
        if(isset(oldVal) && isRelativeCoord(oldVal)) {
            // This must be an object id
            var objectId = null;
            if(oldVal.search('%') !== -1)
                objectId = oldVal.split('%')[0];
            else
                objectId = oldVal;

            // Only an object id. Get the coordinate and return it
            var refObj = getMapObjByDomObjId(objectId);
            // FIXME: Maybe the parent object is also a line. Then -1 is not correct
            if(refObj)
                val = this.getRelCoords(refObj, val, dir, -1);
            objectId = null;
        } else if(num === -1) {
            val = Math.round(val);
        }
        oldVal = null;

        if(num === -1) {
            return val;
        } else {
            var old  = this.conf[dir].split(',');
            if(isRelativeCoord(val))
                old[num] = val;
            else
                old[num] = Math.round(val);
            return old.join(',');
        }
    },

    /**
     * Used to gather all referenced parent object ids from the object
     * configuration. Returns a object where the keys are the gathered
     * parent object ids.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    getParentObjectIds: function(num) {
        var parentIds = {};

        if(isset(num))
            var coords = (this.conf['x'].split(',')[num] + ',' + this.conf['y'].split(',')[num]).split(',');
        else
            var coords = (this.conf.x + ',' + this.conf.y).split(',');

        for(var i = 0, len = coords.length; i < len; i++)
            if(isRelativeCoord(coords[i]))
                if(coords[i].search('%') !== -1)
                    parentIds[coords[i].split('%')[0]] = true;
                else
                    parentIds[coords[i]] = true;
        coords = null;

        return parentIds;
    },

    /**
     * Returns the coord indexes which use a specific parent object_id
     */
    getRelativeCoordsUsingParent: function(parentId) {
        var matches = {};
        for(var i = 0, len = this.conf.x.split(',').length; i < len; i++) {
        if(this.getCoordParent(this.conf.x, i) === parentId && !isset(matches[i]))
            matches[i] = true;
        else if(this.getCoordParent(this.conf.y, i) === parentId && !isset(matches[i]))
            matches[i] = true;
        }
        return matches;
    },

    /**
     * This is used to add a child item to the object. Child items are
     * gathered automatically by the frontend. Child positions depend
     * on the related parent position on the map -> relative positioning.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    addChild: function(obj) {
        if(this.childs.indexOf(obj) === -1)
            this.childs.push(obj);
        obj = null;
    },

    delChild: function(obj) {
        this.childs.splice(this.childs.indexOf(obj), 1);
        obj = null;
    },

    /**
     * This method removes all attached map objects and make their coordinates
     * absolute.
     *
     * Find the coords which have a relative coord and are using
     * this object id as parent object. Then make these coordinates
     * absolute using child.makeAbsoluteCoords(num).
     * After that the change must be sent to the core using saveObject...
     */
    detachChilds: function() {
        for(var i = this.childs.length - 1; i >= 0; i--) {
        var nums = this.childs[i].getRelativeCoordsUsingParent(this.conf.object_id);
        var obj = this.childs[i];

        for(var num in nums) {
            obj.makeAbsoluteCoords(num);
        }

        saveObjectAttr(obj.conf.object_id, {'x': obj.conf.x, 'y': obj.conf.y });

        obj  = null;
        nums = null;
        }
    },

    /**
     * Moves the icon to it's location as described by this js object
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    moveIcon: function () {
        var container = document.getElementById(this.conf.object_id + '-icondiv');
        container.style.top  = this.parseCoord(this.conf.y, 'y') + 'px';
        container.style.left = this.parseCoord(this.conf.x, 'x') + 'px';
        container = null;
    },

    /**
     * Returns the x coordinate of the object in px
     */
    parsedX: function() {
        return this.parseCoords(this.conf.x, 'x');
    },

    /**
     * Returns the y coordinate of the object in px
     */
    parsedY: function() {
        return this.parseCoords(this.conf.y, 'y');
    },

    /**
     * Entry point for repositioning objects in NagVis frontend
     * Handles whole redrawing of the object while moving
     *
     * Author: Lars Michelsen <lars@vertical-visions.de>
     */
    reposition: function() {
        if(this.conf.view_type === 'line' || this.conf.type === 'line')
            this.drawLine();
        else if(this.conf.type === 'textbox' || this.conf.type === 'container')
            this.moveBox();
        else
            this.moveIcon();

        // Move the objects label when enabled
        if(this.conf.label_show && this.conf.label_show == '1')
            this.updateLabel();

        // Move child objects
        for(var i = 0, l = this.childs.length; i < l; i++)
            this.childs[i].reposition();

        // redraw the controls
        if(!this.bIsLocked)
            this.redrawControls();
    },

    /*** CONTROL FUNCTIONS ***/

    redrawControls: function () {
        if(typeof(this.removeControls) == 'function')
            this.removeControls();
        if(typeof(this.parseControls) == 'function')
            this.parseControls();
    },

    parseControls: function () {
        // Ensure the controls container exists
        var oControls = document.getElementById(this.conf.object_id+'-controls');
        if(!oControls) {
            oControls = document.createElement('div');
            oControls.setAttribute('id', this.conf.object_id+'-controls');
            if(this.parsedObject)
                this.parsedObject.appendChild(oControls);
        }
        oControls = null;

        if(this.conf.view_type === 'line' || this.conf.type === 'line')
            this.parseLineControls();
        else if(this.conf.view_type === 'icon' || this.conf.view_type === 'gadget')
            this.parseIconControls();
        else if(this.conf.type === 'textbox' || this.conf.type === 'container')
            this.parseBoxControls();
        else if(this.conf.type === 'shape')
            this.parseShapeControls();
    },

    addControl: function (obj) {
        var o = document.getElementById(this.conf.object_id+'-controls');
        if(o) {
            // Add to DOM
            o.appendChild(obj);
            o = null;

            // Add to controls list
            this.objControls.push(obj);
        }
    },

    /**
     * Toggles the position of the line middle. The mid of the line
     * can either be the 2nd of three line coords or is automaticaly
     * the middle between two line coords.
     */
    toggleLineMidLock: function() {
        // What is the current state?
        var x = this.conf.x.split(',');
        var y = this.conf.y.split(',')

        if(this.conf.line_type != 10 && this.conf.line_type != 13
           && this.conf.line_type != 14 && this.conf.line_type != 15) {
            alert('Not available for this line. Only lines with 2 line parts have a middle coordinate.');
            return;
        }

        if(x.length == 2) {
            // The line has 2 coords configured
            // - Calculate and add the 3rd coord as 2nd
            // - Add a drag control for the 2nd coord
            this.conf.x = [
              x[0],
              middle(this.parseCoords(this.conf.x, 'x', false)[0], this.parseCoords(this.conf.x, 'x', false)[1], this.conf.line_cut),
              x[1],
            ].join(',');
            this.conf.y = [
                y[0],
                middle(this.parseCoords(this.conf.y, 'y', false)[0], this.parseCoords(this.conf.y, 'y', false)[1], this.conf.line_cut),
                y[1],
            ].join(',');
        } else {
            // The line has 3 coords configured
            // - Remove the 2nd coord
            // - Remove the drag control for the 2nd coord
            this.conf.x = [ x[0], x[2] ].join(',');
            this.conf.y = [ y[0], y[2] ].join(',');
        }

        // send to server
        saveObjectAttr(this.conf.object_id, { 'x': this.conf.x, 'y': this.conf.y});

        // redraw the controls
        if(!this.bIsLocked)
            this.redrawControls();

        // redraw the line
        this.drawLine();
    },

    needsContextMenu: function () {
        return (this.conf.context_menu && this.conf.context_menu !== '' && this.conf.context_menu !== '0'
            && this.conf.context_template && this.conf.context_template !== '') || !this.bIsLocked;
    },

    needsHoverMenu: function() {
        return this.conf.hover_menu && this.conf.hover_menu !== '' && this.conf.hover_menu !== '0'
            && ((this.conf.hover_template && this.conf.hover_template !== '') ||
                (this.conf.hover_url && this.conf.hover_url !== ''));
    },

    needsLink: function() {
        return this.conf.url && this.conf.url !== '' && this.conf.url !== '#';
    },

    needsLineHoverArea: function() {
        return this.needsHoverMenu() || this.needsContextMenu() || this.needsLink() || !this.bIsLocked;
    },

    parseLineHoverArea: function(oContainer) {
        // This is only the container for the hover/label elements
        // The real area or labels are added later
        var oLink = document.getElementById(this.conf.object_id+'-linelink');
        if(!oLink) {
            var oLink = document.createElement('a');
            oLink.setAttribute('id', this.conf.object_id+'-linelink');
            oLink.setAttribute('class', 'linelink');
            oLink.setAttribute('className', 'linelink');
            oLink.href = this.conf.url;
            oLink.target = this.conf.url_target;

            oContainer.appendChild(oLink);
        }

        // Hide if not needed, show if needed
        if(!this.needsLineHoverArea()) {
            oLink.style.display = 'none';
        } else {
            oLink.style.display = 'block';
        }

        oLink = null;
        oContainer = null;
    },

    removeLineHoverArea: function() {
        if(!this.needsLineHoverArea()) {
            var area = document.getElementById(this.conf.object_id+'-linelink');
            area.style.display = 'none';
            area = null;
        }
    },

    parseLineControls: function () {
        var x = this.parseCoords(this.conf.x, 'x');
        var y = this.parseCoords(this.conf.y, 'y');

        var size = oGeneralProperties['controls_size'];
	var lineEndSize = size;
	if(size < 20)
	    lineEndSize = 20;
        for(var i = 0, l = x.length; i < l; i++) {
	    // Line middle drag coord needs to be smaller
	    if(l > 2 && i == 1) 
		this.parseControlDrag(i, x[i], y[i], - size / 2, - size / 2, size);
	    else
		this.parseControlDrag(i, x[i], y[i], - lineEndSize / 2, - lineEndSize / 2, lineEndSize);
            makeDragable([this.conf.object_id+'-drag-'+i], this.saveObject, this.moveObject);
        }

        if(this.conf.view_type === 'line' && (this.conf.line_type == 10
           || this.conf.line_type == 13 || this.conf.line_type == 14 || this.conf.line_type == 15))
	    this.parseControlToggleLineMid(x.length+2, this.getLineMid(this.conf.x, 'x'), this.getLineMid(this.conf.y, 'y'), 20 - size / 2, -size / 2 + 5, size);

        lineEndSize = null;
        size = null;
        x = null;
        y = null;
    },

    getLineMid: function(coord, dir) {
        var c = coord.split(',');
        if(c.length == 2)
            return middle(this.parseCoords(coord, dir)[0],
                          this.parseCoords(coord, dir)[1],
                          this.conf.line_cut);
        else
            return this.parseCoords(coord, dir)[1];
    },

    lineCoords: function() {
        return [
            this.parseCoords(this.conf.x, 'x'),
            this.parseCoords(this.conf.y, 'y'),
            [this.conf.line_cut, this.conf.line_label_pos_in, this.conf.line_label_pos_out]
        ];
    },

    removeControls: function() {
        var oControls = document.getElementById(this.conf.object_id+'-controls');
        if(oControls)
            for(var i = oControls.childNodes.length; i > 0; i--)
                oControls.removeChild(oControls.childNodes[0]);
        this.objControls = [];
        oControls = null;

        if(this.conf.type === 'textbox' || this.conf.type === 'container') {
            this.removeBoxControls();
            makeUndragable([this.conf.object_id+'-label']);
        } else {
            makeUndragable([this.conf.object_id+'-icondiv']);
        }
        
        if(this.conf.view_type === 'line' || this.conf.type === 'line')
            this.removeLineHoverArea();
    },

    parseControlDrag: function (num, objX, objY, offX, offY, size) {
        var ctl = document.createElement('div');
        ctl.setAttribute('id',         this.conf.object_id+'-drag-' + num);
        ctl.setAttribute('class',     'control drag');
        ctl.setAttribute('className', 'control drag');
	// FIXME: Multilanguage
	ctl.title          = 'Move object';
        ctl.style.zIndex   = parseInt(this.conf.z)+1;
        ctl.style.width    = addZoomFactor(size) + 'px';
        ctl.style.height   = addZoomFactor(size) + 'px';
        ctl.style.left     = (objX + offX) + 'px';
        ctl.style.top      = (objY + offY) + 'px';
        ctl.objOffsetX     = offX;
        ctl.objOffsetY     = offY;

        ctl.onmouseover = function() {
            document.body.style.cursor = 'move';
        };

        ctl.onmouseout = function() {
            document.body.style.cursor = 'auto';
        };

        this.addControl(ctl);
        ctl = null;
    },

    /**
     * Adds the modify button to the controls including
     * all eventhandlers
     *
     * Author: Lars Michelsen <lm@larsmichelsen.com>
     */
    parseControlToggleLineMid: function (num, objX, objY, offX, offY, size) {
        var ctl= document.createElement('div');
        ctl.setAttribute('id',         this.conf.object_id+'-togglemid-' + num);
        ctl.setAttribute('class',     'control togglemid');
        ctl.setAttribute('className', 'control togglemid');
	// FIXME: Multilanguage
	ctl.title          = 'Lock/Unlock line middle';
        ctl.style.zIndex   = parseInt(this.conf.z)+1;
        ctl.style.width    = addZoomFactor(size) + 'px';
        ctl.style.height   = addZoomFactor(size) + 'px';
        ctl.style.left     = (objX + offX) + 'px';
        ctl.style.top      = (objY + offY) + 'px';
        ctl.objOffsetX     = offX;
        ctl.objOffsetY     = offY;

        ctl.onclick = function(event) {
            // In the event handler this points to the ctl object
            var arr   = this.id.split('-');
            var objId = arr[0];

	    toggleLineMidLock(event, objId);
	    contextHide();

            objId = null;
            arr   = null;

            document.body.style.cursor = 'auto';
        };

        ctl.onmouseover = function() {
            document.body.style.cursor = 'pointer';
        };

        ctl.onmouseout = function() {
            document.body.style.cursor = 'auto';
        };

        this.addControl(ctl);
        ctl = null;
    },

    /**
     * Handler for the move event
     *
     * Important: This is called from an event handler
     * the 'this.' keyword can not be used here.
     */
    moveObject: function(obj) {
        var arr        = obj.id.split('-');
        var objId      = arr[0];
        if(arr.length > 1)
            var anchorType = arr[1];

        var newPos;
        var viewType = getDomObjViewType(objId);

        var jsObj = getMapObjByDomObjId(objId);

        if(viewType === 'line') {
            newPos = getMidOfAnchor(obj);

            // Get current positions and replace only the current one
            var anchorId   = arr[2];
            newPos = [ jsObj.calcNewCoord(newPos[0], 'x', anchorId),
                       jsObj.calcNewCoord(newPos[1], 'y', anchorId) ];

            var parents = jsObj.getParentObjectIds(anchorId);

            anchorId   = null;
        } else {
            // In case of an anchor there is an offset to the real object.
            // Handle this offset in the coordinate calculation for the obj
            var offsetX = isset(obj.objOffsetX) ? obj.objOffsetX : 0;
            var offsetY = isset(obj.objOffsetY) ? obj.objOffsetY : 0;

            newPos = [ jsObj.calcNewCoord(obj.x - offsetX, 'x'),
                       jsObj.calcNewCoord(obj.y - offsetY, 'y') ];

            var parents = jsObj.getParentObjectIds();
        }

        // Highlight parents when relative
        for (var objectId in parents) {
            var p = getMapObjByDomObjId(objectId);
            if(p)
            p.highlight(true);
            p = null;
        }
        parents = null;

        jsObj.conf.x = newPos[0];
        jsObj.conf.y = newPos[1];

        jsObj.reposition();

        jsObj      = null;
        objId      = null;
        anchorType = null;
        newPos     = null;
        viewType   = null;
    },

    /**
     * Handler for the drop event
     *
     * Important: This is called from an event handler
     * the 'this.' keyword can not be used here.
     */
    saveObject: function(obj, oParent) {
        var arr        = obj.id.split('-');
        var objId      = arr[0];
        if(arr.length > 2)
            var anchorId = arr[2];
        var viewType   = getDomObjViewType(objId);
        var jsObj      = getMapObjByDomObjId(objId);

        if(viewType !== 'line')
            anchorId = -1;

        // Honor the enabled grid and reposition the object after dropping
        if(useGrid()) {
            if(viewType === 'line') {
               var pos = coordsToGrid(jsObj.parseCoords(jsObj.conf.x, 'x', false)[anchorId],
                                      jsObj.parseCoords(jsObj.conf.y, 'y', false)[anchorId]);
               jsObj.conf.x = jsObj.calcNewCoord(pos[0], 'x', anchorId);
               jsObj.conf.y = jsObj.calcNewCoord(pos[1], 'y', anchorId);
               pos = null;
            } else {
               var pos = coordsToGrid(jsObj.parseCoord(jsObj.conf.x, 'x', false),
                                      jsObj.parseCoord(jsObj.conf.y, 'y', false));
               jsObj.conf.x = jsObj.calcNewCoord(pos[0], 'x');
               jsObj.conf.y = jsObj.calcNewCoord(pos[1], 'y');
               pos = null;
            }
            jsObj.reposition();
        }

        // Make relative when oParent set and not already relative
        if(isset(oParent))
            if(oParent !== false)
                jsObj.makeRelativeCoords(oParent, anchorId);
            else
                jsObj.makeAbsoluteCoords(anchorId);

        saveObjectAfterAnchorAction(obj);

        // Remove the dragging hand after dropping
        document.body.style.cursor = 'auto';

        arr      = null;
        objId    = null;
        anchorId = null;
        jsObj    = null;
    },

    /**
     * Returns the object ID of the object
     */
    getJsObjId: function() {
        if(this.conf.view_type && this.conf.view_type === 'line')
            return this.conf.object_id+'-linelink';
        else
            return this.conf.object_id+'-icon';
    },

    /**
     * PUBLIC toggleObjectActions()
     *
     * This enables/disables the hover menu and the icon link
     * temporary. e.g. in unlocked mode the hover menu shal be suppressed.
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    toggleObjectActions: function(enable) {
	var o = document.getElementById(this.getJsObjId());
	if(o) {
	    if(enable && isset(o.disabled_onmousemove)) {
                // Hover-menu
		o.onmousemove = o.disabled_onmousemove;
		o.onmouseout  = o.disabled_onmouseout;
		o.disabled_onmousemove = null;
		o.disabled_onmouseout  = null;

                // Link (Left mouse action)
                if(o.parentNode.tagName == 'A')
                    o.parentNode.onclick = null;
	    } else if(!enable) {
                // Hover-menu
		o.disabled_onmousemove = o.onmousemove;
		o.disabled_onmouseout  = o.onmouseout;
		o.onmousemove = null;
		o.onmouseout  = null;

                // Link (Left mouse action)
                if(o.parentNode.tagName == 'A')
                    o.parentNode.onclick = function(event) {
                        var event = !event ? window.event : event;
                        if(event.stopPropagation)
                            event.stopPropagation();
                        event.cancelBubble = true;
                        return false;
                    };
	    }
            o = null;
	}
    },

    highlight: function(show) {}
});
