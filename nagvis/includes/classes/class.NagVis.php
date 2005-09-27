<?
##########################################################################
##     	                           NagVis                              ##
##        *** Klasse zum erzeugen der grafischen Oberfläche ***         ##
##                               Lizenz GPL                             ##
##########################################################################
class NagVis
{
	var $site;
	
	// ********************* Web-Seite *********************
	
	// Web-Seite öffnen.
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
	}
	
	// Inhalt der Web-Seite erzeugen.
	function printSite() {
		foreach ($this->site as $row) {
			echo $row."\n";
		}
	}	
	
	// Erzeugt das Menu im Header, wenn es aktiviert ist.
	function makeHeaderMenu($Menu) {
		include("./etc/config.inc.php");
		$x="0";
		$this->site[] = '<BODY CLASS="main"  MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0">';
		$this->site[] = '<TABLE WIDTH="100%" BORDER="0" BGCOLOR="black">';
		$this->site[] = '<DIV CLASS="header">';
		while(isset($Menu[$x]['entry']) && $Menu[$x]['entry'] != "") {
			$this->site[] = '<TR VALIGN="BOTTOM">';
			for($d="1";$d<=$headerCount;$d++) {
				if($Menu[$x]['entry'] != "") {
					$this->site[] = '<TD WIDTH="13">';
					$this->site[] = '<IMG SRC="./images/greendot.gif" WIDTH="13" HEIGHT="14" NAME="'.$Menu[$x]['entry'].'_'.$x.'">';
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
	
	//Schliesst die Web-Seite
	function closeSite() {
		$this->site[] = '</DIV>';
		$this->site[] = '</BODY>';
		$this->site[] = '</HTML>';
	}

	// ********************* Message-Box *******************
	
	// Erzeugt eine Message-Box.
	function messageBox($messagenr, $vars) {
                include("./etc/config.inc.php");
		$LanguageFile = $Base."/etc/languages/".$Language.".txt";
                if(!file_exists($LanguageFile)) {
                        $msg[0] = "XXXX";
                        $msg[1] = "img_error.png";
                        $msg[2] = "Languagefile not found!";
                        $msg[3] = "Check if languagefile variaibale is set correct in config.inc.php!";
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
		
		$messageNr	= $msg[0];
                $messageIcon 	= $msg[1];
                $messageHead 	= $msg[2];
		$message 	= $msg[3];
			
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
	
	// ******************** Map ********************
	
	// Map (Hintergrundbild) erstellen.
	function printMap($map_image)	{
		include("./etc/config.inc.php");
			$header = "";
			if($header == "0") {
				$this->site[] = '<BODY CLASS="main"  MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0">';
			}
			$this->site[] = '  <DIV CLASS="map">';
			$this->site[] = '   <IMG SRC="./draw.php?map='.$map_image.'">';
	}
	
	// Info-Box erstellen (Java - overLib.js)
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
		}
		$Info .= '\', CAPTION, \''.$Type.'\', SHADOW, WRAP, VAUTO);" onmouseout="return nd();" ';
		return($Info);
	}
	
	//Index der Map feststellen
	function mapCount($map) {
		include("./etc/config.inc.php");
		$Index = array_search($map,$maps);
		//Wenn letze Map, wieder von vorne anfangen
		if (($Index + 1) >= sizeof($maps)) {
			$Index = -1;
		}
		$Index++;
		return($Index);
	}

}
?>
