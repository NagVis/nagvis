/*****************************************************************************
 *
 * frontendMessage.js - Creates a messagebox in NagVis JS frontend
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
 
function frontendMessageHide() {
	if(document.getElementById('messageBoxDiv')) {
		document.body.removeChild(document.getElementById('messageBoxDiv'));
	}
}

function frontendMessage(oMessage) {
	var oContainerDiv;
	var oTable;
	var oRow;
	var oCell;
	var oImg;
	
	oContainerDiv = document.createElement('div');
	oContainerDiv.setAttribute('id', 'messageBoxDiv');
	
	oTable = document.createElement('table');
	oTable.setAttribute('id', 'messageBox');
	oTable.setAttribute('class', oMessage.type);
	oTable.setAttribute('className', oMessage.type);
	oTable.style.height = '100%';
	oTable.style.width = '100%';
	oTable.cellPadding = '0';
	oTable.cellSpacing = '0';
	
	oRow = document.createElement('tr');
	
	oCell = document.createElement('td');
	oCell.setAttribute('class', oMessage.type);
	oCell.setAttribute('className', oMessage.type);
	oCell.colSpan = '3';
	oCell.style.height = '16px';
	
	oRow.appendChild(oCell);
	
	oTable.appendChild(oRow);
	
	oRow = document.createElement('tr');
	oRow.style.height = '32px';
	
	oCell = document.createElement('th');
	oCell.setAttribute('class', oMessage.type);
	oCell.setAttribute('className', oMessage.type);
	oCell.style.width = '60px';
	
	oImg = document.createElement('img');
	oImg.src = htmlBase+'/nagvis/images/internal/msg_'+oMessage.type+'.png';
	
	oCell.appendChild(oImg);
	oRow.appendChild(oCell);
	
	oCell = document.createElement('th');
	oCell.setAttribute('class', oMessage.type);
	oCell.setAttribute('className', oMessage.type);
	oCell.appendChild(document.createTextNode(oMessage.title));
	
	oRow.appendChild(oCell);
	
	oCell = document.createElement('th');
	oCell.setAttribute('class', oMessage.type);
	oCell.setAttribute('className', oMessage.type);
	oCell.style.width = '60px';
	
	oImg = document.createElement('img');
	oImg.src = htmlBase+'/nagvis/images/internal/msg_'+oMessage.type+'.png';
	
	oCell.appendChild(oImg);
	oRow.appendChild(oCell);
	
	oTable.appendChild(oRow);
	
	oRow = document.createElement('tr');
	
	oCell = document.createElement('td');
	oCell.setAttribute('class', oMessage.type);
	oCell.setAttribute('className', oMessage.type);
	oCell.colSpan = '3';
	oCell.style.paddingTop = '16px';
	oCell.appendChild(document.createTextNode(oMessage.message));
	
	oRow.appendChild(oCell);
	
	oTable.appendChild(oRow);
	
	oContainerDiv.appendChild(oTable);
	
	document.body.appendChild(oContainerDiv);
}
