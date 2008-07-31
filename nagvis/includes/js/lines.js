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
function drawNagVisLine(type,x1,y1,x2,y2,state,ack,downtime) {
	var colorFill = '';
	var colorBorder = '#000000';
	
	// FIXME: Make the width configurable
	var width = 3;
	
	// Get the fill color depending on the object state
	switch (state) {
    case 'OK':
    case 'UP':
			colorFill = '#00FF00';
		break;
		case 'WARNING':
			colorFill = '#FFFF00';
		break;
		case 'CRITICAL':
		case 'DOWN':
			colorFill = '#00FFFF';
		break;
		case 'ERROR':
			colorFill = '#0000FF';
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
		
		drawArrow(x1, y1, xMid, yMid, width, colorFill, colorBorder);
		drawArrow(x2, x2, xMid, yMid, width, colorFill, colorBorder);
	} else if(type == 11) {
		drawArrow(x1, y1, x2, y2, width, colorFill, colorBorder);
	}
}

// This function draws an arrow like it is used on NagVis maps
function drawArrow(x1, y1, x2, y2, w, colorFill, colorBorder) {
	var xCoord = Array();
	var yCoord = Array();
	
	var oLine1 = new jsGraphics();
	var oLine1Border = new jsGraphics();
	
	oLine1.setColor(colorFill);
	oLine1Border.setColor(colorBorder);
	
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
	
	oLine1.fillPolygon(xCoord, yCoord);
	oLine1Border.drawPolygon(xCoord, yCoord);
	oLine1.paint();
	oLine1Border.paint();
}

function newX(a, b, x, y) {
	return (Math.cos(Math.atan2(y,x)+Math.atan2(b,a))*Math.sqrt(x*x+y*y));
}

function newY(a, b, x, y) {
	return (Math.sin(Math.atan2(y,x)+Math.atan2(b,a))*Math.sqrt(x*x+y*y));
}

function middle(x1,x2) {
	return (x1+(x2-x1)/2);
}
