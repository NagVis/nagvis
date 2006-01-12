<?
/**
* This is a Class to Debug information
*
* @author Michael Lübben <michael_luebben@web.de>
*/

class debug 
	{

	var $debug;

	/**
	* Output Info-Text in the Debug-Mode
	*
	* @param string $text
	*
	* @author Michael Lübben <michael_luebben@web.de>
	*/
	function debug_insertInfo($enable,$text)
		{
		if($enable == "1") { 
			$debug[] = '<B>'.$text.'</B>';
			return($debug);
		}
	}

	/**
	* Output a row in the Debug-Output
	*
	* @param string $text
	*
	* @author Michael Lübben <michael_luebben@web.de>
	*/
	function debug_insertRow($enable)
		{
		if($enable == "1") {
			$debug[] = '<BR>';
			return($debug);
		}
	}

	/**
	* Create a link to NagVis-Doku
	*
	* @author Michael Lübben <michael_luebben@web.de>
	*/
	function debug_createLinkToDoku($type,$class)
		{
		global $backend;
		global $HTMLBaseDoku;
		
		if ($type == "class") {
			$lowerClass = strtolower($class);
			$link = '<A HREF="'.$HTMLBaseDoku.'includes/classes/class.CheckState_'.$backend.'.php.html#'.$lowerClass.'" TARGET="_blank">'.$class.'</A>';
		}
		return($link);
	}

	/**
	* Debug strings for function checkState
	*
	* @param string $arrayPos
	* @param string $enDebug
	* @param string $enFunc
	*
	* @author Michael Lübben <michael_luebben@web.de>
	*/
	function debug_checkState($enDebug,$enFunc,$arrayPos)
		{
		global $mapCfg;
		global $CgiPath;
		global $CgiUser;
		global $state;
		
		if($enDebug == "1" && $enFunc == "1") {
			$debug[] = '<B>function</B> $state = '.$this->debug_createLinkToDoku('class','checkStates').'('.$mapCfg[$arrayPos]['type'].','.$mapCfg[$arrayPos]['name'].','.$mapCfg[$arrayPos]['recognize_services'].','.$mapCfg[$arrayPos]['service_description'].',0,'.$CgiPath.','.$CgiUser.')';

			$debug[] = '--> $state[State]:'.$state[State];
			$debug[] = '--> $state[Count]:'.$state[Count];
			$debug[] = '--> $state[Output]:'.$state[Output];
			return($debug);
		}
	}

	/**
	* Debug the function fixIcon
	*
	* @param string $arrayPos
	* @param string $enDebug
	* @param string $enFunc
	*
	* @author Michael Lübben <michael_luebben@web.de>
	*/
	function debug_fixIcon($enDebug,$enFunc,$arrayPos)
		{
		global $state;
		global $mapCfg;
		global $defaultIcons;
		global $Icon;

		if($enDebug == "1" && $enFunc == "1") {
			$debug[] = '<B>function</B> $Icon =  '.$this->debug_createLinkToDoku('class','fixIcon').'('.$state[State].','.$mapCfg[$arrayPos]['iconset'].','.$arrayPos.','.$defaultIcons.','.$mapCfg[$arrayPos]['type'].')';

			$debug[] = '--> $Icon:'.$Icon;
			return($debug);
		}
	}

}
?>