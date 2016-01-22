/*****************************************************************************
 *
 * NagVisTextbox.js - This class handles the visualisation of textbox objects
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

var NagVisTextbox = NagVisStatelessObject.extend({
    update: function() {
        new ElementBox(this).addTo(this);
        this.base();
    },

    replaceMacros: function (text) {
        text = text.replace('[refresh_counter]', '<font id="refreshCounter"></font>');
        text = text.replace('[worker_last_run]', '<font id="workerLastRunCounter"></font>');
        return text;
    },

    getText: function() {
        return this.replaceMacros(this.conf.text);
    }
});
