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

<html>
<head>
<link rel="stylesheet" type="text/css" href="./css/addmodify.css" />
<TITLE>Nagvis config tool</TITLE>
</head>
<body>

<?
include("../includes/classes/class.NagVisConfig.php");
include("../includes/classes/class.NagVisLanguage.php");

$MAINCFG = new MainNagVisCfg('../etc/config.ini.php');
// we set that this is a wui session
$MAINCFG->setRuntimeValue('wui',1);

# we load the language file
$LANG = new NagVisLanguage($MAINCFG,$MAPCFG);
$LANG->readLanguageFile();

$CFGLANG = new NagVisLanguage($MAINCFG,$MAPCFG,'config');
$CFGLANG->readLanguageFile();

print "<table name=\"mytable\">";
print "<form name=\"edit_config\" method=\"post\" action=wui.function.inc.php?myaction=update_config onsubmit=\"return update_param();\">";
	
# we draw the invisible textbox which will contain the values when we validate the form
print "<input type=\"hidden\" name=\"properties\">";

# for each line in the main config file
foreach($MAINCFG->config AS $key => $val) 
{

	if(is_array($val)) {
		foreach($val AS $key2 => $val2) {
			if(@substr($key2,0,8) != "comment_") {
				//print the ini section from config.ini.php as headline for ech section (e.g. [global] [backend_ndo] and so on)
				if(@substr($key,0,8) != "comment_" && $lastSection != $key) {	
						print "<tr><td class=\"tdsection\" colspan=\"3\">".$key."</td>";
						$lastSection = $key;
				}

				# we add a line in the form
				print "<tr><td class=\"tdlabel\">".$key2."</td>";
					
				# we retrieve the possible help message associated with this parameter
				$help_context=$CFGLANG->getTextSilent($key2);
					
				if($help_context=="")
				{
					print "<td></td>";
				}
				else
				{
					print "<td>";
					print "<img style=\"cursor:help\" src=\"help_icon.png\" onclick=\"javascript:alert('".$help_context."')\">";
					print "</td>\n";
				}
					
				if($key2 == 'language')
				{
					print "<td class=\"tdfield\"><select name=\"conf_".$key2."\">";
					$files = array();
					if ($handle2 = opendir($MAINCFG->getValue('paths', 'cfg')."/languages")) 
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
						
					print "<script>document.edit_config.elements['conf_".$key2."'].value='".$val2."';</script>\n";
						
				}
				elseif($key2 == 'backend')
				{
					print "<td class=\"tdfield\"><select name=\"conf_".$key2."\">";
					$files = array();
					if ($handle2 = opendir($MAINCFG->getValue('paths', 'base')."/includes/classes")) 
					{
			 			while (false !== ($file = readdir($handle2))) 
						{
							//FIXME: Ha: I guess we should better replace all this substr sontructs in the if()s with preg_match
							if ($file != "." && $file != ".." && substr($file,0,17) == "class.CheckState_") { 
								$files[]=substr($file,17,strlen($file)-21);
							}				
						}
				
						if ($files) natcasesort($files); 
						foreach ($files as $file) { print "<option value=\"$file\">$file</option>"; }
					}
					closedir($handle2);
					print "</select></td>\n";
						
					print "<script>document.edit_config.elements['conf_".$key2."'].value='".$val2."';</script>\n";
						
				}
				elseif($key2 == 'defaulticons')
				{
					print "<td class=\"tdfield\"><select name=\"conf_".$key2."\">";
					$files=array();
					if ($handle2 = opendir($MAINCFG->getValue('paths', 'icon'))) 
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
					print "<script>document.edit_config.elements['conf_".$key2."'].value='".$val2."';</script>\n";
						
				}
				elseif($key2 == 'rotatemaps' || $key2 == 'displayheader' || $key2 == 'checkconfig' || $key2 == 'usegdlibs'
						|| $key2 == 'debug' || $key2 == 'debugstates' || $key2 == 'debugcheckstate' || $key2 == 'debugfixicon')
				{
					print "<td class=\"tdfield\"><select name=\"conf_".$key2."\">";
					print "<option value=\"1\">".$LANG->getText("6")."</option>";
					print "<option value=\"0\">".$LANG->getText("7")."</option>";
					print "</select></td>\n";
					print "<script>document.edit_config.elements['conf_".$key2."'].value='".$val2."';</script>\n";
				}
				elseif($key2 == 'autoupdatefreq')
				{
					print "<td class=\"tdfield\"><select name=\"conf_".$key2."\">";
					print "<option value=\"0\">".$LANG->getText("52")."</option>";
					print "<option value=\"2\">2</option>";
					print "<option value=\"5\">5</option>";
					print "<option value=\"10\">10</option>";
					print "<option value=\"25\">25</option>";
					print "<option value=\"50\">50</option>";
					print "</select></td>\n";
					print "<script>document.edit_config.elements['conf_".$key2."'].value='".$val2."';</script>\n";
				}
				else
				{
					print "<td class=\"tdfield\"><input type=\"text\" name=\"conf_".$key2."\" value='".$val2."'></td></tr>\n";
					
					if($key2=="HTMLBase")
					{
						print "<script>document.edit_config.elements['conf_".$key2."'].value='".str_replace('\'',"\'",$val2)."';</script>";
					}
						
					if($key2 == 'version' || $key2 == 'title' || $key2=='host_servicename')
					{
						print "<script>document.edit_config.elements['conf_".$key2."'].disabled=true;</script>\n";
					}
				}
				print "</tr>";
			}
		}
	}
}
	
# we draw the validate button
print "<tr height=\"20px\"><td></td></tr>";
print "<tr><td align=\"center\" colspan=\"3\" id=\"mycell\"><button name=\"button_submit\" type=submit value=\"submit\"	id=\"commit\">".$LANG->getText("8")."</button></td></tr>";
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
			document.edit_config.properties.value=document.edit_config.properties.value+'^'+document.edit_config.elements[i].name.substring(5,document.edit_config.elements[i].name.length)+'='+document.edit_config.elements[i].value;
		}
	}
	document.edit_config.properties.value=document.edit_config.properties.value.substring(1,document.edit_config.properties.value.length);
	return true;
}

//--></script>



