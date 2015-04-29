/*****************************************************************************
 *
 * ViewWorldmap.js - All NagVis worldmap related top level code
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

var ViewWorldmap = ViewMap.extend({
    init: function() {
        this.initWorldmap();
        this.base();
    },

    /**
     * END OF PUBLIC METHODS
     */

    initWorldmap: function() {
        L.Icon.Default.imagePath = oGeneralProperties.path_base+'/frontend/nagvis-js/images/leaflet';
        g_map = L.map('map', {
            markerZoomAnimation: false,
        }).setView(getViewParam('worldmap_center').split(','), parseInt(getViewParam('worldmap_zoom')));
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(g_map);

        g_map_objects = L.layerGroup().addTo(g_map);

        // The worldmap is showing it's objects depending on the objects
        // which are visible on the screen. This is realized by issueing
        // an ajax call with the current viewport to the core code, which
        // then returns a list of objects to add to the map depending on
        // this viewport.
        g_map.on('moveend', this.handleMoveEnd.bind(this));

        // hide eventual open header dropdown menus when clicking on the map
        g_map.on('mousedown', checkHideMenu);
        g_map.on('mousedown', context_handle_global_mousedown);
    },

    // Is used to update the objects to show on the worldmap
    handleMoveEnd: function(e) {
        this.render(); // re-render the whole map

        // Update the related view properties
        var ll = g_map.getCenter();
        setViewParam('worldmap_center', ll.lat+','+ll.lng);
        setViewParam('worldmap_zoom', g_map.getZoom());
    },

    saveView: function() {
        call_ajax(oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='
                  + this.id + '&type=global&id=0'
                  + '&worldmap_center='+getViewParam('worldmap_center')
                  + '&worldmap_zoom='+getViewParam('worldmap_zoom'));
    },

    // Scale the map to a viewport showing all objects at once
    scaleToAll: function() {
        call_ajax(oGeneralProperties.path_server+'?mod=Map&act=getWorldmapBounds'
                  + '&show=' + this.id, {
            response_handler: this.handleScaleToAll.bind(this),
        });
    },

    handleScaleToAll: function(data) {
        g_map.fitBounds(data);
    },

    // converts an a single or an array of XY coordinates to latlng coordinates
    // based on the current visible viewport
    convertXYToLatLng: function(x, y) {
        if (typeof x !== 'object') {
            x = [x];
            y = [y];
        }

        var latlng, lat = [], lng = [];
        for (var i = 0, l = x.length; i < l; i++) {
            latlng = g_map.containerPointToLatLng(L.point(x[i], y[i]));
            lat[i] = latlng.lat;
            lng[i] = latlng.lng;
        }

        return [lat, lng];
    }
});
