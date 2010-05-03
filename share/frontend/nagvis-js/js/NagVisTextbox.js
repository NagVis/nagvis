/*****************************************************************************
 *
 * NagVisTextbox.js - This class handles the visualisation of textbox objects
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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

var NagVisTextbox = NagVisStatelessObject.extend({
	// Initialize
	constructor: function(oConf) {
		// Call parent constructor
		this.base(oConf);
	},
	
	/**
	 * PUBLIC parse()
	 *
	 * Parses the object
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parse: function() {
		var oContainerDiv;
		
		this.replaceMacros();
		
		// Create container div
		oContainerDiv = document.createElement('div');
		oContainerDiv.setAttribute('id', this.conf.object_id);
		
		// Parse object depending on line or normal icon
		var oTextbox = this.parseTextbox();
		oContainerDiv.appendChild(oTextbox);
		oTextbox = null;
		
		// When this is an update, remove the object first
		this.remove();
		
		var oMap = document.getElementById('map');
		this.parsedObject = oMap.appendChild(oContainerDiv);
		
		oContainerDiv = null;
		oMap = null;
	},
	
	/**
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	replaceMacros: function () {
		this.conf.text = this.conf.text.replace('[refresh_counter]', '<font id="refreshCounter"></font>');
		this.conf.text = this.conf.text.replace('[worker_last_run]', '<font id="workerLastRunCounter"></font>');
	},
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @return	String	String with HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseTextbox: function () {
		return drawNagVisTextbox(this.conf.object_id+'label', 'box', this.conf.background_color, this.conf.border_color, this.conf.x, this.conf.y, this.conf.z, this.conf.w, this.conf.h, this.conf.text, this.conf.style);
	}
});
