/*****************************************************************************
 *
 * NagVisRotation.js - This class handles the visualisation of the rotations
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

var NagVisRotation = NagVisStatelessObject.extend({
    constructor: function(oConf) {
        this.base(oConf);
    },

    parseOverview: function() {
        var oTbody = document.getElementById('overviewRotations');

        /* Rotation title */

        var oTr = document.createElement('tr');
        var oTd = document.createElement('td');
        oTd.setAttribute('rowSpan', this.conf.num_steps);
        oTd.rowSpan = this.conf.num_steps;

        var url = this.conf.url;

        oTd.onclick = function() {
            location.href = url;
        };

        oTd.onmouseover = function() {
            this.style.cursor = 'pointer';
            this.style.backgroundColor = '#ffffff';
        };

        oTd.onmouseout = function() {
            this.style.cursor = 'auto';
            this.style.backgroundColor = '';
        };

        var oH2 = document.createElement('h2');
        oH2.appendChild(document.createTextNode(this.conf.name));

        oTd.appendChild(oH2);
        oH2 = null;

        oTr.appendChild(oTd);
        oTd = null;

        /* Rotation steps */

        for(var i = 0, len = this.conf.steps.length; i < len; i++) {
            if(i !== 0) {
                oTr = document.createElement('tr');
            }

            oTd = document.createElement('td');
            oTd.width = '250px';

            var sUrl = this.conf.steps[i].url;

            oTd.onclick = function() {
                location.href = sUrl;
            };

            oTd.onmouseover = function() {
                this.style.cursor = 'pointer';
                this.style.backgroundColor = '#ffffff';
            };

            oTd.onmouseout = function() {
                this.style.cursor = 'auto';
                this.style.backgroundColor = '';
            };

            oTd.appendChild(document.createTextNode(this.conf.steps[i].name));

            oTr.appendChild(oTd);
            oTd = null;

            oTbody.appendChild(oTr);
            oTr = null;
        }

        oTbody = null;
    }
});