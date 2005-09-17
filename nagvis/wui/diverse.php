<?
#################################################################################
#       Nagvis Web Configurator 0.4						#
#	GPL License								#
#										#
#	Last modified : 10/08/05						#
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

<?
include("../etc/config.inc.php");
include("./classes.wui.php");
$langfile= new langFile($cfgPath."languages/wui_".$Language.".txt");

$myaction = $_GET['action'];

if($myaction == "nagvis_config")
{
	print "<table name=\"mytable\">";
	print "<tr><td>";
	print "<form name=\"diverse\" method=\"post\" action=wui.function.inc.php?myaction=update_config>";
	
	#print "<textarea name=\"config_file\" cols=80 rows=25>"; 
	print "<textarea name=\"config_file\" style=\"width:650px;height:450px;border-style:solid\">"; 
	$handle = fopen("../etc/config.inc.php", "r");
	while (!feof($handle)) 
	{
		$buffer = fgets($handle, 4096);
		print "$buffer";
	}
	fclose($handle);
	print "</textarea>";

	print "<tr><td align=\"center\"><button name=\"button_submit\" type=\"submit\" value=\"submit\" id=\"commit\">".$langfile->get_text("8")."</button></td></tr>";
	print "</form>";
	print "</td></tr>";
	
	

	print "</table>";	
}



?>

</html>


<script type="text/javascript" language="JavaScript"><!--

	window.resizeTo(720,560);
	myx=(screen.width - 720)/ 2;
	myy=(screen.height - 560)/ 2;
	window.moveTo((screen.width - 720)/ 2,(screen.height - 560)/ 2);
	

//--></script>



