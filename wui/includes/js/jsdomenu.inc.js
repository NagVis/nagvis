/*###############################################################################
#       Nagvis Web Configurator 						#
#	GPL License								#
#										#
#										#
#	Web interface to configure Nagvis maps.					#
#										#
#	Drag & drop, Tooltip and shapes javascript code taken from 		#
#	http://www.walterzorn.com   						#
#										#
###############################################################################*/

var index_list=new Array();
var value_list=new Array();

var index_list_users=new Array();
var value_list_users=new Array();

// function that returns the text associated with a certain index
function get_label(key) {
	if(langMenu[key]!="") {
		return langMenu[key];
	} else {
		alert('Your language file seem to be damaged: ' + myindex + ' missing');
		return "";
	}

}

//################################################################
// function that creates the menu
function createjsDOMenu() {

  mainMenu = new jsDOMenu(160);
  with (mainMenu) {
    addMenuItem(new menuItem(get_label('save'), "menu_save", "code:document.myvalues.submit.click();","","",""));
    addMenuItem(new menuItem(get_label('restore'), "menu_restore", "code:confirm_restore();","","",""));
    addMenuItem(new menuItem(get_label('properties'), "menu_properties", "code:fenetre('./addmodify.php?action=modify&map='+mapname+'&type=global&id=0');","","",""));
    addMenuItem(new menuItem("-"));
    addMenuItem(new menuItem(get_label('open'), "menu_maps_open", ""));

    addMenuItem(new menuItem(get_label('addObject'), "menu_addobject", "","","",""));
    addMenuItem(new menuItem("-"));
    addMenuItem(new menuItem(get_label('nagVisConfig'), "", "code:fenetre_big('./edit_config.php');"));
    addMenuItem(new menuItem(get_label('manageMaps'), "menu_map_mgmt", "code:fenetre_management('./map_management.php');"));
    addMenuItem(new menuItem(get_label('manageBackends'), "menu_backend_mgmt", "code:fenetre_management('./backend_management.php');"));
  }
  
  submenu_maps = new jsDOMenu(140);
  with (submenu_maps) {
    addMenuItem(new menuItem(get_label('open'), "menu_maps_open", ""));
  }
  
  submenu_addobject = new jsDOMenu(120);
  with (submenu_addobject) {
  	addMenuItem(new menuItem(get_label('icon'), "menu_addobject_icon", ""));
	addMenuItem(new menuItem(get_label('line'), "menu_addobject_line", ""));
  
  }
  
  submenu_addobject_icon = new jsDOMenu(140);
  with (submenu_addobject_icon) {
    addMenuItem(new menuItem(get_label('host'), "", "code:get_click('host',1,'add');"));
    addMenuItem(new menuItem(get_label('service'), "", "code:get_click('service',1,'add');"));
    addMenuItem(new menuItem(get_label('hostgroup'), "", "code:get_click('hostgroup',1,'add');"));
    addMenuItem(new menuItem(get_label('servicegroup'), "", "code:get_click('servicegroup',1,'add');"));
    addMenuItem(new menuItem(get_label('map'), "", "code:get_click('map',1,'add');"));
    addMenuItem(new menuItem(get_label('textbox'), "", "code:get_click('textbox',2,'add');"));
    addMenuItem(new menuItem(get_label('shape'), "", "code:get_click('shape',1,'add');"));
  }
  
  submenu_addobject_line = new jsDOMenu(140);
  with (submenu_addobject_line) {
    addMenuItem(new menuItem(get_label('host'), "", "code:get_click('host',2,'add');"));
    addMenuItem(new menuItem(get_label('service'), "", "code:get_click('service',2,'add');"));
    addMenuItem(new menuItem(get_label('hostgroup'), "", "code:get_click('hostgroup',2,'add');"));
    addMenuItem(new menuItem(get_label('servicegroup'), "", "code:get_click('servicegroup',2,'add');"));
  }

  
  submenu_maps_open = new jsDOMenu(140);
  for(i=0;i<arrMaps.length;i++) {
	myval="link:./index.php?map="+arrMaps[i]+"";
	submenu_maps_open.addMenuItem(new menuItem(arrMaps[i],arrMaps[i],myval,"","",""));
	
	if(!checkUserAllowed(arrMaps[i],mapOptions,username)) {
		submenu_maps_open.items[arrMaps[i]].enabled=false;
		submenu_maps_open.items[arrMaps[i]].className='jsdomenuitem_disabled';
	}
  }
  
  mainMenu.items.menu_maps_open.setSubMenu(submenu_maps_open);
  
  if(mapname != '') {
  	  mainMenu.items.menu_addobject.setSubMenu(submenu_addobject);
	  submenu_addobject.items.menu_addobject_icon.setSubMenu(submenu_addobject_icon);
	  submenu_addobject.items.menu_addobject_line.setSubMenu(submenu_addobject_line);
  }
  
  setPopUpMenu(mainMenu);
  activatePopUpMenuBy(1, 2);
  
  filter = new Array("IMG.background");
  mainMenu.setNoneExceptFilter(filter);
 
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
