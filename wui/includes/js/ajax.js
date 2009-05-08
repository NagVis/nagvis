/*****************************************************************************
 *
 * ajax.js - Functions for handling WUI Ajax requests
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

function getServices(backend_id, type, host_name, field, selected) {
	// Only update services when host is not empty and not "Manual input..."
	if(host_name != '' && host_name != lang['manualInput']) {
		var oOpt = Object();
		oOpt.field = field;
		oOpt.selected = selected;
		oOpt.type = type;
		
		printObjects(getSyncRequest('ajax_handler.php?action=getServices&backend_id='+backend_id+'&host_name='+host_name), oOpt);
	}
}

function getObjects(backend_id, type, field, selected) {
	var oOpt = Object();
	oOpt.field = field;
	oOpt.selected = selected;
	oOpt.type = type;
	
	printObjects(getSyncRequest('ajax_handler.php?action=getObjects&backend_id='+backend_id+'&type='+type), oOpt);
}