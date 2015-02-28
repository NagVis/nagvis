/*****************************************************************************
 *
 * NagVisRotation.js - This class handles the visualisation of the rotations
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

var NagVisRotation = NagVisStatelessObject.extend({
    constructor: function(oConf) {
        this.base(oConf);
    },

    parseOverview: function() {
        var container = document.getElementById('overviewRotations');

        var oTable = document.createElement('table');
        oTable.className = 'rotation';
        container.appendChild(oTable);
        var oTbody = document.createElement('tbody');
        oTable.appendChild(oTbody);

        /* Rotation title */
        var oTr = document.createElement('tr');
        var oTd = document.createElement('td');
        oTd.className = 'title';
        oTd.setAttribute('rowSpan', this.conf.num_steps);
        oTd.rowSpan = this.conf.num_steps;

        // Link
        var oLink = document.createElement('a');
        oLink.href = this.conf.url;

        var h3 = document.createElement('h3');
        h3.appendChild(document.createTextNode(this.conf.name));

        oLink.appendChild(h3);
        h3 = null;

        oTd.appendChild(oLink);

        oTr.appendChild(oTd);
        oTd = null;

        /* Rotation steps */
        for(var i = 0, len = this.conf.steps.length; i < len; i++) {
            if(i !== 0)
                oTr = document.createElement('tr');

            oTd = document.createElement('td');

            oLink = document.createElement('a');
            oLink.href = this.conf.steps[i].url;
            oLink.appendChild(document.createTextNode(this.conf.steps[i].name));

            oTd.appendChild(oLink);
            oLink = null;

            oTr.appendChild(oTd);
            oTd = null;

            oTbody.appendChild(oTr);
            oTr = null;
        }

        container = null;
    }
});
