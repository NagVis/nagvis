/*****************************************************************************
 *
 * lines.js - Functions for drawing lines in javascript
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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

// This function is being called by NagVis for drawing the lines
function drawNagVisLine(objId, type, x1, y1, x2, y2, width, state, ack, downtime, bLinkArea) {
	var colorFill = '';
	var colorBorder = '#000000';
	
	// Ensure format
	x1 = parseInt(x1);
	x2 = parseInt(x2);
	y1 = parseInt(y1);
	y2 = parseInt(y2);
	width = parseInt(width);
	
	// Get the fill color depending on the object state
	switch (state) {
    case 'UNREACHABLE':
		case 'DOWN':
		case 'CRITICAL':
		case 'WARNING':
    case 'UNKNOWN':
		case 'ERROR':
    case 'UP':
    case 'OK':
    case 'PENDING':
			colorFill = oStates[state].color;
		break;
		default:
			colorFill = '#FFCC66';
		break;
  }
	
	// Get the border color depending on ack/downtime
	if(ack) {
		colorBorder = '#666666';
	}
	
	if(downtime) {
		colorBorder = '#666666';
	}
	
	if(type == 10) {
		var xMid = middle(x1,x2);
		var yMid = middle(y1,y2);
		
		drawArrow(objId, x1, y1, xMid, yMid, width, colorFill, colorBorder, bLinkArea);
		drawArrow(objId, x2, y2, xMid, yMid, width, colorFill, colorBorder, bLinkArea);
	} else if(type == 11) {
		drawArrow(objId, x1, y1, x2, y2, width, colorFill, colorBorder, bLinkArea);
	}
}

// This function draws an arrow like it is used on NagVis maps
function drawArrow(objId, x1, y1, x2, y2, w, colorFill, colorBorder, bLinkArea) {
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
	
	var oCanvas = document.createElement('canvas');
	if(oCanvas.getContext) {
		var xMin = Math.round(min(xCoord));
		var yMin = Math.round(min(yCoord));
		var xMax = Math.round(max(xCoord));
		var yMax = Math.round(max(yCoord));
		
		var oLineContainer = document.getElementById(objId+'-line');
		
		// Draw the line
		oCanvas.setAttribute('id', objId+'-canvas');
		oCanvas.style.position = 'absolute';
		oCanvas.style.left = xMin+"px";
		oCanvas.style.top = yMin+"px";
		oCanvas.width = Math.round(xMax-xMin);
		oCanvas.height = Math.round(yMax-yMin);
		
		var ctx = oCanvas.getContext('2d');
		
		ctx.fillStyle = colorFill;
		ctx.beginPath();
		ctx.moveTo(xCoord[0]-xMin, yCoord[0]-yMin);
		ctx.lineTo(xCoord[1]-xMin, yCoord[1]-yMin);
		ctx.lineTo(xCoord[2]-xMin, yCoord[2]-yMin);
		ctx.lineTo(xCoord[3]-xMin, yCoord[3]-yMin);
		ctx.lineTo(xCoord[4]-xMin, yCoord[4]-yMin);
		ctx.lineTo(xCoord[5]-xMin, yCoord[5]-yMin);
		ctx.lineTo(xCoord[6]-xMin, yCoord[6]-yMin);
		ctx.fill();
		
		oLineContainer.appendChild(oCanvas);
		ctx = null;
		oCanvas = null;
		oLineContainer = null;
		
	} else {
		// Fallback to old line style
		var oLine = new jsGraphics(objId+'-line');
		oLine.setColor(colorFill);
		oLine.fillPolygon(xCoord, yCoord);
		oLine.paint();
	}
	
	oCanvas = null;
	
	// Now draw the link
	// FIXME: Would be better to have a link allover the line
	// -------------------------------------------------------------------------
	
	if(bLinkArea) {
		var oLinkContainer = document.getElementById(objId+'-linelinkdiv');
		var oImg = document.createElement('img');
		oImg.setAttribute('id', objId+'-link');
		oImg.src = oGeneralProperties.path_htmlimages+'iconsets/20x20.gif';
		oImg.style.position = 'absolute';
		oImg.style.left = (middle(x1, x2)-10)+"px";
		oImg.style.top = (middle(y1, y2)-10)+"px";
		
		oLinkContainer.appendChild(oImg);
		oImg = null;
		oLinkContainer = null;
	}
}

function newX(a, b, x, y) {
	return (Math.cos(Math.atan2(y,x)+Math.atan2(b,a))*Math.sqrt(x*x+y*y));
}

function newY(a, b, x, y) {
	return (Math.sin(Math.atan2(y,x)+Math.atan2(b,a))*Math.sqrt(x*x+y*y));
}

// Calculates the middle between two integers
function middle(x1,x2) {
	return (x1+(x2-x1)/2);
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
