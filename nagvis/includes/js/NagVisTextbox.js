/*****************************************************************************
 *
 * NagVisTextbox.js - This class handles the visualisation of textbox objects
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

NagVisTextbox.Inherits(NagVisStatelessObject);
function NagVisTextbox (oConf) {
	// Call parent constructor
	this.Inherits(NagVisStatelessObject, oConf);
	
	/**
	 * PUBLIC parse()
	 *
	 * Parses the object
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	this.parse = function () {
		var oContainerDiv;
		
		this.replaceMacros();
		
		// Create container div
		oContainerDiv = document.createElement('div');
		oContainerDiv.setAttribute('id', this.objId);
		
		// Parse object depending on line or normal icon
		oContainerDiv.appendChild(this.parseTextbox());
		
		// When this is an update, remove the object first
		var oMap = document.getElementById('map');
		if(this.parsedObject) {
			oMap.removeChild(this.parsedObject);
		}
		
		this.parsedObject = oMap.appendChild(oContainerDiv);
	}
	
	/**
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	this.replaceMacros = function () {
		this.conf.text = this.conf.text.replace('[refresh_counter]', '<font id="refreshCounter"></font>');
		this.conf.text = this.conf.text.replace('[worker_last_run]', '<font id="workerLastRunCounter"></font>');
	}
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @return	String	String with HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	this.parseTextbox = function () {
		oLabelDiv = document.createElement('div');
		oLabelDiv.setAttribute('id', this.objId+'label');
		oLabelDiv.setAttribute('class', 'box');
		oLabelDiv.setAttribute('className', 'box');
		oLabelDiv.style.background=this.conf.background_color;
		oLabelDiv.style.borderColor=this.conf.border_color;
		
		oLabelDiv.style.position = 'absolute';
		oLabelDiv.style.left = this.conf.x+'px';
		oLabelDiv.style.top = this.conf.y+'px';
		oLabelDiv.style.width = this.conf.w+'px';
		oLabelDiv.style.zIndex = this.conf.z+1;
		oLabelDiv.style.overflow= 'visible';
		
		/**
		 * IE workaround: The transparent for the color is not enough. The border
		 * has really to be hidden.
		 */
		if(this.conf.border_color == 'transparent') {
			oLabelDiv.style.borderStyle = 'none';
		} else {
			oLabelDiv.style.borderStyle = 'solid';
		}
		
		// Create span for text and add label text
		var oLabelSpan = document.createElement('span');
		
		oLabelSpan.innerHTML = this.conf.text;
		
		oLabelDiv.appendChild(oLabelSpan);
		
		return oLabelDiv;	
	}
}
