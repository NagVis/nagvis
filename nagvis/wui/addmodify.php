<?
#################################################################################
#       Nagvis Web Configurator 						#
#	GPL License								#
#										#
#										#
#	Web interface to configure Nagvis maps.					#
#										#
#	Drag & drop, Tooltip and shapes javascript code taken from 		#
#	http://www.walterzorn.com   						#
#										#
#################################################################################
?>

<head>
<link rel="stylesheet" type="text/css" href="./css/addmodify.css" />
<TITLE>Nagvis configtool</TITLE>
</head>

<?
include("../includes/classes/class.NagVisConfig.php");
include("./classes.wui.php");

$CONFIG = new nagvisconfig('../etc/config.ini');

# we load the language file
$langfile= new langFile($CONFIG->getValue('paths', 'cfg')."languages/wui_".$CONFIG->getValue('global', 'language').".txt");

# we get the parameters passed in the URL
$myaction = $_GET['action'];    # possible values : add, delete or modify
$mytype = $_GET['type'];	# possible values : host,service,hostgroup,etc..
$myid = $_GET['id'];		# possible values : 1,2,3,...
$mymap = $_GET['map'];		# possible values : demo,routers,...

if(isset($_GET['coords'])) {
	$mycoords = $_GET['coords'];
}
else{
	$mycoords = "";
}

# we check that the mapname is defined and that the file exits on the server
if(!file_exists($CONFIG->getValue('paths', 'mapcfg').$mymap.".cfg")) { exit; }

# we verify that the user is defined
if(isset($_SERVER['PHP_AUTH_USER']))   { $user = $_SERVER['PHP_AUTH_USER'];}
elseif(isset($_SERVER['REMOTE_USER'])) { $user = $_SERVER['REMOTE_USER'];  }
if($user == "") {exit;}

# we verify he's authorized to config the map
$myreadfile = new readFile_wui();
$analyse = $myreadfile->readNagVisCfg($mymap);
$allowed_user = explode(",",trim($analyse[1]['allowed_for_config']));
if((!in_array($user,$allowed_user)) && (!in_array("EVERYONE",$allowed_user))) {  exit; }

# we load the arrays, containing the properties list for each type of object
$type_tab=array("service" => 1, "host" => 2, "hostgroup" => 3, "servicegroup" => 4, "map" => 5, "textbox" => 6, "global" => 7);

$service_prop=array("host_name*","service_description*","x*","y*","line_type","url","iconset"); 
$host_prop=array("host_name*","x*","y*","recognize_services","line_type","url","iconset"); 
$hostgroup_prop=array("hostgroup_name*","x*","y*","recognize_services","line_type","url","iconset"); 
$servicegroup_prop=array("servicegroup_name*","x*","y*","url","line_type","iconset"); 
$map_prop=array("map_name*","x*","y*","url","iconset"); 
$textbox_prop=array("text*","x*","y*","w*"); 
$global_prop=array("allowed_user*","allowed_for_config*","iconset*","map_image*");

$type_tab["service"]=$service_prop;
$type_tab["host"]=$host_prop;
$type_tab["hostgroup"]=$hostgroup_prop;
$type_tab["servicegroup"]=$servicegroup_prop;
$type_tab["map"]=$map_prop;
$type_tab["textbox"]=$textbox_prop;
$type_tab["global"]=$global_prop;

##########################################
# we display the form, made from these properties lists, for the current object type
?>

<table id="mytable">
	<form method="post" action="wui.function.inc.php?myaction=<? print $myaction; ?>" name="addmodify" onsubmit="return check_object();" id="addmodify">
		<input type="hidden" name="type" size="12" value="<? echo $mytype; ?>">
		<input type="hidden" name="id" size="12" value="<? echo $myid; ?>">
		<input type="hidden" name="map" size="12" value="<? echo $mymap; ?>">
		<input type="hidden" name="properties">

<?

foreach($type_tab["$mytype"] as $propname)
{
	print "<tr><td class=\"tdlabel\">";
	# if the property name contains a * we write it in red
	if(substr($propname,strlen($propname)-1) == "*")
	{
		$propname_ok=substr($propname,0,strlen($propname)-1);
		print "<font color=\"red\">".$propname_ok." </font>";
	}
	else
	{
		$propname_ok=$propname;
		print "$propname_ok ";
	}
	print "</td>";
	
	# we treat the special case of iconset, which will display a listbox instead of the normal textbox
	if($propname_ok == "iconset")
	{
		print "<td class=\"tdfield\"><select name=\"$propname\">";
		if(substr($propname,strlen($propname)-1) != "*") print "<option value=\"\"></option>";
		$files=array();
		$valid_format = array(
                        0=>"gif",
                        1=>"png",
                        2=>"bmp",
                        3=>"jpg",
                        4=>"jpeg"
                );
		if ($handle = opendir($CONFIG->getValue('paths', 'icon'))) 
		{
 			while (false !== ($file = readdir($handle))) 
			{
				$file_ext=explode(".",$file);
				//if ($file != "." && $file != ".." && substr($file,strlen($file)-7,7) == "_ok.png" )
				if ($file != "." && $file != ".." && substr($file_ext['0'],strlen($file_ext['0'])-3,3) == "_ok" && in_array($file_ext['1'],$valid_format))
			 	{ $files[]=substr($file,0,strlen($file)-7)."(".$file_ext['1'].")";}				
			}
			
			if ($files) natcasesort($files); 
			foreach ($files as $file) { 
			$file_save=explode("(",$file);
			$save=trim($file_save['0']);
			print "<option value=\"$save\">$file</option>"; }
			
		}
		closedir($handle);
		print "</select></td>\n";
	}
	
	# we treat the special case of map_image, which will display a listbox instead of the normal textbox
	else if($propname_ok == "map_image")
	{
		print "<td class=\"tdfield\"><select name=\"$propname\">";
		$files=array();
		if ($handle = opendir($CONFIG->getValue('paths', 'map'))) 
		{
 			while (false !== ($file = readdir($handle))) 
			{
				if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".png" ) { 
				$files[]=$file;}				
			}
			
			if ($files) natcasesort($files); 
			foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
		}
		closedir($handle);
		print "</select></td>\n";
	}
	
	# we treat the special case of recognize_services, which will display a "yes/no" listbox instead of the normal textbox
	else if($propname_ok == "recognize_services")
	{
		print "<td class=\"tdfield\"><select name=\"$propname\">";
		print "<option value=\"\">".$langfile->get_text("6")."</option>";
		print "<option value=\"0\">".$langfile->get_text("7")."</option>";
		print "</select></td>\n";
	}
	
	# we treat the special case of line_type, which will display a listbox showing the different possible shapes for the line
	else if($propname_ok == "line_type")
	{
		print "<td class=\"tdfield_linetype\"><select name=\"$propname\">";
		print "<option value=\"\"></option>";
		print "<option value=\"0\">------><------</option>";
		print "<option value=\"1\">--------------></option>";
		print "</select></td>\n";
	}
	
	# we treat the special case of map_name, which will display a listbox instead of the normal textbox
	else if($propname_ok == "map_name")
	{
		print "<td class=\"tdfield\"><select name=\"$propname\">";
		$files=array();
		if ($handle = opendir($CONFIG->getValue('paths', 'mapcfg'))) 
		{
 			while (false !== ($file = readdir($handle))) 
			{
				if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".cfg" && substr($file,0,strlen($file)-4) != $mymap ) { $files[]=substr($file,0,strlen($file)-4);}				
			}
			
			if ($files) natcasesort($files); 
			foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
		}
		closedir($handle);
		print "</select></td>\n";
	}
	
	# we display a simple textbox
	else
	{
		print "<td class=\"tdfield\"><input type=\"text\" name=\"$propname\" value=\"\"></td></tr>\n";
	}
	
	# we add this property to the arrau of the object properties
	$properties_list[]=$propname;
}

?>

		<tr height="20px"><td></td></tr>
		<tr><td align="center" colspan="2" id="mycell"><button name="button_submit" type=submit value="submit" id="commit"><? echo $langfile->get_text("8") ?></button></td></tr>
	</form>
</table>


<?
##########################################
# if the action specified in the URL is "modify", we set the different property values to the object values
if($myaction == "modify")
{
	$readfile = new readFile_wui();
	$mapCfg = $readfile->readNagVisCfg($mymap);
	$myval=$myid+1;
	print "<script type=\"text/javascript\" language=\"JavaScript\"><!--\n";
	
	foreach($type_tab["$mytype"] as $propname)
	{
		if(substr($propname,strlen($propname)-1,1) == "*" ) 
		{ 
			$propname_ok=substr($propname,0,strlen($propname)-1);
		}
		else
		{
			$propname_ok=$propname;
		}
		
		
		if(isset($mapCfg[$myval][$propname_ok]))
		{
				if($propname_ok == 'line_type')
				{
					print "document.addmodify.elements['$propname'].value='".substr($mapCfg[$myval][$propname_ok],strlen($mapCfg[$myval][$propname_ok])-1,1)."';\n";
				}
				else
				{
					print "document.addmodify.elements['$propname'].value='".$mapCfg[$myval][$propname_ok]."';\n";
				}
		}

	}
	if($mycoords != "")
	{
		$val_coords=explode(',',$mycoords);
		if ($mytype == "textbox")
		{
			$objwidth=$val_coords[2]-$val_coords[0];
			print "document.addmodify.elements['x*'].value='".$val_coords[0]."';\n";
			print "document.addmodify.elements['y*'].value='".$val_coords[1]."';\n";
			print "document.addmodify.elements['w*'].value='".$objwidth."';\n";
		}
		else
		{
			print "document.addmodify.elements['x*'].value='".$val_coords[0].",".$val_coords[2]."';\n";
			print "document.addmodify.elements['y*'].value='".$val_coords[1].",".$val_coords[3]."';\n";
		}		
	
	}
	
	print "//--></script>\n";	
}
##########################################
# if the action specified in the URL is "add", we set the object coordinates (that we retrieve from the mycoords parameter)
else if($myaction == "add")
{
	if($mycoords != "")
	{
		$val_coords=explode(',',$mycoords);
		print "<script type=\"text/javascript\" language=\"JavaScript\"><!--\n";
		if(count($val_coords)==2)
		{			
			print "document.addmodify.elements['x*'].value='".$val_coords[0]."';\n";
			print "document.addmodify.elements['y*'].value='".$val_coords[1]."';\n";
		}
		else if(count($val_coords)==4)
		{
			if ($mytype == "textbox")
			{
				$objwidth=$val_coords[2]-$val_coords[0];
				print "document.addmodify.elements['x*'].value='".$val_coords[0]."';\n";
				print "document.addmodify.elements['y*'].value='".$val_coords[1]."';\n";
				print "document.addmodify.elements['w*'].value='".$objwidth."';\n";
			}
			else
			{
				print "document.addmodify.elements['x*'].value='".$val_coords[0].",".$val_coords[2]."';\n";
				print "document.addmodify.elements['y*'].value='".$val_coords[1].",".$val_coords[3]."';\n";
			}		
		}
		print "//--></script>\n";
	}
}

?>

<script type="text/javascript" language="JavaScript"><!--

// we save the current username, that we'll use in javascript functions to make sure the user doesn't ban himself
var user='<?echo $user?>';

// function that checks the object is valid : all the properties marked with a * (required) have a value
// if the object is valid it writes the list of its properties/values in an invisible field, which will be passed when the form is submitted
function check_object()
{
	object_name='';
	line_type='';
	iconset='';
	x='';
	y='';
	
	for(i=0;i<document.addmodify.elements.length;i++)
	{
		if(document.addmodify.elements[i].type != 'submit' && document.addmodify.elements[i].type != 'hidden')
		{
		
			if(document.addmodify.elements[i].name.substring(document.addmodify.elements[i].name.length-6,document.addmodify.elements[i].name.length)=='_name*')
			{
				object_name=document.addmodify.elements[i].value;
			}
			if(document.addmodify.elements[i].name == 'iconset')
			{
				iconset=document.addmodify.elements[i].value;
			}
			if(document.addmodify.elements[i].name == 'x*')
			{
				x=document.addmodify.elements[i].value;
			}			
			if(document.addmodify.elements[i].name == 'y*')
			{
				y=document.addmodify.elements[i].value;
			}
			
			if(document.addmodify.elements[i].name == 'allowed_for_config*')
			{
				users_tab=document.addmodify.elements[i].value.split(',');
				suicide=true;
				for(k=0;k<users_tab.length;k++)
				{
					if ( (users_tab[k]=='EVERYONE') || (users_tab[k]==user) ) { suicide=false; }
				}
				if(suicide)
				{
					mess="<? echo $langfile->get_text("50"); ?>";
					alert(mess);
					document.addmodify.properties.value='';
					document.addmodify.elements[i].focus();
					return false;
				}
			}		

			if(document.addmodify.elements[i].value != '')
			{
				if(document.addmodify.elements[i].name.charAt(document.addmodify.elements[i].name.length-1) == '*')
				{
					document.addmodify.properties.value=document.addmodify.properties.value+'^'+document.addmodify.elements[i].name.substring(0,document.addmodify.elements[i].name.length-1)+'='+document.addmodify.elements[i].value;
				}
				else
				{
					if(document.addmodify.elements[i].name=='line_type')
					{
						line_type=object_name.split(",").length+document.addmodify.elements[i].value;
						document.addmodify.properties.value=document.addmodify.properties.value+'^'+document.addmodify.elements[i].name+'='+line_type;
					}
					else
					{
						document.addmodify.properties.value=document.addmodify.properties.value+'^'+document.addmodify.elements[i].name+'='+document.addmodify.elements[i].value;
					}
					
					
				}
				
			}
			else
			{
				if(document.addmodify.elements[i].name.charAt(document.addmodify.elements[i].name.length-1) == '*')
				{
					mess="<? echo $langfile->get_text("9"); ?>";
					alert(mess);
					document.addmodify.properties.value='';
					document.addmodify.elements[i].focus();
					return false;
				}
			}
		}
	}
	document.addmodify.properties.value=document.addmodify.properties.value.substring(1,document.addmodify.properties.value.length);
	
	// we make some post tests (concerning the line_type and iconset values)
	if(line_type != '')
	{
		// we verify that the current line_type is valid
		valid_list=new Array("10","11","20");
		for(j=0;valid_list[j]!=line_type && j<valid_list.length;j++);
		if(j==valid_list.length)
		{
			mess="<? echo $langfile->get_text("10"); ?>";
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
		
		// we verify we don't have both iconset and line_type defined
		if(iconset != '')
		{
			mess="<? echo $langfile->get_text("11"); ?>";
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
		
		// we verify we have 2 x coordinates and 2 y coordinates
		if(x.split(",").length != 2)
		{
			mess="<? echo $langfile->get_text_replace("12","COORD=X"); ?>";
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
		
		if(y.split(",").length != 2)
		{
			mess="<? echo $langfile->get_text_replace("12","COORD=Y"); ?>";
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
		
	}
	
	if(x.split(",").length > 1)
	{
		if(x.split(",").length != 2)
		{
			mess="<? echo $langfile->get_text_replace("13","COORD=X"); ?>";
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
		else
		{
			if(line_type == '')
			{
				mess="<? echo $langfile->get_text_replace("14","COORD=X"); ?>";
				alert(mess);
				document.addmodify.properties.value='';
				return false;
			}
		}
	
	}
	
	if(y.split(",").length > 1)
	{
		if(y.split(",").length != 2)
		{
			mess="<? echo $langfile->get_text_replace("13","COORD=Y"); ?>";
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
		else
		{
			if(line_type == '')
			{
				mess="<? echo $langfile->get_text_replace("14","COORD=Y"); ?>";
				alert(mess);
				document.addmodify.properties.value='';
				return false;
			}
		}
	
	}
	
	return true;
	
}
	
	
// we resize the window (depending on the number of properties displayed)	
window.resizeTo(410,<? echo count($properties_list) ?>*40+80);
	
	

//--></script>
