/*****************************************************************************
 *
 * View.js - Base class for all NagVis view classes
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
        var show = args.show ? '&show='+args.show : '';

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
                    if (workerTimeoutID)
                        window.clearTimeout(workerTimeoutID);
                    window.location.reload(true);
                    return;
    
                } else {
                    if (iNumUnlocked > 0) {
                        eventlog("worker", "info", "Map config updated. "+iNumUnlocked+" objects unlocked - not reloading.");
                    } else {
                        eventlog("worker", "info", "Map configuration file was updated. Reparsing the map.");
                        g_view.render();
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
            this.updateObjects(o);
    },

    // Bulk update object states and then visualize eventual changes
    updateObjects: function(state_infos) {
        var at_least_one_changed = false;
    
        // Loop all object which have new information
        for (var i = 0, len = state_infos.length; i < len; i++) {
            var objectId = state_infos[i].object_id;
    
            // Object not found
            if (!isset(this.objects[objectId])) {
                eventlog("updateObjects", "critical", "Could not find an object with "
                                                     +"the id "+objectId+" in object array");
                return false;
            }
    
            at_least_one_changed &= this.objects[objectId].update_state(state_infos[i]);
        }
    
        if (at_least_one_changed)
            this.handleStateChanged();
    },

    handleStateChanged: function() {},

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
                // Do not update objects where enable_refresh=0
                if (this.objects[i].conf.type !== 'textbox' 
                   && this.objects[i].conf.type !== 'shape'
                   && this.objects[i].conf.type !== 'container') {
                    stateful.push(i);
                } else if (this.objects[i].conf.enable_refresh
                           && this.objects[i].conf.enable_refresh == '1') {
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
     * @author  Lars Michelsen <lars@vertical-visions.de>
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

        sidebarUpdatePosition();
    }

});
