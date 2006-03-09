<?
##########################################################################
##     	                           NagVis                               ##
##         *** Klasse zum einlesen verschiedenener Dateien ***          ##
##                               Lizenz GPL                             ##
##########################################################################

/**
* This Class read the Configuration-Files from NagVis
*/

class readFile {	
	var $MAINCFG;
	
	/**
	* Constructor
	*
	* @param config $MAINCFG
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function readFile($MAINCFG) {
		$this->MAINCFG = $MAINCFG;
	}
	
	/**
	* Read the Map-Configuration files
	*
	* Allowed defines are:
	*
	* - global
	*   For Global parameters for the map
	*
	* - host
	*   This defines a Icon for a Host on the map
	*
	* - service
	*   This defines a Icon for a Service on the map
	*
	* - hostgroup
	*   This define a Icon for a Hostgroup on the map
	*
	* - servicegroup
	*   This define a Icon for a Servicegroup on the map
	*
	* - map
	*   This define a Icon fron another map form nagvis.
	*
	* - textbox
	*   This define a Textbox on the map.
	*
	* @param string $file
	*
	* @author Michael Luebben <michael_luebben@web.de>
	*
	* DEPRECATED: should be done in class.MapCfg in the future
	*/
	/*function readNagVisCfg($file) {
		$NagVisCfg = file($this->MAINCFG->getValue('paths', 'mapcfg').$file.".cfg");
		
		$l="0";
		$x="0";
		$type = array("global","host","service","hostgroup","servicegroup","map","textbox");
		$createArray = array("allowed_user");
		
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
						if(in_array(trim($entry[0]),$createArray)) {
							$nagvis[$x][trim($entry[0])] = explode(",",$entry[1]);
						}
						elseif(isset($entry[1])) {
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
	}*/
	
	/**
	* Read the Map-Configuration files in a new Array-Format
	*
	* Allowed defines are:
	*
	* - global
	*   For Global parameters for the map
	*
	* - host
	*   This defines a Icon for a Host on the map
	*
	* - service
	*   This defines a Icon for a Service on the map
	*
	* - hostgroup
	*   This define a Icon for a Hostgroup on the map
	*
	* - servicegroup
	*   This define a Icon for a Servicegroup on the map
	*
	* - map
	*   This define a Icon fron another map form nagvis.
	*
	* - textbox
	*   This define a Textbox on the map.
	*
	* @param string $file
	*
	* @author Michael Luebben <michael_luebben@web.de>
	*
	* DEPRECATED: should be done in class.MapCfg in the future
	*/
	/*function readNagVisCfgNew($file) {
		$NagVisCfg = file($this->MAINCFG->getValue('paths', 'mapcfg').$file.".cfg");
		
		$l="0";
		$x="0";
		$type = array("global","host","service","hostgroup","servicegroup","map","textbox");
		$createArray = array("allowed_user");
		
		while (isset($NagVisCfg[$l]) && $NagVisCfg[$l] != "") {
			if(!ereg("^#",$NagVisCfg[$l]) && !ereg("^;",$NagVisCfg[$l])) {
				$defineCln = explode("{", $NagVisCfg[$l]);
				$define = explode(" ",$defineCln[0]);
				if (isset($define[1]) && in_array(trim($define[1]),$type)) {
					$x++;
					$l++;
					$nagvis[$define[1]][$x]['type'] = $define[1];
					while (trim($NagVisCfg[$l]) != "}") {
						$entry = explode("=",$NagVisCfg[$l], 2);
						
						if(isset($entry[1])) {
							if(ereg("name", $entry[0])) {
								$entry[0] = "name";
							}
							if(in_array(trim($entry[0]),$createArray)) {
								$nagvis[$define[1]][$x][trim($entry[0])] = explode(",",$entry[1]);
							} else {
								$nagvis[$define[1]][$x][trim($entry[0])] = trim($entry[1]);
							}
						}
						$l++;	
					}
				}
			}
			$l++;
		}
		return($nagvis);
	}*/
	
	/**
	* Read the Configuration-File for the Header-Menu.
	*
	* @author Michael Lübben <michael_luebben@web.de>
	*/
	function readMenu() {
		$Menu = file($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header'));
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
