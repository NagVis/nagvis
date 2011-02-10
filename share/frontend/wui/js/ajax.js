/*****************************************************************************
 *
 * ajax.js - Functions for handling WUI Ajax requests
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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

function getObjects(backend_id, type, field, selected, name1) {
	var oOpt = Object();
	oOpt.field = field;
	oOpt.selected = selected;
	oOpt.type = type;

	if(backend_id === lang['manualInput'])
		return true;

	if(type === 'service'
	   && (typeof name1 === 'undefined' || name1 == ''
	       || name1 === lang['manualInput'])) {
		return true;
	}

	if(typeof name1 === 'undefined')
		name1 = ''
	
	printObjects(getSyncRequest(oGeneralProperties.path_server
	                            +'?mod=Map&act=getObjects&backendid='+backend_id
	                            +'&backendid='+backend_id+'&type='+type+'&name1='+name1), oOpt);
}
