/*****************************************************************************
 *
 * NagVisService.js - This class handles the visualisation of service objects
 *
 * Copyright (c) 2004-2008 NagVis Project
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

var NagVisService = NagVisStatefulObject.extend({
	constructor: function(oConf) {
		// Call parent constructor
		this.base(oConf);
	},
	
	/**
	 * PUBLIC parseGadget()
	 *
	 * Parses the HTML-Code of a gadget
	 *
	 * @return	String		gadget object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseGadget: function () {
		var alt = this.conf.name+'-'+this.conf.service_description;
		
		// Add object informations and current perfdata to gadget_url
		var iParam = this.conf.gadget_url.indexOf('?');
		var sParams = '';
		if (iParam == -1) {
			// Append with leading "?" to mark parameter beginning
			sParams = '?';
		} else {
			// Append with leading "&" to continue parameters
			sParams = '&';
		}
		
		// Process the optional gadget_opts param
		var sGadgetOpts = '';
		if(this.conf.gadget_opts && this.conf.gadget_opts != '') {
			sGadgetOpts = '&opts=' + escapeUrlValues(this.conf.gadget_opts.toString());
		}
		
		sParams = sParams + 'name1=' + this.conf.name + '&name2=' + escapeUrlValues(this.conf.service_description) + '&scale=' + escapeUrlValues(this.conf.gadget_scale.toString()) + '&state=' + this.conf.state + '&stateType=' + this.conf.state_type + '&perfdata=' + this.conf.perfdata.replace(/\&quot\;|\&\#145\;/g,'%22') + sGadgetOpts;
		
		var oIcon = document.createElement('img');
		oIcon.setAttribute('id', this.conf.object_id+'-icon');
		oIcon.src = this.conf.gadget_url + sParams;
		oIcon.alt = this.conf.type+'-'+alt;
		
		var oIconDiv = document.createElement('div');
		oIconDiv.setAttribute('id', this.conf.object_id+'-icondiv');
		oIconDiv.setAttribute('class', 'icon');
		oIconDiv.setAttribute('className', 'icon');
		oIconDiv.style.position = 'absolute';
		oIconDiv.style.top = this.conf.y+'px';
		oIconDiv.style.left = this.conf.x+'px';
		oIconDiv.style.zIndex = this.conf.z;
		
		// Parse link only when set
		if(this.conf.url && this.conf.url !== '') {
			var oIconLink = document.createElement('a');
			oIconLink.href = this.conf.url;
			oIconLink.target = this.conf.url_target;
			oIconLink.appendChild(oIcon);
			oIcon = null;
			
			oIconDiv.appendChild(oIconLink);
			oIconLink = null;
		} else {
			oIconDiv.appendChild(oIcon);
			oIcon = null;
		}
		
		return oIconDiv;
	}
});
