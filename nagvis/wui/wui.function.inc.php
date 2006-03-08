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

# script called when the user submits a form. Depending on the action value, it 
# calls the bash script with the right arguments. It's this bash script which 
# applies the changes on the server. 

include("../includes/classes/class.NagVisConfig.php");
$CONFIG = new nagvisconfig('../etc/config.ini');

############################################
# passes the lists (image, valx and valy) to the bash script which modifies the coordinates in the map cfg file
function savemap() {
	global $CONFIG;
	$mymap=$_POST['formulaire'];
	$lines=$_POST['image'];
	$val_x=$_POST['valx'];
	$val_y=$_POST['valy'];

	exec('./wui.function.inc.bash modify '.$CONFIG->getValue('global', 'autoupdatefreq').' '.$CONFIG->getValue('paths', 'mapcfg').' "'.$mymap.'.cfg" '.$lines.' '.$val_x.' '.$val_y);
	
	return $mymap;
}

############################################
function add_element() {
	global $CONFIG;
	
	$mymap=$_POST['map'];
	$mytype=$_POST['type'];
	$myvalues=$_POST['properties'];
	
	exec('./wui.function.inc.bash add_element '.$CONFIG->getValue('paths', 'mapcfg').' "'.$mymap.'.cfg" '.$mytype.' "'.$myvalues.'" '.$CONFIG->getValue('global', 'autoupdatefreq'));
	
	return $mymap;
}

############################################
function modify_element() {
	global $CONFIG;
	
	$mymap=$_POST['map'];
	$mytype=$_POST['type'];
	$myvalues=$_POST['properties'];
	$myid=$_POST['id'];
	exec('./wui.function.inc.bash modify_element '.$CONFIG->getValue('paths', 'mapcfg').' "'.$mymap.'.cfg" '.$myid.' '.$mytype.' "'.$myvalues.'" '.$CONFIG->getValue('global', 'autoupdatefreq'));
	
	return $mymap;
	
}

############################################
function delete_element() {
	global $CONFIG;

	$mymap=$_GET['map'];
	$myid=$_GET['id'];
	
	exec('./wui.function.inc.bash delete_element '.$CONFIG->getValue('paths', 'mapcfg').' "'.$mymap.'.cfg" '.$myid.' '.$CONFIG->getValue('global', 'autoupdatefreq'));
	
	return $mymap;
	
}


############################################
function create_map() {
	global $CONFIG;

	$mymap=$_POST['map_name'];
	$myallow=$_POST['allowed_users'];
	$myicon=$_POST['map_iconset'];
	$myimage=$_POST['map_image'];
	$myallowconfig=$_POST['allowed_for_config'];
	
	exec('./wui.function.inc.bash mgt_map_create '.$CONFIG->getValue('paths', 'mapcfg').' "'.$mymap.'.cfg" "'.$myallow.'" "'.$myicon.'" "'.$myimage.'" "'.$myallowconfig.'" '.$CONFIG->getValue('global', 'autoupdatefreq'));
	
	return $mymap;
}


############################################
function rename_map() {
	global $CONFIG;

	$mymap_name=$_POST['map_name'];
	$mymap_new_name=$_POST['map_new_name'];
	$mymap=$_POST['map'];
	
	exec('./wui.function.inc.bash mgt_map_rename'.' "'.$CONFIG->getValue('paths', 'mapcfg').'" "'.$mymap_name.'" "'.$mymap_new_name.'"');
	
	if($mymap_name==$mymap)
	{
		return $mymap_new_name;
	}
	else
	{
		return $mymap;
	}
}


############################################
function delete_map() {
	global $CONFIG;

	$mymap_name=$_POST['map_name'];
	$mymap=$_POST['map'];

	exec('./wui.function.inc.bash mgt_map_delete '.$CONFIG->getValue('paths', 'mapcfg').' "'.$mymap_name.'.cfg"');
	
	if($mymap_name==$mymap)
	{
		return "";
	}
	else
	{
		return $mymap;
	}
}


############################################
function delete_image() {
	global $CONFIG;
	
	$mymap_image=$_POST['map_image'];
	
	exec('./wui.function.inc.bash mgt_image_delete "'.$CONFIG->getValue('paths', 'map').$mymap_image.'"');
	
}

############################################
function restore_map() {
	global $CONFIG;
	
	$mymap=$_GET['map'];
	
	exec('./wui.function.inc.bash map_restore '.$CONFIG->getValue('paths', 'mapcfg').' "'.$mymap.'.cfg" '.$CONFIG->getValue('global', 'autoupdatefreq'));
	return $mymap;
}

############################################
# MAIN SCRIPT
############################################


$myaction = $_GET['myaction'];

if($myaction == "save")
{
	# save the coordinates on the server
	$mymap = savemap();
	# display the same map again
	print "<script>document.location.href='../config.php?map=$mymap';</script>\n";
}
else if($myaction == "open")
{
	# we retrieve the map chosen by the user and open it
	$mymap = $_POST['map_choice'];
	print "<script>document.location.href='../config.php?map=$mymap';</script>\n";
}

else if($myaction == "modify")
{
		# we modify the object in the cfg file and display the map again
		$mymap= modify_element();
		print "<script>window.opener.document.location.href='../config.php?map=$mymap';</script>\n";
		print "<script>window.close();</script>\n";
		
}
else if($myaction == "add")
{
		# we append a new object definition line in the map cfg file
		$mymap= add_element();
		# we display the same map again, with the autosave value activated : the map will automatically be saved
		# after the next drag and drop (after the user placed the new object on the map)
		print "<script>window.opener.document.location.href='../config.php?map=$mymap&autosave=true';</script>\n";
		print "<script>window.close();</script>\n";
}
else if($myaction == "delete")
{
		$mymap= delete_element();
		print "<script>document.location.href='../config.php?map=$mymap';</script>\n";
}
else if($myaction == "update_config") {
	$param=explode('^',$_POST['properties']);
	
	foreach($param AS $myparam)
	{
		$arr = @explode("=",$myparam);
		// Read the key
		$key = @strtolower(@trim($arr[0]));
		unset($arr[0]);
		// Rest of the array is the value
		$val = @trim(@implode("=", $arr));
		
		$CONFIG->setValue($CONFIG->findSecOfVar($key),$key,$val);
	}
	if($CONFIG->writeConfig()) {
		print "<script>window.opener.document.location.reload();</script>\n";
		print "<script>window.close();</script>\n";
	} else {
		print "<script>alert('error while opening the file ".$CONFIG->getValue('paths', 'cfg')."config.ini"." for writing.')</script>";
	}
}
else if($myaction == "mgt_map_create")
{	
	$mymap=create_map();
	print "<script>window.opener.document.location.href='../config.php?map=$mymap';</script>\n";
	print "<script>window.close();</script>\n";
	
}

else if($myaction == "mgt_map_rename")
{	
	$mymap=rename_map();
	print "<script>window.opener.document.location.href='../config.php?map=$mymap';</script>\n";
	print "<script>window.close();</script>\n";
	
}

else if($myaction == "mgt_map_delete")
{	

	$mymap=delete_map();
	print "<script>window.opener.document.location.href='../config.php?map=$mymap';</script>\n";
	print "<script>window.close();</script>\n";
	
}

else if($myaction == "mgt_image_delete")
{	
	
	delete_image();
	print "<script>window.opener.document.location.reload();</script>\n";
	print "<script>window.close();</script>\n";
	
}

else if($myaction == "mgt_new_image")
{
	if (!is_array(${'HTTP_POST_FILES'})) 
	{
		$HTTP_POST_FILES = $_FILES;
	}
	
	# we check the file (the map) is properly uploaded
	if(is_uploaded_file($HTTP_POST_FILES['fichier']['tmp_name']))
	{
	    $ficname = $HTTP_POST_FILES['fichier']['name'];
	  
	    if(substr($ficname,strlen($ficname)-4,4) == ".png")
	    {
	    	if(move_uploaded_file($HTTP_POST_FILES['fichier']['tmp_name'], $CONFIG->getValue('paths', 'map').$ficname)) {
			    print "<script>window.opener.document.location.reload();</script>\n";
			    print "<script>window.close();</script>\n";
			} else {
	    		print "A problem occured !";
				return;
	    	}
	    }
	      
	}
	else
	{
		print "A problem occured !";
		return;
	}
  	
}

else if($myaction == "map_restore")
{	
	$mymap=restore_map();
	print "<script>window.document.location.href='../config.php?map=$mymap';</script>\n";	
}

?>

	



