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

function NagVisObject () {
	this.parsedObject;
	this.hover_template_code;
	
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
		if(this.conf.hover_menu) {
			// Parse the configured URL or get the hover menu
			if(this.conf.hover_url && this.conf.hover_url != '') {
				//FIXME: Just noticed that cronn domain ajax requests are not possible. TODO: Revert to fetching url contents via PHP.
				this.hover_template_code = getHttpRequest(this.conf.hover_url, true);
				this.hover_template_code = this.hover_template_code.replace(/\r\n/g,'').replace(/\n/g,'').replace(/\t/g,'').replace(/\'/g,'\\\'').replace(/\"/g,'\\\'');
			} else {
				this.getHoverTemplateCode();
				
				// Don't replace childs here, replace it on mouseover
				//this.replaceHoverTemplateMacros('0', this, this.hover_template_code);
			}
			
			// Add the hover menu functionality to the object
			oObj.onmouseover = new Function('displayHoverMenu(replaceHoverTemplateMacros(\'0\', '+ JSON.stringify(this)+', \''+this.hover_template_code+'\'), '+this.conf.hover_delay+');');
			oObj.onmouseout = new Function('hideHoverMenu();');
		}
	}
	
	this.getHoverTemplateCode = function() {
		// Get the hover template
		this.hover_template_code = getHoverTemplate(this.conf.hover_template);
	}
	
	this.replaceHoverTemplateMacros = function (replaceChild, oObj, sTemplateCode) {
		this.hover_template_code = replaceHoverTemplateMacros(replaceChild, oObj, sTemplateCode);
	}
}
