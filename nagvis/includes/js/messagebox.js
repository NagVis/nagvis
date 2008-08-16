/*****************************************************************************
 *
 * messagebox.js - Creates a messagebox
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: michael_luebben@web.de)
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
 * Messagebox
 *
 * @param   string   $type
 *				Follow type available:
 *					- note 	 -> Blue box for Information
 *					- warning -> Yellow box for warnings messages
 *					- error	 -> Red box for errors messages
 *					- ok		 -> Green box ok messages
 *
 * @param   string   $title	Title for the Box
 * @param   string   $message	Message
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
function messageBox(type, title, message) {

	var body=document.getElementById("body");
	var div=document.getElementById("overDiv");
	var body=div.parentNode;

	// -------------------- Create overDiv --------------------
	var divOverDiv = document.createElement("div");
		divOverDiv.setAttribute("id","overDiv");
		divOverDiv.setAttribute("position","absolute");
		divOverDiv.setAttribute("visibility","visible");
		divOverDiv.setAttribute("z-index","1000");

	// -------------------- Create shadow --------------------
	var divMessageBoxShadow = document.createElement("div");
		divMessageBoxShadow.setAttribute("id","shadow");

	// -------------------- Create table for the message box --------------------
	var tableMessageBox = document.createElement("table");
		tableMessageBox.setAttribute("class","messageBox");
		tableMessageBox.setAttribute("id",type + "MessageBoxBorder");
		tableMessageBox.setAttribute("border","1");
		tableMessageBox.setAttribute("cellpadding","0");
		tableMessageBox.setAttribute("cellspacing","0");

	var trMessageBox = document.createElement("tr");

	var tdMessageBox = document.createElement("td");
		tdMessageBox.setAttribute("class",type + "MessageBoxBackground");

	// -------------------- Create first table for header --------------------
	var tableMessageBoxHeading = document.createElement("table");
		tableMessageBoxHeading.setAttribute("height","10");
		tableMessageBoxHeading.setAttribute("width","100%");
		tableMessageBoxHeading.setAttribute("cellpadding","0");
		tableMessageBoxHeading.setAttribute("cellspacing","0");

	var trMessageBoxHeading = document.createElement("tr");

	var tdMessageBoxHeading = document.createElement("td");
		tdMessageBoxHeading.setAttribute("class",type + "MessageBoxBackground");

	tdMessageBox.appendChild(tableMessageBoxHeading);
	trMessageBoxHeading.appendChild(tdMessageBoxHeading);
	tableMessageBoxHeading.appendChild(trMessageBoxHeading);

	// -------------------- Create second table for header with title --------------------
	var tableMessageBoxHeadingTitle = document.createElement("table");
		tableMessageBoxHeadingTitle.setAttribute("width","100%");
		tableMessageBoxHeadingTitle.setAttribute("cellpadding","0");
		tableMessageBoxHeadingTitle.setAttribute("cellspacing","0");

	var trMessageBoxHeadingTitle = document.createElement("tr");

	// Create left icon
	var tdMessageBoxHeadingTitleIconLeft = document.createElement("td");
		tdMessageBoxHeadingTitleIconLeft.setAttribute("id",type + "MessageHeader");
		tdMessageBoxHeadingTitleIconLeft.setAttribute("align","center");
		tdMessageBoxHeadingTitleIconLeft.setAttribute("width","60");

	var imgMessageBoxHeadingTitleIconLeft = document.createElement("img");
		imgMessageBoxHeadingTitleIconLeft.setAttribute("src","/nagios/nagvis/nagvis/images/internal/msg_"+type+".png");

	// Create title text
	var tdMessageBoxHeadingTitleText = document.createElement("td");
		tdMessageBoxHeadingTitleText.setAttribute("class","messageHeader");
		tdMessageBoxHeadingTitleText.setAttribute("id",type + "MessageHeader");

	var tdMessageBoxHeadingTitleTextNode=document.createTextNode(title);

	// Create right icon
	var tdMessageBoxHeadingTitleIconRight = document.createElement("td");
		tdMessageBoxHeadingTitleIconRight.setAttribute("id",type + "MessageHeader");
		tdMessageBoxHeadingTitleIconRight.setAttribute("align","center");
		tdMessageBoxHeadingTitleIconRight.setAttribute("width","60");

	var imgMessageBoxHeadingTitleIconRight = document.createElement("img");
		imgMessageBoxHeadingTitleIconRight.setAttribute("src","/nagios/nagvis/nagvis/images/internal/msg_"+type+".png");

	tdMessageBoxHeadingTitleIconRight.appendChild(imgMessageBoxHeadingTitleIconRight);
	trMessageBoxHeadingTitle.appendChild(tdMessageBoxHeadingTitleIconRight);
	tdMessageBoxHeadingTitleText.appendChild(tdMessageBoxHeadingTitleTextNode);
	trMessageBoxHeadingTitle.appendChild(tdMessageBoxHeadingTitleText);
	tdMessageBoxHeadingTitleIconLeft.appendChild(imgMessageBoxHeadingTitleIconLeft);
	trMessageBoxHeadingTitle.appendChild(tdMessageBoxHeadingTitleIconLeft);
	tableMessageBoxHeadingTitle.appendChild(trMessageBoxHeadingTitle);
	tdMessageBox.appendChild(tableMessageBoxHeadingTitle);

	// -------------------- Create Table with message --------------------
	var tableMessageBoxMessage = document.createElement("table");
		tableMessageBoxMessage.setAttribute("width","100%");
		tableMessageBoxMessage.setAttribute("height","78%");
		tableMessageBoxMessage.setAttribute("cellpadding","0");
		tableMessageBoxMessage.setAttribute("cellspacing","0");

	var trMessageBoxMessage = document.createElement("tr");

	var tdMessageBoxMessage = document.createElement("td");
		tdMessageBoxMessage.setAttribute("class",type + "MessageBoxBackground");

	var tdMessageBoxMessageNode=document.createTextNode(message);

	tdMessageBoxMessage.appendChild(tdMessageBoxMessageNode);
	trMessageBoxMessage.appendChild(tdMessageBoxMessage);
	tableMessageBoxMessage.appendChild(trMessageBoxMessage);
	tdMessageBox.appendChild(tableMessageBoxMessage);



	trMessageBox.appendChild(tdMessageBox);
	tableMessageBox.appendChild(trMessageBox);
	divMessageBoxShadow.appendChild(tableMessageBox);
	divOverDiv.appendChild(divMessageBoxShadow);


	// Replace new DIV
	body.replaceChild(divOverDiv,div);
}