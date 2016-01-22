/*****************************************************************************
 *
 * NagVisContainer.js - This class handles the visualisation of textbox objects
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

var NagVisContainer = NagVisStatelessObject.extend({
    update: function() {
        new ElementBox(this).addTo(this);
        this.base();
    },

    render: function () {
        this.base();

        var span = this.elements[0].dom_obj.childNodes[0];
        span.style.display = 'block';
        span.style.height = '100%';

        if (this.conf.view_type === 'inline') {
            // Request data via ajax call and add it directly to the current page
            call_ajax(this.conf.url, {
                response_handler: function(html, span) {
                    span.innerHTML = html;
                },
                error_handler: function(status_code, response, span) {
                    if (status_code === 200)
                        span.innerHTML = 'Error: '+response;
                    else
                        span.innerHTML = 'Error: '+status_code;
                },
                handler_data : span,
                decode_json  : false,
                add_ajax_id  : false,
            });
        } else {
            // Create an iframe element which holds the requested url
            span.innerHTML = '';
            var oIframe = document.createElement('iframe');
            oIframe.style.borderWidth = 0;
            oIframe.style.width  = '100%';
            oIframe.style.height = '100%';
            oIframe.src = this.conf.url;
            span.appendChild(oIframe);
            oIframe = null;
        }
    },

    //
    // END OF PUBLIC METHODS
    //

    getText: function() {
        return '';
    }
});
