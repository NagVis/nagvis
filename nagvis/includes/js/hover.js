/*****************************************************************************
 *
 * hover.js - Function collection for handling the hover menu in NagVis
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

/**
 * Returns the HTML code of a hover template
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
 
// Is only needed for index/automap atm
function getHoverTemplate(sName) {
	return getSyncRequest(htmlBase+'/nagvis/ajax_handler.php?action=getHoverTemplate&name[]='+sName)[0].code;
}

function replaceHoverTemplateMacros(replaceChild, oObj, sTemplateCode) {
	var oMacros = new Object();
	var oSectionMacros = new Object();
	
	oMacros.obj_type = oObj.conf.type;
	oMacros.last_status_refresh = date(oGeneralProperties.date_format, oObj.lastUpdate/1000);
	
	// Replace language strings
	oMacros.lang_obj_type = oObj.conf.lang_obj_type;
	oMacros.lang_name = oObj.conf.lang_name;
	oMacros.lang_child_name = oObj.conf.lang_child_name;
	oMacros.lang_child_name1 = oObj.conf.lang_child_name1;
	oMacros.lang_last_status_refresh = oObj.conf.lang_last_status_refresh;
	
	// On child service objects in hover menu replace obj_name with 
	// service_description
	if(replaceChild == '1' && oObj.conf.type == 'service') {
		oMacros.obj_name = oObj.conf.service_description;
	} else {
		oMacros.obj_name = oObj.conf.name;
	}
	
	if(oObj.conf.alias && oObj.conf.alias != '') {
		oMacros.obj_alias = oObj.conf.alias;
	} else {
		oMacros.obj_alias = '';
	}
	
	if(oObj.conf.display_name && oObj.conf.display_name != '') {
		oMacros.obj_display_name = oObj.conf.display_name;
	} else {
		oMacros.obj_display_name = '';
	}
	
	oMacros.obj_state = oObj.conf.state;
	oMacros.obj_summary_state = oObj.conf.summary_state;
	
	if(oObj.conf.summary_problem_has_been_acknowledged) {
		oMacros.obj_summary_acknowledged = '(Acknowledged)';
	} else {
		oMacros.obj_summary_acknowledged = '';
	}
	
	if(oObj.conf.problem_has_been_acknowledged) {
		oMacros.obj_acknowledged = '(Acknowledged)';
	} else {
		oMacros.obj_acknowledged = '';
	}
	
	if(oObj.conf.summary_in_downtime) {
		oMacros.obj_summary_in_downtime = '(Downtime)';
	} else {
		oMacros.obj_summary_in_downtime = '';
	}
	
	if(oObj.conf.in_downtime) {
		oMacros.obj_in_downtime = '(Downtime)';
	} else {
		oMacros.obj_in_downtime = '';
	}
	
	if(replaceChild != '1' && oObj.conf.type != 'map') {
		oMacros.obj_backendid = oObj.conf.backend_id;
		
		oMacros.obj_backend_instancename = oObj.conf.backend_instancename;
	} else {
		// Remove the macros in map objects
		oMacros.obj_backendid = '';
		oMacros.obj_backend_instancename = '';
	}
	
	oMacros.obj_output = oObj.conf.output;
	oMacros.obj_summary_output = oObj.conf.summary_output;
	
	// Macros which are only for services and hosts
	if(oObj.conf.type == 'host' || oObj.conf.type == 'service') {
		oMacros.obj_address = oObj.conf.address;
		oMacros.obj_last_check = date(oGeneralProperties.date_format, oObj.conf.last_check);
		oMacros.obj_next_check = date(oGeneralProperties.date_format, oObj.conf.next_check);
		oMacros.obj_state_type = oObj.conf.state_type;
		oMacros.obj_current_check_attempt = oObj.conf.current_check_attempt;
		oMacros.obj_max_check_attempts = oObj.conf.max_check_attempts;
		oMacros.obj_last_state_change = date(oGeneralProperties.date_format, oObj.conf.last_state_change);
		oMacros.obj_last_hard_state_change = date(oGeneralProperties.date_format, oObj.conf.last_hard_state_change);
		oMacros.obj_state_duration = oObj.conf.state_duration;
		oMacros.obj_perfdata = oObj.conf.perfdata;
	}
	
	if(oObj.conf.type == 'service') {
		oMacros.service_description = oObj.conf.service_description;
		oMacros.pnp_hostname = oObj.conf.name.replace(' ','%20');
		oMacros.pnp_service_description = oObj.conf.service_description.replace(' ','%20');
	} else {
		oSectionMacros.service = '<!--\\\sBEGIN\\\sservice\\\s-->.+?<!--\\\sEND\\\sservice\\\s-->';
	}
	
	// Macros which are only for hosts
	if(oObj.conf.type == 'host') {
		oMacros.pnp_hostname = oObj.conf.name.replace(' ','%20');
	} else {
		oSectionMacros.host = '<!--\\\sBEGIN\\\shost\\\s-->.+?<!--\\\sEND\\\shost\\\s-->';
	}
	
	// Macros which are only for servicegroups
	if(oObj.conf.type == 'servicegroup') {
		//$ret .= '\'[lang_child_name1]\': \''.$this->CORE->LANG->getText('hostname');
	} else {
		oSectionMacros.servicegroup = '<!--\\\sBEGIN\\\sservicegroup\\\s-->.+?<!--\\\sEND\\\sservicegroup\\\s-->';
	}
	
	// Macros which are only for servicegroup childs
	if(replaceChild == '1' && oObj.parent_type == 'servicegroup' && oObj.conf.type == 'service') {
		oMacros.obj_name1 = oObj.parent_name;
	} else {
		oSectionMacros.servicegroupChild = '<!--\\\sBEGIN\\\sservicegroup_child\\\s-->.+?<!--\\\sEND\\\sservicegroup_child\\\s-->';
	}
	
	// Replace child section when unwanted
	if(oObj.conf.hover_childs_show != 1 || !oObj.conf.num_members || oObj.conf.num_members == 0) {
		oSectionMacros.childs = '<!--\\\sBEGIN\\\schilds\\\s-->.+?<!--\\\sEND\\\schilds\\\s-->';
	}
	
	// Replace child macros
	if(replaceChild != '1' && oObj.conf.hover_childs_show == 1 && oObj.conf.num_members > 0) {
		var arrChildObjects;
		var mapName = '';
		
		if(typeof(oMapProperties) != 'undefined' && oMapProperties !== null) {
			mapName = oMapProperties.map_name;
		}
		
		if(oObj.members && oObj.members.length > 0) {
			var regex = new RegExp("<!--\\sBEGIN\\sloop_child\\s-->(.+?)<!--\\sEND\\sloop_child\\s-->");
			var results = regex.exec(sTemplateCode);
			if(results != null) {
				var childsHtmlCode = '';
				var rowHtmlCode = results[1];
				
				// Loop all child object until all looped or the child limit is reached
				for(var i = 0; i <= oObj.conf.hover_childs_limit && i < oObj.members.length; i++) {
					if(i < oObj.conf.hover_childs_limit) {
						var oMember = oObj.members[i];
						if(oMember.type != 'textbox' && oMember.type != 'shape') {
							// Childs need to know where they belong
							oMember.parent_type = oObj.type;
							oMember.parent_name = oObj.name;
							
							childsHtmlCode += replaceHoverTemplateMacros('1', oMember, rowHtmlCode);
						}
					} else {
						// Create an end line which shows the number of hidden child items
						var numHiddenMembers = oObj.conf.num_members - oObj.conf.hover_childs_limit;
						
						var oMember = { 'conf': { 'type': 'host', 
						                          'name': '', 
						                          'summary_state': '', 
						                          'summary_output': numHiddenMembers+' more items...', 
						                          '<!--\sBEGIN\sservicegroup_child\s-->.+?<!--\sEND\sservicegroup_child\s-->': ''}};
						
						childsHtmlCode += replaceHoverTemplateMacros('1', oMember, rowHtmlCode);
					}
				}
				
				sTemplateCode = sTemplateCode.replace(regex, childsHtmlCode);
			}
		}
	}
	
	// Loop and replace all unwanted section macros
	for (var key in oSectionMacros) {
		var regex = new RegExp(oSectionMacros[key], 'gm');
		sTemplateCode = sTemplateCode.replace(regex, '');
	}
	
	// Loop and replace all normal macros
	for (var key in oMacros) {
		var regex = new RegExp('\\\['+key+'\\\]', 'g');
		sTemplateCode = sTemplateCode.replace(regex, oMacros[key]);
	}
	
	return sTemplateCode;
}

function displayHoverMenu(sHoverCode, iHoverDelay) {
	// Change cursor to "hand" when displaying hover menu
	document.body.style.cursor='pointer';
	
	// Everything seems ok, display the hover menu
	overlib(sHoverCode, WRAP, VAUTO, DELAY, iHoverDelay*1000);
}

/**
 * Hides/Closes the hover menu 
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function hideHoverMenu() {
	// Change cursor to auto when hiding hover menu
	document.body.style.cursor='auto';
	
	return nd();
}
