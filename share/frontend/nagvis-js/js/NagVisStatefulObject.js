/*****************************************************************************
 *
 * NagVisObject.js - This class handles the visualisation of statefull objects
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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
    has_state: true,

    // Stores the information from last refresh (Needed for change detection)
    last_state: null,
    // Array of member objects
    members: [],
    // Timestamps for last event handling events if repeated events are enabled
    event_time_first: null,
    event_time_last: null,

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
                    case 'dyngroup':
                        oObj = new NagVisDynGroup(oMember);
                    break;
                    case 'aggr':
                        oObj = new NagVisAggr(oMember);
                    break;
                    case 'map':
                        oObj = new NagVisMap(oMember);
                    break;
                    case 'textbox':
                        oObj = new NagVisTextbox(oMember);
                    break;
                    case 'container':
                        oObj = new NagVisContainer(oMember);
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
        this.last_state = {
          'summary_state': this.conf.summary_state,
            'summary_in_downtime': this.conf.summary_in_downtime,
            'summary_stale': this.conf.summary_stale,
            'summary_problem_has_been_acknowledged': this.conf.summary_problem_has_been_acknowledged,
            'output': this.conf.output,
            'perfdata': this.conf.perfdata
        };
    },

    /**
     * PUBLIC stateChanged()
     *
     * Check if a state change occured since last refresh
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    stateChanged: function() {
        if(this.conf.summary_state != this.last_state.summary_state ||
           this.conf.summary_problem_has_been_acknowledged != this.last_state.summary_problem_has_been_acknowledged ||
           this.conf.summary_stale != this.last_state.summary_stale ||
           this.conf.summary_in_downtime != this.last_state.summary_in_downtime) {
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
        if(this.last_state.summary_problem_has_been_acknowledged && this.last_state.summary_problem_has_been_acknowledged === 1) {
            lastSubState = 'ack';
        } else if(this.last_state.summary_in_downtime && this.last_state.summary_in_downtime == 1) {
            lastSubState = 'downtime';
        } else if(this.last_state.summary_stale) {
            lastSubState = 'stale';
        }

        // If there is no "last state" return true here
        if(!this.last_state.summary_state) {
            return true;
        }

        var lastWeight = oStates[this.last_state.summary_state][lastSubState];

        var subState = 'normal';
        if(this.conf.summary_problem_has_been_acknowledged && this.conf.summary_problem_has_been_acknowledged === 1) {
            subState = 'ack';
        } else if(this.conf.summary_in_downtime && this.conf.summary_in_downtime === 1) {
            subState = 'downtime';
        } else if(this.conf.summary_stale) {
            subState = 'stale';
        }

        var weight = oStates[this.conf.summary_state][subState];

        return lastWeight < weight;
    },

    /**
     * Returns true if the object is in non acked problem state
     */
    hasProblematicState: function() {
        // In case of acked/downtimed states this is no problematic state
        if(this.conf.summary_problem_has_been_acknowledged && this.conf.summary_problem_has_been_acknowledged === 1) {
            return false;
        } else if(this.conf.summary_in_downtime && this.conf.summary_in_downtime === 1) {
            return false;
        } else if(this.conf.summary_stale && this.conf.summary_stale) {
            return false;
        }
        
        var weight = oStates[this.conf.summary_state]['normal'];
        return weight > oStates['UP']['normal'];
    },

    /**
     * PUBLIC outputChanged()
     *
     * Check if an output/perfdata change occured since last refresh
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    outputOrPerfdataChanged: function() {
        return this.conf.output != this.last_state.output || this.conf.perfdata != this.last_state.perfdata;
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
        // Only replace the macros on first parse
        if(!this.parsedObject) {
            this.replaceMacros();
        }

        // When this is an update, remove the object first
        this.remove();

        // Create container div
        var doc = document;
        var oContainerDiv = doc.createElement('div');
        oContainerDiv.setAttribute('id', this.conf.object_id);

        // Parse object depending on line or normal icon
        switch(this.conf.view_type) {
            case 'line':
                oContainerDiv.appendChild(this.parseLine());
            break;
            case 'gadget':
                oContainerDiv.appendChild(this.parseGadget());
            break;
            default:
                oContainerDiv.appendChild(this.parseIcon());
            break;
        }

        // Append child to map and save reference in parsedObject
        var oMap = doc.getElementById('map');
        if(oMap) {
            this.parsedObject = oMap.appendChild(oContainerDiv);
            oMap = null;
        }
        doc = null;

        // Parse label when configured
        if(this.conf.label_show && this.conf.label_show == '1') {
            this.parseLabel(oContainerDiv);
        }

        oContainerDiv = null;

        // Now really draw the line when this is one
        if(this.conf.view_type && this.conf.view_type == 'line')
            this.drawLine();

        // Enable the controls when the object is not locked
        if(!this.bIsLocked) {
            this.parseControls();
	    this.unlockLabel();

	    if(typeof(this.toggleObjectActions) == 'function')
                this.toggleObjectActions(this.bIsLocked);
	}
    },

    /**
     * Is called to remove the rendered object
     * This must not remove the object from the JS lists
     */
    remove: function () {
        // Parsed object is the container with the id "<object_id>"
        if(!this.parsedObject)
            return;

        var doc = document;
        var oMap = doc.getElementById('map');
        if(!oMap) {
            doc = null;
            return;
        }

        //
        // Remove all event handlers
        //
        var oObj;
        // In case of lines the *-linelink div holds the event handlers
        if(isset(this.conf.view_type) && this.conf.view_type === 'line')
            oObj = doc.getElementById(this.conf.object_id+'-linelink');
        else
            oObj = doc.getElementById(this.conf.object_id+'-icon');

        if(oObj) {
            // Remove event listeners
            oObj.onmousedown    = null;
            oObj.oncontextmenu  = null;
            oObj.onmouseover    = null;
            oObj.onmouseout     = null;
            oObj.onload         = null;
            oObj = null;
        }

        // Remove context, hover menus and the labels
        // Needs to be removed after unsetting the eventhandlers
        var oContext = doc.getElementById(this.conf.object_id+'-context');
        if(oContext) {
            try {
                this.parsedObject.removeChild(oContext);
            } catch(e) {}
            oContext = null;
        }
        var oHover = doc.getElementById(this.conf.object_id+'-hover');
        if(oHover) {
            try {
                this.parsedObject.removeChild(oHover);
            } catch(e) {}
            oHover = null;
        }
        var oLabel = doc.getElementById(this.conf.object_id+'-label');
        if(oLabel) {
            try {
                this.parsedObject.removeChild(oLabel);
            } catch(e) {}
            oLabel = null;
        }

        // Remove icons
        if(isset(this.conf.view_type) && this.conf.view_type === 'line') {
            var linediv = doc.getElementById(this.conf.object_id+'-linediv');
            linediv.removeChild(doc.getElementById(this.conf.object_id+'-line'));
            linediv.removeChild(doc.getElementById(this.conf.object_id+'-linelink'));
            this.parsedObject.removeChild(linediv);
        } else {
            this.parsedObject.removeChild(doc.getElementById(this.conf.object_id+'-icondiv'));
        }

        // Remove all controls
        if(!this.bIsLocked)
            this.removeControls();

        // Remove object from DOM
        oMap.removeChild(this.parsedObject);

        // Remove object reference
        this.parsedObject = null;

        oMap = null;
        doc = null;
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
        this.getHoverMenu(this.getJsObjId());

        // Display the hover menu when it was open before re-rendering
        if(this.hoverX !== null) {
            hoverShow(this.hoverX, this.hoverY, this.conf.object_id);
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
                this.conf.url = this.conf.url.replace(getRegEx('htmlcgi', '\\[htmlcgi\\]', 'g'), this.conf.htmlcgi);
            } else {
                this.conf.url = this.conf.url.replace(getRegEx('htmlcgi', '\\[htmlcgi\\]', 'g'), oGeneralProperties.path_cgi);
            }

            this.conf.url = this.conf.url.replace(getRegEx('htmlbase', '\\[htmlbase\\]', 'g'), oGeneralProperties.path_base);

            this.conf.url = this.conf.url.replace(getRegEx(name, '\\['+name+'\\]', 'g'), this.conf.name);
            if(this.conf.type == 'service') {
                this.conf.url = this.conf.url.replace(getRegEx('service_description', '\\[service_description\\]', 'g'), this.conf.service_description);
            }

            if(this.conf.type != 'map') {
                this.conf.url = this.conf.url.replace(getRegEx('backend_id', '\\[backend_id\\]', 'g'), this.conf.backend_id);
            }
        }

        if(this.conf.hover_url && this.conf.hover_url !== '') {
            this.conf.hover_url = this.conf.hover_url.replace(getRegEx(name, '\\['+name+'\\]', 'g'), this.conf.name);
            if(this.conf.type == 'service') {
                this.conf.hover_url = this.conf.hover_url.replace(getRegEx('service_description', '\\[service_description\\]', 'g'), this.conf.service_description);
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

            this.conf.label_text = this.conf.label_text.replace(getRegEx('name', '\\[name\\]', 'g'), objName);
            this.conf.label_text = this.conf.label_text.replace(getRegEx('alias', '\\[alias\\]', 'g'), this.conf.alias);

            if(this.conf.type == 'service') {
                this.conf.label_text = this.conf.label_text.replace(getRegEx('service_description', '\\[service_description\\]', 'g'), this.conf.service_description);
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
            sReturn = sReturn.replace(getRegEx('output', '\\[output\\]', 'g'), this.conf.output);

            if(this.conf.type == 'service' || this.conf.type == 'host') {
                sReturn = sReturn.replace(getRegEx('perfdata', '\\[perfdata\\]', 'g'), this.conf.perfdata);
            }
        }

        if (this.conf.label_maxlen > 0 && sReturn.length > this.conf.label_maxlen) {
            sReturn = sReturn.substr(0, this.conf.label_maxlen - 2) + '...';
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
        var doc = document;
        var oContainerDiv = doc.createElement('div');
        oContainerDiv.setAttribute('id', this.conf.object_id+'-linediv');

        // Create line div
        var oLineDiv = doc.createElement('div');
        oLineDiv.setAttribute('id', this.conf.object_id+'-line');
        oLineDiv.style.zIndex = this.conf.z;

        oContainerDiv.appendChild(oLineDiv);
        oLineDiv = null;

        this.parseLineHoverArea(oContainerDiv);

        doc = null;
        return oContainerDiv;
    },

    /**
     * Draws the NagVis lines on the already added divs.
     *
     * @return	String		HTML code
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    drawLine: function() {
        var width = addZoomFactor(this.conf.line_width);
        if(width <= 0)
            width = 1; // minimal width for lines

        var colorFill   = '';
        var colorFill2  = '';
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
        if(this.conf.line_type == 13 || this.conf.line_type == 14 || this.conf.line_type == 15) {
            colorFill  = '#000000';
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
            if(setPerfdata == 'empty'
               || !isset(setPerfdata[0]) || setPerfdata[0][0] == 'dummyPercentIn'
               || !isset(setPerfdata[1]) || setPerfdata[1][0] == 'dummyPercentOut'
               || (this.conf.line_type == 14 && (
                   !isset(setPerfdata[2]) || setPerfdata[2][0] == 'dummyActualIn'
                   || !isset(setPerfdata[3]) || setPerfdata[3][0] == 'dummyActualOut'))) {

                var msg = "Missing performance data - ";
                if(setPerfdata == 'empty')
                        msg += "perfdata string is empty";
                else {
                    if(isset(setPerfdata[0]) && setPerfdata[0][0] == 'dummyPercentIn')
                  	msg += "value 1 is \'" + setPerfdata[0][1] + "\'";

                    if(isset(setPerfdata[1]) && setPerfdata[1][0] == 'dummyPercentOut')
                  	msg += " value 2 is \'" + setPerfdata[1][1] + "\'";

                    if(this.conf.line_type == 14) {
                        if(isset(setPerfdata[2]) && setPerfdata[2][0] == 'dummyActualIn')
                            msg += " value 3 is \'" + setPerfdata[2][1] + "\'";

                        if(isset(setPerfdata[3]) && setPerfdata[3][0] == 'dummyActualOut')
                            msg += " value 4 is \'" + setPerfdata[3][1] + "\'";
                    }
                }

                this.conf.summary_output += ' (Weathermap Line Error: ' + msg + ')';
            } else {
                // This is the correct place to handle other perfdata format than the percent value

                // When no UOM is set try to calculate something...
                // This can fix the perfdata values from Check_MKs if and if64 checks.
                // The assumption is that there are perfdata values 'in' and 'out' with byte rates
                // and maximum values given to be able to calculate the percentage usage
                if(setPerfdata[0][2] === null || setPerfdata[0][2] === ''
                   || setPerfdata[1][2] === null || setPerfdata[1][2] === '') {
                    setPerfdata = this.calculateUsage(setPerfdata);
                }

                // Get colorFill #1 (in)
                if(setPerfdata[0][2] !== null && setPerfdata[0][2] == '%' && setPerfdata[0][1] !== null) {
                    colorFill = this.getColorFill(setPerfdata[0][1]);
                } else {
                    colorFill = '#000000';
                    this.perfdataError('First', setPerfdata[0][1], this.conf.name, this.conf.service_description);
                }

                // Get colorFill #2 (out)
                if(setPerfdata[1][2] !== null && setPerfdata[1][2] == '%' && setPerfdata [1][1] !== null) {
                    colorFill2 = this.getColorFill(setPerfdata[1][1]);
                } else {
                    colorFill2 = '#000000';
                    this.perfdataError('Second', setPerfdata[1][1], this.conf.name, this.conf.service_description);
                }
            }
        }

        // Get the border color depending on ack/downtime
        if(this.conf.summary_problem_has_been_acknowledged === 1 || this.conf.summary_in_downtime === 1 || this.conf.summary_stale) {
            colorBorder = '#666666';
            colorFill = lightenColor(colorFill, 100, 100, 100);
        }

        // Parse the line object
        drawNagVisLine(this.conf.object_id, this.conf.line_type, this.lineCoords(),
                       this.conf.z, width, colorFill, colorFill2, setPerfdata, colorBorder,
                       this.needsLineHoverArea(),
                       (this.conf.line_label_show && this.conf.line_label_show === '1'),
                       parseInt(this.conf.line_label_y_offset));
    },

    /**
     * PRIVATE getColorFill()
     *
     * This function returns the color to use for this line depending on the
     * given percentage usage and on the configured options for this object
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    getColorFill: function(perc) {
        var ranges = this.conf.line_weather_colors.split(',');
        // 0 contains the percentage until this color is used
        // 1 contains the color to be used
        for(var i = 0; i < ranges.length; i++) {
            var parts = ranges[i].split(':');
            if(parseFloat(perc) <= parts[0])
                return parts[1];
            parts = null;
        }
        ranges = null;
        return '#000000';
    },

    /**
     * PRIVATE calculateUsage()
     *
     * Loops all perfdata sets and searches for labels "in" and "out"
     * with an empty UOM. If found it uses the current value and max value
     * for calculating the percentage usage and also the current usage.
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    calculateUsage: function(oldPerfdata) {
        var newPerfdata = [];
        var foundNew = false;

        // Check_MK if/if64 checks support switching between bytes/bits. The detection
        // can be made by some curios hack. The most hackish hack I've ever seen. From hell.
        // Well, let's deal with it.
        var display_bits = false;
        if(oldPerfdata.length >= 11 && oldPerfdata[10][5] == '0.0')
            display_bits = true;

        // This loop takes perfdata with the labels "in" and "out" and uses the current value
        // and maximum values to parse the percentage usage of the line
        for(var i = 0; i < oldPerfdata.length; i++) {
            if(oldPerfdata[i][0] == 'in' && (oldPerfdata[i][2] === null || oldPerfdata[i][2] === '')) {
                newPerfdata[0] = this.perfdataCalcPerc(oldPerfdata[i]);
                if(!display_bits) {
                    newPerfdata[2] = this.perfdataCalcBytesReadable(oldPerfdata[i]);
                } else {
                    oldPerfdata[i][1] *= 8; // convert those hakish bytes to bits
                    newPerfdata[2] = this.perfdataCalcBitsReadable(oldPerfdata[i]);
                }
                foundNew = true;
            }
            if(oldPerfdata[i][0] == 'out' && (oldPerfdata[i][2] === null || oldPerfdata[i][2] === '')) {
                newPerfdata[1] = this.perfdataCalcPerc(oldPerfdata[i]);
                if(!display_bits) {
                    newPerfdata[3] = this.perfdataCalcBytesReadable(oldPerfdata[i]);
                } else {
                    oldPerfdata[i][1] *= 8; // convert those hakish bytes to bits
                    newPerfdata[3] = this.perfdataCalcBitsReadable(oldPerfdata[i]);
                }
                foundNew = true;
            }
        }
        if(foundNew)
            return newPerfdata;
        else
            return oldPerfdata;
    },

    /**
     * PRIVATE perfdataCalcBitsReadable()
     *
     * Transform bits in a perfdata set to a human readable value
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    perfdataCalcBitsReadable: function(set) {
        var KB   = 1024;
        var MB   = 1024 * 1024;
        var GB   = 1024 * 1024 * 1024;
        if(set[1] > GB) {
            set[1] /= GB
            set[2]  = 'Gbit/s'
        } else if(set[1] > MB) {
            set[1] /= MB
            set[2]  = 'Mbit/s'
        } else if(set[1] > KB) {
            set[1] /= KB
            set[2]  = 'Kbit/s'
        } else {
            set[2]  = 'bit/s'
        }
        set[1] = Math.round(set[1]*100)/100;
        return set;
    },

    /**
     * PRIVATE perfdataCalcBytesReadable()
     *
     * Transform bytes in a perfdata set to a human readable value
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    perfdataCalcBytesReadable: function(set) {
        var KB   = 1024;
        var MB   = 1024 * 1024;
        var GB   = 1024 * 1024 * 1024;
        if(set[1] > GB) {
            set[1] /= GB
            set[2]  = 'GB/s'
        } else if(set[1] > MB) {
            set[1] /= MB
            set[2]  = 'MB/s'
        } else if(set[1] > KB) {
            set[1] /= KB
            set[2]  = 'KB/s'
        } else {
            set[2]  = 'B/s'
        }
        set[1] = Math.round(set[1]*100)/100;
        return set;
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
        return Array(set[0], Math.round(set[1]*100/set[6]*100)/100, '%', set[3], set[4], 0, 100);
    },

    /**
     * PRIVATE perfdataError()
     *
     * Tells the user about wrong perfdata information
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    perfdataError: function(type, value, name1, name2) {
        this.conf.summary_output += ' (Weathermap Line Error: ' + type+' set of performance data ('+value+') for  '+name1+' ['+name2+'] is not a percentage value)';
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

        var doc = document;
        var oIcon = doc.createElement('img');
        oIcon.setAttribute('id', this.conf.object_id+'-icon');

        // Register controls reposition handler to handle resizes during
        // loading the image (from alt="" text to the real image
        oIcon.onload = function() {
            // In the event handler "this" points to the image object
            var arr   = this.id.split('-');
            var objId = arr[0];
            var obj = getMapObjByDomObjId(objId);
            if (obj) {
                if (obj.conf.label_show && obj.conf.label_show == '1')
                    obj.updateLabel();

                if (!obj.bIsLocked)
                    obj.redrawControls();
                obj = null;
            }
            objId = null;
            arr = null;
        };

        addZoomHandler(oIcon);

        oIcon.src = oGeneralProperties.path_iconsets + this.conf.icon;
        oIcon.alt = this.conf.type + '-' + alt;

        var oIconDiv = doc.createElement('div');
        oIconDiv.setAttribute('id', this.conf.object_id+'-icondiv');
        oIconDiv.setAttribute('class', 'icon');
        oIconDiv.setAttribute('className', 'icon');
        oIconDiv.style.position = 'absolute';
        oIconDiv.style.top  = this.parseCoord(this.conf.y, 'y') + 'px';
        oIconDiv.style.left = this.parseCoord(this.conf.x, 'x') + 'px';
        oIconDiv.style.zIndex = this.conf.z;

        // Parse link only when set
        if(this.conf.url && this.conf.url !== '' && this.conf.url !== '#') {
            var oIconLink = doc.createElement('a');
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

        doc = null;
        return oIconDiv;
    },

    /**
     * Moves the label of the object after the objec thas been dragged
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    updateLabel: function () {
        var label = document.getElementById(this.conf.object_id + '-label');
        if (label) {
            this.updateLabelPos(label);
            label = null;
        }
    },

    /**
     * Handles drag events of the label
     *
     * This needs to calculate the offset of the current position to the first position,
     * then create a new coord (relative/absolue) and save them in label_x/y attributes
     *
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    dragLabel: function(obj, event) {
        var arr        = obj.id.split('-');
        var objId      = arr[0];
        var anchorType = arr[1];

        var viewType = getDomObjViewType(objId);

        var jsObj = getMapObjByDomObjId(objId);

        jsObj.conf.label_x = jsObj.calcNewLabelCoord(jsObj.conf.label_x, jsObj.parseCoord(jsObj.conf.x, 'x', false), obj.x);
        jsObj.conf.label_y = jsObj.calcNewLabelCoord(jsObj.conf.label_y, jsObj.parseCoord(jsObj.conf.y, 'y', false), obj.y);

        jsObj      = null;
        objId      = null;
        anchorType = null;
        viewType   = null;
    },

    /**
     * Calculates relative/absolute coords depending on the current configured type
     */
    calcNewLabelCoord: function (labelCoord, coord, newCoord) {
	if(labelCoord.toString().match(/^(?:\+|\-)/)) {
	    var ret = newCoord - coord;
	    if(ret >= 0)
	        return '+' + ret;
	    return ret;
	} else
	    return newCoord;
    },

    /**
     * Handler for the drop event
     *
     * Important: This is called from an event handler
     * the 'this.' keyword can not be used here.
     */
    saveLabel: function(obj, oParent) {
        var arr        = obj.id.split('-');
        var objId      = arr[0];
        var jsObj      = getMapObjByDomObjId(objId);
        saveObjectAttr(objId, { 'label_x': jsObj.conf.label_x, 'label_y': jsObj.conf.label_y});
	jsObj = null;
	arr   = null;
    },

    /**
     * Calculates and applies the real positions of the objects label. It uses the configuration
     * variables label_x/label_y and repositions the labels based on the config. The label
     * must have been rendered and added to dom to have the dimensions of the object to be able
     * to realize the center/bottom coordinate definitions.
     */
    updateLabelPos: function (oLabel) {
        oLabel.style.left = this.parseLabelCoord('x', oLabel) + 'px';
        oLabel.style.top  = this.parseLabelCoord('y', oLabel) + 'px';
        oLabel = null;
    },

    parseLabelCoord: function (dir, oLabel) {
        if (dir === 'x') {
            var coord = this.conf.label_x;

            if (this.conf.view_type && this.conf.view_type == 'line') {
                var obj_coord = this.getLineMid(this.conf.x, 'x');
            } else {
                var obj_coord = addZoomFactor(this.parseCoords(this.conf.x, 'x', false)[0], true);
            }
        } else {
            var coord = this.conf.label_y;
            if (this.conf.view_type && this.conf.view_type == 'line') {
                var obj_coord = this.getLineMid(this.conf.y, 'y');
            } else {
                var obj_coord = addZoomFactor(this.parseCoords(this.conf.y, 'y', false)[0], true);
            }
        }

        if (dir == 'x' && coord && coord.toString() == 'center') {
            var diff = parseInt(parseInt(oLabel.clientWidth) - rmZoomFactor(this.getObjWidth())) / 2;
            coord = obj_coord - diff;
        } else if (dir == 'y' && coord && coord.toString() == 'bottom') {
            coord = obj_coord + rmZoomFactor(this.getObjHeight());
        } else if (coord && coord.toString().match(/^(?:\+|\-)/)) {
            // If there is a presign it should be relative to the objects x/y
            coord = obj_coord + addZoomFactor(parseFloat(coord));
        } else if (!coord || coord === '0') {
           // If no x/y coords set, fallback to object x/y
            coord = obj_coord;
        } else {
            // This must be absolute coordinates, apply zoom factor
            coord = addZoomFactor(coord, true);
        }

        return coord;
    },

    /**
     * Parses the HTML-Code of a label
     *
     * @return	String		HTML code of the label
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    parseLabel: function (oContainer) {
        var oLabel = drawNagVisTextbox(
            oContainer, this.conf.object_id + '-label', 'object_label',
            this.conf.label_background, this.conf.label_border,
            // use object coords for initial rendering, updated by updateLabelPos below
            this.parseCoord(this.conf.x, 'x', false), this.parseCoord(this.conf.y, 'y', false),
            this.conf.z,
            this.conf.label_width, '', this.replaceLabelTextDynamicMacros(),
            this.conf.label_style
        );
        this.updateLabelPos(oLabel);
        oLabel = null;
    },

    unlockLabel: function () {
	var o = document.getElementById(this.conf.object_id + '-label');
	if(!o)
	    return;
	o.onmouseover = function() {
            document.body.style.cursor = 'move';
        };
        o.onmouseout = function() {
            document.body.style.cursor = 'auto';
        };

	makeDragable([o], this.saveLabel, this.dragLabel);
	o = null;
    },

    lockLabel: function () {
	var o = document.getElementById(this.conf.object_id + '-label');
	if(!o)
	    return;
	// Clone the node to remove all attached event handlers
	var n = o.cloneNode(true);
	o.parentNode.replaceChild(n, o);
	makeUndragable([o]);
	o = null;
	n = null;
    },

    toggleLabelLock: function () {
	if(this.bIsLocked)
	    this.lockLabel();
	else
	    this.unlockLabel();
    },

    parseIconControls: function () {
        // Simply make it dragable. Maybe will be extended in the future...
        makeDragable([this.conf.object_id+'-icondiv'], this.saveObject, this.moveObject);
    },

    highlight: function(show) {
        // FIXME: Highlight lines in the future too
        if(this.conf.view_type !== 'icon')
            return;

        var oObjIcon = document.getElementById(this.conf.object_id + '-icon');
        var oObjIconDiv = document.getElementById(this.conf.object_id + '-icondiv');

        var sColor = oStates[this.conf.summary_state].color;

        this.bIsFlashing = show;
        if(show) {
            oObjIcon.style.border  = "5px solid " + sColor;
            oObjIconDiv.style.top  = (this.parseCoord(this.conf.y, 'y') - 5) + 'px';
            oObjIconDiv.style.left = (this.parseCoord(this.conf.x, 'x') - 5) + 'px';
        } else {
            oObjIcon.style.border  = "none";
            oObjIconDiv.style.top  = this.parseCoord(this.conf.y, 'y') + 'px';
            oObjIconDiv.style.left = this.parseCoord(this.conf.x, 'x') + 'px';
        }

        sColor      = null;
        oObjIconDiv = null;
        oObjIcon    = null;
    },

    requestGadget: function (param_str) {
        var data = 'members='+escape(JSON.stringify(this.conf.members));
        return postSyncUrl(this.conf.gadget_url + param_str, data);
    },

    detectGadgetType: function (param_str) {
        var content = this.requestGadget(param_str);
        if (content.substring(0, 4) === 'GIF8' || ! /^[\x00-\x7F]*$/.test(content[0]))
            this.gadget_type = 'img';
        else
            this.gadget_type = 'html';
    },

    /**
     * Parses the object as gadget
     */
    parseGadget: function () {
        var sParams = 'name1=' + this.conf.name;
        if (this.conf.type == 'service')
            sParams += '&name2=' + escapeUrlValues(this.conf.service_description);

        sParams += '&type=' + this.conf.type
                 + '&object_id=' + this.conf.object_id
                 + '&scale=' + escapeUrlValues(this.conf.gadget_scale.toString())
                 + '&state=' + this.conf.summary_state
                 + '&stateType=' + this.conf.state_type
                 + '&ack=' + this.conf.summary_problem_has_been_acknowledged
                 + '&downtime=' + this.conf.summary_in_downtime;

        if (this.conf.type == 'dyngroup')
            sParams += '&object_types=' + this.conf.object_types;

        if (this.conf.perfdata && this.conf.perfdata != '')
            sParams += '&perfdata=' + this.conf.perfdata.replace(/\&quot\;|\&\#145\;/g,'%22');

        // Process the optional gadget_opts param
        if (this.conf.gadget_opts && this.conf.gadget_opts != '')
            sParams += '&opts=' + escapeUrlValues(this.conf.gadget_opts.toString());

        // Append with leading "?" or "&" to build a correct url
        if (this.conf.gadget_url.indexOf('?') == -1)
            sParams = '?' + sParams;
        else
            sParams = '&' + sParams;

        this.detectGadgetType(sParams);
        if (this.gadget_type === 'img') {
            var oGadget = document.createElement('img');
            addZoomHandler(oGadget);
            oGadget.src = this.conf.gadget_url + sParams;

            var alt = this.conf.type + '-' + this.conf.name;
            if (this.conf.type == 'service')
                alt += '-'+this.conf.service_description;
            oGadget.alt = alt;
        } else {
            var oGadget = document.createElement('div');
            oGadget.innerHTML = this.requestGadget(sParams);
        }
        oGadget.setAttribute('id', this.conf.object_id + '-icon');

        var oIconDiv = document.createElement('div');
        oIconDiv.setAttribute('id', this.conf.object_id + '-icondiv');
        oIconDiv.setAttribute('class', 'icon');
        oIconDiv.setAttribute('className', 'icon');
        oIconDiv.style.position = 'absolute';
        oIconDiv.style.top      = this.parseCoord(this.conf.y, 'y') + 'px';
        oIconDiv.style.left     = this.parseCoord(this.conf.x, 'x') + 'px';
        oIconDiv.style.zIndex   = this.conf.z;

        // Parse link only when set
        if(this.conf.url && this.conf.url !== '') {
            var oIconLink = document.createElement('a');
            oIconLink.href = this.conf.url;
            oIconLink.target = this.conf.url_target;
            oIconLink.appendChild(oGadget);
            oGadget = null;

            oIconDiv.appendChild(oIconLink);
            oIconLink = null;
        } else {
            oIconDiv.appendChild(oGadget);
            oGadget = null;
        }

        return oIconDiv;
    }
});
