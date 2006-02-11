<?
##########################################################################
##     	                           NagVis                               ##
##        *** Klasse zum erzeugen der grafischen Oberfläche ***         ##
##                               Lizenz GPL                             ##
##########################################################################

/**
* This Class creates the Web-Frontend for NagVis
*/

include("./etc/config.inc.php");
include("./includes/classes/class.Common.php");

class frontend extends common
{
	var $site;
	
	/**
	* Open a Web-Site in a Array site[].
	*
	* @param string $RefreshTime
	* @param string $rotateUrl
	*
	* @author Michael Luebben <michael_luebben@web.de>
    */
	function openSite($rotateUrl) {
		include("./etc/config.inc.php");
		$this->site[] = '<HTML>';
		$this->site[] = '<HEAD>';
		$this->site[] = '<TITLE>'.$title.'</TITLE>';
		$this->site[] = '<META http-equiv="refresh" CONTENT="'.$RefreshTime.';'.$rotateUrl.'">';
		$this->site[] = '<SCRIPT TYPE="text/javascript" SRC="./includes/js/nagvis.js"></SCRIPT>';
		$this->site[] = '<SCRIPT TYPE="text/javascript" SRC="./includes/js/overlib.js"></SCRIPT>';
		$this->site[] = '<SCRIPT TYPE="text/javascript" SRC="./includes/js/overlib_shadow.js"></SCRIPT>';
		$this->site[] = '</HEAD>';
		$this->site[] = '<LINK HREF="./includes/css/style.css" REL="stylesheet" TYPE="text/css">';
		$this->site[] = '<BODY CLASS="main"  MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0">';
	}
	
	/**
	* Create a Web-Side from the Array site[].
	*
	* @author Michael Luebben <michael_luebben@web.de>
	*/
	function printSite() {
		foreach ($this->site as $row) {
			echo $row."\n";
		}
	}	
	
	/**
	* Create a Header-Menu
	*
	* @param array $Menu
	* @see function class.ReadFiles.php -> readMenu()
	*
	* @author Michael Luebben <michael_luebben@web.de>
    */
	function makeHeaderMenu($Menu) {
		include("./etc/config.inc.php");
		$x="0";
		$this->site[] = '<TABLE WIDTH="100%" BORDER="0" BGCOLOR="black">';
		$this->site[] = '<DIV CLASS="header">';
		while(isset($Menu[$x]['entry']) && $Menu[$x]['entry'] != "") {
			$this->site[] = '<TR VALIGN="BOTTOM">';
			for($d="1";$d<=$headerCount;$d++) {
				if($Menu[$x]['entry'] != "") {
					$this->site[] = '<TD WIDTH="13">';
					$this->site[] = '<IMG SRC="images/greendot.gif" WIDTH="13" HEIGHT="14" NAME="'.$Menu[$x]['entry'].'_'.$x.'">';
					$this->site[] = '</TD>';
					$this->site[] = '<TD NOWRAP>';
					$this->site[] = '<A HREF='.$Menu[$x]['url'].' onMouseOver="switchdot(\''.$Menu[$x]['entry'].'_'.$x.'\',1)" onMouseOut="switchdot(\''.$Menu[$x]['entry'].'_'.$x.'\',0)" CLASS="NavBarItem">'.$Menu[$x]['entry'].'</A>';
					$this->site[] = '</TD>';
				}
				$x++;
			}
			$this->site[] = '</TR>';
		}
		$this->site[] = '</TABLE></DIV>';
	}
	
	/**
	* Closed a Web-Site in the Array site[]
	*
	* @author Michael Luebben <michael_luebben@web.de>
    */
	function closeSite() {
		$this->site[] = '</DIV>';
		$this->site[] = '</BODY>';
		$this->site[] = '</HTML>';
	}
	
	/**
	* Create a Messagebox for informations and errors.
	*
	* Message-Numbers:
	*  0 = StatusCgi not found!
	*  1 = StatusCgi not executable!
	*  2 = No map config available!
	*  3 = No map image available!
	*  4 = No permissions!
	*  5 = No Nagios logfile!
	*  6 = No support!
	*  7 = Host not found!
	*  8 = Service not found!
	*  9 = Group is empty!
	* 10 = Servicegroup not found!
	* 11 = servicegroup-status!
	* 12 = State not defined!
	* 13 = Service-status!
	* 14 = No User!
	* 15 = GD Lib
	* 16 = wui/wui.function.inc.bash not executable
	*
	* @param string $messagnr
	* @param string $vars
	*
    * @author Michael Luebben <michael_luebben@web.de>
    */
	function messageBox($messagenr, $vars) {
		include("./etc/config.inc.php");
		$LanguageFile = $Base."/etc/languages/".$Language.".txt";
                if(!file_exists($LanguageFile)) {
                        $msg[0] = "XXXX";
                        $msg[1] = "img_error.png";
                        $msg[2] = "Languagefile not found!";
                        $msg[3] = "Check if languagefile variable is set right in config.inc.php!";
                }
                elseif(!is_readable($LanguageFile)) {
                        $msg[0] = "XXXX";
                        $msg[1] = "img_error.png";
                        $msg[2] = "Languagefile not readable!";
                        $msg[3] = "Check permissions of languagefile $LanguageFile!";
                }
                else {
                        $fd=file($LanguageFile);
                        if(!explode("~", $fd[$messagenr])) {
                                $msg[0] = "XXXX";
                                $msg[1] = "img_error.png";
                                $msg[2] = "Wrong error number.";
								$msg[3] = "Maybe error-number is not known.";
                        }
                        else {
                                $msg=explode("~", $fd[$messagenr]);
                        }
                }
		
		$messageNr = $msg[0];
		$messageIcon = $msg[1];
		$messageHead = $msg[2];
		$message = $msg[3];
			
		//eval("\$message = \"$message\";");
		for($i=0;$i<count(explode(' ', $vars));$i++) {
			$var = explode('~', $vars);
			$message = str_replace("[".$var[0]."]", $var[1], $message);
		}
	
		$this->site[] = '<BODY>';
		$this->site[] = '<TABLE CLASS="messageBox" WIDTH="50%" ALIGN="CENTER">';
		$this->site[] = ' <TR>';
		$this->site[] = '  <TD CLASS="messageBoxHead" WIDTH="40">';
		$this->site[] = '   <IMG SRC="./images/'.$messageIcon.'" ALIGN="LEFT">';
		$this->site[] = '  </TD>';
		$this->site[] = '  <TD CLASS="messageBoxHead" ALIGN="CENTER">';
		$this->site[] =     $messagenr.":".$messageHead;
		$this->site[] = '  </TD>';
		$this->site[] = ' </TR>';
		$this->site[] = ' <TR>';
		$this->site[] = '  <TD CLASS="messageBoxMessage" ALIGN="CENTER" COLSPAN="2">';
		$this->site[] =     $message;
		$this->site[] = '  </TD>';
		$this->site[] = ' </TR>';
		$this->site[] = '</TABLE>';
	}
	
	/**
	* Create a Background-Image for a Map.
	*
	* @param string $map_image
	*
	* @author Michael Luebben <michael_luebben@web.de>
	*/
	function printMap($map_image)	{
		include("./etc/config.inc.php");
		$this->site[] = '  <DIV CLASS="map">';
		if ($useGDLibs == "1") {
			$this->site[] = '   <IMG SRC="./draw.php?map='.$map_image.'">';
		} else {
			$this->site[] = '   <IMG SRC="'.$mapHTMLBaseFolder.$map_image.'">';
		}
	}
	
	/**
	* Creates a Java-Box with information.
	*
	* @param string $Type
	* @param string $Hostname
	* @param string $ServiceDesc
	* @param array $stateArray
	*
	* @author Michael Luebben <michael_luebben@web.de>
    */
	function infoBox($Type,$Hostname,$ServiceDesc,$stateArray) {
		include("./etc/config.inc.php");
		$Type = ucfirst($Type);
		$State = $stateArray['State'];
		$Output = $stateArray['Output'];
		if(!isset($stateArray['Count'])) {
			$stateArray['Count'] = 0;
		}
		$Count=$stateArray['Count'];
		$ServiceHostState = $stateArray['Host'];
		if(!isset($ServiceDesc)) {
			$ServiceDesc = $host_servicename;
		}
		// FIXME meht Output (ackComment, mehr Zahlen etc.)
		$Info = 'onmouseover="return overlib(\'';

		if($Type == "Host") {
			$Info .= '<b>Hostname:</b> '.$Hostname.'<br>';
			$Info .= '<b>State:</b> '.$State.'<br>';
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
		}
		elseif($Type == "Service") {
			$Info .= '<b>Hostname:</b> '.$Hostname.'<br>';
			$Info .= '<b>Servicename:</b> '.$ServiceDesc.'<br>';
			$Info .= '<b>State:</b> '.$State.'<br>';
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>';
		}
		elseif($Type == "Hostgroup") {
			$Info .= '<b>Hostgroup Name:</b> '.$Hostname.'<br>';
			$Info .= '<b>Hostgroup State:</b> '.$State.'<br>';
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
		}	
		elseif($Type == "Servicegroup") {
			$Info .= '<b>Servicegroup Name:</b> '.$Hostname.'<br>';
			$Info .= '<b>Servicegroup State:</b> '.$State.'<br>';
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
		}
		elseif($Type == "Map") {
			$Info .= '<b>Map Name:</b> '.$Hostname.'<br>';
			$Info .= '<b>Map State:</b> '.strtr(addslashes($State), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
		}
		$Info .= '\', CAPTION, \''.$Type.'\', SHADOW, WRAP, VAUTO);" onmouseout="return nd();" ';
		return($Info);
	}
	
	/**
	* Create a Debug-Output
	*
	* @param array $debug
	*
	* @author Michael Luebben <michael_luebben@web.de>
	*/
	function debug($debug)
	{
		global $enableDebug;
		
		if ($enableDebug == "1") {
			$this->site[] = '<TABLE CLASS="debugBox" WIDTH="90%" ALIGN="CENTER">';
			$this->site[] = ' <TR>';
			$this->site[] = '  <TD CLASS="debugBoxHead" WIDTH="40" ALIGN="CENTER">';
			$this->site[] = '   <IMG SRC="./images/img_debug.png">';
			$this->site[] = '  </TD>';
			$this->site[] = '  <TD CLASS="debugBoxHead" ALIGN="CENTER">';
			$this->site[] = '   Debugging';
			$this->site[] = '  </TD>';
			$this->site[] = '  <TD CLASS="debugBoxHead" WIDTH="40" ALIGN="CENTER">';
			$this->site[] = '   <IMG SRC="./images/img_debug.png">';
			$this->site[] = '  </TD>';
			$this->site[] = ' </TR>';
			$this->site[] = ' <TR>';
			$this->site[] = '  <TD CLASS="debugBoxMessage" ALIGN="LEFT" COLSPAN="3">';
			foreach ($debug as $debugArray) {
				if(is_array($debugArray)) {
					foreach ($debugArray as $row) {
						$this->site[] = $row."<BR>";
					}
				}
			}
			$this->site[] = '  </TD>';
			$this->site[] = ' </TR>';
			$this->site[] = '</TABLE>';
		}
	}
	
	/**
	* Create a Link for a Line
	*
	* @param array $mapCfg
	*
	* @author FIXME
	*/
	function createBoxLine($mapCfg,$state1,$state2) {
		
		global $HTMLCgiPath;
		
	    if($mapCfg['line_type'] == '10' || $mapCfg['line_type'] == '11'){
			list($x_from,$x_to) = explode(",", $mapCfg['x']);
			list($y_from,$y_to) = explode(",", $mapCfg['y']);
			$x_middle = middle($x_from,$x_to);
			$y_middle = middle($y_from,$y_to);
			$IconPosition = $this->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $this->infoBox($mapCfg['type'],$mapCfg['name'],$mapCfg['service_description'],$state1);
			$this->site[] = $IconPosition;
			$this->site[] = $this->createLink($HTMLCgiPath,$mapCfg['url'],$mapCfg['type'],$mapCfg['name'],$mapCfg['service_description']);
			$this->site[] = '<img src=iconsets/20x20.gif '.$Box.';></A>';
			$this->site[] = '</div>';
		} elseif($mapCfg['line_type'] == '20') {
			list($host_name_from,$host_name_to) = explode(",", $mapCfg['name']);
			list($service_description_from,$service_description_to) = explode(",", $mapCfg['service_description']);
			list($x_from,$x_to) = explode(",", $mapCfg['x']);
			list($y_from,$y_to) = explode(",", $mapCfg['y']);
			// From
			$x_middle = middle2($x_from,$x_to);
			$y_middle = middle2($y_from,$y_to);
			$IconPosition = $this->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $this->infoBox($mapCfg['type'],$host_name_from,$service_description_from,$state1);
			$this->site[] = $IconPosition;
			$this->site[] = $this->createLink($HTMLCgiPath,$mapCfg['url'],$mapCfg['type'],$host_name_from,$service_description_from);
			$this->site[] = '<img src= iconsets/20x20.gif '.$Box.';></A>';
			$this->site[] = '</div>';
			// To
			$x_middle = middle2($x_to,$x_from);
			$y_middle = middle2($y_to,$y_from);
			$IconPosition = $this->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $this->infoBox($mapCfg['type'],$host_name_to,$service_description_to,$state2);
			$this->site[] = $IconPosition;
			$this->site[] = $this->createLink($HTMLCgiPath,$mapCfg['url'],$mapCfg['type'],$host_name_to,$service_description_to);
			$this->site[] = '<img src=iconsets/20x20.gif '.$Box.';></A>';
			$this->site[] = '</div>';
		}
	}
	
	/**
	* Create a Comment-Textbox
	*
	* @param string $x
	* @param string $y
	* @param string $width
	* @param string $text
	*
	* @author Joerg Linge
	*/
	function TextBox($x,$y,$width,$text) {
		$Comment = '<div class="box" style="left : '.$x.'px; top : '.$y.'px; width : '.$width.'px; overflow : visible;">';	
		$Comment .= '<span>'.$text.'</span>';
		$Comment .= '</div>';
		return($Comment);	
	}	

}
?>
