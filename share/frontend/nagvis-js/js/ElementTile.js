/*****************************************************************************
 *
 * ElementTile.js - This class handles the visualisation of maps on the overview page
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

var ElementTile = Element.extend({
    render: function() {
        this.renderTile();
    },

    //
    // END OF PUBLIC METHODS
    //

    // Renders the DOM nodes of the object to show it on the overview page
    renderTile: function () {
        var alt = '';
        if(this.type == 'service')
            alt = this.obj.conf.name+'-'+this.obj.conf.service_description;
        else
            alt = this.obj.conf.name;

        var div = document.createElement('div');
        this.dom_obj = div;
        this.obj.trigger_obj = div;
        div.setAttribute('id', this.obj.conf.object_id);
        div.className = 'mapobj '+this.obj.conf.overview_class;

        // needed for IE. It seems as the IE does not really know what to do
        // with the link definied below ... strange one
        var url = this.obj.conf.overview_url;
        div.onclick = function() {
            location.href = url;
            return false;
        };

        // Only show map thumb when configured
        if(oPageProperties.showmapthumbs == 1)
            div.style.height = '200px';

        // Link
        var oLink = document.createElement('a');
        oLink.setAttribute('id', this.obj.conf.object_id+'-icon');
        oLink.style.display = "block";
        oLink.style.width = "100%";
        oLink.style.height = "100%";
        oLink.href = this.obj.conf.overview_url;

        // Status image
        if (this.obj.conf.icon !== null && this.obj.conf.icon !== '') {
            var oImg   = document.createElement('img');
            oImg.className = 'state';
            oImg.align = 'right';
            oImg.src   = oGeneralProperties.path_iconsets + this.obj.conf.icon;
            oImg.style.width = this.obj.conf.icon_size + 'px';
            oImg.style.height = this.obj.conf.icon_size + 'px';
            oImg.alt   = this.obj.stateText();

            oLink.appendChild(oImg);
        }

        // Title
        var h3 = document.createElement('h3');
        h3.appendChild(document.createTextNode(this.obj.conf.alias));
        oLink.appendChild(h3);

        // Only show map thumb when configured
        if(oPageProperties.showmapthumbs == 1 && this.obj.conf.overview_image != '') {
            oImg = document.createElement('img');
            oImg.style.width = '200px';
            oImg.style.height = '150px';
            oImg.src = this.obj.conf.overview_image;
            oLink.appendChild(oImg);
        }

        div.appendChild(oLink);
    }
});
