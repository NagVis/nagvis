/*****************************************************************************
 *
 * ElementLine.js - This class handles the visualisation of lines
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

var ElementLine = Element.extend({
    line_container: null,

    update: function() {
         new ElementLineControls(this.obj).addTo(this.obj);
    },

    render: function() {
        var container = document.createElement('div');
        container.setAttribute('id', this.obj.conf.object_id+'-linediv');
        this.dom_obj = container;

        // Create line div
        var oLineDiv = document.createElement('div');
        this.line_container = oLineDiv;
        container.appendChild(oLineDiv);
        oLineDiv.setAttribute('id', this.obj.conf.object_id+'-line');
        oLineDiv.style.zIndex = this.obj.conf.z;

        this.renderHoverArea();
        this.renderLine();
    },

    place: function() {
        // FIXME: This should be possible without re-rendering everything
        this.erase();
        this.render();
        this.draw();
    },

    //
    // END OF PUBLIC METHODS
    //

    renderHoverArea: function() {
        // This is only the container for the hover/label elements
        // The real area or labels are added later
        var oLink = document.createElement('a');
        this.dom_obj.appendChild(oLink);
        this.obj.trigger_obj = oLink;
        oLink.setAttribute('id', this.obj.conf.object_id+'-linelink');
        oLink.className = 'linelink';
        oLink.href = this.obj.conf.url;
        oLink.target = this.obj.conf.url_target;

        // Hide if not needed, show if needed
        if (!this.obj.needsLineHoverArea())
            oLink.style.display = 'none';
        else
            oLink.style.display = 'block';
    },

    removeLineHoverArea: function() {
        if (!this.obj.needsLineHoverArea())
            this.obj.trigger_obj.style.display = 'none';
    },

    renderLine: function() {
        var colorFill   = '#FFCC66';
        var colorFill2  = '';

        // Get the fill color depending on the object state
        switch (this.obj.conf.summary_state) {
            case 'UNREACHABLE':
            case 'DOWN':
            case 'CRITICAL':
            case 'WARNING':
            case 'UNKNOWN':
            case 'ERROR':
            case 'UP':
            case 'OK':
            case 'PENDING':
                colorFill = oStates[this.obj.conf.summary_state].color;
            break;
        }

        var setPerfdata = [];
        setPerfdata[0] = Array('dummyPercentIn', 88, '%', 85, 98, 0, 100);
        setPerfdata[1] = Array('dummyPercentOut', 99, '%', 85, 98, 0, 100);
        setPerfdata[2] = Array('dummyActualIn', 88.88, 'mB/s', 850, 980, 0, 1000);
        setPerfdata[3] = Array('dummyActualOut', 99.99, 'mB/s', 850, 980, 0, 1000);

        // Adjust fill color based on perfdata for weathermap lines
        if(this.obj.conf.line_type == 13 || this.obj.conf.line_type == 14 || this.obj.conf.line_type == 15) {
            colorFill  = '#000000';
            colorFill2 = '#000000';

            // Convert perfdata to structured array
            setPerfdata = this.parsePerfdata();

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
               || (this.obj.conf.line_type == 14 && (
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

                    if(this.obj.conf.line_type == 14) {
                        if(isset(setPerfdata[2]) && setPerfdata[2][0] == 'dummyActualIn')
                            msg += " value 3 is \'" + setPerfdata[2][1] + "\'";

                        if(isset(setPerfdata[3]) && setPerfdata[3][0] == 'dummyActualOut')
                            msg += " value 4 is \'" + setPerfdata[3][1] + "\'";
                    }
                }

                this.obj.conf.summary_output += ' (Weathermap Line Error: ' + msg + ')';
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
                    this.perfdataError('First', setPerfdata[0][1], this.obj.conf.name, this.obj.conf.service_description);
                }

                // Get colorFill #2 (out)
                if(setPerfdata[1][2] !== null && setPerfdata[1][2] == '%' && setPerfdata [1][1] !== null) {
                    colorFill2 = this.getColorFill(setPerfdata[1][1]);
                } else {
                    colorFill2 = '#000000';
                    this.perfdataError('Second', setPerfdata[1][1], this.obj.conf.name, this.obj.conf.service_description);
                }
            }
        }

        // Get the border color depending on ack/downtime
        if (this.obj.conf.summary_problem_has_been_acknowledged === 1
            || this.obj.conf.summary_in_downtime === 1
            || this.obj.conf.summary_stale) {
            colorFill = lightenColor(colorFill, 100, 100, 100);
        }

        // Parse the line object
        this.renderNagVisLine(colorFill, colorFill2, setPerfdata);
    },

    renderNagVisLine: function(colorFill, colorFill2, perfdata) {
        var x = this.obj.parseCoords(this.obj.conf.x, 'x');
        var y = this.obj.parseCoords(this.obj.conf.y, 'y');
    
        // Convert all coords to int
        for(var i = 0; i < x.length; i++) {
            x[i] = parseInt(x[i], 10);
            y[i] = parseInt(y[i], 10);
        }
    
        var xStart = x[0];
        var yStart = y[0];
        var xEnd   = x[x.length - 1];
        var yEnd   = y[y.length - 1];
    
        //If no performance data is available, make perfdata defined, so perfdata[0]
        //doesn't trigger an error, but is unset
        if(perfdata == null)
            perfdata = [];
    
        var width = addZoomFactor(this.obj.conf.line_width);
        if(width <= 0)
            width = 1; // minimal width for lines
    
        // If not set below, better output something readable than "null"
        var perfdataA = "N/A";
        var perfdataB = "N/A";
    
        // Lines meeting point position
        // First line label position
        // Second line label position
        var cut    = this.obj.conf.line_cut;
        var cutIn  = this.obj.conf.line_label_pos_in;
        var cutOut = this.obj.conf.line_label_pos_out;

        var yOffset = parseInt(this.obj.conf.line_label_y_offset);
        
        switch (this.obj.conf.line_type) {
            case '10':
                // ---><--- lines
                if(x.length == 2) {
                    var xMid = middle(xStart, xEnd, cut);
                    var yMid = middle(yStart, yEnd, cut);
                } else {
                    var xMid = x[1];
                    var yMid = y[1];
                }
    
                this.renderArrow(1, xStart, yStart, xMid, yMid, width, colorFill);
                this.renderLinkOrLabel(1, xMid, yMid, perfdataA, perfdataB);
                this.renderArrow(2, xEnd, yEnd, xMid, yMid, width, colorFill);
                this.renderLinkOrLabel(2, xMid, yMid, perfdataA, perfdataB);
            break;
            case '11':
                // ---> lines
                var xMid = middle(xStart, xEnd, cut);
                var yMid = middle(yStart, yEnd, cut);
                this.renderArrow(1, xStart, yStart, xEnd, yEnd, width, colorFill);
                this.renderLinkOrLabel(1, xMid, yMid, perfdataA, perfdataB);
            break;
            case '12':
                // --- lines
                var xMid = middle(xStart, xEnd, cut);
                var yMid = middle(yStart, yEnd, cut);
                this.renderSimpleLine(1, xStart, yStart, xEnd, yEnd, width, colorFill);
                this.renderLinkOrLabel(1, xMid, yMid, perfdataA, perfdataB);
            break;
            case '13':
                // FIXME: Clean 13,14,15 cases up to one
                // -%-><-%- lines
                if(x.length == 2) {
                    var xMid = middle(xStart, xEnd, cut);
                    var yMid = middle(yStart, yEnd, cut);
                } else {
                    var xMid = x[1];
                    var yMid = y[1];
                }
                // perfdataA contains the percentage info
                if(isset(perfdata[0]) && isset(perfdata[0][1]) && isset(perfdata[0][2]))
                    perfdataA = perfdata[0][1] + perfdata[0][2];
                this.renderArrow(1, xStart, yStart, xMid, yMid, width, colorFill);
                this.renderLinkOrLabel(1, middle(xStart, xMid, cutIn), middle(yStart, yMid, cutIn), perfdataA, perfdataB);
                if(isset(perfdata[1]) && isset(perfdata[1][1]) && isset(perfdata[1][2]))
                    perfdataA = perfdata[1][1] + perfdata[1][2];
                this.renderArrow(2, xEnd, yEnd, xMid, yMid, width, colorFill2);
                this.renderLinkOrLabel(2, middle(xEnd, xMid, cutOut), middle(yEnd, yMid, cutOut), perfdataA, perfdataB);
            break;
            case '14':
                // -%+BW-><-%+BW- lines
                if(x.length == 2) {
                    var xMid = middle(xStart, xEnd, cut);
                    var yMid = middle(yStart, yEnd, cut);
                } else {
                    var xMid = x[1];
                    var yMid = y[1];
                }
    
                // Take the configured line width into account
                yOffset = yOffset + width;
    
                // perfdataA contains the percentage info
                // perfdataB contains the bandwith info
                if(isset(perfdata[0]) && isset(perfdata[0][1]) && isset(perfdata[0][2]))
                    perfdataA = perfdata[0][1] + perfdata[0][2];
                if(isset(perfdata[2]) && isset(perfdata[2][1]) && isset(perfdata[2][2]))
                    perfdataB = perfdata[2][1] + perfdata[2][2];
                this.renderArrow(1, xStart, yStart, xMid, yMid, width, colorFill);
                this.renderLinkOrLabel(1, middle(xStart, xMid, cutOut), middle(yStart, yMid, cutOut), perfdataA, perfdataB, yOffset);
    
                if(isset(perfdata[1]) && isset(perfdata[1][1]) && isset(perfdata[1][2]))
                    perfdataA = perfdata[1][1] + perfdata[1][2];
                if(isset(perfdata[3]) && isset(perfdata[3][1]) && isset(perfdata[3][2]))
                    perfdataB = perfdata[3][1] + perfdata[3][2];
                // Needs to be num = 3 because renderLinkOrLabel() call above consumes two ids
                this.renderArrow(3, xEnd, yEnd, xMid, yMid, width, colorFill2);
                this.renderLinkOrLabel(3, middle(xEnd, xMid, cutIn), middle(yEnd, yMid, cutIn), perfdataA, perfdataB, yOffset);
            break;
            case '15':
                // -BW-><-BW- lines
                if(x.length == 2) {
                    var xMid = middle(xStart, xEnd, cut);
                    var yMid = middle(yStart, yEnd, cut);
                } else {
                    var xMid = x[1];
                    var yMid = y[1];
                }
    
                // Take the configured line width into account
                yOffset = yOffset + width;
    
                // perfdataA contains the bandwith info
                if(isset(perfdata[2]) && isset(perfdata[2][1]) && isset(perfdata[2][2]))
                    perfdataA = perfdata[2][1] + perfdata[2][2];
                this.renderArrow(1, xStart, yStart, xMid, yMid, width, colorFill);
                this.renderLinkOrLabel(1, middle(xStart, xMid, cutOut), middle(yStart, yMid, cutOut), perfdataA, perfdataB, yOffset);
    
                if(isset(perfdata[3]) && isset(perfdata[3][1]) && isset(perfdata[3][2]))
                    perfdataA = perfdata[3][1] + perfdata[3][2];
                // Needs to be num = 3 because renderLinkOrLabel() call above consumes two ids
                this.renderArrow(3, xEnd, yEnd, xMid, yMid, width, colorFill2);
                this.renderLinkOrLabel(3, middle(xEnd, xMid, cutIn), middle(yEnd, yMid, cutIn), perfdataA, perfdataB, yOffset);
            break;
            default:
                // Unknown
                alert('Error: Unknown line type');
        }
    },

    renderPolygon: function(num, xCoord, yCoord, colorFill) {
        var xMin = min(xCoord);
        var yMin = min(yCoord);
        var xMax = max(xCoord);
        var yMax = max(yCoord);
    
        var oCanvas = document.createElement('canvas');
        if(oCanvas.getContext) {
            oCanvas = document.getElementById(this.obj.conf.object_id+'-canvas'+num);
            if (!oCanvas) {
                oCanvas = document.createElement('canvas');
                oCanvas.setAttribute('id', this.obj.conf.object_id+'-canvas'+num);
                oCanvas.style.position = 'absolute';
                this.line_container.appendChild(oCanvas);
            }
            oCanvas.style.left = xMin+"px";
            oCanvas.style.top = yMin+"px";
            oCanvas.width = Math.round(xMax-xMin);
            oCanvas.height = Math.round(yMax-yMin);
            oCanvas.style.zIndex = this.obj.conf.z;
    
            var ctx = oCanvas.getContext('2d');
            ctx.clearRect(0, 0, oCanvas.width, oCanvas.height);
    
            ctx.fillStyle = colorFill;
            ctx.beginPath();
            ctx.moveTo(xCoord[0]-xMin, yCoord[0]-yMin);
    
            for(var i = 1, len = xCoord.length; i < len; i++)
                ctx.lineTo(xCoord[i]-xMin, yCoord[i]-yMin);
    
            ctx.fill();
        }
    },

    // This function renders an arrow like it is used on NagVis maps
    // It renders following line types: --->
    renderArrow: function(num, x1, y1, x2, y2, w, colorFill) {
        var xCoord = [];
        var yCoord = [];
    
        xCoord[0] = x1 + newX(x2-x1, y2-y1, 0, w);
        xCoord[1] = x2 + newX(x2-x1, y2-y1, -4*w, w);
        xCoord[2] = x2 + newX(x2-x1, y2-y1, -4*w, 2*w);
        xCoord[3] = x2;
        xCoord[4] = x2 + newX(x2-x1, y2-y1, -4*w, -2*w);
        xCoord[5] = x2 + newX(x2-x1, y2-y1, -4*w, -w);
        xCoord[6] = x1 + newX(x2-x1, y2-y1, 0, -w);
    
        yCoord[0] = y1 + newY(x2-x1, y2-y1, 0, w);
        yCoord[1] = y2 + newY(x2-x1, y2-y1, -4*w, w);
        yCoord[2] = y2 + newY(x2-x1, y2-y1, -4*w, 2*w);
        yCoord[3] = y2;
        yCoord[4] = y2 + newY(x2-x1, y2-y1, -4*w, -2*w);
        yCoord[5] = y2 + newY(x2-x1, y2-y1, -4*w, -w);
        yCoord[6] = y1 + newY(x2-x1, y2-y1, 0, -w);
    
        // First create the line itselfs by the calculated coords
        this.renderPolygon(num, xCoord, yCoord, colorFill);
    
        yCoord = null;
        xCoord = null;
    },

    // This function renders simple lines (without arrow)
    renderSimpleLine: function(num, x1, y1, x2, y2, w, colorFill) {
        var xCoord = [];
        var yCoord = [];
    
        xCoord[0] = x1 + newX(x2-x1, y2-y1, 0, w);
        xCoord[1] = x2 + newX(x2-x1, y2-y1, w, w);
        xCoord[2] = x2 + newX(x2-x1, y2-y1, w, -w);
        xCoord[3] = x1 + newX(x2-x1, y2-y1, 0, -w);
    
        yCoord[0] = y1 + newY(x2-x1, y2-y1, 0, w);
        yCoord[1] = y2 + newY(x2-x1, y2-y1, w, w);
        yCoord[2] = y2 + newY(x2-x1, y2-y1, w, -w);
        yCoord[3] = y1 + newY(x2-x1, y2-y1, 0, -w);
    
        // First create the line itselfs by the calculated coords
        this.renderPolygon(objectId, num, xCoord, yCoord, colorFill);
    
        yCoord = null;
        xCoord = null;
    },

    renderLinkOrLabel: function(num, x, y, perfdataA, perfdataB, yOffset) {
        // First try to create the labels (For weathermap lines only atm) and if none
        // should be shown try to create link a link area for the line.
        if (this.obj.conf.line_label_show && this.obj.conf.line_label_show === '1'
            && (this.obj.conf.line_type == 13 || this.obj.conf.line_type == 14
                || this.obj.conf.line_type == 15))
            this.renderLabel(num, x, y, perfdataA, perfdataB, yOffset);
        else if (this.obj.needsLineHoverArea())
            this.renderLinkArea(num, x, y);
    },

    getLabelShift: function(str) {
        if(str && str.length > 0)
            return (str.length / 2) * 9;
        else
            return 10;
    },

    renderLabel: function(num, lx, ly, perfdataA, perfdataB, yOffset) {
        var labelShift = this.getLabelShift(perfdataA);
    
        // Maybe use function to detect the real height in future
        var labelHeight = 21;
    
        if (this.obj.conf.line_type == '13' || this.obj.conf.line_type == '15') {
            this.obj.trigger_obj.appendChild(
                renderNagVisTextbox(this.obj.conf.object_id+'-link'+num,
                                    '#ffffff', '#000000', (lx-labelShift), parseInt(ly - labelHeight / 2),
                                    this.obj.conf.z, 'auto', 'auto', '<b>' + perfdataA + '</b>'));
    
        } else if(this.obj.conf.line_type == '14') {
            this.obj.trigger_obj.appendChild(
                renderNagVisTextbox(this.obj.conf.object_id+'-link'+num,
                                   '#ffffff', '#000000', (lx-labelShift), parseInt(ly - labelHeight - yOffset),
                                   this.obj.conf.z, 'auto', 'auto', '<b>' + perfdataA + '</b>'));
            labelShift = this.getLabelShift(perfdataB);
            this.obj.trigger_obj.appendChild(
                renderNagVisTextbox(this.obj.conf.object_id+'-link'+(num+1),
                                    '#ffffff', '#000000', (lx-labelShift), parseInt(ly + yOffset),
                                    this.obj.conf.z, 'auto', 'auto', '<b>' + perfdataB + '</b>'));
        }
    },

    renderLinkArea: function(num, lx, ly) {
        oImg = document.createElement('img');
        oImg.setAttribute('id', this.obj.conf.object_id+'-link'+num);
        oImg.style.position = 'absolute';
        oImg.style.zIndex = parseInt(this.obj.conf.z)+1;
        oImg.src = oGeneralProperties.path_iconsets+'20x20.gif';
        oImg.style.left = (lx-10)+"px";
        oImg.style.top = (ly-10)+"px";
        oImg.style.width = addZoomFactor(20) + 'px';
        oImg.style.height = addZoomFactor(20) + 'px';

        this.obj.trigger_obj.appendChild(oImg);
    },

    /**
     * This function returns the color to use for this line depending on the
     * given percentage usage and on the configured options for this object
     */
    getColorFill: function(perc) {
        var ranges = this.obj.conf.line_weather_colors.split(',');
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
     * Loops all perfdata sets and searches for labels "in" and "out"
     * with an empty UOM. If found it uses the current value and max value
     * for calculating the percentage usage and also the current usage.
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
     * Transform bits in a perfdata set to a human readable value
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
     * Transform bytes in a perfdata set to a human readable value
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
     * Calculates the percentage usage of a line when the current value
     *  and the max value are given in the perfdata string
     */
    perfdataCalcPerc: function(set) {
        // Check if all needed information are present
        if(set[1] === null || set[6] === null || set[1] == '' || set[6] == '')
            return set;

        // Calculate percentages with 2 decimals and reset other options
        return Array(set[0], Math.round(set[1]*100/set[6]*100)/100, '%', set[3], set[4], 0, 100);
    },

    /**
     * Tells the user about wrong perfdata information
     */
    perfdataError: function(type, value, name1, name2) {
        this.obj.conf.summary_output += ' (Weathermap Line Error: ' + type+' set of performance data ('+value+') for  '+name1+' ['+name2+'] is not a percentage value)';
    },

    /**
     * Split perfdata into mutlidimensional array
     *      Each 1st dimension is a set of perfdata such as 'inUsage=19.34%;85;98')
     *      The 2nd dimension is each set broken apart (label, value, uom, etc.)
     *
     * Inspired by parsePerfdata function by Lars Michelsen which was
     * adapted from PNP process_perfdata.pl.  Thanks to Jörg Linge..
     * The function was originally taken from Nagios::Plugin::Performance
     * Thanks to Gavin Carr and Ton Voon
     *
     * @author      Greg Frater <greg@fraterfactory.com>
     *
     */
    parsePerfdata: function() {
        var parsed = [];
    
        var perfdata = this.obj.conf.perfdata;
        if (!perfdata)
            return [];

        // Clean up perfdata
        perfdata = perfdata.replace('/\s*=\s*/', '=');
    
        // Break perfdata string into array of individual sets
        var re = /([^=]+)=([\d\.\-]+)([\w%]*);?([\d\.\-:~@]+)?;?([\d\.\-:~@]+)?;?([\d\.\-]+)?;?([\d\.\-]+)?\s*/g;
        var perfdataMatches = perfdata.match(re);
    
        // Check for empty perfdata
        if (perfdataMatches == null) {
            frontendMessage({'type': 'WARNING', 'title': 'Data error',
                             'message': 'No performance data found in perfdata string'});
            return;
        }

        // Break perfdata parts into array
        for (var i = 0; i < perfdataMatches.length; i++) {
            // Get parts of perfdata from string
            var tmpSetMatches = perfdataMatches[i].match(/(&#145;)?([\w\s\=\']*)(&#145;)?\=([\d\.\-\+]*)([\w%]*)[\;|\s]?([\d\.\-:~@]+)*[\;|\s]?([\d\.\-:~@]+)*[\;|\s]?([\d\.\-\+]*)[\;|\s]?([\d\.\-\+]*)/);
    
            // Check if we got any perfdata
            if (tmpSetMatches === null) {
                frontendMessage({'type': 'WARNING', 'title': 'Data error',
                                 'message': 'No valid performance data in perfdata string - lines.js (305)'});
                continue;
            }
    
            parsed[i] = [
                tmpSetMatches[2], // label
                tmpSetMatches[4], // value
                tmpSetMatches[5], // UOM
                tmpSetMatches[6], // warn
                tmpSetMatches[7], // crit
                tmpSetMatches[8], // min
                tmpSetMatches[9], // max
            ];
        }
        return parsed;
    }

});

var ElementLineControls = Element.extend({
    render: function() {
        // don't render the controls during normal render calls. We
        // want to keep the number of DOM objects low, so only render
        // them when being unlocked for the first time
    },

    draw: function() {
        // When locked: Don't draw on regular draw calls (only draw when locked/unlocked)
        if (!this.obj.bIsLocked)
            this.base();
    },

    unlock: function() {
        if (!this.dom_obj)
            this._render();
        this._draw();
    },

    lock: function() {
        this.erase();
    },

    place: function() {
        // FIXME: This should be possible without re-rendering everything
        if (!this.obj.bIsLocked) {
            this.erase();
            this._render();
            this.draw();
        }
    },

    //
    // END OF PUBLIC METHODS
    //

    _draw: function() {
        Element.prototype.draw.call(this);
    },

    _render: function() {
        var container = document.createElement('div');
        container.setAttribute('id', this.obj.conf.object_id+'-controls');
        this.dom_obj = container;

        var x = this.obj.parseCoords(this.obj.conf.x, 'x');
        var y = this.obj.parseCoords(this.obj.conf.y, 'y');

        var size = oGeneralProperties['controls_size'];
	var lineEndSize = size;
	if(size < 20)
	    lineEndSize = 20;

        for(var i = 0, l = x.length; i < l; i++) {
	    // Line middle drag coord needs to be smaller
	    if(l > 2 && i == 1) 
		this.renderDragger(i, x[i], y[i], - size / 2, - size / 2, size);
	    else
		this.renderDragger(i, x[i], y[i], - lineEndSize / 2, - lineEndSize / 2, lineEndSize);
        }

        if (this.hasTwoParts())
	    this.renderMidToggle(x.length+2,
                this.obj.getLineMid(this.obj.conf.x, 'x'),
                this.obj.getLineMid(this.obj.conf.y, 'y'),
                20 - size / 2,
                -size / 2 + 5,
                size);
    },

    hasTwoParts: function() {
        return this.obj.conf.view_type === 'line'
               && (this.obj.conf.line_type == 10
                   || this.obj.conf.line_type == 13
                   || this.obj.conf.line_type == 14
                   || this.obj.conf.line_type == 15);
    },

    renderDragger: function (num, objX, objY, offX, offY, size) {
        var ctl = document.createElement('div');
        this.dom_obj.appendChild(ctl);
        ctl.setAttribute('id', this.obj.conf.object_id+'-drag-' + num);
        ctl.className = 'control drag';
	// FIXME: Multilanguage
	ctl.title          = 'Move object';
        ctl.style.zIndex   = parseInt(this.obj.conf.z)+1;
        ctl.style.width    = addZoomFactor(size) + 'px';
        ctl.style.height   = addZoomFactor(size) + 'px';
        ctl.style.left     = (objX + offX) + 'px';
        ctl.style.top      = (objY + offY) + 'px';
        ctl.objOffsetX     = offX;
        ctl.objOffsetY     = offY;

        makeDragable(ctl, this.obj, this.obj.saveObject, this.obj.moveObject);
    },

    // Adds the modify button to the controls including all eventhandlers
    renderMidToggle: function (num, objX, objY, offX, offY, size) {
        var ctl = document.createElement('div');
        this.dom_obj.appendChild(ctl);
        ctl.setAttribute('id', this.obj.conf.object_id+'-togglemid-' + num);
        ctl.className = 'control togglemid';
	// FIXME: Multilanguage
        if (this.obj.bIsLocked)
	    ctl.title = 'Unlock line middle';
        else
	    ctl.title = 'Lock line middle';
        ctl.style.zIndex   = parseInt(this.obj.conf.z)+1;
        ctl.style.width    = addZoomFactor(size) + 'px';
        ctl.style.height   = addZoomFactor(size) + 'px';
        ctl.style.left     = (objX + offX) + 'px';
        ctl.style.top      = (objY + offY) + 'px';
        ctl.objOffsetX     = offX;
        ctl.objOffsetY     = offY;

        ctl.onclick = function(element_obj) {
            return function(event) {
                var event = !event ? window.event : event;

                element_obj.toggleMidLock();
	        contextHide();

                if(event.stopPropagation)
                event.stopPropagation();
                event.cancelBubble = true;
                return false;
            };
        }(this);
        ctl = null;
    },

    /**
     * Toggles the position of the line middle. The mid of the line
     * can either be the 2nd of three line coords or is automaticaly
     * the middle between two line coords.
     */
    toggleMidLock: function() {
        // What is the current state?
        var x = this.obj.conf.x.split(',');
        var y = this.obj.conf.y.split(',')

        if (x.length == 2) {
            // The line has 2 coords configured
            // - Calculate and add the 3rd coord as 2nd
            // - Add a drag control for the 2nd coord
            this.obj.conf.x = [
              x[0],
              middle(this.obj.parseCoords(this.obj.conf.x, 'x', false)[0], this.obj.parseCoords(this.obj.conf.x, 'x', false)[1], this.obj.conf.line_cut),
              x[1],
            ].join(',');
            this.obj.conf.y = [
                y[0],
                middle(this.obj.parseCoords(this.obj.conf.y, 'y', false)[0], this.obj.parseCoords(this.obj.conf.y, 'y', false)[1], this.obj.conf.line_cut),
                y[1],
            ].join(',');
        } else {
            // The line has 3 coords configured
            // - Remove the 2nd coord
            // - Remove the drag control for the 2nd coord
            this.obj.conf.x = [ x[0], x[2] ].join(',');
            this.obj.conf.y = [ y[0], y[2] ].join(',');
        }

        // send to server
        saveObjectAttr(this.obj.conf.object_id, { 'x': this.obj.conf.x, 'y': this.obj.conf.y});

        // redraw the whole object
        this.obj.render();
    }
});
