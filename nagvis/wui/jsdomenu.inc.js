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

// function that loads the text of the menu items in an array
function load_labels() {


	temp=document.forms['myvalues'].menu_labels.text.split("^");
	
	for(var i=0;i<temp.length;i++)
	{
		temp2=temp[i].split("=");
		index_list.push(temp2[0]);
		value_list.push(temp2[1]);
	}
	
}

// function that returns the text associated with a certain index
function get_label(myindex)
{
	for(var i=0;i<index_list.length && index_list[i]!=myindex;i++);
	if(i<=index_list.length)
	{
		return value_list[i];
	}
	else
	{
		alert('Your language file seem to be damaged : ' + myindex + ' missing');
		return "  ";
	}

}

// function that loads the list of the allowed users for all the maps in an array
function load_allowed_users() {

	temp=document.forms['myvalues'].allowed_users_by_map.value.split("^");
	
	for(var i=0;i<temp.length;i++)
	{
		temp2=temp[i].split("=");
		index_list_users.push(temp2[0]);
		value_list_users.push(temp2[1]);
	}
	
}

// function that says if the current user is allowed to have access to a special map
function is_allowed_user(mymapname)
{
	for(var i=0;index_list_users[i]!=mymapname;i++) {}
	temp=value_list_users[i].split(",");
	for(var j=0;j<temp.length;j++)
	{
		if( (document.forms['myvalues'].username.value==temp[j]) || (temp[j]=="EVERYONE") ) return true;
	}
	return false;
}

//################################################################
// function that creates the menu
function createjsDOMenu() 
{
  load_labels();
  load_allowed_users();

  mainMenu = new jsDOMenu(160);
  with (mainMenu) {
    addMenuItem(new menuItem(get_label('1'), "menu_save", "code:document.myvalues.submit.click();","","",""));
    addMenuItem(new menuItem(get_label('2'), "menu_properties", "code:fenetre('./wui/addmodify.php?action=modify&map='+document.myvalues.formulaire.value+'&type=global&id=0');","","",""));
    addMenuItem(new menuItem("-"));
    addMenuItem(new menuItem(get_label('3'), "menu_maps", ""));

    addMenuItem(new menuItem(get_label('4'), "menu_addobject", "","","",""));
    addMenuItem(new menuItem("-"));
    addMenuItem(new menuItem(get_label('5'), "", "code:fenetre_big('./wui/edit_config.php');"));
    //addMenuItem(new menuItem(get_label('6'), "", "code:alert('will open a nice help webpage, localized in the user language');"));
  }
  
  submenu_maps = new jsDOMenu(140);
  with (submenu_maps) {
    addMenuItem(new menuItem(get_label('3_1'), "menu_maps_open", ""));
    addMenuItem(new menuItem(get_label('3_2'), "menu_maps_create", "code:fenetre_management('./wui/map_management.php');"));
    

  }
  
  submenu_addobject = new jsDOMenu(120);
  with (submenu_addobject) {
  	addMenuItem(new menuItem(get_label('4_1'), "menu_addobject_icon", ""));
	addMenuItem(new menuItem(get_label('4_2'), "menu_addobject_line", ""));
  
  }
  
  submenu_addobject_icon = new jsDOMenu(140);
  with (submenu_addobject_icon) {
    addMenuItem(new menuItem("Host", "", "code:get_click('host',1,'add');"));
    addMenuItem(new menuItem("Service", "", "code:get_click('service',1,'add');"));
    addMenuItem(new menuItem("Hostgroup", "", "code:get_click('hostgroup',1,'add');"));
    addMenuItem(new menuItem("Servicegroup", "", "code:get_click('servicegroup',1,'add');"));
    addMenuItem(new menuItem("Map", "", "code:get_click('map',1,'add');"));
    addMenuItem(new menuItem("Textbox", "", "code:get_click('textbox',2,'add');"));
  }
  
  submenu_addobject_line = new jsDOMenu(140);
  with (submenu_addobject_line) {
    addMenuItem(new menuItem("Host", "", "code:get_click('host',2,'add');"));
    addMenuItem(new menuItem("Service", "", "code:get_click('service',2,'add');"));
    addMenuItem(new menuItem("Hostgroup", "", "code:get_click('hostgroup',2,'add');"));
    addMenuItem(new menuItem("Servicegroup", "", "code:get_click('servicegroup',2,'add');"));
  }

  
  submenu_maps_open = new jsDOMenu(140);
  for(i=0;i<document.open_map.map_choice.length;i++)
  {
	myval="code:document.open_map.map_choice.value='"+document.open_map.map_choice.options[i].value+"';document.open_map.open.click();";
	submenu_maps_open.addMenuItem(new menuItem(document.open_map.map_choice.options[i].value,document.open_map.map_choice.options[i].value,myval,"","",""));
	
	if(is_allowed_user(document.open_map.map_choice.options[i].value)==false)
	{
		namemap=new String(document.open_map.map_choice.options[i].value);
		submenu_maps_open.items[namemap].enabled=false;
		submenu_maps_open.items[namemap].className='jsdomenuitem_disabled';
	}
  }
  
  mainMenu.items.menu_maps.setSubMenu(submenu_maps);
  submenu_maps.items.menu_maps_open.setSubMenu(submenu_maps_open);
  
  if(document.myvalues.formulaire.value!='')
  {
  	  mainMenu.items.menu_addobject.setSubMenu(submenu_addobject);
	  submenu_addobject.items.menu_addobject_icon.setSubMenu(submenu_addobject_icon);
	  submenu_addobject.items.menu_addobject_line.setSubMenu(submenu_addobject_line);
  }
  
  setPopUpMenu(mainMenu);
  activatePopUpMenuBy(1, 2);
  
  filter = new Array("IMG.background");
  mainMenu.setNoneExceptFilter(filter);
 
  if(document.myvalues.formulaire.value=='')
  {
 	mainMenu.items.menu_save.enabled=false;
	mainMenu.items.menu_save.className='jsdomenuitem_disabled';
	mainMenu.items.menu_properties.enabled=false;
	mainMenu.items.menu_properties.className='jsdomenuitem_disabled';
	mainMenu.items.menu_addobject.enabled=false;
	mainMenu.items.menu_addobject.className='jsdomenuitem_disabled';

	
  }
 
}
