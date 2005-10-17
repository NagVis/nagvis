<?
#################################################################################
#       Nagvis Web Configurator 0.6						#
#	GPL License								#
#										#
#	Last modified : 24/08/05						#
#										#
#	Web interface to configure Nagvis maps.					#
#										#
#	Drag & drop, Tooltip and shapes javascript code taken from 		#
#	http://www.walterzorn.com   						#
#										#
#################################################################################

include("./etc/config.inc.php");
include("./includes/classes/class.NagVis.php");
include("./includes/classes/class.readFiles.php");
include("./includes/classes/class.checkState.php");
include("./wui/classes.wui.php");

# we load the language file
$langfile= new langFile($cfgPath."languages/wui_".$Language.".txt");

############################################################################################################
# SOME JAVASCRIPTS FUNCTIONS WE WILL NEED
############################################################################################################
?>


<script type="text/javascript" language="JavaScript"><!--

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


// functions used to track the mouse movements, when the user is adding an object. Draw a line a rectangle following the mouse
// when the user has defined enough points we open the "add object" window

function get_click(newtype,nbclicks,action)
{
	coords='';
	action_click=action;
	objtype=newtype;
	
	// we init the number of points coordinates we're going to wait for before we display the add object window
	cpt_clicks=nbclicks;
		
	document.images['background'].style.cursor='crosshair';
	document.body.onclick=get_click_pos;
	document.body.onmousemove=track_mouse;
	window.status="<? echo $langfile->get_text("1"); ?>" + cpt_clicks;
	
}

function track_mouse(e)
{
	
	if(follow_mouse)
	{	
		
		if (!e) var e = window.event;
	
		if (e.pageX || e.pageY)
		{
			posx = e.pageX;
			posy = e.pageY;
		}
		else if (e.clientX || e.clientY)
		{
			posx = e.clientX;
			posy = e.clientY;
		}
		
		myshape.clear();
		
		if(objtype != 'textbox')
		{
			myshape.drawLine(myshapex, myshapey, posx, posy);
		}
		else
		{
			myshape.drawRect(myshapex, myshapey, posx-myshapex, posy-myshapey);
		}
		
		myshape.paint();
	}
	return true;
	
}

function get_click_pos(e)
{
	if(cpt_clicks > 0)
	{
		var posx = 0;
		var posy = 0;
		if (!e) var e = window.event;
	
		if (e.pageX || e.pageY)
		{
			posx = e.pageX;
			posy = e.pageY;
		}
		else if (e.clientX || e.clientY)
		{
			posx = e.clientX;
			posy = e.clientY;
		}
		
		if(cpt_clicks == 2) 
		{
						
			myshape = new jsGraphics("mycanvas");
			myshapex=posx;
			myshapey=posy;
			
			myshape.setColor('#06B606');
			myshape.setStroke(1);
			follow_mouse=true;
			
		}
		
		coords=coords+posx+','+posy+',';
		cpt_clicks=cpt_clicks-1;
	}
	
	if(cpt_clicks > 0)
	{
		window.status="<? echo $langfile->get_text("1"); ?>" + cpt_clicks;
	}
	else if(cpt_clicks == 0)
	{
		if (follow_mouse) myshape.clear();
		coords=coords.substr(0,coords.length-1);
		window.status='';
		document.images['background'].style.cursor='default';
		follow_mouse=false;
		if(action_click=='add')
		{
			link="./wui/addmodify.php?action=add&map="+document.myvalues.formulaire.value+"&type="+objtype+"&coords="+coords;
		}
		else if(action_click=='modify')
		{
			link="./wui/addmodify.php?action=modify&map="+document.myvalues.formulaire.value+"&type="+objtype+"&id="+objid+"&coords="+coords;
		}
		
		fenetre(link);
		cpt_clicks=-1;
	}	
}


// simple function to ask to confirm before we delete an object
function confirm_object_deletion()
{
	confirm_message='<? echo $langfile->get_text("2"); ?>';
	if(confirm(confirm_message)) return true;
	else return false;
	
}

// functions used to open a popup window in different sizes, with or without sidebars
var win = null;
function fenetre(page)

      {
        L=410;
	H=400;
	nom="Nagvis";
	
        posX = (screen.width) ? (screen.width - L)/ 2 : 0;
        posY = (screen.height) ? (screen.height - H)/ 2 : 0;
	options='height='+H+', width='+L+',top='+posY+',left='+posX+',scrollbars=no,resizable=yes';
        win = window.open(page, nom, options);	
      }


function fenetre_big(page)

      {
        L=520;
	H=580;
	nom="Nagvis";
	
        posX = (screen.width) ? (screen.width - L)/ 2 : 0;
        posY = (screen.height) ? (screen.height - H)/ 2 : 0;
	options='height='+H+', width='+L+',top='+posY+',left='+posX+',scrollbars=yes,resizable=yes';
        win = window.open(page, nom, options);
	
      }

function fenetre_management(page)

      {
        L=530;
	H=540;
	nom="Nagvis";
	
        posX = (screen.width) ? (screen.width - L)/ 2 : 0;
        posY = (screen.height) ? (screen.height - H)/ 2 : 0;
	options='height='+H+', width='+L+',top='+posY+',left='+posX+',scrollbars=no,resizable=yes';
        win = window.open(page, nom, options);
	
      }


//--></script>


<?

$nagvis = new NagVis();
$readfile = new readFile();
$rotateUrl = "";

#############################################
# we read ALL the maps definition files, to build the lists of allowed users and map_images. At the end we have s.th like
# demo=root,nagiosadmin^map2=user1
# demo=back1.png^map2=mynetwork.png
#
# These lists will be stored in invisible fields, in the form 'myvalues' in this page.
# The list of allowed_user will be used : 
#		- to list, in the right click menu, only the maps the user is granted access to
#		- to prevent the user to rename or delete a map he's not granted access to
#
# The list of map_images will be used :
#		- to make sure a background image is not in use by another map, before it's deleted
$all_allowed_user="";
$all_map_image="";
$myreadfile = new readFile();
$res="";
$files=array();
if ($handle2 = opendir($cfgFolder)) 
{
	while (false !== ($file = readdir($handle2))) 
	{
		if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".cfg" ) { $files[]=substr($file,0,strlen($file)-4);}				
	}
	
	if ($files) natcasesort($files); 
	foreach ($files as $file) 
	{ 
		$analyse=$myreadfile->readNagVisCfg($file);
		$all_allowed_user=$all_allowed_user."^".$file."=".trim($analyse[1]['allowed_user']);	
		$all_map_image=$all_map_image."^".$file."=".trim($analyse[1]['map_image']);
	}
}
closedir($handle2);
# we remove the first ^
$all_allowed_user=substr($all_allowed_user,1,strlen($all_allowed_user));
$all_map_image=substr($all_map_image,1,strlen($all_map_image));
###############################################


unset($browser);
$browser = $_SERVER['HTTP_USER_AGENT'];

# we retrieve the map parameter passed in the URL, if defined
if(isset($_GET['map'])) {
	$map = $_GET['map'];
}
else {
	$map = "";
}

# we retrieve the autosave parameter passed in the URL, if defined. if defined, the map will be saved after the next object is moved
if(isset($_GET['autosave']))
{
   $just_added = 'true';
}
else
   {
   $just_added = 'false';
}

# we retrieve the current username used to display this page (protected by a .htaccess file)
if(isset($_SERVER['PHP_AUTH_USER'])) {
	$user = $_SERVER['PHP_AUTH_USER'];
}
elseif(isset($_SERVER['REMOTE_USER'])) {
	$user = $_SERVER['REMOTE_USER'];
}
else {
	$nagvis->openSite($rotateUrl);
        $nagvis->messageBox("14", "");
        $nagvis->closeSite();
        $nagvis->printSite();
	exit;
}

# if a map is defined in the URL we load its definition file and retrieve its allowed_user list and map_image parameter
if($map!="")
{
	if(file_exists($cfgFolder.$map.".cfg")) {
		$mapCfg = $readfile->readNagVisCfg($map);
		$allowed_users = explode(",",trim($mapCfg[1]['allowed_user']));
		$map_image_array = explode(",",trim($mapCfg[1]['map_image']));
		$map_image=$map_image_array[0];
	}
}

$nagvis->site[] = '<HTML>';
$nagvis->site[] = '<HEAD>';
$nagvis->site[] = '<TITLE>'.$title.'</TITLE>';
$nagvis->site[] = '<SCRIPT TYPE="text/javascript" SRC="./includes/js/nagvis.js"></SCRIPT>';
$nagvis->site[] = '<SCRIPT TYPE="text/javascript" SRC="./includes/js/overlib.js"></SCRIPT>';
$nagvis->site[] = '<SCRIPT TYPE="text/javascript" SRC="./wui/wz_jsgraphics.js"></SCRIPT>';
$nagvis->site[] = '</HEAD>';
$nagvis->site[] = '<LINK HREF="./includes/css/style.css" REL="stylesheet" TYPE="text/css">';


# if a map name is defined in the URL, we check if :
#	- its definition file exists
#	- its background image exists
#	- the current user is allowed to have acees to it
if($map!="")
{
	if(!file_exists($cfgFolder.$map.".cfg")) {
		$nagvis->openSite($rotateUrl);
		$nagvis->messageBox("2", "MAP~".$map.".cfg");
		$nagvis->closeSite();
		$nagvis->printSite();
		exit;
	}


	elseif(!file_exists($mapFolder.$map_image)) {
		$nagvis->openSite($rotateUrl);
		$nagvis->messageBox("3", "MAPPATH~".$mapFolder.$map_image);
		$nagvis->closeSite();
		$nagvis->printSite();
		exit;
	}

	elseif(!in_array($user,$allowed_users) && isset($allowed_users)) {
		$nagvis->openSite($rotateUrl);
		$nagvis->messageBox("4", "USER~".$user);
		$nagvis->closeSite();
		$nagvis->printSite();
		exit;
	}
}

		
# we load the page background image :
#	- the map_image if a map is defined in the URL
#	- a blank image (size 600x600) if not map is defined
$nagvis->site[] = '<body MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0">';
if ($map!="")
{
	$nagvis->site[] = '<TABLE MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0"><div id="mycanvas" style="position:absolute" MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0";"><IMG SRC="./maps/'.$map_image.'" ID="background" style="cursor:default;border-width:1" MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0" style="border-style:none solid solid none"></div></TABLE>';
}
else
{
	$nagvis->site[] = '<TABLE MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0"><div id="mycanvas" style="position:absolute" MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0";"><IMG SRC="'.$HTMLBase.'/images/pixel.gif" WIDTH="600px" HEIGHT="600px" ID="background" style="cursor:default;border-width:1" MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0" style="border-style:none solid solid none"></div></TABLE>';
}

# we write the beginning of the body with all the includes needed	
$nagvis->site[] = "<script type=\"text/javascript\" src=\"./wui/wz_dragdrop.js\"></script>";
$nagvis->site[] = "<script type=\"text/javascript\" src=\"./wui/jsdomenu.js\"></script>";
$nagvis->site[] = "<script type=\"text/javascript\" src=\"./wui/jsdomenu.inc.js\"></script>";
# CSS file used for the right click menu	
$nagvis->site[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"./wui/css/office_xp/office_xp.css\">";
# we make the background image drawable	
$nagvis->site[] = "<script type=\"text/javascript\">myshape_background = new jsGraphics('mycanvas');</script>";
$nagvis->site[] = "<script type=\"text/javascript\">myshape_background.setColor('#FF0000');</script>";
$nagvis->site[] = "<script type=\"text/javascript\">myshape_background.setStroke(1);</script>";

##############################################################################	
# we read and display the objects, one by one	
$checkstate = new checkState();
$countStates = count($mapCfg)-1;
$arrayPos="2";
	
for($x="1";$x<=$countStates;$x++) 
{
	# we retrieve the coordinates	
	$mapCfg[$arrayPos]['x'] = $mapCfg[$arrayPos]['x'] ;
	$mapCfg[$arrayPos]['y'] = $mapCfg[$arrayPos]['y'] ;
	
	# we treat the case of an object of type "map"	
	if($mapCfg[$arrayPos]['type'] == 'map') 
	{
		if(!isset($mapCfg[$arrayPos]['recognize_services'])) 
		{
			$mapCfg[$arrayPos]['recognize_services'] = 1;
		}
		if(!isset($mapCfg[$arrayPos]['service_description'])) 
		{
			$mapCfg[$arrayPos]['service_description'] = "";
		}
		if(file_exists($cfgPath.$cfgFolder.$mapCfg[$arrayPos]['name'].'.cfg')) 
		{
			$mapCfgState = $readfile->readNagVisCfg($mapCfg[$arrayPos]['name']);
			$countStatesState = count($mapCfgState);
			for($y=2;$y<=$countStatesState;$y++) 
			{
				$stateState = $checkstate->checkStates($mapCfgState[$arrayPos]['type'],$mapCfgState[$arrayPos]['name'],$mapCfg[$arrayPos]['recognize_services'],$mapCfg[$arrayPos]['service_description'],$StatusCgi,$CgiUser);
				$stateAllDefines[] = $stateState['State'];
			}
			$state['Map'] = $mapCfg[$arrayPos]['name'];
			$state['State'] = $checkstate->findStateArray($stateAllDefines);
		}
		else 
		{
			$state['State'] = "error";
		}
	}
	
	# we treat the case of an object of type "textbox"	
	elseif($mapCfg[$arrayPos]['type'] == 'textbox') 
	{
		$TextBox = $checkstate->TextBox($mapCfg[$arrayPos]['x'],$mapCfg[$arrayPos]['y'],$mapCfg[$arrayPos]['w'],$mapCfg[$arrayPos]['text']);
		$nagvis->site[] = $TextBox;			
	}
	
	# we treat the case of an object of another type
	else 
	{
		if(!isset($mapCfg[$arrayPos]['recognize_services'])) 
		{
			$mapCfg[$arrayPos]['recognize_services'] = 0;
		}
		if(!isset($mapCfg[$arrayPos]['service_description'])) 
		{
			$mapCfg[$arrayPos]['service_description'] = "";
		}
			
		// we mark the object in the OK or UP STATE (we don't care of the current object state in this designer)
		if(($mapCfg[$arrayPos]['type'] == 'host') || ($mapCfg[$arrayPos]['type'] == 'hostgroup')) {$state['State'] = 'UP';}
		else {$state['State'] = 'OK';}
		
                $state['Count'] = 0;
	}
	
	# we set the icon representing the object	
	if(isset($mapCfg[$arrayPos]['line_type']) || $mapCfg[$arrayPos]['type']=='textbox')
	{
		$Icon_name = "20x20.gif";
	}
	else
	{
		$Icon_name = $checkstate->fixIcon($state,$mapCfg,$arrayPos,$defaultIcons);
	}
		
	# the coordinates in the definition file representing the center of the object, we compute the coordinates of the left up corner of the iconn to display
	$Icon=$iconHTMLBaseFolder.$Icon_name;
	list($mywidth,$myheight,$type,$attr) = getimagesize($iconBaseFolder.$Icon_name);
	$myposx=$mapCfg[$arrayPos]['x']-($mywidth/2);
	$myposy=$mapCfg[$arrayPos]['y']-($myheight/2);
	
	# we add the icon on the map	
	$nagvis->site[] = "<DIV id=\"box_$x\" STYLE=\"position:absolute; left:".$myposx."px; top:".$myposy."px;\">";
	$nagvis->site[] = "<img border=\"0\" src=\"$Icon\" onmouseover=\"this.T_DELAY=1000;this.T_STICKY=true;this.T_OFFSETX=6;this.T_OFFSETY=6;this.T_WIDTH=200;this.T_FONTCOLOR='#000000';this.T_BORDERCOLOR='#000000';this.T_BGCOLOR='#FFFFFF';this.T_STATIC=true;this.T_TITLE='<b>".strtoupper($mapCfg[$arrayPos]['type'])."</b>';";
		
	# we add all the object's defined properties to the tooltip body
	$tooltip_text="";
	$i=0;
	$properties = array_keys($mapCfg[$arrayPos]);
	while ($i < count($properties))
	{
		if( $mapCfg[$arrayPos][$properties[$i]] != ""  && $properties[$i]!="type" && $properties[$i]!="x" && $properties[$i]!="y")
		{
			$tooltip_text=$tooltip_text.$properties[$i]." : ".$mapCfg[$arrayPos][$properties[$i]]."<br>";
		}
		$i++;
	}
		
	# we add the Edit link in the tooltip
	$val="./wui/addmodify.php?action=modify&map=".$map."&type=".$mapCfg[$arrayPos]['type']."&id=$x";
	$tooltip_text=$tooltip_text."<br><a href=".$val." onclick=\'fenetre(href); return false\'>".$langfile->get_text("3")."</a>";
	$tooltip_text=$tooltip_text."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$val="./wui/wui.function.inc.php?myaction=delete&map=".$map."&type=".$mapCfg[$arrayPos]['type']."&id=$x";		
	$actiona="\'return confirm_object_deletion();return false;\'";
	$tooltip_text=$tooltip_text."<a href=".$val." onClick=".$actiona.">".$langfile->get_text("4")."</a>";
	
	# lines and textboxes have one more link in the tooltip : "size/position"	
	if(isset($mapCfg[$arrayPos]['line_type']) || $mapCfg[$arrayPos]['type']=='textbox')
	{
		$tooltip_text=$tooltip_text."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$actiona="objid=".$x.";get_click(\'".$mapCfg[$arrayPos]['type']."\',2,\'modify\');";
		$tooltip_text=$tooltip_text."<a href=javascript:".$actiona.">".$langfile->get_text("5")."</a>";			
	}
		
	# we finish to define the tooltip
	$nagvis->site[] = "return escape('$tooltip_text');\">";
	$nagvis->site[] = "</DIV>";
		
	# if the current object has its line_type property defined we add a line to the canvas (to add it on the map in the end)
	if(isset($mapCfg[$arrayPos]['line_type']))
	{
		list($pointa_x,$pointb_x) = explode(",", $mapCfg[$arrayPos]['x']);
		list($pointa_y,$pointb_y) = explode(",", $mapCfg[$arrayPos]['y']);
		$nagvis->site[] = "<script type=\"text/javascript\">myshape_background.drawLine($pointa_x,$pointa_y,$pointb_x,$pointb_y);</script>";		
	}
		
	# we add this object to the list of the components which will have to be movable, if it's not a line or a textbox
	if(!isset($mapCfg[$arrayPos]['line_type']) && $mapCfg[$arrayPos]['type'] != 'textbox')
	{
		$movable = $movable."\"box_$x\",";
	}
		
	# we go to the next element	
	$arrayPos++;
}
	
# we print in the HTML page all the code we just computed
$nagvis->printSite();

# we make the objects in the "movable list" movable :) 
if (strlen($movable) != 0)
{
	print "<script type='text/javascript'>\n";
	print "<!--\n";
	print "SET_DHTML(TRANSPARENT,CURSOR_HAND,".substr($movable,0,strlen($movable)-1).");\n";
	print "//-->\n";
	print "</script>\n";
}


############################################################################################################
# BEGINNING OF THE INVISIBLE SIDE BAR		
############################################################################################################
?>
    
<form method="post" action="./wui/wui.function.inc.php?myaction=open" name="open_map">
	<input type="hidden" name="formulaire" value="<? echo $map; ?>">
		<select name="map_choice">
		<?
			# we build the list of .cfg files (without extension) present in the maps directory
			if ($handle = opendir($cfgFolder)) 
			{
				while (false !== ($file = readdir($handle))) 
				{
		  
			       		if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".cfg" ) 
			       		{
		       		  		 print "<option value=\"".substr($file,0,strlen($file)-4)."\">".substr($file,0,strlen($file)-4)."</option>";
					}
				}
			}
			closedir($handle);
		?> 
		</select>
	   <input name="open" type=submit value="Open the map">
</form>
		
<?
##################################
# important form. it makes possible to communicate the coordinates of all the objects to the server 
# Idea : when one drags and drops an object, the wz_dragdrop.js which handles this has been modified to update these hidden fields.
# At any time, the fields are filled like :
#    image : 2,5  (the numbers representing $key, which is the line number in the map .cfg file, counting from 0)
#    valx : 12,165
#    valy : 41,98
# this simple example represents 2 objects : obj1 (defined line 3 in the map.cfg file) x=12 y=41
#                                            obj2 (defined line 6 in the map.cfg file) x=165 y=98
# When the user clicks on the Save buton, these lists are passed to a bash script executed on the server, which will parse them and treat them.
# This is how it works to save the maps :)
#
# the other fields of this form are used to store datas the other pages will use
?>
<form name="myvalues" action="./wui/wui.function.inc.php?myaction=save" method="post">
	<input type="hidden" name="image">
	<input type="hidden" name="formulaire" value="<? echo $map; ?>">
	<input type="hidden" name="valx">
	<input type="hidden" name="valy">
	<input type="hidden" name="autosave" value="<? echo $just_added; ?>">
	<input type="hidden" name="username" value="<? echo $user; ?>">
	<textarea name="menu_labels"></textarea>
	<input type="text" name="allowed_users_by_map" value="<? echo $all_allowed_user ?>">
	<input type="text" name="image_map_by_map" value="<? echo $all_map_image ?>">
	<input name="submit" type=submit value="Save this map">
</form> 

<form name="add_object" action="./wui/wui.function.inc.php?myaction=add_modify" method="post" onsubmit="return check_new_object();">
	<input type="hidden" name="formulaire" value="<? echo $map; ?>">
	<input type="hidden" name="modify_line" value="">		
		<select name="add_type" style="width : 108px">
			<option value="host">host</option>
			<option value="hostgroup">hostgroup</option>
			<option value="service">service</option>
			<option value="servicegroup">servicegroup</option>
			<option value="map">map</option>
			<option value="textbox">textbox</option>
		</select>		
	<input name="add" type=submit value="Add object">		
</form> 


<?
# we load and store in an invisible field the right-click menu items text
$menulabels='';	
for($i=0;$i<=$langfile->nb;$i++)
{
	if(substr($langfile->indexes[$i],0,5) == "menu_")
	{
		$ind=substr($langfile->indexes[$i],5,strlen($langfile->indexes[$i]));
		$menulabels=$menulabels."^".$ind."=".$langfile->values[$i];	
	}		
}
$menulabels=substr($menulabels,1,strlen($menulabels));
print "<script>document.forms['myvalues'].menu_labels.text='".$menulabels."'</script>";	
?>


<script type="text/javascript" language="JavaScript">
  <!--
	// we make the forms invisible
	document.forms['open_map'].style.visibility='hidden';
	document.forms['myvalues'].style.visibility='hidden';
	document.forms['add_object'].style.visibility='hidden';
	
	// we build the right-click menu
	initjsDOMenu();
	
	// we draw the shapes on the background
	myshape_background.paint();
	
	
  //-->
</script>

<script type="text/javascript" src="./wui/wz_tooltip.js"></script>

</body>
</html>
