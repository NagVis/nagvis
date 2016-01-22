/*****************************************************************************
 *
 * ViewOverview.js - All NagVis overview related top level code
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

var ViewOverview = View.extend({
    type           : 'overview',
    rendered_maps  : 0,
    processed_maps : 0,

    constructor: function() {
        this.base(null);
    },

    init: function() {
        addEvent(document, 'mousedown', context_handle_global_mousedown);
        this.renderPageBasics();
        this.render();
        this.loadMaps();
        this.loadRotations();
    },

    update: function() {
        var to_update = this.getObjectsToUpdate();
        if (to_update[0].length > 0) {
            this.base({
                mod  : 'Overview',
                data : to_update[0]
            });
        }
    },

    /**
     * END OF PUBLIC METHODS
     */

    render: function() {
        this.dom_obj = document.getElementById('overview');

        // Render maps and the rotations when enabled
        var types = [
            [ oPageProperties.showmaps,      'overviewMaps',      oPageProperties.lang_mapIndex ],
            [ oPageProperties.showrotations, 'overviewRotations', oPageProperties.lang_rotationPools ]
        ];
        for (var i = 0; i < types.length; i++) {
            if (types[i][0] === 1) {
                var h2 = document.createElement('h2');
                h2.innerHTML = types[i][2];
                this.dom_obj.appendChild(h2);

                var container = document.createElement('div');
                container.setAttribute('id', types[i][1]);
                container.className = 'infobox';
                this.dom_obj.appendChild(container);
            }
        }
        this.base();
    },

    // Adds a single map to the overview map list
    addMap: function(objects, data) {
        var map_name           = data[0],
            worldmap_has_bbox  = data[1];

        this.processed_maps += 1;

        // Exit this function on invalid call
        if (objects === null || objects.length != 1)  {
            eventlog("worker", "warning", "addOverviewMap: Invalid call - maybe broken ajax response ("+map_name+")");
            if (this.processed_maps == g_map_names.length)
                finishOverviewMaps();
            return false;
        }
        var map_conf = objects[0];

        // The worldmap preview summary state should reflect the state shown by the worldmap
        // when the user opens it. This means it is needed to calculate which area of the worldmap
        // will be shown to the user and then only deal with the objects within this area.
        // Bad here: For the worldmaps we need to do a second HTTP request which should be avoided
        if (!worldmap_has_bbox && map_conf['sources']
            && map_conf['sources'].indexOf('worldmap') !== -1) {

            var worldmap = L.map('overview', {
                markerZoomAnimation: false,
                maxBounds: [ [-85,-180.0], [85,180.0] ],
                minZoom: 2
            }).setView(map_conf['worldmap_center'].split(','), parseInt(map_conf['worldmap_zoom']));

            worldmap.remove();

            // leaflet does not clean up everything. We do it on our own.
            var overview = document.getElementById('overview');
            remove_class(overview, 'leaflet-container');
            remove_class(overview, 'leaflet-retina');
            remove_class(overview, 'leaflet-fade-anim');

            this.processed_maps -= 1;
            call_ajax(oGeneralProperties.path_server+'?mod=Overview&act=getObjectStates'
                      + '&i[]=map-' + escapeUrlValues(map_name)
                      + getViewParams({'bbox': worldmap.getBounds().toBBoxString()}), {
                response_handler : this.addMap.bind(this),
                handler_data     : [ map_name, true ]
            });
            return;
        }

        this.rendered_maps += 1; // also count errors

        var container = document.getElementById('overviewMaps');

        // Find the map placeholder div (replace it to keep sorting)
        var mapdiv = null;
        var child = null;
        for (var i = 0; i < container.childNodes.length; i++) {
            child = container.childNodes[i];
            if (child.id == map_name) {
                mapdiv = child;
                break;
            }
        }

        // render the map object
        var obj = new NagVisMap(map_conf);
        // Save object to map objects array
        this.objects[obj.conf.object_id] = obj;
        obj.update();
        obj.render();
        container.replaceChild(obj.dom_obj, mapdiv);

        // Finalize rendering after last map...
        if (this.processed_maps == g_map_names.length)
            this.finishMaps();
    },

    finishMaps: function() {
        // Hide the "Loading..." message. This is not the best place since rotations
        // might not have been loaded now but in most cases this is the longest running request
        hideStatusMessage();
    },

    // Does initial parsing of rotations on the overview page
    addRotations: function(rotations) {
        if (oPageProperties.showrotations === 1 && rotations.length > 0) {
            for (var i = 0, len = rotations.length; i < len; i++) {
                new NagVisRotation(rotations[i]).parseOverview();
            }
        } else {
            // Hide the rotations container
            var container = document.getElementById('overviewRotations');
            if (container) {
                container.style.display = 'none';
            }
        }
    },

    // Fetches all maps to be shown on the overview page
    loadMaps: function() {
        var map_container = document.getElementById('overviewMaps');

        if (oPageProperties.showmaps !== 1 || g_map_names.length == 0) {
            if (map_container)
                map_container.parentNode.style.display = 'none';
            hideStatusMessage();
            return false;
        }

        for (var i = 0, len = g_map_names.length; i < len; i++) {
            var mapdiv = document.createElement('div');
            mapdiv.setAttribute('id', g_map_names[i])
            map_container.appendChild(mapdiv);
            call_ajax(oGeneralProperties.path_server+'?mod=Overview&act=getObjectStates'
                      + '&i[]=map-' + escapeUrlValues(g_map_names[i]) + getViewParams(), {
                response_handler : this.addMap.bind(this),
                handler_data     : [ g_map_names[i], false ]
            });
        }
    },

    // Fetches all rotations to be shown on the overview page
    loadRotations: function() {
        call_ajax(oGeneralProperties.path_server+'?mod=Overview&act=getOverviewRotations', {
            response_handler: this.addRotations.bind(this)
        });
    }
});
