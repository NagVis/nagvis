/*****************************************************************************
 *
 * ViewMap.js - All NagVis map related top level code
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

var ViewMap = View.extend({
    type           : 'map',
    // is set to true on first rendering
    rendered       : false,
    // This is turned to true when the map is currently reparsing (e.g. due to
    // a changed map config file). This blocks object updates.
    blockUpdates   : false,
    // The number of currently unlocked objects for editing. When this is
    // above 0, it will lead to block state updates till it's 0 again
    num_unlocked   : false,

    constructor: function(id) {
        this.base(id);
    },

    init: function() {
        if (!usesSource('worldmap')) {
            renderZoombar();
            addEvent(document, 'mousedown', context_handle_global_mousedown);
        }

        this.render();
    },

    update: function() {
        var to_update = this.getObjectsToUpdate();

        if (to_update[0].length > 0) {
            this.base({
                mod  : 'Map',
                show : this.id,
                data : to_update[0]
            });
        }

        this.rerenderStatelessObjects(to_update[1]);
    },

    /**
     * END OF PUBLIC METHODS
     */

    // Parses the map on initial page load or changed map configuration
    render: function() {
        this.dom_obj = document.getElementById('map');

        // Is updated later by this.getProperties(), but we might need it in
        // the case an error occurs in getProperties() and one needs
        // the map name anyways, e.g. to detect the currently open map
        // during map deletion
        oPageProperties.map_name = this.id;

        // Block updates of the current map
        this.blockUpdates = true;

        var wasInMaintenance = inMaintenance(false);

        // Get new map properties from server on changed map cfg
        if (this.rendered)
            this.updateProperties();

        if(inMaintenance()) {
            this.blockUpdates = false;
            return false
        } else if(wasInMaintenance === true) {
            // Hide the maintenance message when it was in maintenance before
            frontendMessageRemove('maintenance');
        }
        wasInMaintenance = null;

        call_ajax(oGeneralProperties.path_server + '?mod=Map&act=getMapObjects&show='
                  + this.id + getViewParams(), {
            response_handler : this.handleMapInit.bind(this),
            error_handler    : this.handleMapInitError.bind(this)
        });

        this.rendered = true;
        this.base();
    },

    handleMapInitError: function(status_code, response, handler_data) {
        hideStatusMessage();
        if (response === null) {
            var response = {
                'type'    : 'error',
                'title'   : 'Error: Invalid response',
                'message' : 'Got empty response from server (code: ' + status_code + '). '
                          + 'Take a look at the web server error log for details.',
            };
        }
        frontendMessage(response, 'serverError');
    },

    handleMapInit: function(oObjects) {
        // Only perform the rendering actions when all information are available
        if (!oObjects) {
            hideStatusMessage();
            return;
        }

        // Remove all old objects
        for (var i in this.objects) {
            var obj = this.objects[i];
            if(obj && typeof obj.remove === 'function') {
                // Remove parsed object from map
                obj.remove();

                if(!obj.bIsLocked)
                    this.updateNumUnlocked(-1);

                // Remove element from object container
                delete this.objects[i];
            }
        }

        if (usesSource('worldmap')) {
            g_map_objects.clearLayers();
        }

        eventlog("worker", "info", "Parsing "+this.type+" objects");
        this.initializeObjects(oObjects);

        // Maybe force page reload when the map shal fill the viewport
        if (getViewParam('zoom') == 'fill')
            set_fill_zoom_factor();

        // Set map basics
        // Needs to be called after the summary state of the map is known
        this.renderMapBasics();

        // When user searches for an object highlight it
        if(oViewProperties && oViewProperties.search && oViewProperties.search != '') {
            eventlog("worker", "info", "Searching for matching object(s)");
            searchObjects(oViewProperties.search);
        }

        hideStatusMessage();

        // Updates are allowed again
        this.blockUpdates = false;
    },

    addObject: function(attrs) {
        var obj;
        switch (attrs.type) {
            case 'host':
                obj = new NagVisHost(attrs);
            break;
            case 'service':
                obj = new NagVisService(attrs);
            break;
            case 'hostgroup':
                obj = new NagVisHostgroup(attrs);
            break;
            case 'servicegroup':
                obj = new NagVisServicegroup(attrs);
            break;
            case 'dyngroup':
                obj = new NagVisDynGroup(attrs);
            break;
            case 'aggr':
                obj = new NagVisAggr(attrs);
            break;
            case 'map':
                obj = new NagVisMap(attrs);
            break;
            case 'textbox':
                obj = new NagVisTextbox(attrs);
            break;
            case 'container':
                obj = new NagVisContainer(attrs);
            break;
            case 'shape':
                obj = new NagVisShape(attrs);
            break;
            case 'line':
                obj = new NagVisLine(attrs);
            break;
            default:
                alert('Error: Unknown object type');
                return;
            break;
        }
    
        // Save the number of unlocked objects
        if (!obj.bIsLocked)
            this.updateNumUnlocked(1);
    
        // Put object to map objects array
        this.objects[obj.conf.object_id] = obj;
    },

    erase: function() {
        for (var i in this.objects) {
            this.objects[i].erase();
        }
    },

    renderObject: function(object_id) {
        var obj = this.objects[object_id];

        // FIXME: Are all these steps needed here?
        obj.update();
        obj.render();
    
        // add eventhandling when enabled via event_on_load option
        if (isset(oViewProperties.event_on_load) && oViewProperties.event_on_load == 1
           && obj.has_state && obj.hasProblematicState()) {
            obj.raiseEvents(false);
            obj.initRepeatedEvents();
        }

        // Store object dependencies
        var parents = obj.getParentObjectIds();
        if (parents) {
            for (var parentObjId in parents) {
                this.objects[parentObjId].addChild(obj);
            }
        }
    },

    // Add the objects dom_obj to the maps dom_obj
    drawObject: function(obj) {
        this.dom_obj.appendChild(obj.dom_obj);
    },

    // Removes the given objects dom_obj from the maps dom_obj 
    eraseObject: function(obj) {
        this.dom_obj.removeChild(obj.dom_obj);
    },

    // Does initial rendering of map objects
    initializeObjects: function(aMapObjectConf) {
        eventlog("worker", "debug", "initializeObjects: Start setting map objects");
    
        // Don't loop the first object - that is the summary of the current map
        this.sum_obj = new NagVisMap(aMapObjectConf[0]);
    
        for (var i = 1, len = aMapObjectConf.length; i < len; i++)
            this.addObject(aMapObjectConf[i]);
    
        // First parse the objects on the map
        // Then store the object position dependencies.
        // Before both can be done all objects need to be added
        // to the map objects list
        for (var i in this.objects)
            this.renderObject(i);

        eventlog("worker", "debug", "initializeObjects: End setting map objects");
    },

    // Sets basic information like background image
    renderMapBasics: function() {
        oPageProperties.page_title = oPageProperties.alias
                                     + ' (' + this.sum_obj.conf.summary_state + ') :: '
                                     + oGeneralProperties.internal_title;

        this.renderPageBasics();
        this.renderBackgroundImage();
    },

    renderBackgroundImage: function() {
        var sImage = oPageProperties.background_image;
        // Only work with the background image if some is configured
        if (typeof sImage !== 'undefined' && sImage !== 'none' && sImage !== '') {
            // Use existing image or create new
            var oImage = document.getElementById('backgroundImage');
            if (!oImage) {
                var oImage = document.createElement('img');
                oImage.id = 'backgroundImage';
                document.getElementById('map').appendChild(oImage);
            }

            addZoomHandler(oImage, true);
            oImage.src = sImage;
        }
    },

    // When at least one object state changed, fetch a new summary state from the server
    handleStateChanged: function() {
        call_ajax(oGeneralProperties.path_server + '?mod=Map&act=getObjectStates&show='+this.id
                        + '&ty=summary' + getViewParams(), {
            response_handler : this.handleSumObjUpdate.bind(this)
        });
    },

    // This function updates the map basics like background, favicon and title
    handleSumObjUpdate: function(objects) {
        this.sum_obj = new NagVisMap(objects[0]);
        this.renderMapBasics();
    },

    /**
     * Fetches the current map properties from the core. Normally this
     * is set during initial rendering, but needed when the configuration
     * has changed on the server.
     */
    updateProperties: function() {
        call_ajax(oGeneralProperties.path_server+'?mod=Map&act=getMapProperties&show='
                  + escapeUrlValues(this.id)+getViewParams(), {
            response_handler : function(props) {
                oPageProperties = props;
            }
        });
    },

    /**
     * Bulk reload, reparse shapes and containers which have enable_refresh=1
     * Stateless objects which shal be refreshed (enable_refresh=1) need a special
     * handling as they are reloaded by being reparsed.
     */
    rerenderStatelessObjects: function(objects) {
        for (var i = 0, len = objects.length; i < len; i++)
            this.objects[objects[i]].render();
    },

    removeObject: function(object_id) {
        var obj = this.objects[object_id];

        obj.detachChilds();
        saveObjectRemove(object_id);
        obj.remove();

        if (!obj.bIsLocked)
            this.updateNumUnlocked(-1);

        delete this.objects[object_id];
    },

    /**
     * OBJECT VIEW/EDIT LOCKING
     */

    hasUnlocked: function() {
        return this.num_unlocked > 0;
    },

    toggleObjectLock: function(object_id, lock) {
        this.updateNumUnlocked(this.objects[object_id].toggleLock(lock));
    },

    updateNumUnlocked: function(num) {
        this.num_unlocked += num;
        if (this.num_unlocked == 0) {
            // Not in edit mode anymore
            var o = document.getElementById('editIndicator');
            if (o)
                o.style.display = 'none';

            gridRemove();
        } else {
            // In edit mode (for at least one object)
            var o = document.getElementById('editIndicator');
            if (o)
                o.style.display = '';

            gridParse();
        }
    }

});
