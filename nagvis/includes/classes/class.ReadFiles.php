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
