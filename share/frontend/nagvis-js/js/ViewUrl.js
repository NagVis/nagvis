/*****************************************************************************
 *
 * ViewUrl.js - All NagVis url related top level code
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

var ViewUrl = View.extend({
    type: 'url',
    constructor: function(id) {
        this.base(id);
    },

    init: function() {
        this.render();
        hideStatusMessage();
    },

    update: function() {
        // Fetches the contents from the server and prints it to the page
        this.render();
    },

    /**
     * END OF PUBLIC METHODS
     */

    // Fetches the contents of the given url and prints it on the current page
    render: function() {
        var url = oPageProperties.url;
        this.dom_obj = document.getElementById('url');
        if (this.dom_obj.tagName == 'DIV') {
            // Fetch contents from server
            call_ajax(oGeneralProperties.path_server + '?mod=Url&act=getContents&show='
                      + escapeUrlValues(url), {
                response_handler: function(response) {
                    this.dom_obj.innerHTML = response.content;
                }.bind(this),
            });
        }
        else {
            // iframe
            this.dom_obj.src = url;
        }

        this.base();
    }
});
