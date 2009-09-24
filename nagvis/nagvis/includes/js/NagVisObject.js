/*****************************************************************************
 *
 * NagVisObject.js - This class handles the visualisation of Nagvis objects
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

var NagVisObject = Base.extend({
	parsedObject: null,
	hover_template_code: null,
	context_template_code: null,
	conf: null,
	contextMenu: null,
	lastUpdate: null,
	firstUpdate: null,
	bIsFlashing: false,
	
	constructor: function(oConf) {
		// Initialize
		this.setLastUpdate();
		
		this.conf = oConf;
		
		// When no object_id given by server: generate own id
		if(this.conf.object_id == null) {
			this.conf.object_id = getRandomLowerCaseLetter() + getRandom(1, 99999);
		}
	},
	
	/**
	 * PUBLIC setLastUpdate
	 *
	 * Sets the time of last status update of this object
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	setLastUpdate: function() {
		this.lastUpdate = iNow;
		
		// Save datetime of the first state update (needed for hover parsing)
		if(this.firstUpdate === null) {
			this.firstUpdate = this.lastUpdate;
		}
	},
  
	/**
	 * PUBLIC getContextMenu()
	 *
	 * Creates a context menu for the object
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	getContextMenu: function (sObjId) {
		// Only enable context menu when configured
		if(this.conf.context_menu && this.conf.context_menu == '1') {
			// Writes template code to "this.context_template_code"
			this.getContextTemplateCode();
			
			// Replace object specific macros
			this.replaceContextTemplateMacros();
			
			var oObj = document.getElementById(sObjId);
			var oContainer = document.getElementById(this.conf.object_id);
			
			// Only create a new div when the context menu does not exist
			var contextMenu = document.getElementById(this.conf.object_id+'-context');
			if(!contextMenu) {
				// Create context menu div
				var contextMenu = document.createElement('div');
				contextMenu.setAttribute('id', this.conf.object_id+'-context');
			}
			
			contextMenu.setAttribute('class', 'context');
			contextMenu.setAttribute('className', 'context');
			contextMenu.style.zIndex = '1000';
			contextMenu.style.display = 'none';
			contextMenu.style.position = 'absolute';
			contextMenu.style.overflow = 'visible';
			
			// Append template code to context menu div
			contextMenu.innerHTML = this.context_template_code;
			
			// Append context menu div to object container
			oContainer.appendChild(contextMenu);
			contextMenu = null;
			
			// Add eventhandlers for context menu
			oObj.onmousedown = contextMouseDown;
			oObj.oncontextmenu = contextShow;
			
			oContainer = null;
			oObj = null;
		}
  },
	
	/**
	 * replaceContextTemplateMacros()
	 *
	 * Replaces object specific macros in the template code
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	replaceContextTemplateMacros: function() {
		var oMacros = {};
		var oSectionMacros = {};
		
		// Break when no template code found
		if(!this.context_template_code || this.context_template_code === '') {
			return false;
		}
		
		oMacros.obj_id = this.conf.object_id;
		oMacros.name = this.conf.name;
		oMacros.address = this.conf.address;
		
		if(this.conf.type === 'service') {
			oMacros.service_description = escapeUrlValues(this.conf.service_description);
		} else {
			oSectionMacros.service = '<!--\\sBEGIN\\sservice\\s-->.+?<!--\\sEND\\sservice\\s-->';
		}
		
		// Macros which are only for hosts
		if(this.conf.type !== 'host') {
			oSectionMacros.host = '<!--\\sBEGIN\\shost\\s-->.+?<!--\\sEND\\shost\\s-->';
		}
		
		// Loop and replace all unwanted section macros
		for (var key in oSectionMacros) {
			var regex = new RegExp(oSectionMacros[key], 'gm');
			this.context_template_code = this.context_template_code.replace(regex, '');
			regex = null;
		}
		oSectionMacros = null;
		
		// Loop and replace all normal macros
		for (var key in oMacros) {
			var regex = new RegExp('\\['+key+'\\]', 'g');
			this.context_template_code = this.context_template_code.replace(regex, oMacros[key]);
			regex = null;
		}
		
		oMacros = null;
	},
	
	/**
	 * getContextTemplateCode()
	 *
	 * Get the context template from the global object which holds all templates of 
	 * the map
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	getContextTemplateCode: function() {
		this.context_template_code = oContextTemplates[this.conf.context_template];
	},
	
	/**
	 * PUBLIC getHoverMenu
	 *
	 * Creates a hover box for objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	getHoverMenu: function (oObj) {
		// Only enable hover menu when configured
		if(this.conf.hover_menu && this.conf.hover_menu == '1') {
			var sTemplateCode;
			var iHoverDelay = this.conf.hover_delay;
			
			// Parse the configured URL or get the hover menu
			if(this.conf.hover_url && this.conf.hover_url !== '') {
				this.getHoverUrlCode();
				
				sTemplateCode = this.hover_template_code;
			} else {
				// Only fetch hover template code and parse static macros when this is
				// no update
				if(this.hover_template_code === null) {
					this.getHoverTemplateCode();
				}
				
				// Replace dynamic (state dependent) macros
				sTemplateCode = replaceHoverTemplateDynamicMacros('0', this, this.hover_template_code);
			}
			
			// Add the hover menu functionality to the object
			oObj.onmouseover = function() { var sT = sTemplateCode; var iH = iHoverDelay; displayHoverMenu(sT, iH); sT = null; iH = null; };
			oObj.onmouseout = function() { hideHoverMenu(); };
		}
		
		oObj = null;
	},
	
	/**
	 * getHoverUrlCode()
	 *
	 * Get the hover code from the hover url
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	getHoverUrlCode: function() {
		this.hover_template_code = oHoverUrls[this.conf.hover_url];
		
		if(this.hover_template_code === null) {
			this.hover_template_code = '';
		}
	},
	
	/**
	 * getHoverTemplateCode()
	 *
	 * Get the hover template from the global object which holds all templates of 
	 * the map
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	getHoverTemplateCode: function() {
		// Asign the template code and replace only the static macros
		// These are typicaly configured static configued values from nagios
		this.hover_template_code = replaceHoverTemplateStaticMacros('0', this, oHoverTemplates[this.conf.hover_template]);
	}
});
