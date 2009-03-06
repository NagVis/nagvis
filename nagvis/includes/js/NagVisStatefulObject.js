/*****************************************************************************
 *
 * NagVisObject.js - This class handles the visualisation of statefull objects
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

var NagVisStatefulObject = NagVisObject.extend({
	// Stores the information from last refresh (Needed for change detection)
	last_conf: {},
	
	constructor: function(oConf) {
		// Call parent constructor
		this.base(oConf);
	},
	
	getMembers: function() {
		this.members = [];
		if(this.conf && this.conf.members && this.conf.members.length > 0) {
			for(var i = 0, len = this.conf.members.length; i < len; i++) {
				var oMember = this.conf.members[i];
				var oObj;
				
				switch (oMember.type) {
					case 'host':
						oObj = new NagVisHost(oMember);
					break;
					case 'service':
						oObj = new NagVisService(oMember);
					break;
					case 'hostgroup':
						oObj = new NagVisHostgroup(oMember);
					break;
					case 'servicegroup':
						oObj = new NagVisServicegroup(oMember);
					break;
					case 'map':
						oObj = new NagVisMap(oMember);
					break;
					case 'textbox':
						oObj = new NagVisTextbox(oMember);
					break;
					case 'shape':
						oObj = new NagVisShape(oMember);
					break;
					default:
						alert('Error: Unknown member object type ('+oMember.type+')');
					break;
				}
				
				if(oObj != null) {
					this.members.push(oObj);
				}
			}
			
			oObj = null;
		}
	},
	
	/**
	 * PUBLIC saveLastState()
	 *
	 * Saves the current state in last state array for later change detection
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	saveLastState: function() {
		// FIXME: Do not copy the whole conf array
		for (i in this.conf) {
			this.last_conf[i] = this.conf[i];
		}
	},
	
	/**
	 * PUBLIC stateChanged()
	 *
	 * Check if a state change occured since last refresh
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	stateChanged: function() {
		if(this.conf.summary_state != this.last_conf.summary_state || 
		   this.conf.summary_problem_has_been_acknowledged != this.last_conf.summary_problem_has_been_acknowledged || 
		   this.conf.summary_in_downtime != this.last_conf.summary_in_downtime) {
			return true;
		} else {
			return false;
		}
	},
	
	/**
	 * PUBLIC parse()
	 *
	 * Parses the object
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parse: function () {
		var oContainerDiv;
		
		this.replaceMacros();
		
		// Create container div
		oContainerDiv = document.createElement('div');
		oContainerDiv.setAttribute('id', this.objId);
		
		// Parse object depending on line or normal icon
		switch(this.conf.view_type) {
			case 'line':
				var oLine = this.parseLine();
				oContainerDiv.appendChild(oLine);
				oLine = null;
			break;
			case 'gadget':
				var oGadget = this.parseGadget();
				oContainerDiv.appendChild(oGadget);
				oGadget = null;
			break;
			default:
				var oIcon = this.parseIcon();
				oContainerDiv.appendChild(oIcon);
				oIcon = null;
		}
		
		// Parse label when configured
		if(this.conf.label_show && this.conf.label_show == '1') {
			var oLabel = this.parseLabel();
			oContainerDiv.appendChild(oLabel);
			oLabel = null;
		}
		
		// When this is an update, remove the object first
		this.remove();
    
    // Append child to map and save reference in parsedObject
		var oMap = document.getElementById('map');
		this.parsedObject = oMap.appendChild(oContainerDiv);
		oContainerDiv = null;
		oMap = null;
		
		if(this.conf.view_type && this.conf.view_type == 'line') {
			this.drawLine();
		}
	},
	
	remove: function () {
		var oMap = document.getElementById('map');
		if(this.parsedObject) {
			// Remove event listeners
			var oObj;
			if(this.conf.view_type && this.conf.view_type === 'line') {
				oObj = document.getElementById(this.objId+'-linediv');
			} else {
				oObj = document.getElementById(this.objId+'-icon');
			}
			if(oObj) {
				oObj.onmousedown = null;
				oObj.oncontextmenu = null;
				oObj.onmouseover = null;
				oObj.onmouseout = null;
				oObj = null;
			}
			
			// Remove object from DOM
			oMap.removeChild(this.parsedObject);
			
			// Remove object reference
			this.parsedObject = null;
		}
		oMap = null;
	},
	
	/**
	 * PUBLIC parseHoverMenu()
	 *
	 * Parses the hover menu. Don't add this functionality to the normal icon
	 * parsing
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseHoverMenu: function () {
		var oObj;
		
		// Get the object to apply the hover menu to
		if(this.conf.view_type && this.conf.view_type === 'line') {
			oObj = document.getElementById(this.objId+'-linelinkdiv');
		} else {
			oObj = document.getElementById(this.objId+'-icon');
		}
		
		// Create hover menu
		this.getHoverMenu(oObj);
		
		oObj = null;
	},
	
	/**
	 * PUBLIC parseContextMenu()
	 *
	 * Parses the context menu. Don't add this functionality to the normal icon
	 * parsing
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseContextMenu: function () {
    // Add a context menu to the object when enabled
    if(this.conf.context_menu && this.conf.context_menu == '1') {
      if(this.conf.view_type && this.conf.view_type == 'line') {
        this.getContextMenu(this.objId, this.objId+'-link');
      } else {
        this.getContextMenu(this.objId, this.objId+'-icon');
			}
    }
	},
	
	/**
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	replaceMacros: function () {
		var name = '';
		if(this.conf.type == 'service') {
			name = 'host_name';
		} else {
			name = this.conf.type + '_name';
		}
		
		if(this.conf.url && this.conf.url != '') {
			this.conf.url = this.conf.url.replace(new RegExp('\\\[htmlcgi\\\]', 'g'), oGeneralProperties.path_htmlcgi);
			this.conf.url = this.conf.url.replace(new RegExp('\\\[htmlbase\\\]', 'g'), oGeneralProperties.path_htmlbase);
			
			this.conf.url = this.conf.url.replace(new RegExp('\\\['+name+'\\\]', 'g'), this.conf.name);
			if(this.conf.type == 'service') {
				this.conf.url = this.conf.url.replace(new RegExp('\\\[service_description\\\]', 'g'), this.conf.service_description);
			}
			
			// Replace special chars in url
			this.conf.url = this.conf.url.replace(new RegExp('#', 'g'), '%23');
		}
		
		if(this.conf.hover_url && this.conf.hover_url != '') {
			this.conf.hover_url = this.conf.hover_url.replace(new RegExp('\\\['+name+'\\\]', 'g'), this.conf.name);
			if(this.conf.type == 'service') {
				this.conf.hover_url = this.conf.hover_url.replace(new RegExp('\\\[service_description\\\]', 'g'), this.conf.service_description);
			}
		}
		
		if(this.conf.label_text && this.conf.label_text != '') {
			var objName;
			// For maps use the alias as display string
			if(this.conf.type == 'map') {
				objName = this.conf.alias;   
			} else {
				objName = this.conf.name;
			}
			
			this.conf.label_text = this.conf.label_text.replace(new RegExp('\\\[name\\\]', 'g'), objName);
			this.conf.label_text = this.conf.label_text.replace(new RegExp('\\\[output\\\]', 'g'), this.conf.output);
			
			if(this.conf.type == 'service' || this.conf.type == 'host') {
				this.conf.label_text = this.conf.label_text.replace(new RegExp('\\\[perfdata\\\]', 'g'), this.conf.perfdata);
			}
			
			if(this.conf.type == 'service') {
				this.conf.label_text = this.conf.label_text.replace(new RegExp('\\\[service_description\\\]', 'g'), this.conf.service_description);
			}
		}
	},
	
	/**
	 * Parses the HTML-Code of a line
	 *
	 * @return	String		HTML code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseLine: function () {
		var ret = '';
		var link = '';
		
		// Create container div
		var oContainerDiv = document.createElement('div');
		oContainerDiv.setAttribute('id', this.objId+'-linediv');
		
		// Create line div
		var oLineDiv = document.createElement('div');
		oLineDiv.setAttribute('id', this.objId+'-line');
		oLineDiv.style.zIndex = this.conf.z;
		
		oContainerDiv.appendChild(oLineDiv);
		oLineDiv = null;
		
		// Parse hover/link area only when needed
		if((this.conf.url && this.conf.url !== '') || (this.conf.hover_menu && this.conf.hover_menu !== '')) {
			var oLinkDiv = document.createElement('div');
			
			oLinkDiv.setAttribute('id', this.objId+'-linelinkdiv');
			oLinkDiv.style.zIndex = (this.conf.z+1);
			var sUrl = this.conf.url;
			var sUrlTarget = this.conf.url_target;
			oLinkDiv.onclick = function() { window.open(sUrl, sUrlTarget, ""); };
			
			oContainerDiv.appendChild(oLinkDiv);
			oLinkDiv = null;
		}
		
		return oContainerDiv;
	},
	
	/**
	 * Draws the NagVis lines on the already added divs.
	 *
	 * @return	String		HTML code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	drawLine: function() {
		var x = this.conf.x.split(',');
		var y = this.conf.y.split(',');
		
		var width = this.conf.line_width;
		
		// Parse the line object
		drawNagVisLine(this.objId, this.conf.line_type, x[0], y[0], x[1], y[1], width, this.conf.summary_state, this.conf.summary_problem_has_been_acknowledged, this.conf.summary_in_downtime, ((this.conf.url && this.conf.url !== '') || (this.conf.hover_menu && this.conf.hover_menu !== '')));
	},
	
	/**
	 * PUBLIC parseIcon()
	 *
	 * Parses the HTML-Code of an icon
	 *
	 * @return	String		String with Html Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseIcon: function () {
		var alt = '';
		
		if(this.type == 'service') {
			alt = this.conf.name+'-'+this.conf.service_description;
		} else {
			alt = this.conf.name;
		}
		
		var oIcon = document.createElement('img');
		oIcon.setAttribute('id', this.objId+'-icon');
		oIcon.src = this.conf.iconHtmlPath+this.conf.icon;
		oIcon.alt = this.conf.type+'-'+alt;
		
		var oIconDiv = document.createElement('div');
		oIconDiv.setAttribute('id', this.objId+'-icondiv');
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
	},
	
	/**
	 * Parses the HTML-Code of a label
	 *
	 * @return	String		HTML code of the label
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseLabel: function () {
		var oLabelDiv;
		
		// If there is a presign it should be relative to the objects x/y
		if(this.conf.label_x.toString().match(/^(?:\+|\-)/)) {
			this.conf.label_x = parseFloat(this.conf.x) + parseFloat(this.conf.label_x);
		}
		if(this.conf.label_y.toString().match(/^(?:\+|\-)/)) {
			this.conf.label_y = parseFloat(this.conf.y) + parseFloat(this.conf.label_y);
		}
		
		// If no x/y coords set, fallback to object x/y
		if(!this.conf.label_x || this.conf.label_x == '' || this.conf.label_x == 0) {
			this.conf.label_x = this.conf.x;
		}
		if(!this.conf.label_y || this.conf.label_y == '' || this.conf.label_y == 0) {
			this.conf.label_y = this.conf.y;
		}
		
		if(this.conf.label_width && this.conf.label_width != 'auto') {
			this.conf.label_width += 'px';	
		}
		
		oLabelDiv = document.createElement('div');
		oLabelDiv.setAttribute('id', this.objId+'-label');
		oLabelDiv.setAttribute('class', 'object_label');
		oLabelDiv.setAttribute('className', 'object_label');
		oLabelDiv.style.background=this.conf.label_background;
		oLabelDiv.style.borderColor=this.conf.label_border;
		
		oLabelDiv.style.position = 'absolute';
		oLabelDiv.style.left = this.conf.label_x+'px';
		oLabelDiv.style.top = this.conf.label_y+'px';
		oLabelDiv.style.width = this.conf.label_width;
		oLabelDiv.style.zIndex = this.conf.z+1;
		oLabelDiv.style.overflow= 'visible';
		
		/**
		 * IE workaround: The transparent for the color is not enough. The border
		 * has really to be hidden.
		 */
		if(this.conf.label_border == 'transparent') {
			oLabelDiv.style.borderStyle = 'none';
		} else {
			oLabelDiv.style.borderStyle = 'solid';
		}
		
		// Create span for text and add label text
		var oLabelSpan = document.createElement('span');
		oLabelSpan.innerHTML = this.conf.label_text;
		oLabelDiv.appendChild(oLabelSpan);
		oLabelSpan = null;
		
		return oLabelDiv;
	}
});
