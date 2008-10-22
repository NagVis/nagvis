/*****************************************************************************
 *
 * NagVisShape.js - This class handles the visualisation of shape objects
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

NagVisShape.Inherits(NagVisStatelessObject);
function NagVisShape (oConf) {
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
		
		// Create container div
		oContainerDiv = document.createElement('div');
		oContainerDiv.setAttribute('id', this.objId);
		
		oContainerDiv.appendChild(this.parseShape());
		
		// Parse label when configured
		if(this.conf.label_show && this.conf.label_show == '1') {
			oContainerDiv.appendChild(this.parseLabel());
		}
		
		// When this is an update, remove the object first
		if(this.parsedObject) {
			document.getElementById('map').removeChild(this.parsedObject);
		}
		this.parsedObject = document.getElementById('map').appendChild(oContainerDiv);
	}
	
	/**
	 * Parses the shape
	 *
	 * @return	String	String with Html Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	this.parseShape = function () {
		var oIconDiv = document.createElement('div');
		oIconDiv.setAttribute('class', 'icon');
		oIconDiv.setAttribute('className', 'icon');
		oIconDiv.style.top = this.conf.y+'px';
		oIconDiv.style.left = this.conf.x+'px';
		oIconDiv.style.zIndex = this.conf.z;
		
		var oIcon = document.createElement('img');
		oIcon.src = this.conf.icon;
		oIcon.alt = this.conf.type;
		
		if(this.conf.url && this.conf.url != '') {
			var oIconLink = document.createElement('a');
			oIconLink.href = this.conf.url;
			oIconLink.target = this.conf.url_target;
			oIconLink.appendChild(oIcon);
			
			oIconDiv.appendChild(oIconLink);
		} else {
			oIconDiv.appendChild(oIcon);
		}
		
		if(this.conf.hover_url && this.conf.hover_url != '') {
			this.getHoverMenu(oIcon);
		}
		
		return oIconDiv;
	}
}
