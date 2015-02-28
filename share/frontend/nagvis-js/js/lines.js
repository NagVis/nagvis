/*****************************************************************************
 *
 * lines.js - Functions for drawing lines in javascript
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

// Calculates a position between two integers
function middle(x1, x2, cut) {
    return parseInt(x1) + parseInt((x2 - x1) * cut);
}

// Returns the maximum value in an array
function max(arr) {
    var max = arr[0];

    for (var i = 1, len = arr.length; i < len; i++) {
        if (arr[i] > max) {
            max = arr[i];
        }
    }

    return max;
}

// Returns the minimum value in an array
function min(arr) {
    var min = arr[0];

    for (var i = 1, len = arr.length; i < len; i++) {
        if (arr[i] < min) {
            min = arr[i];
        }
    }

    return min;
}

function newX(a, b, x, y) {
    return Math.round(Math.cos(Math.atan2(y,x)+Math.atan2(b,a))*Math.sqrt(x*x+y*y));
}

function newY(a, b, x, y) {
    return Math.round(Math.sin(Math.atan2(y,x)+Math.atan2(b,a))*Math.sqrt(x*x+y*y));
}

// Draws polygon based object. By default it draws lines (arrows and also plain lines)
// FIXME: Make this reload aware
function drawPolygonBasedObject(objectId, num, xCoord, yCoord, z, colorFill, colorBorder) {
    var xMin = min(xCoord);
    var yMin = min(yCoord);
    var xMax = max(xCoord);
    var yMax = max(yCoord);

    // Detect if the browser is able to render canvas objects
    // If so: Use canvas rendering which performs much better
    //        than using the jsGraphics library
    var oCanvas = document.createElement('canvas');
    if(oCanvas.getContext) {
        oCanvas = document.getElementById(objectId+'-canvas'+num);
        if(!oCanvas)
            oCanvas = document.createElement('canvas');
        // Draw the line
        oCanvas.setAttribute('id', objectId+'-canvas'+num);
        oCanvas.style.position = 'absolute';
        oCanvas.style.left = xMin+"px";
        oCanvas.style.top = yMin+"px";
        oCanvas.width = Math.round(xMax-xMin);
        oCanvas.height = Math.round(yMax-yMin);
        oCanvas.style.zIndex = z;

        var ctx = oCanvas.getContext('2d');
        ctx.clearRect(0, 0, oCanvas.width, oCanvas.height);

        ctx.fillStyle = colorFill;
        ctx.beginPath();
        ctx.moveTo(xCoord[0]-xMin, yCoord[0]-yMin);

        // Loop all coords
        for(var i = 1, len = xCoord.length; i < len; i++) {
            ctx.lineTo(xCoord[i]-xMin, yCoord[i]-yMin);
        }

        ctx.fill();
        ctx = null;

        if(!isset(oCanvas.parentNode)) {
            var oLineContainer = document.getElementById(objectId+'-line');
            if(oLineContainer) {
                oLineContainer.appendChild(oCanvas);
                oLineContainer = null;
            }
        }
        oCanvas = null;
    } else {
        var oLine = document.getElementById(objectId+'-line');

        // When redrawing while e.g. moving remove old parts first
        // But only remove the elements of the current line part
        var oContainer;
        if(oLine.hasChildNodes() && oLine.childNodes.length > num - 1) {
            oContainer = oLine.childNodes[num - 1];
            while(oContainer.hasChildNodes() && oContainer.childNodes.length >= 1)
                oContainer.removeChild(oContainer.firstChild);
        } else {
            oContainer = document.createElement('div');
            oLine.appendChild(oContainer);
        }

        oContainer.setAttribute('class', 'jsline');
        oContainer.setAttribute('className', 'jsline');
        
        // Fallback to old line style
        var oL = new jsGraphics(oContainer);
        oL.setColor(colorFill);
        oL.fillPolygon(xCoord, yCoord);
        oL.paint();

        oL         = null;
        oContainer = null;
        oLine      = null;
    }

    oCanvas = null;
}

function drawLabel(objectId, num, lineType, lx, ly, z, perfdataA, perfdataB, yOffset) {
    var oLinkContainer = document.getElementById(objectId+'-linelink');

    labelShift = getLabelShift(perfdataA);

    // Maybe use function to detect the real height in future
    var labelHeight = 21;

    if(lineType == '13' || lineType == '15') {
        if(oLinkContainer)
            drawNagVisTextbox(oLinkContainer, objectId+'-link'+num, 'box', '#ffffff', '#000000', (lx-labelShift), parseInt(ly - labelHeight / 2), z, 'auto', 'auto', '<b>' + perfdataA + '</b>');

    } else if(lineType == '14') {
        drawNagVisTextbox(oLinkContainer, objectId+'-link'+num, 'box', '#ffffff', '#000000', (lx-labelShift), parseInt(ly - labelHeight - yOffset), z, 'auto', 'auto', '<b>' + perfdataA + '</b>');
        labelShift = getLabelShift(perfdataB);
        drawNagVisTextbox(oLinkContainer, objectId+'-link'+(num+1), 'box', '#ffffff', '#000000', (lx-labelShift), parseInt(ly + yOffset), z, 'auto', 'auto', '<b>' + perfdataB + '</b>');
    }

    oLinkContainer = null;
}

function drawLinkArea(objectId, num, lx, ly, z) {
    var oLinkContainer = document.getElementById(objectId+'-linelink');
    if(!oLinkContainer)
        return;

    var oImg = document.getElementById(objectId+'-link'+num);
    if(!oImg)
        oImg = document.createElement('img');
    oImg.setAttribute('id', objectId+'-link'+num);
    oImg.src = oGeneralProperties.path_iconsets+'20x20.gif';
    oImg.style.position = 'absolute';
    oImg.style.left = (lx-10)+"px";
    oImg.style.top = (ly-10)+"px";
    oImg.style.width = addZoomFactor(20) + 'px';
    oImg.style.height = addZoomFactor(20) + 'px';
    oImg.style.zIndex = parseInt(z)+1;

    oLinkContainer.appendChild(oImg);
    oImg = null;

    oLinkContainer = null;
}

function getLabelShift(str) {
    if(str && str.length > 0)
        return (str.length / 2) * 9;
    else
        return 10
}

// This function draws an arrow like it is used on NagVis maps
// It draws following line types: --->
function drawArrow(objectId, num, x1, y1, x2, y2, z, w, colorFill, colorBorder) {
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
    drawPolygonBasedObject(objectId, num, xCoord, yCoord, z, colorFill, colorBorder);

    yCoord = null;
    xCoord = null;
}

// This function draws simple lines (without arrow)
function drawSimpleLine(objectId, num, x1, y1, x2, y2, z, w, colorFill, colorBorder) {
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
    drawPolygonBasedObject(objectId, num, xCoord, yCoord, z, colorFill, colorBorder);

    yCoord = null;
    xCoord = null;
}

// This function is being called by NagVis for drawing the lines
function drawNagVisLine(objectId, lineType, lineCoords, z, width, colorFill, colorFill2, perfdata, colorBorder, bLinkArea, bLabelShow, yOffset) {
    var x    = lineCoords[0];
    var y    = lineCoords[1];
    var cuts = lineCoords[2];

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

    // Handle start/end offsets
    //xStart = xStart + newX(xEnd-xStart, yEnd-yStart, 16, 0);
    //yStart = yStart + newY(xEnd-xStart, yEnd-yStart, 16, 0);
    //xEnd   = xEnd + newX(xEnd-xStart, yEnd-yStart, -16, 0);
    //yEnd   = yEnd + newY(xEnd-xStart, yEnd-yStart, -16, 0);

    width = parseInt(width, 10);

    // If not set below, better output something readable than "null"
    var perfdataA = "N/A";
    var perfdataB = "N/A";

    // Cuts
    // Lines meeting point position
    // First line label position
    // Second line label position
    var cut = cuts[0];
    var cutIn = cuts[1];
    var cutOut = cuts[2];

    switch (lineType) {
        case '10':
            // ---><--- lines
            if(x.length == 2) {
                var xMid = middle(xStart, xEnd, cut);
                var yMid = middle(yStart, yEnd, cut);
            } else {
                var xMid = x[1];
                var yMid = y[1];
            }

            drawArrow(objectId, 1, xStart, yStart, xMid, yMid, z, width, colorFill, colorBorder);
            drawLinkOrLabel(objectId, 1, lineType, xMid, yMid, z, perfdataA, perfdataB, bLinkArea, bLabelShow);
            drawArrow(objectId, 2, xEnd, yEnd, xMid, yMid, z, width, colorFill, colorBorder);
            drawLinkOrLabel(objectId, 2, lineType, xMid, yMid, z, perfdataA, perfdataB, bLinkArea, bLabelShow);
        break;
        case '11':
            // ---> lines
            var xMid = middle(xStart, xEnd, cut);
            var yMid = middle(yStart, yEnd, cut);
            drawArrow(objectId, 1, xStart, yStart, xEnd, yEnd, z, width, colorFill, colorBorder);
            drawLinkOrLabel(objectId, 1, lineType, xMid, yMid, z, perfdataA, perfdataB, bLinkArea, bLabelShow);
        break;
        case '12':
            // --- lines
            var xMid = middle(xStart, xEnd, cut);
            var yMid = middle(yStart, yEnd, cut);
            drawSimpleLine(objectId, 1, xStart, yStart, xEnd, yEnd, z, width, colorFill, colorBorder);
            drawLinkOrLabel(objectId, 1, lineType, xMid, yMid, z, perfdataA, perfdataB, bLinkArea, bLabelShow);
        break;
        case '13':
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
            drawArrow(objectId, 1, xStart, yStart, xMid, yMid, z, width, colorFill, colorBorder);
            drawLinkOrLabel(objectId, 1, lineType, middle(xStart, xMid, cutIn), middle(yStart, yMid, cutIn), z, perfdataA, perfdataB, bLinkArea, bLabelShow);

            if(isset(perfdata[1]) && isset(perfdata[1][1]) && isset(perfdata[1][2]))
                perfdataA = perfdata[1][1] + perfdata[1][2];
            drawArrow(objectId, 2, xEnd, yEnd, xMid, yMid, z, width, colorFill2, colorBorder);
            drawLinkOrLabel(objectId, 2, lineType, middle(xEnd, xMid, cutOut), middle(yEnd, yMid, cutOut), z, perfdataA, perfdataB, bLinkArea, bLabelShow);
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
            drawArrow(objectId, 1, xStart, yStart, xMid, yMid, z, width, colorFill, colorBorder);
            drawLinkOrLabel(objectId, 1, lineType, middle(xStart, xMid, cutOut), middle(yStart, yMid, cutOut), z, perfdataA, perfdataB, bLinkArea, bLabelShow, yOffset);

            if(isset(perfdata[1]) && isset(perfdata[1][1]) && isset(perfdata[1][2]))
                perfdataA = perfdata[1][1] + perfdata[1][2];
            if(isset(perfdata[3]) && isset(perfdata[3][1]) && isset(perfdata[3][2]))
                perfdataB = perfdata[3][1] + perfdata[3][2];
	    // Needs to be num = 3 because drawLinkOrLabel() call above consumes two ids
            drawArrow(objectId, 3, xEnd, yEnd, xMid, yMid, z, width, colorFill2, colorBorder);
            drawLinkOrLabel(objectId, 3, lineType, middle(xEnd, xMid, cutIn), middle(yEnd, yMid, cutIn), z, perfdataA, perfdataB, bLinkArea, bLabelShow, yOffset);
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
            drawArrow(objectId, 1, xStart, yStart, xMid, yMid, z, width, colorFill, colorBorder);
            drawLinkOrLabel(objectId, 1, lineType, middle(xStart, xMid, cutOut), middle(yStart, yMid, cutOut), z, perfdataA, perfdataB, bLinkArea, bLabelShow, yOffset);

            if(isset(perfdata[3]) && isset(perfdata[3][1]) && isset(perfdata[3][2]))
                perfdataA = perfdata[3][1] + perfdata[3][2];
	    // Needs to be num = 3 because drawLinkOrLabel() call above consumes two ids
            drawArrow(objectId, 3, xEnd, yEnd, xMid, yMid, z, width, colorFill2, colorBorder);
            drawLinkOrLabel(objectId, 3, lineType, middle(xEnd, xMid, cutIn), middle(yEnd, yMid, cutIn), z, perfdataA, perfdataB, bLinkArea, bLabelShow, yOffset);
        break;
        default:
            // Unknown
            alert('Error: Unknown line type');
    }
}

function drawLinkOrLabel(objectId, num, lineType, x, y, z, perfdataA, perfdataB, bLinkArea, bLabelShow, yOffset) {
    // First try to create the labels (For weathermap lines only atm) and if none
    // should be shown try to create link a link area for the line.
    if(bLabelShow && (lineType == 13 || lineType == 14 || lineType == 15))
        drawLabel(objectId, num, lineType, x, y, z, perfdataA, perfdataB, yOffset);
    else
        drawLinkArea(objectId, num, x, y, z);
}

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
 * @param       String  raw perfdata like 'inUsage=19.34%;85;98 outUsage=0.89%;85;98 inAbsolut=3362060 outAbsolut=14884975'
 * @return      Multi dimensional array of indexed perfdata
 * @author      Greg Frater <greg@fraterfactory.com>
 *
 */
function splicePerfdata(nagiosPerfdata) {
    var oMsg = {};
    var setMatches = [];

    // Check if we got any perfdata
    if(!nagiosPerfdata || nagiosPerfdata == '')
        return 'empty';
    else {

        // Clean up perfdata
        nagiosPerfdata = nagiosPerfdata.replace('/\s*=\s*/', '=');

        // Break perfdata string into array of individual sets
        var re = /([^=]+)=([\d\.\-]+)([\w%]*);?([\d\.\-:~@]+)?;?([\d\.\-:~@]+)?;?([\d\.\-]+)?;?([\d\.\-]+)?\s*/g;
        var perfdataMatches = nagiosPerfdata.match(re);

        // Check for empty perfdata
        if(perfdataMatches == null)
            frontendMessage({'type': 'WARNING', 'title': 'Data error', 'message': 'No performance data found in perfdata string - lines.js (271)'});
        else {
            // Break perfdata parts into array
            for (var i = 0; i < perfdataMatches.length; i++) {
                var tmpMatches = perfdataMatches[i];
                var tmpSetMatches = [];

                // Get parts of perfdata from string
                tmpSetMatches = tmpMatches.match(/(&#145;)?([\w\s\=\']*)(&#145;)?\=([\d\.\-\+]*)([\w%]*)[\;|\s]?([\d\.\-:~@]+)*[\;|\s]?([\d\.\-:~@]+)*[\;|\s]?([\d\.\-\+]*)[\;|\s]?([\d\.\-\+]*)/);

                // Check if we got any perfdata
                if(tmpSetMatches !== null) {
                    setMatches[i] = new Array(7);
                    // Label
                    setMatches[i][0] = tmpSetMatches[2];
                    // Value
                    setMatches[i][1] = tmpSetMatches[4];
                    // UOM
                    setMatches[i][2] = tmpSetMatches[5];
                    // Warn
                    setMatches[i][3] = tmpSetMatches[6];
                    // Crit
                    setMatches[i][4] = tmpSetMatches[7];
                    // Min
                    setMatches[i][5] = tmpSetMatches[8];
                    // Max
                    setMatches[i][6] = tmpSetMatches[9];
                } else
                    frontendMessage({'type': 'WARNING', 'title': 'Data error', 'message': 'No valid performance data in perfdata string - lines.js (305)'});
            }

            return setMatches;
        }
    }
}
