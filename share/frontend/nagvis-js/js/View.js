/*****************************************************************************
 *
 * View.js - Base class for all NagVis view classes
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

var View = Base.extend({
    id      : null,
    dom_obj : null,
    sum_obj : null,
    objects : null,

    constructor: function(id) {
        this.id = id;
        this.objects = {};
    },

    update: function(args) {
        var show = isset(args.show) ? '&show='+args.show : '';

        var data = [];
        for (var i = 0, len = args.data.length; i < len; i++)
            data.push('i[]='+args.data[i]);

        // Get the updated objects via bulk request
        call_ajax(oGeneralProperties.path_server+'?mod=' + args.mod + '&act=getObjectStates'
                       + show +'&ty=state'+getViewParams() + this.getFileAgeParams(), {
            response_handler : this.handleUpdate.bind(this),
            method           : "POST",
            post_data        : data.join('&')
        });

        // Need to re-raise repeated events?
        this.handleRepeatEvents();
    },

    render: function() {
        addEvent(window, 'resize', function() { g_view.scaleView() });
        addEvent(window, 'scroll', function() { g_view.scaleView() });
        this.scaleView();
    },

    /**
     * This callback function handles the ajax response of bulk object
     * status updates
     */
    handleUpdate: function(o) {
        // Stop processing these informations when the current view should not be
        // updated at the moment e.g. when reparsing the map after a changed mapcfg
        if (this.blockUpdates) {
            eventlog("ajax", "info", "Throwing new object information away since the view is blocked");
            return false;
        }

        if (!o) {
            eventlog("ajax", "info", "handleUpdate: got empty object. Terminating.");
            return false;
        }

        // Procees the "config changed" responses
        if (isset(o['status']) && o['status'] == 'CHANGED') {
            var oChanged = o['data'];

            for (var key in oChanged) {
                if (key == 'maincfg') {
                    eventlog("worker", "info", "Main configuration file was updated. Need to reload the page");
                    // Clear the scheduling timeout to prevent problems with FF4 bugs
                    if (g_worker_id)
                        window.clearTimeout(g_worker_id);
                    window.location.reload(true);
                    return;

                } else {
                    if (g_view.hasUnlocked()) {
                        eventlog("worker", "info", "Map config updated, but having unlocked objects - not reloading.");
                    } else {
                        eventlog("worker", "info", "Map configuration file was updated. Rerendering the map.");
                        g_view.render();
                        g_view.updateFileAges(oChanged);
                        return;
                    }
                }
            }
        }

        // I don't think empty maps make any sense. So when no objects are present:
        // Try to fetch them continously
        if (oLength(g_view.objects) === 0) {
            eventlog("worker", "info", "No objects found, reparsing...");
            g_view.init();
            return;
        }

        /*
         * Now proceed with real actions when everything is OK
         */

        if (o.length > 0)
            this.updateObjects(o, true);
    },

    updateFileAges: function(data) {
        for (var key in data)
            oFileAges[key] = data[key];
    },

    // Bulk update object states and then visualize eventual changes
    updateObjects: function(attrs, only_state) {
        var at_least_one_changed = false;

        // Loop all object which have new information
        for (var i = 0, len = attrs.length; i < len; i++) {
            var objectId = attrs[i].object_id;

            // Object not found. This should only be happen for new objects which have
            // just been added to the map by the user. In this case we always have "full"
            // object information, not only state attrs
            if (!isset(this.objects[objectId])) {
                // this is only relevant for ViewMap at the moment
                this.addObject(attrs[i]);
                this.renderObject(objectId);
                at_least_one_changed = true; // always reload summary state
            }
            else {
                at_least_one_changed &= this.objects[objectId].updateAttrs(attrs[i], only_state);
            }
        }

        if (at_least_one_changed)
            this.handleStateChanged();
    },

    handleStateChanged: function() {},

    // Is called by an object to add the rendered dom object to the map
    drawObject: function(obj) {},
    eraseObject: function(obj) {},

    /**
     * Is called by the worker function to check all objects for repeated
     * events to be triggered independent of the state updates.
     */
    handleRepeatEvents: function() {
        eventlog("worker", "debug", "handleRepeatEvents: Start");
        for (var i in this.objects) {
            // Trigger repeated event checking/raising for eacht stateful object which
            // has event_time_first set (means there was an event before which is
            // required to be repeated some time)
            if (this.objects[i].has_state && this.objects[i].event_time_first !== null) {
                this.objects[i].checkRepeatEvents();
            }
        }
        eventlog("worker", "debug", "handleRepeatEvents: End");
    },

    getFileAgeParams: function() {
        var addParams = '';
        if (g_view.type === 'map' && this.id !== false)
            addParams = '&f[]=map,' + this.id + ',' + oFileAges[this.id];
        return '&f[]=maincfg,maincfg,' + oFileAges['maincfg'] + addParams;
    },

    // Detects objects with deprecated state information
    getObjectsToUpdate: function() {
        eventlog("worker", "debug", "getObjectsToUpdate: Start");
        var stateful = [],
            stateless = [];

        // Assign all object which need an update indexes to return Array
        for (var i in this.objects) {
            if (this.objects[i].lastUpdate <= iNow - oWorkerProperties.worker_update_object_states) {
                if (this.objects[i] instanceof NagVisStatefulObject) {
                    stateful.push(i);
                } else if (this.objects[i].conf.enable_refresh
                           && this.objects[i].conf.enable_refresh == '1') {
                    // Do not update stateless objects where enable_refresh=0
                    stateless.push(i);
                }
            }
        }

        eventlog("worker", "debug", "getObjectsToUpdate: Stateful: "+stateful.length
                                   +" Stateless: "+stateless.length);
        return [stateful, stateless];
    },

    // Renders basic information like favicon and page title
    renderPageBasics: function() {
        if (this.sum_obj)
            favicon.change(this.getFaviconImage(this.sum_obj.conf));

        document.title = oPageProperties.page_title;

        if (this.sum_obj)
            document.body.style.backgroundColor = this.getBackgroundColor(this.sum_obj.conf);
    },

    // Gets the background color of the map by the summary state of the map
    getBackgroundColor: function(oObj) {
        if (!oPageProperties.event_background || oPageProperties.event_background != '1')
            return oPageProperties.background_color;

        var sColor;
        // When state is PENDING, OK, UP, set default background color
        if (!oObj.summary_state || oObj.summary_state == 'PENDING'
           || oObj.summary_state == 'OK' || oObj.summary_state == 'UP')
            sColor = oPageProperties.background_color;
        else {
            sColor = oStates[oObj.summary_state].bgcolor;

            // Ack or downtime?
            if (oObj.summary_in_downtime && oObj.summary_in_downtime === 1)
                if (isset(oStates[oObj.summary_state]['downtime_bgcolor'])
                    && oStates[oObj.summary_state]['downtime_bgcolor'] != '')
                    sColor = oStates[oObj.summary_state]['downtime_bgcolor'];
                else
                    sColor = lightenColor(sColor, 100, 100, 100);

            else if (oObj.summary_problem_has_been_acknowledged
                     && oObj.summary_problem_has_been_acknowledged === 1)

                if (isset(oStates[oObj.summary_state]['ack_bgcolor'])
                    && oStates[oObj.summary_state]['ack_bgcolor'] != '')
                    sColor = oStates[oObj.summary_state]['ack_bgcolor'];
                else
                    sColor = lightenColor(sColor, 100, 100, 100);
        }
        return sColor;
    },
    
    // Gets the favicon of the page representating the state of the view
    getFaviconImage: function(oObj) {
        var sFavicon;
    
        if (oObj.summary_in_downtime && oObj.summary_in_downtime === 1)
            sFavicon = 'downtime';
        else if (oObj.summary_problem_has_been_acknowledged
                 && oObj.summary_problem_has_been_acknowledged === 1)
            sFavicon = 'ack';
        else if (oObj.summary_state.toLowerCase() == 'unreachable')
            sFavicon = 'down';
        else
            sFavicon = oObj.summary_state.toLowerCase();
        
        return oGeneralProperties.path_images+'internal/favicon_'+sFavicon+'.png';
    },

    /**
     * Hack to scale elements which should fill 100% of the windows viewport. The
     * problem here is that some map objects might increase the width of the map
     * so that the viewport is not enough to display the whole map. In that case
     * elements with a width of 100% don't scale to the map width. Instead of this
     * the elements are scaled to the viewports width.
     * This changes the behaviour and resizes the 100% elements to the map size.
     *
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    scaleView: function() {
        var header  = document.getElementById('header');
        var content = this.dom_obj;
        if (!content)
            return;

        var headerSpacer = document.getElementById('headerspacer');
        if (header) {
            header.style.width = pageWidth() + 'px';
            if (headerSpacer) {
                headerSpacer.style.height = header.clientHeight + 'px';
                headerSpacer = null;
            }
        }

        content.style.top = getHeaderHeight() + 'px';

        if (typeof sidebarUpdatePosition == "function") {
            sidebarUpdatePosition();
        }
    },

    // Transforms the view specific coordinates to browser x/y coordinates.
    // This is executed when loading object coordinates from persistance.
    project: function(x, y) {
        return [x, y];
    },

    // Transforms the X/Y coordinates to view specific coords, like
    // on worldmaps to lat/long coordinates. This is executed when
    // persisting the object coordinates after a change
    unproject: function(x, y) {
        return [x, y];
    },

});
