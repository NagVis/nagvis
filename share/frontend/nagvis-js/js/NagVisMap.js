/*****************************************************************************
 *
 * NagVisMap.js - This class handles the visualisation of map objects
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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

var NagVisMap = NagVisStatefulObject.extend({
    constructor: function(oConf) {
        // Call parent constructor
        this.base(oConf);

        this.getMembers();
    },

    /**
     * Parses the object of a map on the overview page
     *
     * @return	String		HTML code of the label
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    parseOverview: function () {
        var alt = '';

        if(this.type == 'service')
            alt = this.conf.name+'-'+this.conf.service_description;
        else
            alt = this.conf.name;

        this.replaceMacros();

        var oTd = document.createElement('td');
        oTd.setAttribute('id', this.conf.object_id);
        oTd.setAttribute('class', this.conf.overview_class);
        oTd.setAttribute('className', this.conf.overview_class);
        oTd.style.width = '200px';
        oTd.style.margin = '0';

        // needed for IE. It seems as the IE does not really know what to do
        // with the link definied below ... strange one
        var url = this.conf.overview_url;
        oTd.onclick = function() {
            location.href = url;
        };

        // Only show map thumb when configured
        if(oPageProperties.showmapthumbs == 1)
            oTd.style.height = '200px';

        // Link
        var oLink = document.createElement('a');
        oLink.setAttribute('id', this.conf.object_id+'-icon');
        oLink.style.zIndex = 1000;
        oLink.style.display = "block";
        oLink.style.width = "100%";
        oLink.style.height = "100%";
        oLink.href = this.conf.overview_url;

        // Status image
        if(this.conf.icon !== null && this.conf.icon !== '') {
            var oImg   = document.createElement('img');
            oImg.align = 'right';
            oImg.src   = oGeneralProperties.path_iconsets + this.conf.icon;
            oImg.alt   = this.conf.type + '-' + alt;

            oLink.appendChild(oImg);
            oImg = null;
        }

        // Title
        var h2 = document.createElement('h2');
        h2.appendChild(document.createTextNode(this.conf.alias));
        oLink.appendChild(h2);
        h2 = null;

        var br = document.createElement('br');
        oLink.appendChild(br);
        br = null;

        // Only show map thumb when configured
        if(oPageProperties.showmapthumbs == 1 && this.conf.overview_image != '') {
            oImg = document.createElement('img');
            oImg.style.width = '200px';
            oImg.style.height = '150px';
            oImg.src = this.conf.overview_image;
            oLink.appendChild(oImg);
            oImg = null;
        }

        oTd.appendChild(oLink);
        oLink = null;

        return oTd;
    }
});