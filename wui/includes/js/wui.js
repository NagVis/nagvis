var cpt_clicks = 0;
var coords= '';
var objtype= '';
var follow_mouse=false;
var action_click="";
var myshape = null;
var myshape_background = null;
var myshapex=0;
var myshapey=0;
var objid=0;

// function that says if the current user is allowed to have access to the map
function checkUserAllowed(mapName,allowedUsers,username) {
	for(var i=0;i<allowedUsers.length;i++) {
		if((allowedUsers[i] == username) || (allowedUsers[i] == "EVERYONE") ) {
			return true;
		}
	}
	return false;
}

// functions used to track the mouse movements, when the user is adding an object. Draw a line a rectangle following the mouse
// when the user has defined enough points we open the "add object" window

function get_click(newtype,nbclicks,action) {
	coords='';
	action_click=action;
	objtype=newtype;
	
	// we init the number of points coordinates we're going to wait for before we display the add object window
	cpt_clicks=nbclicks;
		
	document.images['background'].style.cursor='crosshair';
	document.body.onclick=get_click_pos;
	document.body.onmousemove=track_mouse;
	window.status = printLang(lang['clickMapToSetPoints'],'') + cpt_clicks;
}

function printLang(sLang,sReplace) {
	sLang = sLang.replace(/<(\/|)(i|b)>/ig,'');
	
	aReplace = sReplace.split(",")
	for(var i = 0; i < aReplace.length; i++) {
		var aReplaceSplit = aReplace[i].split("~");
		sLang = sLang.replace("["+aReplaceSplit[0]+"]",aReplaceSplit[1]);
	}
	
	return sLang;
}

function track_mouse(e) {
	
	if(follow_mouse) {
		if (!e) var e = window.event;
		
		if (e.pageX || e.pageY) {
			posx = e.pageX;
			posy = e.pageY;
		} else if (e.clientX || e.clientY) {
			posx = e.clientX;
			posy = e.clientY;
		}
		
		myshape.clear();
		
		if(objtype != 'textbox') {
			myshape.drawLine(myshapex, myshapey, posx, posy);
		} else {
			myshape.drawRect(myshapex, myshapey, (posx - myshapex), (posy - myshapey));
		}
		
		myshape.paint();
	}
	return true;
	
}

function get_click_pos(e) {
	if(cpt_clicks > 0) {
		var posx = 0;
		var posy = 0;
		if (!e) var e = window.event;
	
		if (e.pageX || e.pageY) {
			posx = e.pageX;
			posy = e.pageY;
		}
		else if (e.clientX || e.clientY) {
			posx = e.clientX;
			posy = e.clientY;
		}
		
		if(cpt_clicks == 2) {		
			myshape = new jsGraphics("mymap");
			myshapex=posx;
			myshapey=posy;
			
			myshape.setColor('#06B606');
			myshape.setStroke(1);
			follow_mouse=true;
		}
		
		coords=coords+posx+','+posy+',';
		cpt_clicks=cpt_clicks-1;
	}
	
	if(cpt_clicks > 0) {
		window.status = printLang(lang['clickMapToSetPoints'],'') + cpt_clicks;
	}
	else if(cpt_clicks == 0) {
		if (follow_mouse) myshape.clear();
		coords=coords.substr(0,coords.length-1);
		window.status='';
		document.images['background'].style.cursor='default';
		follow_mouse=false;
		if(action_click=='add') {
			link="./addmodify.php?action=add&map="+mapname+"&type="+objtype+"&coords="+coords;
		} else if(action_click=='modify') {
			link="./addmodify.php?action=modify&map="+mapname+"&type="+objtype+"&id="+objid+"&coords="+coords;
		}
		
		fenetre(link);
		cpt_clicks=-1;
	}	
}


// simple function to ask to confirm before we delete an object
function confirm_object_deletion() {
	if(confirm(printLang(lang['confirmDelete'],''))) {
		return true;
	} else {
		return false;
	}
}

// simple function to ask to confirm before we restore a map
function confirm_restore() {
	if(confirm(printLang(lang['confirmRestore'],''))) {
		document.location.href='./wui.function.inc.php?myaction=map_restore&map='+mapname;
	}
	return true;
}

// functions used to open a popup window in different sizes, with or without sidebars
var win = null;
function fenetre(page) {
	L=410;
	H=400;
	nom="Nagvis";
	
	posX = (screen.width) ? (screen.width - L)/ 2 : 0;
	posY = (screen.height) ? (screen.height - H)/ 2 : 0;
	options='height='+H+', width='+L+',top='+posY+',left='+posX+',scrollbars=no,resizable=yes';
	win = window.open(page, nom, options);	
}


function fenetre_big(page) {
	L=530;
	H=580;
	nom="Nagvis";
	
	posX = (screen.width) ? (screen.width - L)/ 2 : 0;
	posY = (screen.height) ? (screen.height - H)/ 2 : 0;
	options='height='+H+', width='+L+',top='+posY+',left='+posX+',scrollbars=yes,resizable=yes';
	win = window.open(page, nom, options);
}

function fenetre_management(page) {
	L=540;
	H=660;
	nom="Nagvis";
	
	posX = (screen.width) ? (screen.width - L)/ 2 : 0;
	posY = (screen.height) ? (screen.height - H)/ 2 : 0;
	options='height='+H+', width='+L+',top='+posY+',left='+posX+',scrollbars=no,resizable=yes';
	win = window.open(page, nom, options);
}