/*****************************************************************************
 *
 * jsdomenu.inc.js - File for defining the WUI context menu
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
 
// function that returns the text associated with a certain index
function get_label(key) {
	if(langMenu[key] && langMenu[key] != '') {
		return langMenu[key];
	} else {
		alert('Your language file seem to be damaged: Key "' + key + '" is missing');
		return "";
	}

}

//################################################################
// function that creates the menu
function createjsDOMenu() {
	var mainMenu = new jsDOMenu(170);
	with (mainMenu) {
		addMenuItem(new menuItem(get_label('overview'), "menu_overview", "link:../nagvis/index.php", ""));
		addMenuItem(new menuItem("-"));
		addMenuItem(new menuItem(get_label('open'), "menu_maps_open", ""));
		addMenuItem(new menuItem(get_label('openInNagVis'), "menu_maps_open_nagvis", ""));
		addMenuItem(new menuItem("-"));
		addMenuItem(new menuItem(get_label('restore'), "menu_restore", "code:confirm_restore();","","",""));
		
		addMenuItem(new menuItem(get_label('properties'), "menu_properties", "code:popupWindow('"+get_label('properties')+"', getSyncRequest('./ajax_handler.php?action=getFormContents&form=addmodify&do=modify&map='+mapname+'&type=global&id=0', true, false));","","",""));
		addMenuItem(new menuItem(get_label('addObject'), "menu_addobject", "","","",""));
		addMenuItem(new menuItem("-"));
		addMenuItem(new menuItem(get_label('nagVisConfig'), "", "code:popupWindow('"+get_label('nagVisConfig')+"', getSyncRequest('./ajax_handler.php?action=getFormContents&form=editMainCfg', true, false));"));
		addMenuItem(new menuItem(get_label('manage'), "menu_management", "","","",""));
	}
	
	var submenu_addobject = new jsDOMenu(120);
	with (submenu_addobject) {
		addMenuItem(new menuItem(get_label('icon'), "menu_addobject_icon", ""));
		addMenuItem(new menuItem(get_label('line'), "menu_addobject_line", ""));
		addMenuItem(new menuItem(get_label('special'), "menu_addobject_special", ""));
	}
	
	var submenu_addobject_icon = new jsDOMenu(140);
	with (submenu_addobject_icon) {
		addMenuItem(new menuItem(get_label('host'), "", "code:get_click('host',1,'add');"));
		addMenuItem(new menuItem(get_label('service'), "", "code:get_click('service',1,'add');"));
		addMenuItem(new menuItem(get_label('hostgroup'), "", "code:get_click('hostgroup',1,'add');"));
		addMenuItem(new menuItem(get_label('servicegroup'), "", "code:get_click('servicegroup',1,'add');"));
		addMenuItem(new menuItem(get_label('map'), "", "code:get_click('map',1,'add');"));
	}
	
	var submenu_addobject_line = new jsDOMenu(140);
	with (submenu_addobject_line) {
		addMenuItem(new menuItem(get_label('host'), "", "code:get_click('host',2,'add');"));
		addMenuItem(new menuItem(get_label('service'), "", "code:get_click('service',2,'add');"));
		addMenuItem(new menuItem(get_label('hostgroup'), "", "code:get_click('hostgroup',2,'add');"));
		addMenuItem(new menuItem(get_label('servicegroup'), "", "code:get_click('servicegroup',2,'add');"));
	}
	
	var submenu_addobject_special = new jsDOMenu(140);
	with (submenu_addobject_special) {
		addMenuItem(new menuItem(get_label('textbox'), "", "code:get_click('textbox',2,'add');"));
		addMenuItem(new menuItem(get_label('shape'), "", "code:get_click('shape',1,'add');"));
	}
	
	var submenu_management = new jsDOMenu(170);
	with (submenu_management) {
		addMenuItem(new menuItem(get_label('manageMaps'), "menu_map_mgmt", "code:popupWindow('"+get_label('manageMaps')+"', getSyncRequest('./ajax_handler.php?action=getFormContents&form=manageMaps', true, false));"));
		addMenuItem(new menuItem(get_label('manageBackgrounds'), "menu_background_mgmt", "code:popupWindow('"+get_label('manageBackgrounds')+"', getSyncRequest('./ajax_handler.php?action=getFormContents&form=manageBackgrounds', true, false));"));
		addMenuItem(new menuItem(get_label('manageShapes'), "menu_shape_mgmt", "code:popupWindow('"+get_label('manageShapes')+"', getSyncRequest('./ajax_handler.php?action=getFormContents&form=manageShapes', true, false));"));
		addMenuItem(new menuItem(get_label('manageBackends'), "menu_backend_mgmt", "code:popupWindow('"+get_label('manageBackends')+"', getSyncRequest('./ajax_handler.php?action=getFormContents&form=manageBackends', true, false));"));
	}
	
	mainMenu.items.menu_management.setSubMenu(submenu_management);
	
	// Seperate map listing when there are more than 15 maps
	if(mapOptions.length > 15) {
		// Create open in WUI menu
		var submenu_maps_open = [];
		var submenu_maps_open_sep = new jsDOMenu(170);
		
		// Create open in NagVis menu
		var submenu_maps_open_nagvis = [];
		var submenu_maps_open_sep_nagvis = new jsDOMenu(170);
		
		// Loop maps
		var iWuiLinks = 0;
		var iWuiCurSubmenu = 0;
		var iNagVisLinks = 0;
		var iNagVisCurSubmenu = 0;
		var iLinksPerPage = 15
		for(var i = 0, len = mapOptions.length; i < len; i++) {
			iNagVisCurSubmenu = Math.floor(iNagVisLinks / iLinksPerPage);
			iWuiCurSubmenu = Math.floor(iWuiLinks / iLinksPerPage);
			
			// Next WUI seperator needed?
			if(submenu_maps_open[iWuiCurSubmenu] == undefined) {
				// Create a 15 element sized submenu
				var newMenuItem = new menuItem(i+"-"+(i+iLinksPerPage), "menu_maps_open_"+iWuiCurSubmenu, "")
				submenu_maps_open_sep.addMenuItem(newMenuItem);
				
				submenu_maps_open[iWuiCurSubmenu] = new jsDOMenu(170);
				
				// Append it to the list
				document.getElementById(newMenuItem.id).setSubMenu(submenu_maps_open[iWuiCurSubmenu]);
			}
			
			// Next NagVis seperator needed?
			if(submenu_maps_open_nagvis[iNagVisCurSubmenu] == undefined) {
				// Add new seperator menu
				var newMenuItem = new menuItem(i+"-"+(i+iLinksPerPage), "menu_maps_open_"+iNagVisCurSubmenu+"_nagvis", "")
				submenu_maps_open_sep_nagvis.addMenuItem(newMenuItem);
				
				// Create new submenu
				submenu_maps_open_nagvis[iNagVisCurSubmenu] = new jsDOMenu(170);
				
				// Append it to the list
				document.getElementById(newMenuItem.id).setSubMenu(submenu_maps_open_nagvis[iNagVisCurSubmenu]);
			}
			
			// Only add permited objects to the NagVis list
			if(checkUserAllowed(getMapPermissions(mapOptions[i].mapName, mapOptions, "allowedUsers"), username)) {
				submenu_maps_open_nagvis[iNagVisCurSubmenu].addMenuItem(new menuItem(mapOptions[i].mapAlias, mapOptions[i].mapAlias, "link:../nagvis/index.php?map="+mapOptions[i].mapName, "", "", ""));
				iNagVisLinks++;
			}
			
			// Only add permited objects to the WUI list
			if(checkUserAllowed(getMapPermissions(mapOptions[i].mapName,mapOptions, "allowedForConfig"),username)) {
				submenu_maps_open[iWuiCurSubmenu].addMenuItem(new menuItem(mapOptions[i].mapAlias, mapOptions[i].mapAlias, "link:./index.php?map="+mapOptions[i].mapName, "", "", ""));
				iWuiLinks++;
			}
		}
		
		// Append both menus
		mainMenu.items.menu_maps_open.setSubMenu(submenu_maps_open_sep);
		mainMenu.items.menu_maps_open_nagvis.setSubMenu(submenu_maps_open_sep_nagvis);
	} else {
		var submenu_maps_open = new jsDOMenu(170);
		for(var i=0;i<mapOptions.length;i++) {
			
			// Only add permited objects
			if(checkUserAllowed(getMapPermissions(mapOptions[i].mapName, mapOptions, "allowedForConfig"), username)) {
				submenu_maps_open.addMenuItem(new menuItem(mapOptions[i].mapAlias,mapOptions[i].mapAlias,"link:./index.php?map="+mapOptions[i].mapName,"","",""));
			}
		}
		mainMenu.items.menu_maps_open.setSubMenu(submenu_maps_open);
		
		// Open in NagVis
		var submenu_maps_open_nagvis = new jsDOMenu(170);
		for(var i=0;i<mapOptions.length;i++) {
			
			// Only add permited objects
			if(checkUserAllowed(getMapPermissions(mapOptions[i].mapName, mapOptions, "allowedUsers"), username)) {
				submenu_maps_open_nagvis.addMenuItem(new menuItem(mapOptions[i].mapAlias,mapOptions[i].mapAlias,"link:../index.php?map="+mapOptions[i].mapName,"","",""));
			}
		}
		mainMenu.items.menu_maps_open_nagvis.setSubMenu(submenu_maps_open_nagvis);
	}
	
	if(mapname != '') {
		mainMenu.items.menu_addobject.setSubMenu(submenu_addobject);
		submenu_addobject.items.menu_addobject_icon.setSubMenu(submenu_addobject_icon);
		submenu_addobject.items.menu_addobject_line.setSubMenu(submenu_addobject_line);
		submenu_addobject.items.menu_addobject_special.setSubMenu(submenu_addobject_special);
	}
	
	mainMenu.setAllExceptFilter([]);
	
	setPopUpMenu(mainMenu);
	activatePopUpMenuBy(1, 2);
	
	
	if(mapname == '') {
		mainMenu.items.menu_properties.enabled=false;
		mainMenu.items.menu_properties.className='jsdomenuitem_disabled';
		mainMenu.items.menu_addobject.enabled=false;
		mainMenu.items.menu_addobject.className='jsdomenuitem_disabled';
	}
	
	if(backupAvailable != '1') {
		mainMenu.items.menu_restore.enabled=false;
		mainMenu.items.menu_restore.className='jsdomenuitem_disabled';
	}
}