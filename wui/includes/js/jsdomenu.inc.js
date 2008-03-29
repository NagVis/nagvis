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
	mainMenu = new jsDOMenu(170);
	with (mainMenu) {
		addMenuItem(new menuItem(get_label('open'), "menu_maps_open", ""));
		addMenuItem(new menuItem(get_label('openInNagVis'), "menu_maps_open_nagvis", ""));
		addMenuItem(new menuItem("-"));
		addMenuItem(new menuItem(get_label('save'), "menu_save", "code:document.myvalues.submit.click();","","",""));
		addMenuItem(new menuItem(get_label('restore'), "menu_restore", "code:confirm_restore();","","",""));
		addMenuItem(new menuItem(get_label('properties'), "menu_properties", "code:open_window('./index.php?page=addmodify&action=modify&map='+mapname+'&type=global&id=0','wui_addmodify');","","",""));
		addMenuItem(new menuItem(get_label('addObject'), "menu_addobject", "","","",""));
		addMenuItem(new menuItem("-"));
		addMenuItem(new menuItem(get_label('nagVisConfig'), "", "code:open_window_management('./index.php?page=edit_config','wui_management');"));
		addMenuItem(new menuItem(get_label('manage'), "menu_management", "","","",""));
	}
	
	submenu_addobject = new jsDOMenu(120);
	with (submenu_addobject) {
		addMenuItem(new menuItem(get_label('icon'), "menu_addobject_icon", ""));
		addMenuItem(new menuItem(get_label('line'), "menu_addobject_line", ""));
		addMenuItem(new menuItem(get_label('special'), "menu_addobject_special", ""));
	}
	
	submenu_addobject_icon = new jsDOMenu(140);
	with (submenu_addobject_icon) {
		addMenuItem(new menuItem(get_label('host'), "", "code:get_click('host',1,'add');"));
		addMenuItem(new menuItem(get_label('service'), "", "code:get_click('service',1,'add');"));
		addMenuItem(new menuItem(get_label('hostgroup'), "", "code:get_click('hostgroup',1,'add');"));
		addMenuItem(new menuItem(get_label('servicegroup'), "", "code:get_click('servicegroup',1,'add');"));
		addMenuItem(new menuItem(get_label('map'), "", "code:get_click('map',1,'add');"));
	}
	
	submenu_addobject_line = new jsDOMenu(140);
	with (submenu_addobject_line) {
		addMenuItem(new menuItem(get_label('host'), "", "code:get_click('host',2,'add');"));
		addMenuItem(new menuItem(get_label('service'), "", "code:get_click('service',2,'add');"));
		addMenuItem(new menuItem(get_label('hostgroup'), "", "code:get_click('hostgroup',2,'add');"));
		addMenuItem(new menuItem(get_label('servicegroup'), "", "code:get_click('servicegroup',2,'add');"));
	}
	
	submenu_addobject_special = new jsDOMenu(140);
	with (submenu_addobject_special) {
		addMenuItem(new menuItem(get_label('textbox'), "", "code:get_click('textbox',2,'add');"));
		addMenuItem(new menuItem(get_label('shape'), "", "code:get_click('shape',1,'add');"));
	}
	
	submenu_management = new jsDOMenu(170);
	with (submenu_management) {
		addMenuItem(new menuItem(get_label('manageMaps'), "menu_map_mgmt", "code:open_window_management('./index.php?page=map_management','wui_management');"));
		addMenuItem(new menuItem(get_label('manageBackgrounds'), "menu_background_mgmt", "code:open_window_management('./index.php?page=background_management','wui_management');"));
		addMenuItem(new menuItem(get_label('manageShapes'), "menu_shape_mgmt", "code:open_window_management('./index.php?page=shape_management','wui_management');"));
		addMenuItem(new menuItem(get_label('manageBackends'), "menu_backend_mgmt", "code:open_window_management('./index.php?page=backend_management','wui_management');"));
	}
	mainMenu.items.menu_management.setSubMenu(submenu_management);
	
	if(mapOptions.length > 15) {
		submenu_maps_open = Array();
		submenu_maps_open_sep = new jsDOMenu(170);
		for(i=0;i<=Math.floor(mapOptions.length/15);i++) {
			newMenuItem = new menuItem((0+15*i)+"-"+(15+15*i), "menu_maps_open_"+i, "")
			submenu_maps_open_sep.addMenuItem(newMenuItem);
			submenu_maps_open[i] = new jsDOMenu(170);
			for(a=(0+15*i);a<(15+15*i);a++) {
				if(a >= mapOptions.length) break;
				submenu_maps_open[i].addMenuItem(new menuItem(mapOptions[a].mapAlias,mapOptions[a].mapAlias,"link:./index.php?map="+mapOptions[a].mapName,"","",""));
				
				if(!checkUserAllowed(getMapPermissions(mapOptions[a].mapName,mapOptions,"allowedForConfig"),username)) {
					submenu_maps_open[i].items[mapOptions[a].mapName].enabled=false;
					submenu_maps_open[i].items[mapOptions[a].mapName].className='jsdomenuitem_disabled';
				}
			}
			
			document.getElementById(newMenuItem.id).setSubMenu(submenu_maps_open[i]);
		}
		mainMenu.items.menu_maps_open.setSubMenu(submenu_maps_open_sep);
		
		// Open in NagVis
		submenu_maps_open_nagvis = Array();
		submenu_maps_open_sep_nagvis = new jsDOMenu(170);
		for(i=0;i<=Math.floor(mapOptions.length/15);i++) {
			newMenuItem = new menuItem((0+15*i)+"-"+(15+15*i), "menu_maps_open_"+i+"_nagvis", "")
			submenu_maps_open_sep_nagvis.addMenuItem(newMenuItem);
			submenu_maps_open_nagvis[i] = new jsDOMenu(170);
			for(a=(0+15*i);a<(15+15*i);a++) {
				if(a >= mapOptions.length) break;
				submenu_maps_open_nagvis[i].addMenuItem(new menuItem(mapOptions[a].mapAlias,mapOptions[a].mapAlias,"link:../nagvis/index.php?map="+mapOptions[a].mapName,"","",""));
				
				if(!checkUserAllowed(getMapPermissions(mapOptions[i].mapName,mapOptions,"allowedUsers"),username)) {
					submenu_maps_open_nagvis[i].items[mapOptions[a].mapName].enabled=false;
					submenu_maps_open_nagvis[i].items[mapOptions[a].mapName].className='jsdomenuitem_disabled';
				}
			}
			
			document.getElementById(newMenuItem.id).setSubMenu(submenu_maps_open_nagvis[i]);
		}
		mainMenu.items.menu_maps_open_nagvis.setSubMenu(submenu_maps_open_sep_nagvis);
	} else {
		submenu_maps_open = new jsDOMenu(170);
		for(i=0;i<mapOptions.length;i++) {
			submenu_maps_open.addMenuItem(new menuItem(mapOptions[i].mapAlias,mapOptions[i].mapAlias,"link:./index.php?map="+mapOptions[i].mapName,"","",""));
			
			if(!checkUserAllowed(getMapPermissions(mapOptions[i].mapName,mapOptions,"allowedUsers"),username)) {
				submenu_maps_open.items[mapOptions[i].mapName].enabled=false;
				submenu_maps_open.items[mapOptions[i].mapName].className='jsdomenuitem_disabled';
			}
		}
		mainMenu.items.menu_maps_open.setSubMenu(submenu_maps_open);
		
		// Open in NagVis
		submenu_maps_open_nagvis = new jsDOMenu(170);
		for(i=0;i<mapOptions.length;i++) {
			submenu_maps_open_nagvis.addMenuItem(new menuItem(mapOptions[i].mapAlias,mapOptions[i].mapAlias,"link:../index.php?map="+mapOptions[i].mapName,"","",""));
			
			if(!checkUserAllowed(getMapPermissions(mapOptions[i].mapName,mapOptions,"allowedUsers"),username)) {
				submenu_maps_open_nagvis.items[mapOptions[i].mapName].enabled=false;
				submenu_maps_open_nagvis.items[mapOptions[i].mapName].className='jsdomenuitem_disabled';
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
	
	filter = new Array("IMG.background");
	mainMenu.setNoneExceptFilter(filter);
	
	setPopUpMenu(mainMenu);
	activatePopUpMenuBy(1, 2);
	
	
	if(mapname == '') {
		mainMenu.items.menu_save.enabled=false;
		mainMenu.items.menu_save.className='jsdomenuitem_disabled';
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