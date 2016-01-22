/*****************************************************************************
 *
 * NagVisLine.js - This class handles the visualisation of stateless line
 *                 objects in the NagVis js frontend
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

var NagVisLine = NagVisStatelessObject.extend({
    constructor: function(oConf) {
        // Call parent constructor;
        this.base(oConf);
    },

    update: function() {
        var line = new ElementLine(this).addTo(this);

        // Apply line color configurations
        line.calcColors = function(obj) {
            return function() {
                return [obj.conf.line_color, obj.conf.line_color_border];
            };
        }(this);

        this.base();
    },
});
