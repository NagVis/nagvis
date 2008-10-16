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

function NagVisObject (oConf) {
	this.parsedObject;
	this.hover_template_code;
	this.conf;
	
	this.construct = function(oConf) {
		// Initialize
		this.setLastUpdate();
		this.objId = getRandomLowerCaseLetter() + getRandom(1, 99999);
		this.conf = oConf;
	}
	
	/**
	 * PUBLIC setLastUpdate
	 *
	 * Sets the time of last status update of this object
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	this.setLastUpdate = function() {
		this.lastUpdate = Date.parse(new Date());
	}
	
	/**
	 * PUBLIC getHoverMenu
	 *
	 * Creates a hover box for objects
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	this.getHoverMenu = function (oObj) {
		// Only enable hover menu when configured
		if(this.conf.hover_menu && this.conf.hover_menu == '1') {
			// Parse the configured URL or get the hover menu
			if(this.conf.hover_url && this.conf.hover_url != '') {
				this.getHoverUrlCode();
			} else {
				this.getHoverTemplateCode();
			}
			
			// Reseting parsedObject. This makes problems in IE when converting to json with JSON.stringify
			// Maybe it results in other problems when removing parsedObject so clone this before
			var oObjA = cloneObject(this);
			
			// Add the hover menu functionality to the object
			oObj.onmouseover = new Function('displayHoverMenu(replaceHoverTemplateMacros(\'0\', '+ JSON.stringify(oObjA)+', \''+this.hover_template_code+'\'), '+this.conf.hover_delay+');');
			oObj.onmouseout = new Function('hideHoverMenu();');
		}
	}
	
	/**
	 * getHoverUrlCode()
	 *
	 * Get the hover code from the hover url
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	this.getHoverUrlCode = function() {
		// FIXME: Move to bulk fetching like normal hover menus
		this.hover_template_code = oHoverUrls[this.conf.hover_url];
	}
	
	/**
	 * getHoverTemplateCode()
	 *
	 * Get the hover template from the global object which holds all templates of 
	 * the map
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	this.getHoverTemplateCode = function() {
		this.hover_template_code = oHoverTemplates[this.conf.hover_template];
	}
	
	this.replaceHoverTemplateMacros = function (replaceChild, oObj, sTemplateCode) {
		this.hover_template_code = replaceHoverTemplateMacros(replaceChild, oObj, sTemplateCode);
	}
	
	// Call the constructor of the object
	this.construct(oConf);
}
