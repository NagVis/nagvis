/*****************************************************************************
 *
 * NagVisMap.js - This class handles the visualisation of map objects
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

var NagVisMap = NagVisStatefulObject.extend({
    update: function () {
        if (g_view.type == 'overview')
            new ElementTile(this).addTo(this);

        this.base();
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
});
