<?
##########################################################################
##     	                           NagVis                              ##
##         *** Klasse zum einlesen verschiedenener Dateien ***          ##
##                               Lizenz GPL                             ##
##########################################################################
class readFile 
{	
	// <map>.cfg einlesen (neues Format).
	function readNagVisCfg($file) {
		include("./etc/config.inc.php");
		$NagVisCfg = file($cfgFolder.$file.".cfg");
		
		$l="0";
		$x="0";
		$type = array("global","host","service","hostgroup","servicegroup","map","textbox");
		
		while (isset($NagVisCfg[$l]) && $NagVisCfg[$l] != "") {
			if(!ereg("^#",$NagVisCfg[$l]) && !ereg("^;",$NagVisCfg[$l])) {
				$defineCln = explode("{", $NagVisCfg[$l]);
				$define = explode(" ",$defineCln[0]);
				if (isset($define[1]) && in_array(trim($define[1]),$type)) {
					$x++;
					$l++;
					$nagvis[$x]['type'] = $define[1];
					while (trim($NagVisCfg[$l]) != "}") {
						$entry = explode("=",$NagVisCfg[$l], 2);
						if(isset($entry[1])) {
							if(ereg("name", $entry[0])) {
								$entry[0] = "name";
							}
							$nagvis[$x][trim($entry[0])] = trim($entry[1]);
						}
						$l++;	
					}
				}
			}
			$l++;
		}
		return($nagvis);
	}
	
	//Menu für den Header einlesen.
	function readMenu() {
		include("./etc/config.inc.php");
		$Menu = file($cfgPath.$headerInc);
		$a="0";
		$b="0";
		while (isset($Menu[$a]) && $Menu[$a] != "")
		{
			if (!ereg("#",$Menu[$a]) && trim($Menu[$a]) != "")
			{
				$entry = explode(";",$Menu[$a]);
				$link[$b]['entry'] = $entry[0];
				$link[$b]['url'] = $entry[1];
				$b++;
			}
			$a++;
		}
		return($link);
	}
	
}
