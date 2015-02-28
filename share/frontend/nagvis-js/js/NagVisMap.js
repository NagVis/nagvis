/*****************************************************************************
 *
 * NagVisMap.js - This class handles the visualisation of map objects
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

var NagVisMap = NagVisStatefulObject.extend({
    constructor: function(oConf) {
        // Call parent constructor
        this.base(oConf);

        this.getMembers();
    },

    stateText: function () {
        var substate = '';
        if (this.conf.summary_in_downtime == 1)
            substate = ' (downtime)';
        else if (this.conf.summary_problem_has_been_acknowledged == 1)
            substate = ' (ack)';
        else if (this.conf.summary_stale)
            substate = ' (stale)';
        return this.conf.summary_state + substate;
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

        var div = document.createElement('div');
        div.setAttribute('id', this.conf.object_id);
        div.className = 'mapobj '+this.conf.overview_class;

        // needed for IE. It seems as the IE does not really know what to do
        // with the link definied below ... strange one
        var url = this.conf.overview_url;
        div.onclick = function() {
            location.href = url;
            return false;
        };

        // Only show map thumb when configured
        if(oPageProperties.showmapthumbs == 1)
            div.style.height = '200px';

        // Link
        var oLink = document.createElement('a');
        oLink.setAttribute('id', this.conf.object_id+'-icon');
        oLink.style.display = "block";
        oLink.style.width = "100%";
        oLink.style.height = "100%";
        oLink.href = this.conf.overview_url;

        // Status image
        if(this.conf.icon !== null && this.conf.icon !== '') {
            var oImg   = document.createElement('img');
            oImg.className = 'state';
            oImg.align = 'right';
            oImg.src   = oGeneralProperties.path_iconsets + this.conf.icon;
            oImg.alt   = this.stateText();

            oLink.appendChild(oImg);
            oImg = null;
        }

        // Title
        var h3 = document.createElement('h3');
        h3.appendChild(document.createTextNode(this.conf.alias));
        oLink.appendChild(h3);
        h3 = null;

        // Only show map thumb when configured
        if(oPageProperties.showmapthumbs == 1 && this.conf.overview_image != '') {
            oImg = document.createElement('img');
            oImg.style.width = '200px';
            oImg.style.height = '150px';
            oImg.src = this.conf.overview_image;
            oLink.appendChild(oImg);
            oImg = null;
        }

        div.appendChild(oLink);
        oLink = null;

        return div;
    }
});
