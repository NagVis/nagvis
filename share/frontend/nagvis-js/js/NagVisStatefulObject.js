/*****************************************************************************
 *
 * NagVisObject.js - This class handles the visualisation of statefull objects
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

var NagVisStatefulObject = NagVisObject.extend({
	// Stores the information from last refresh (Needed for change detection)
	last_conf: null,
	// Array of member objects
	members: null,
	
	constructor: function(oConf) {
		// Call parent constructor
		this.base(oConf);
	},
	
	getMembers: function() {
		// Clear member array on every launch
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
					case 'line':
						oObj = new NagVisLine(oMember);
					break;
					default:
						alert('Error: Unknown member object type ('+oMember.type+')');
					break;
				}
				
				if(oObj !== null) {
					this.members.push(oObj);
				}
				
				oObj = null;
				oMember = null;
			}
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
		this.last_conf = {};
		
		// FIXME: Do not copy the whole conf array
		for (var i in this.conf) {
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
	 * PUBLIC stateChangedToWorse()
	 *
	 * Check if a state change occured to a worse state
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	stateChangedToWorse: function() {
		var lastSubState = 'normal';
		if(this.last_conf.summary_problem_has_been_acknowledged && this.last_conf.summary_problem_has_been_acknowledged == 1) {
			lastSubState = 'ack';
		} else if(this.last_conf.summary_in_downtime && this.last_conf.summary_in_downtime == 1) {
			lastSubState = 'downtime';
		}

		// If there is no "last state" return true here
		if(!this.last_conf.summary_state) {
			return true;
		}
		
		var lastWeight = oStates[this.last_conf.summary_state][lastSubState];
		
		var subState = 'normal';
		if(this.conf.summary_problem_has_been_acknowledged && this.conf.summary_problem_has_been_acknowledged == 1) {
			subState = 'ack';
		} else if(this.conf.summary_in_downtime && this.conf.summary_in_downtime == 1) {
			subState = 'downtime';
		}
		
		var weight = oStates[this.conf.summary_state][subState];
		
		if(lastWeight < weight) {
			return true;
		} else {
			return false;
		}
	},
	
	/**
	 * PUBLIC outputChanged()
	 *
	 * Check if an output/perfdata change occured since last refresh
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	outputOrPerfdataChanged: function() {
		if(this.conf.output != this.last_conf.output || 
		   this.conf.perfdata != this.last_conf.perfdata) {
			return true;
		} else {
			return false;
		}
	},
	
	/**
	 * PUBLIC parseAutomap()
	 *
	 * Parses the object on the automap
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseAutomap: function () {
		var oContainerDiv;
		
		if(!this.parsedObject) {
			// Only replace the macros on first parse
			this.replaceMacros();
		}
		
		// When this is an update, remove the object first
		this.remove();
		
		// Create container div
		oContainerDiv = document.createElement('div');
		oContainerDiv.setAttribute('id', this.conf.object_id);
		
		// Parse icon on automap
		var oIcon = this.parseAutomapIcon();
		oContainerDiv.appendChild(oIcon);
		oIcon = null;
		
		// Parse label when configured
		if(this.conf.label_show && this.conf.label_show == '1') {
			var oLabel = this.parseLabel();
			oContainerDiv.appendChild(oLabel);
			oLabel = null;
		}
    
    // Append child to map and save reference in parsedObject
		var oMap = document.getElementById('map');
		if(oMap) {
			this.parsedObject = oMap.appendChild(oContainerDiv);
			oMap = null;
		}
		oContainerDiv = null;
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
		
		// Only replace the macros on first parse
		if(!this.parsedObject) {
			this.replaceMacros();
		}
		
		// When this is an update, remove the object first
		this.remove();
		
		// Create container div
		oContainerDiv = document.createElement('div');
		oContainerDiv.setAttribute('id', this.conf.object_id);
		
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
			break;
		}
		
		// Parse label when configured
		if(this.conf.label_show && this.conf.label_show == '1') {
			var oLabel = this.parseLabel();
			oContainerDiv.appendChild(oLabel);
			oLabel = null;
		}
    
    // Append child to map and save reference in parsedObject
		var oMap = document.getElementById('map');
		if(oMap) {
			this.parsedObject = oMap.appendChild(oContainerDiv);
			oMap = null;
		}
		oContainerDiv = null;
		
		if(this.conf.view_type && this.conf.view_type == 'line') {
			this.drawLine();
		}
	},
	
	remove: function () {
		if(this.parsedObject) {
			var oMap = document.getElementById('map');
			if(!oMap)
				return;

			var oObj;
			if(this.conf.view_type && this.conf.view_type === 'line') {
				oObj = document.getElementById(this.conf.object_id+'-linediv');
			} else {
				oObj = document.getElementById(this.conf.object_id+'-icon');
			}
			
			if(oObj) {
				// Remove event listeners
				oObj.onmousedown = null;
				oObj.oncontextmenu = null;
				oObj.onmouseover = null;
				oObj.onmouseout = null;
				oObj = null;
			}

			var oContext = document.getElementById(this.conf.object_id+'-context');
			// Remove context menu
			// Needs to be removed after unsetting the eventhandlers
			if(oContext) {
				this.parsedObject.removeChild(oContext);
				oContext = null;
			}
			
			// Remove object from DOM
			oMap.removeChild(this.parsedObject);
			
			// Remove object reference
			this.parsedObject = null;
			
			oMap = null;
		}
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
		if(this.conf.view_type && this.conf.view_type === 'line')
			this.getHoverMenu(this.conf.object_id+'-linelinkdiv');
		else
			this.getHoverMenu(this.conf.object_id+'-icon');
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
        this.getContextMenu(this.conf.object_id+'-link');
      } else {
        this.getContextMenu(this.conf.object_id+'-icon');
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
		
		if(this.conf.url && this.conf.url !== '') {
			if(this.conf.htmlcgi && this.conf.htmlcgi !== '') {
				this.conf.url = this.conf.url.replace(new RegExp('\\[htmlcgi\\]', 'g'), this.conf.htmlcgi);
			} else {
				this.conf.url = this.conf.url.replace(new RegExp('\\[htmlcgi\\]', 'g'), oGeneralProperties.path_cgi);
			}
			
			this.conf.url = this.conf.url.replace(new RegExp('\\[htmlbase\\]', 'g'), oGeneralProperties.path_base);
			
			this.conf.url = this.conf.url.replace(new RegExp('\\['+name+'\\]', 'g'), this.conf.name);
			if(this.conf.type == 'service') {
				this.conf.url = this.conf.url.replace(new RegExp('\\[service_description\\]', 'g'), this.conf.service_description);
			}

			if(this.conf.type != 'map') {
				this.conf.url = this.conf.url.replace(new RegExp('\\[backend_id\\]', 'g'), this.conf.backend_id);
			}
			
			// Replace special chars in url
			this.conf.url = this.conf.url.replace(new RegExp('#', 'g'), '%23');
		}
		
		if(this.conf.hover_url && this.conf.hover_url !== '') {
			this.conf.hover_url = this.conf.hover_url.replace(new RegExp('\\['+name+'\\]', 'g'), this.conf.name);
			if(this.conf.type == 'service') {
				this.conf.hover_url = this.conf.hover_url.replace(new RegExp('\\[service_description\\]', 'g'), this.conf.service_description);
			}
		}
		
		// Replace static macros in label_text when needed
		if(this.conf.label_text && this.conf.label_text !== '') {
			var objName;
			// For maps use the alias as display string
			if(this.conf.type == 'map') {
				objName = this.conf.alias;   
			} else {
				objName = this.conf.name;
			}
			
			this.conf.label_text = this.conf.label_text.replace(new RegExp('\\[name\\]', 'g'), objName);
			this.conf.label_text = this.conf.label_text.replace(new RegExp('\\[alias\\]', 'g'), this.conf.alias);
			
			if(this.conf.type == 'service') {
				this.conf.label_text = this.conf.label_text.replace(new RegExp('\\[service_description\\]', 'g'), this.conf.service_description);
			}
		}
	},
	
	/**
	 * Replaces dynamic macros which need to be updated on every state refresh
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	replaceLabelTextDynamicMacros: function () {
		var sReturn = this.conf.label_text;
		
		// Replace static macros in label_text when needed
		if(sReturn && sReturn !== '') {
			sReturn = sReturn.replace(new RegExp('\\[output\\]', 'g'), this.conf.output);
			
			if(this.conf.type == 'service' || this.conf.type == 'host') {
				sReturn = sReturn.replace(new RegExp('\\[perfdata\\]', 'g'), this.conf.perfdata);
			}
		}
		
		return sReturn;
	},
	
	/**
	 * Parses the HTML-Code of a line
	 *
	 * @return	String		HTML code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseLine: function () {
		// Create container div
		var oContainerDiv = document.createElement('div');
		oContainerDiv.setAttribute('id', this.conf.object_id+'-linediv');
		
		// Create line div
		var oLineDiv = document.createElement('div');
		oLineDiv.setAttribute('id', this.conf.object_id+'-line');
		oLineDiv.style.zIndex = this.conf.z;
		
		oContainerDiv.appendChild(oLineDiv);
		oLineDiv = null;
		
		// Parse hover/link area only when needed
		if((this.conf.url && this.conf.url !== '') || (this.conf.hover_menu && this.conf.hover_menu !== '')) {
			var oLinkDiv = document.createElement('div');
			
			oLinkDiv.setAttribute('id', this.conf.object_id+'-linelinkdiv');
			oLinkDiv.style.zIndex = (this.conf.z+1);
			
			var sUrl = this.conf.url;
			var sUrlTarget = this.conf.url_target;
			oLinkDiv.onclick = function() { window.open(sUrl, sUrlTarget, ""); sUrl = null; sUrlTarget = null; };
			
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
		
		var colorFill = '';
    var colorFill2 = '';
		var colorBorder = '#000000';

		var setPerfdata = [];
		setPerfdata[0] = Array('dummyPercentIn', 88, '%', 85, 98, 0, 100);
		setPerfdata[1] = Array('dummyPercentOut', 99, '%', 85, 98, 0, 100);
		setPerfdata[2] = Array('dummyActualIn', 88.88, 'mB/s', 850, 980, 0, 1000);
		setPerfdata[3] = Array('dummyActualOut', 99.99, 'mB/s', 850, 980, 0, 1000);
		
		// Get the fill color depending on the object state
		switch (this.conf.summary_state) {
	    case 'UNREACHABLE':
			case 'DOWN':
			case 'CRITICAL':
			case 'WARNING':
	    case 'UNKNOWN':
			case 'ERROR':
	    case 'UP':
	    case 'OK':
	    case 'PENDING':
				colorFill = oStates[this.conf.summary_state].color;
			break;
			default:
				colorFill = '#FFCC66';
			break;
	  }

		// Adjust fill color based on perfdata for weathermap lines
		if(this.conf.line_type == 13 || this.conf.line_type == 14) {
			colorFill = '#000000';
			colorFill2 = '#000000';

			// Convert perfdata to structured array
			setPerfdata = splicePerfdata(this.conf.perfdata);
	
			// array index returned from splice function
			/* 0 = label
			   1 = value
			   2 = unit of measure (UOM)
			   3 = warning
			   4 = critical
			   5 = minimum
			   6 = maximum
			*/

			// Check perfdata array, did we get usable data back
			if(setPerfdata == 'empty' || setPerfdata[0][0] == 'dummyPercentIn' || setPerfdata[1][0] == 'dummyPercentOut') {
				var msg = "Missing performance data - value 1 is \'" + setPerfdata[0][1] + "\' value 2 is \'" + setPerfdata[1][1] + "\'";
		
				if(this.conf.line_type == 14 && (setPerfdata[2][0] == 'dummyActualIn' || setPerfdata[3][0] == 'dummyActualOut'))
					msg += " value 3 is \'" + setPerfdata[2][1] + "\' value 4 is \'" + setPerfdata[3][1] + "\'";
				
				this.conf.summary_output += ' (Weathermap Line Error: ' + msg + ')'
			} else {
				// This is the correct place to handle other perfdata format than the percent value
				// When no UOM is set try to calculate something...
				if(setPerfdata[0][2] === null || setPerfdata[0][2] === ''
           || setPerfdata[1][2] === null || setPerfdata[1][2] === '') {
					setPerfdata = this.calculatePercentageUsage(setPerfdata);
				}

				// Get colorFill #1 (in)
				if(setPerfdata[0][2] !== null && setPerfdata[0][2] == '%' && setPerfdata[0][1] !== null && setPerfdata[0][1] >= 0 && setPerfdata[0][1] <= 100)
					colorFill = getColorFill(setPerfdata[0][1]);
				else
					this.perfdataError('First', setPerfdata[0][1], this.conf.name, this.conf.service_description);
				
				// Get colorFill #2 (out)
				if(setPerfdata[1][2] !== null && setPerfdata[1][2] == '%' && setPerfdata [1][1] !== null && setPerfdata[1][1] >= 0 && setPerfdata[1][1] <= 100)
					colorFill2 = getColorFill(setPerfdata[1][1]);
				else
					this.perfdataError('Second', setPerfdata[1][1], this.conf.name, this.conf.service_description);
			}
		}

		// Get the border color depending on ack/downtime
		if(this.conf.summary_problem_has_been_acknowledged == 1 || this.conf.summary_in_downtime == 1) {
			colorBorder = '#666666';
			colorFill = lightenColor(colorFill, 100, 100, 100);
		}

		// Parse the line object
		drawNagVisLine(this.conf.object_id, this.conf.line_type, x[0], y[0], x[1], y[1],
		               this.conf.z, width, colorFill, colorFill2, setPerfdata, colorBorder,
		               ((this.conf.url && this.conf.url !== '') || (this.conf.hover_menu && this.conf.hover_menu !== '')));
	},

	/**
	 * PRIVATE calculatePercentageUsage()
	 *
	 * Loops all perfdata sets and searches for labels "in" and "out"
	 * with an empty UOM. If found it uses the current value and max value
	 * for calculating the percentage usage.
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	calculatePercentageUsage: function(oldPerfdata) {
		var newPerfdata = [];
		var foundNew = false;
		
		// This loop takes perfdata with the labels "in" and "out" and uses the current value
		// and maximum values to parse the percentage usage of the line
		for(var i = 0; i < oldPerfdata.length; i++) {
			if(oldPerfdata[i][0] == 'in' && (oldPerfdata[i][2] === null || oldPerfdata[i][2] === '')) {
				newPerfdata[0] = this.perfdataCalcPerc(oldPerfdata[i]);
				foundNew = true;
			}
			if(oldPerfdata[i][0] == 'out' && (oldPerfdata[i][2] === null || oldPerfdata[i][2] === '')) {
				newPerfdata[1] = this.perfdataCalcPerc(oldPerfdata[i]);
				foundNew = true;
			}
		}
		if(foundNew)
			return newPerfdata;
		else
			return oldPerfdata;
	},

	/**
	 * PRIVATE perfdataCalcPerc()
	 *
	 * Calculates the percentage usage of a line when the current value
	 *  and the max value are given in the perfdata string
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	perfdataCalcPerc: function(set) {
		// Check if all needed information are present
		if(set[1] === null || set[6] === null || set[1] == '' || set[6] == '')
			return set;

		// Calculate percentages with 2 decimals and reset other options
		set[1] = Math.round(set[1]*100/set[6]*100)/100;
		set[2] = '%';
		set[5] = 0;
		set[6] = 100;

		return set;
	},

	/**
	 * PRIVATE perfdataError()
	 *
	 * Tells the user about wrong perfdata information
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	perfdataError: function(type, value, name1, name2) {
		frontendMessage({'type': 'WARNING',
		                 'message': type+' set of performance data ('+value+') for  '+name1+' ['+name2+'] is not a percentage value',
		                 'title': 'Data error'});
	},
	
	/**
	 * PUBLIC parseAutomapIcon()
	 *
	 * Parses the HTML-Code of an automap icon
	 *
	 * @return	String		String with Html Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	parseAutomapIcon: function () {
		var alt = '';
		
		if(this.type == 'service') {
			alt = this.conf.name+'-'+this.conf.service_description;
		} else {
			alt = this.conf.name;
		}
		
		var oIcon = document.createElement('img');
		oIcon.setAttribute('id', this.conf.object_id+'-icon');
		oIcon.src = this.conf.iconHtmlPath+this.conf.icon;
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
		oIcon.setAttribute('id', this.conf.object_id+'-icon');
		oIcon.src = this.conf.iconHtmlPath+this.conf.icon;
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
		if(this.conf.label_x && this.conf.label_x.toString().match(/^(?:\+|\-)/)) {
			this.conf.label_x = parseFloat(this.conf.x) + parseFloat(this.conf.label_x);
		}
		if(this.conf.label_y && this.conf.label_y.toString().match(/^(?:\+|\-)/)) {
			this.conf.label_y = parseFloat(this.conf.y) + parseFloat(this.conf.label_y);
		}
		
		// If no x/y coords set, fallback to object x/y
		if(!this.conf.label_x || this.conf.label_x === '' || this.conf.label_x === '0') {
			this.conf.label_x = this.conf.x;
		}
		if(!this.conf.label_y || this.conf.label_y === '' || this.conf.label_y === '0') {
			this.conf.label_y = this.conf.y;
		}

		return drawNagVisTextbox(this.conf.object_id + '-label', 'object_label', this.conf.label_background, this.conf.label_border, this.conf.label_x, this.conf.label_y, this.conf.z, this.conf.label_width, '', this.replaceLabelTextDynamicMacros(), this.conf.label_style);
	}
});
