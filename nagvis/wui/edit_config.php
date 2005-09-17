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
?>

<html>
<head>
<link rel="stylesheet" type="text/css" href="./css/addmodify.css" />
<TITLE>Nagvis config tool</TITLE>
</head>
<body>

<?
include("../etc/config.inc.php");
include("./classes.wui.php");
$langfile= new langFile($cfgPath."languages/wui_".$Language.".txt");
$lang_config_file= new langFile($cfgPath."languages/config_".$Language.".txt");
$help_context_icon="help_icon.png";


print "<table name=\"mytable\">";
print "<form name=\"edit_config\" method=\"post\" action=wui.function.inc.php?myaction=update_config onsubmit=\"return update_param();\">";
	
# we draw the invisible textbox which will contain the values when we validate the form
print "<input type=\"hidden\" name=\"properties\">";
	
# for each line in the main config file
$handle = fopen("../etc/config.inc.php", "r");
while (!feof($handle)) 
{
	# we get the line
	$buffer = fgets($handle, 4096);
		
	# we see if it starts with a $
	if (substr($buffer,0,1) == '$' || substr($buffer,0,2) == '//')
	{
		# we separate the name and the value of the parameter
		$temp=explode("=",$buffer);
		# we remove the $ from the param name
		$param_name=substr(trim($temp[0]),1,strlen(trim($temp[0]))-1);
		# we remove the final ;
		$param_value=trim(substr(trim($temp[1]),0,strlen(trim($temp[1]))-1));
			
		# we remove the 2 " enclosing a 'normal' value.
		# examples : for $Language="english";       we return english
		#                $MapFolder=$Base."/maps/"; we return $Base."/maps/"
		if(preg_match('/\"/', $param_value) ) {
			if(strpos($param_value,"\"")==0 && strpos($param_value,"\"",1)==strlen($param_value)-1)
			{
				$param_value=substr($param_value,1,strlen($param_value)-2);				
			}
		}
			
		# we add a line in the form
		if(preg_match('/^\//', $param_name)) {
			preg_match('/^\/(.*)$/', $param_name, $param_value);
			$param_value = $param_value[1];
			$param_name  = "comment";
		}
		print "<tr><td class=\"tdlabel\">".$param_name."</td>";
			
		# we retrieve the possible help message associated with this parameter
		$help_context=$lang_config_file->get_text_silent($param_name);
			
		if($help_context=="")
		{
			print "<td></td>";
		}
		else
		{
			print "<td>";
			print "<img style=\"cursor:help\" src=\"".$help_context_icon."\" onclick=\"javascript:alert('".$help_context."')\">";
			print "</td>\n";
		}
		
		if($param_name=='comment') 
		{
			print "<td class=\"tdfield\"><input type=\"text\" name=\"conf_".$param_name."\" value='".$param_value."'></td></tr>\n";
			
			if($param_name=="HTMLBase")
			{
				print "<script>document.edit_config.elements['conf_".$param_name."'].value='".str_replace('\'',"\'",$param_value)."';</script>";
			}
				
			if($param_name=='version' || $param_name=='title' || $param_name=='host_servicename')
			{
				print "<script>document.edit_config.elements['conf_".$param_name."'].disabled=true;</script>\n";
			}
		}		
		elseif($param_name=='Language')
		{
			print "<td class=\"tdfield\"><select name=\"conf_".$param_name."\">";
			$files=array();
			if ($handle2 = opendir($cfgPath."/languages")) 
			{
	 			while (false !== ($file = readdir($handle2))) 
				{
					if ($file != "." && $file != ".." && substr($file,0,4) == "wui_" ) { $files[]=substr($file,4,strlen($file)-8);}				
				}
		
				if ($files) natcasesort($files); 
				foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
			}
			closedir($handle2);
			print "</select></td>\n";
				
			print "<script>document.edit_config.elements['conf_".$param_name."'].value='".$param_value."';</script>\n";
				
		}
		elseif($param_name=='defaultIcons')
		{
			print "<td class=\"tdfield\"><select name=\"conf_".$param_name."\">";
			$files=array();
			if ($handle2 = opendir($iconBaseFolder)) 
			{
	 			while (false !== ($file = readdir($handle2))) 
				{
					if ($file != "." && $file != ".." && substr($file,strlen($file)-7,7) == "_ok.png" ) { $files[]=substr($file,0,strlen($file)-7);}
				}
			
				if ($files) natcasesort($files); 
				foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
			}
			closedir($handle2);
			print "</select></td>\n";
			print "<script>document.edit_config.elements['conf_".$param_name."'].value='".$param_value."';</script>\n";
				
		}
		elseif($param_name=='RotateMaps' || $param_name=='Header')
		{
			print "<td class=\"tdfield\"><select name=\"conf_".$param_name."\">";
			print "<option value=\"1\">".$langfile->get_text("6")."</option>";
			print "<option value=\"0\">".$langfile->get_text("7")."</option>";
			print "</select></td>\n";
			print "<script>document.edit_config.elements['conf_".$param_name."'].value='".$param_value."';</script>\n";
		}
		else
		{
			print "<td class=\"tdfield\"><input type=\"text\" name=\"conf_".$param_name."\" value='".$param_value."'></td></tr>\n";
			
			if($param_name=="HTMLBase")
			{
				print "<script>document.edit_config.elements['conf_".$param_name."'].value='".str_replace('\'',"\'",$param_value)."';</script>";
			}
				
			if($param_name=='version' || $param_name=='title' || $param_name=='host_servicename')
			{
				print "<script>document.edit_config.elements['conf_".$param_name."'].disabled=true;</script>\n";
			}
		}
		print "</tr>";
						
	}
}
fclose($handle);
	
# we draw the validate button
print "<tr height=\"20px\"><td></td></tr>";
print "<tr><td align=\"center\" colspan=\"3\" id=\"mycell\"><button name=\"button_submit\" type=submit value=\"submit\"	id=\"commit\">".$langfile->get_text("8")."</button></td></tr>";
print "</form>";
print "</table>";	


?>

</body>
</html>


<script type="text/javascript" language="JavaScript"><!--

// function that builds up the list of parameters/values. There are 2 kinds of parameters values :
//	- the "normal value". example : $param="value";
//	- the other one (value computed with other ones) . example : $param="part1".$otherparam;
function update_param()
{
	document.edit_config.properties.value='';
	for(i=0;i<document.edit_config.elements.length;i++)
	{
		if(document.edit_config.elements[i].name.substring(0,5)=='conf_')
		{
			if(document.edit_config.elements[i].value.lastIndexOf('"',document.edit_config.elements[i].value.length)>0)
			{
				document.edit_config.properties.value=document.edit_config.properties.value+'^$'+document.edit_config.elements[i].name.substring(5,document.edit_config.elements[i].name.length)+'='+document.edit_config.elements[i].value+';';			
			}
			else
			{			
				document.edit_config.properties.value=document.edit_config.properties.value+'^$'+document.edit_config.elements[i].name.substring(5,document.edit_config.elements[i].name.length)+'="'+document.edit_config.elements[i].value+'";';
			}
		}
	}
	document.edit_config.properties.value=document.edit_config.properties.value.substring(1,document.edit_config.properties.value.length);
	return true;
}

//--></script>



