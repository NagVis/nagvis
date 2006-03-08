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
<link rel="stylesheet" type="text/css" href="./css/management.css" />
<TITLE>Nagvis configtool</TITLE>
</head>

<?
include("../includes/classes/class.NagVisConfig.php");
include("./classes.wui.php");

$CONFIG = new MainNagVisCfg('../etc/config.ini');

# we load the language file
$langfile= new langFile($CONFIG->getValue('paths', 'cfg')."languages/wui_".$CONFIG->getValue('global', 'language').".txt");

# we set the current username
if(isset($_SERVER['PHP_AUTH_USER'])) {
	$user = $_SERVER['PHP_AUTH_USER'];
}
elseif(isset($_SERVER['REMOTE_USER'])) {
	$user = $_SERVER['REMOTE_USER'];
}
if($user == "") {exit;}


?>
<script type="text/javascript" language="JavaScript"><!--

// we now check that this page has been called by config.php. if it's not the case, we close it
if (window.opener==undefined)
{
	window.document.location.href='about:blank';
}
if(window.opener.document.location.pathname.substr(window.opener.document.location.pathname.length-18,18)!="/nagvis/config.php")
{
	window.document.location.href='about:blank';
}
//--></script>


<body>

<table>
	<form method="post" action="wui.function.inc.php?myaction=mgt_map_create" name="map_create" onsubmit="return check_create_map();">
	<caption class="tdlabel" ><? echo strtoupper($langfile->get_text("15")); ?></caption>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="30%" class="tdfield"><? echo $langfile->get_text("24"); ?></td>
		<td width="40%" class="tdfield"><input type="text" name="map_name"></td>
		<td width="25%" valign="middle" align="center" rowspan="5"><input type="submit" name="map_create_ok" value="<? echo $langfile->get_text("20"); ?>"></td>
	</tr>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="35%" class="tdfield"><? echo $langfile->get_text("25"); ?></td>
		<td width="35%" class="tdfield"><input type="text" name="allowed_users" value="<? echo $user ?>"</td>
	</tr>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="35%" class="tdfield"><? echo $langfile->get_text("49"); ?></td>
		<td width="35%" class="tdfield"><input type="text" name="allowed_for_config" value="<? echo $user ?>"</td>
	</tr>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="35%" class="tdfield"><? echo $langfile->get_text("32"); ?></td>
		
		<td width="35%" class="tdfield" style="text-align:left"><select name="map_iconset">
		<?
			$files=array();
			if ($handle = opendir($CONFIG->getValue('paths', 'icon'))) 
			{
 				while (false !== ($file = readdir($handle))) 
				{
					if ($file != "." && $file != ".." && substr($file,strlen($file)-7,7) == "_ok.png" ) { $files[]=substr($file,0,strlen($file)-7);}				
				}
			if ($files) natcasesort($files); 
			foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
			
		}
		closedir($handle);
		?>
		</select></td>
		<script>document.map_create.map_iconset.value='<? echo $defaultIcons ?>';</script>	
	</tr>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="35%" class="tdfield"><? echo $langfile->get_text("26"); ?></td>
		
		<td width="35%" class="tdfield" style="text-align:left"><select name="map_image">
		<?
			$files=array();
			if ($handle = opendir($CONFIG->getValue('paths', 'map'))) 
			{
	 			while (false !== ($file = readdir($handle))) 
				{
					if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".png" ) { $files[]=$file;}				
				}
			
				if ($files) natcasesort($files); 
				foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
			}
			closedir($handle);
		?>
		</select></td>
		
	</tr>
	</form>
</table>

<br>


<table>
	<form method="post" action="wui.function.inc.php?myaction=mgt_map_rename" name="map_rename" onsubmit="return check_map_rename();">
	<caption class="tdlabel" ><? echo strtoupper($langfile->get_text("16")); ?></caption>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="35%" class="tdfield"><? echo $langfile->get_text("27"); ?></td>
		
		<td width="35%" class="tdfield" style="text-align:left"><select name="map_name">
		<?
			$files=array();
			if ($handle = opendir($CONFIG->getValue('paths', 'mapcfg'))) 
			{
	 			while (false !== ($file = readdir($handle))) 
				{
					if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".cfg" ) { $files[]=substr($file,0,strlen($file)-4);}				
				}
			
				if ($files) natcasesort($files); 
				foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
			}
			closedir($handle);
		?>
		</select></td>
		
		<td width="25%" valign="middle" align="center" rowspan="2"><input type="submit" name="map_rename_ok" value="<? echo $langfile->get_text("22"); ?>"></td>
	</tr>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="35%" class="tdfield"><? echo $langfile->get_text("28"); ?></td>
		<td width="35%" class="tdfield"><input type="text" name="map_new_name">
		<input type="hidden" name="map">
		<script>document.map_rename.map.value=window.opener.document.myvalues.formulaire.value</script>
		</td>
		
	</tr>
	</form>
</table>

<br>

<table>
	<form method="post" action="wui.function.inc.php?myaction=mgt_map_delete" name="map_delete" onsubmit="return check_map_delete();">
	<caption class="tdlabel" ><? echo strtoupper($langfile->get_text("17")); ?></caption>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="35%" class="tdfield"><? echo $langfile->get_text("27"); ?></td>
		
		<td width="35%" class="tdfield" style="text-align:left"><select name="map_name">
		<?
			$files=array();
			if ($handle = opendir($CONFIG->getValue('paths', 'mapcfg'))) 
			{
	 			while (false !== ($file = readdir($handle))) 
				{
					if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".cfg" ) { $files[]=substr($file,0,strlen($file)-4);}				
				}
			
				if ($files) natcasesort($files); 
				foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
			}
			closedir($handle);
		?>
		</select>
		<input type="hidden" name="map">
		<script>document.map_delete.map.value=window.opener.document.myvalues.formulaire.value</script>
		</td>
		
		<td width="25%" valign="middle" align="center"><input type="submit" name="map_delete_ok" value="<? echo $langfile->get_text("21"); ?>"></td>
	</tr>
	</form>
</table>

<br>
<br>
<br>

<table>
	<form name="new_image" method="POST" action="./wui.function.inc.php?myaction=mgt_new_image" enctype="multipart/form-data" onsubmit="return check_png();"
	<input type="hidden" name="MAX_FILE_SIZE" value="1000000">
	
	<caption class="tdlabel" ><? echo strtoupper($langfile->get_text("18")); ?></caption>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="35%" class="tdfield"><? echo $langfile->get_text("29"); ?></td>
		<td width="35%" class="tdfield"><input type="file" name="fichier"></td>
		<td width="25%" valign="middle" align="center"><input type="submit" name="new_image_ok" value="<? echo $langfile->get_text("23"); ?>"></td>
	</tr>
	</form>
</table>

<br>
<table>
	<form method="post" action="wui.function.inc.php?myaction=mgt_image_delete" name="image_delete" onsubmit="return check_image_delete();">
	<caption class="tdlabel" ><? echo strtoupper($langfile->get_text("19")); ?></caption>
	<tr>
		<td width="5%">&nbsp;</td>
		<td width="35%" class="tdfield"><? echo $langfile->get_text("29"); ?></td>
		
		<td width="35%" class="tdfield" style="text-align:left"><select name="map_image">
		<?
			$files=array();
			if ($handle = opendir($CONFIG->getValue('paths', 'map'))) 
			{
	 			while (false !== ($file = readdir($handle))) 
				{
					if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".png" ) { $files[]=$file;}				
				}
			
				if ($files) natcasesort($files); 
				foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
			}
			closedir($handle);
		?>
		</select></td>
		
		<td width="25%" valign="middle" align="center"><input type="submit" name="image_delete_ok" value="<? echo $langfile->get_text("21"); ?>"></td>
	</tr>
	</form>
</table>

</body>




<script type="text/javascript" language="JavaScript"><!--

// checks that the file the user wants to upload has the .png extension
function check_png() 
{

  if(document.new_image.fichier.value.length == 0)
  {
  	 alert('<? echo $langfile->get_text_silent("30"); ?>');
	 return false;
  }
  else
  {
  	  var ext = document.new_image.fichier.value;
	  ext = ext.substring(ext.length-3,ext.length);
	  ext = ext.toLowerCase();
	  if(ext != 'png') 
	  {
	    alert('<? echo $langfile->get_text_silent("31"); ?>');
	    return false; 
	  }
	  else return true; 
  }
}


function check_create_map()
{
	if (document.map_create.map_name.value=='')
	{
		alert("<? echo $langfile->get_text_silent("33") ?>");
		return false;
	}
	if (document.map_create.map_name.value.split(" ").length > 1)
	{
		alert("<? echo $langfile->get_text_silent("53") ?>");
		return false;
	}
	if (document.map_create.allowed_users.value=='')
	{
		alert("<? echo $langfile->get_text_silent("34") ?>");
		return false;
	}
	if (document.map_create.allowed_for_config.value=='')
	{
		alert("<? echo $langfile->get_text_silent("48") ?>");
		return false;
	}
	if (document.map_create.map_iconset.value=='')
	{
		alert("<? echo $langfile->get_text_silent("35") ?>");
		return false;
	}
	if (document.map_create.map_image.value=='')
	{
		alert("<? echo $langfile->get_text_silent("36") ?>");
		return false;
	}
	
	for(var i=0;i<document.map_rename.map_name.length;i++)
	{
		if(document.map_rename.map_name.options[i].value == document.map_create.map_name.value)
		{
			alert("<? echo $langfile->get_text_silent("39") ?>");
			return false;
		}
	}
	
	if (confirm("<? echo $langfile->get_text_silent("42") ?>") === false)
	{
		return false;
	}
	
	return true;
}


function is_user_allowed(mapname)
{
	username=window.opener.document.myvalues.username.value;
	temp=window.opener.document.myvalues.allowed_users_by_map.value.split("^");

	for(var i=0;i<temp.length;i++)
	{
		temp2=temp[i].split("=");
		if(temp2[0]==mapname)
		{
			temp3=temp2[1].split(",");
			for(var j=0;j<temp3.length;j++)
			{
				if( (temp3[j]==username) || (temp3[j]=="EVERYONE") ) return true;
			}
			return false;
		}
	}
	return false;
}


function check_map_rename()
{
	if (document.map_rename.map_name.value=='')
	{
		alert("<? echo $langfile->get_text_silent("37") ?>");
		return false;
	}
	
	if (document.map_rename.map_new_name.value.split(" ").length > 1)
	{
		alert("<? echo $langfile->get_text_silent("53") ?>");
		return false;
	}
	
	if (document.map_rename.map_new_name.value=='')
	{
		alert("<? echo $langfile->get_text_silent("38") ?>");
		return false;
	}
	
	for(var i=0;i<document.map_rename.map_name.length;i++)
	{
		if(document.map_rename.map_name.options[i].value == document.map_rename.map_new_name.value)
		{
			alert("<? echo $langfile->get_text_silent("39") ?>");
			return false;
		}
	}
	
	if (is_user_allowed(document.map_rename.map_name.value)===false)
	{
		alert("<? echo $langfile->get_text_silent("47") ?>");
		return false;
	}
	
	if (confirm("<? echo $langfile->get_text_silent("43") ?>") === false)
	{
		return false;
	}
	
	return true;
}

var mapname_used_by;
function is_mapname_used(map_name)
{
	mapname_used_by="";
	temp=window.opener.document.forms['myvalues'].mapname_by_map.value.split("^");
	for(var i=0;i<temp.length;i++)
	{
		temp2=temp[i].split("=");
		if(temp2[1]==map_name)
		{
			mapname_used_by=temp2[0];
			return true;
		}

	}
	
	return false;
}


function check_map_delete()
{
	if (document.map_delete.map_name.value=='')
	{
		alert("<? echo $langfile->get_text_silent("40") ?>");
		return false;
	}
	
	if (is_user_allowed(document.map_delete.map_name.value)===false)
	{
		alert("<? echo $langfile->get_text_silent("47") ?>");
		return false;
	}
	
	if(is_mapname_used(document.map_delete.map_name.value))
	{
		mess=new String("<? echo $langfile->get_text_silent('46') ?>");
		mess=mess.replace("[MAP]",mapname_used_by);
		mess=mess.replace("[IMAGENAME]",document.map_delete.map_name.value);
		alert(mess);
		return false;
	}
	
	if (confirm("<? echo $langfile->get_text_silent("44") ?>") === false)
	{
		return false;
	}
	
	return true;
}


var image_used_by;
function is_map_image_used(imagename)
{
	image_used_by="";
	temp=window.opener.document.forms['myvalues'].image_map_by_map.value.split("^");
	for(var i=0;i<temp.length;i++)
	{
		temp2=temp[i].split("=");
		if(temp2[1]==imagename)
		{
			image_used_by=temp2[0];
			return true;
		}

	}
	
	return false;
}


function check_image_delete()
{
	if (document.image_delete.map_image.value=='')
	{
		alert("<? echo $langfile->get_text_silent("41") ?>");
		return false;
	}
	
	if(is_map_image_used(document.image_delete.map_image.value))
	{
		mess=new String("<? echo $langfile->get_text_silent('46') ?>");
		mess=mess.replace("[MAP]",image_used_by);
		mess=mess.replace("[IMAGENAME]",document.image_delete.map_image.value);
		alert(mess);
		return false;
	}
	
	if (confirm("<? echo $langfile->get_text_silent("45") ?>") === false)
	{
		return false;
	}
	
	return true;
}



//--></script>
